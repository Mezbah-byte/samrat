<a href="<?php echo base_url('admin/mail'); ?>" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> All mailboxes</a>

<div class="row justify-content-center">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header"><i class="bi bi-envelope-at"></i> <?php echo $mode === 'edit' ? 'Edit Mailbox' : 'New Mailbox'; ?></div>
      <div class="card-body">
        <?php echo validation_errors('<div class="alert alert-danger py-2 small">', '</div>'); ?>

        <?php if ($mode === 'create'): ?>
        <div class="alert alert-warning py-2 small">
          <i class="bi bi-info-circle"></i>
          Create the mailbox in cPanel first &mdash; <em>Email Accounts &rarr; Create</em>. This form only stores the
          address and password so the panel can sign in to it.
        </div>
        <?php endif; ?>

        <?php echo form_open($mode === 'edit' ? 'admin/mail/edit/'.$m->id : 'admin/mail/create'); ?>
          <div class="row g-3">

            <div class="col-md-6">
              <label class="form-label">Mailbox name <span class="text-danger">*</span></label>
              <input type="text" name="local_part" class="form-control mono" value="<?php echo set_value('local_part', $m->local_part); ?>" placeholder="support" required maxlength="120">
              <div class="form-text">The part before the <span class="mono">@</span>. Paste a whole address and the domain part is dropped.</div>
            </div>

            <div class="col-md-6">
              <label class="form-label">Domain <span class="text-danger">*</span></label>
              <select name="domain_id" class="form-select" required>
                <option value="">Choose a domain</option>
                <?php foreach ($domains as $d): ?>
                  <option value="<?php echo (int) $d->id; ?>" <?php echo set_select('domain_id', $d->id, (int) $m->domain_id === (int) $d->id); ?>>
                    @<?php echo html_escape($d->domain); ?><?php echo $d->status !== 'active' ? ' (inactive)' : ''; ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <div class="form-text">The server settings come from the domain.</div>
            </div>

            <div class="col-md-6">
              <label class="form-label">Mailbox password <?php echo $mode === 'create' ? '<span class="text-danger">*</span>' : ''; ?></label>
              <input type="password" name="password" class="form-control" autocomplete="new-password" <?php echo $mode === 'create' ? 'required' : ''; ?> placeholder="<?php echo $mode === 'edit' ? 'Leave blank to keep the stored password' : ''; ?>">
              <div class="form-text">
                <?php if ($mode === 'edit'): ?>
                  Blank leaves the stored password alone. Type a new one only after changing it in cPanel.
                <?php else: ?>
                  The password you set on the mailbox in cPanel. Stored encrypted, and needed on every connection.
                <?php endif; ?>
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label">Display name</label>
              <input type="text" name="display_name" class="form-control" value="<?php echo set_value('display_name', $m->display_name); ?>" placeholder="Support Team" maxlength="120">
              <div class="form-text">The name recipients see next to the address on outgoing mail.</div>
            </div>

            <div class="col-md-6">
              <label class="form-label">Status</label>
              <select name="status" class="form-select">
                <option value="active"    <?php echo set_select('status', 'active',    $m->status === 'active');    ?>>Active</option>
                <option value="suspended" <?php echo set_select('status', 'suspended', $m->status === 'suspended'); ?>>Suspended</option>
              </select>
              <div class="form-text">Suspended keeps the credentials but blocks opening and sending from the panel.</div>
            </div>

            <div class="col-12">
              <label class="form-label">Signature</label>
              <textarea name="signature" class="form-control" rows="3" maxlength="2000" placeholder="Support Team&#10;Phone: ..."><?php echo set_value('signature', $m->signature); ?></textarea>
              <div class="form-text">Appended to every message composed from this mailbox, after a <span class="mono">--</span> separator.</div>
            </div>

          </div>

          <button class="btn btn-primary mt-3"><i class="bi bi-check2"></i> <?php echo $mode === 'edit' ? 'Save Changes' : 'Add Mailbox'; ?></button>
          <a href="<?php echo base_url('admin/mail'); ?>" class="btn btn-outline-secondary mt-3">Cancel</a>
        <?php echo form_close(); ?>
      </div>
    </div>

    <?php if ($mode === 'edit' && $m->last_error): ?>
    <div class="alert alert-danger mt-3 small">
      <i class="bi bi-exclamation-triangle"></i>
      Last connection failed: <?php echo html_escape($m->last_error); ?>
    </div>
    <?php endif; ?>
  </div>
</div>
