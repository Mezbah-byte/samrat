<?php
/* The whole referral ladder as one form: a rate and an on/off switch per
 * generation, plus the two rules that decide which upline accounts qualify.
 * Generation 1 is the direct referrer. */
?>

<div class="row g-3 mb-3">
  <div class="col-md-4">
    <div class="card stat-card">
      <div class="stat-label">Generations</div>
      <div class="stat-value fs-4"><?php echo count($rows); ?></div>
      <div class="small text-muted"><?php echo count(array_filter($rows, function ($r) { return $r->status === 'active'; })); ?> paying</div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card stat-card">
      <div class="stat-label">Total Payout Rate</div>
      <div class="stat-value fs-4 text-brand"><?php echo rtrim(rtrim(number_format($total_pct, 2), '0'), '.'); ?>%</div>
      <div class="small text-muted">Of every approved deposit, across all generations.</div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card stat-card">
      <div class="stat-label">Commission Paid</div>
      <div class="stat-value fs-4"><?php echo money(array_sum(array_column($paid, 'paid'))); ?></div>
      <div class="small text-muted"><a href="<?php echo base_url('admin/referrals'); ?>">See the history</a></div>
    </div>
  </div>
</div>

<?php echo form_open('admin/referral-levels'); ?>
<div class="card mb-3">
  <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
    <span><i class="bi bi-diagram-3"></i> Generations</span>
    <span class="small text-muted">Paid once, when a deposit is approved.</span>
  </div>

  <div class="table-wrap">
    <table class="table table-hover align-middle mb-0">
      <thead>
        <tr>
          <th>Generation</th>
          <th>Who earns</th>
          <th style="width:180px">Rate (%)</th>
          <th class="text-center" style="width:110px">Paying</th>
          <th class="text-end">Paid so far</th>
          <th style="width:60px"></th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $i => $r): ?>
        <?php
        $lvl     = (int) $r->level;
        $stats   = isset($paid[$lvl]) ? $paid[$lvl] : array('deals' => 0, 'paid' => 0);
        $is_top  = $i === count($rows) - 1;
        $who     = $lvl === 1
            ? 'The direct referrer'
            : 'The referrer '.$lvl.' steps up the tree';
        ?>
        <tr>
          <td class="fw-semibold">G<?php echo $lvl; ?></td>
          <td class="small text-muted"><?php echo $who; ?></td>
          <td>
            <div class="input-group input-group-sm">
              <input type="number" step="0.0001" min="0" max="100"
                     class="form-control"
                     name="percent[<?php echo (int) $r->id; ?>]"
                     value="<?php echo html_escape(rtrim(rtrim($r->percent, '0'), '.')); ?>">
              <span class="input-group-text">%</span>
            </div>
          </td>
          <td class="text-center">
            <div class="form-check form-switch d-inline-block m-0">
              <input class="form-check-input" type="checkbox"
                     name="active[<?php echo (int) $r->id; ?>]" value="1"
                     <?php echo $r->status === 'active' ? 'checked' : ''; ?>>
            </div>
          </td>
          <td class="text-end">
            <span class="fw-semibold"><?php echo money($stats['paid']); ?></span>
            <div class="small text-muted"><?php echo (int) $stats['deals']; ?> payouts</div>
          </td>
          <td class="text-end">
            <?php if ($is_top && count($rows) > 1): ?>
              <button type="submit" class="btn btn-sm btn-ghost text-danger"
                      formaction="<?php echo base_url('admin/referral-levels/delete/'.(int) $r->id); ?>"
                      formnovalidate
                      title="Remove the highest generation">
                <i class="bi bi-trash"></i>
              </button>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="card-footer d-flex flex-wrap justify-content-between align-items-center gap-2">
    <button type="submit" class="btn btn-sm btn-outline-secondary"
            formaction="<?php echo base_url('admin/referral-levels/add'); ?>" formnovalidate>
      <i class="bi bi-plus-lg"></i> Add generation
    </button>
    <span class="small text-muted">Set a rate to 0 or switch a generation off to stop it paying; its history stays.</span>
  </div>
</div>

<div class="card mb-3">
  <div class="card-header"><i class="bi bi-shield-check"></i> Who qualifies</div>
  <div class="card-body">
    <div class="form-check form-switch mb-3">
      <input class="form-check-input" type="checkbox" id="ruleActive"
             name="referral_require_active_upline" value="1"
             <?php echo $rules['referral_require_active_upline'] ? 'checked' : ''; ?>>
      <label class="form-check-label" for="ruleActive">
        Pay only active upline accounts
        <div class="small text-muted">A blocked or suspended account is skipped, and the generations above it still earn.</div>
      </label>
    </div>

    <div class="form-check form-switch m-0">
      <input class="form-check-input" type="checkbox" id="ruleInvested"
             name="referral_require_upline_investment" value="1"
             <?php echo $rules['referral_require_upline_investment'] ? 'checked' : ''; ?>>
      <label class="form-check-label" for="ruleInvested">
        Upline must hold an active plan to earn
        <div class="small text-muted">Off by default. Switch it on to stop dormant accounts collecting from a downline.</div>
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
      When a deposit is approved, the commission walks up from the depositor: their referrer is
      generation 1, that person's referrer generation 2, and so on for as many generations as exist
      above. Each generation is paid its own rate on the <strong>deposit amount</strong>, once, and it
      lands in that account's balance immediately.
    </p>
    <p class="mb-0">
      A generation that is switched off is skipped without cutting the walk short - the generations
      above it still earn. Nobody is paid twice for the same deposit, even if the deposit is
      re-approved.
    </p>
  </div>
</div>
