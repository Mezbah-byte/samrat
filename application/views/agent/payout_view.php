<div class="page-head reveal" data-reveal-order="0">
  <div>
    <h1>Cash-Out #<?php echo (int) $payout->id; ?></h1>
    <p class="lede">
      <?php echo money($payout->net_amount); ?> from your
      <?php echo strtolower(agent_wallet_label($payout->source)); ?> wallet.
    </p>
  </div>
  <div class="d-flex gap-2 align-items-center">
    <?php echo chip($payout->status); ?>
    <a href="<?php echo base_url('agent/payouts'); ?>" class="btn btn-ghost"><i data-lucide="arrow-left"></i> Back</a>
  </div>
</div>

<div class="row g-3 justify-content-center">
  <div class="col-xl-7">
    <div class="panel reveal" data-reveal-order="1">
      <div class="panel-head"><i data-lucide="banknote"></i> Request Detail</div>
      <div class="panel-body">
        <div class="tile mb-3">
          <div class="tile-row">
            <span class="text-muted">From wallet</span>
            <span class="chip chip-mute"><?php echo agent_wallet_label($payout->source); ?></span>
          </div>
          <div class="tile-row"><span class="text-muted">Requested</span><span class="num"><?php echo money($payout->amount); ?></span></div>
          <?php if ((float) $payout->fee > 0): ?>
            <div class="tile-row">
              <span class="text-muted">Fee <span class="text-dim">(<?php echo percent($payout->fee_percent); ?>)</span></span>
              <span class="num text-bad">-<?php echo money($payout->fee); ?></span>
            </div>
          <?php endif; ?>
          <div class="tile-row">
            <span class="text-muted">You receive</span>
            <strong class="num text-accent fs-5"><?php echo money($payout->net_amount); ?></strong>
          </div>
          <div class="tile-row"><span class="text-muted">Requested at</span><span class="text-muted"><?php echo fmt_date($payout->created_at); ?></span></div>
          <?php if ($payout->processed_at): ?>
            <div class="tile-row"><span class="text-muted">Processed</span><span class="text-muted"><?php echo fmt_date($payout->processed_at); ?></span></div>
          <?php endif; ?>
        </div>

        <label class="form-label">
          Sending to
          <span class="chip chip-info ms-1"><?php echo html_escape($payout->network); ?></span>
        </label>
        <div class="mono small text-dim text-break mb-3"><?php echo html_escape($payout->wallet_address); ?></div>

        <?php if ($payout->txid): ?>
          <label class="form-label">Payout TXID</label>
          <div class="copy-field mb-3">
            <input type="text" class="form-control form-control-sm mono" id="txidField" readonly value="<?php echo html_escape($payout->txid); ?>">
            <button class="btn btn-ghost" type="button" data-copy-target="#txidField" aria-label="Copy hash"><i data-lucide="copy"></i></button>
          </div>
        <?php endif; ?>

        <?php if ($payout->admin_note): ?>
          <label class="form-label">Admin note</label>
          <p class="small mb-0"><?php echo html_escape($payout->admin_note); ?></p>
        <?php endif; ?>
      </div>
      <div class="panel-foot">
        <?php if ($payout->status === 'pending'): ?>
          <span class="text-warn"><i data-lucide="hourglass"></i>
            <?php echo money($payout->amount); ?> is held out of your
            <?php echo strtolower(agent_wallet_label($payout->source)); ?> wallet while this is reviewed.</span>
        <?php elseif ($payout->status === 'approved'): ?>
          <span class="text-ok"><i data-lucide="check"></i> Approved. Waiting for the admin to send it.</span>
        <?php elseif ($payout->status === 'rejected'): ?>
          <span class="text-bad"><i data-lucide="rotate-ccw"></i>
            Rejected &mdash; <?php echo money($payout->amount); ?> was returned to your wallet.</span>
        <?php else: ?>
          <span class="text-ok"><i data-lucide="check-check"></i> Sent.</span>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
