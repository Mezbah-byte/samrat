<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Two-way encryption for secrets the application has to be able to read back.
 *
 * Passwords for people are hashed, never encrypted - there is no reason to
 * recover them. Mailbox passwords are the exception: IMAP LOGIN and SMTP AUTH
 * both want the cleartext on every connection, so the panel cannot store a
 * digest and still open a mailbox. This library is that narrow exception and
 * nothing else should reach for it.
 *
 * The key comes from `application/config/secrets.php`, which is gitignored.
 * The repository-wide `encryption_key` in config.php is deliberately NOT used:
 * it is committed, so anyone who has ever seen the repo holds it.
 */
class Crypto_lib {

	/** @var CI_Controller */
	protected $CI;

	/** @var string|null raw 32-byte key, resolved once per request */
	protected $key = NULL;

	/** @var string */
	protected $cipher = 'aes-256-cbc';

	public function __construct()
	{
		$this->CI =& get_instance();
	}

	/** Is a usable key configured? Screens warn instead of failing blind. */
	public function ready()
	{
		return $this->key() !== NULL;
	}

	/**
	 * Encrypt a string.
	 *
	 * Output is base64 of iv|hmac|ciphertext. The HMAC is over the ciphertext,
	 * so decrypt() can reject a tampered or truncated row before OpenSSL is
	 * handed anything - an encrypt-then-MAC arrangement rather than trusting
	 * the padding check to notice.
	 *
	 * @return string|false
	 */
	public function encrypt($plain)
	{
		if (($key = $this->key()) === NULL)
		{
			return FALSE;
		}

		$iv = random_bytes(openssl_cipher_iv_length($this->cipher));

		$cipher_text = openssl_encrypt((string) $plain, $this->cipher, $key, OPENSSL_RAW_DATA, $iv);

		if ($cipher_text === FALSE)
		{
			return FALSE;
		}

		$hmac = hash_hmac('sha256', $iv.$cipher_text, $key, TRUE);

		return base64_encode($iv.$hmac.$cipher_text);
	}

	/**
	 * Reverse encrypt(). FALSE for a bad key, a corrupt row, or a payload that
	 * fails its HMAC - all three mean "do not trust this", and the caller
	 * should treat them the same way.
	 *
	 * @return string|false
	 */
	public function decrypt($payload)
	{
		if (($key = $this->key()) === NULL || ! is_string($payload) || $payload === '')
		{
			return FALSE;
		}

		$raw = base64_decode($payload, TRUE);

		if ($raw === FALSE)
		{
			return FALSE;
		}

		$iv_len = openssl_cipher_iv_length($this->cipher);

		// iv + hmac + at least one cipher block.
		if (strlen($raw) <= $iv_len + 32)
		{
			return FALSE;
		}

		$iv          = substr($raw, 0, $iv_len);
		$hmac        = substr($raw, $iv_len, 32);
		$cipher_text = substr($raw, $iv_len + 32);

		$expected = hash_hmac('sha256', $iv.$cipher_text, $key, TRUE);

		if ( ! hash_equals($expected, $hmac))
		{
			return FALSE;
		}

		$plain = openssl_decrypt($cipher_text, $this->cipher, $key, OPENSSL_RAW_DATA, $iv);

		return $plain === FALSE ? FALSE : $plain;
	}

	/**
	 * Resolve the raw key once.
	 *
	 * A missing secrets.php is not fatal - the third argument to config->load()
	 * keeps it quiet - but it does leave every mailbox unusable, which the
	 * calling screen reports rather than dying on.
	 */
	protected function key()
	{
		if ($this->key !== NULL)
		{
			return $this->key === FALSE ? NULL : $this->key;
		}

		$this->CI->config->load('secrets', TRUE, TRUE);
		$secrets = $this->CI->config->item('secrets');

		$hex = (is_array($secrets) && isset($secrets['mail_crypt_key']))
			? trim((string) $secrets['mail_crypt_key'])
			: '';

		if ($hex === '' || strlen($hex) !== 64 || ! ctype_xdigit($hex))
		{
			log_message('error', 'Crypto_lib: mail_crypt_key missing or not 64 hex characters. Mailbox passwords cannot be read.');
			$this->key = FALSE;
			return NULL;
		}

		$this->key = hex2bin($hex);

		return $this->key;
	}
}
