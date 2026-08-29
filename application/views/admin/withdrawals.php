<div class="row g-3 mb-3">
  <div class="col-md-4"><div class="card stat-card"><div class="stat-label">Pending</div><div class="stat-value fs-4 <?php echo $stats['pending_count'] ? 'text-warning' : ''; ?>"><?php echo (int) $stats['pending_count']; ?></div></div></div>
  <div class="col-md-4"><div class="card stat-card"><div class="stat-label">Paid Out</div><div class="stat-value fs-4"><?php echo money($stats['paid_total']); ?></div></div></div>
  <div class="col-md-4"><div class="card stat-card"><div class="stat-label">Fees Collected</div><div class="stat-value fs-4 text-success"><?php echo money($stats['fee_total']); ?></div></div></div>
</div>

<div class="card">
  <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
    <span><i class="bi bi-cash-stack"></i> Withdrawals</span>
    <?php echo form_open('admin/withdrawals', array('method' => 'get', 'class' => 'd-flex gap-2 m-0')); ?>
      <select name="status" class="form-select form-select-sm" data-autosubmit>
        <option value="">All statuses</option>
        <?php foreach (array('pending', 'approved', 'paid', 'rejected') as $s): ?>
          <option value="<?php echo $s; ?>" <?php echo $status === $s ? 'selected' : ''; ?>><?php echo ucfirst($s); ?></option>
        <?php endforeach; ?>
      </select>
      <input type="search" name="q" class="form-control form-control-sm" placeholder="User, address or TXID" value="<?php echo html_escape($search); ?>" style="min-width:210px">
      <button class="btn btn-sm btn-primary"><i class="bi bi-search"></i></button>
    <?php echo form_close(); ?>
  </div>

  <?php if (empty($rows)): ?>
    <div class="empty-state"><i class="bi bi-inbox"></i>No withdrawals match this filter.</div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table table-hover mb-0">
        <thead>
          <tr><th>#</th><th>User</th><th class="text-end">Requested</th><th class="text-end">Fee</th><th class="text-end">Net</th>
              <th>Network</th><th>Address</th><th>Status</th><th>Date</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $w): ?>
          <tr>
            <td class="text-muted">#<?php echo (int) $w->id; ?></td>
            <td>
              <a href="<?php echo base_url('admin/users/view/'.$w->user_id); ?>" class="small fw-semibold"><?php echo html_escape($w->username); ?></a>
              <div class="small text-muted"><?php echo html_escape($w->full_name); ?></div>
            </td>
            <td class="text-end"><?php echo money($w->amount); ?></td>
            <td class="text-end text-danger"><?php echo money($w->fee); ?></td>
            <td class="text-end fw-semibold"><?php echo money($w->net_amount); ?></td>
            <td><span class="badge text-bg-light border"><?php echo html_escape($w->network); ?></span></td>
            <td class="mono small" title="<?php echo html_escape($w->wallet_address); ?>"><?php echo html_escape(short_txt($w->wallet_address)); ?></td>
            <td><?php echo badge($w->status); ?></td>
            <td class="small text-muted text-nowrap"><?php echo fmt_date($w->created_at, 'd M, H:i'); ?></td>
            <td class="text-end">
              <a href="<?php echo base_url('admin/withdrawals/view/'.$w->id); ?>"
                 class="btn btn-sm <?php echo in_array($w->status, array('pending', 'approved'), TRUE) ? 'btn-primary' : 'btn-outline-secondary'; ?>">
                <?php echo in_array($w->status, array('pending', 'approved'), TRUE) ? 'Process' : 'View'; ?>
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="card-footer d-flex justify-content-between align-items-center">
      <small class="text-muted"><?php echo (int) $total; ?> requests</small>
      <?php echo pager(base_url('admin/withdrawals').'?status='.urlencode($status).'&q='.urlencode($search), $total, $per_page, $page); ?>
    </div>
  <?php endif; ?>
</div>
