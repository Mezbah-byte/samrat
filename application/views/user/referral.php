<?php
$tiles = array(
	array('Total Referrals',   (int) $total_count,     'users',       'grad-primary', 0),
	array('Commission Earned', (float) $earned_total,  'hand-coins',  'grad-success', 2),
	array('Commission Rate',   (float) $percent,       'percent',     'grad-info',    0),
);
?>

<div class="page-head reveal" data-reveal-order="0">
  <div>
    <h1>Referral</h1>
    <p class="lede">Earn <?php echo $percent; ?>% of a direct referral's deposit, paid once on approval.</p>
  </div>
</div>

<div class="row g-3 mb-3">
  <?php foreach ($tiles as $i => $t): ?>
    <?php list($label, $value, $icon, $grad, $dp) = $t; ?>
    <div class="col-md-4">
      <div class="panel lift stat h-100 reveal" data-reveal-order="<?php echo $i + 1; ?>">
        <div class="stat-top">
          <div>
            <div class="stat-label"><?php echo $label; ?></div>
            <div class="stat-value num"
                 data-count="<?php echo $value; ?>"
                 data-count-decimals="<?php echo $dp; ?>"
                 data-count-prefix="<?php echo $dp ? html_escape(currency()) : ''; ?>"
                 data-count-suffix="<?php echo $label === 'Commission Rate' ? '%' : ''; ?>">
              <?php echo $dp ? money($value) : $value.($label === 'Commission Rate' ? '%' : ''); ?>
            </div>
          </div>
          <span class="icon-tile <?php echo $grad; ?>"><i data-lucide="<?php echo $icon; ?>"></i></span>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="panel mb-3 reveal" data-reveal-order="4">
  <div class="panel-head"><i data-lucide="share-2"></i> Your Referral Link</div>
  <div class="panel-body">
    <div class="row g-3">
      <div class="col-md-4">
        <label class="form-label">Referral ID</label>
        <div class="copy-field">
          <input type="text" class="form-control" id="refCode" readonly value="<?php echo html_escape($user->referral_code); ?>">
          <button class="btn btn-ghost" type="button" data-copy-target="#refCode" aria-label="Copy referral ID"><i data-lucide="copy"></i></button>
        </div>
      </div>
      <div class="col-md-8">
        <label class="form-label">Referral Link</label>
        <div class="copy-field">
          <input type="text" class="form-control" id="refLink" readonly value="<?php echo html_escape($referral_link); ?>">
          <button class="btn btn-ghost" type="button" data-copy-target="#refLink" aria-label="Copy referral link"><i data-lucide="copy"></i></button>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-xl-6">
    <div class="panel h-100 reveal" data-reveal-order="5">
      <div class="panel-head"><i data-lucide="users"></i> My Referrals</div>
      <?php if (empty($downline)): ?>
        <div class="empty-state"><i data-lucide="user-plus"></i>No one has signed up with your ID yet.</div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="table">
            <thead><tr><th>User</th><th>Country</th><th class="text-end">Deposited</th><th>Status</th><th>Joined</th></tr></thead>
            <tbody>
            <?php foreach ($downline as $d): ?>
              <tr>
                <td>
                  <div class="fw-semibold"><?php echo html_escape($d->username); ?></div>
                  <div class="small text-muted"><?php echo html_escape($d->full_name); ?></div>
                </td>
                <td class="small"><?php echo html_escape($d->country); ?></td>
                <td class="text-end num fw-semibold"><?php echo money($d->total_deposit); ?></td>
                <td><?php echo chip($d->status); ?></td>
                <td class="small text-muted text-nowrap"><?php echo fmt_date($d->created_at, 'd M y'); ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-xl-6">
    <div class="panel h-100 reveal" data-reveal-order="6">
      <div class="panel-head"><i data-lucide="hand-coins"></i> Commission History</div>
      <?php if (empty($rows)): ?>
        <div class="empty-state"><i data-lucide="receipt"></i>No commission earned yet.</div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="table">
            <thead><tr><th>From</th><th class="text-center">Rate</th><th class="text-end">Amount</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $c): ?>
              <tr>
                <td class="fw-semibold"><?php echo html_escape($c->referred_username); ?></td>
                <td class="text-center small"><?php echo percent($c->percent); ?></td>
                <td class="text-end num fw-semibold text-ok">+<?php echo money($c->amount); ?></td>
                <td class="small text-muted text-nowrap"><?php echo fmt_date($c->created_at, 'd M, H:i'); ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="panel-foot">
          <span><?php echo (int) $total; ?> total</span>
          <?php echo pager(base_url('referral'), $total, $per_page, $page); ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
