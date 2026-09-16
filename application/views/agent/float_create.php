<a href="<?php echo base_url('agent/float'); ?>" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> Float orders</a>

<div class="row justify-content-center g-3">
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header"><i class="bi bi-coin"></i> Buy Deposit Float</div>
      <div class="card-body">
        <div class="alert alert-info small">
          <i class="bi bi-info-circle"></i>
          Send USDT to one of the company wallets below, then record the transfer here.
          Float is sold at par &mdash; <?php echo money(100); ?> sent becomes <?php echo money(100); ?> of float.
          Your balance moves only after an admin verifies the hash.
        </div>

        <?php echo validation_errors('<div class="alert alert-danger small">', '</div>'); ?>
        <?php echo form_open_multipart('agent/float/create'); ?>
          <div class="mb-3">
            <label class="form-label">Company wallet you paid</label>
            <select name="deposit_method_id" class="form-select" required id="methodPick">
              <?php foreach ($methods as $m): ?>
                <option value="<?php echo (int) $m->id; ?>"
                        data-address="<?php echo html_escape($m->wallet_address); ?>"
                        data-min="<?php echo html_escape($m->min_amount); ?>"
                        <?php echo set_select('deposit_method_id', $m->id); ?>>
                  <?php echo html_escape($m->name); ?> &mdash; min <?php echo money($m->min_amount); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Amount sent</label>
            <input type="number" step="0.01" min="0" name="amount" class="form-control" required
                   value="<?php echo set_value('amount'); ?>">
            <div class="form-text">Must match the transfer exactly, or the admin will reject it.</div>
          </div>

          <div class="mb-3">
            <label class="form-label">Transaction hash</label>
            <input type="text" name="txid" class="form-control mono" maxlength="191" required
                   value="<?php echo set_value('txid'); ?>">
          </div>

          <div class="mb-3">
            <label class="form-label">Screenshot (optional)</label>
            <input type="file" name="proof_image" class="form-control" accept="image/*">
          </div>

          <button class="btn btn-primary"><i class="bi bi-send"></i> Submit Float Order</button>
        <?php echo form_close(); ?>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card">
      <div class="card-header"><i class="bi bi-bank"></i> Company Wallets</div>
      <ul class="list-group list-group-flush">
        <?php foreach ($methods as $m): ?>
          <li class="list-group-item">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <strong class="small"><?php echo html_escape($m->name); ?></strong>
              <span class="badge text-bg-light border"><?php echo html_escape($m->network); ?></span>
            </div>
            <div class="copy-field">
              <input type="text" class="form-control form-control-sm mono" id="addr<?php echo (int) $m->id; ?>" readonly value="<?php echo html_escape($m->wallet_address); ?>">
              <button class="btn btn-sm btn-outline-secondary" type="button" data-copy-target="#addr<?php echo (int) $m->id; ?>"><i class="bi bi-clipboard"></i></button>
            </div>
            <?php if ($m->qr_image): ?>
              <div class="text-center mt-2"><img src="<?php echo upload_url('qr', $m->qr_image); ?>" style="max-height:130px" class="rounded border p-1" alt="QR"></div>
            <?php endif; ?>
            <?php if ($m->instructions): ?>
              <div class="small text-muted mt-2"><?php echo html_escape($m->instructions); ?></div>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>

    <div class="card mt-3">
      <div class="card-body small">
        <div class="d-flex justify-content-between"><span class="text-muted">Float you hold now</span><strong><?php echo money($balance); ?></strong></div>
      </div>
    </div>
  </div>
</div>
