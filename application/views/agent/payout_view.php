<a href="<?php echo base_url('agent/payouts'); ?>" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> Cash-outs</a>

<div class="row justify-content-center">
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span>Cash-Out #<?php echo (int) $payout->id; ?></span>
        <?php echo badge($payout->status); ?>
      </div>
      <ul class="list-group list-group-flush">
        <li class="list-group-item d-flex justify-content-between"><span class="text-muted">From wallet</span><span class="badge text-bg-light border"><?php echo agent_wallet_label($payout->source); ?></span></li>
        <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Requested</span><strong><?php echo money($payout->amount); ?></strong></li>
        <?php if ((float) $payout->fee > 0): ?>
          <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Fee (<?php echo percent($payout->fee_percent); ?>)</span><span class="text-danger">-<?php echo money($payout->fee); ?></span></li>
        <?php endif; ?>
        <li class="list-group-item d-flex justify-content-between"><span class="text-muted">You receive</span><strong class="text-brand fs-5"><?php echo money($payout->net_amount); ?></strong></li>
        <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Network</span><span class="badge text-bg-light border"><?php echo html_escape($payout->network); ?></span></li>
        <li class="list-group-item">
          <div class="text-muted small mb-1">Sending to</div>
          <div class="mono small"><?php echo html_escape($payout->wallet_address); ?></div>
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
      <div class="card-footer small text-muted">
        <?php if ($payout->status === 'pending'): ?>
          <i class="bi bi-hourglass-split"></i> <?php echo money($payout->amount); ?> is held out of your <?php echo agent_wallet_label($payout->source); ?> wallet while this is reviewed.
        <?php elseif ($payout->status === 'approved'): ?>
          <i class="bi bi-check2"></i> Approved. Waiting for the admin to send it.
        <?php elseif ($payout->status === 'rejected'): ?>
          <i class="bi bi-arrow-counterclockwise"></i> Rejected &mdash; <?php echo money($payout->amount); ?> was returned to your wallet.
        <?php else: ?>
          <i class="bi bi-check2-circle"></i> Sent.
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
