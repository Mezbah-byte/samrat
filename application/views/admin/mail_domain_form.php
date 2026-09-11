<a href="<?php echo base_url('admin/mail-domains'); ?>" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> All domains</a>

<div class="row justify-content-center">
  <div class="col-lg-9">
    <div class="card">
      <div class="card-header"><i class="bi bi-globe2"></i> <?php echo $mode === 'edit' ? 'Edit Mail Domain' : 'New Mail Domain'; ?></div>
      <div class="card-body">
        <?php echo validation_errors('<div class="alert alert-danger py-2 small">', '</div>'); ?>

        <div class="alert alert-secondary py-2 small mb-3">
          On cPanel hosting these are usually <span class="mono">mail.yourdomain.com</span> on ports
          <span class="mono">993</span> (IMAP, SSL) and <span class="mono">465</span> (SMTP, SSL).
          The exact values are in cPanel under <em>Email Accounts &rarr; Connect Devices</em>.
        </div>

        <?php echo form_open($mode === 'edit' ? 'admin/mail-domains/edit/'.$m->id : 'admin/mail-domains/create'); ?>
          <div class="row g-3">

            <div class="col-md-8">
              <label class="form-label">Domain <span class="text-danger">*</span></label>
              <input type="text" name="domain" class="form-control mono" value="<?php echo set_value('domain', $m->domain); ?>" placeholder="yourdomain.com" required maxlength="190">
              <div class="form-text">The part after the <span class="mono">@</span>. A pasted URL or a leading <span class="mono">@</span> is trimmed for you.</div>
            </div>

            <div class="col-md-4">
              <label class="form-label">Status</label>
              <select name="status" class="form-select">
                <option value="active" <?php echo set_select('status', 'active', $m->status === 'active'); ?>>Active</option>
                <option value="inactive" <?php echo set_select('status', 'inactive', $m->status === 'inactive'); ?>>Inactive</option>
              </select>
              <div class="form-text">Inactive hides the domain when adding a mailbox.</div>
            </div>

            <div class="col-12"><hr class="my-1"><span class="fw-semibold small text-uppercase text-muted">Incoming &mdash; IMAP</span></div>

            <div class="col-md-6">
              <label class="form-label">IMAP host <span class="text-danger">*</span></label>
              <input type="text" name="imap_host" class="form-control mono" value="<?php echo set_value('imap_host', $m->imap_host); ?>" placeholder="mail.yourdomain.com" required maxlength="190">
            </div>

            <div class="col-md-3">
              <label class="form-label">Port <span class="text-danger">*</span></label>
              <input type="number" name="imap_port" class="form-control" value="<?php echo set_value('imap_port', $m->imap_port); ?>" required min="1" max="65535">
            </div>

            <div class="col-md-3">
              <label class="form-label">Encryption</label>
              <select name="imap_encryption" class="form-select">
                <option value="ssl"  <?php echo set_select('imap_encryption', 'ssl',  $m->imap_encryption === 'ssl');  ?>>SSL (993)</option>
                <option value="tls"  <?php echo set_select('imap_encryption', 'tls',  $m->imap_encryption === 'tls');  ?>>STARTTLS (143)</option>
                <option value="none" <?php echo set_select('imap_encryption', 'none', $m->imap_encryption === 'none'); ?>>None (143)</option>
              </select>
            </div>

            <div class="col-12">
              <div class="form-check">
                <input type="checkbox" class="form-check-input" id="validateCert" name="imap_validate_cert" value="1" <?php echo set_checkbox('imap_validate_cert', '1', (bool) $m->imap_validate_cert); ?>>
                <label class="form-check-label" for="validateCert">Validate the server certificate</label>
              </div>
              <div class="form-text text-danger-emphasis">
                Leave this on. Turning it off makes the connection accept any certificate, which means an
                attacker on the network can impersonate the mail server and collect every mailbox password
                the panel sends it. Only unset it for a host you control that uses a self-signed certificate.
              </div>
            </div>

            <div class="col-12"><hr class="my-1"><span class="fw-semibold small text-uppercase text-muted">Outgoing &mdash; SMTP</span></div>

            <div class="col-md-6">
              <label class="form-label">SMTP host <span class="text-danger">*</span></label>
              <input type="text" name="smtp_host" class="form-control mono" value="<?php echo set_value('smtp_host', $m->smtp_host); ?>" placeholder="mail.yourdomain.com" required maxlength="190">
            </div>

            <div class="col-md-3">
              <label class="form-label">Port <span class="text-danger">*</span></label>
              <input type="number" name="smtp_port" class="form-control" value="<?php echo set_value('smtp_port', $m->smtp_port); ?>" required min="1" max="65535">
            </div>

            <div class="col-md-3">
              <label class="form-label">Encryption</label>
              <select name="smtp_encryption" class="form-select">
                <option value="ssl"  <?php echo set_select('smtp_encryption', 'ssl',  $m->smtp_encryption === 'ssl');  ?>>SSL (465)</option>
                <option value="tls"  <?php echo set_select('smtp_encryption', 'tls',  $m->smtp_encryption === 'tls');  ?>>STARTTLS (587)</option>
                <option value="none" <?php echo set_select('smtp_encryption', 'none', $m->smtp_encryption === 'none'); ?>>None (25)</option>
              </select>
            </div>

            <div class="col-12"><hr class="my-1"><span class="fw-semibold small text-uppercase text-muted">Folders</span></div>

            <div class="col-md-6">
              <label class="form-label">Sent folder <span class="text-danger">*</span></label>
              <input type="text" name="sent_folder" class="form-control mono" value="<?php echo set_value('sent_folder', $m->sent_folder); ?>" required maxlength="120">
              <div class="form-text">Where a copy of outgoing mail is filed. cPanel uses <span class="mono">INBOX.Sent</span>; some servers use plain <span class="mono">Sent</span>.</div>
            </div>

            <div class="col-md-6">
              <label class="form-label">Trash folder <span class="text-danger">*</span></label>
              <input type="text" name="trash_folder" class="form-control mono" value="<?php echo set_value('trash_folder', $m->trash_folder); ?>" required maxlength="120">
              <div class="form-text">Deleting a message moves it here rather than erasing it. Deleting from here is final.</div>
            </div>

            <div class="col-12">
              <label class="form-label">Note</label>
              <input type="text" name="note" class="form-control" value="<?php echo set_value('note', $m->note); ?>" placeholder="Main company domain" maxlength="255">
            </div>

          </div>

          <button class="btn btn-primary mt-3"><i class="bi bi-check2"></i> <?php echo $mode === 'edit' ? 'Save Changes' : 'Add Domain'; ?></button>
          <a href="<?php echo base_url('admin/mail-domains'); ?>" class="btn btn-outline-secondary mt-3">Cancel</a>
        <?php echo form_close(); ?>
      </div>
    </div>
  </div>
</div>
