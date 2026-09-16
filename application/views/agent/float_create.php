<div class="page-head reveal" data-reveal-order="0">
  <div>
    <h1>Buy Float</h1>
    <p class="lede">You hold <strong class="text-accent"><?php echo money($balance); ?></strong> right now.</p>
  </div>
  <a href="<?php echo base_url('agent/float'); ?>" class="btn btn-ghost"><i data-lucide="arrow-left"></i> Float orders</a>
</div>

<div class="row g-3">
  <div class="col-xl-7">
    <div class="panel reveal" data-reveal-order="1">
      <div class="panel-head"><i data-lucide="file-text"></i> Record Your Transfer</div>
      <div class="panel-body">
        <div class="d-flex gap-2 align-items-start mb-3">
          <span class="icon-tile sm grad-info"><i data-lucide="info"></i></span>
          <p class="small text-muted mb-0">
            Send USDT to one of the company wallets on the right, then record it here.
            Float is sold at par. Your balance moves only after an admin verifies the hash.
          </p>
        </div>

        <?php echo validation_errors('<div class="alert alert-danger py-2 small">', '</div>'); ?>
        <?php echo form_open_multipart('agent/float/create'); ?>
          <div class="mb-3">
            <label class="form-label">Company wallet you paid <span class="text-bad">*</span></label>
            <select name="deposit_method_id" class="form-select" required id="methodSelect">
              <?php foreach ($methods as $m): ?>
                <option value="<?php echo (int) $m->id; ?>" <?php echo set_select('deposit_method_id', $m->id); ?>>
                  <?php echo html_escape($m->name); ?> &mdash; min <?php echo money($m->min_amount); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Amount sent <span class="text-bad">*</span></label>
            <div class="input-group">
              <span class="input-group-text"><?php echo html_escape(currency()); ?></span>
              <input type="number" step="0.01" min="0" name="amount" class="form-control" required
                     value="<?php echo set_value('amount'); ?>">
            </div>
            <div class="form-text">Must match the transfer exactly, or the admin will reject it.</div>
          </div>

          <div class="mb-3">
            <label class="form-label">Transaction hash <span class="text-bad">*</span></label>
            <input type="text" name="txid" class="form-control mono" maxlength="191" required
                   minlength="10" placeholder="e.g. 8f3c1b...d92a" value="<?php echo set_value('txid'); ?>">
            <div class="form-text">Each hash can only be submitted once.</div>
          </div>

          <div class="mb-4">
            <label class="form-label">Screenshot <span class="text-muted">(optional)</span></label>
            <input type="file" name="proof_image" class="form-control" accept="image/*">
            <div class="form-text">JPG, PNG, GIF or WEBP, up to 4 MB.</div>
          </div>

          <div class="d-flex gap-2">
            <button class="btn btn-grad"><i data-lucide="send"></i> Submit Float Order</button>
            <a href="<?php echo base_url('agent/float'); ?>" class="btn btn-quiet">Cancel</a>
          </div>
        <?php echo form_close(); ?>
      </div>
    </div>
  </div>

  <div class="col-xl-5">
    <div class="panel reveal" data-reveal-order="2">
      <div class="panel-head"><i data-lucide="landmark"></i> Company Wallets</div>
      <div class="panel-body">
        <?php foreach ($methods as $i => $m): ?>
          <div class="<?php echo $i ? 'mt-4 pt-3' : ''; ?>" <?php echo $i ? 'style="border-top:1px solid var(--line)"' : ''; ?>>
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="fw-semibold"><?php echo html_escape($m->name); ?></span>
              <span class="chip chip-info"><?php echo html_escape($m->network); ?></span>
            </div>
            <?php if ($m->qr_image): ?>
              <div class="qr-box"><img src="<?php echo upload_url('qr', $m->qr_image); ?>" alt="QR code"></div>
            <?php endif; ?>
            <label class="form-label">Wallet address</label>
            <div class="copy-field mb-2">
              <input type="text" class="form-control form-control-sm mono" id="addr<?php echo (int) $m->id; ?>" readonly value="<?php echo html_escape($m->wallet_address); ?>">
              <button class="btn btn-ghost" type="button" data-copy-target="#addr<?php echo (int) $m->id; ?>" aria-label="Copy address"><i data-lucide="copy"></i></button>
            </div>
            <?php if ($m->instructions): ?>
              <p class="small text-muted mb-0"><?php echo html_escape($m->instructions); ?></p>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>

        <div class="tile mt-3">
          <div class="tile-row">
            <span class="text-muted">Float you hold now</span>
            <strong class="num"><?php echo money($balance); ?></strong>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
