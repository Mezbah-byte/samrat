<?php
  $commission = round((float) $withdrawal->amount * (float) $percent / 100, 2);
?>
<a href="<?php echo base_url('agent/requests/withdrawals'); ?>" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> Withdraw requests</a>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span>Withdraw Request #<?php echo (int) $withdrawal->id; ?></span>
        <?php if ($withdrawal->agent_status === 'pending'): ?><span class="badge text-bg-warning">Waiting on you</span>
        <?php elseif ($withdrawal->agent_status === 'accepted'): ?><span class="badge text-bg-success">Paid</span>
        <?php elseif ($withdrawal->agent_status === 'expired'): ?><span class="badge text-bg-secondary">Expired</span>
        <?php else: ?><span class="badge text-bg-danger">Declined</span><?php endif; ?>
      </div>
      <ul class="list-group list-group-flush">
        <li class="list-group-item d-flex justify-content-between"><span class="text-muted">User</span><span><?php echo html_escape($withdrawal->username); ?> &middot; <?php echo html_escape($withdrawal->full_name); ?></span></li>
        <li class="list-group-item d-flex justify-content-between"><span class="text-muted">They requested</span><span><?php echo money($withdrawal->amount); ?></span></li>
        <?php if ((float) $withdrawal->fee > 0): ?>
          <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Platform fee (<?php echo percent($withdrawal->fee_percent); ?>)</span><span class="text-muted">-<?php echo money($withdrawal->fee); ?></span></li>
        <?php endif; ?>
        <li class="list-group-item d-flex justify-content-between"><span class="text-muted">You must send</span><strong class="text-brand fs-5"><?php echo money($withdrawal->net_amount); ?></strong></li>
        <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Network</span><span class="badge text-bg-light border"><?php echo html_escape($withdrawal->network); ?></span></li>
        <li class="list-group-item">
          <div class="text-muted small mb-1">Send to this address</div>
          <div class="copy-field">
            <input type="text" class="form-control form-control-sm mono" id="addrField" readonly value="<?php echo html_escape($withdrawal->wallet_address); ?>">
            <button class="btn btn-sm btn-outline-secondary" type="button" data-copy-target="#addrField"><i class="bi bi-clipboard"></i></button>
          </div>
        </li>
        <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Requested at</span><span class="small"><?php echo fmt_date($withdrawal->created_at); ?></span></li>
        <?php if ($withdrawal->agent_paid_at): ?>
          <li class="list-group-item d-flex justify-content-between"><span class="text-muted">You paid at</span><span class="small"><?php echo fmt_date($withdrawal->agent_paid_at); ?></span></li>
          <li class="list-group-item"><div class="text-muted small mb-1">Your TXID</div><div class="mono small"><?php echo html_escape($withdrawal->agent_txid); ?></div></li>
          <li class="list-group-item d-flex justify-content-between"><span class="text-muted">You earned</span><strong class="text-success"><?php echo money($withdrawal->agent_commission); ?></strong></li>
        <?php endif; ?>
        <?php if ($withdrawal->agent_note): ?>
          <li class="list-group-item"><div class="text-muted small mb-1">Your note</div><?php echo html_escape($withdrawal->agent_note); ?></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>

  <div class="col-lg-5">
    <?php if ($withdrawal->agent_status === 'pending' && $withdrawal->status === 'pending'): ?>
      <div class="card">
        <div class="card-header"><i class="bi bi-send"></i> Confirm You Paid</div>
        <div class="card-body">
          <div class="alert alert-warning small">
            <i class="bi bi-exclamation-triangle"></i>
            Send <strong><?php echo money($withdrawal->net_amount); ?></strong> to the address on the left
            <em>first</em>, then confirm here. Confirming credits your withdraw wallet and closes the
            request &mdash; it cannot be undone from this panel.
          </div>

          <ul class="list-group list-group-flush small mb-3">
            <li class="list-group-item d-flex justify-content-between px-0"><span class="text-muted">Collected into withdraw wallet</span><strong>+<?php echo money($withdrawal->net_amount); ?></strong></li>
            <li class="list-group-item d-flex justify-content-between px-0"><span class="text-muted">Your commission (<?php echo percent($percent); ?>)</span><strong class="text-success">+<?php echo money($commission); ?></strong></li>
          </ul>

          <?php echo form_open('agent/requests/pay/'.$withdrawal->id, array('class' => 'mb-3')); ?>
            <label class="form-label small">Hash of the transfer you sent</label>
            <input type="text" name="txid" class="form-control form-control-sm mono mb-2" maxlength="191" required>
            <button class="btn btn-success w-100" data-confirm="Confirm you already sent <?php echo money($withdrawal->net_amount); ?> to the user?">
              <i class="bi bi-check2"></i> I sent it &mdash; Confirm
            </button>
          <?php echo form_close(); ?>

          <hr>

          <?php echo form_open('agent/requests/decline_withdrawal/'.$withdrawal->id); ?>
            <label class="form-label small">Cannot pay this one? Hand it to an admin</label>
            <input type="text" name="agent_note" class="form-control form-control-sm mb-2" maxlength="500" required placeholder="e.g. out of funds today">
            <button class="btn btn-outline-danger w-100" data-confirm="Hand this request to an admin?">
              <i class="bi bi-arrow-up-right"></i> Hand to Admin
            </button>
          <?php echo form_close(); ?>
        </div>
      </div>
    <?php else: ?>
      <div class="card"><div class="card-body">
        <div class="empty-state py-4">
          <i class="bi bi-check2-circle"></i>
          <?php if ($withdrawal->agent_status === 'accepted'): ?>
            You paid this one. Nothing left to do.
          <?php elseif ($withdrawal->agent_status === 'expired'): ?>
            This request timed out and went to an admin.
          <?php elseif ($withdrawal->agent_status === 'rejected'): ?>
            You handed this to an admin.
          <?php else: ?>
            This request is already <?php echo html_escape($withdrawal->status); ?>.
          <?php endif; ?>
        </div>
      </div></div>
    <?php endif; ?>
  </div>
</div>
