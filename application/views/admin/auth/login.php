<h1 class="auth-title">Admin Panel</h1>
<p class="auth-sub">Restricted access.</p>

<?php echo validation_errors('<div class="alert alert-danger py-2 small">', '</div>'); ?>

<?php echo form_open('admin/login'); ?>
  <div class="mb-3">
    <label class="auth-label">Username or Email</label>
    <div class="auth-field">
      <i class="bi bi-person-fill"></i>
      <input type="text" name="identity" class="form-control" value="<?php echo set_value('identity'); ?>" autofocus required>
    </div>
  </div>
  <div class="mb-4">
    <label class="auth-label">Password</label>
    <div class="auth-field">
      <i class="bi bi-lock-fill"></i>
      <input type="password" name="password" class="form-control" required>
    </div>
  </div>
  <button class="btn-auth">Sign in</button>
<?php echo form_close(); ?>

<p class="auth-foot">
  <a href="<?php echo base_url(); ?>"><i class="bi bi-arrow-left"></i> Back to site</a>
</p>
