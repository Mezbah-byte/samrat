<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * An IMAP client, wrapped around ext/imap.
 *
 * One instance holds one open connection. Everything the panel does to a
 * mailbox in a request goes through the same stream, because re-authenticating
 * per operation is the single most expensive mistake a webmail screen can make
 * against a remote server.
 *
 * Two conversions happen at this boundary and nowhere else:
 *
 *   - Folder names travel over IMAP in modified UTF-7. The rest of the
 *     application only ever sees UTF-8, so every name is decoded on the way
 *     out and re-encoded on the way in.
 *   - Message bodies arrive in whatever charset and transfer encoding the
 *     sender chose. They leave here as UTF-8.
 *
 * ext/imap emits warnings through its own error stack rather than exceptions.
 * Every entry point drains that stack (see `drain()`) so a failure in one call
 * cannot surface as a spurious error in the next, and so nothing leaks into
 * the rendered page.
 */
class Imap_lib {

	/** @var CI_Controller */
	protected $CI;

	/** @var resource|IMAP\Connection|null */
	protected $stream = NULL;

	/** @var object|null the account row, joined to its domain */
	protected $account = NULL;

	/** @var string current folder, UTF-8 */
	protected $folder = 'INBOX';

	/** @var string last failure, human-readable */
	protected $error = '';

	/** @var array|null folder list cache for this connection */
	protected $folder_cache = NULL;

	/**
	 * Seconds before a dead mail server gives up its hold on the request.
	 *
	 * The default is 60 per phase, which is long enough for one unreachable
	 * host to make the whole admin panel look broken.
	 */
	const TIMEOUT = 12;

	public function __construct()
	{
		$this->CI =& get_instance();
	}

	/** Has ext/imap actually been built into this PHP? */
	public function available()
	{
		return function_exists('imap_open');
	}

	public function error()
	{
		return $this->error;
	}

	public function folder()
	{
		return $this->folder;
	}

	/* ================================================================ */
	/*  Connection                                                      */
	/* ================================================================ */

	/**
	 * Log in and select a folder.
	 *
	 * @param  object $account row from Mail_account_model::with_domain()
	 * @param  string $password cleartext
	 * @param  string $folder   UTF-8 folder path
	 * @return bool
	 */
	public function open($account, $password, $folder = 'INBOX')
	{
		if ( ! $this->available())
		{
			$this->error = 'The PHP imap extension is not enabled on this server, so no mailbox can be opened.';
			return FALSE;
		}

		if ($password === NULL || $password === '')
		{
			$this->error = 'No usable password is stored for this mailbox.';
			return FALSE;
		}

		$this->close();

		$this->account = $account;
		$this->folder  = $folder !== '' ? $folder : 'INBOX';

		imap_timeout(IMAP_OPENTIMEOUT,  self::TIMEOUT);
		imap_timeout(IMAP_READTIMEOUT,  self::TIMEOUT);
		imap_timeout(IMAP_WRITETIMEOUT, self::TIMEOUT);
		imap_timeout(IMAP_CLOSETIMEOUT, self::TIMEOUT);

		$this->drain();

		// Retry count 1: ext/imap otherwise re-attempts a rejected login three
		// times, which on a host that counts failures is a fast way to get the
		// mailbox locked out.
		$stream = @imap_open($this->mailbox_ref($this->folder), $account->email, $password, 0, 1);

		if ($stream === FALSE)
		{
			$this->error = $this->friendly_error($this->drain());
			$this->account = NULL;
			return FALSE;
		}

		$this->stream       = $stream;
		$this->folder_cache = NULL;
		$this->drain();

		return TRUE;
	}

