<div class="row g-3 justify-content-center">
  <div class="col-lg-5">
    <div class="card">
      <div class="card-header"><i class="bi bi-person-vcard"></i> Agent Details</div>
      <div class="card-body small">
        <div class="d-flex justify-content-between py-1"><span class="text-muted">Username</span><span class="fw-semibold"><?php echo html_escape($agent->username); ?></span></div>
        <div class="d-flex justify-content-between py-1"><span class="text-muted">Country</span><span><?php echo html_escape($agent->country ?: '-'); ?></span></div>
        <div class="d-flex justify-content-between py-1"><span class="text-muted">NID number</span><span><?php echo html_escape($agent->nid_number ?: '-'); ?></span></div>
        <div class="d-flex justify-content-between py-1"><span class="text-muted">Status</span><span><?php echo badge($agent->status); ?></span></div>
        <hr class="my-2">
        <div class="d-flex justify-content-between py-1">
          <span class="text-muted">Linked user</span>
          <span class="fw-semibold"><?php echo $linked ? html_escape($linked->username) : 'Not linked'; ?></span>
        </div>
        <div class="d-flex justify-content-between py-1"><span class="text-muted">Team size</span><span class="fw-semibold"><?php echo (int) $team_size; ?></span></div>
        <div class="d-flex justify-content-between py-1"><span class="text-muted">Total commission</span><span class="fw-semibold"><?php echo money($agent->total_commission); ?></span></div>
        <hr class="my-2">
        <div class="d-flex justify-content-between py-1"><span class="text-muted">Joined</span><span><?php echo fmt_date($agent->created_at); ?></span></div>
        <div class="d-flex justify-content-between py-1"><span class="text-muted">Last login</span><span><?php echo fmt_date($agent->last_login_at); ?></span></div>
      </div>
      <div class="card-footer small text-muted">
        Username, NID and commission rates are set by an admin. Ask them if any of it needs to change.
      </div>
    </div>
  </div>

  <div class="col-lg-7">
    <div class="card">
      <div class="card-header"><i class="bi bi-pencil"></i> Edit Profile</div>
      <div class="card-body">

        <?php echo validation_errors('<div class="alert alert-danger py-2 small">', '</div>'); ?>

        <?php echo form_open('agent/profile'); ?>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Name <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control" value="<?php echo set_value('name', $agent->name); ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Email <span class="text-danger">*</span></label>
              <input type="email" name="email" class="form-control" value="<?php echo set_value('email', $agent->email); ?>" required>
            </div>

            <div class="col-12"><hr class="my-1"></div>

            <div class="col-md-6">
              <label class="form-label">Current Password</label>
              <input type="password" name="current_password" class="form-control" autocomplete="current-password">
              <div class="form-text">Only needed when setting a new password.</div>
            </div>
            <div class="col-md-6">
              <label class="form-label">New Password</label>
              <input type="password" name="password" class="form-control" minlength="8" autocomplete="new-password">
              <div class="form-text">At least 8 characters. Leave blank to keep the current one.</div>
            </div>
          </div>

          <button class="btn btn-primary mt-3"><i class="bi bi-check2"></i> Save Changes</button>
        <?php echo form_close(); ?>

      </div>
    </div>
  </div>
</div>
