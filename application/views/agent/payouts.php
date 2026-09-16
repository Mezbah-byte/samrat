<?php
$cashable = $balances['withdraw'] + $balances['commission'];

$tiles = array(
	array('label' => 'Deposit Float',       'value' => $balances['deposit'],    'icon' => 'coins',
	      'grad' => 'grad-primary', 'note' => 'Not cashable - spend it', 'link' => 'agent/float'),
	array('label' => 'Withdraw Collection', 'value' => $balances['withdraw'],   'icon' => 'piggy-bank',
	      'grad' => 'grad-teal',    'note' => 'From withdrawals you paid'),
	array('label' => 'Commission',          'value' => $balances['commission'], 'icon' => 'sparkles',
	      'grad' => 'grad-success', 'note' => 'Cash out or top up float'),
);
?>

<div class="page-head reveal" data-reveal-order="0">
  <div>
    <h1>Cash Out</h1>
    <p class="lede">
      <strong class="text-accent"><?php echo money($cashable); ?></strong> available to withdraw from the platform.
    </p>
  </div>
  <a href="<?php echo base_url('agent/ledger'); ?>" class="btn btn-ghost"><i data-lucide="receipt-text"></i> Ledger</a>
</div>

<?php $this->load->view('agent/_tiles', array('tiles' => $tiles, 'tiles_offset' => 1, 'tiles_cols' => 'col-md-4')); ?>

<div class="row g-3">
  <div class="col-xl-5">
    <div class="panel mb-3 reveal" data-reveal-order="4">
      <div class="panel-head"><i data-lucide="banknote"></i> Request a Cash-Out</div>
      <div class="panel-body">
        <div class="d-flex gap-2 align-items-start mb-3">
          <span class="icon-tile sm grad-info"><i data-lucide="info"></i></span>
          <p class="small text-muted mb-0">
            The amount leaves your wallet the moment you submit, so it cannot be spent twice while
            an admin reviews it. A rejected request returns it in full.
          </p>
        </div>

        <?php echo form_open('agent/payouts/create'); ?>
          <div class="mb-3">
            <label class="form-label">From wallet <span class="text-bad">*</span></label>
            <select name="source" class="form-select" required>
              <option value="withdraw">Withdraw Collection &mdash; <?php echo money($balances['withdraw']); ?></option>
              <option value="commission">Commission &mdash; <?php echo money($balances['commission']); ?></option>
            </select>
            <div class="form-text">Deposit float cannot be cashed out. Spend it, or ask an admin.</div>
          </div>

          <div class="mb-3">
            <label class="form-label">Amount <span class="text-bad">*</span></label>
            <div class="input-group">
              <span class="input-group-text"><?php echo html_escape(currency()); ?></span>
              <input type="number" step="0.01" min="0" name="amount" class="form-control" required>
            </div>
            <?php if ($fee_percent > 0): ?>
              <div class="form-text"><?php echo percent($fee_percent); ?> fee is deducted from what is sent.</div>
            <?php endif; ?>
          </div>

          <div class="mb-3">
            <label class="form-label">Network <span class="text-bad">*</span></label>
            <select name="network" class="form-select" required>
              <?php foreach (network_list() as $code => $label): ?>
                <option value="<?php echo $code; ?>"><?php echo html_escape($label); ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-4">
            <label class="form-label">Your wallet address <span class="text-bad">*</span></label>
            <input type="text" name="wallet_address" class="form-control mono" maxlength="191" required>
            <div class="form-text text-bad">Double-check it &mdash; a wrong address cannot be recovered.</div>
          </div>

          <button class="btn btn-grad w-100" data-confirm="Submit this cash-out request? The amount is held straight away.">
            <i data-lucide="send"></i> Request Cash-Out
          </button>
        <?php echo form_close(); ?>
      </div>
    </div>

    <div class="panel reveal" data-reveal-order="5">
      <div class="panel-head"><i data-lucide="arrow-left-right"></i> Commission &rarr; Float</div>
      <div class="panel-body">
        <p class="small text-muted">
          Move commission into your deposit float to settle more user deposits with it. This is the
          only transfer allowed between your own wallets.
        </p>
        <?php echo form_open('agent/payouts/transfer', array('class' => 'd-flex gap-2')); ?>
          <div class="input-group">
            <span class="input-group-text"><?php echo html_escape(currency()); ?></span>
            <input type="number" step="0.01" min="0" name="amount" class="form-control" placeholder="Amount" required>
          </div>
          <button class="btn btn-quiet text-nowrap" data-confirm="Move this from commission into your deposit float?">
            <i data-lucide="arrow-right"></i> Move
          </button>
        <?php echo form_close(); ?>
      </div>
    </div>
  </div>

  <div class="col-xl-7">
    <div class="panel h-100 reveal" data-reveal-order="6">
      <div class="panel-head">
        <i data-lucide="history"></i> Cash-Out History
        <span class="spacer"></span>
        <?php echo form_open('agent/payouts', array('method' => 'get', 'class' => 'd-flex gap-2 m-0')); ?>
          <select name="status" class="form-select form-select-sm" data-autosubmit style="width:auto">
            <option value="">All statuses</option>
            <?php foreach (array('pending', 'approved', 'paid', 'rejected') as $s): ?>
              <option value="<?php echo $s; ?>" <?php echo $status === $s ? 'selected' : ''; ?>><?php echo ucfirst($s); ?></option>
            <?php endforeach; ?>
          </select>
          <button class="btn btn-quiet btn-icon" aria-label="Filter"><i data-lucide="filter"></i></button>
        <?php echo form_close(); ?>
      </div>

      <?php if (empty($rows)): ?>
        <div class="empty-state"><i data-lucide="banknote"></i>No cash-outs yet.</div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="table">
            <thead><tr><th>#</th><th>From</th><th class="text-end">Requested</th><th class="text-end">To receive</th><th>Status</th><th>When</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $p): ?>
              <tr>
                <td class="text-dim">#<?php echo (int) $p->id; ?></td>
                <td class="text-muted"><?php echo agent_wallet_label($p->source); ?></td>
                <td class="text-end num"><?php echo money($p->amount); ?></td>
                <td class="text-end num fw-semibold"><?php echo money($p->net_amount); ?></td>
                <td><?php echo chip($p->status); ?></td>
                <td class="text-muted text-nowrap small"><?php echo fmt_date($p->created_at, 'd M, H:i'); ?></td>
                <td class="text-end"><a href="<?php echo base_url('agent/payouts/view/'.$p->id); ?>" class="btn btn-ghost btn-icon"><i data-lucide="eye"></i></a></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="panel-foot">
          <span><?php echo (int) $total; ?> request<?php echo $total == 1 ? '' : 's'; ?></span>
          <?php echo pager(base_url('agent/payouts').'?status='.urlencode($status), $total, $per_page, $page); ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
