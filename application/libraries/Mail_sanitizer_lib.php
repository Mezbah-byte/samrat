<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Makes the HTML body of a received message safe to put on an admin page.
 *
 * A mail body is attacker-controlled text. Anyone in the world can send one to
 * a company address, and it is opened by a session that can approve deposits
 * and edit admin accounts - so a single unescaped `<script>` here is a
 * full compromise of the panel, triggered by nothing more than reading mail.
 *
 * Three defences, deliberately overlapping, because each one alone has known
 * gaps:
 *
 *   1. This class strips scripting constructs out of the markup.
 *   2. The view renders the result inside a sandboxed <iframe srcdoc>, so even
 *      markup that slipped past step 1 executes in an origin of its own with
 *      no access to the panel's DOM, cookies or storage.
 *   3. That iframe carries a restrictive CSP that forbids script entirely.
 *
 * Remote images are held back separately. They are not a code-execution risk,
 * but loading one tells the sender that the mail was read, by whom and from
 * where, so they stay blocked until the reader asks for them.
 */
class Mail_sanitizer_lib {

	/** Elements removed along with everything inside them. */
	protected $strip_tree = array(
		'script', 'style', 'iframe', 'frame', 'frameset', 'object', 'embed',
		'applet', 'template', 'noscript', 'svg', 'math', 'form', 'button',
		'input', 'select', 'textarea', 'base',
	);

	/** Elements removed while their contents are kept. */
	protected $strip_tag_only = array('link', 'meta', 'title', 'head');

	/** @var bool did the last run hold back a remote image? */
	protected $blocked_images = FALSE;

	public function blocked_images()
	{
		return $this->blocked_images;
	}

	/**
	 * Clean a message body for display.
	 *
	 * @param  string $html        raw HTML from the message
	 * @param  bool   $show_images load remote images this time
	 * @param  array  $inline      cid => URL, for images carried in the message
	 * @return string
	 */
	public function clean($html, $show_images = FALSE, $inline = array())
	{
		$this->blocked_images = FALSE;

		$html = (string) $html;

		if (trim($html) === '')
		{
			return '';
		}

		// Comments can hide markup from a regex pass and are never wanted, and
		// conditional comments are markup as far as IE-era mail is concerned.
		$html = preg_replace('/<!--.*?-->/s', '', $html);

		foreach ($this->strip_tree as $tag)
		{
			$html = preg_replace('#<'.$tag.'\b[^>]*>.*?</'.$tag.'\s*>#is', '', $html);
			// An unclosed one would otherwise survive the pair-matching pass.
			$html = preg_replace('#</?'.$tag.'\b[^>]*>#is', '', $html);
		}

		foreach ($this->strip_tag_only as $tag)
		{
			$html = preg_replace('#</?'.$tag.'\b[^>]*>#is', '', $html);
		}

		// Event handlers, in every quoting style including unquoted.
		$html = preg_replace('/\son[a-z-]+\s*=\s*"[^"]*"/i', '', $html);
		$html = preg_replace("/\son[a-z-]+\s*=\s*'[^']*'/i", '', $html);
		$html = preg_replace('/\son[a-z-]+\s*=\s*[^\s>]+/i', '', $html);

		// Scheme-bearing attributes. `javascript:`, `vbscript:` and `data:` in
		// an href are all script execution on click; the whitespace class
		// catches `java\nscript:` padding.
		$html = preg_replace_callback(
			'/\s(href|src|action|formaction|background|poster|xlink:href)\s*=\s*("([^"]*)"|\'([^\']*)\'|([^\s>]+))/i',
			array($this, 'clean_url_attribute'),
			$html
		);

		// `style` can execute through url(javascript:) and expression() in old
		// renderers, and can also position content over the surrounding page.
		$html = preg_replace_callback(
			'/\sstyle\s*=\s*("([^"]*)"|\'([^\']*)\')/i',
			array($this, 'clean_style_attribute'),
			$html
		);

		$html = $this->resolve_inline_images($html, $inline);

		if ( ! $show_images)
		{
			$html = $this->block_remote_images($html);
		}

		// Anything still linked should open away from the panel and must not
		// hand the destination a referrer or a window handle.
		$html = preg_replace('/<a\b/i', '<a target="_blank" rel="noopener noreferrer nofollow"', $html);

		return $html;
	}

