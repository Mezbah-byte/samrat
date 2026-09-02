<div class="row g-3 mb-3">
  <div class="col-6 col-xl-4">
    <div class="card stat-card"><div class="card-body">
      <div class="stat-label">Pending</div>
      <div class="stat-value"><?php echo (int) $stats['pending_count']; ?></div>
      <i class="bi bi-hourglass-split stat-icon"></i>
    </div></div>
  </div>
  <div class="col-6 col-xl-4">
    <div class="card stat-card"><div class="card-body">
      <div class="stat-label">Awaiting Your Review</div>
      <div class="stat-value"><?php echo (int) $stats['awaiting_review']; ?></div>
      <i class="bi bi-clipboard-check stat-icon"></i>
    </div></div>
  </div>
  <div class="col-12 col-xl-4">
    <div class="card stat-card"><div class="card-body">
      <div class="stat-label">Paid to Team</div>
      <div class="stat-value"><?php echo money($stats['paid_total']); ?></div>
      <i class="bi bi-cash-stack stat-icon"></i>
    </div></div>
  </div>
</div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <span><i class="bi bi-cash-stack"></i> Team Withdrawals</span>
    <form class="d-flex gap-2" method="get" action="<?php echo base_url('agent/withdrawals'); ?>">
      <select name="status" class="form-select form-select-sm">
        <option value="">All status</option>
        <option value="pending"  <?php echo $status === 'pending'  ? 'selected' : ''; ?>>Pending</option>
        <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>Approved</option>
        <option value="paid"     <?php echo $status === 'paid'     ? 'selected' : ''; ?>>Paid</option>
        <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
      </select>
      <input type="text" name="q" class="form-control form-control-sm" placeholder="User or wallet" value="<?php echo html_escape($search); ?>">
      <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-search"></i></button>
    </form>
  </div>

  <div class="table-wrap">
    <table class="table table-hover mb-0">
      <thead>
        <tr><th>#</th><th>User</th><th>Amount</th><th>Fee</th><th>Net</th><th>Status</th><th>My Recommendation</th><th>Date</th><th></th></tr>
      </thead>
      <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="9" class="text-center text-muted py-4">No withdrawals match this filter.</td></tr>
      <?php else: foreach ($rows as $w): ?>
        <tr>
          <td class="text-muted">#<?php echo (int) $w->id; ?></td>
          <td class="small"><?php echo html_escape($w->username); ?></td>
          <td class="small text-nowrap"><?php echo money($w->amount); ?></td>
          <td class="small text-nowrap"><?php echo money($w->fee); ?></td>
          <td class="small text-nowrap fw-semibold"><?php echo money($w->net_amount); ?></td>
          <td><?php echo badge($w->status); ?></td>
          <td class="small">
            <?php if ($w->agent_recommendation): ?>
              <?php echo badge($w->agent_recommendation === 'approve' ? 'approved' : 'rejected'); ?>
            <?php else: ?>
              <span class="text-muted">Not reviewed</span>
            <?php endif; ?>
          </td>
          <td class="small text-muted text-nowrap"><?php echo fmt_date($w->created_at, 'd M Y'); ?></td>
          <td class="text-end"><a href="<?php echo base_url('agent/withdrawals/view/'.$w->id); ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($total > $per_page): ?>
    <div class="card-footer"><?php echo pager(base_url('agent/withdrawals'), $total, $per_page, $page); ?></div>
  <?php endif; ?>
</div>
