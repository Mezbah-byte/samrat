<?php
$summary = array(
	array('Balance',         money($user->balance),               'text-ok'),
	array('Total Deposit',   money($user->total_deposit),         ''),
	array('Total Earned',    money($user->total_earned),          'text-ok'),
	array('Total Withdrawn', money($user->total_withdrawn),       ''),
	array('Referral Bonus',  money($user->total_referral_bonus),  ''),
	array('Referrals',       (int) $referral_count,               ''),
	array('Referral ID',     html_escape($user->referral_code),   'mono'),
	array('Member Since',    fmt_date($user->created_at, 'd M Y'),''),
);
?>

<div class="page-head reveal" data-reveal-order="0">
  <div>
    <h1>Profile</h1>
    <p class="lede">Your details, security and account summary.</p>
  </div>
  <a href="<?php echo base_url('profile/password'); ?>" class="btn btn-ghost"><i data-lucide="key-round"></i> Change Password</a>
</div>

<div class="row g-3">
  <div class="col-xl-4">
    <div class="panel mb-3 reveal" data-reveal-order="1">
      <div class="panel-body text-center">
        <img src="<?php echo avatar_url($user->avatar); ?>" class="avatar-lg mb-3" alt="">
        <h5 class="mb-0"><?php echo html_escape($user->full_name); ?></h5>
        <div class="text-muted small mb-2">@<?php echo html_escape($user->username); ?></div>
        <?php echo chip($user->status); ?>

        <?php echo form_open_multipart('profile/avatar', array('class' => 'mt-3')); ?>
          <input type="file" name="avatar" class="form-control form-control-sm mb-2" accept="image/*" required>
          <button class="btn btn-ghost w-100"><i data-lucide="upload"></i> Update Picture</button>
        <?php echo form_close(); ?>
      </div>
    </div>

    <div class="panel reveal" data-reveal-order="2">
      <div class="panel-head"><i data-lucide="wallet"></i> Account Summary</div>
      <div class="panel-body">
        <?php foreach ($summary as $row): ?>
          <div class="tile-row">
            <span class="text-muted"><?php echo $row[0]; ?></span>
            <strong class="num <?php echo $row[2]; ?>"><?php echo $row[1]; ?></strong>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="col-xl-8">
    <div class="panel mb-3 reveal" data-reveal-order="3">
      <div class="panel-head"><i data-lucide="user-cog"></i> Personal Information</div>
      <div class="panel-body">
        <?php echo validation_errors('<div class="alert alert-danger py-2 small">', '</div>'); ?>

        <?php echo form_open('profile'); ?>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Full Name</label>
              <input type="text" name="full_name" class="form-control" value="<?php echo set_value('full_name', $user->full_name); ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Username</label>
              <input type="text" class="form-control" value="<?php echo html_escape($user->username); ?>" readonly disabled>
              <div class="form-text">Usernames cannot be changed.</div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-control" value="<?php echo set_value('email', $user->email); ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Mobile Number</label>
              <input type="text" name="mobile" class="form-control" value="<?php echo set_value('mobile', $user->mobile); ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Country</label>
              <select name="country" class="form-select" required>
                <?php foreach ($countries as $c): ?>
                  <option value="<?php echo html_escape($c); ?>" <?php echo $c === $user->country ? 'selected' : ''; ?>><?php echo html_escape($c); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="d-flex gap-2 mt-3">
            <button class="btn btn-grad"><i data-lucide="check"></i> Save Changes</button>
            <a href="<?php echo base_url('profile/password'); ?>" class="btn btn-quiet"><i data-lucide="key-round"></i> Change Password</a>
          </div>
        <?php echo form_close(); ?>
      </div>
    </div>

    <div class="panel reveal" data-reveal-order="4">
      <div class="panel-head"><i data-lucide="box"></i> Active Plans</div>
      <?php if (empty($active_plans)): ?>
        <div class="empty-state"><i data-lucide="inbox"></i>No active plan.</div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="table">
            <thead><tr><th>Plan</th><th class="text-end">Invested</th><th class="text-end">Daily</th><th class="text-center">Days</th><th class="text-end">Earned</th></tr></thead>
            <tbody>
            <?php foreach ($active_plans as $inv): ?>
              <tr>
                <td class="fw-semibold"><?php echo html_escape($inv->package_name); ?></td>
                <td class="text-end num"><?php echo money($inv->amount); ?></td>
                <td class="text-end num text-ok"><?php echo money($inv->daily_amount); ?></td>
                <td class="text-center num"><?php echo (int) $inv->days_credited; ?>/<?php echo (int) $inv->duration_days; ?></td>
                <td class="text-end num fw-semibold"><?php echo money($inv->total_earned); ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
