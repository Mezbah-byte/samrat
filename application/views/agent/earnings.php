<?php
$tiles = array(
	array('label' => 'Total Earned', 'value' => $earned_total, 'icon' => 'coins',
	      'grad' => 'grad-primary', 'note' => 'Lifetime, every source'),
	array('label' => 'This Month', 'value' => $earned_month, 'icon' => 'calendar-check',
	      'grad' => 'grad-info', 'note' => 'Since the 1st'),
	array('label' => 'From Team Activity', 'value' => $by_source['deposit']['earned'] + $by_source['daily_profit']['earned'],
	      'icon' => 'users', 'grad' => 'grad-teal', 'note' => 'Deposits + daily profit'),
	array('label' => 'From Transactions', 'value' => $by_source['agent_deposit']['earned'] + $by_source['agent_withdraw']['earned'],
	      'icon' => 'arrow-left-right', 'grad' => 'grad-success', 'note' => 'Settled and paid by you'),
);

$labels = array(
	'deposit'        => 'Team deposit',
	'daily_profit'   => 'Daily profit',
	'agent_deposit'  => 'Deposit you settled',
	'agent_withdraw' => 'Withdrawal you paid',
);

$filters = $float_on
	? array('' => 'All sources', 'deposit' => 'Team deposits', 'daily_profit' => 'Daily profit',
	        'agent_deposit' => 'Deposits settled', 'agent_withdraw' => 'Withdrawals paid')
	: array('' => 'All sources', 'deposit' => 'Team deposits', 'daily_profit' => 'Daily profit');
?>

<div class="page-head reveal" data-reveal-order="0">
  <div>
    <h1>Earnings</h1>
    <p class="lede">Every commission you have booked, and the rate behind each one.</p>
  </div>
</div>

<?php $this->load->view('agent/_tiles', array('tiles' => $tiles, 'tiles_offset' => 1)); ?>

<div class="row g-3 mb-3">
  <div class="col-xl-5">
    <div class="panel h-100 reveal" data-reveal-order="5">
      <div class="panel-head"><i data-lucide="percent"></i> Your Rates</div>
      <div class="panel-body">
        <div class="tile mb-3">
          <div class="tile-row">
            <span class="text-muted">Team deposit approved</span>
            <strong class="num"><?php echo percent($deposit_pct); ?></strong>
          </div>
          <div class="tile-row">
            <span class="text-muted">Team daily profit</span>
            <strong class="num"><?php echo percent($profit_pct); ?></strong>
          </div>
          <?php if ($float_on): ?>
            <div class="tile-row">
              <span class="text-muted">Deposit you settle</span>
              <strong class="num"><?php echo percent($settle_pct); ?></strong>
            </div>
            <div class="tile-row">
              <span class="text-muted">Withdrawal you pay</span>
              <strong class="num"><?php echo percent($withdraw_pct); ?></strong>
            </div>
          <?php endif; ?>
        </div>

        <p class="small text-muted mb-0">
          <?php if ($float_on): ?>
            <i data-lucide="split"></i>
            Team commission lands in
            <?php echo $agent->user_id ? 'your linked user wallet' : 'an unsettled balance an admin pays by hand'; ?>;
            transaction commission lands in your
            <a href="<?php echo base_url('agent/payouts'); ?>">commission wallet</a>.
          <?php elseif ($agent->user_id): ?>
            <i data-lucide="wallet"></i> Commission is credited to your linked user wallet as it accrues.
          <?php else: ?>
            <i data-lucide="alert-triangle"></i>
            No linked user wallet, so commission accrues here and an admin settles it by hand.
            <strong><?php echo money($unsettled); ?></strong> is currently unsettled.
          <?php endif; ?>
        </p>
      </div>
    </div>
  </div>

  <div class="col-xl-7">
    <div class="panel h-100 reveal" data-reveal-order="6">
      <div class="panel-head"><i data-lucide="pie-chart"></i> Breakdown by Source</div>
      <div class="panel-body">
        <?php
          $max = 0;
          foreach ($by_source as $s) { $max = max($max, (float) $s['earned']); }
        ?>
        <?php foreach ($labels as $key => $label): ?>
          <?php if ( ! $float_on && in_array($key, array('agent_deposit', 'agent_withdraw'), TRUE)) { continue; } ?>
          <?php $row = $by_source[$key]; $pct = $max > 0 ? round($row['earned'] / $max * 100) : 0; ?>
          <div class="mb-3">
            <div class="d-flex justify-content-between align-items-baseline mb-1">
              <span><?php echo $label; ?> <span class="text-dim small">(<?php echo (int) $row['deals']; ?>)</span></span>
              <strong class="num"><?php echo money($row['earned']); ?></strong>
            </div>
            <div class="progress"><div class="progress-bar" data-bar="<?php echo $pct; ?>"></div></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<div class="panel reveal" data-reveal-order="7">
  <div class="panel-head">
    <i data-lucide="history"></i> Commission History
    <span class="spacer"></span>
    <?php echo form_open('agent/earnings', array('method' => 'get', 'class' => 'd-flex gap-2 m-0')); ?>
      <select name="source" class="form-select form-select-sm" data-autosubmit style="width:auto">
        <?php foreach ($filters as $k => $label): ?>
          <option value="<?php echo $k; ?>" <?php echo $source === $k ? 'selected' : ''; ?>><?php echo $label; ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn btn-quiet btn-icon" aria-label="Filter"><i data-lucide="filter"></i></button>
    <?php echo form_close(); ?>
  </div>

  <?php if (empty($rows)): ?>
    <div class="empty-state"><i data-lucide="coins"></i>No commission earned yet.</div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>#</th><th>Member</th><th>Source</th><th class="text-end">Base</th><th class="text-end">Rate</th>
                   <th class="text-end">Earned</th><th>Paid into</th><th>Date</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $c): ?>
          <tr>
            <td class="text-dim">#<?php echo (int) $c->id; ?></td>
            <td class="fw-semibold"><?php echo html_escape($c->member_username ?: 'Deleted user'); ?></td>
            <td class="text-muted"><?php echo isset($labels[$c->source]) ? $labels[$c->source] : html_escape($c->source); ?></td>
            <td class="text-end num text-dim"><?php echo money($c->base_amount); ?></td>
            <td class="text-end num text-dim"><?php echo percent($c->percent); ?></td>
            <td class="text-end num fw-semibold text-ok"><?php echo money($c->amount); ?></td>
            <td>
              <?php if (isset($c->wallet) && $c->wallet === 'commission'): ?>
                <span class="chip chip-ok">Commission wallet</span>
              <?php elseif ($c->settled): ?>
                <span class="chip chip-info">User wallet</span>
              <?php else: ?>
                <span class="chip chip-warn">Unsettled</span>
              <?php endif; ?>
            </td>
            <td class="text-muted text-nowrap small"><?php echo fmt_date($c->created_at, 'd M Y, H:i'); ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="panel-foot">
      <span><?php echo (int) $total; ?> entr<?php echo $total == 1 ? 'y' : 'ies'; ?></span>
      <?php echo pager(base_url('agent/earnings').'?source='.urlencode($source), $total, $per_page, $page); ?>
    </div>
  <?php endif; ?>
</div>
