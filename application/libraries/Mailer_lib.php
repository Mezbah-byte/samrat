<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Sends mail as one of the registered mailboxes.
 *
 * Every message goes out over authenticated SMTP as the mailbox itself, never
 * through PHP's `mail()`. That is not a style preference: mail claiming to be
 * from a domain but posted by an unrelated server fails SPF and DKIM and lands
 * in spam, and on shared hosting it is often refused outright.
 *
 * The assembled message is handed back to the caller so a copy can be filed in
 * the mailbox's Sent folder over IMAP. SMTP does not do that - a sent message
 * exists nowhere until something puts it there.
 */
class Mailer_lib {

	/** @var CI_Controller */
	protected $CI;

	/** @var string */
	protected $error = '';

	/** @var string the raw message from the last successful send */
	protected $raw = '';

	/** Seconds to wait on the SMTP conversation before giving up. */
	const TIMEOUT = 15;

	public function __construct()
	{
		$this->CI =& get_instance();
		$this->CI->load->library('email');
	}

	public function error()
	{
		return $this->error;
	}

	/** The sent bytes, for filing into Sent. Empty when the send failed. */
	public function raw()
	{
		return $this->raw;
	}

	/**
	 * Send one message.
	 *
	 * @param object $account row from Mail_account_model::with_domain()
	 * @param string $password cleartext SMTP password
	 * @param array  $msg      to, cc, bcc, subject, body, attachments,
	 *                         in_reply_to, references
	 * @return bool
	 */
	public function send($account, $password, $msg)
	{
		$this->error = '';
		$this->raw   = '';

		if ($password === NULL || $password === '')
		{
			$this->error = 'No usable password is stored for this mailbox.';
			return FALSE;
		}

		$to = $this->addresses(isset($msg['to']) ? $msg['to'] : '');

		if (empty($to))
		{
			$this->error = 'There is nobody to send this to.';
			return FALSE;
		}

		$this->CI->email->clear(TRUE);
		$this->CI->email->initialize($this->config_for($account, $password));

		$this->CI->email->from($account->email, $account->display_name ?: $account->email);
		$this->CI->email->reply_to($account->email, $account->display_name ?: $account->email);
		$this->CI->email->to($to);

		if ($cc = $this->addresses(isset($msg['cc']) ? $msg['cc'] : ''))
		{
			$this->CI->email->cc($cc);
		}

		if ($bcc = $this->addresses(isset($msg['bcc']) ? $msg['bcc'] : ''))
		{
			$this->CI->email->bcc($bcc);
		}

		$this->CI->email->subject(isset($msg['subject']) ? $msg['subject'] : '');
		$this->CI->email->message(isset($msg['body']) ? $msg['body'] : '');

		// The plain-text alternative is not optional in practice: an HTML-only
		// message scores badly with every spam filter worth the name.
		$this->CI->email->set_alt_message($this->to_plain(isset($msg['body']) ? $msg['body'] : ''));

		// Threading headers. Without these a reply starts a new conversation in
		// the recipient's client instead of continuing the one they wrote in.
		if ( ! empty($msg['in_reply_to']))
		{
			$this->CI->email->set_header('In-Reply-To', $msg['in_reply_to']);
			$this->CI->email->set_header('References', trim(
				(isset($msg['references']) ? $msg['references'].' ' : '').$msg['in_reply_to']
			));
		}

		foreach ((array) (isset($msg['attachments']) ? $msg['attachments'] : array()) as $a)
		{
			// Buffered form: the bytes are already in memory from the upload,
			// and passing a path would mean trusting a client-supplied name.
			$this->CI->email->attach($a['content'], 'attachment', $a['name'], $a['mime']);
		}

		// FALSE keeps the assembled message alive so raw_message() can read it.
		if ( ! $this->CI->email->send(FALSE))
		{
			$this->error = $this->friendly_error($this->CI->email->print_debugger(array('headers')));
			$this->CI->email->clear(TRUE);
			return FALSE;
		}

		$this->raw = $this->CI->email->raw_message();
		$this->CI->email->clear(TRUE);

		return TRUE;
	}

