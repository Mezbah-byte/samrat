<div class="row g-3 mb-3">
  <div class="col-6 col-xl-3">
    <div class="card stat-card"><div class="card-body">
      <div class="stat-label">Total Members</div>
      <div class="stat-value"><?php echo (int) $stats['total']; ?></div>
      <i class="bi bi-people-fill stat-icon"></i>
    </div></div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="card stat-card"><div class="card-body">
      <div class="stat-label">Active</div>
      <div class="stat-value"><?php echo (int) $stats['active']; ?></div>
      <i class="bi bi-check2-circle stat-icon"></i>
    </div></div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="card stat-card"><div class="card-body">
      <div class="stat-label">Joined (30d)</div>
      <div class="stat-value"><?php echo (int) $stats['joined_30d']; ?></div>
      <i class="bi bi-person-plus stat-icon"></i>
    </div></div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="card stat-card"><div class="card-body">
      <div class="stat-label">Team Deposits</div>
      <div class="stat-value"><?php echo money($stats['total_deposit']); ?></div>
      <i class="bi bi-graph-up-arrow stat-icon"></i>
    </div></div>
  </div>
</div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <span><i class="bi bi-people-fill"></i> My Team</span>
    <form class="d-flex gap-2" method="get" action="<?php echo base_url('agent/team'); ?>">
      <select name="status" class="form-select form-select-sm">
        <option value="">All status</option>
        <option value="active"  <?php echo $status === 'active'  ? 'selected' : ''; ?>>Active</option>
        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
        <option value="blocked" <?php echo $status === 'blocked' ? 'selected' : ''; ?>>Blocked</option>
      </select>
      <input type="text" name="q" class="form-control form-control-sm" placeholder="Search" value="<?php echo html_escape($search); ?>">
      <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-search"></i></button>
    </form>
  </div>

  <div class="table-wrap">
    <table class="table table-hover mb-0">
      <thead>
        <tr><th>#</th><th>Name</th><th>Username</th><th>Country</th><th>Status</th><th>Deposited</th><th>Balance</th><th>Joined</th><th></th></tr>
      </thead>
      <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="9" class="text-center text-muted py-4">
          <?php echo $agent->user_id ? 'No members match this filter.' : 'This agent account has no linked user, so it has no team.'; ?>
        </td></tr>
      <?php else: foreach ($rows as $m): ?>
        <tr>
          <td class="text-muted">#<?php echo (int) $m->id; ?></td>
          <td class="fw-semibold"><?php echo html_escape($m->full_name); ?></td>
          <td class="small"><?php echo html_escape($m->username); ?></td>
          <td class="small"><?php echo html_escape($m->country ?: '-'); ?></td>
          <td><?php echo badge($m->status); ?></td>
          <td class="small text-nowrap"><?php echo money($m->total_deposit); ?></td>
          <td class="small text-nowrap"><?php echo money($m->balance); ?></td>
          <td class="small text-muted text-nowrap"><?php echo fmt_date($m->created_at, 'd M Y'); ?></td>
          <td class="text-end"><a href="<?php echo base_url('agent/team/view/'.$m->id); ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($total > $per_page): ?>
    <div class="card-footer"><?php echo pager(base_url('agent/team'), $total, $per_page, $page); ?></div>
  <?php endif; ?>
</div>

<div class="alert alert-info mt-3 small">
  This is a read-only view. Blocking, approving or adjusting a member's balance is an admin action.
</div>
