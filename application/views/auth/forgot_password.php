<h1 class="auth-title">Forgot password</h1>
<p class="auth-sub">Enter the email on your account and we will generate a reset link.</p>

<?php echo validation_errors('<div class="alert alert-danger py-2 small">', '</div>'); ?>

<?php if ($issued_token): ?>
  <div class="alert alert-success small">
    <p class="fw-semibold mb-2">Reset link generated</p>
    <p class="mb-2">No mail transport is configured on this install, so the link is shown here. Configure SMTP in production and email it instead.</p>
    <div class="copy-field">
      <input type="text" class="form-control form-control-sm" id="resetLink" readonly
             value="<?php echo base_url('reset-password/'.$issued_token); ?>">
      <button class="btn btn-sm btn-outline-secondary" type="button" data-copy-target="#resetLink"><i class="bi bi-clipboard"></i></button>
    </div>
  </div>
<?php endif; ?>

<?php echo form_open('forgot-password'); ?>
  <div class="mb-4">
    <label class="auth-label">Email</label>
    <div class="auth-field">
      <i class="bi bi-envelope-fill"></i>
      <input type="email" name="email" class="form-control" value="<?php echo set_value('email'); ?>" required autofocus>
    </div>
  </div>
  <button class="btn-auth">Send reset link</button>
<?php echo form_close(); ?>

<p class="auth-foot"><a href="<?php echo base_url('login'); ?>">Back to sign in</a></p>
