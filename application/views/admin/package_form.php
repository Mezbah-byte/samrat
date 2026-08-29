<a href="<?php echo base_url('admin/packages'); ?>" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> All packages</a>

<div class="row justify-content-center">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header"><i class="bi bi-box-seam"></i> <?php echo $mode === 'edit' ? 'Edit Package' : 'New Package'; ?></div>
      <div class="card-body">
        <?php echo validation_errors('<div class="alert alert-danger py-2 small">', '</div>'); ?>

        <?php echo form_open_multipart($mode === 'edit' ? 'admin/packages/edit/'.$p->id : 'admin/packages/create'); ?>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Name <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control" value="<?php echo set_value('name', $p->name); ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Deposit Amount <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text"><?php echo html_escape(currency()); ?></span>
                <input type="number" step="0.01" min="0.01" name="price" class="form-control" value="<?php echo set_value('price', $p->price); ?>" required>
              </div>
            </div>
            <div class="col-md-4">
              <label class="form-label">Daily Return % <span class="text-danger">*</span></label>
              <div class="input-group">
                <input type="number" step="0.0001" min="0.0001" name="daily_return_percent" class="form-control" value="<?php echo set_value('daily_return_percent', $p->daily_return_percent); ?>" required>
                <span class="input-group-text">%</span>
              </div>
            </div>
            <div class="col-md-4">
              <label class="form-label">Duration (days) <span class="text-danger">*</span></label>
              <input type="number" min="1" name="duration_days" class="form-control" value="<?php echo set_value('duration_days', $p->duration_days); ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Daily Ads <span class="text-danger">*</span></label>
              <input type="number" min="0" name="daily_ads" class="form-control" value="<?php echo set_value('daily_ads', $p->daily_ads); ?>" required>
              <div class="form-text">Ads the user must watch each day to unlock that day's profit.</div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Minimum Withdrawal <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text"><?php echo html_escape(currency()); ?></span>
                <input type="number" step="0.01" min="0" name="min_withdraw" class="form-control" value="<?php echo set_value('min_withdraw', $p->min_withdraw); ?>" required>
              </div>
            </div>
            <div class="col-md-3">
              <label class="form-label">Sort Order</label>
              <input type="number" min="0" name="sort_order" class="form-control" value="<?php echo set_value('sort_order', $p->sort_order); ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label">Status</label>
              <select name="status" class="form-select">
                <option value="active" <?php echo $p->status === 'active' ? 'selected' : ''; ?>>Active</option>
                <option value="inactive" <?php echo $p->status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">Description</label>
              <textarea name="description" class="form-control" rows="3"><?php echo set_value('description', $p->description); ?></textarea>
            </div>
            <div class="col-12">
              <label class="form-label">Image <span class="text-muted">(optional)</span></label>
              <input type="file" name="image" class="form-control" accept="image/*">
              <?php if ($p->image): ?>
                <img src="<?php echo upload_url('ads', $p->image); ?>" class="mt-2 rounded" style="max-height:80px" alt="">
              <?php endif; ?>
            </div>
          </div>

          <button class="btn btn-primary mt-3"><i class="bi bi-check2"></i> <?php echo $mode === 'edit' ? 'Save Changes' : 'Create Package'; ?></button>
          <a href="<?php echo base_url('admin/packages'); ?>" class="btn btn-outline-secondary mt-3">Cancel</a>
        <?php echo form_close(); ?>
      </div>
    </div>
  </div>
</div>
