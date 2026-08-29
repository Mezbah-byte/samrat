<a href="<?php echo base_url('admin/deposits'); ?>" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> All deposits</a>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span>Deposit #<?php echo (int) $deposit->id; ?></span>
        <?php echo badge($deposit->status); ?>
      </div>
      <ul class="list-group list-group-flush">
        <li class="list-group-item d-flex justify-content-between">
          <span class="text-muted">User</span>
          <a href="<?php echo base_url('admin/users/view/'.$deposit->user_id); ?>"><?php echo html_escape($deposit->username); ?> &middot; <?php echo html_escape($deposit->full_name); ?></a>
        </li>
        <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Email</span><span class="small"><?php echo html_escape($deposit->email); ?></span></li>
        <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Package</span><strong><?php echo html_escape($deposit->package_name); ?></strong></li>
        <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Amount</span><strong class="text-brand fs-5"><?php echo money($deposit->amount); ?></strong></li>
        <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Network</span><span class="badge text-bg-light border"><?php echo html_escape($deposit->network); ?></span></li>
        <li class="list-group-item">
          <div class="text-muted small mb-1">Company wallet used</div>
          <div class="mono small"><?php echo html_escape($deposit->wallet_address ?: '-'); ?></div>
        </li>
        <li class="list-group-item">
          <div class="text-muted small mb-1">Transaction hash (verify this on-chain)</div>
          <div class="copy-field">
            <input type="text" class="form-control form-control-sm" id="txidField" readonly value="<?php echo html_escape($deposit->txid); ?>">
            <button class="btn btn-sm btn-outline-secondary" type="button" data-copy-target="#txidField"><i class="bi bi-clipboard"></i></button>
          </div>
          <div class="mt-2 d-flex gap-2 flex-wrap">
            <?php if ($deposit->network === 'TRC20'): ?>
              <a class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener" href="https://tronscan.org/#/transaction/<?php echo urlencode($deposit->txid); ?>">Open in Tronscan</a>
            <?php elseif ($deposit->network === 'BEP20'): ?>
              <a class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener" href="https://bscscan.com/tx/<?php echo urlencode($deposit->txid); ?>">Open in BscScan</a>
            <?php elseif ($deposit->network === 'ERC20'): ?>
              <a class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener" href="https://etherscan.io/tx/<?php echo urlencode($deposit->txid); ?>">Open in Etherscan</a>
            <?php endif; ?>
          </div>
        </li>
        <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Submitted</span><span class="small"><?php echo fmt_date($deposit->created_at); ?></span></li>
        <?php if ($deposit->reviewed_at): ?>
          <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Reviewed</span><span class="small"><?php echo fmt_date($deposit->reviewed_at); ?></span></li>
        <?php endif; ?>
        <?php if ($deposit->admin_note): ?>
          <li class="list-group-item"><div class="text-muted small mb-1">Admin note</div><?php echo html_escape($deposit->admin_note); ?></li>
        <?php endif; ?>
      </ul>
    </div>

    <?php if ($deposit->status === 'pending'): ?>
      <div class="card">
        <div class="card-header"><i class="bi bi-check2-square"></i> Decision</div>
        <div class="card-body">
          <div class="alert alert-warning small">
            <i class="bi bi-exclamation-triangle"></i>
            Confirm the transaction on the block explorer first. Approving credits
            <strong><?php echo money($deposit->amount); ?></strong>, activates the plan and pays any referral commission.
          </div>

          <?php echo form_open('admin/deposits/approve/'.$deposit->id, array('class' => 'mb-3')); ?>
            <label class="form-label small">Note (optional)</label>
            <input type="text" name="admin_note" class="form-control form-control-sm mb-2" maxlength="500">
            <button class="btn btn-success" data-confirm="Approve this deposit and activate the plan?">
              <i class="bi bi-check2"></i> Approve &amp; Activate
            </button>
          <?php echo form_close(); ?>

          <hr>

          <?php echo form_open('admin/deposits/reject/'.$deposit->id); ?>
            <label class="form-label small">Rejection reason</label>
            <input type="text" name="admin_note" class="form-control form-control-sm mb-2" maxlength="500" placeholder="e.g. transaction not found on chain">
            <button class="btn btn-outline-danger" data-confirm="Reject this deposit? No balance will be credited.">
              <i class="bi bi-x-lg"></i> Reject
            </button>
          <?php echo form_close(); ?>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <div class="col-lg-5">
    <div class="card">
      <div class="card-header"><i class="bi bi-image"></i> Payment Screenshot</div>
      <div class="card-body text-center">
        <?php if ($deposit->proof_image): ?>
          <a href="<?php echo upload_url('deposits', $deposit->proof_image); ?>" target="_blank" rel="noopener">
            <img src="<?php echo upload_url('deposits', $deposit->proof_image); ?>" class="img-fluid rounded" alt="Payment proof">
          </a>
          <div class="small text-muted mt-2">Click to open full size.</div>
        <?php else: ?>
          <div class="empty-state py-4"><i class="bi bi-image"></i>No screenshot was uploaded.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
