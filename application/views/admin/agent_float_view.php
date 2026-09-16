<a href="<?php echo base_url('admin/agent-float'); ?>" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> All float orders</a>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span>Float Order #<?php echo (int) $order->id; ?></span>
        <?php echo badge($order->status); ?>
      </div>
      <ul class="list-group list-group-flush">
        <li class="list-group-item d-flex justify-content-between">
          <span class="text-muted">Agent</span>
          <a href="<?php echo base_url('admin/agents/wallets/'.$order->agent_id); ?>"><?php echo html_escape($order->agent_username); ?> &middot; <?php echo html_escape($order->agent_name); ?></a>
        </li>
        <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Email</span><span class="small"><?php echo html_escape($order->agent_email); ?></span></li>
        <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Amount</span><strong class="text-brand fs-5"><?php echo money($order->amount); ?></strong></li>
        <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Current float</span><strong><?php echo money($order->deposit_balance); ?></strong></li>
        <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Network</span><span class="badge text-bg-light border"><?php echo html_escape($order->network ?: '-'); ?></span></li>
        <li class="list-group-item">
          <div class="text-muted small mb-1">Company wallet used</div>
          <div class="mono small"><?php echo html_escape($order->wallet_address ?: '-'); ?></div>
        </li>
        <li class="list-group-item">
          <div class="text-muted small mb-1">Transaction hash (verify this on-chain)</div>
          <div class="copy-field">
            <input type="text" class="form-control form-control-sm" id="txidField" readonly value="<?php echo html_escape($order->txid); ?>">
            <button class="btn btn-sm btn-outline-secondary" type="button" data-copy-target="#txidField"><i class="bi bi-clipboard"></i></button>
          </div>
          <div class="mt-2 d-flex gap-2 flex-wrap">
            <?php if ($order->network === 'TRC20'): ?>
              <a class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener" href="https://tronscan.org/#/transaction/<?php echo urlencode($order->txid); ?>">Open in Tronscan</a>
            <?php elseif ($order->network === 'BEP20'): ?>
              <a class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener" href="https://bscscan.com/tx/<?php echo urlencode($order->txid); ?>">Open in BscScan</a>
            <?php elseif ($order->network === 'ERC20'): ?>
              <a class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener" href="https://etherscan.io/tx/<?php echo urlencode($order->txid); ?>">Open in Etherscan</a>
            <?php endif; ?>
          </div>
        </li>
        <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Submitted</span><span class="small"><?php echo fmt_date($order->created_at); ?></span></li>
        <?php if ($order->reviewed_at): ?>
          <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Reviewed</span><span class="small"><?php echo fmt_date($order->reviewed_at); ?></span></li>
        <?php endif; ?>
        <?php if ($order->admin_note): ?>
          <li class="list-group-item"><div class="text-muted small mb-1">Admin note</div><?php echo html_escape($order->admin_note); ?></li>
        <?php endif; ?>
      </ul>
    </div>

    <?php if ($order->status === 'pending' && admin_can_any(array('agent_float.approve', 'agent_float.reject'))): ?>
      <div class="card">
        <div class="card-header"><i class="bi bi-check2-square"></i> Decision</div>
        <div class="card-body">
          <div class="alert alert-warning small">
            <i class="bi bi-exclamation-triangle"></i>
            Confirm the transfer on the block explorer first. Approving adds
            <strong><?php echo money($order->amount); ?></strong> of spendable float to this agent's deposit wallet.
            Float is sold at par - no discount is applied here.
          </div>

          <?php if (admin_can('agent_float.approve')): ?>
          <?php echo form_open('admin/agent-float/approve/'.$order->id, array('class' => 'mb-3')); ?>
            <label class="form-label small">Note (optional)</label>
            <input type="text" name="admin_note" class="form-control form-control-sm mb-2" maxlength="500">
            <button class="btn btn-success" data-confirm="Approve this order and credit <?php echo money($order->amount); ?> of float?">
              <i class="bi bi-check2"></i> Approve &amp; Credit Float
            </button>
          <?php echo form_close(); ?>

          <hr>
          <?php endif; ?>

          <?php if (admin_can('agent_float.reject')): ?>
          <?php echo form_open('admin/agent-float/reject/'.$order->id); ?>
            <label class="form-label small">Rejection reason</label>
            <input type="text" name="admin_note" class="form-control form-control-sm mb-2" maxlength="500" placeholder="e.g. transaction not found on chain">
            <button class="btn btn-outline-danger" data-confirm="Reject this order? No float will be credited.">
              <i class="bi bi-x-lg"></i> Reject
            </button>
          <?php echo form_close(); ?>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <div class="col-lg-5">
    <div class="card">
      <div class="card-header"><i class="bi bi-image"></i> Payment Screenshot</div>
      <div class="card-body text-center">
        <?php if ($order->proof_image): ?>
          <a href="<?php echo upload_url('deposits', $order->proof_image); ?>" target="_blank" rel="noopener">
            <img src="<?php echo upload_url('deposits', $order->proof_image); ?>" class="img-fluid rounded" alt="Payment proof">
          </a>
          <div class="small text-muted mt-2">Click to open full size.</div>
        <?php else: ?>
          <div class="empty-state py-4"><i class="bi bi-image"></i>No screenshot was uploaded.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