	/**
	 * Move to another folder on the open connection.
	 *
	 * imap_reopen rather than a fresh imap_open: same socket, same login, one
	 * SELECT.
	 */
	public function select($folder)
	{
		if ( ! $this->stream)
		{
			$this->error = 'Not connected.';
			return FALSE;
		}

		if ($folder === $this->folder)
		{
			return TRUE;
		}

		$this->drain();

		if ( ! @imap_reopen($this->stream, $this->mailbox_ref($folder)))
		{
			$this->error = $this->friendly_error($this->drain());
			return FALSE;
		}

		$this->folder = $folder;
		$this->drain();

		return TRUE;
	}

	public function close()
	{
		if ($this->stream)
		{
			// CL_EXPUNGE is deliberately not passed: a message is only ever
			// erased by an explicit purge, never as a side effect of closing.
			@imap_close($this->stream);
			$this->stream = NULL;
		}

		$this->account      = NULL;
		$this->folder_cache = NULL;
		$this->drain();
	}

	public function __destruct()
	{
		$this->close();
	}

	/* ================================================================ */
	/*  Folders                                                         */
	/* ================================================================ */

	/**
	 * Every folder in the mailbox, with its message counts.
	 *
	 * @return array of array(path, name, depth, delimiter, total, unseen, special)
	 */
	public function folders()
	{
		if ($this->folder_cache !== NULL)
		{
			return $this->folder_cache;
		}

		if ( ! $this->stream)
		{
			return array();
		}

		$this->drain();
		$boxes = @imap_getmailboxes($this->stream, $this->server_ref(), '*');
		$this->drain();

		if ( ! is_array($boxes))
		{
			return $this->folder_cache = array();
		}

		$prefix_len = strlen($this->server_ref());
		$out        = array();

		foreach ($boxes as $box)
		{
			// getmailboxes returns the full {server}FOLDER string; only the
			// folder part is ours.
			$raw  = substr($box->name, $prefix_len);
			$path = $this->from_imap_utf7($raw);

			if ($path === '')
			{
				continue;
			}

			// \Noselect marks a container that holds folders but no mail.
			$selectable = ! ($box->attributes & LATT_NOSELECT);
			$delim      = $box->delimiter ?: '.';

			$status = $selectable ? @imap_status($this->stream, $box->name, SA_MESSAGES | SA_UNSEEN) : FALSE;
			$this->drain();

			$parts = explode($delim, $path);

			$out[] = array(
				'path'       => $path,
				'name'       => end($parts),
				'depth'      => max(0, count($parts) - 1),
				'delimiter'  => $delim,
				'selectable' => $selectable,
				'total'      => $status ? (int) $status->messages : 0,
				'unseen'     => $status ? (int) $status->unseen   : 0,
				'special'    => $this->special_kind($path),
			);
		}

		usort($out, array($this, 'compare_folders'));

		return $this->folder_cache = $out;
	}

	/**
	 * Classify a folder so the sidebar can order and ice it.
	 *
	 * The domain's configured Sent and Trash win over the guess, because those
	 * two are the ones the application itself writes to.
	 *
	 * @return string inbox|sent|trash|drafts|junk|archive|''
	 */
	public function special_kind($path)
	{
		$d = $this->account;

		if (strcasecmp($path, 'INBOX') === 0)                          return 'inbox';
		if ($d && strcasecmp($path, (string) $d->sent_folder)  === 0)  return 'sent';
		if ($d && strcasecmp($path, (string) $d->trash_folder) === 0)  return 'trash';

		$leaf = $path;
		if (($pos = strrpos($path, '.')) !== FALSE)
		{
			$leaf = substr($path, $pos + 1);
		}

		$map = array(
			'sent' => 'sent', 'sent items' => 'sent', 'sent messages' => 'sent',
			'trash' => 'trash', 'deleted' => 'trash', 'deleted items' => 'trash', 'deleted messages' => 'trash',
			'drafts' => 'drafts', 'draft' => 'drafts',
			'junk' => 'junk', 'spam' => 'junk', 'junk e-mail' => 'junk', 'bulk mail' => 'junk',
			'archive' => 'archive', 'archives' => 'archive',
		);

		$key = strtolower($leaf);

		return isset($map[$key]) ? $map[$key] : '';
	}

