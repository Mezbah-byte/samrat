<div class="row g-3 mb-3">
  <div class="col-6 col-xl-3">
    <div class="card stat-card"><div class="card-body">
      <div class="stat-label">Total Earned</div>
      <div class="stat-value"><?php echo money($earned_total); ?></div>
      <i class="bi bi-coin stat-icon"></i>
    </div></div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="card stat-card"><div class="card-body">
      <div class="stat-label">This Month</div>
      <div class="stat-value"><?php echo money($earned_month); ?></div>
      <i class="bi bi-calendar-check stat-icon"></i>
    </div></div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="card stat-card"><div class="card-body">
      <div class="stat-label">From Deposits</div>
      <div class="stat-value"><?php echo money($by_source['deposit']['earned']); ?></div>
      <i class="bi bi-inbox-fill stat-icon"></i>
    </div></div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="card stat-card"><div class="card-body">
      <div class="stat-label">From Daily Profit</div>
      <div class="stat-value"><?php echo money($by_source['daily_profit']['earned']); ?></div>
      <i class="bi bi-graph-up-arrow stat-icon"></i>
    </div></div>
  </div>
</div>

<div class="alert alert-info small">
  <i class="bi bi-info-circle"></i>
  Your rates: <strong><?php echo percent($deposit_pct); ?></strong> of every approved team deposit and
  <strong><?php echo percent($profit_pct); ?></strong> of every daily profit your team earns.
  <?php if ($agent->user_id): ?>
    Commission is credited to your linked user wallet as it accrues.
  <?php else: ?>
    This account has no linked user wallet, so commission accrues here and an admin settles it manually.
    <strong><?php echo money($unsettled); ?></strong> is currently unsettled.
  <?php endif; ?>
</div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <span><i class="bi bi-list-columns-reverse"></i> Commission History</span>
    <form class="d-flex gap-2" method="get" action="<?php echo base_url('agent/earnings'); ?>">
      <select name="source" class="form-select form-select-sm">
        <option value="">All sources</option>
        <option value="deposit"      <?php echo $source === 'deposit'      ? 'selected' : ''; ?>>Team deposits</option>
        <option value="daily_profit" <?php echo $source === 'daily_profit' ? 'selected' : ''; ?>>Daily profit</option>
      </select>
      <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-funnel"></i></button>
    </form>
  </div>

  <div class="table-wrap">
    <table class="table table-hover mb-0">
      <thead>
        <tr><th>#</th><th>Member</th><th>Source</th><th>Base</th><th>Rate</th><th>Earned</th><th>Settled</th><th>Date</th></tr>
      </thead>
      <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="8" class="text-center text-muted py-4">No commission earned yet.</td></tr>
      <?php else: foreach ($rows as $c): ?>
        <tr>
          <td class="text-muted">#<?php echo (int) $c->id; ?></td>
          <td class="small"><?php echo html_escape($c->member_username ?: 'Deleted user'); ?></td>
          <td class="small"><?php echo $c->source === 'deposit' ? 'Team deposit' : 'Daily profit'; ?></td>
          <td class="small text-nowrap"><?php echo money($c->base_amount); ?></td>
          <td class="small text-nowrap"><?php echo percent($c->percent); ?></td>
          <td class="small text-nowrap fw-semibold"><?php echo money($c->amount); ?></td>
          <td><?php echo $c->settled ? badge('credited') : badge('pending'); ?></td>
          <td class="small text-muted text-nowrap"><?php echo fmt_date($c->created_at, 'd M Y, H:i'); ?></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($total > $per_page): ?>
    <div class="card-footer"><?php echo pager(base_url('agent/earnings'), $total, $per_page, $page); ?></div>
  <?php endif; ?>
</div>
