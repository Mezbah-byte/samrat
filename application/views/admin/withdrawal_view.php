<a href="<?php echo base_url('admin/withdrawals'); ?>" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> All withdrawals</a>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span>Withdrawal #<?php echo (int) $withdrawal->id; ?></span>
        <?php echo badge($withdrawal->status); ?>
      </div>
      <ul class="list-group list-group-flush">
        <li class="list-group-item d-flex justify-content-between">
          <span class="text-muted">User</span>
          <a href="<?php echo base_url('admin/users/view/'.$withdrawal->user_id); ?>"><?php echo html_escape($withdrawal->username); ?> &middot; <?php echo html_escape($withdrawal->full_name); ?></a>
        </li>
        <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Email</span><span class="small"><?php echo html_escape($withdrawal->email); ?></span></li>
        <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Current balance</span><strong><?php echo money($withdrawal->balance); ?></strong></li>
        <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Requested</span><strong><?php echo money($withdrawal->amount); ?></strong></li>
        <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Fee (<?php echo percent($withdrawal->fee_percent); ?>)</span><strong class="text-danger"><?php echo money($withdrawal->fee); ?></strong></li>
        <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Send to user</span><strong class="text-success fs-5"><?php echo money($withdrawal->net_amount); ?></strong></li>
        <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Network</span><span class="badge text-bg-light border"><?php echo html_escape($withdrawal->network); ?></span></li>
        <li class="list-group-item">
          <div class="text-muted small mb-1">Destination wallet</div>
          <div class="copy-field">
            <input type="text" class="form-control form-control-sm" id="destWallet" readonly value="<?php echo html_escape($withdrawal->wallet_address); ?>">
            <button class="btn btn-sm btn-outline-secondary" type="button" data-copy-target="#destWallet"><i class="bi bi-clipboard"></i></button>
          </div>
        </li>
        <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Requested at</span><span class="small"><?php echo fmt_date($withdrawal->created_at); ?></span></li>
        <?php if ($withdrawal->processed_at): ?>
          <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Processed at</span><span class="small"><?php echo fmt_date($withdrawal->processed_at); ?></span></li>
        <?php endif; ?>
        <?php if ($withdrawal->txid): ?>
          <li class="list-group-item"><div class="text-muted small mb-1">Payout TXID</div><div class="mono small"><?php echo html_escape($withdrawal->txid); ?></div></li>
        <?php endif; ?>
        <?php if ($withdrawal->admin_note): ?>
          <li class="list-group-item"><div class="text-muted small mb-1">Admin note</div><?php echo html_escape($withdrawal->admin_note); ?></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>

  <div class="col-lg-5">
    <?php if ( ! empty($withdrawal->agent_recommendation)): ?>
      <div class="card mb-3">
        <div class="card-header"><i class="bi bi-person-vcard"></i> Agent Recommendation</div>
        <div class="card-body">
          <p class="mb-2">
            <?php echo badge($withdrawal->agent_recommendation === 'approve' ? 'approved' : 'rejected'); ?>
            The agent recommends <strong><?php echo html_escape($withdrawal->agent_recommendation); ?></strong>,
            <?php echo fmt_date($withdrawal->agent_reviewed_at); ?>.
          </p>
          <?php if ( ! empty($withdrawal->agent_note)): ?>
            <div class="text-muted small mb-1">Agent note</div>
            <div class="small"><?php echo html_escape($withdrawal->agent_note); ?></div>
          <?php endif; ?>
          <p class="text-muted small mb-0 mt-2">Advisory only. The decision below is yours.</p>
        </div>
      </div>
    <?php endif; ?>

    <?php if (in_array($withdrawal->status, array('pending', 'approved'), TRUE)): ?>
      <div class="card mb-3">
        <div class="card-header"><i class="bi bi-check2-square"></i> Actions</div>
        <div class="card-body">
          <div class="alert alert-info small">
            <?php echo money($withdrawal->amount); ?> was already held from the user's balance when they submitted.
            Rejecting returns the full amount.
          </div>

          <?php if ($withdrawal->status === 'pending'): ?>
            <?php echo form_open('admin/withdrawals/approve/'.$withdrawal->id, array('class' => 'mb-3')); ?>
              <label class="form-label small">Note (optional)</label>
              <input type="text" name="admin_note" class="form-control form-control-sm mb-2" maxlength="500">
              <button class="btn btn-primary w-100" data-confirm="Mark this request as approved for payout?">
                <i class="bi bi-check2"></i> Approve for Payout
              </button>
            <?php echo form_close(); ?>
            <hr>
          <?php endif; ?>

          <?php echo form_open('admin/withdrawals/mark_paid/'.$withdrawal->id, array('class' => 'mb-3')); ?>
            <label class="form-label small">Payout TXID <span class="text-danger">*</span></label>
            <input type="text" name="txid" class="form-control form-control-sm mono mb-2" maxlength="191" required
                   placeholder="Hash of the transfer you sent">
            <input type="hidden" name="admin_note" value="<?php echo html_escape($withdrawal->admin_note); ?>">
            <button class="btn btn-success w-100" data-confirm="Confirm the payout was sent and close this request?">
              <i class="bi bi-send-check"></i> Mark as Paid
            </button>
          <?php echo form_close(); ?>

          <hr>

          <?php echo form_open('admin/withdrawals/reject/'.$withdrawal->id); ?>
            <label class="form-label small">Rejection reason</label>
            <input type="text" name="admin_note" class="form-control form-control-sm mb-2" maxlength="500">
            <button class="btn btn-outline-danger w-100" data-confirm="Reject and refund <?php echo money($withdrawal->amount); ?> to the user?">
              <i class="bi bi-x-lg"></i> Reject &amp; Refund
            </button>
          <?php echo form_close(); ?>
        </div>
      </div>
    <?php else: ?>
      <div class="card">
        <div class="card-body text-center text-muted py-4">
          <i class="bi bi-lock fs-2 opacity-50"></i>
          <p class="mb-0 mt-2 small">This request is <?php echo html_escape($withdrawal->status); ?> and can no longer be changed.</p>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>