	/** Inbox first, the other well-known folders next, then everything A-Z. */
	protected function compare_folders($a, $b)
	{
		$rank = array('inbox' => 0, 'drafts' => 1, 'sent' => 2, 'archive' => 3, 'junk' => 4, 'trash' => 5);

		$ra = isset($rank[$a['special']]) ? $rank[$a['special']] : 6;
		$rb = isset($rank[$b['special']]) ? $rank[$b['special']] : 6;

		if ($ra !== $rb)
		{
			return $ra - $rb;
		}

		return strcasecmp($a['path'], $b['path']);
	}

	/** Does this folder exist in the mailbox? Guards every folder parameter. */
	public function folder_exists($path)
	{
		foreach ($this->folders() as $f)
		{
			if ($f['path'] === $path)
			{
				return TRUE;
			}
		}

		return FALSE;
	}

	/* ================================================================ */
	/*  Message lists                                                   */
	/* ================================================================ */

	/**
	 * One page of the current folder, newest first.
	 *
	 * The sort happens on the server (IMAP SORT) so that "newest 25" costs one
	 * command instead of pulling every header down to order them here. Servers
	 * that refuse SORT fall back to search-and-reverse, which is the same
	 * result by arrival order rather than by Date: header.
	 *
	 * @param  int    $page   1-based
	 * @param  int    $per
	 * @param  string $search free text, matched against the whole message
	 * @return array array('rows' => array, 'total' => int)
	 */
	public function messages($page = 1, $per = 25, $search = '')
	{
		if ( ! $this->stream)
		{
			return array('rows' => array(), 'total' => 0);
		}

		$criteria = $this->search_criteria($search);

		$this->drain();
		$uids = @imap_sort($this->stream, SORTARRIVAL, TRUE, SE_UID, $criteria, 'UTF-8');

		if ( ! is_array($uids))
		{
			$this->drain();
			$found = @imap_search($this->stream, $criteria !== NULL ? $criteria : 'ALL', SE_UID, 'UTF-8');
			$uids  = is_array($found) ? array_reverse($found) : array();
		}

		$this->drain();

		$total = count($uids);
		$slice = array_slice($uids, max(0, ((int) $page - 1) * (int) $per), (int) $per);

		if (empty($slice))
		{
			return array('rows' => array(), 'total' => $total);
		}

		$overview = @imap_fetch_overview($this->stream, implode(',', $slice), FT_UID);
		$this->drain();

		$rows = array();

		foreach ((array) $overview as $o)
		{
			$rows[] = array(
				'uid'         => (int) $o->uid,
				'subject'     => $this->decode_header(isset($o->subject) ? $o->subject : ''),
				'from'        => $this->decode_header(isset($o->from) ? $o->from : ''),
				'from_email'  => $this->address_of($o, 'from'),
				'to'          => $this->decode_header(isset($o->to) ? $o->to : ''),
				'date'        => isset($o->udate) ? (int) $o->udate : 0,
				'size'        => isset($o->size) ? (int) $o->size : 0,
				'seen'        => ! empty($o->seen),
				'flagged'     => ! empty($o->flagged),
				'answered'    => ! empty($o->answered),
				'draft'       => ! empty($o->draft),
				'attachments' => $this->has_attachment((int) $o->uid),
			);
		}

		// fetch_overview does not promise the order it was asked in.
		$order = array_flip($slice);
		usort($rows, function ($a, $b) use ($order) {
			return $order[$a['uid']] - $order[$b['uid']];
		});

		return array('rows' => $rows, 'total' => $total);
	}

