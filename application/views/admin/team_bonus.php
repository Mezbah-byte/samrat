<?php
/* The whole team bonus ladder as one form: a name, a target, a bonus, how the
 * target is measured, and an on/off switch per tier. Targets are measured
 * against a user's DIRECT referrals only. */
$active_count = count(array_filter($rows, function ($r) { return $r->status === 'active'; }));
$unlocked_all = array_sum(array_column($stats, 'unlocked'));
?>

<div class="row g-3 mb-3">
  <div class="col-md-3">
    <div class="card stat-card">
      <div class="stat-label">Tiers</div>
      <div class="stat-value fs-4"><?php echo count($rows); ?></div>
      <div class="small text-muted"><?php echo $active_count; ?> running</div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card stat-card">
      <div class="stat-label">Ladder Value</div>
      <div class="stat-value fs-4 text-brand">
        <?php echo money(array_sum(array_map(function ($r) {
          return $r->status === 'active' ? (float) $r->bonus_amount : 0;
        }, $rows))); ?>
      </div>
      <div class="small text-muted">What one user clearing every tier costs.</div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card stat-card">
      <div class="stat-label">Waiting to Claim</div>
      <div class="stat-value fs-4"><?php echo (int) $unlocked_all; ?></div>
      <div class="small text-muted">Unlocked, not yet taken.</div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card stat-card">
      <div class="stat-label">Bonus Paid</div>
      <div class="stat-value fs-4"><?php echo money($paid_total); ?></div>
      <div class="small text-muted"><a href="<?php echo base_url('admin/transactions?type=team_bonus'); ?>">See the history</a></div>
    </div>
  </div>
</div>

<?php if ( ! $rules['team_bonus_enabled']): ?>
  <div class="alert alert-warning">
    <i class="bi bi-pause-circle"></i>
    The team bonus is switched off. Nothing accrues, the user page is hidden, and no tier can unlock.
  </div>
<?php endif; ?>

<?php echo form_open('admin/team-bonus'); ?>
<div class="card mb-3">
  <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
    <span><i class="bi bi-trophy"></i> Bonus Tiers</span>
    <span class="small text-muted">Measured on what a user's direct referrals buy. Volume never resets.</span>
  </div>

  <div class="table-wrap">
    <table class="table table-hover align-middle mb-0">
      <thead>
        <tr>
          <th style="width:170px">Name</th>
          <th style="width:150px">Target</th>
          <th style="width:150px">Bonus</th>
          <th style="width:150px">Measured on</th>
          <th style="width:110px">Min buyers</th>
          <th style="width:90px">Order</th>
          <th class="text-center" style="width:90px">Live</th>
          <th class="text-end">Claimed</th>
          <th style="width:60px"></th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="9" class="text-center text-muted py-4">No tiers yet. Add one below.</td></tr>
      <?php endif; ?>
      <?php foreach ($rows as $r): ?>
        <?php
        $id = (int) $r->id;
        $s  = isset($stats[$id]) ? $stats[$id] : array('unlocked' => 0, 'claimed' => 0, 'paid' => 0.0);
        ?>
        <tr>
          <td>
            <input type="text" class="form-control form-control-sm" maxlength="80"
                   name="name[<?php echo $id; ?>]" value="<?php echo html_escape($r->name); ?>">
          </td>
          <td>
            <div class="input-group input-group-sm">
              <span class="input-group-text"><?php echo html_escape(currency()); ?></span>
              <input type="number" step="0.01" min="0" class="form-control"
                     name="target_volume[<?php echo $id; ?>]"
                     value="<?php echo html_escape(rtrim(rtrim($r->target_volume, '0'), '.')); ?>">
            </div>
          </td>
          <td>
            <div class="input-group input-group-sm">
              <span class="input-group-text"><?php echo html_escape(currency()); ?></span>
              <input type="number" step="0.01" min="0" class="form-control"
                     name="bonus_amount[<?php echo $id; ?>]"
                     value="<?php echo html_escape(rtrim(rtrim($r->bonus_amount, '0'), '.')); ?>">
            </div>
          </td>
          <td>
            <select class="form-select form-select-sm" name="mode[<?php echo $id; ?>]">
              <option value="combined" <?php echo $r->mode === 'combined' ? 'selected' : ''; ?>>Whole team combined</option>
              <option value="single"   <?php echo $r->mode === 'single'   ? 'selected' : ''; ?>>One single purchase</option>
            </select>
          </td>
          <td>
            <input type="number" step="1" min="0" class="form-control form-control-sm"
                   name="min_referrals[<?php echo $id; ?>]"
                   value="<?php echo (int) $r->min_referrals; ?>">
          </td>
          <td>
            <input type="number" step="1" min="0" class="form-control form-control-sm"
                   name="sort_order[<?php echo $id; ?>]"
                   value="<?php echo (int) $r->sort_order; ?>">
          </td>
          <td class="text-center">
            <div class="form-check form-switch d-inline-block m-0">
              <input class="form-check-input" type="checkbox"
                     name="active[<?php echo $id; ?>]" value="1"
                     <?php echo $r->status === 'active' ? 'checked' : ''; ?>>
            </div>
          </td>
          <td class="text-end">
            <span class="fw-semibold"><?php echo money($s['paid']); ?></span>
            <div class="small text-muted">
              <?php echo (int) $s['claimed']; ?> claimed
              <?php if ($s['unlocked'] > 0): ?>
                &bull; <span class="text-warning"><?php echo (int) $s['unlocked']; ?> waiting</span>
              <?php endif; ?>
            </div>
          </td>
          <td class="text-end">
            <button type="submit" class="btn btn-sm btn-ghost text-danger"
                    formaction="<?php echo base_url('admin/team-bonus/delete/'.$id); ?>"
                    formnovalidate
                    title="Remove this tier">
              <i class="bi bi-trash"></i>
            </button>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="card-footer d-flex flex-wrap justify-content-between align-items-center gap-2">
    <div class="d-flex gap-2">
      <button type="submit" class="btn btn-sm btn-outline-secondary"
              formaction="<?php echo base_url('admin/team-bonus/add'); ?>" formnovalidate>
        <i class="bi bi-plus-lg"></i> Add tier
      </button>
      <button type="submit" class="btn btn-sm btn-outline-secondary"
              formaction="<?php echo base_url('admin/team-bonus/recompute'); ?>" formnovalidate
              title="Rebuild every user's team volume from the approved deposits">
        <i class="bi bi-arrow-repeat"></i> Recompute counters
      </button>
    </div>
    <span class="small text-muted">Set a target or bonus to 0, or switch a tier off, to stop it unlocking; its history stays.</span>
  </div>
