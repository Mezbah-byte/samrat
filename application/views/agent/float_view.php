<div class="page-head reveal" data-reveal-order="0">
  <div>
    <h1>Float Order #<?php echo (int) $order->id; ?></h1>
    <p class="lede"><?php echo money($order->amount); ?> sent on <?php echo fmt_date($order->created_at, 'd M Y'); ?>.</p>
  </div>
  <div class="d-flex gap-2 align-items-center">
    <?php echo chip($order->status); ?>
    <a href="<?php echo base_url('agent/float'); ?>" class="btn btn-ghost"><i data-lucide="arrow-left"></i> Back</a>
  </div>
</div>

<div class="row g-3 justify-content-center">
  <div class="col-xl-7">
    <div class="panel reveal" data-reveal-order="1">
      <div class="panel-head"><i data-lucide="coins"></i> Order Detail</div>
      <div class="panel-body">
        <div class="tile mb-3">
          <div class="tile-row">
            <span class="text-muted">Amount</span>
            <strong class="num text-accent fs-5"><?php echo money($order->amount); ?></strong>
          </div>
          <div class="tile-row">
            <span class="text-muted">Network</span>
            <span class="chip chip-mute"><?php echo html_escape($order->network ?: '-'); ?></span>
          </div>
          <div class="tile-row"><span class="text-muted">Submitted</span><span class="text-muted"><?php echo fmt_date($order->created_at); ?></span></div>
          <?php if ($order->reviewed_at): ?>
            <div class="tile-row"><span class="text-muted">Reviewed</span><span class="text-muted"><?php echo fmt_date($order->reviewed_at); ?></span></div>
          <?php endif; ?>
        </div>

        <label class="form-label">Sent to</label>
        <div class="mono small text-dim text-break mb-3"><?php echo html_escape($method ? $method->wallet_address : '-'); ?></div>

        <label class="form-label">Transaction hash</label>
        <div class="copy-field mb-3">
          <input type="text" class="form-control form-control-sm mono" id="txidField" readonly value="<?php echo html_escape($order->txid); ?>">
          <button class="btn btn-ghost" type="button" data-copy-target="#txidField" aria-label="Copy hash"><i data-lucide="copy"></i></button>
        </div>

        <?php if ($order->admin_note): ?>
          <label class="form-label">Admin note</label>
          <p class="small mb-0"><?php echo html_escape($order->admin_note); ?></p>
        <?php endif; ?>
      </div>
      <div class="panel-foot">
        <?php if ($order->status === 'pending'): ?>
          <span class="text-warn"><i data-lucide="hourglass"></i> Waiting for an admin to verify. Your float is unchanged until then.</span>
        <?php elseif ($order->status === 'approved'): ?>
          <span class="text-ok"><i data-lucide="check-check"></i> <?php echo money($order->amount); ?> was added to your deposit float.</span>
        <?php else: ?>
          <span class="text-bad"><i data-lucide="x"></i> Rejected &mdash; no float was credited.</span>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($order->proof_image): ?>
      <div class="panel mt-3 reveal" data-reveal-order="2">
        <div class="panel-head"><i data-lucide="image"></i> Your Screenshot</div>
        <div class="panel-body text-center">
          <a href="<?php echo upload_url('deposits', $order->proof_image); ?>" target="_blank" rel="noopener">
            <img src="<?php echo upload_url('deposits', $order->proof_image); ?>" class="img-fluid rounded" alt="Payment proof">
          </a>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>