	/**
	 * Free text to an IMAP SEARCH criterion.
	 *
	 * TEXT covers headers and body, which is what someone typing into a mail
	 * search box means. The quoting matters: an unescaped double quote would
	 * otherwise end the string and the remainder would be read as further
	 * criteria.
	 *
	 * @return string|null NULL for "no filter"
	 */
	protected function search_criteria($search)
	{
		$search = trim((string) $search);

		if ($search === '')
		{
			return NULL;
		}

		return 'TEXT "'.str_replace(array('\\', '"'), array('\\\\', '\\"'), $search).'"';
	}

	/**
	 * Does this message carry an attachment?
	 *
	 * One BODYSTRUCTURE per listed message. It is a round trip each, which is
	 * why it is only ever asked for the page on screen and never for the whole
	 * folder.
	 */
	protected function has_attachment($uid)
	{
		$structure = @imap_fetchstructure($this->stream, $uid, FT_UID);
		$this->drain();

		if ( ! $structure || empty($structure->parts))
		{
			return FALSE;
		}

		foreach ($this->flatten_parts($structure) as $part)
		{
			if ($part['is_attachment'])
			{
				return TRUE;
			}
		}

		return FALSE;
	}

	/* ================================================================ */
	/*  One message                                                     */
	/* ================================================================ */

	/**
	 * A whole message, decoded.
	 *
	 * @param  int  $uid
	 * @param  bool $mark_seen FALSE when opening must not change the flag
	 * @return array|false
	 */
	public function message($uid, $mark_seen = TRUE)
	{
		if ( ! $this->stream)
		{
			return FALSE;
		}

		$uid = (int) $uid;

		$this->drain();
		$header_raw = @imap_fetchheader($this->stream, $uid, FT_UID | FT_PREFETCHTEXT);
		$structure  = @imap_fetchstructure($this->stream, $uid, FT_UID);
		$this->drain();

		if ($structure === FALSE)
		{
			$this->error = 'That message is no longer in this folder.';
			return FALSE;
		}

		$overview = @imap_fetch_overview($this->stream, (string) $uid, FT_UID);
		$this->drain();
		$o = ! empty($overview) ? $overview[0] : NULL;

		$headers = $this->parse_headers($header_raw);

		$text = '';
		$html = '';
		$attachments = array();
		$inline      = array();

		foreach ($this->flatten_parts($structure) as $part)
		{
			if ($part['is_attachment'])
			{
				$entry = array(
					'part'     => $part['part'],
					'filename' => $part['filename'],
					'mime'     => $part['mime'],
					'size'     => $part['size'],
					'cid'      => $part['cid'],
				);

				// An inline part with a Content-ID is referenced from the HTML
				// body as cid:..., so it is kept aside to be resolved there
				// rather than listed as a download.
				if ($part['cid'] !== '' && $part['disposition'] === 'inline')
				{
					$inline[$part['cid']] = $entry;
				}
				else
				{
					$attachments[] = $entry;
				}

				continue;
			}

			$body = $this->fetch_part($uid, $part);

			if ($part['mime'] === 'text/html' && $html === '')
			{
				$html = $body;
			}
			elseif ($part['mime'] === 'text/plain' && $text === '')
			{
				$text = $body;
			}
		}

		if ($mark_seen)
		{
			@imap_setflag_full($this->stream, (string) $uid, '\\Seen', ST_UID);
			$this->drain();
		}

		return array(
			'uid'         => $uid,
			'subject'     => isset($headers['subject']) ? $headers['subject'] : '(no subject)',
			'from'        => isset($headers['from']) ? $headers['from'] : '',
			'from_email'  => $this->address_of($o, 'from'),
			'to'          => isset($headers['to']) ? $headers['to'] : '',
			'cc'          => isset($headers['cc']) ? $headers['cc'] : '',
			'reply_to'    => isset($headers['reply-to']) ? $headers['reply-to'] : '',
			'message_id'  => isset($headers['message-id']) ? $headers['message-id'] : '',
			'references'  => isset($headers['references']) ? $headers['references'] : '',
			'date'        => $o ? (int) $o->udate : (isset($headers['date']) ? strtotime($headers['date']) : 0),
			'size'        => $o ? (int) $o->size : 0,
			'seen'        => $o ? ! empty($o->seen) : TRUE,
			'flagged'     => $o ? ! empty($o->flagged) : FALSE,
			'answered'    => $o ? ! empty($o->answered) : FALSE,
			'text'        => $text,
			'html'        => $html,
			'attachments' => $attachments,
			'inline'      => $inline,
		);
	}

