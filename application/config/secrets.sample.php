<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Secrets that must not enter the repository.
 *
 * Copy this file to `secrets.php` on every machine and every server. That
 * copy is gitignored; this sample is the only version git ever sees.
 *
 * Crypto_lib reads `mail_crypt_key` to encrypt and decrypt the mailbox
 * passwords in `mail_accounts`. Those passwords have to be recoverable - IMAP
 * LOGIN wants the cleartext on every connection - so this key is the only
 * thing standing between a stolen database dump and every company mailbox.
 *
 * Generate a fresh one per environment:
 *
 *   php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
 *
 * Changing the key strands every stored password: they were encrypted under
 * the old one and will no longer decrypt. Re-enter each mailbox password after
 * a rotation.
 */

/* 64 hex characters = 32 raw bytes = AES-256. */
$config['mail_crypt_key'] = '12c64b423f1126252737accb999c6950bafa2619458703cbd3f2ba76cfe3c5f8';
