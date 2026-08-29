<div class="row g-3 mb-3">
  <div class="col-6 col-xl-3">
    <div class="card stat-card h-100">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="stat-label">Users</div>
          <div class="stat-value"><?php echo number_format($users['total_users']); ?></div>
          <div class="small text-muted"><?php echo number_format($users['active_users']); ?> active &bull; <?php echo number_format($users['blocked_users']); ?> blocked</div>
        </div>
        <div class="stat-icon bg-brand-soft text-brand"><i class="bi bi-people-fill"></i></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="card stat-card h-100">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="stat-label">Approved Deposits</div>
          <div class="stat-value"><?php echo money($deposits['approved_total']); ?></div>
          <div class="small <?php echo $deposits['pending_count'] ? 'text-warning fw-semibold' : 'text-muted'; ?>">
            <?php echo (int) $deposits['pending_count']; ?> pending review
          </div>
        </div>
        <div class="stat-icon bg-primary-subtle text-primary"><i class="bi bi-inbox-fill"></i></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="card stat-card h-100">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="stat-label">Paid Out</div>
          <div class="stat-value"><?php echo money($withdrawals['paid_total']); ?></div>
          <div class="small <?php echo $withdrawals['pending_count'] ? 'text-warning fw-semibold' : 'text-muted'; ?>">
            <?php echo (int) $withdrawals['pending_count']; ?> pending &bull; <?php echo money($withdrawals['fee_total']); ?> fees
          </div>
        </div>
        <div class="stat-icon bg-success-subtle text-success"><i class="bi bi-cash-stack"></i></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="card stat-card h-100">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="stat-label">Profit Paid Today</div>
          <div class="stat-value text-success"><?php echo money($profit_today); ?></div>
          <div class="small text-muted"><?php echo (int) $ads['views_today']; ?> ad views today</div>
        </div>
        <div class="stat-icon bg-warning-subtle text-warning"><i class="bi bi-graph-up-arrow"></i></div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mb-3">
  <div class="col-md-3 col-6">
    <div class="card stat-card"><div class="stat-label">User Balances</div><div class="stat-value fs-5"><?php echo money($users['total_balance']); ?></div></div>
  </div>
  <div class="col-md-3 col-6">
    <div class="card stat-card"><div class="stat-label">Active Plans</div><div class="stat-value fs-5"><?php echo number_format($investments['active_count']); ?></div></div>
  </div>
  <div class="col-md-3 col-6">
    <div class="card stat-card"><div class="stat-label">Capital in Plans</div><div class="stat-value fs-5"><?php echo money($investments['active_invested']); ?></div></div>
  </div>
  <div class="col-md-3 col-6">
    <div class="card stat-card"><div class="stat-label">Completed Plans</div><div class="stat-value fs-5"><?php echo number_format($investments['completed_count']); ?></div></div>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-inbox-fill"></i> Pending Deposits</span>
        <a href="<?php echo base_url('admin/deposits?status=pending'); ?>" class="small">Review all</a>
      </div>
      <?php if (empty($pending_deps)): ?>
        <div class="empty-state py-4"><i class="bi bi-check2-circle"></i>Nothing waiting.</div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="table mb-0">
            <thead><tr><th>User</th><th>Package</th><th class="text-end">Amount</th><th>Submitted</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($pending_deps as $d): ?>
              <tr>
                <td class="small fw-semibold"><?php echo html_escape($d->username); ?></td>
                <td class="small"><?php echo html_escape($d->package_name); ?></td>
                <td class="text-end fw-semibold"><?php echo money($d->amount); ?></td>
                <td class="small text-muted text-nowrap"><?php echo fmt_date($d->created_at, 'd M, H:i'); ?></td>
                <td class="text-end"><a href="<?php echo base_url('admin/deposits/view/'.$d->id); ?>" class="btn btn-sm btn-outline-primary">Review</a></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-cash-stack"></i> Pending Withdrawals</span>
        <a href="<?php echo base_url('admin/withdrawals?status=pending'); ?>" class="small">Review all</a>
      </div>
      <?php if (empty($pending_wds)): ?>
        <div class="empty-state py-4"><i class="bi bi-check2-circle"></i>Nothing waiting.</div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="table mb-0">
            <thead><tr><th>User</th><th class="text-end">Net</th><th>Network</th><th>Requested</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($pending_wds as $w): ?>
              <tr>
                <td class="small fw-semibold"><?php echo html_escape($w->username); ?></td>
                <td class="text-end fw-semibold"><?php echo money($w->net_amount); ?></td>
                <td><span class="badge text-bg-light border"><?php echo html_escape($w->network); ?></span></td>
                <td class="small text-muted text-nowrap"><?php echo fmt_date($w->created_at, 'd M, H:i'); ?></td>
                <td class="text-end"><a href="<?php echo base_url('admin/withdrawals/view/'.$w->id); ?>" class="btn btn-sm btn-outline-primary">Review</a></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-lg-7">
    <div class="card h-100">
      <div class="card-header"><i class="bi bi-bar-chart"></i> Registrations (last 14 days)</div>
      <div class="card-body">
        <?php $max = max(1, max($signups)); ?>
        <div class="d-flex align-items-end gap-1" style="height:150px">
          <?php foreach ($signups as $day => $count): ?>
            <div class="flex-fill d-flex flex-column justify-content-end align-items-center" title="<?php echo $day.': '.$count; ?>">
              <div class="small text-muted mb-1"><?php echo $count ?: ''; ?></div>
              <div class="w-100 rounded-top bg-brand" style="height:<?php echo max(3, round($count / $max * 110)); ?>px"></div>
              <div class="text-muted" style="font-size:.65rem"><?php echo date('d', strtotime($day)); ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card mb-3">
      <div class="card-header"><i class="bi bi-trophy"></i> Top Referrers</div>
      <?php if (empty($leaderboard)): ?>
        <div class="empty-state py-4"><i class="bi bi-people"></i>No commissions paid yet.</div>
      <?php else: ?>
        <ul class="list-group list-group-flush">
          <?php foreach ($leaderboard as $l): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center py-2">
              <div>
                <div class="small fw-semibold"><?php echo html_escape($l->username); ?></div>
                <div class="small text-muted"><?php echo (int) $l->deals; ?> referral deposits</div>
              </div>
              <strong class="text-success"><?php echo money($l->earned); ?></strong>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>

    <div class="card">
      <div class="card-header"><i class="bi bi-clock"></i> Daily Cron</div>
      <div class="card-body">
        <p class="small text-muted">Run once a day. It closes missed days, opens today's rows and completes finished plans. Safe to run more than once.</p>
        <label class="form-label small text-muted mb-1">Command line</label>
        <div class="copy-field mb-2">
          <input type="text" class="form-control form-control-sm" id="cronCli" readonly value="php index.php cron run">
          <button class="btn btn-sm btn-outline-secondary" type="button" data-copy-target="#cronCli"><i class="bi bi-clipboard"></i></button>
        </div>
        <label class="form-label small text-muted mb-1">HTTP URL</label>
        <div class="copy-field">
          <input type="text" class="form-control form-control-sm" id="cronUrl" readonly value="<?php echo base_url('cron/run?key='.$cron_secret); ?>">
          <button class="btn btn-sm btn-outline-secondary" type="button" data-copy-target="#cronUrl"><i class="bi bi-clipboard"></i></button>
        </div>
      </div>
    </div>
  </div>
</div>
