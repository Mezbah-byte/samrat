<div class="page-head reveal" data-reveal-order="0">
  <div>
    <h1>Deposit #<?php echo (int) $deposit->id; ?></h1>
    <p class="lede"><?php echo html_escape($deposit->username); ?> &middot; <?php echo money($deposit->amount); ?></p>
  </div>
  <div class="d-flex gap-2 align-items-center">
    <?php echo chip($deposit->status); ?>
    <a href="<?php echo base_url('agent/deposits'); ?>" class="btn btn-ghost"><i data-lucide="arrow-left"></i> Back</a>
  </div>
</div>

<div class="row g-3">
  <div class="col-xl-7">
    <div class="panel reveal" data-reveal-order="1">
      <div class="panel-head"><i data-lucide="inbox"></i> Submission</div>
      <div class="panel-body">
        <div class="tile mb-3">
          <div class="tile-row">
            <span class="text-muted">User</span>
            <span><strong><?php echo html_escape($deposit->username); ?></strong> <span class="text-dim">&middot; <?php echo html_escape($deposit->full_name); ?></span></span>
          </div>
          <div class="tile-row"><span class="text-muted">Package</span><strong><?php echo html_escape($deposit->package_name); ?></strong></div>
          <div class="tile-row"><span class="text-muted">Amount</span><strong class="num text-accent"><?php echo money($deposit->amount); ?></strong></div>
          <div class="tile-row"><span class="text-muted">Method</span><span><?php echo html_escape($deposit->method_name ?: '-'); ?></span></div>
          <div class="tile-row"><span class="text-muted">Network</span><span class="chip chip-mute"><?php echo html_escape($deposit->network ?: '-'); ?></span></div>
          <div class="tile-row"><span class="text-muted">Submitted</span><span class="text-muted"><?php echo fmt_date($deposit->created_at); ?></span></div>
        </div>

        <label class="form-label">Transaction hash</label>
        <div class="copy-field mb-3">
          <input type="text" class="form-control form-control-sm mono" id="txidField" readonly value="<?php echo html_escape($deposit->txid); ?>">
          <button class="btn btn-ghost" type="button" data-copy-target="#txidField" aria-label="Copy hash"><i data-lucide="copy"></i></button>
        </div>

        <?php if ($deposit->admin_note): ?>
          <label class="form-label">Admin note</label>
          <p class="small mb-0"><?php echo html_escape($deposit->admin_note); ?></p>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($deposit->proof_image): ?>
      <div class="panel mt-3 reveal" data-reveal-order="2">
        <div class="panel-head"><i data-lucide="image"></i> Payment Screenshot</div>
        <div class="panel-body text-center">
          <a href="<?php echo upload_url('deposits', $deposit->proof_image); ?>" target="_blank" rel="noopener">
            <img src="<?php echo upload_url('deposits', $deposit->proof_image); ?>" class="img-fluid rounded" alt="Payment proof">
          </a>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <div class="col-xl-5">
    <div class="panel reveal" data-reveal-order="2">
      <div class="panel-head"><i data-lucide="clipboard-check"></i> My Recommendation</div>
      <div class="panel-body">
        <?php if ($deposit->agent_recommendation): ?>
          <div class="tile mb-3">
            <div class="tile-row">
              <span class="text-muted">You recommended</span>
              <?php echo chip($deposit->agent_recommendation === 'approve' ? 'approved' : 'rejected'); ?>
            </div>
            <div class="tile-row"><span class="text-muted">On</span><span class="text-muted"><?php echo fmt_date($deposit->agent_reviewed_at); ?></span></div>
          </div>
          <?php if ($deposit->agent_note): ?>
            <p class="small text-muted mb-3"><i data-lucide="message-square"></i> <?php echo html_escape($deposit->agent_note); ?></p>
          <?php endif; ?>
        <?php endif; ?>

        <?php if ($deposit->status !== 'pending'): ?>
          <div class="empty-state" style="padding:1.5rem 1rem">
            <i data-lucide="lock"></i>
            <p class="mb-0">Already <?php echo html_escape($deposit->status); ?> by an admin. Nothing more to add.</p>
          </div>
        <?php else: ?>
          <p class="small text-muted">
            Advisory only. An admin makes the final decision and moves the money.
          </p>
          <?php echo form_open('agent/deposits/recommend/'.$deposit->id); ?>
            <div class="mb-3">
              <label class="form-label">Note <span class="text-muted">(required to reject)</span></label>
              <textarea name="agent_note" class="form-control" rows="3" maxlength="500"><?php echo set_value('agent_note', $deposit->agent_note); ?></textarea>
            </div>
            <button name="recommendation" value="approve" class="btn btn-grad w-100 mb-2">
              <i data-lucide="thumbs-up"></i> Recommend Approve
            </button>
            <button name="recommendation" value="reject" class="btn btn-quiet w-100"
                    data-confirm="Recommend rejecting this deposit?">
              <i data-lucide="thumbs-down"></i> Recommend Reject
            </button>
          <?php echo form_close(); ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
