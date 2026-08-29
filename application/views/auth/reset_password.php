<h1 class="auth-title">Set a new password</h1>
<p class="auth-sub">Choose a password of at least 6 characters.</p>

<?php echo validation_errors('<div class="alert alert-danger py-2 small">', '</div>'); ?>

<?php echo form_open('reset-password/'.$token); ?>
  <div class="mb-3">
    <label class="auth-label">New Password</label>
    <div class="auth-field">
      <i class="bi bi-lock-fill"></i>
      <input type="password" name="password" class="form-control" minlength="6" required autofocus>
    </div>
  </div>
  <div class="mb-4">
    <label class="auth-label">Confirm Password</label>
    <div class="auth-field">
      <i class="bi bi-shield-lock-fill"></i>
      <input type="password" name="confirm_password" class="form-control" minlength="6" required>
    </div>
  </div>
  <button class="btn-auth">Update password</button>
<?php echo form_close(); ?>

<p class="auth-foot"><a href="<?php echo base_url('login'); ?>">Back to sign in</a></p>
