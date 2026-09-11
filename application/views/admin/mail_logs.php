<?php $base = $account ? base_url('admin/mail/logs/'.$account->id) : base_url('admin/mail/logs'); ?>

<a href="<?php echo base_url('admin/mail'); ?>" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> All mailboxes</a>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span>
      <i class="bi bi-clock-history"></i> Mailbox Access Log
      <?php if ($account): ?><span class="text-muted small">&mdash; <?php echo html_escape($account->email); ?></span><?php endif; ?>
    </span>
    <?php if ($account): ?>
      <a href="<?php echo base_url('admin/mail/logs'); ?>" class="btn btn-sm btn-outline-secondary">All mailboxes</a>
    <?php endif; ?>
  </div>

  <?php if (empty($rows)): ?>
    <div class="empty-state"><i class="bi bi-clock-history"></i>Nothing recorded yet.</div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table table-hover mb-0 align-middle">
        <thead><tr><th>When</th><th>Admin</th><th>Mailbox</th><th>Action</th><th>Detail</th><th>IP</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td class="small text-nowrap"><?php echo fmt_date($r->created_at); ?></td>
            <td class="small"><?php echo html_escape($r->admin_name ?: 'deleted admin'); ?></td>
            <td class="small mono"><?php echo html_escape(isset($r->account_email) ? ($r->account_email ?: '—') : '—'); ?></td>
            <td><span class="badge text-bg-secondary"><?php echo html_escape($r->action); ?></span></td>
            <td class="small text-muted"><?php echo html_escape($r->detail); ?></td>
            <td class="small mono text-muted"><?php echo html_escape($r->ip_address); ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php if ($total > $per_page): ?>
      <div class="card-footer d-flex justify-content-end">
        <?php echo pager($base, $total, $per_page, $page); ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>
