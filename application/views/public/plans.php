<div class="container section">
  <div class="section-head reveal" data-reveal-order="0">
    <h2>Investment Plans</h2>
    <p>Every plan pays a fixed daily percentage for its full term, provided the daily ad quota is met.</p>
  </div>

  <?php if (empty($packages)): ?>
    <div class="panel"><div class="empty-state"><i data-lucide="box"></i>No packages are available right now.</div></div>
  <?php else: ?>
    <div class="panel mb-4 reveal" data-reveal-order="1">
      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th>Package</th>
              <th class="text-end">Deposit</th>
              <th class="text-end">Daily Income</th>
              <th class="text-center">Duration</th>
              <th class="text-center">Daily Ads</th>
              <th class="text-end">Min. Withdraw</th>
              <th class="text-end">Total Profit</th>
              <th class="text-end">Total Return</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($packages as $p): ?>
            <?php
              $daily  = (float) $p->price * (float) $p->daily_return_percent / 100;
              $profit = $daily * (int) $p->duration_days;
            ?>
            <tr>
              <td class="fw-semibold"><?php echo html_escape($p->name); ?></td>
              <td class="text-end num"><?php echo money($p->price); ?></td>
              <td class="text-end num text-ok fw-semibold"><?php echo money($daily); ?></td>
              <td class="text-center num"><?php echo (int) $p->duration_days; ?> days</td>
              <td class="text-center"><span class="chip chip-mute"><?php echo (int) $p->daily_ads; ?>/day</span></td>
              <td class="text-end num"><?php echo money($p->min_withdraw); ?></td>
              <td class="text-end num"><?php echo money($profit); ?></td>
              <td class="text-end num fw-bold"><?php echo money((float) $p->price + $profit); ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="alert alert-warning reveal" data-reveal-order="2">
      <i data-lucide="triangle-alert"></i>
      <strong>Daily ads are mandatory.</strong> Each day you must watch the number of ads shown for your package.
      A day whose quota is not met is marked as missed and its income is lost &mdash; the plan's end date does not move.
    </div>

    <div class="text-center reveal" data-reveal-order="3">
      <a href="<?php echo base_url('register'); ?>" class="btn btn-grad px-4 py-2"><i data-lucide="user-plus"></i> Create an Account</a>
    </div>
  <?php endif; ?>
</div>
