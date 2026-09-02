<div class="row g-3 mb-3">
  <div class="col-6 col-xl-3">
    <div class="card stat-card">
      <div class="card-body">
        <div class="stat-label">Total Agents</div>
        <div class="stat-value"><?php echo (int) $stats['total']; ?></div>
        <i class="bi bi-person-vcard stat-icon"></i>
      </div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="card stat-card">
      <div class="card-body">
        <div class="stat-label">Active</div>
        <div class="stat-value"><?php echo (int) $stats['active']; ?></div>
        <i class="bi bi-check2-circle stat-icon"></i>
      </div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="card stat-card">
      <div class="card-body">
        <div class="stat-label">Blocked</div>
        <div class="stat-value"><?php echo (int) $stats['blocked']; ?></div>
        <i class="bi bi-slash-circle stat-icon"></i>
      </div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="card stat-card">
      <div class="card-body">
        <div class="stat-label">Commission Accrued</div>
        <div class="stat-value"><?php echo money($stats['paid']); ?></div>
        <i class="bi bi-coin stat-icon"></i>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <span><i class="bi bi-person-vcard"></i> Agents</span>
    <div class="d-flex gap-2">
      <form class="d-flex gap-2" method="get" action="<?php echo base_url('admin/agents'); ?>">
        <select name="status" class="form-select form-select-sm">
          <option value="">All status</option>
          <option value="active"  <?php echo $status === 'active'  ? 'selected' : ''; ?>>Active</option>
          <option value="blocked" <?php echo $status === 'blocked' ? 'selected' : ''; ?>>Blocked</option>
        </select>
        <input type="text" name="q" class="form-control form-control-sm" placeholder="Search" value="<?php echo html_escape($search); ?>">
        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-search"></i></button>
      </form>
      <a href="<?php echo base_url('admin/agents/create'); ?>" class="btn btn-sm btn-primary text-nowrap"><i class="bi bi-plus-lg"></i> New Agent</a>
    </div>
  </div>

  <div class="table-wrap">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th>#</th><th>Name</th><th>Username</th><th>Email</th>
          <th>Linked User</th><th>Commission</th><th>Status</th><th>Last Login</th><th></th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="9" class="text-center text-muted py-4">No agents yet.</td></tr>
      <?php else: foreach ($rows as $a): ?>
        <tr>
          <td class="text-muted">#<?php echo (int) $a->id; ?></td>
          <td class="fw-semibold"><?php echo html_escape($a->name); ?></td>
          <td class="small"><?php echo html_escape($a->username); ?></td>
          <td class="small"><?php echo html_escape($a->email); ?></td>
          <td class="small">
            <?php if ($a->user_id): ?>
              <a href="<?php echo base_url('admin/users/view/'.(int) $a->user_id); ?>">#<?php echo (int) $a->user_id; ?></a>
            <?php else: ?>
              <span class="text-muted">Not linked</span>
            <?php endif; ?>
          </td>
          <td class="small text-nowrap"><?php echo money($a->total_commission); ?></td>
          <td><?php echo badge($a->status); ?></td>
          <td class="small text-muted text-nowrap"><?php echo fmt_date($a->last_login_at, 'd M Y, H:i'); ?></td>
          <td class="text-end text-nowrap">
            <?php if ($a->status === 'active'): ?>
              <?php echo form_open('admin/impersonate/agent/'.$a->id, array('class' => 'd-inline')); ?>
                <button class="btn btn-sm btn-outline-warning" title="Login as agent" data-confirm="Sign in as this agent? You will have full access to their panel and every action you take is logged against your admin account."><i class="bi bi-incognito"></i></button>
              <?php echo form_close(); ?>
            <?php endif; ?>
            <a href="<?php echo base_url('admin/agents/edit/'.$a->id); ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
            <?php echo form_open('admin/agents/delete/'.$a->id, array('class' => 'd-inline')); ?>
              <button class="btn btn-sm btn-outline-danger" data-confirm="Delete this agent? Their commission history is deleted too."><i class="bi bi-trash"></i></button>
            <?php echo form_close(); ?>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($total > $per_page): ?>
    <div class="card-footer"><?php echo pager(base_url('admin/agents'), $total, $per_page, $page); ?></div>
  <?php endif; ?>
</div>

<div class="alert alert-info mt-3 small">
  An agent with no linked user has no team, so their team, deposit and withdrawal screens stay empty
  and they earn no automatic commission. Link an active user account to give an agent a downline.
</div>
