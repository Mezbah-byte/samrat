<div class="page-head reveal" data-reveal-order="0">
  <div>
    <h1>Withdrawal #<?php echo (int) $withdrawal->id; ?></h1>
    <p class="lede"><?php echo html_escape($withdrawal->username); ?> &middot; net <?php echo money($withdrawal->net_amount); ?></p>
  </div>
  <div class="d-flex gap-2 align-items-center">
    <?php echo chip($withdrawal->status); ?>
    <a href="<?php echo base_url('agent/withdrawals'); ?>" class="btn btn-ghost"><i data-lucide="arrow-left"></i> Back</a>
  </div>
</div>

<div class="row g-3">
  <div class="col-xl-7">
    <div class="panel reveal" data-reveal-order="1">
      <div class="panel-head"><i data-lucide="hand-coins"></i> Request</div>
      <div class="panel-body">
        <div class="tile mb-3">
          <div class="tile-row">
            <span class="text-muted">User</span>
            <span><strong><?php echo html_escape($withdrawal->username); ?></strong> <span class="text-dim">&middot; <?php echo html_escape($withdrawal->full_name); ?></span></span>
          </div>
          <div class="tile-row"><span class="text-muted">Balance now</span><span class="num"><?php echo money($withdrawal->balance); ?></span></div>
        </div>

        <div class="tile mb-3">
          <div class="tile-row"><span class="text-muted">Requested</span><span class="num"><?php echo money($withdrawal->amount); ?></span></div>
          <div class="tile-row">
            <span class="text-muted">Fee <span class="text-dim">(<?php echo percent($withdrawal->fee_percent); ?>)</span></span>
            <span class="num text-dim">-<?php echo money($withdrawal->fee); ?></span>
          </div>
          <div class="tile-row">
            <span class="text-muted">Net payout</span>
            <strong class="num text-accent fs-5"><?php echo money($withdrawal->net_amount); ?></strong>
          </div>
        </div>

        <label class="form-label">
          Payout address
          <span class="chip chip-info ms-1"><?php echo html_escape($withdrawal->network); ?></span>
        </label>
        <div class="mono small text-dim text-break mb-3"><?php echo html_escape($withdrawal->wallet_address); ?></div>

        <div class="tile mb-3">
          <div class="tile-row"><span class="text-muted">Requested at</span><span class="text-muted"><?php echo fmt_date($withdrawal->created_at); ?></span></div>
          <?php if ($withdrawal->txid): ?>
            <div class="tile-row"><span class="text-muted">Payout TXID</span><span class="mono small text-break"><?php echo html_escape($withdrawal->txid); ?></span></div>
          <?php endif; ?>
        </div>

        <?php if ($withdrawal->admin_note): ?>
          <label class="form-label">Admin note</label>
          <p class="small mb-0"><?php echo html_escape($withdrawal->admin_note); ?></p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-xl-5">
    <div class="panel reveal" data-reveal-order="2">
      <div class="panel-head"><i data-lucide="clipboard-check"></i> My Recommendation</div>
      <div class="panel-body">
        <?php if ($withdrawal->agent_recommendation): ?>
          <div class="tile mb-3">
            <div class="tile-row">
              <span class="text-muted">You recommended</span>
              <?php echo chip($withdrawal->agent_recommendation === 'approve' ? 'approved' : 'rejected'); ?>
            </div>
            <div class="tile-row"><span class="text-muted">On</span><span class="text-muted"><?php echo fmt_date($withdrawal->agent_reviewed_at); ?></span></div>
          </div>
          <?php if ($withdrawal->agent_note): ?>
            <p class="small text-muted mb-3"><i data-lucide="message-square"></i> <?php echo html_escape($withdrawal->agent_note); ?></p>
          <?php endif; ?>
        <?php endif; ?>

        <?php if ($withdrawal->status !== 'pending'): ?>
          <div class="empty-state" style="padding:1.5rem 1rem">
            <i data-lucide="lock"></i>
            <p class="mb-0">Already <?php echo html_escape($withdrawal->status); ?>. Nothing more to add.</p>
          </div>
        <?php else: ?>
          <p class="small text-muted">
            Advisory only. An admin approves the payout and sends the funds.
          </p>
          <?php echo form_open('agent/withdrawals/recommend/'.$withdrawal->id); ?>
            <div class="mb-3">
              <label class="form-label">Note <span class="text-muted">(required to reject)</span></label>
              <textarea name="agent_note" class="form-control" rows="3" maxlength="500"><?php echo set_value('agent_note', $withdrawal->agent_note); ?></textarea>
            </div>
            <button name="recommendation" value="approve" class="btn btn-grad w-100 mb-2">
              <i data-lucide="thumbs-up"></i> Recommend Approve
            </button>
            <button name="recommendation" value="reject" class="btn btn-quiet w-100"
                    data-confirm="Recommend rejecting this withdrawal?">
              <i data-lucide="thumbs-down"></i> Recommend Reject
            </button>
          <?php echo form_close(); ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
