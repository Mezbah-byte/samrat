<div class="row g-3 mb-3">
  <div class="col-md-3 col-6"><div class="card stat-card"><div class="stat-label">Total Users</div><div class="stat-value fs-5"><?php echo number_format($stats['total_users']); ?></div></div></div>
  <div class="col-md-3 col-6"><div class="card stat-card"><div class="stat-label">Active</div><div class="stat-value fs-5 text-success"><?php echo number_format($stats['active_users']); ?></div></div></div>
  <div class="col-md-3 col-6"><div class="card stat-card"><div class="stat-label">Blocked</div><div class="stat-value fs-5 text-danger"><?php echo number_format($stats['blocked_users']); ?></div></div></div>
  <div class="col-md-3 col-6"><div class="card stat-card"><div class="stat-label">Held Balances</div><div class="stat-value fs-5"><?php echo money($stats['total_balance']); ?></div></div></div>
</div>

<div class="card">
  <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
    <span><i class="bi bi-people-fill"></i> Users</span>
    <?php echo form_open('admin/users', array('method' => 'get', 'class' => 'd-flex gap-2 m-0')); ?>
      <select name="status" class="form-select form-select-sm" data-autosubmit>
        <option value="">All statuses</option>
        <?php foreach (array('active', 'pending', 'blocked') as $s): ?>
          <option value="<?php echo $s; ?>" <?php echo $status === $s ? 'selected' : ''; ?>><?php echo ucfirst($s); ?></option>
        <?php endforeach; ?>
      </select>
      <input type="search" name="q" class="form-control form-control-sm" placeholder="Search name, email, ref ID" value="<?php echo html_escape($search); ?>" style="min-width:220px">
      <button class="btn btn-sm btn-primary"><i class="bi bi-search"></i></button>
    <?php echo form_close(); ?>
  </div>

  <?php if (empty($rows)): ?>
    <div class="empty-state"><i class="bi bi-person-x"></i>No users match this filter.</div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table table-hover mb-0">
        <thead>
          <tr><th>#</th><th>User</th><th>Contact</th><th>Country</th><th>Ref ID</th>
              <th class="text-end">Balance</th><th class="text-end">Deposited</th><th class="text-end">Earned</th>
              <th>Status</th><th>Joined</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $u): ?>
          <tr>
            <td class="text-muted">#<?php echo (int) $u->id; ?></td>
            <td>
              <div class="d-flex align-items-center gap-2">
                <img src="<?php echo avatar_url($u->avatar); ?>" class="avatar-sm" alt="">
                <div>
                  <div class="small fw-semibold"><?php echo html_escape($u->username); ?></div>
                  <div class="small text-muted"><?php echo html_escape($u->full_name); ?></div>
                </div>
              </div>
            </td>
            <td class="small">
              <div><?php echo html_escape($u->email); ?></div>
              <div class="text-muted"><?php echo html_escape($u->mobile); ?></div>
            </td>
            <td class="small"><?php echo html_escape($u->country); ?></td>
            <td class="mono small"><?php echo html_escape($u->referral_code); ?></td>
            <td class="text-end fw-semibold"><?php echo money($u->balance); ?></td>
            <td class="text-end"><?php echo money($u->total_deposit); ?></td>
            <td class="text-end text-success"><?php echo money($u->total_earned); ?></td>
            <td><?php echo badge($u->status); ?></td>
            <td class="small text-muted text-nowrap"><?php echo fmt_date($u->created_at, 'd M y'); ?></td>
            <td class="text-end"><a href="<?php echo base_url('admin/users/view/'.$u->id); ?>" class="btn btn-sm btn-outline-primary">Manage</a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="card-footer d-flex justify-content-between align-items-center">
      <small class="text-muted"><?php echo (int) $total; ?> users</small>
      <?php echo pager(base_url('admin/users').'?status='.urlencode($status).'&q='.urlencode($search), $total, $per_page, $page); ?>
    </div>
  <?php endif; ?>
</div>
