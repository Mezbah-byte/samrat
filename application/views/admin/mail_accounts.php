<?php if ( ! $imap_ready): ?>
  <div class="alert alert-danger">
    <i class="bi bi-exclamation-octagon"></i>
    <strong>The PHP <span class="mono">imap</span> extension is not loaded</strong>, so no mailbox can be opened on this server.
    On XAMPP, uncomment <span class="mono">extension=imap</span> in <span class="mono">php.ini</span> and restart Apache.
    On shared hosting it is usually a toggle in the PHP selector.
  </div>
<?php endif; ?>

<?php if ( ! $crypto_ready): ?>
  <div class="alert alert-danger">
    <i class="bi bi-key"></i>
    <strong>No encryption key is configured.</strong>
    Copy <span class="mono">application/config/secrets.sample.php</span> to <span class="mono">secrets.php</span> and put a
    64-character key in it. Until then no mailbox password can be stored or read back.
  </div>
<?php endif; ?>

<div class="card mb-3">
  <div class="card-body py-2">
    <form method="get" action="<?php echo base_url('admin/mail'); ?>" class="row g-2 align-items-end">
      <div class="col-md-4">
        <label class="form-label small mb-1">Search</label>
        <input type="text" name="q" class="form-control form-control-sm" value="<?php echo html_escape($f_search); ?>" placeholder="address or name">
      </div>
      <div class="col-md-3">
        <label class="form-label small mb-1">Domain</label>
        <select name="domain" class="form-select form-select-sm">
          <option value="">All domains</option>
          <?php foreach ($domains as $d): ?>
            <option value="<?php echo (int) $d->id; ?>" <?php echo ((int) $f_domain === (int) $d->id) ? 'selected' : ''; ?>><?php echo html_escape($d->domain); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label small mb-1">Status</label>
        <select name="status" class="form-select form-select-sm">
          <option value="">Any</option>
          <option value="active"    <?php echo $f_status === 'active'    ? 'selected' : ''; ?>>Active</option>
          <option value="suspended" <?php echo $f_status === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
        </select>
      </div>
      <div class="col-md-2 d-flex gap-2">
        <button class="btn btn-sm btn-primary w-100"><i class="bi bi-funnel"></i> Filter</button>
        <a href="<?php echo base_url('admin/mail'); ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span><i class="bi bi-envelope-at"></i> Mailboxes <span class="text-muted small">(<?php echo (int) $total; ?>)</span></span>
    <div class="d-flex gap-2">
      <a href="<?php echo base_url('admin/mail/logs'); ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-clock-history"></i> Access log</a>
      <a href="<?php echo base_url('admin/mail-domains'); ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-globe2"></i> Domains</a>
      <?php if ($can_manage): ?>
      <a href="<?php echo base_url('admin/mail/create'); ?>" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> Add Mailbox</a>
      <?php endif; ?>
    </div>
  </div>

  <?php if (empty($rows)): ?>
    <div class="empty-state">
      <i class="bi bi-envelope-at"></i>
      No mailboxes registered.
      Create the mailbox in cPanel first, then add its address and password here.
    </div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table table-hover mb-0 align-middle">
        <thead><tr><th>Mailbox</th><th>Display name</th><th>Status</th><th>Last checked</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td>
              <span class="fw-semibold"><?php echo html_escape($r->email); ?></span>
              <?php if ($r->last_error): ?>
                <div class="small text-danger"><i class="bi bi-exclamation-triangle"></i> <?php echo html_escape($r->last_error); ?></div>
              <?php endif; ?>
            </td>
            <td class="small text-muted"><?php echo html_escape($r->display_name); ?></td>
            <td><?php echo badge($r->status); ?></td>
            <td class="small text-muted">
              <?php echo $r->last_checked_at ? fmt_date($r->last_checked_at) : '<span class="text-muted">never</span>'; ?>
            </td>
            <td class="text-end text-nowrap">
              <?php if ($can_access && $r->status === 'active' && $imap_ready): ?>
                <a href="<?php echo base_url('admin/mail/open/'.$r->id); ?>" class="btn btn-sm btn-primary"><i class="bi bi-inbox"></i> Open</a>
              <?php endif; ?>
              <?php if ($can_manage): ?>
                <a href="<?php echo base_url('admin/mail/test/'.$r->id); ?>" class="btn btn-sm btn-outline-secondary" title="Test the connection"><i class="bi bi-plug"></i></a>
                <a href="<?php echo base_url('admin/mail/edit/'.$r->id); ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                <?php echo form_open('admin/mail/delete/'.$r->id, array('class' => 'd-inline')); ?>
                  <button class="btn btn-sm btn-outline-danger" data-confirm="Remove <?php echo html_escape($r->email); ?> from the panel? The mailbox on the server is not touched."><i class="bi bi-trash"></i></button>
                <?php echo form_close(); ?>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php if ($total > $per_page): ?>
      <div class="card-footer d-flex justify-content-end">
        <?php echo pager(base_url('admin/mail').'?q='.urlencode($f_search).'&domain='.(int) $f_domain.'&status='.urlencode((string) $f_status), $total, $per_page, $page); ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>

<div class="alert alert-secondary mt-3 small">
  <i class="bi bi-shield-lock"></i>
  Mailbox passwords are stored encrypted so the panel can log in on demand, and every open, read and send is
  recorded against the admin who did it. Registering a mailbox here does not create it, and removing one does
  not delete it &mdash; both happen in cPanel.
</div>
