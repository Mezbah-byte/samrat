<a href="<?php echo base_url('admin/deposit-methods'); ?>" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> All wallets</a>

<div class="row justify-content-center">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header"><i class="bi bi-wallet2"></i> <?php echo $mode === 'edit' ? 'Edit Wallet' : 'New Wallet'; ?></div>
      <div class="card-body">
        <?php echo validation_errors('<div class="alert alert-danger py-2 small">', '</div>'); ?>

        <?php echo form_open_multipart($mode === 'edit' ? 'admin/deposit-methods/edit/'.$m->id : 'admin/deposit-methods/create'); ?>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Display Name <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control" value="<?php echo set_value('name', $m->name); ?>" placeholder="USDT TRC20" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">Network <span class="text-danger">*</span></label>
              <input type="text" name="network" class="form-control text-uppercase" list="networkList" value="<?php echo set_value('network', $m->network); ?>" required>
              <datalist id="networkList">
                <?php foreach ($networks as $key => $label): ?>
                  <option value="<?php echo html_escape($key); ?>"><?php echo html_escape($label); ?></option>
                <?php endforeach; ?>
              </datalist>
            </div>
            <div class="col-md-3">
              <label class="form-label">Currency <span class="text-danger">*</span></label>
              <input type="text" name="currency" class="form-control text-uppercase" value="<?php echo set_value('currency', $m->currency); ?>" required>
            </div>
            <div class="col-12">
              <label class="form-label">Wallet Address <span class="text-danger">*</span></label>
              <input type="text" name="wallet_address" class="form-control mono" value="<?php echo set_value('wallet_address', $m->wallet_address); ?>" required minlength="20">
              <div class="form-text text-danger">Check this character by character. Every deposit will be sent here.</div>
            </div>
            <div class="col-md-4">
              <label class="form-label">Minimum Amount</label>
              <div class="input-group">
                <span class="input-group-text"><?php echo html_escape(currency()); ?></span>
                <input type="number" step="0.01" min="0" name="min_amount" class="form-control" value="<?php echo set_value('min_amount', $m->min_amount); ?>">
              </div>
            </div>
            <div class="col-md-4">
              <label class="form-label">Sort Order</label>
              <input type="number" min="0" name="sort_order" class="form-control" value="<?php echo set_value('sort_order', $m->sort_order); ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Status</label>
              <select name="status" class="form-select">
                <option value="active" <?php echo $m->status === 'active' ? 'selected' : ''; ?>>Active</option>
                <option value="inactive" <?php echo $m->status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">Instructions to the user</label>
              <textarea name="instructions" class="form-control" rows="3"><?php echo set_value('instructions', $m->instructions); ?></textarea>
              <div class="form-text">Shown on the deposit page. State the exact token and network.</div>
            </div>
            <div class="col-12">
              <label class="form-label">QR Code Image</label>
              <input type="file" name="qr_image" class="form-control" accept="image/*">
              <?php if ($m->qr_image): ?>
                <img src="<?php echo upload_url('qr', $m->qr_image); ?>" class="mt-2 rounded" style="max-height:130px" alt="">
              <?php endif; ?>
            </div>
          </div>

          <button class="btn btn-primary mt-3"><i class="bi bi-check2"></i> <?php echo $mode === 'edit' ? 'Save Changes' : 'Add Wallet'; ?></button>
          <a href="<?php echo base_url('admin/deposit-methods'); ?>" class="btn btn-outline-secondary mt-3">Cancel</a>
        <?php echo form_close(); ?>
      </div>
    </div>
  </div>
</div>
