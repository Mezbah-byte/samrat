<?php
  $amount     = (float) $deposit->amount;
  $commission = round($amount * (float) $percent / 100, MONEY_DISPLAY);
  $can_pay    = $balance >= $amount;
  $open       = ($deposit->agent_status === 'pending' && $deposit->status === 'pending');
?>
<div class="page-head reveal" data-reveal-order="0">
  <div>
    <h1>Deposit Request #<?php echo (int) $deposit->id; ?></h1>
    <p class="lede">
      <?php echo html_escape($deposit->username); ?> says they sent
      <strong class="text-accent"><?php echo money($amount); ?></strong> to your wallet.
    </p>
  </div>
  <div class="d-flex gap-2 align-items-center">
    <?php echo agent_request_chip($deposit->agent_status, 'deposit'); ?>
    <a href="<?php echo base_url('agent/requests/deposits'); ?>" class="btn btn-ghost"><i data-lucide="arrow-left"></i> Back</a>
  </div>
</div>

<div class="row g-3">
  <div class="col-xl-7">
    <div class="panel mb-3 reveal" data-reveal-order="1">
      <div class="panel-head"><i data-lucide="file-text"></i> What the user submitted</div>
      <div class="panel-body">
        <div class="tile mb-3">
          <div class="tile-row">
            <span class="text-muted">User</span>
            <span><strong><?php echo html_escape($deposit->username); ?></strong> <span class="text-dim">&middot; <?php echo html_escape($deposit->full_name); ?></span></span>
          </div>
          <div class="tile-row"><span class="text-muted">Package</span><strong><?php echo html_escape($deposit->package_name); ?></strong></div>
          <div class="tile-row"><span class="text-muted">Amount</span><strong class="num text-accent fs-5"><?php echo money($amount); ?></strong></div>
          <div class="tile-row"><span class="text-muted">Submitted</span><span class="text-muted"><?php echo fmt_date($deposit->created_at); ?></span></div>
        </div>

        <label class="form-label">Your wallet they paid</label>
        <div class="tile mb-3">
          <div class="tile-row">
            <span><?php echo html_escape($deposit->agent_wallet_label ?: 'Wallet removed'); ?></span>
            <span class="chip chip-info"><?php echo html_escape($deposit->agent_wallet_network ?: $deposit->network); ?></span>
          </div>
          <div class="tile-row">
            <span class="mono small text-dim text-break"><?php echo html_escape($deposit->agent_wallet_address ?: '-'); ?></span>
          </div>
        </div>

        <label class="form-label">Transaction hash</label>
        <div class="copy-field mb-2">
          <input type="text" class="form-control form-control-sm mono" id="txidField" readonly value="<?php echo html_escape($deposit->txid); ?>">
          <button class="btn btn-ghost" type="button" data-copy-target="#txidField" aria-label="Copy hash"><i data-lucide="copy"></i></button>
        </div>
        <p class="small text-muted mb-0">
          <i data-lucide="shield-alert"></i>
          Check this against your own wallet history. A hash alone proves nothing.
        </p>

        <?php if ($deposit->agent_accepted_at): ?>
          <div class="stat-foot"><span>Settled at</span><span><?php echo fmt_date($deposit->agent_accepted_at); ?></span></div>
          <div class="stat-foot" style="margin-top:.4rem;padding-top:.4rem">
            <span>You earned</span><strong class="num text-ok"><?php echo money($deposit->agent_commission); ?></strong>
          </div>
        <?php endif; ?>

        <?php if ($deposit->agent_note): ?>
          <div class="stat-foot"><span>Your note</span><span><?php echo html_escape($deposit->agent_note); ?></span></div>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($deposit->proof_image): ?>
      <div class="panel reveal" data-reveal-order="2">
        <div class="panel-head"><i data-lucide="image"></i> User's Screenshot</div>
        <div class="panel-body text-center">
          <a href="<?php echo upload_url('deposits', $deposit->proof_image); ?>" target="_blank" rel="noopener">
            <img src="<?php echo upload_url('deposits', $deposit->proof_image); ?>" class="img-fluid rounded" alt="Payment proof">
          </a>
          <p class="small text-muted mt-2 mb-0">Click to open full size.</p>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <div class="col-xl-5">
    <?php if ($open): ?>
      <div class="panel mb-3 reveal" data-reveal-order="2">
        <div class="panel-head"><i data-lucide="calculator"></i> What Accepting Costs You</div>
        <div class="panel-body">
          <div class="tile mb-3">
            <div class="tile-row"><span class="text-muted">Float now</span><strong class="num"><?php echo money($balance); ?></strong></div>
            <div class="tile-row"><span class="text-muted">This deposit</span><strong class="num text-bad">-<?php echo money($amount); ?></strong></div>
            <div class="tile-row"><span class="text-muted">Float after</span><strong class="num"><?php echo money(max(0, $balance - $amount)); ?></strong></div>
            <div class="tile-row">
              <span class="text-muted">Commission <span class="text-dim">(<?php echo percent($percent); ?>)</span></span>
              <strong class="num text-ok">+<?php echo money($commission); ?></strong>
            </div>
          </div>

          <?php if ($can_pay): ?>
            <div class="d-flex gap-2 align-items-start mb-3">
              <span class="icon-tile sm grad-warning"><i data-lucide="alert-triangle"></i></span>
              <p class="small text-muted mb-0">
                Accept only once the money is actually in your wallet. This activates the user's
                plan immediately &mdash; there is no admin step afterwards, and no undo here.
              </p>
            </div>
            <?php echo form_open('agent/requests/accept/'.$deposit->id); ?>
              <button class="btn btn-grad w-100"
                      data-confirm="Accept this deposit? <?php echo money($amount); ?> leaves your float and the user's plan activates now.">
                <i data-lucide="check-check"></i> I received it &mdash; Accept
              </button>
            <?php echo form_close(); ?>
          <?php else: ?>
            <div class="empty-state" style="padding:1.5rem 1rem">
              <i data-lucide="battery-low"></i>
              <p class="mb-2">Float is <?php echo money($balance); ?>, short of the <?php echo money($amount); ?> needed.</p>
              <a href="<?php echo base_url('agent/float/create'); ?>" class="btn btn-grad"><i data-lucide="coins"></i> Buy Float</a>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="panel reveal" data-reveal-order="3">
        <div class="panel-head"><i data-lucide="corner-up-right"></i> Did Not Receive It?</div>
        <div class="panel-body">
          <p class="small text-muted">
            Hand it to an admin. Your float is not touched, and the user is told support is
            looking at it.
          </p>
          <?php echo form_open('agent/requests/decline/'.$deposit->id); ?>
            <div class="mb-3">
              <label class="form-label">What happened <span class="text-bad">*</span></label>
              <input type="text" name="agent_note" class="form-control" maxlength="500" required
                     placeholder="e.g. nothing arrived at this address">
            </div>
            <button class="btn btn-quiet w-100" data-confirm="Hand this request to an admin? Your float is not touched.">
              <i data-lucide="corner-up-right"></i> Hand to Admin
            </button>
          <?php echo form_close(); ?>
        </div>
      </div>
    <?php else: ?>
      <div class="panel reveal" data-reveal-order="2">
        <div class="panel-body">
          <div class="empty-state">
            <?php if ($deposit->agent_status === 'accepted'): ?>
              <i data-lucide="check-circle"></i>
              <p class="mb-1 fw-semibold">You settled this one.</p>
              <p class="small text-muted mb-0">
                <?php echo money($amount); ?> left your float and you earned
                <?php echo money($deposit->agent_commission); ?>.
              </p>
            <?php elseif ($deposit->agent_status === 'expired'): ?>
              <i data-lucide="clock-alert"></i>
              <p class="mb-1 fw-semibold">Timed out.</p>
              <p class="small text-muted mb-0">An admin is handling it. Your float was not touched.</p>
            <?php elseif ($deposit->agent_status === 'rejected'): ?>
              <i data-lucide="corner-up-right"></i>
              <p class="mb-1 fw-semibold">Handed to an admin.</p>
              <p class="small text-muted mb-0">Your float was not touched.</p>
            <?php else: ?>
              <i data-lucide="lock"></i>
              <p class="mb-0">This deposit is already <?php echo html_escape($deposit->status); ?>.</p>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>
