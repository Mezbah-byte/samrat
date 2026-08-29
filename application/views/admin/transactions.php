<?php
$types = array(
	'' => 'All types', 'deposit' => 'Deposit', 'investment' => 'Package Purchase',
	'daily_profit' => 'Daily Profit', 'referral_bonus' => 'Referral Bonus',
	'withdrawal' => 'Withdrawal', 'withdrawal_fee' => 'Withdrawal Fee',
	'refund' => 'Refund', 'admin_credit' => 'Admin Credit', 'admin_debit' => 'Admin Debit',
);
?>

<div class="row g-3 mb-3">
  <div class="col-md col-6"><div class="card stat-card"><div class="stat-label">Deposits</div><div class="stat-value fs-5"><?php echo money($totals['deposit']); ?></div></div></div>
  <div class="col-md col-6"><div class="card stat-card"><div class="stat-label">Profit Paid</div><div class="stat-value fs-5 text-success"><?php echo money($totals['daily_profit']); ?></div></div></div>
  <div class="col-md col-6"><div class="card stat-card"><div class="stat-label">Referral Paid</div><div class="stat-value fs-5"><?php echo money($totals['referral_bonus']); ?></div></div></div>
  <div class="col-md col-6"><div class="card stat-card"><div class="stat-label">Paid Out</div><div class="stat-value fs-5 text-danger"><?php echo money($totals['withdrawal']); ?></div><div class="small text-muted">Refunded <?php echo money($totals['refunded']); ?></div></div></div>
  <div class="col-md col-6"><div class="card stat-card"><div class="stat-label">Fees Kept</div><div class="stat-value fs-5"><?php echo money($totals['withdrawal_fee']); ?></div></div></div>
</div>

<div class="card">
  <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
    <span><i class="bi bi-list-columns-reverse"></i> Ledger</span>
    <?php echo form_open('admin/transactions', array('method' => 'get', 'class' => 'd-flex gap-2 m-0')); ?>
      <select name="type" class="form-select form-select-sm" data-autosubmit style="min-width:170px">
        <?php foreach ($types as $key => $label): ?>
          <option value="<?php echo $key; ?>" <?php echo $type === $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
        <?php endforeach; ?>
      </select>
      <input type="search" name="q" class="form-control form-control-sm" placeholder="User or description" value="<?php echo html_escape($search); ?>" style="min-width:200px">
      <button class="btn btn-sm btn-primary"><i class="bi bi-search"></i></button>
    <?php echo form_close(); ?>
  </div>

  <?php if (empty($rows)): ?>
    <div class="empty-state"><i class="bi bi-receipt"></i>No transactions match this filter.</div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table table-hover mb-0">
        <thead><tr><th>#</th><th>User</th><th>Type</th><th>Description</th><th class="text-end">Amount</th><th class="text-end">Balance After</th><th>Date</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $t): ?>
          <tr>
            <td class="text-muted">#<?php echo (int) $t->id; ?></td>
            <td><a href="<?php echo base_url('admin/users/view/'.$t->user_id); ?>" class="small fw-semibold"><?php echo html_escape($t->username); ?></a></td>
            <td><span class="badge text-bg-light border"><?php echo html_escape(tx_label($t->type)); ?></span></td>
            <td class="small text-muted"><?php echo html_escape($t->description); ?></td>
            <td class="text-end fw-semibold <?php echo $t->amount >= 0 ? 'text-success' : 'text-danger'; ?>"><?php echo ($t->amount >= 0 ? '+' : '-').money(abs($t->amount)); ?></td>
            <td class="text-end text-muted"><?php echo money($t->balance_after); ?></td>
            <td class="small text-muted text-nowrap"><?php echo fmt_date($t->created_at, 'd M, H:i'); ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="card-footer d-flex justify-content-between align-items-center">
      <small class="text-muted"><?php echo (int) $total; ?> transactions</small>
      <?php echo pager(base_url('admin/transactions').'?type='.urlencode($type).'&q='.urlencode($search), $total, $per_page, $page); ?>
    </div>
  <?php endif; ?>
</div>
