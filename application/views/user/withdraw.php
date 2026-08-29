<div class="page-head reveal" data-reveal-order="0">
  <div>
    <h1>Withdraw</h1>
    <p class="lede">Available balance <strong class="text-ok"><?php echo money($user->balance); ?></strong>.</p>
  </div>
  <a href="<?php echo base_url('withdraw/history'); ?>" class="btn btn-ghost"><i data-lucide="history"></i> All requests</a>
</div>

<div class="row g-3">
  <div class="col-xl-7">
    <div class="panel reveal" data-reveal-order="1">
      <div class="panel-head"><i data-lucide="banknote"></i> Request Withdrawal</div>
      <div class="panel-body">
        <?php echo validation_errors('<div class="alert alert-danger py-2 small">', '</div>'); ?>

        <?php if ( ! $enabled): ?>
          <div class="alert alert-warning mb-0"><i data-lucide="pause"></i> Withdrawals are temporarily disabled by the administrator.</div>
        <?php elseif ($floor <= 0): ?>
          <div class="empty-state">
            <i data-lucide="package-open"></i>
            <p class="mb-3">You need an active package before you can withdraw.</p>
            <a href="<?php echo base_url('packages'); ?>" class="btn btn-grad"><i data-lucide="box"></i> Browse Packages</a>
          </div>
        <?php else: ?>
          <?php echo form_open('withdraw'); ?>
            <div class="mb-3">
              <label class="form-label">Amount <span class="text-bad">*</span></label>
              <div class="input-group">
                <span class="input-group-text"><?php echo html_escape(currency()); ?></span>
                <input type="number" step="0.01" min="<?php echo $floor; ?>" name="amount" id="withdrawAmount"
                       class="form-control" value="<?php echo set_value('amount'); ?>"
                       data-fee="<?php echo $fee_percent; ?>" data-symbol="<?php echo html_escape(currency()); ?>" required>
              </div>
              <div class="form-text">
                Minimum for your package: <strong><?php echo money($floor); ?></strong>.
                Available balance: <strong><?php echo money($user->balance); ?></strong>.
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label">Network <span class="text-bad">*</span></label>
              <select name="network" class="form-select" required>
                <?php foreach ($networks as $key => $label): ?>
                  <option value="<?php echo html_escape($key); ?>" <?php echo set_select('network', $key); ?>><?php echo html_escape($label); ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label">Your Wallet Address <span class="text-bad">*</span></label>
              <input type="text" name="wallet_address" class="form-control mono" value="<?php echo set_value('wallet_address'); ?>" required minlength="20" maxlength="191">
              <div class="form-text text-bad">
                Double-check this. Crypto sent to a wrong address cannot be recovered.
              </div>
            </div>

            <div class="tile mb-3">
              <div class="tile-row"><span class="text-muted">Fee (<?php echo $fee_percent; ?>%)</span><strong class="num" id="withdrawFee"><?php echo money(0); ?></strong></div>
              <div class="tile-row"><span class="text-muted">You receive</span><strong class="num text-ok fs-5" id="withdrawNet"><?php echo money(0); ?></strong></div>
            </div>

            <button class="btn btn-grad" data-confirm="Submit this withdrawal request? The amount will be held from your balance immediately.">
              <i data-lucide="send"></i> Request Withdrawal
            </button>
          <?php echo form_close(); ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-xl-5">
    <div class="panel mb-3 reveal" data-reveal-order="2">
      <div class="panel-head"><i data-lucide="info"></i> How it works</div>
      <div class="panel-body small text-muted">
        <ol class="ps-3 mb-0 d-grid gap-2">
          <li>The requested amount is held from your balance right away.</li>
          <li>An admin reviews the request and sends the payout on-chain.</li>
          <li>Once paid, the transaction hash appears in your history.</li>
          <li>If a request is rejected, the full amount is returned to your balance.</li>
        </ol>
        <?php if ($pending > 0): ?>
          <div class="alert alert-info small mt-3 mb-0">
            You have <strong><?php echo (int) $pending; ?></strong> request<?php echo $pending > 1 ? 's' : ''; ?> awaiting review.
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="panel reveal" data-reveal-order="3">
      <div class="panel-head">
        <i data-lucide="history"></i> Recent
        <span class="spacer"></span>
        <a href="<?php echo base_url('withdraw/history'); ?>">All</a>
      </div>
      <?php if (empty($recent)): ?>
        <div class="empty-state"><i data-lucide="inbox"></i>No requests yet.</div>
      <?php else: ?>
        <div class="feed">
          <?php foreach ($recent as $w): ?>
            <div class="feed-item">
              <span class="icon-tile sm grad-teal"><i data-lucide="banknote"></i></span>
              <div class="feed-main">
                <div class="feed-title num"><?php echo money($w->net_amount); ?> <span class="text-muted fw-normal">net</span></div>
                <div class="feed-sub"><?php echo fmt_date($w->created_at, 'd M Y'); ?> &middot; <?php echo html_escape($w->network); ?></div>
              </div>
              <?php echo chip($w->status); ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
