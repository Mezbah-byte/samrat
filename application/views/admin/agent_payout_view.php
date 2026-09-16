<a href="<?php echo base_url('admin/agent-payouts'); ?>" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> All payouts</a>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span>Agent Payout #<?php echo (int) $payout->id; ?></span>
        <?php echo badge($payout->status); ?>
      </div>
      <ul class="list-group list-group-flush">
        <li class="list-group-item d-flex justify-content-between">
          <span class="text-muted">Agent</span>
          <a href="<?php echo base_url('admin/agents/wallets/'.$payout->agent_id); ?>"><?php echo html_escape($payout->agent_username); ?> &middot; <?php echo html_escape($payout->agent_name); ?></a>
        </li>
        <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Email</span><span class="small"><?php echo html_escape($payout->agent_email); ?></span></li>
        <li class="list-group-item d-flex justify-content-between">
          <span class="text-muted">Drawn from</span>
          <span><span class="badge text-bg-light border"><?php echo ucfirst($payout->source); ?> wallet</span></span>
        </li>
        <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Requested</span><strong><?php echo money($payout->amount); ?></strong></li>
        <?php if ((float) $payout->fee > 0): ?>
          <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Fee (<?php echo percent($payout->fee_percent); ?>)</span><span class="text-danger">-<?php echo money($payout->fee); ?></span></li>
        <?php endif; ?>
        <li class="list-group-item d-flex justify-content-between"><span class="text-muted">To send</span><strong class="text-brand fs-5"><?php echo money($payout->net_amount); ?></strong></li>
        <li class="list-group-item d-flex justify-content-between">
          <span class="text-muted">Wallet balances now</span>
          <span class="small">withdraw <?php echo money($payout->withdraw_balance); ?> &middot; commission <?php echo money($payout->commission_balance); ?></span>
        </li>
        <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Network</span><span class="badge text-bg-light border"><?php echo html_escape($payout->network); ?></span></li>
        <li class="list-group-item">
          <div class="text-muted small mb-1">Send to</div>
          <div class="copy-field">
            <input type="text" class="form-control form-control-sm" id="addrField" readonly value="<?php echo html_escape($payout->wallet_address); ?>">
            <button class="btn btn-sm btn-outline-secondary" type="button" data-copy-target="#addrField"><i class="bi bi-clipboard"></i></button>
          </div>
        </li>
        <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Requested at</span><span class="small"><?php echo fmt_date($payout->created_at); ?></span></li>
        <?php if ($payout->processed_at): ?>
          <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Processed</span><span class="small"><?php echo fmt_date($payout->processed_at); ?></span></li>
        <?php endif; ?>
        <?php if ($payout->txid): ?>
          <li class="list-group-item"><div class="text-muted small mb-1">Payout TXID</div><div class="mono small"><?php echo html_escape($payout->txid); ?></div></li>
        <?php endif; ?>
        <?php if ($payout->admin_note): ?>
          <li class="list-group-item"><div class="text-muted small mb-1">Admin note</div><?php echo html_escape($payout->admin_note); ?></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>

  <div class="col-lg-5">
    <?php if (in_array($payout->status, array('pending', 'approved'), TRUE)): ?>
      <div class="card">
        <div class="card-header"><i class="bi bi-check2-square"></i> Actions</div>
        <div class="card-body">
          <div class="alert alert-info small mb-3">
            <i class="bi bi-info-circle"></i>
            <?php echo money($payout->amount); ?> was already taken out of the agent's
            <?php echo html_escape($payout->source); ?> wallet when they made this request.
            Approving moves nothing; rejecting returns it.
          </div>

          <?php if ($payout->status === 'pending' && admin_can('agent_payouts.approve')): ?>
            <?php echo form_open('admin/agent-payouts/approve/'.$payout->id, array('class' => 'mb-3')); ?>
              <label class="form-label small">Note (optional)</label>
              <input type="text" name="admin_note" class="form-control form-control-sm mb-2" maxlength="500">
              <button class="btn btn-success w-100" data-confirm="Approve this payout for sending?">
                <i class="bi bi-check2"></i> Approve
              </button>
            <?php echo form_close(); ?>
            <hr>
          <?php endif; ?>

          <?php if (admin_can('agent_payouts.mark_paid')): ?>
            <?php echo form_open('admin/agent-payouts/mark_paid/'.$payout->id, array('class' => 'mb-3')); ?>
              <label class="form-label small">Payout transaction hash</label>
              <input type="text" name="txid" class="form-control form-control-sm mb-2" maxlength="191" required placeholder="Paste the hash of the transfer you sent">
              <input type="text" name="admin_note" class="form-control form-control-sm mb-2" maxlength="500" placeholder="Note (optional)">
              <button class="btn btn-primary w-100" data-confirm="Mark this payout as paid?">
                <i class="bi bi-send"></i> Mark as Paid
              </button>
            <?php echo form_close(); ?>
            <hr>
          <?php endif; ?>

          <?php if (admin_can('agent_payouts.reject')): ?>
            <?php echo form_open('admin/agent-payouts/reject/'.$payout->id); ?>
              <label class="form-label small">Rejection reason</label>
              <input type="text" name="admin_note" class="form-control form-control-sm mb-2" maxlength="500">
              <button class="btn btn-outline-danger w-100" data-confirm="Reject this payout and return <?php echo money($payout->amount); ?> to the agent?">
                <i class="bi bi-x-lg"></i> Reject &amp; Return
              </button>
            <?php echo form_close(); ?>
          <?php endif; ?>
        </div>
      </div>
    <?php else: ?>
      <div class="card"><div class="card-body">
        <div class="empty-state py-4"><i class="bi bi-check2-circle"></i>This payout is <?php echo html_escape($payout->status); ?>. Nothing left to do.</div>
      </div></div>
    <?php endif; ?>
  </div>
</div>