	/**
	 * One attachment, decoded.
	 *
	 * @return array|false array(filename, mime, data)
	 */
	public function attachment($uid, $part_id)
	{
		if ( ! $this->stream)
		{
			return FALSE;
		}

		$structure = @imap_fetchstructure($this->stream, (int) $uid, FT_UID);
		$this->drain();

		if ($structure === FALSE)
		{
			return FALSE;
		}

		foreach ($this->flatten_parts($structure) as $part)
		{
			if ($part['part'] === (string) $part_id)
			{
				return array(
					'filename' => $part['filename'] !== '' ? $part['filename'] : 'attachment',
					'mime'     => $part['mime'],
					'data'     => $this->fetch_part($uid, $part, FALSE),
				);
			}
		}

		return FALSE;
	}

	/** The raw RFC822 source, for reply quoting and for forwarding. */
	public function raw_source($uid)
	{
		if ( ! $this->stream)
		{
			return '';
		}

		$this->drain();
		$raw = @imap_fetchheader($this->stream, (int) $uid, FT_UID).@imap_body($this->stream, (int) $uid, FT_UID);
		$this->drain();

		return $raw;
	}

	/* ================================================================ */
	/*  Mutations                                                       */
	/* ================================================================ */

	/**
	 * Set or clear a flag on a set of UIDs.
	 *
	 * @param  array  $uids
	 * @param  string $flag \Seen, \Flagged, \Answered
	 * @param  bool   $on
	 */
	public function flag($uids, $flag, $on = TRUE)
	{
		if ( ! $this->stream || empty($uids))
		{
			return FALSE;
		}

		$list = implode(',', array_map('intval', (array) $uids));

		$this->drain();
		$ok = $on
			? @imap_setflag_full($this->stream, $list, $flag, ST_UID)
			: @imap_clearflag_full($this->stream, $list, $flag, ST_UID);

		if ( ! $ok)
		{
			$this->error = $this->friendly_error($this->drain());
		}

		$this->drain();

		return (bool) $ok;
	}

	/**
	 * Move messages to another folder.
	 *
	 * imap_mail_move leaves the originals flagged \Deleted rather than gone, so
	 * the expunge is what actually completes the move.
	 */
	public function move($uids, $target)
	{
		if ( ! $this->stream || empty($uids))
		{
			return FALSE;
		}

		if ($target === $this->folder)
		{
			return TRUE;
		}

		$list = implode(',', array_map('intval', (array) $uids));

		$this->drain();
		$ok = @imap_mail_move($this->stream, $list, $this->to_imap_utf7($target), CP_UID);

		if ( ! $ok)
		{
			$this->error = $this->friendly_error($this->drain());
			$this->drain();
			return FALSE;
		}

		@imap_expunge($this->stream);
		$this->folder_cache = NULL;
		$this->drain();

		return TRUE;
	}

	/**
	 * Delete messages.
	 *
	 * From anywhere but Trash this is a move to Trash, which is recoverable.
	 * From Trash itself there is nowhere further to go, so it erases - and that
	 * is the only path in this library that destroys mail.
	 */
	public function delete_messages($uids, $trash_folder)
	{
		if ( ! $this->stream || empty($uids))
		{
			return FALSE;
		}

		$in_trash = ($trash_folder === '' || strcasecmp($this->folder, $trash_folder) === 0);

		if ( ! $in_trash && $this->folder_exists($trash_folder))
		{
			return $this->move($uids, $trash_folder);
		}

		$list = implode(',', array_map('intval', (array) $uids));

		$this->drain();
		$ok = @imap_delete($this->stream, $list, FT_UID);

		if ($ok)
		{
			@imap_expunge($this->stream);
		}
		else
		{
			$this->error = $this->friendly_error($this->drain());
		}

		$this->folder_cache = NULL;
		$this->drain();

		return (bool) $ok;
	}

