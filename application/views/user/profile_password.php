<div class="page-head reveal" data-reveal-order="0">
  <div>
    <h1>Change Password</h1>
    <p class="lede">Pick something you do not use anywhere else.</p>
  </div>
  <a href="<?php echo base_url('profile'); ?>" class="btn btn-ghost"><i data-lucide="arrow-left"></i> Back to profile</a>
</div>

<div class="row justify-content-center">
  <div class="col-xl-6 col-lg-8">
    <div class="panel reveal" data-reveal-order="1">
      <div class="panel-head"><i data-lucide="key-round"></i> Password</div>
      <div class="panel-body">
        <?php echo validation_errors('<div class="alert alert-danger py-2 small">', '</div>'); ?>

        <?php echo form_open('profile/password'); ?>
          <div class="mb-3">
            <label class="form-label">Current Password</label>
            <input type="password" name="current_password" class="form-control" required autofocus>
          </div>
          <div class="mb-3">
            <label class="form-label">New Password</label>
            <input type="password" name="password" class="form-control" minlength="6" required>
            <div class="form-text">At least 6 characters.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Confirm New Password</label>
            <input type="password" name="confirm_password" class="form-control" minlength="6" required>
          </div>
          <div class="d-flex gap-2">
            <button class="btn btn-grad"><i data-lucide="check"></i> Update Password</button>
            <a href="<?php echo base_url('profile'); ?>" class="btn btn-quiet">Cancel</a>
          </div>
        <?php echo form_close(); ?>
      </div>
    </div>
  </div>
</div>