</div>

<div class="card mb-3">
  <div class="card-header"><i class="bi bi-shield-check"></i> Switches</div>
  <div class="card-body">
    <div class="form-check form-switch mb-3">
      <input class="form-check-input" type="checkbox" id="tbEnabled"
             name="team_bonus_enabled" value="1"
             <?php echo $rules['team_bonus_enabled'] ? 'checked' : ''; ?>>
      <label class="form-check-label" for="tbEnabled">
        Team Volume Bonus enabled
        <div class="small text-muted">
          Off hides the user page and the sidebar entry, and stops all accrual. Tiers already
          unlocked stay on record and become claimable again when it is switched back on.
        </div>
      </label>
    </div>

    <div class="form-check form-switch m-0">
      <input class="form-check-input" type="checkbox" id="tbActive"
             name="team_bonus_require_active_upline" value="1"
             <?php echo $rules['team_bonus_require_active_upline'] ? 'checked' : ''; ?>>
      <label class="form-check-label" for="tbActive">
        Only active accounts can unlock a bonus
        <div class="small text-muted">A blocked or pending account keeps accruing volume but unlocks nothing until it is active again.</div>
      </label>
    </div>
  </div>
  <div class="card-footer text-end">
    <button class="btn btn-primary"><i class="bi bi-check2"></i> Save ladder</button>
  </div>
</div>
<?php echo form_close(); ?>

<div class="card">
  <div class="card-header"><i class="bi bi-info-circle"></i> How it pays</div>
  <div class="card-body small text-muted mb-0">
    <p class="mb-2">
      When a deposit is approved, its amount is added to the depositor's <strong>direct
      referrer's</strong> team volume &mdash; one hop up, never the whole downline. That is separate
      from the generation commission, which still pays as before. A user can earn both on the same
      deposit.
    </p>
    <p class="mb-2">
      A <strong>combined</strong> tier looks at that summed volume. A <strong>single</strong> tier
      looks instead at the largest single purchase any one team member has made, so a $5,000 tier on
      single mode needs one member to buy $5,000 in one go. <strong>Min buyers</strong> is an extra
      gate on how many different direct referrals have bought anything at all; leave it at 0 to
      switch it off.
    </p>
    <p class="mb-2">
      Volume is lifetime cumulative and never resets, so clearing the $1,000 tier leaves that same
      $1,000 counting toward the $5,000 one. Each tier unlocks once per user, and the money only
      moves when the user presses Claim.
    </p>
    <p class="mb-0">
      Editing a tier changes what is still to come. A tier someone has already unlocked keeps the
      target and amount it had at that moment, so nobody is paid less than they were promised.
      <strong>Recompute counters</strong> rebuilds every team volume from the approved deposits; the
      nightly cron does the same, so it is only needed after a deposit is edited by hand.
    </p>
  </div>
</div>