	/** File a raw RFC822 message into a folder - used to keep a Sent copy. */
	public function append($folder, $raw, $flags = '\\Seen')
	{
		if ( ! $this->stream)
		{
			return FALSE;
		}

		$this->drain();
		$ok = @imap_append($this->stream, $this->mailbox_ref($folder), $raw, $flags);

		if ( ! $ok)
		{
			$this->error = $this->friendly_error($this->drain());
		}

		$this->folder_cache = NULL;
		$this->drain();

		return (bool) $ok;
	}

	/* ================================================================ */
	/*  MIME                                                            */
	/* ================================================================ */

	/**
	 * Walk a BODYSTRUCTURE into a flat list of leaf parts.
	 *
	 * IMAP numbers parts positionally - "1", "2.1", "2.2.1" - and that number
	 * is how a part is fetched later, so it is built here rather than
	 * reconstructed.
	 *
	 * A single-part message has no `parts` at all; its body is part "1".
	 */
	protected function flatten_parts($structure, $prefix = '', $out = array())
	{
		if (empty($structure->parts))
		{
			$out[] = $this->describe_part($structure, $prefix === '' ? '1' : $prefix);
			return $out;
		}

		foreach ($structure->parts as $i => $part)
		{
			$number = ($prefix === '') ? (string) ($i + 1) : $prefix.'.'.($i + 1);

			if ( ! empty($part->parts))
			{
				// message/rfc822 wraps a whole nested message; its own parts
				// are numbered from the wrapper, not from a new root.
				$out = $this->flatten_parts($part, $number, $out);
				continue;
			}

			$out[] = $this->describe_part($part, $number);
		}

		return $out;
	}

	/** Normalise one BODYSTRUCTURE node into the shape the rest of this uses. */
	protected function describe_part($part, $number)
	{
		static $types = array('text', 'multipart', 'message', 'application', 'audio', 'image', 'video', 'other');

		$type = isset($types[$part->type]) ? $types[$part->type] : 'other';
		$sub  = isset($part->subtype) ? strtolower($part->subtype) : '';
		$mime = $sub !== '' ? $type.'/'.$sub : $type;

		$params = array();

		foreach (array('parameters', 'dparameters') as $bag)
		{
			if ( ! empty($part->$bag))
			{
				foreach ($part->$bag as $p)
				{
					$params[strtolower($p->attribute)] = $p->value;
				}
			}
		}

		$filename = '';

		foreach (array('filename', 'name') as $key)
		{
			if (isset($params[$key]) && $params[$key] !== '')
			{
				$filename = $this->decode_header($params[$key]);
				break;
			}
		}

		$disposition = isset($part->disposition) ? strtolower($part->disposition) : '';

		// A part is an attachment when it says so, or when it has a filename,
		// or when it is simply not something that could be the message body.
		$is_attachment = ($disposition === 'attachment')
			|| ($filename !== '')
			|| ! ($type === 'text' && in_array($sub, array('plain', 'html'), TRUE));

		$cid = isset($part->id) ? trim($part->id, '<>') : '';

		return array(
			'part'          => $number,
			'mime'          => $mime,
			'type'          => $type,
			'subtype'       => $sub,
			'encoding'      => isset($part->encoding) ? (int) $part->encoding : 0,
			'charset'       => isset($params['charset']) ? $params['charset'] : 'UTF-8',
			'filename'      => $filename,
			'disposition'   => $disposition,
			'cid'           => $cid,
			'size'          => isset($part->bytes) ? (int) $part->bytes : 0,
			'is_attachment' => $is_attachment,
		);
	}

