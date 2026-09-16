<?php
  $commission = round((float) $withdrawal->amount * (float) $percent / 100, MONEY_DISPLAY);
  $open       = ($withdrawal->agent_status === 'pending' && $withdrawal->status === 'pending');
?>
<div class="page-head reveal" data-reveal-order="0">
  <div>
    <h1>Withdraw Request #<?php echo (int) $withdrawal->id; ?></h1>
    <p class="lede">
      Send <strong class="text-accent"><?php echo money($withdrawal->net_amount); ?></strong>
      to <?php echo html_escape($withdrawal->username); ?>, then confirm it here.
    </p>
  </div>
  <div class="d-flex gap-2 align-items-center">
    <?php echo agent_request_chip($withdrawal->agent_status, 'withdrawal'); ?>
    <a href="<?php echo base_url('agent/requests/withdrawals'); ?>" class="btn btn-ghost"><i data-lucide="arrow-left"></i> Back</a>
  </div>
</div>

<div class="row g-3">
  <div class="col-xl-7">
    <div class="panel reveal" data-reveal-order="1">
      <div class="panel-head"><i data-lucide="send"></i> Payment Details</div>
      <div class="panel-body">
        <div class="tile mb-3">
          <div class="tile-row">
            <span class="text-muted">User</span>
            <span><strong><?php echo html_escape($withdrawal->username); ?></strong> <span class="text-dim">&middot; <?php echo html_escape($withdrawal->full_name); ?></span></span>
          </div>
          <div class="tile-row"><span class="text-muted">They requested</span><span class="num"><?php echo money($withdrawal->amount); ?></span></div>
          <?php if ((float) $withdrawal->fee > 0): ?>
            <div class="tile-row">
              <span class="text-muted">Platform fee <span class="text-dim">(<?php echo percent($withdrawal->fee_percent); ?>)</span></span>
              <span class="num text-dim">-<?php echo money($withdrawal->fee); ?></span>
            </div>
          <?php endif; ?>
          <div class="tile-row">
            <span class="text-muted">You must send</span>
            <strong class="num text-accent fs-5"><?php echo money($withdrawal->net_amount); ?></strong>
          </div>
          <div class="tile-row"><span class="text-muted">Requested at</span><span class="text-muted"><?php echo fmt_date($withdrawal->created_at); ?></span></div>
        </div>

        <label class="form-label">
          Send to this address
          <span class="chip chip-info ms-1"><?php echo html_escape($withdrawal->network); ?></span>
        </label>
        <div class="copy-field mb-2">
          <input type="text" class="form-control mono" id="addrField" readonly value="<?php echo html_escape($withdrawal->wallet_address); ?>">
          <button class="btn btn-ghost" type="button" data-copy-target="#addrField" aria-label="Copy address"><i data-lucide="copy"></i></button>
        </div>
        <p class="small text-bad mb-0">
          <i data-lucide="shield-alert"></i>
          Copy it, never retype it. Funds sent to a wrong address cannot be recovered and you still owe the user.
        </p>

        <?php if ($withdrawal->agent_paid_at): ?>
          <div class="stat-foot"><span>You paid at</span><span><?php echo fmt_date($withdrawal->agent_paid_at); ?></span></div>
          <div class="stat-foot" style="margin-top:.4rem;padding-top:.4rem">
            <span>You earned</span><strong class="num text-ok"><?php echo money($withdrawal->agent_commission); ?></strong>
          </div>
          <label class="form-label mt-3">Your TXID</label>
          <div class="mono small text-dim text-break"><?php echo html_escape($withdrawal->agent_txid); ?></div>
        <?php endif; ?>

        <?php if ($withdrawal->agent_note): ?>
          <div class="stat-foot"><span>Your note</span><span><?php echo html_escape($withdrawal->agent_note); ?></span></div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-xl-5">
    <?php if ($open): ?>
      <div class="panel mb-3 reveal" data-reveal-order="2">
        <div class="panel-head"><i data-lucide="check-check"></i> Confirm You Paid</div>
        <div class="panel-body">
          <div class="tile mb-3">
            <div class="tile-row">
              <span class="text-muted">Collected into your wallet</span>
              <strong class="num text-ok">+<?php echo money($withdrawal->net_amount); ?></strong>
            </div>
            <div class="tile-row">
              <span class="text-muted">Commission <span class="text-dim">(<?php echo percent($percent); ?>)</span></span>
              <strong class="num text-ok">+<?php echo money($commission); ?></strong>
            </div>
          </div>

          <div class="d-flex gap-2 align-items-start mb-3">
            <span class="icon-tile sm grad-warning"><i data-lucide="alert-triangle"></i></span>
            <p class="small text-muted mb-0">
              Send the money <strong>first</strong>, then confirm. Confirming closes the request
              and cannot be undone from this panel.
            </p>
          </div>

          <?php echo form_open('agent/requests/pay/'.$withdrawal->id); ?>
            <div class="mb-3">
              <label class="form-label">Hash of the transfer you sent <span class="text-bad">*</span></label>
              <input type="text" name="txid" class="form-control mono" maxlength="191" required
                     placeholder="Paste from your wallet">
            </div>
            <button class="btn btn-grad w-100"
                    data-confirm="Confirm you already sent <?php echo money($withdrawal->net_amount); ?> to the user?">
              <i data-lucide="check-check"></i> I sent it &mdash; Confirm
            </button>
          <?php echo form_close(); ?>
        </div>
      </div>

      <div class="panel reveal" data-reveal-order="3">
        <div class="panel-head"><i data-lucide="corner-up-right"></i> Cannot Pay This One?</div>
        <div class="panel-body">
          <p class="small text-muted">
            Hand it to an admin. The user's balance stays held and support takes it from there.
          </p>
          <?php echo form_open('agent/requests/decline_withdrawal/'.$withdrawal->id); ?>
            <div class="mb-3">
              <label class="form-label">Reason <span class="text-bad">*</span></label>
              <input type="text" name="agent_note" class="form-control" maxlength="500" required
                     placeholder="e.g. out of funds today">
            </div>
            <button class="btn btn-quiet w-100" data-confirm="Hand this request to an admin?">
              <i data-lucide="corner-up-right"></i> Hand to Admin
            </button>
          <?php echo form_close(); ?>
        </div>
      </div>
    <?php else: ?>
      <div class="panel reveal" data-reveal-order="2">
        <div class="panel-body">
          <div class="empty-state">
            <?php if ($withdrawal->agent_status === 'accepted'): ?>
              <i data-lucide="check-circle"></i>
              <p class="mb-1 fw-semibold">You paid this one.</p>
              <p class="small text-muted mb-0">
                <?php echo money($withdrawal->net_amount); ?> collected, plus
                <?php echo money($withdrawal->agent_commission); ?> commission.
              </p>
            <?php elseif ($withdrawal->agent_status === 'expired'): ?>
              <i data-lucide="clock-alert"></i>
              <p class="mb-1 fw-semibold">Timed out.</p>
              <p class="small text-muted mb-0">An admin is paying it from the platform.</p>
            <?php elseif ($withdrawal->agent_status === 'rejected'): ?>
              <i data-lucide="corner-up-right"></i>
              <p class="mb-1 fw-semibold">Handed to an admin.</p>
              <p class="small text-muted mb-0">Nothing was collected.</p>
            <?php else: ?>
              <i data-lucide="lock"></i>
              <p class="mb-0">This request is already <?php echo html_escape($withdrawal->status); ?>.</p>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>
