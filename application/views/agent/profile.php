<div class="page-head reveal" data-reveal-order="0">
  <div>
    <h1>My Profile</h1>
    <p class="lede">Your account details and sign-in credentials.</p>
  </div>
  <?php echo chip($agent->status); ?>
</div>

<div class="row g-3">
  <div class="col-xl-5">
    <div class="panel reveal" data-reveal-order="1">
      <div class="panel-head"><i data-lucide="id-card"></i> Agent Details</div>
      <div class="panel-body">
        <div class="tile mb-3">
          <div class="tile-row"><span class="text-muted">Username</span><strong><?php echo html_escape($agent->username); ?></strong></div>
          <div class="tile-row"><span class="text-muted">Country</span><span><?php echo html_escape($agent->country ?: '-'); ?></span></div>
          <div class="tile-row"><span class="text-muted">NID number</span><span class="mono small"><?php echo html_escape($agent->nid_number ?: '-'); ?></span></div>
        </div>

        <div class="tile mb-3">
          <div class="tile-row">
            <span class="text-muted">Linked user</span>
            <?php if ($linked): ?>
              <strong><?php echo html_escape($linked->username); ?></strong>
            <?php else: ?>
              <span class="chip chip-warn">Not linked</span>
            <?php endif; ?>
          </div>
          <div class="tile-row"><span class="text-muted">Team size</span><strong class="num"><?php echo (int) $team_size; ?></strong></div>
          <div class="tile-row"><span class="text-muted">Total commission</span><strong class="num text-ok"><?php echo money($agent->total_commission); ?></strong></div>
        </div>

        <?php if ( ! empty($float_on)): ?>
          <label class="form-label">Float wallets</label>
          <div class="tile mb-3">
            <div class="tile-row"><span class="text-muted">Deposit float</span><strong class="num"><?php echo money($agent->deposit_balance); ?></strong></div>
            <div class="tile-row"><span class="text-muted">Withdraw collection</span><strong class="num"><?php echo money($agent->withdraw_balance); ?></strong></div>
            <div class="tile-row"><span class="text-muted">Commission</span><strong class="num"><?php echo money($agent->commission_balance); ?></strong></div>
          </div>
          <div class="d-flex gap-2 mb-3">
            <span class="chip <?php echo (int) $agent->accepting_deposits ? 'chip-ok' : 'chip-mute'; ?>">
              Deposits <?php echo (int) $agent->accepting_deposits ? 'on' : 'off'; ?>
            </span>
            <span class="chip <?php echo (int) $agent->accepting_withdrawals ? 'chip-ok' : 'chip-mute'; ?>">
              Withdrawals <?php echo (int) $agent->accepting_withdrawals ? 'on' : 'off'; ?>
            </span>
          </div>
        <?php endif; ?>

        <div class="tile">
          <div class="tile-row"><span class="text-muted">Joined</span><span class="text-muted small"><?php echo fmt_date($agent->created_at); ?></span></div>
          <div class="tile-row"><span class="text-muted">Last login</span><span class="text-muted small"><?php echo fmt_date($agent->last_login_at); ?></span></div>
        </div>
      </div>
      <div class="panel-foot">
        <span class="small text-muted">
          <i data-lucide="lock"></i>
          Username, NID, commission rates and the accepting switches are set by an admin.
        </span>
      </div>
    </div>
  </div>

  <div class="col-xl-7">
    <div class="panel reveal" data-reveal-order="2">
      <div class="panel-head"><i data-lucide="pencil"></i> Edit Profile</div>
      <div class="panel-body">
        <?php echo validation_errors('<div class="alert alert-danger py-2 small">', '</div>'); ?>

        <?php echo form_open('agent/profile'); ?>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Name <span class="text-bad">*</span></label>
              <input type="text" name="name" class="form-control" value="<?php echo set_value('name', $agent->name); ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Email <span class="text-bad">*</span></label>
              <input type="email" name="email" class="form-control" value="<?php echo set_value('email', $agent->email); ?>" required>
            </div>

            <div class="col-12"><hr class="my-1" style="border-color:var(--line)"></div>

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

            <div class="col-12">
              <button class="btn btn-grad"><i data-lucide="check"></i> Save Changes</button>
            </div>
          </div>
        <?php echo form_close(); ?>
      </div>
    </div>
  </div>
</div>