	/**
	 * Fetch and decode one part.
	 *
	 * @param bool $to_utf8 TRUE for text, FALSE for binary - an attachment must
	 *                      come back byte-for-byte, and running it through a
	 *                      charset conversion would corrupt it.
	 */
	protected function fetch_part($uid, $part, $to_utf8 = TRUE)
	{
		$raw = @imap_fetchbody($this->stream, (int) $uid, $part['part'], FT_UID);
		$this->drain();

		if ($raw === FALSE)
		{
			return '';
		}

		switch ($part['encoding'])
		{
			case ENCBASE64:        $raw = base64_decode($raw);          break;
			case ENCQUOTEDPRINTABLE: $raw = quoted_printable_decode($raw); break;
			case ENCBINARY:
			case ENC8BIT:
			case ENC7BIT:
			default:                                                    break;
		}

		return $to_utf8 ? $this->to_utf8($raw, $part['charset']) : $raw;
	}

	/**
	 * Convert to UTF-8 from whatever the part claimed.
	 *
	 * Senders lie about charset often enough that a failed conversion must not
	 * be fatal: a mangled accent is better than a blank message.
	 */
	protected function to_utf8($text, $charset)
	{
		$charset = strtoupper(trim((string) $charset));

		if ($charset === '' || $charset === 'UTF-8' || $charset === 'US-ASCII')
		{
			return mb_check_encoding($text, 'UTF-8') ? $text : mb_convert_encoding($text, 'UTF-8', 'ISO-8859-1');
		}

		$converted = @mb_convert_encoding($text, 'UTF-8', $charset);

		if ($converted === FALSE || $converted === '')
		{
			$converted = @iconv($charset, 'UTF-8//IGNORE', $text);
		}

		return ($converted === FALSE || $converted === '') ? $text : $converted;
	}

	/**
	 * Decode an RFC 2047 encoded-word header into UTF-8.
	 *
	 * imap_mime_header_decode splits a header into runs, each with its own
	 * charset, because one Subject: can legitimately mix them.
	 */
	public function decode_header($value)
	{
		$value = (string) $value;

		if ($value === '')
		{
			return '';
		}

		$out   = '';
		$parts = @imap_mime_header_decode($value);

		if ( ! is_array($parts))
		{
			return $value;
		}

		foreach ($parts as $p)
		{
			$out .= $this->to_utf8($p->text, $p->charset === 'default' ? 'UTF-8' : $p->charset);
		}

		return $out;
	}

	/**
	 * Split a raw header block into a decoded map.
	 *
	 * imap_rfc822_parse_headers gives the same thing as an object, but folded
	 * continuation lines and encoded words still need handling, and the object
	 * drops headers it does not know about.
	 */
	protected function parse_headers($raw)
	{
		$out = array();

		if ( ! is_string($raw) || $raw === '')
		{
			return $out;
		}

		// Unfold: a header continued on the next line starts with whitespace.
		$raw   = preg_replace("/\r\n[ \t]+/", ' ', str_replace("\n", "\r\n", str_replace("\r\n", "\n", $raw)));
		$lines = explode("\r\n", $raw);

		foreach ($lines as $line)
		{
			if ($line === '')
			{
				break;
			}

			if (($colon = strpos($line, ':')) === FALSE)
			{
				continue;
			}

			$key = strtolower(trim(substr($line, 0, $colon)));
			$val = trim(substr($line, $colon + 1));

			if ( ! isset($out[$key]))
			{
				$out[$key] = $this->decode_header($val);
			}
		}

		return $out;
	}

	/** The bare address out of an overview row, without the display name. */
	protected function address_of($overview, $field)
	{
		if ( ! $overview)
		{
			return '';
		}

		$raw = isset($overview->$field) ? $overview->$field : '';

		if ($raw === '' || ! preg_match('/<([^>]+)>/', $raw, $m))
		{
			return trim(strip_tags((string) $raw));
		}

		return trim($m[1]);
	}

