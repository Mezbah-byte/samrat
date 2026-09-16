<div class="row g-3 mb-3">
  <?php foreach (array('deposit', 'withdraw', 'commission') as $w): ?>
    <div class="col-md-4">
      <div class="card stat-card">
        <div class="card-body">
          <div class="stat-label"><?php echo agent_wallet_label($w); ?></div>
          <div class="stat-value"><?php echo money($balances[$w]); ?></div>
          <i class="bi bi-<?php echo $w === 'deposit' ? 'wallet2' : ($w === 'withdraw' ? 'cash-stack' : 'coin'); ?> stat-icon"></i>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="card">
  <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
    <span><i class="bi bi-list-columns-reverse"></i> Wallet Ledger</span>
    <?php echo form_open('agent/ledger', array('method' => 'get', 'class' => 'd-flex gap-2 m-0')); ?>
      <select name="wallet" class="form-select form-select-sm" data-autosubmit>
        <option value="">All wallets</option>
        <?php foreach (array('deposit', 'withdraw', 'commission') as $w): ?>
          <option value="<?php echo $w; ?>" <?php echo $wallet === $w ? 'selected' : ''; ?>><?php echo agent_wallet_label($w); ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn btn-sm btn-primary"><i class="bi bi-funnel"></i></button>
    <?php echo form_close(); ?>
  </div>

  <?php if (empty($rows)): ?>
    <div class="empty-state"><i class="bi bi-list-columns-reverse"></i>No movements yet.</div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table table-hover mb-0">
        <thead><tr><th>When</th><th>Wallet</th><th>Type</th><th>Detail</th><th class="text-end">Amount</th><th class="text-end">Balance</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $t): ?>
          <tr>
            <td class="small text-muted text-nowrap"><?php echo fmt_date($t->created_at, 'd M, H:i'); ?></td>
            <td><span class="badge text-bg-light border"><?php echo agent_wallet_label($t->wallet); ?></span></td>
            <td class="small"><?php echo agent_tx_label($t->type); ?></td>
            <td class="small text-muted"><?php echo html_escape($t->description ?: '-'); ?></td>
            <td class="text-end fw-semibold <?php echo (float) $t->amount < 0 ? 'text-danger' : 'text-success'; ?>">
              <?php echo ((float) $t->amount < 0 ? '-' : '+').money(abs((float) $t->amount)); ?>
            </td>
            <td class="text-end small text-muted"><?php echo money($t->balance_after); ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="card-footer d-flex justify-content-between align-items-center">
      <small class="text-muted"><?php echo (int) $total; ?> movements</small>
      <?php echo pager(base_url('agent/ledger').'?wallet='.urlencode($wallet), $total, $per_page, $page); ?>
    </div>
  <?php endif; ?>
</div>
