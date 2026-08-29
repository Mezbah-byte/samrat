<h1 class="auth-title">Register Now</h1>
<p class="auth-sub">All fields are required unless marked optional.</p>

<?php echo validation_errors('<div class="alert alert-danger py-2 small">', '</div>'); ?>

<?php echo form_open('register'); ?>
  <div class="row g-3">
    <div class="col-md-6">
      <label class="auth-label">Full Name</label>
      <div class="auth-field">
        <i class="bi bi-person-fill"></i>
        <input type="text" name="full_name" class="form-control" value="<?php echo set_value('full_name'); ?>" required>
      </div>
    </div>
    <div class="col-md-6">
      <label class="auth-label">Username</label>
      <div class="auth-field">
        <i class="bi bi-at"></i>
        <input type="text" name="username" class="form-control" value="<?php echo set_value('username'); ?>" required>
      </div>
      <div class="form-text">Letters, numbers, dashes and underscores only.</div>
    </div>
    <div class="col-md-6">
      <label class="auth-label">Email</label>
      <div class="auth-field">
        <i class="bi bi-envelope-fill"></i>
        <input type="email" name="email" class="form-control" value="<?php echo set_value('email', $this->input->get('email', TRUE)); ?>" required>
      </div>
    </div>
    <div class="col-md-6">
      <label class="auth-label">Mobile Number</label>
      <div class="auth-field">
        <i class="bi bi-phone-fill"></i>
        <input type="text" name="mobile" class="form-control" value="<?php echo set_value('mobile'); ?>" required>
      </div>
    </div>
    <div class="col-md-6">
      <label class="auth-label">Country</label>
      <div class="auth-field">
        <i class="bi bi-globe2"></i>
        <select name="country" class="form-select" required>
          <option value="">Select country</option>
          <?php foreach ($countries as $c): ?>
            <option value="<?php echo html_escape($c); ?>" <?php echo set_select('country', $c); ?>><?php echo html_escape($c); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="col-md-6">
      <label class="auth-label">Referral ID <span class="fw-normal">(optional)</span></label>
      <div class="auth-field">
        <i class="bi bi-people-fill"></i>
        <input type="text" name="referral_code" class="form-control text-uppercase"
               value="<?php echo html_escape($referral_code); ?>" maxlength="16">
      </div>
    </div>
    <div class="col-md-6">
      <label class="auth-label">Password</label>
      <div class="auth-field">
        <i class="bi bi-lock-fill"></i>
        <input type="password" name="password" class="form-control" minlength="6" required>
      </div>
    </div>
    <div class="col-md-6">
      <label class="auth-label">Confirm Password</label>
      <div class="auth-field">
        <i class="bi bi-shield-lock-fill"></i>
        <input type="password" name="confirm_password" class="form-control" minlength="6" required>
      </div>
    </div>
  </div>

  <div class="form-check mt-3 mb-4">
    <input class="form-check-input" type="checkbox" name="agree" id="agree" value="1" <?php echo set_checkbox('agree', '1'); ?> required>
    <label class="form-check-label small" for="agree">
      I understand that deposits are made in cryptocurrency, are irreversible, and that returns are not guaranteed.
    </label>
  </div>

  <button class="btn-auth">Create Account</button>
<?php echo form_close(); ?>

<p class="auth-foot">
  Already registered? <a href="<?php echo base_url('login'); ?>">Sign in</a>
</p>

<p class="auth-foot">
  <a href="<?php echo base_url(); ?>"><i class="bi bi-arrow-left"></i> Back to site</a>
</p>