	/**
	 * Test that the SMTP server accepts these credentials, without sending.
	 *
	 * There is no way to ask CI_Email to connect and stop, so the conversation
	 * is held directly: EHLO, upgrade if asked for, AUTH LOGIN, QUIT. It is the
	 * same handshake a real send starts with, which is the point - a test that
	 * exercised a different path would not prove anything.
	 */
	public function test($account, $password)
	{
		$this->error = '';

		$host    = $account->smtp_host;
		$port    = (int) $account->smtp_port;
		$crypto  = $account->smtp_encryption;
		// The port stays out of the host string - fsockopen reads a `host:port`
		// first argument as a hostname in its entirety and never connects.
		$remote  = ($crypto === 'ssl' ? 'ssl://' : '').$host;
		$errno   = 0;
		$errstr  = '';

		$socket = @fsockopen($remote, $port > 0 ? $port : 25, $errno, $errstr, self::TIMEOUT);

		if ( ! $socket)
		{
			$this->error = 'Could not connect to '.$host.':'.$port.' - '.($errstr !== '' ? $errstr : 'no response').'.';
			return FALSE;
		}

		stream_set_timeout($socket, self::TIMEOUT);

		$reply = $this->smtp_read($socket);

		if (strpos($reply, '220') !== 0)
		{
			$this->error = 'The server did not greet us: '.$this->first_line($reply);
			fclose($socket);
			return FALSE;
		}

		$this->smtp_write($socket, 'EHLO '.$this->hostname());
		$reply = $this->smtp_read($socket);

		if ($crypto === 'tls')
		{
			$this->smtp_write($socket, 'STARTTLS');
			$reply = $this->smtp_read($socket);

			if (strpos($reply, '220') !== 0)
			{
				$this->error = 'The server refused STARTTLS: '.$this->first_line($reply);
				fclose($socket);
				return FALSE;
			}

			if ( ! @stream_socket_enable_crypto($socket, TRUE, STREAM_CRYPTO_METHOD_TLS_CLIENT))
			{
				$this->error = 'The TLS handshake failed. Check the port and the certificate.';
				fclose($socket);
				return FALSE;
			}

			$this->smtp_write($socket, 'EHLO '.$this->hostname());
			$reply = $this->smtp_read($socket);
		}

		$this->smtp_write($socket, 'AUTH LOGIN');
		$reply = $this->smtp_read($socket);

		if (strpos($reply, '334') !== 0)
		{
			$this->error = 'The server would not start authentication: '.$this->first_line($reply);
			fclose($socket);
			return FALSE;
		}

		$this->smtp_write($socket, base64_encode($account->email));
		$this->smtp_read($socket);
		$this->smtp_write($socket, base64_encode($password));
		$reply = $this->smtp_read($socket);

		$this->smtp_write($socket, 'QUIT');
		fclose($socket);

		if (strpos($reply, '235') !== 0)
		{
			$this->error = 'SMTP rejected the password: '.$this->first_line($reply);
			return FALSE;
		}

		return TRUE;
	}

	/* ================================================================ */

	/**
	 * CI_Email settings for one account.
	 *
	 * `smtp_crypto` is only set for STARTTLS; implicit SSL is expressed by the
	 * `ssl://` scheme on the host, and setting both makes CI attempt to upgrade
	 * a connection that is already encrypted.
	 */
	protected function config_for($account, $password)
	{
		$config = array(
			'protocol'     => 'smtp',
			'smtp_host'    => ($account->smtp_encryption === 'ssl' ? 'ssl://' : '').$account->smtp_host,
			'smtp_port'    => (int) $account->smtp_port,
			'smtp_user'    => $account->email,
			'smtp_pass'    => $password,
			'smtp_timeout' => self::TIMEOUT,
			'smtp_keepalive' => FALSE,
			'mailtype'     => 'html',
			'charset'      => 'utf-8',
			'wordwrap'     => FALSE,
			'validate'     => TRUE,
			// RFC 5322 line endings. CI defaults to "\n", which some servers
			// accept and others silently mangle the message over.
			'newline'      => "\r\n",
			'crlf'         => "\r\n",
		);

		if ($account->smtp_encryption === 'tls')
		{
			$config['smtp_crypto'] = 'tls';
		}

		return $config;
	}