	/* ================================================================ */
	/*  Plumbing                                                        */
	/* ================================================================ */

	/** `{host:port/imap/ssl/validate-cert}` - the connection half of a ref. */
	protected function server_ref()
	{
		$d = $this->account;

		$flags = '/imap';

		switch ($d->imap_encryption)
		{
			case 'ssl': $flags .= '/ssl';   break;
			case 'tls': $flags .= '/tls';   break;
			default:    $flags .= '/notls'; break;
		}

		$flags .= $d->imap_validate_cert ? '/validate-cert' : '/novalidate-cert';

		return '{'.$d->imap_host.':'.(int) $d->imap_port.$flags.'}';
	}

	/** Connection ref plus a folder, encoded the way the server wants it. */
	protected function mailbox_ref($folder)
	{
		return $this->server_ref().$this->to_imap_utf7($folder);
	}

	/**
	 * UTF-8 folder name to modified UTF-7.
	 *
	 * Pure ASCII is left alone: mb_convert_encoding would happily round-trip
	 * it, but leaving it untouched keeps the common case free of any chance of
	 * a surprise.
	 */
	protected function to_imap_utf7($folder)
	{
		$folder = (string) $folder;

		if ($folder === '' || ! preg_match('/[^\x20-\x7E]/', $folder))
		{
			return $folder;
		}

		$converted = @mb_convert_encoding($folder, 'UTF7-IMAP', 'UTF-8');

		return $converted === FALSE ? $folder : $converted;
	}

	protected function from_imap_utf7($folder)
	{
		$folder = (string) $folder;

		if ($folder === '' || strpos($folder, '&') === FALSE)
		{
			return $folder;
		}

		$converted = @mb_convert_encoding($folder, 'UTF-8', 'UTF7-IMAP');

		return $converted === FALSE ? $folder : $converted;
	}

	/**
	 * Take everything off the ext/imap error stack and return the last entry.
	 *
	 * The stack is process-global and survives across calls, so anything left
	 * on it would be reported as the cause of some later, unrelated failure.
	 */
	protected function drain()
	{
		$errors = @imap_errors();
		@imap_alerts();

		return is_array($errors) && ! empty($errors) ? end($errors) : '';
	}

	/**
	 * Turn an IMAP error into something an admin can act on.
	 *
	 * The raw strings are written for a mail administrator; the person reading
	 * this screen wants to know whether to fix the password or the host.
	 */
	protected function friendly_error($raw)
	{
		$raw = trim((string) $raw);

		if ($raw === '')
		{
			return 'Could not reach the mail server.';
		}

		$low = strtolower($raw);

		if (strpos($low, 'authentication fail') !== FALSE
			|| strpos($low, 'invalid credentials') !== FALSE
			|| strpos($low, 'login failure') !== FALSE
			|| strpos($low, 'authentication failed') !== FALSE)
		{
			return 'The mail server rejected the username or password.';
		}

		if (strpos($low, 'certificate') !== FALSE)
		{
			return 'The mail server certificate was not accepted. Check the host name matches the certificate.';
		}

		if (strpos($low, 'timed out') !== FALSE || strpos($low, 'connection refused') !== FALSE
			|| strpos($low, 'no route') !== FALSE || strpos($low, 'can\'t connect') !== FALSE)
		{
			return 'Could not connect to the mail server. Check the host, the port and that outbound IMAP is not blocked.';
		}

		if (strpos($low, 'no such mailbox') !== FALSE || strpos($low, 'unknown mailbox') !== FALSE
			|| strpos($low, "mailbox doesn't exist") !== FALSE || strpos($low, 'mailbox does not exist') !== FALSE)
		{
			return 'That folder does not exist on the server.';
		}

		return $raw;
	}
}
