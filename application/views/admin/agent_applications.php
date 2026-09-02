<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <span>
      <i class="bi bi-clipboard-check"></i> Agentship Applications
      <?php if ($pending_count): ?><span class="badge text-bg-warning ms-2"><?php echo (int) $pending_count; ?> pending</span><?php endif; ?>
    </span>
    <form class="d-flex gap-2" method="get" action="<?php echo base_url('admin/agent-applications'); ?>">
      <select name="status" class="form-select form-select-sm">
        <option value="">All status</option>
        <option value="pending"  <?php echo $status === 'pending'  ? 'selected' : ''; ?>>Pending</option>
        <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>Approved</option>
        <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
      </select>
      <input type="text" name="q" class="form-control form-control-sm" placeholder="Name, username, NID" value="<?php echo html_escape($search); ?>">
      <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-search"></i></button>
    </form>
  </div>

  <div class="table-wrap">
    <table class="table table-hover mb-0">
      <thead>
        <tr><th>#</th><th>Applicant</th><th>User</th><th>Country</th><th>Team at Submit</th><th>Status</th><th>Submitted</th><th></th></tr>
      </thead>
      <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="8" class="text-center text-muted py-4">No applications match this filter.</td></tr>
      <?php else: foreach ($rows as $a): ?>
        <tr>
          <td class="text-muted">#<?php echo (int) $a->id; ?></td>
          <td class="fw-semibold"><?php echo html_escape($a->full_name); ?><div class="small text-muted"><?php echo html_escape($a->email); ?></div></td>
          <td class="small">
            <?php if ($a->user_username): ?>
              <a href="<?php echo base_url('admin/users/view/'.(int) $a->user_id); ?>"><?php echo html_escape($a->user_username); ?></a>
            <?php else: ?>
              <span class="text-muted">Deleted</span>
            <?php endif; ?>
          </td>
          <td class="small"><?php echo html_escape($a->country ?: '-'); ?></td>
          <td class="small">
            <span class="<?php echo $a->team_active_count >= $threshold ? 'text-success fw-semibold' : 'text-danger'; ?>">
              <?php echo (int) $a->team_active_count; ?>
            </span>
            <span class="text-muted">/ <?php echo (int) $threshold; ?></span>
          </td>
          <td><?php echo badge($a->status); ?></td>
          <td class="small text-muted text-nowrap"><?php echo fmt_date($a->created_at, 'd M Y'); ?></td>
          <td class="text-end"><a href="<?php echo base_url('admin/agent-applications/view/'.$a->id); ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($total > $per_page): ?>
    <div class="card-footer"><?php echo pager(base_url('admin/agent-applications'), $total, $per_page, $page); ?></div>
  <?php endif; ?>
</div>

<div class="alert alert-info mt-3 small">
  Approving opens the agent create form prefilled from the application. The account is only created
  once you save that form with a password &mdash; nothing is generated or emailed automatically.
</div>