	/** Drop an attribute whose value carries an executable scheme. */
	protected function clean_url_attribute($m)
	{
		$attr  = strtolower($m[1]);
		$value = isset($m[3]) && $m[3] !== '' ? $m[3] : (isset($m[4]) && $m[4] !== '' ? $m[4] : (isset($m[5]) ? $m[5] : ''));

		if ($this->is_dangerous_url($value))
		{
			return ' data-stripped-'.$attr.'="1"';
		}

		return $m[0];
	}

	/**
	 * Is this URL an execution vector?
	 *
	 * Entities and stray whitespace are normalised first, because
	 * `j&#97;vascript:` and `java script:` both still run.
	 */
	protected function is_dangerous_url($url)
	{
		$u = html_entity_decode((string) $url, ENT_QUOTES | ENT_HTML5, 'UTF-8');
		$u = preg_replace('/[\s\x00-\x1F\x7F]+/', '', $u);
		$u = strtolower($u);

		foreach (array('javascript:', 'vbscript:', 'livescript:', 'mocha:', 'about:') as $scheme)
		{
			if (strpos($u, $scheme) === 0)
			{
				return TRUE;
			}
		}

		// data: is allowed only for an image, which is how mail clients embed
		// small logos. data:text/html is a document with script in it.
		if (strpos($u, 'data:') === 0 && strpos($u, 'data:image/') !== 0)
		{
			return TRUE;
		}

		return FALSE;
	}

	/** Strip the executable and layout-breaking bits out of an inline style. */
	protected function clean_style_attribute($m)
	{
		$css = isset($m[2]) && $m[2] !== '' ? $m[2] : (isset($m[3]) ? $m[3] : '');

		$css = preg_replace('/expression\s*\(/i', '', $css);
		$css = preg_replace('/url\s*\(\s*[\'"]?\s*(javascript|vbscript|data:text)/i', 'url(about:blank', $css);
		$css = preg_replace('/position\s*:\s*(fixed|sticky)/i', 'position:static', $css);
		$css = str_replace('"', '', $css);

		return ' style="'.$css.'"';
	}

	/**
	 * Point `cid:` references at the attachment route.
	 *
	 * An HTML mail with an embedded logo refers to it by Content-ID. Serving it
	 * from our own URL keeps the image working without inlining megabytes of
	 * base64 into the page.
	 *
	 * @param array $inline cid => already-escaped URL
	 */
	protected function resolve_inline_images($html, $inline)
	{
		if (empty($inline))
		{
			return $html;
		}

		return preg_replace_callback(
			'/(\ssrc\s*=\s*)("cid:([^"]+)"|\'cid:([^\']+)\')/i',
			function ($m) use ($inline) {
				$cid = isset($m[3]) && $m[3] !== '' ? $m[3] : (isset($m[4]) ? $m[4] : '');
				$cid = trim($cid, '<> ');

				return isset($inline[$cid]) ? $m[1].'"'.$inline[$cid].'"' : $m[1].'""';
			},
			$html
		);
	}

	/**
	 * Park remote image URLs on a data attribute.
	 *
	 * The markup stays intact so that one click can put every URL back, which
	 * is what the "Show images" control does.
	 */
	protected function block_remote_images($html)
	{
		return preg_replace_callback(
			'/<img\b([^>]*)>/i',
			function ($m) {
				$attrs = $m[1];

				if ( ! preg_match('/\ssrc\s*=\s*("|\')?\s*https?:/i', $attrs))
				{
					return $m[0];
				}

				$this->blocked_images = TRUE;

				return '<img'.preg_replace('/\ssrc\s*=/i', ' data-blocked-src=', $attrs).'>';
			},
			$html
		);
	}

	/**
	 * Render a plain-text body as HTML.
	 *
	 * Escaped first, so the text branch cannot be a way around everything
	 * above, then bare URLs are made clickable and quoted lines are marked so
	 * the reply history reads as history.
	 */
	public function text_to_html($text)
	{
		$safe = html_escape((string) $text);

		$safe = preg_replace_callback(
			'~\b(https?://[^\s<>"\']+)~i',
			function ($m) {
				return '<a href="'.$m[1].'" target="_blank" rel="noopener noreferrer nofollow">'.$m[1].'</a>';
			},
			$safe
		);

		$out = '';

		foreach (preg_split("/\r\n|\n|\r/", $safe) as $line)
		{
			$quoted = (strpos(ltrim($line), '&gt;') === 0);
			$out .= $quoted
				? '<span class="mail-quote">'.$line.'</span>'."\n"
				: $line."\n";
		}

		return '<pre class="mail-plain">'.$out.'</pre>';
	}
}