	/**
	 * Split a recipient field into addresses CI_Email will accept.
	 *
	 * Admins separate with commas, semicolons and newlines interchangeably, and
	 * paste `Name <a@b.com>` out of other clients.
	 */
	public function addresses($raw)
	{
		$parts = preg_split('/[,;\r\n]+/', (string) $raw);
		$out   = array();

		foreach ($parts as $part)
		{
			$part = trim($part);

			if ($part === '')
			{
				continue;
			}

			if (preg_match('/<([^>]+)>/', $part, $m))
			{
				$part = trim($m[1]);
			}

			if (filter_var($part, FILTER_VALIDATE_EMAIL))
			{
				$out[] = $part;
			}
		}

		return array_values(array_unique($out));
	}

	/** Everything in the field that is not a usable address. */
	public function rejected_addresses($raw)
	{
		$parts = preg_split('/[,;\r\n]+/', (string) $raw);
		$bad   = array();

		foreach ($parts as $part)
		{
			$part = trim($part);

			if ($part === '')
			{
				continue;
			}

			$addr = preg_match('/<([^>]+)>/', $part, $m) ? trim($m[1]) : $part;

			if ( ! filter_var($addr, FILTER_VALIDATE_EMAIL))
			{
				$bad[] = $part;
			}
		}

		return $bad;
	}

	/** A readable plain-text alternative for an HTML body. */
	protected function to_plain($html)
	{
		$text = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', '', (string) $html);
		$text = preg_replace('#<br\s*/?>#i', "\n", $text);
		$text = preg_replace('#</(p|div|tr|h[1-6]|li)>#i', "\n", $text);
		$text = strip_tags($text);
		$text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
		$text = preg_replace("/\n{3,}/", "\n\n", $text);

		return trim($text);
	}

	protected function hostname()
	{
		$host = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost';

		return preg_replace('/[^A-Za-z0-9.\-]/', '', $host) ?: 'localhost';
	}

	protected function smtp_write($socket, $line)
	{
		fwrite($socket, $line."\r\n");
	}

	/** Read one SMTP reply, following multi-line continuations. */
	protected function smtp_read($socket)
	{
		$data = '';

		while ($line = fgets($socket, 512))
		{
			$data .= $line;

			// "250-" continues, "250 " ends.
			if (strlen($line) > 3 && $line[3] === ' ')
			{
				break;
			}

			$meta = stream_get_meta_data($socket);

			if ( ! empty($meta['timed_out']))
			{
				break;
			}
		}

		return $data;
	}

	protected function first_line($reply)
	{
		$lines = preg_split("/\r\n|\n/", trim((string) $reply));

		return $lines[0] !== '' ? $lines[0] : 'no response';
	}

	/**
	 * CI_Email's debugger is a wall of markup aimed at a developer. Pull the
	 * part an admin can act on out of it.
	 */
	protected function friendly_error($debug)
	{
		$text = trim(html_entity_decode(strip_tags((string) $debug), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
		$low  = strtolower($text);

		if (strpos($low, 'authentication') !== FALSE || strpos($low, '535') !== FALSE)
		{
			return 'SMTP rejected the mailbox password.';
		}

		if (strpos($low, 'unable to connect') !== FALSE || strpos($low, 'connection refused') !== FALSE
			|| strpos($low, 'timed out') !== FALSE)
		{
			return 'Could not connect to the SMTP server. Check the host, the port and that outbound SMTP is not blocked.';
		}

		if (strpos($low, 'relay') !== FALSE)
		{
			return 'The server refused to relay this message. It usually means the From address is not one it hosts.';
		}

		if ($text === '')
		{
			return 'The message could not be sent.';
		}

		// The debugger repeats the whole conversation; the first few lines
		// carry the refusal and the rest is noise.
		$lines = array_slice(array_filter(preg_split("/\n+/", $text), 'strlen'), 0, 4);

		return implode(' ', $lines);
	}
}
