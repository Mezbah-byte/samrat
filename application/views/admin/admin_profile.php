<div class="row justify-content-center">
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header"><i class="bi bi-person"></i> My Profile</div>
      <div class="card-body">
        <?php echo validation_errors('<div class="alert alert-danger py-2 small">', '</div>'); ?>

        <?php echo form_open('admin/admins/profile'); ?>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Name</label>
              <input type="text" name="name" class="form-control" value="<?php echo set_value('name', $admin->name); ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-control" value="<?php echo set_value('email', $admin->email); ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Username</label>
              <input type="text" class="form-control" value="<?php echo html_escape($admin->username); ?>" disabled readonly>
            </div>
            <div class="col-md-6">
              <label class="form-label">Role</label>
              <input type="text" class="form-control" value="<?php echo html_escape(ucwords(str_replace('_', ' ', $admin->role))); ?>" disabled readonly>
            </div>
          </div>

          <hr>
          <h6 class="fw-semibold">Change Password</h6>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Current Password</label>
              <input type="password" name="current_password" class="form-control" autocomplete="current-password">
            </div>
            <div class="col-md-6">
              <label class="form-label">New Password</label>
              <input type="password" name="password" class="form-control" minlength="8" autocomplete="new-password">
              <div class="form-text">Leave both blank to keep your current password.</div>
            </div>
          </div>

          <button class="btn btn-primary mt-3"><i class="bi bi-check2"></i> Save Changes</button>
        <?php echo form_close(); ?>
      </div>
    </div>
  </div>
</div>
