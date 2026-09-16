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

<div class="row g-3">
  <div class="col-lg-5">
    <div class="card mb-3">
      <div class="card-header"><i class="bi bi-send"></i> Request a Cash-Out</div>
      <div class="card-body">
        <div class="alert alert-info small">
          <i class="bi bi-info-circle"></i>
          The amount leaves your wallet the moment you submit, so it cannot be spent twice
          while an admin reviews it. A rejected request returns it in full.
        </div>
        <?php echo form_open('agent/payouts/create'); ?>
          <div class="mb-2">
            <label class="form-label small">From wallet</label>
            <select name="source" class="form-select form-select-sm" required>
              <option value="withdraw">Withdraw Collection (<?php echo money($balances['withdraw']); ?>)</option>
              <option value="commission">Commission (<?php echo money($balances['commission']); ?>)</option>
            </select>
            <div class="form-text">Deposit float cannot be cashed out &mdash; spend it or ask an admin.</div>
          </div>
          <div class="mb-2">
            <label class="form-label small">Amount</label>
            <input type="number" step="0.01" min="0" name="amount" class="form-control form-control-sm" required>
            <?php if ($fee_percent > 0): ?>
              <div class="form-text"><?php echo percent($fee_percent); ?> fee is deducted from what is sent.</div>
            <?php endif; ?>
          </div>
          <div class="mb-2">
            <label class="form-label small">Network</label>
            <select name="network" class="form-select form-select-sm" required>
              <?php foreach (network_list() as $code => $label): ?>
                <option value="<?php echo $code; ?>"><?php echo html_escape($label); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label small">Your wallet address</label>
            <input type="text" name="wallet_address" class="form-control form-control-sm mono" maxlength="191" required>
          </div>
          <button class="btn btn-primary w-100 btn-sm" data-confirm="Submit this cash-out request? The amount is held straight away."><i class="bi bi-send"></i> Request Cash-Out</button>
        <?php echo form_close(); ?>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><i class="bi bi-arrow-left-right"></i> Commission &rarr; Float</div>
      <div class="card-body">
        <p class="small text-muted">
          Move commission into your deposit float so you can settle more user deposits with it.
          This is the only transfer between your own wallets.
        </p>
        <?php echo form_open('agent/payouts/transfer', array('class' => 'd-flex gap-2')); ?>
          <input type="number" step="0.01" min="0" name="amount" class="form-control form-control-sm" placeholder="Amount" required>
          <button class="btn btn-outline-primary btn-sm text-nowrap" data-confirm="Move this from commission into your deposit float?">Move</button>
        <?php echo form_close(); ?>
      </div>
    </div>
  </div>

  <div class="col-lg-7">
    <div class="card">
      <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span><i class="bi bi-list-columns-reverse"></i> Cash-Out History</span>
        <?php echo form_open('agent/payouts', array('method' => 'get', 'class' => 'd-flex gap-2 m-0')); ?>
          <select name="status" class="form-select form-select-sm" data-autosubmit>
            <option value="">All statuses</option>
            <?php foreach (array('pending', 'approved', 'paid', 'rejected') as $s): ?>
              <option value="<?php echo $s; ?>" <?php echo $status === $s ? 'selected' : ''; ?>><?php echo ucfirst($s); ?></option>
            <?php endforeach; ?>
          </select>
          <button class="btn btn-sm btn-primary"><i class="bi bi-funnel"></i></button>
        <?php echo form_close(); ?>
      </div>

      <?php if (empty($rows)): ?>
        <div class="empty-state"><i class="bi bi-send"></i>No cash-outs yet.</div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="table table-hover mb-0">
            <thead><tr><th>#</th><th>From</th><th class="text-end">Requested</th><th class="text-end">To receive</th><th>Status</th><th>When</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $p): ?>
              <tr>
                <td class="text-muted">#<?php echo (int) $p->id; ?></td>
                <td class="small"><?php echo agent_wallet_label($p->source); ?></td>
                <td class="text-end small"><?php echo money($p->amount); ?></td>
                <td class="text-end fw-semibold"><?php echo money($p->net_amount); ?></td>
                <td><?php echo badge($p->status); ?></td>
                <td class="small text-muted text-nowrap"><?php echo fmt_date($p->created_at, 'd M, H:i'); ?></td>
                <td class="text-end"><a href="<?php echo base_url('agent/payouts/view/'.$p->id); ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center">
          <small class="text-muted"><?php echo (int) $total; ?> requests</small>
          <?php echo pager(base_url('agent/payouts').'?status='.urlencode($status), $total, $per_page, $page); ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
