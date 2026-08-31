<div class="page-head reveal" data-reveal-order="0">
  <div>
    <h1>Packages</h1>
    <p class="lede">Pick a plan, watch the daily ads, collect the daily profit.</p>
  </div>
  <a href="<?php echo base_url('deposit'); ?>" class="btn btn-ghost"><i data-lucide="wallet"></i> Deposit</a>
</div>

<div class="row g-3 mb-4">
  <?php foreach ($packages as $i => $p): ?>
    <?php
    $daily = (float) $p->price * (float) $p->daily_return_percent / 100;
    $total = $daily * (int) $p->duration_days;
    ?>
    <div class="col-md-6 col-xl-4">
      <div class="panel lift plan reveal" data-reveal-order="<?php echo $i + 1; ?>">
        <div class="d-flex justify-content-between align-items-start mb-2">
          <span class="plan-name"><?php echo html_escape($p->name); ?></span>
          <span class="chip chip-info"><i data-lucide="trending-up"></i> <?php echo percent($p->daily_return_percent); ?>/day</span>
        </div>

        <div class="plan-price"><?php echo money($p->price); ?></div>

        <div class="plan-list">
          <div class="tile-row"><span class="text-muted">Daily income</span><strong class="num text-ok"><?php echo money($daily); ?></strong></div>
          <div class="tile-row"><span class="text-muted">Duration</span><strong class="num"><?php echo (int) $p->duration_days; ?> days</strong></div>
          <div class="tile-row"><span class="text-muted">Daily ads</span><strong class="num"><?php echo (int) $p->daily_ads; ?></strong></div>
          <div class="tile-row"><span class="text-muted">Min. withdraw</span><strong class="num"><?php echo money($p->min_withdraw); ?></strong></div>
          <div class="tile-row"><span class="text-muted">Total return</span><strong class="num"><?php echo money($total); ?></strong></div>
        </div>

        <?php if ($p->description): ?>
          <p class="small text-muted"><?php echo html_escape($p->description); ?></p>
        <?php endif; ?>

        <a href="<?php echo base_url('packages/buy/'.$p->id); ?>" class="btn btn-grad w-100 mt-auto">
          <i data-lucide="shopping-cart"></i> Buy <?php echo html_escape($p->name); ?>
        </a>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="panel reveal" data-reveal-order="7">
  <div class="panel-head"><i data-lucide="history"></i> My Plans</div>

  <?php if (empty($investments)): ?>
    <div class="empty-state"><i data-lucide="inbox"></i>You have not purchased a package yet.</div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>#</th><th>Plan</th><th class="text-end">Invested</th><th class="text-end">Daily</th>
            <th class="text-center">Ads/day</th><th style="min-width:170px">Progress</th>
            <th class="text-end">Earned</th><th>Period</th><th>Status</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($investments as $inv): ?>
          <?php $pct = round($inv->days_credited / max(1, $inv->duration_days) * 100); ?>
          <tr>
            <td class="text-dim">#<?php echo (int) $inv->id; ?></td>
            <td class="fw-semibold"><?php echo html_escape($inv->package_name); ?></td>
            <td class="text-end num"><?php echo money($inv->amount); ?></td>
            <td class="text-end num text-ok"><?php echo money($inv->daily_amount); ?></td>
            <td class="text-center num"><?php echo (int) $inv->daily_ads; ?></td>
            <td>
              <div class="progress mb-1"><div class="progress-bar" data-bar="<?php echo $pct; ?>"></div></div>
              <small class="text-muted">
                <?php echo (int) $inv->days_credited; ?>/<?php echo (int) $inv->duration_days; ?>
                <?php if ($inv->days_missed > 0): ?><span class="text-bad">&bull; <?php echo (int) $inv->days_missed; ?> missed</span><?php endif; ?>
              </small>
            </td>
            <td class="text-end num fw-semibold"><?php echo money($inv->total_earned); ?></td>
            <td class="small text-muted text-nowrap"><?php echo fmt_date($inv->start_date, 'd M y'); ?> &rarr; <?php echo fmt_date($inv->end_date, 'd M y'); ?></td>
            <td><?php echo chip($inv->status); ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
