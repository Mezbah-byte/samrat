<div class="row g-3 mb-3">
  <div class="col-6 col-xl-4">
    <div class="card stat-card"><div class="card-body">
      <div class="stat-label">Waiting on You</div>
      <div class="stat-value <?php echo $stats['pending_count'] ? 'text-warning' : ''; ?>"><?php echo (int) $stats['pending_count']; ?></div>
      <i class="bi bi-hourglass-split stat-icon"></i>
    </div></div>
  </div>
  <div class="col-6 col-xl-4">
    <div class="card stat-card"><div class="card-body">
      <div class="stat-label">Paid Out Lifetime</div>
      <div class="stat-value"><?php echo money($stats['paid_total']); ?></div>
      <i class="bi bi-cash-stack stat-icon"></i>
    </div></div>
  </div>
  <div class="col-12 col-xl-4">
    <div class="card stat-card"><div class="card-body">
      <div class="stat-label">Earned on Withdrawals</div>
      <div class="stat-value"><?php echo money($stats['earned_total']); ?></div>
      <i class="bi bi-coin stat-icon"></i>
    </div></div>
  </div>
</div>

<div class="card">
  <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
    <span><i class="bi bi-box-arrow-up-right"></i> Withdraw Requests</span>
    <?php echo form_open('agent/requests/withdrawals', array('method' => 'get', 'class' => 'd-flex gap-2 m-0')); ?>
      <select name="status" class="form-select form-select-sm" data-autosubmit>
        <option value="">All</option>
        <?php foreach (array('pending' => 'Waiting', 'accepted' => 'Paid', 'rejected' => 'Declined', 'expired' => 'Expired') as $k => $label): ?>
          <option value="<?php echo $k; ?>" <?php echo $status === $k ? 'selected' : ''; ?>><?php echo $label; ?></option>
        <?php endforeach; ?>
      </select>
      <input type="search" name="q" class="form-control form-control-sm" placeholder="User or address" value="<?php echo html_escape($search); ?>" style="min-width:180px">
      <button class="btn btn-sm btn-primary"><i class="bi bi-search"></i></button>
    <?php echo form_close(); ?>
  </div>

  <?php if (empty($rows)): ?>
    <div class="empty-state">
      <i class="bi bi-box-arrow-up-right"></i>
      Nothing here yet. Users pick you when they request a withdrawal.
    </div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table table-hover mb-0">
        <thead><tr><th>#</th><th>User</th><th class="text-end">You send</th><th>Network</th><th>Address</th><th>Request</th><th>Asked</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $w): ?>
          <tr>
            <td class="text-muted">#<?php echo (int) $w->id; ?></td>
            <td>
              <span class="small fw-semibold"><?php echo html_escape($w->username); ?></span>
              <div class="small text-muted"><?php echo html_escape($w->full_name); ?></div>
            </td>
            <td class="text-end fw-semibold"><?php echo money($w->net_amount); ?></td>
            <td><span class="badge text-bg-light border"><?php echo html_escape($w->network); ?></span></td>
            <td class="mono small" title="<?php echo html_escape($w->wallet_address); ?>"><?php echo html_escape(short_txt($w->wallet_address)); ?></td>
            <td>
              <?php if ($w->agent_status === 'pending'): ?><span class="badge text-bg-warning">Waiting</span>
              <?php elseif ($w->agent_status === 'accepted'): ?><span class="badge text-bg-success">Paid</span>
              <?php elseif ($w->agent_status === 'expired'): ?><span class="badge text-bg-secondary">Expired</span>
              <?php else: ?><span class="badge text-bg-danger">Declined</span><?php endif; ?>
            </td>
            <td class="small text-muted text-nowrap"><?php echo fmt_date($w->created_at, 'd M, H:i'); ?></td>
            <td class="text-end">
              <a href="<?php echo base_url('agent/requests/withdrawal/'.$w->id); ?>"
                 class="btn btn-sm <?php echo $w->agent_status === 'pending' ? 'btn-primary' : 'btn-outline-secondary'; ?>">
                <?php echo $w->agent_status === 'pending' ? 'Handle' : 'View'; ?>
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="card-footer d-flex justify-content-between align-items-center">
      <small class="text-muted"><?php echo (int) $total; ?> requests</small>
      <?php echo pager(base_url('agent/requests/withdrawals').'?status='.urlencode($status).'&q='.urlencode($search), $total, $per_page, $page); ?>
    </div>
  <?php endif; ?>
</div>
