<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span><i class="bi bi-person-badge"></i> Admin Users</span>
    <a href="<?php echo base_url('admin/admins/create'); ?>" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> New Admin</a>
  </div>

  <div class="table-wrap">
    <table class="table table-hover mb-0">
      <thead><tr><th>#</th><th>Name</th><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th>Last Login</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($rows as $a): ?>
        <tr>
          <td class="text-muted">#<?php echo (int) $a->id; ?></td>
          <td class="fw-semibold"><?php echo html_escape($a->name); ?></td>
          <td class="small"><?php echo html_escape($a->username); ?></td>
          <td class="small"><?php echo html_escape($a->email); ?></td>
          <td><span class="badge text-bg-<?php echo $a->role === 'super_admin' ? 'primary' : 'secondary'; ?>"><?php echo html_escape(str_replace('_', ' ', $a->role)); ?></span></td>
          <td><?php echo badge($a->status); ?></td>
          <td class="small text-muted text-nowrap"><?php echo fmt_date($a->last_login_at, 'd M Y, H:i'); ?></td>
          <td class="text-end text-nowrap">
            <a href="<?php echo base_url('admin/admins/edit/'.$a->id); ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
            <?php if ((int) $a->id !== (int) $admin->id): ?>
              <?php echo form_open('admin/admins/delete/'.$a->id, array('class' => 'd-inline')); ?>
                <button class="btn btn-sm btn-outline-danger" data-confirm="Delete this admin account?"><i class="bi bi-trash"></i></button>
              <?php echo form_close(); ?>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="alert alert-info mt-3 small">
  <i class="bi bi-info-circle"></i>
  <strong>Roles.</strong> <em>Super admin</em> manages admin accounts, settings and the cron secret.
  <em>Admin</em> handles day-to-day operations including approvals and balance adjustments.
  <em>Moderator</em> can view and approve but not delete or adjust balances.
</div>
