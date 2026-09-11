# Mailbox management

Admin &rarr; **Mail**. Registers the mailboxes on a company domain and gives the
panel a webmail client for them: read, reply, forward, file, delete, search,
attachments.

The panel is a **client**, not a mail server and not a provisioning tool. It
never creates or deletes a mailbox. Creating one stays a cPanel job; what is
stored here is the address and password the panel signs in with.

---

## Setting it up

### 1. The `imap` extension

Every screen below `Open` needs PHP's `imap` extension.

* **XAMPP** &mdash; uncomment `extension=imap` in `C:\xampp\php\php.ini`, restart Apache.
* **cPanel** &mdash; *Select PHP Version &rarr; Extensions*, tick `imap`.

The mailbox list says so in red if it is missing, so there is no guessing.

### 2. The encryption key

IMAP LOGIN needs the mailbox password in cleartext on every connection, so it
cannot be hashed the way an admin password is. It is AES-256 encrypted instead,
with a key kept out of the repository:

```bash
cp application/config/secrets.sample.php application/config/secrets.php
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"   # paste into mail_crypt_key
```

`secrets.php` is gitignored. It has to be created on each machine and uploaded
to the server by hand, like `cheat.php`.

Changing the key strands every stored password &mdash; they were encrypted under the
old one. Re-enter each mailbox password after a rotation.

### 3. The tables

```bash
mysql -u USER -p DATABASE < database/upgrade_mail.sql
```

Creates `mail_domains`, `mail_accounts` and `mail_access_logs`. Re-runnable.

### 4. Permissions

Five new capabilities, granted per admin account under Admin &rarr; Admins:

| Key | What it allows |
|---|---|
| `mail.view` | See the mailbox list and the access log |
| `mail.manage` | Add, edit and remove mailbox credentials; run the connection test |
| `mail.domains` | Edit domains and their server endpoints |
| `mail.access` | Open a mailbox and read its mail |
| `mail.send` | Send from a mailbox |

A super admin holds all of them by bypass. Existing admin accounts hold **none**
until granted &mdash; the role presets only apply to accounts created afterwards.

`mail.domains` is withheld from the `admin` preset on purpose: whoever can edit
`imap_host` can point the domain at a machine they control and collect every
password the panel sends it.

### 5. A domain, then a mailbox

Admin &rarr; Mail &rarr; **Domains** &rarr; Add. On cPanel hosting the values are under
*Email Accounts &rarr; Connect Devices*, and are usually:

```
IMAP  mail.yourdomain.com : 993  SSL
SMTP  mail.yourdomain.com : 465  SSL
Sent  INBOX.Sent      Trash  INBOX.Trash
```

Then Mail &rarr; **Add Mailbox**: the local part, the domain, and the password the
mailbox was given in cPanel. Saving lands on the connection test, which tries
IMAP and SMTP separately and lists the folders it found &mdash; check the Sent and
Trash names there against what the server actually reports.

---

## How it behaves

* **Folders** come from the server; Inbox, Drafts, Sent, Archive, Junk and Trash
  are recognised and sorted to the top whatever they are called.
* **Deleting** moves to the domain's Trash folder. Deleting *from* Trash erases,
  and says so before it does.
* **Sending** goes out over authenticated SMTP as the mailbox itself, so SPF and
  DKIM pass. A copy is appended to Sent over IMAP afterwards &mdash; SMTP does not
  file one, and that second step can fail on its own without the mail being
  unsent.
* **Replies** carry `In-Reply-To` and `References`, so they continue the
  recipient's thread instead of starting a new one.
* **Searching** uses IMAP SEARCH against the whole message, on the server.

---

## Security

Reading mail from inside an admin session is the sharp edge of this module, and
three things are load-bearing:

**1. The message body never touches the panel's DOM.** Anyone in the world can
send mail to a company address, and it is opened by a session that can approve
withdrawals. The HTML is stripped of scripting constructs (`Mail_sanitizer_lib`)
and *then* rendered inside an `<iframe sandbox>` with neither `allow-scripts`
nor `allow-same-origin`, carrying a CSP of `default-src 'none'`. Any one of the
three failing leaves the other two.

**2. Remote images are blocked until asked for.** Not a code-execution risk, but
loading one tells the sender that the mail was read, by whom, and from where.

**3. Attachments download as opaque bytes.** Always `application/octet-stream`,
always `Content-Disposition: attachment`, never the type the sender claimed, and
never rendered inline on this origin.

Every open, read, send, move, delete and download is written to
`mail_access_logs` with the admin, the mailbox and the IP. Admin &rarr; Mail &rarr;
**Access log**.

### What a database dump gives away

`mail_accounts.password_enc` is reversible by design. A dump alone is not enough
&mdash; the key is in `secrets.php`, not in the database and not in the repo &mdash; but a
dump **plus** filesystem access is every mailbox. Keep `secrets.php` readable
only by the web user, and treat database backups accordingly.
