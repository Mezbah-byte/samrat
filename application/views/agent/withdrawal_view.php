<a href="<?php echo base_url('agent/withdrawals'); ?>" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> All withdrawals</a>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-cash-stack"></i> Withdrawal #<?php echo (int) $withdrawal->id; ?></span>
        <?php echo badge($withdrawal->status); ?>
      </div>
      <div class="card-body small">
        <div class="d-flex justify-content-between py-1"><span class="text-muted">User</span><span class="fw-semibold"><?php echo html_escape($withdrawal->username); ?></span></div>
        <div class="d-flex justify-content-between py-1"><span class="text-muted">Full name</span><span><?php echo html_escape($withdrawal->full_name); ?></span></div>
        <div class="d-flex justify-content-between py-1"><span class="text-muted">Current balance</span><span><?php echo money($withdrawal->balance); ?></span></div>
        <hr class="my-2">
        <div class="d-flex justify-content-between py-1"><span class="text-muted">Requested</span><span><?php echo money($withdrawal->amount); ?></span></div>
        <div class="d-flex justify-content-between py-1"><span class="text-muted">Fee (<?php echo percent($withdrawal->fee_percent); ?>)</span><span><?php echo money($withdrawal->fee); ?></span></div>
        <div class="d-flex justify-content-between py-1"><span class="text-muted">Net payout</span><span class="fw-semibold"><?php echo money($withdrawal->net_amount); ?></span></div>
        <hr class="my-2">
        <div class="d-flex justify-content-between py-1"><span class="text-muted">Network</span><span><?php echo html_escape($withdrawal->network); ?></span></div>
        <div class="d-flex justify-content-between py-1"><span class="text-muted">Wallet</span><span class="text-break"><?php echo html_escape($withdrawal->wallet_address); ?></span></div>
        <div class="d-flex justify-content-between py-1"><span class="text-muted">Requested at</span><span><?php echo fmt_date($withdrawal->created_at); ?></span></div>
        <?php if ($withdrawal->txid): ?>
          <div class="d-flex justify-content-between py-1"><span class="text-muted">Payout TXID</span><span class="text-break"><?php echo html_escape($withdrawal->txid); ?></span></div>
        <?php endif; ?>

        <?php if ($withdrawal->admin_note): ?>
          <hr class="my-2">
          <div class="text-muted">Admin note</div>
          <div><?php echo html_escape($withdrawal->admin_note); ?></div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card">
      <div class="card-header"><i class="bi bi-clipboard-check"></i> My Recommendation</div>
      <div class="card-body">

        <?php if ($withdrawal->agent_recommendation): ?>
          <div class="alert alert-<?php echo $withdrawal->agent_recommendation === 'approve' ? 'success' : 'danger'; ?> py-2 small mb-3">
            You recommended <strong><?php echo html_escape($withdrawal->agent_recommendation); ?></strong>
            on <?php echo fmt_date($withdrawal->agent_reviewed_at); ?>.
            <?php if ($withdrawal->agent_note): ?><br>Note: <?php echo html_escape($withdrawal->agent_note); ?><?php endif; ?>
          </div>
        <?php endif; ?>

        <?php if ($withdrawal->status !== 'pending'): ?>
          <p class="text-muted small mb-0">
            This request is already <?php echo html_escape($withdrawal->status); ?>.
            No further recommendation is possible.
          </p>
        <?php else: ?>
          <p class="text-muted small">
            Your recommendation is advisory. An admin approves the payout and sends the funds.
          </p>

          <?php echo form_open('agent/withdrawals/recommend/'.$withdrawal->id); ?>
            <div class="mb-3">
              <label class="form-label">Note <span class="text-muted">(required to reject)</span></label>
              <textarea name="agent_note" class="form-control" rows="3" maxlength="500"><?php echo set_value('agent_note', $withdrawal->agent_note); ?></textarea>
            </div>
            <button name="recommendation" value="approve" class="btn btn-success w-100 mb-2">
              <i class="bi bi-hand-thumbs-up"></i> Recommend Approve
            </button>
            <button name="recommendation" value="reject" class="btn btn-outline-danger w-100"
                    data-confirm="Recommend rejecting this withdrawal?">
              <i class="bi bi-hand-thumbs-down"></i> Recommend Reject
            </button>
          <?php echo form_close(); ?>
        <?php endif; ?>

      </div>
    </div>
  </div>
</div>
