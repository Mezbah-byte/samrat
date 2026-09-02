<a href="<?php echo base_url('agent/deposits'); ?>" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> All deposits</a>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-inbox-fill"></i> Deposit #<?php echo (int) $deposit->id; ?></span>
        <?php echo badge($deposit->status); ?>
      </div>
      <div class="card-body small">
        <div class="d-flex justify-content-between py-1"><span class="text-muted">User</span><span class="fw-semibold"><?php echo html_escape($deposit->username); ?></span></div>
        <div class="d-flex justify-content-between py-1"><span class="text-muted">Full name</span><span><?php echo html_escape($deposit->full_name); ?></span></div>
        <div class="d-flex justify-content-between py-1"><span class="text-muted">Package</span><span><?php echo html_escape($deposit->package_name); ?></span></div>
        <div class="d-flex justify-content-between py-1"><span class="text-muted">Amount</span><span class="fw-semibold"><?php echo money($deposit->amount); ?></span></div>
        <div class="d-flex justify-content-between py-1"><span class="text-muted">Method</span><span><?php echo html_escape($deposit->method_name ?: '-'); ?></span></div>
        <div class="d-flex justify-content-between py-1"><span class="text-muted">Network</span><span><?php echo html_escape($deposit->network ?: '-'); ?></span></div>
        <div class="d-flex justify-content-between py-1"><span class="text-muted">TXID</span><span class="text-break"><?php echo html_escape($deposit->txid); ?></span></div>
        <div class="d-flex justify-content-between py-1"><span class="text-muted">Submitted</span><span><?php echo fmt_date($deposit->created_at); ?></span></div>

        <?php if ($deposit->proof_image): ?>
          <hr class="my-2">
          <div class="text-muted mb-2">Payment screenshot</div>
          <a href="<?php echo upload_url('deposits', $deposit->proof_image); ?>" target="_blank" rel="noopener">
            <img src="<?php echo upload_url('deposits', $deposit->proof_image); ?>" alt="Payment proof" class="img-thumbnail" style="max-height:260px">
          </a>
        <?php endif; ?>

        <?php if ($deposit->admin_note): ?>
          <hr class="my-2">
          <div class="text-muted">Admin note</div>
          <div><?php echo html_escape($deposit->admin_note); ?></div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card">
      <div class="card-header"><i class="bi bi-clipboard-check"></i> My Recommendation</div>
      <div class="card-body">

        <?php if ($deposit->agent_recommendation): ?>
          <div class="alert alert-<?php echo $deposit->agent_recommendation === 'approve' ? 'success' : 'danger'; ?> py-2 small mb-3">
            You recommended <strong><?php echo html_escape($deposit->agent_recommendation); ?></strong>
            on <?php echo fmt_date($deposit->agent_reviewed_at); ?>.
            <?php if ($deposit->agent_note): ?><br>Note: <?php echo html_escape($deposit->agent_note); ?><?php endif; ?>
          </div>
        <?php endif; ?>

        <?php if ($deposit->status !== 'pending'): ?>
          <p class="text-muted small mb-0">
            This deposit has already been <?php echo html_escape($deposit->status); ?> by an admin.
            No further recommendation is possible.
          </p>
        <?php else: ?>
          <p class="text-muted small">
            Your recommendation is advisory. An admin makes the final decision and moves the money.
          </p>

          <?php echo form_open('agent/deposits/recommend/'.$deposit->id); ?>
            <div class="mb-3">
              <label class="form-label">Note <span class="text-muted">(required to reject)</span></label>
              <textarea name="agent_note" class="form-control" rows="3" maxlength="500"><?php echo set_value('agent_note', $deposit->agent_note); ?></textarea>
            </div>
            <button name="recommendation" value="approve" class="btn btn-success w-100 mb-2">
              <i class="bi bi-hand-thumbs-up"></i> Recommend Approve
            </button>
            <button name="recommendation" value="reject" class="btn btn-outline-danger w-100"
                    data-confirm="Recommend rejecting this deposit?">
              <i class="bi bi-hand-thumbs-down"></i> Recommend Reject
            </button>
          <?php echo form_close(); ?>
        <?php endif; ?>

      </div>
    </div>
  </div>
</div>
