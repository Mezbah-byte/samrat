<a href="<?php echo base_url('admin/mail'); ?>" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> All mailboxes</a>

<div class="row justify-content-center">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header"><i class="bi bi-plug"></i> Connection test &mdash; <?php echo html_escape($account->email); ?></div>
      <div class="card-body">

        <?php foreach (array('imap' => 'Incoming (IMAP)', 'smtp' => 'Outgoing (SMTP)') as $key => $label): ?>
          <?php list($ok, $detail) = $results[$key]; ?>
          <div class="d-flex align-items-start gap-3 mb-3">
            <div class="fs-4 <?php echo $ok ? 'text-success' : 'text-danger'; ?>">
              <i class="bi bi-<?php echo $ok ? 'check-circle-fill' : 'x-circle-fill'; ?>"></i>
            </div>
            <div>
              <div class="fw-semibold"><?php echo $label; ?></div>
              <div class="small mono text-muted">
                <?php echo html_escape($key === 'imap'
                  ? $account->imap_host.':'.$account->imap_port.' ('.$account->imap_encryption.')'
                  : $account->smtp_host.':'.$account->smtp_port.' ('.$account->smtp_encryption.')'); ?>
              </div>
              <div class="small <?php echo $ok ? 'text-success' : 'text-danger'; ?>"><?php echo html_escape($detail); ?></div>
            </div>
          </div>
        <?php endforeach; ?>

        <?php if ( ! empty($results['folders'])): ?>
          <hr>
          <div class="fw-semibold small text-uppercase text-muted mb-2">Folders on the server</div>
          <div class="d-flex flex-wrap gap-2">
            <?php foreach ($results['folders'] as $f): ?>
              <span class="badge text-bg-light border">
                <?php echo html_escape($f['path']); ?>
                <span class="text-muted">(<?php echo (int) $f['total']; ?>)</span>
              </span>
            <?php endforeach; ?>
          </div>
          <div class="form-text mt-2">
            If the domain's Sent or Trash folder is not in this list, correct it under
            <a href="<?php echo base_url('admin/mail-domains/edit/'.$account->domain_id); ?>">the domain settings</a>
            &mdash; sent copies and deletions go to those names.
          </div>
        <?php endif; ?>

        <div class="mt-3 d-flex gap-2">
          <a href="<?php echo base_url('admin/mail/test/'.$account->id); ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-clockwise"></i> Test again</a>
          <?php if ($results['imap'][0] && admin_can('mail.access')): ?>
            <a href="<?php echo base_url('admin/mail/open/'.$account->id); ?>" class="btn btn-sm btn-primary"><i class="bi bi-inbox"></i> Open mailbox</a>
          <?php endif; ?>
          <a href="<?php echo base_url('admin/mail/edit/'.$account->id); ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i> Edit credentials</a>
        </div>

      </div>
    </div>
  </div>
</div>
