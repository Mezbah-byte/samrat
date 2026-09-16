<a href="<?php echo base_url('agent/float'); ?>" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> Float orders</a>

<div class="row justify-content-center">
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span>Float Order #<?php echo (int) $order->id; ?></span>
        <?php echo badge($order->status); ?>
      </div>
      <ul class="list-group list-group-flush">
        <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Amount</span><strong class="text-brand fs-5"><?php echo money($order->amount); ?></strong></li>
        <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Network</span><span class="badge text-bg-light border"><?php echo html_escape($order->network ?: '-'); ?></span></li>
        <li class="list-group-item">
          <div class="text-muted small mb-1">Sent to</div>
          <div class="mono small"><?php echo html_escape($method ? $method->wallet_address : '-'); ?></div>
        </li>
        <li class="list-group-item">
          <div class="text-muted small mb-1">Transaction hash</div>
          <div class="mono small"><?php echo html_escape($order->txid); ?></div>
        </li>
        <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Submitted</span><span class="small"><?php echo fmt_date($order->created_at); ?></span></li>
        <?php if ($order->reviewed_at): ?>
          <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Reviewed</span><span class="small"><?php echo fmt_date($order->reviewed_at); ?></span></li>
        <?php endif; ?>
        <?php if ($order->admin_note): ?>
          <li class="list-group-item"><div class="text-muted small mb-1">Admin note</div><?php echo html_escape($order->admin_note); ?></li>
        <?php endif; ?>
      </ul>
      <?php if ($order->status === 'pending'): ?>
        <div class="card-footer small text-muted">
          <i class="bi bi-hourglass-split"></i> Waiting for an admin to verify the transfer. Your float is unchanged until then.
        </div>
      <?php endif; ?>
    </div>

    <?php if ($order->proof_image): ?>
      <div class="card mt-3">
        <div class="card-header"><i class="bi bi-image"></i> Your Screenshot</div>
        <div class="card-body text-center">
          <a href="<?php echo upload_url('deposits', $order->proof_image); ?>" target="_blank" rel="noopener">
            <img src="<?php echo upload_url('deposits', $order->proof_image); ?>" class="img-fluid rounded" alt="Payment proof">
          </a>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>
