<a href="<?php echo base_url('agent/wallets'); ?>" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> My wallets</a>

<div class="row justify-content-center">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header"><i class="bi bi-wallet2"></i> <?php echo $mode === 'edit' ? 'Edit Wallet' : 'New Wallet'; ?></div>
      <div class="card-body">
        <?php echo validation_errors('<div class="alert alert-danger small">', '</div>'); ?>
        <?php echo form_open_multipart($mode === 'edit' ? 'agent/wallets/edit/'.$w->id : 'agent/wallets/create'); ?>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Label</label>
              <input type="text" name="label" class="form-control" maxlength="80" required
                     value="<?php echo set_value('label', $w->label); ?>" placeholder="e.g. Binance Pay">
              <div class="form-text">Users see this name when choosing how to pay you.</div>
            </div>
            <div class="col-md-3">
              <label class="form-label">Network</label>
              <select name="network" class="form-select" required>
                <?php foreach (network_list() as $code => $label): ?>
                  <option value="<?php echo $code; ?>" <?php echo set_select('network', $code, $w->network === $code); ?>><?php echo html_escape($label); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Currency</label>
              <input type="text" name="currency" class="form-control" maxlength="20" required
                     value="<?php echo set_value('currency', $w->currency); ?>">
            </div>

            <div class="col-12">
              <label class="form-label">Wallet Address</label>
              <input type="text" name="wallet_address" class="form-control mono" maxlength="191" required
                     value="<?php echo set_value('wallet_address', $w->wallet_address); ?>">
              <div class="form-text text-danger">
                Check this character by character. A wrong address means a user's money is gone
                and you still owe them the balance.
              </div>
            </div>

            <div class="col-md-4">
              <label class="form-label">Minimum Amount</label>
              <input type="number" step="0.01" min="0" name="min_amount" class="form-control"
                     value="<?php echo set_value('min_amount', $w->min_amount); ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Maximum Amount</label>
              <input type="number" step="0.01" min="0" name="max_amount" class="form-control"
                     value="<?php echo set_value('max_amount', $w->max_amount); ?>">
              <div class="form-text">0 = no ceiling.</div>
            </div>
            <div class="col-md-2">
              <label class="form-label">Sort Order</label>
              <input type="number" name="sort_order" class="form-control" value="<?php echo set_value('sort_order', $w->sort_order); ?>">
            </div>
            <div class="col-md-2">
              <label class="form-label">Status</label>
              <select name="status" class="form-select">
                <option value="active"   <?php echo set_select('status', 'active', $w->status === 'active'); ?>>Active</option>
                <option value="inactive" <?php echo set_select('status', 'inactive', $w->status === 'inactive'); ?>>Inactive</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label">QR Image (optional)</label>
              <?php if ($w->qr_image): ?>
                <div class="mb-2"><img src="<?php echo upload_url('qr', $w->qr_image); ?>" style="max-height:110px" class="rounded border p-1" alt=""></div>
              <?php endif; ?>
              <input type="file" name="qr_image" class="form-control" accept="image/*">
            </div>
            <div class="col-md-6">
              <label class="form-label">Instructions (optional)</label>
              <textarea name="instructions" class="form-control" rows="3" placeholder="Anything the user must know before sending."><?php echo set_value('instructions', $w->instructions); ?></textarea>
            </div>

            <div class="col-12">
              <button class="btn btn-primary"><i class="bi bi-check2"></i> <?php echo $mode === 'edit' ? 'Save Wallet' : 'Add Wallet'; ?></button>
              <a href="<?php echo base_url('agent/wallets'); ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
          </div>
        <?php echo form_close(); ?>
      </div>
    </div>
  </div>
</div>
