<?php
$types = array(
	'' => 'All types', 'deposit' => 'Deposit', 'investment' => 'Package Purchase',
	'daily_profit' => 'Daily Profit', 'referral_bonus' => 'Referral Bonus',
	'withdrawal' => 'Withdrawal', 'withdrawal_fee' => 'Withdrawal Fee',
	'refund' => 'Refund', 'admin_credit' => 'Admin Credit', 'admin_debit' => 'Admin Debit',
);
?>

<div class="page-head reveal" data-reveal-order="0">
  <div>
    <h1>Transactions</h1>
    <p class="lede">Every balance movement, newest first.</p>
  </div>
  <?php echo form_open('transactions', array('method' => 'get', 'class' => 'm-0')); ?>
    <select name="type" class="form-select" data-autosubmit style="min-width:190px">
      <?php foreach ($types as $key => $label): ?>
        <option value="<?php echo $key; ?>" <?php echo $type === $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
      <?php endforeach; ?>
    </select>
  <?php echo form_close(); ?>
</div>

<?php if ( ! $ledger_check['balanced']): ?>
  <div class="alert alert-danger">
    <i data-lucide="octagon-alert"></i>
    Ledger mismatch detected (drift <?php echo money($ledger_check['drift']); ?>). Please contact support.
  </div>
<?php endif; ?>

<div class="panel mb-3 reveal" data-reveal-order="1">
  <div class="panel-head">
    <i data-lucide="receipt-text"></i> Ledger
    <span class="spacer"></span>
    <?php if ($ledger_check['balanced']): ?>
      <span class="chip chip-ok"><i data-lucide="shield-check"></i> Balanced</span>
    <?php endif; ?>
  </div>

  <?php if (empty($rows)): ?>
    <div class="empty-state"><i data-lucide="receipt"></i>No transactions found.</div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>#</th><th>Type</th><th>Description</th><th class="text-end">Amount</th><th class="text-end">Balance After</th><th>Date</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $t): ?>
          <?php $up = $t->amount >= 0; ?>
          <tr>
            <td class="text-dim">#<?php echo (int) $t->id; ?></td>
            <td>
              <div class="d-flex align-items-center gap-2">
                <span class="icon-tile sm <?php echo $up ? 'grad-success' : 'grad-danger'; ?>">
                  <i data-lucide="<?php echo $up ? 'arrow-down-left' : 'arrow-up-right'; ?>"></i>
                </span>
                <span class="fw-semibold text-nowrap"><?php echo html_escape(tx_label($t->type)); ?></span>
              </div>
            </td>
            <td class="small text-muted"><?php echo html_escape($t->description); ?></td>
            <td class="text-end num fw-semibold <?php echo $up ? 'text-ok' : 'text-bad'; ?>">
              <?php echo ($up ? '+' : '-').money(abs($t->amount)); ?>
            </td>
            <td class="text-end num text-muted"><?php echo money($t->balance_after); ?></td>
            <td class="small text-muted text-nowrap"><?php echo fmt_date($t->created_at, 'd M Y, H:i'); ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="panel-foot">
      <span><?php echo (int) $total; ?> total</span>
      <?php echo pager(base_url('transactions').($type ? '?type='.urlencode($type) : ''), $total, $per_page, $page); ?>
    </div>
  <?php endif; ?>
</div>

<div class="panel reveal" data-reveal-order="2">
  <div class="panel-head"><i data-lucide="calendar-days"></i> Daily Earning Log</div>
  <?php if (empty($earnings)): ?>
    <div class="empty-state"><i data-lucide="calendar-x"></i>No daily earnings recorded yet.</div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>Date</th><th>Plan</th><th class="text-center">Ads</th><th class="text-end">Amount</th><th>Status</th><th>Credited</th></tr></thead>
        <tbody>
        <?php foreach ($earnings as $e): ?>
          <tr>
            <td><?php echo fmt_date($e->earn_date, 'd M Y'); ?></td>
            <td class="small"><?php echo html_escape($e->package_name); ?></td>
            <td class="text-center num small"><?php echo (int) $e->ads_watched; ?>/<?php echo (int) $e->ads_required; ?></td>
            <td class="text-end num fw-semibold"><?php echo money($e->amount); ?></td>
            <td><?php echo chip($e->status); ?></td>
            <td class="small text-muted"><?php echo $e->credited_at ? fmt_date($e->credited_at, 'd M, H:i') : '-'; ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
