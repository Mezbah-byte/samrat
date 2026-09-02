<div class="page-head reveal" data-reveal-order="0">
  <div>
    <h1>Apply for Agentship</h1>
    <p class="lede">
      You have <strong><?php echo (int) $team_active; ?></strong> active team members, clearing the
      <?php echo (int) $threshold; ?> required. Fill this in once &mdash; an admin reviews it by hand.
    </p>
  </div>
</div>

<div class="row justify-content-center">
  <div class="col-lg-9">
    <div class="panel reveal" data-reveal-order="1">
      <div class="panel-head"><i data-lucide="badge-check"></i> Application</div>
      <div class="panel-body">

        <?php echo validation_errors('<div class="alert alert-danger py-2 small">', '</div>'); ?>

        <?php echo form_open_multipart('agentship/apply'); ?>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Full Name <span class="text-bad">*</span></label>
              <input type="text" name="full_name" class="form-control"
                     value="<?php echo set_value('full_name', $user->full_name); ?>" required>
              <div class="form-text">Exactly as it appears on your NID.</div>
            </div>

            <div class="col-md-6">
              <label class="form-label">Agent Username <span class="text-bad">*</span></label>
              <input type="text" name="username" class="form-control"
                     value="<?php echo set_value('username', $user->username); ?>" required>
              <div class="form-text">
                This is a separate login from your user account. Letters, numbers, dash and underscore only.
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label">Email <span class="text-bad">*</span></label>
              <input type="email" name="email" class="form-control"
                     value="<?php echo set_value('email', $user->email); ?>" required>
            </div>

            <div class="col-md-6">
              <label class="form-label">Country <span class="text-bad">*</span></label>
              <select name="country" class="form-select" required>
                <option value="">Select a country</option>
                <?php foreach (country_list() as $c): ?>
                  <option value="<?php echo html_escape($c); ?>"
                    <?php echo set_value('country', $user->country) === $c ? 'selected' : ''; ?>>
                    <?php echo html_escape($c); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-12">
              <label class="form-label">NID Number <span class="text-bad">*</span></label>
              <input type="text" name="nid_number" class="form-control" maxlength="40"
                     value="<?php echo set_value('nid_number'); ?>" required>
            </div>

            <div class="col-md-6">
              <label class="form-label">NID Front <span class="text-bad">*</span></label>
              <input type="file" name="nid_front" class="form-control" accept="image/*" required>
              <div class="form-text">JPG, PNG, GIF or WEBP, up to 4 MB.</div>
            </div>

            <div class="col-md-6">
              <label class="form-label">NID Back <span class="text-bad">*</span></label>
              <input type="file" name="nid_back" class="form-control" accept="image/*" required>
              <div class="form-text">JPG, PNG, GIF or WEBP, up to 4 MB.</div>
            </div>

            <div class="col-12">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="agree" value="1" id="agreeTerms" required
                  <?php echo set_checkbox('agree', '1'); ?>>
                <label class="form-check-label small" for="agreeTerms">
                  The details above are true and the documents are mine. I understand an agent
                  recommends decisions but cannot approve deposits or withdrawals.
                </label>
              </div>
            </div>
          </div>

          <div class="alert alert-info small mt-3 mb-3">
            <i data-lucide="shield"></i>
            Your NID images are stored privately and are only visible to admins reviewing this
            application. If approved, the admin sets your agent password and passes it to you directly.
          </div>

          <button class="btn btn-grad"><i data-lucide="send"></i> Submit Application</button>
          <a href="<?php echo base_url('agentship'); ?>" class="btn btn-quiet">Cancel</a>
        <?php echo form_close(); ?>

      </div>
    </div>
  </div>
</div>
