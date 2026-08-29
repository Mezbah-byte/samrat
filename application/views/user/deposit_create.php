<div class="page-head reveal" data-reveal-order="0">
  <div>
    <h1>Submit Deposit</h1>
    <p class="lede"><?php echo html_escape($package->name); ?> &middot; <?php echo money($package->price); ?></p>
  </div>
  <a href="<?php echo base_url('deposit'); ?>" class="btn btn-ghost"><i data-lucide="arrow-left"></i> Back</a>
</div>

<div class="row g-3">
  <div class="col-xl-7">
    <div class="panel reveal" data-reveal-order="1">
      <div class="panel-head"><i data-lucide="upload"></i> Deposit Proof</div>
      <div class="panel-body">
        <?php echo validation_errors('<div class="alert alert-danger py-2 small">', '</div>'); ?>

        <div class="alert alert-info small">
          <ol class="mb-0 ps-3">
            <li>Send exactly <strong><?php echo money($package->price); ?></strong> in USDT to the wallet shown.</li>
            <li>Copy the transaction hash (TXID) from your wallet or exchange.</li>
            <li>Submit it here. An admin verifies it on-chain and activates your plan.</li>
          </ol>
        </div>

        <?php echo form_open_multipart('deposit/create/'.$package->id); ?>
          <div class="mb-3">
            <label class="form-label">Package</label>
            <input type="text" class="form-control" value="<?php echo html_escape($package->name).' - '.money($package->price); ?>" readonly>
            <div class="form-text">The amount is fixed by the package price.</div>
          </div>

          <div class="mb-3">
            <label class="form-label">Payment Wallet <span class="text-bad">*</span></label>
            <select name="deposit_method_id" class="form-select" id="methodSelect" required>
              <?php foreach ($methods as $m): ?>
                <option value="<?php echo (int) $m->id; ?>" <?php echo set_select('deposit_method_id', $m->id); ?>
                        data-address="<?php echo html_escape($m->wallet_address); ?>"
                        data-network="<?php echo html_escape($m->network); ?>">
                  <?php echo html_escape($m->name.' ('.$m->network.')'); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Transaction Hash (TXID) <span class="text-bad">*</span></label>
            <input type="text" name="txid" class="form-control mono" value="<?php echo set_value('txid'); ?>"
                   placeholder="e.g. 8f3c1b...d92a" required minlength="10" maxlength="191">
            <div class="form-text">Each TXID can only be submitted once.</div>
          </div>

          <div class="mb-3">
            <label class="form-label">Payment Screenshot <span class="text-muted">(optional)</span></label>
            <input type="file" name="proof_image" class="form-control" accept="image/*">
            <div class="form-text">JPG, PNG, GIF or WEBP, up to 4 MB.</div>
          </div>

          <div class="d-flex gap-2">
            <button class="btn btn-grad"><i data-lucide="send"></i> Submit Deposit</button>
            <a href="<?php echo base_url('deposit'); ?>" class="btn btn-quiet">Cancel</a>
          </div>
        <?php echo form_close(); ?>
      </div>
    </div>
  </div>

  <div class="col-xl-5">
    <div class="panel reveal" data-reveal-order="2">
      <div class="panel-head"><i data-lucide="qr-code"></i> Send Payment To</div>
      <div class="panel-body">
        <?php foreach ($methods as $i => $m): ?>
          <div class="wallet-block <?php echo $i > 0 ? 'd-none' : ''; ?>" data-method="<?php echo (int) $m->id; ?>">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="fw-semibold"><?php echo html_escape($m->name); ?></span>
              <span class="chip chip-info"><?php echo html_escape($m->network); ?></span>
            </div>
            <?php if ($m->qr_image): ?>
              <div class="qr-box"><img src="<?php echo upload_url('qr', $m->qr_image); ?>" alt="QR code"></div>
            <?php endif; ?>
            <label class="form-label">Wallet address</label>
            <div class="copy-field mb-2">
              <input type="text" class="form-control form-control-sm" id="addr<?php echo (int) $m->id; ?>" readonly value="<?php echo html_escape($m->wallet_address); ?>">
              <button class="btn btn-ghost" type="button" data-copy-target="#addr<?php echo (int) $m->id; ?>" aria-label="Copy address"><i data-lucide="copy"></i></button>
            </div>
            <?php if ($m->instructions): ?>
              <p class="small text-muted mb-0"><?php echo html_escape($m->instructions); ?></p>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>

        <div class="tile mt-3">
          <div class="d-flex justify-content-between align-items-center">
            <span class="text-muted">Amount to send</span>
            <strong class="plan-price" style="font-size:1.5rem"><?php echo money($package->price); ?></strong>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// Swap the displayed wallet block when the select changes.
document.getElementById('methodSelect').addEventListener('change', function () {
  var id = this.value;
  document.querySelectorAll('.wallet-block').forEach(function (el) {
    el.classList.toggle('d-none', el.getAttribute('data-method') !== id);
  });
});
</script>
