<?php
$tiles = array(
	array('label' => 'Deposit Float',       'value' => $balances['deposit'],    'icon' => 'coins',
	      'grad' => 'grad-primary', 'note' => 'Spendable on deposits', 'link' => 'agent/float'),
	array('label' => 'Withdraw Collection', 'value' => $balances['withdraw'],   'icon' => 'piggy-bank',
	      'grad' => 'grad-teal',    'note' => 'Cash out from here',    'link' => 'agent/payouts'),
	array('label' => 'Commission',          'value' => $balances['commission'], 'icon' => 'sparkles',
	      'grad' => 'grad-success', 'note' => 'Cash out or move to float', 'link' => 'agent/payouts'),
);
?>

<div class="page-head reveal" data-reveal-order="0">
  <div>
    <h1>Wallet Ledger</h1>
    <p class="lede">Every movement across your three wallets. The balances above are the sum of these rows.</p>
  </div>
</div>

<?php $this->load->view('agent/_tiles', array('tiles' => $tiles, 'tiles_offset' => 1, 'tiles_cols' => 'col-md-4')); ?>

<div class="panel reveal" data-reveal-order="4">
  <div class="panel-head">
    <i data-lucide="receipt-text"></i> Movements
    <span class="spacer"></span>
    <?php echo form_open('agent/ledger', array('method' => 'get', 'class' => 'd-flex gap-2 m-0')); ?>
      <select name="wallet" class="form-select form-select-sm" data-autosubmit style="width:auto">
        <option value="">All wallets</option>
        <?php foreach (array('deposit', 'withdraw', 'commission') as $w): ?>
          <option value="<?php echo $w; ?>" <?php echo $wallet === $w ? 'selected' : ''; ?>><?php echo agent_wallet_label($w); ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn btn-quiet btn-icon" aria-label="Filter"><i data-lucide="filter"></i></button>
    <?php echo form_close(); ?>
  </div>

  <?php if (empty($rows)): ?>
    <div class="empty-state">
      <i data-lucide="receipt-text"></i>
      <p class="mb-3">No movements yet. Buy float to get started.</p>
      <a href="<?php echo base_url('agent/float/create'); ?>" class="btn btn-grad"><i data-lucide="coins"></i> Buy Float</a>
    </div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>When</th><th>Wallet</th><th>Type</th><th>Detail</th><th class="text-end">Amount</th><th class="text-end">Balance after</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $t): ?>
          <?php $neg = (float) $t->amount < 0; ?>
          <tr>
            <td class="text-muted text-nowrap small"><?php echo fmt_date($t->created_at, 'd M, H:i'); ?></td>
            <td><span class="chip chip-mute"><?php echo agent_wallet_label($t->wallet); ?></span></td>
            <td>
              <span class="d-inline-flex align-items-center gap-2">
                <span class="dot <?php echo $neg ? 'dot-bad' : 'dot-ok'; ?>"></span>
                <?php echo agent_tx_label($t->type); ?>
              </span>
            </td>
            <td class="text-muted small"><?php echo html_escape($t->description ?: '-'); ?></td>
            <td class="text-end num fw-semibold <?php echo $neg ? 'text-bad' : 'text-ok'; ?>">
              <?php echo ($neg ? '-' : '+').money(abs((float) $t->amount)); ?>
            </td>
            <td class="text-end num text-dim"><?php echo money($t->balance_after); ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="panel-foot">
      <span><?php echo (int) $total; ?> movement<?php echo $total == 1 ? '' : 's'; ?></span>
      <?php echo pager(base_url('agent/ledger').'?wallet='.urlencode($wallet), $total, $per_page, $page); ?>
    </div>
  <?php endif; ?>
</div>
