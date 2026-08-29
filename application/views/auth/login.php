<h1 class="auth-title">Sign in</h1>
<p class="auth-sub">Welcome back. Enter your details to continue.</p>

<?php echo validation_errors('<div class="alert alert-danger py-2 small">', '</div>'); ?>

<?php echo form_open('login'); ?>
  <div class="mb-3">
    <label class="auth-label">Username or Email</label>
    <div class="auth-field">
      <i class="bi bi-person-fill"></i>
      <input type="text" name="identity" class="form-control" value="<?php echo set_value('identity'); ?>" autofocus required>
    </div>
  </div>
  <div class="mb-3">
    <div class="d-flex justify-content-between align-items-center">
      <label class="auth-label">Password</label>
      <a href="<?php echo base_url('forgot-password'); ?>" class="auth-label text-decoration-underline">Forgot?</a>
    </div>
    <div class="auth-field">
      <i class="bi bi-lock-fill"></i>
      <input type="password" name="password" class="form-control" required>
    </div>
  </div>
  <div class="form-check mb-4">
    <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1">
    <label class="form-check-label small" for="remember">Keep me signed in</label>
  </div>
  <button class="btn-auth">Sign in</button>
<?php echo form_close(); ?>

<p class="auth-foot">
  No account yet? <a href="<?php echo base_url('register'); ?>">Create one</a>
</p>

<p class="auth-foot">
  <a href="<?php echo base_url(); ?>"><i class="bi bi-arrow-left"></i> Back to site</a>
</p>
