<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CI_Email, with the assembled message readable after the fact.
 *
 * Sending a mail and filing a copy in the Sent folder are two different
 * operations against two different servers, and the second one needs the exact
 * bytes the first one transmitted. CI_Email builds those bytes but keeps them
 * to itself, so the only alternative would be to re-assemble the MIME by hand
 * and hope the two agree - which they would stop doing the first time an
 * attachment or a non-ASCII subject was involved.
 *
 * Nothing here changes how mail is sent.
 */
class MY_Email extends CI_Email {

	/**
	 * The full RFC822 message, headers and body, as handed to the SMTP server.
	 *
	 * Only meaningful between a `send(FALSE)` and the next `clear()`: with the
	 * default auto-clear, both halves are empty by the time send() returns.
	 *
	 * @return string
	 */
	public function raw_message()
	{
		return $this->_header_str.$this->_finalbody;
	}
}
