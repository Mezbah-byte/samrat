<?php if ( ! $agent->user_id): ?>
  <div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle"></i>
    This agent account is not linked to a user, so it has no team. Team, deposit and
    withdrawal screens will stay empty and no commission accrues automatically.
    Ask an admin to link a user account.
  </div>
<?php endif; ?>

<div class="row g-3 mb-3">
  <div class="col-6 col-xl-3">
    <div class="card stat-card">
      <div class="card-body">
        <div class="stat-label">Team Members</div>
        <div class="stat-value"><?php echo (int) $team['total']; ?></div>
        <i class="bi bi-people-fill stat-icon"></i>
      </div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="card stat-card">
      <div class="card-body">
        <div class="stat-label">Active Members</div>
        <div class="stat-value"><?php echo (int) $team['active']; ?></div>
        <i class="bi bi-check2-circle stat-icon"></i>
      </div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="card stat-card">
      <div class="card-body">
        <div class="stat-label">Earned This Month</div>
        <div class="stat-value"><?php echo money($earned_month); ?></div>
        <i class="bi bi-calendar-check stat-icon"></i>
      </div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="card stat-card">
      <div class="card-body">
        <div class="stat-label">Total Commission</div>
        <div class="stat-value"><?php echo money($earned_total); ?></div>
        <i class="bi bi-coin stat-icon"></i>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mb-3">
  <div class="col-md-4">
    <div class="card h-100">
      <div class="card-header"><i class="bi bi-people"></i> Team Breakdown</div>
      <div class="card-body small">
        <div class="d-flex justify-content-between py-1"><span class="text-muted">Active</span><span class="fw-semibold"><?php echo (int) $team['active']; ?></span></div>
        <div class="d-flex justify-content-between py-1"><span class="text-muted">Pending</span><span class="fw-semibold"><?php echo (int) $team['pending']; ?></span></div>
        <div class="d-flex justify-content-between py-1"><span class="text-muted">Blocked</span><span class="fw-semibold"><?php echo (int) $team['blocked']; ?></span></div>
        <div class="d-flex justify-content-between py-1"><span class="text-muted">Joined last 30 days</span><span class="fw-semibold"><?php echo (int) $team['joined_30d']; ?></span></div>
        <hr class="my-2">
        <div class="d-flex justify-content-between py-1"><span class="text-muted">Team deposits</span><span class="fw-semibold"><?php echo money($team['total_deposit']); ?></span></div>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card h-100">
      <div class="card-header"><i class="bi bi-clipboard-check"></i> Awaiting Your Review</div>
      <div class="card-body small">
        <div class="d-flex justify-content-between py-1">
          <span class="text-muted">Deposits not yet reviewed</span>
          <span class="fw-semibold"><?php echo (int) $deposits['awaiting_review']; ?></span>
        </div>
        <div class="d-flex justify-content-between py-1">
          <span class="text-muted">Withdrawals not yet reviewed</span>
          <span class="fw-semibold"><?php echo (int) $withdrawals['awaiting_review']; ?></span>
        </div>
        <hr class="my-2">
        <p class="text-muted mb-0">
          Your recommendation is advisory. An admin makes every final decision and
          moves the money.
        </p>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card h-100">
      <div class="card-header"><i class="bi bi-coin"></i> Commission Sources</div>
      <div class="card-body small">
        <div class="d-flex justify-content-between py-1">
          <span class="text-muted">Team deposits (<?php echo (int) $by_source['deposit']['deals']; ?>)</span>
          <span class="fw-semibold"><?php echo money($by_source['deposit']['earned']); ?></span>
        </div>
        <div class="d-flex justify-content-between py-1">
          <span class="text-muted">Daily profit (<?php echo (int) $by_source['daily_profit']['deals']; ?>)</span>
          <span class="fw-semibold"><?php echo money($by_source['daily_profit']['earned']); ?></span>
        </div>
        <?php if ($unsettled > 0 && ! $agent->user_id): ?>
          <hr class="my-2">
          <div class="d-flex justify-content-between py-1">
            <span class="text-muted">Unsettled</span>
            <span class="fw-semibold text-warning"><?php echo money($unsettled); ?></span>
          </div>
          <p class="text-muted mb-0 mt-2">Paid out manually by an admin.</p>
        <?php endif; ?>
        <a href="<?php echo base_url('agent/earnings'); ?>" class="btn btn-sm btn-outline-primary mt-3">View history</a>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-xl-6">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-inbox-fill"></i> Pending Team Deposits</span>
        <a href="<?php echo base_url('agent/deposits?status=pending'); ?>" class="btn btn-sm btn-outline-secondary">All</a>
      </div>
      <div class="table-wrap">
        <table class="table table-hover mb-0">
          <thead><tr><th>#</th><th>User</th><th>Amount</th><th>Reviewed</th><th></th></tr></thead>
          <tbody>
          <?php if (empty($pending_deps)): ?>
            <tr><td colspan="5" class="text-center text-muted py-4">Nothing pending.</td></tr>
          <?php else: foreach ($pending_deps as $d): ?>
            <tr>
              <td class="text-muted">#<?php echo (int) $d->id; ?></td>
              <td class="small"><?php echo html_escape($d->username); ?></td>
              <td class="small text-nowrap"><?php echo money($d->amount); ?></td>
              <td><?php echo $d->agent_recommendation ? badge($d->agent_recommendation === 'approve' ? 'approved' : 'rejected') : '<span class="text-muted small">&mdash;</span>'; ?></td>
              <td class="text-end"><a href="<?php echo base_url('agent/deposits/view/'.$d->id); ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a></td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-xl-6">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-cash-stack"></i> Pending Team Withdrawals</span>
        <a href="<?php echo base_url('agent/withdrawals?status=pending'); ?>" class="btn btn-sm btn-outline-secondary">All</a>
      </div>
      <div class="table-wrap">
        <table class="table table-hover mb-0">
          <thead><tr><th>#</th><th>User</th><th>Net</th><th>Reviewed</th><th></th></tr></thead>
          <tbody>
          <?php if (empty($pending_wds)): ?>
            <tr><td colspan="5" class="text-center text-muted py-4">Nothing pending.</td></tr>
          <?php else: foreach ($pending_wds as $w): ?>
            <tr>
              <td class="text-muted">#<?php echo (int) $w->id; ?></td>
              <td class="small"><?php echo html_escape($w->username); ?></td>
              <td class="small text-nowrap"><?php echo money($w->net_amount); ?></td>
              <td><?php echo $w->agent_recommendation ? badge($w->agent_recommendation === 'approve' ? 'approved' : 'rejected') : '<span class="text-muted small">&mdash;</span>'; ?></td>
              <td class="text-end"><a href="<?php echo base_url('agent/withdrawals/view/'.$w->id); ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a></td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="card mt-3">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span><i class="bi bi-person-plus"></i> Newest Team Members</span>
    <a href="<?php echo base_url('agent/team'); ?>" class="btn btn-sm btn-outline-secondary">All members</a>
  </div>
  <div class="table-wrap">
    <table class="table table-hover mb-0">
      <thead><tr><th>#</th><th>Name</th><th>Username</th><th>Status</th><th>Deposited</th><th>Joined</th><th></th></tr></thead>
      <tbody>
      <?php if (empty($recent_members)): ?>
        <tr><td colspan="7" class="text-center text-muted py-4">No team members yet.</td></tr>
      <?php else: foreach ($recent_members as $m): ?>
        <tr>
          <td class="text-muted">#<?php echo (int) $m->id; ?></td>
          <td class="fw-semibold"><?php echo html_escape($m->full_name); ?></td>
          <td class="small"><?php echo html_escape($m->username); ?></td>
          <td><?php echo badge($m->status); ?></td>
          <td class="small text-nowrap"><?php echo money($m->total_deposit); ?></td>
          <td class="small text-muted text-nowrap"><?php echo fmt_date($m->created_at, 'd M Y'); ?></td>
          <td class="text-end"><a href="<?php echo base_url('agent/team/view/'.$m->id); ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
