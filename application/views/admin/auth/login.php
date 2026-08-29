<div class="card">
  <div class="card-body p-4">
    <div class="text-center mb-4">
      <i class="bi bi-shield-lock-fill fs-1 text-brand"></i>
      <h4 class="fw-bold mt-2 mb-1">Admin Panel</h4>
      <p class="text-muted small mb-0">Restricted access.</p>
    </div>

    <?php echo validation_errors('<div class="alert alert-danger py-2 small">', '</div>'); ?>

    <?php echo form_open('admin/login'); ?>
      <div class="mb-3">
        <label class="form-label">Username or Email</label>
        <input type="text" name="identity" class="form-control" value="<?php echo set_value('identity'); ?>" autofocus required>
      </div>
      <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" required>
      </div>
      <button class="btn btn-primary w-100">Sign in</button>
    <?php echo form_close(); ?>
  </div>
</div>

<p class="text-center small mt-3 mb-0">
  <a href="<?php echo base_url(); ?>" class="text-muted"><i class="bi bi-arrow-left"></i> Back to site</a>
</p>
