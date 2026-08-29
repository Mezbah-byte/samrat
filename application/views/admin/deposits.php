<div class="row g-3 mb-3">
  <div class="col-md-6"><div class="card stat-card"><div class="stat-label">Pending Review</div><div class="stat-value fs-4 <?php echo $stats['pending_count'] ? 'text-warning' : ''; ?>"><?php echo (int) $stats['pending_count']; ?></div></div></div>
  <div class="col-md-6"><div class="card stat-card"><div class="stat-label">Approved Total</div><div class="stat-value fs-4"><?php echo money($stats['approved_total']); ?></div></div></div>
</div>

<div class="card">
  <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
    <span><i class="bi bi-inbox-fill"></i> Deposits</span>
    <?php echo form_open('admin/deposits', array('method' => 'get', 'class' => 'd-flex gap-2 m-0')); ?>
      <select name="status" class="form-select form-select-sm" data-autosubmit>
        <option value="">All statuses</option>
        <?php foreach (array('pending', 'approved', 'rejected') as $s): ?>
          <option value="<?php echo $s; ?>" <?php echo $status === $s ? 'selected' : ''; ?>><?php echo ucfirst($s); ?></option>
        <?php endforeach; ?>
      </select>
      <input type="search" name="q" class="form-control form-control-sm" placeholder="User or TXID" value="<?php echo html_escape($search); ?>" style="min-width:200px">
      <button class="btn btn-sm btn-primary"><i class="bi bi-search"></i></button>
    <?php echo form_close(); ?>
  </div>

  <?php if (empty($rows)): ?>
    <div class="empty-state"><i class="bi bi-inbox"></i>No deposits match this filter.</div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table table-hover mb-0">
        <thead>
          <tr><th>#</th><th>User</th><th>Package</th><th class="text-end">Amount</th>
              <th>Network</th><th>TXID</th><th>Status</th><th>Submitted</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $d): ?>
          <tr>
            <td class="text-muted">#<?php echo (int) $d->id; ?></td>
            <td>
              <a href="<?php echo base_url('admin/users/view/'.$d->user_id); ?>" class="small fw-semibold"><?php echo html_escape($d->username); ?></a>
              <div class="small text-muted"><?php echo html_escape($d->full_name); ?></div>
            </td>
            <td class="small"><?php echo html_escape($d->package_name); ?></td>
            <td class="text-end fw-semibold"><?php echo money($d->amount); ?></td>
            <td><span class="badge text-bg-light border"><?php echo html_escape($d->network); ?></span></td>
            <td class="mono small" title="<?php echo html_escape($d->txid); ?>"><?php echo html_escape(short_txt($d->txid)); ?></td>
            <td><?php echo badge($d->status); ?></td>
            <td class="small text-muted text-nowrap"><?php echo fmt_date($d->created_at, 'd M, H:i'); ?></td>
            <td class="text-end">
              <a href="<?php echo base_url('admin/deposits/view/'.$d->id); ?>"
                 class="btn btn-sm <?php echo $d->status === 'pending' ? 'btn-primary' : 'btn-outline-secondary'; ?>">
                <?php echo $d->status === 'pending' ? 'Review' : 'View'; ?>
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="card-footer d-flex justify-content-between align-items-center">
      <small class="text-muted"><?php echo (int) $total; ?> deposits</small>
      <?php echo pager(base_url('admin/deposits').'?status='.urlencode($status).'&q='.urlencode($search), $total, $per_page, $page); ?>
    </div>
  <?php endif; ?>
</div>
