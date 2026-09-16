<?php
  $amount     = (float) $deposit->amount;
  $commission = round($amount * (float) $percent / 100, 2);
  $can_pay    = $balance >= $amount;
?>
<a href="<?php echo base_url('agent/requests/deposits'); ?>" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> Deposit requests</a>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span>Deposit Request #<?php echo (int) $deposit->id; ?></span>
        <?php if ($deposit->agent_status === 'pending'): ?><span class="badge text-bg-warning">Waiting on you</span>
        <?php elseif ($deposit->agent_status === 'accepted'): ?><span class="badge text-bg-success">Settled</span>
        <?php elseif ($deposit->agent_status === 'expired'): ?><span class="badge text-bg-secondary">Expired</span>
        <?php else: ?><span class="badge text-bg-danger">Declined</span><?php endif; ?>
      </div>
      <ul class="list-group list-group-flush">
        <li class="list-group-item d-flex justify-content-between"><span class="text-muted">User</span><span><?php echo html_escape($deposit->username); ?> &middot; <?php echo html_escape($deposit->full_name); ?></span></li>
        <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Package</span><strong><?php echo html_escape($deposit->package_name); ?></strong></li>
        <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Amount they sent you</span><strong class="text-brand fs-5"><?php echo money($amount); ?></strong></li>
        <li class="list-group-item">
          <div class="text-muted small mb-1">Your wallet they paid</div>
          <div class="small"><?php echo html_escape($deposit->agent_wallet_label ?: 'Wallet removed'); ?>
            <span class="badge text-bg-light border"><?php echo html_escape($deposit->agent_wallet_network ?: $deposit->network); ?></span></div>
          <div class="mono small text-muted"><?php echo html_escape($deposit->agent_wallet_address ?: '-'); ?></div>
        </li>
        <li class="list-group-item">
          <div class="text-muted small mb-1">Transaction hash &mdash; check this against your own wallet</div>
          <div class="copy-field">
            <input type="text" class="form-control form-control-sm mono" id="txidField" readonly value="<?php echo html_escape($deposit->txid); ?>">
            <button class="btn btn-sm btn-outline-secondary" type="button" data-copy-target="#txidField"><i class="bi bi-clipboard"></i></button>
          </div>
        </li>
        <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Submitted</span><span class="small"><?php echo fmt_date($deposit->created_at); ?></span></li>
        <?php if ($deposit->agent_accepted_at): ?>
          <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Settled at</span><span class="small"><?php echo fmt_date($deposit->agent_accepted_at); ?></span></li>
          <li class="list-group-item d-flex justify-content-between"><span class="text-muted">You earned</span><strong class="text-success"><?php echo money($deposit->agent_commission); ?></strong></li>
        <?php endif; ?>
        <?php if ($deposit->agent_note): ?>
          <li class="list-group-item"><div class="text-muted small mb-1">Your note</div><?php echo html_escape($deposit->agent_note); ?></li>
        <?php endif; ?>
      </ul>
    </div>

    <?php if ($deposit->proof_image): ?>
      <div class="card">
        <div class="card-header"><i class="bi bi-image"></i> User's Screenshot</div>
        <div class="card-body text-center">
          <a href="<?php echo upload_url('deposits', $deposit->proof_image); ?>" target="_blank" rel="noopener">
            <img src="<?php echo upload_url('deposits', $deposit->proof_image); ?>" class="img-fluid rounded" alt="Payment proof">
          </a>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <div class="col-lg-5">
    <?php if ($deposit->agent_status === 'pending' && $deposit->status === 'pending'): ?>
      <div class="card mb-3">
        <div class="card-header"><i class="bi bi-check2-square"></i> Settle This Deposit</div>
        <div class="card-body">
          <div class="alert alert-warning small">
            <i class="bi bi-exclamation-triangle"></i>
            Only accept once the money is actually in your wallet. Accepting spends
            <strong><?php echo money($amount); ?></strong> of your float and activates the user's plan
            immediately &mdash; there is no admin step after this.
          </div>

          <ul class="list-group list-group-flush small mb-3">
            <li class="list-group-item d-flex justify-content-between px-0"><span class="text-muted">Your float now</span><strong><?php echo money($balance); ?></strong></li>
            <li class="list-group-item d-flex justify-content-between px-0"><span class="text-muted">Float after</span><strong><?php echo money(max(0, $balance - $amount)); ?></strong></li>
            <li class="list-group-item d-flex justify-content-between px-0"><span class="text-muted">Your commission (<?php echo percent($percent); ?>)</span><strong class="text-success">+<?php echo money($commission); ?></strong></li>
          </ul>

          <?php if ($can_pay): ?>
            <?php echo form_open('agent/requests/accept/'.$deposit->id, array('class' => 'mb-3')); ?>
              <button class="btn btn-success w-100" data-confirm="Accept this deposit? <?php echo money($amount); ?> leaves your float and the user's plan activates now.">
                <i class="bi bi-check2"></i> I received it &mdash; Accept
              </button>
            <?php echo form_close(); ?>
          <?php else: ?>
            <div class="alert alert-danger small">
              Your float is <?php echo money($balance); ?>, below the <?php echo money($amount); ?> needed.
              <a href="<?php echo base_url('agent/float/create'); ?>" class="alert-link">Buy more float</a>.
            </div>
          <?php endif; ?>

          <hr>

          <?php echo form_open('agent/requests/decline/'.$deposit->id); ?>
            <label class="form-label small">Did not receive it? Explain and hand it to an admin</label>
            <input type="text" name="agent_note" class="form-control form-control-sm mb-2" maxlength="500" required placeholder="e.g. nothing arrived at this address">
            <button class="btn btn-outline-danger w-100" data-confirm="Hand this request to an admin? Your float is not touched.">
              <i class="bi bi-arrow-up-right"></i> Hand to Admin
            </button>
          <?php echo form_close(); ?>
        </div>
      </div>
    <?php else: ?>
      <div class="card"><div class="card-body">
        <div class="empty-state py-4">
          <i class="bi bi-check2-circle"></i>
          <?php if ($deposit->agent_status === 'accepted'): ?>
            You settled this one. Nothing left to do.
          <?php elseif ($deposit->agent_status === 'expired'): ?>
            This request timed out and went to an admin.
          <?php elseif ($deposit->agent_status === 'rejected'): ?>
            You handed this to an admin.
          <?php else: ?>
            This deposit is already <?php echo html_escape($deposit->status); ?>.
          <?php endif; ?>
        </div>
      </div></div>
    <?php endif; ?>
  </div>
</div>
