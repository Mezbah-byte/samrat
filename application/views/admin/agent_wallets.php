<a href="<?php echo base_url('admin/agents'); ?>" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> All agents</a>

<div class="card mb-3">
  <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
    <div>
      <div class="fw-semibold"><?php echo html_escape($a->name); ?></div>
      <div class="small text-muted"><?php echo html_escape($a->username); ?> &middot; <?php echo html_escape($a->email); ?></div>
    </div>
    <div class="d-flex gap-2 align-items-center flex-wrap">
      <?php echo badge($a->status); ?>
      <span class="badge text-bg-<?php echo (int) $a->accepting_deposits ? 'success' : 'secondary'; ?>">
        Deposits <?php echo (int) $a->accepting_deposits ? 'on' : 'off'; ?>
      </span>
      <span class="badge text-bg-<?php echo (int) $a->accepting_withdrawals ? 'success' : 'secondary'; ?>">
        Withdrawals <?php echo (int) $a->accepting_withdrawals ? 'on' : 'off'; ?>
      </span>
      <?php if (admin_can('agents.manage')): ?>
        <a href="<?php echo base_url('admin/agents/edit/'.$a->id); ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i> Edit agent</a>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="row g-3 mb-3">
  <?php foreach (array('deposit', 'withdraw', 'commission') as $w): ?>
    <div class="col-md-4">
      <div class="card stat-card">
        <div class="stat-label"><?php echo agent_wallet_label($w); ?></div>
        <div class="stat-value fs-4"><?php echo money($balances[$w]); ?></div>
        <?php if ( ! empty($reconcile[$w]) && ! $reconcile[$w]['balanced']): ?>
          <div class="small text-danger mt-1">
            <i class="bi bi-exclamation-triangle-fill"></i>
            Ledger drift <?php echo money($reconcile[$w]['drift']); ?> (ledger <?php echo money($reconcile[$w]['ledger']); ?>)
          </div>
        <?php else: ?>
          <div class="small text-muted mt-1"><i class="bi bi-check2-circle"></i> Ledger reconciles</div>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php if ($pending_float || $pending_payouts): ?>
  <div class="alert alert-warning small">
    <i class="bi bi-hourglass-split"></i>
    This agent has
    <?php if ($pending_float): ?>
      <a href="<?php echo base_url('admin/agent-float?status=pending'); ?>"><?php echo (int) $pending_float; ?> float order(s)</a>
    <?php endif; ?>
    <?php if ($pending_float && $pending_payouts): ?> and <?php endif; ?>
    <?php if ($pending_payouts): ?>
      <a href="<?php echo base_url('admin/agent-payouts?status=pending'); ?>"><?php echo (int) $pending_payouts; ?> payout(s)</a>
    <?php endif; ?>
    awaiting review.
  </div>
<?php endif; ?>

<div class="row g-3">
  <div class="col-lg-4">
    <?php if (admin_can('agents.adjust_balance')): ?>
      <div class="card">
        <div class="card-header"><i class="bi bi-pencil-square"></i> Manual Adjustment</div>
        <div class="card-body">
          <div class="alert alert-warning small">
            <i class="bi bi-exclamation-triangle"></i>
            This writes an agent balance by hand. It is recorded in the ledger and
            the activity log with the reason you type below.
          </div>
          <?php echo form_open('admin/agents/adjust/'.$a->id); ?>
            <div class="mb-2">
              <label class="form-label small">Wallet</label>
              <select name="wallet" class="form-select form-select-sm" required>
                <?php foreach (array('deposit', 'withdraw', 'commission') as $w): ?>
                  <option value="<?php echo $w; ?>"><?php echo agent_wallet_label($w); ?> (<?php echo money($balances[$w]); ?>)</option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-2">
              <label class="form-label small">Direction</label>
              <select name="direction" class="form-select form-select-sm" required>
                <option value="credit">Credit (add)</option>
                <option value="debit">Debit (remove)</option>
              </select>
            </div>
            <div class="mb-2">
              <label class="form-label small">Amount</label>
              <input type="number" step="0.00000001" min="0.00000001" name="amount" class="form-control form-control-sm" required>
            </div>
            <div class="mb-3">
              <label class="form-label small">Reason (required)</label>
              <input type="text" name="reason" class="form-control form-control-sm" maxlength="255" required placeholder="e.g. correcting float order #12 credited twice">
            </div>
            <button class="btn btn-primary w-100 btn-sm" data-confirm="Apply this adjustment to the agent's wallet?">
              <i class="bi bi-check2"></i> Apply Adjustment
            </button>
          <?php echo form_close(); ?>
        </div>
      </div>
    <?php else: ?>
      <div class="card"><div class="card-body">
        <div class="empty-state py-4"><i class="bi bi-lock"></i>You cannot adjust agent balances.</div>
      </div></div>
    <?php endif; ?>
  </div>

  <div class="col-lg-8">
    <div class="card">
      <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span><i class="bi bi-list-columns-reverse"></i> Wallet Ledger</span>
        <?php echo form_open('admin/agents/wallets/'.$a->id, array('method' => 'get', 'class' => 'd-flex gap-2 m-0')); ?>
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
          <?php echo pager(base_url('admin/agents/wallets/'.$a->id).'?wallet='.urlencode($wallet), $total, $per_page, $page); ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
