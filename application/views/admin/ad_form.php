<a href="<?php echo base_url('admin/ads'); ?>" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> All ads</a>

<div class="row justify-content-center">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header"><i class="bi bi-badge-ad"></i> <?php echo $mode === 'edit' ? 'Edit Ad' : 'New Ad'; ?></div>
      <div class="card-body">
        <?php echo validation_errors('<div class="alert alert-danger py-2 small">', '</div>'); ?>

        <?php echo form_open_multipart($mode === 'edit' ? 'admin/ads/edit/'.$a->id : 'admin/ads/create'); ?>
          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label">Title <span class="text-danger">*</span></label>
              <input type="text" name="title" class="form-control" value="<?php echo set_value('title', $a->title); ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Type</label>
              <select name="type" class="form-select">
                <?php foreach (array('image', 'video', 'banner', 'link') as $t): ?>
                  <option value="<?php echo $t; ?>" <?php echo $a->type === $t ? 'selected' : ''; ?>><?php echo ucfirst($t); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Placement</label>
              <select name="placement" class="form-select">
                <option value="daily_task" <?php echo $a->placement === 'daily_task' ? 'selected' : ''; ?>>Daily task (counts toward quota)</option>
                <option value="global" <?php echo $a->placement === 'global' ? 'selected' : ''; ?>>Global (display only)</option>
              </select>
              <div class="form-text">Only daily-task ads unlock profit.</div>
            </div>
            <div class="col-md-4">
              <label class="form-label">Watch Seconds <span class="text-danger">*</span></label>
              <input type="number" min="0" name="watch_seconds" class="form-control" value="<?php echo set_value('watch_seconds', $a->watch_seconds); ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Sort Order</label>
              <input type="number" min="0" name="sort_order" class="form-control" value="<?php echo set_value('sort_order', $a->sort_order); ?>">
            </div>
            <div class="col-12">
              <label class="form-label">Target URL</label>
              <input type="url" name="target_url" class="form-control" value="<?php echo set_value('target_url', $a->target_url); ?>" placeholder="https://...">
            </div>
            <div class="col-12">
              <label class="form-label">Body Text</label>
              <textarea name="body" class="form-control" rows="3"><?php echo set_value('body', $a->body); ?></textarea>
            </div>
            <div class="col-md-4">
              <label class="form-label">Starts On</label>
              <input type="date" name="starts_at" class="form-control" value="<?php echo set_value('starts_at', $a->starts_at); ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Ends On</label>
              <input type="date" name="ends_at" class="form-control" value="<?php echo set_value('ends_at', $a->ends_at); ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Status</label>
              <select name="status" class="form-select">
                <option value="active" <?php echo $a->status === 'active' ? 'selected' : ''; ?>>Active</option>
                <option value="inactive" <?php echo $a->status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">Media Image</label>
              <input type="file" name="media" class="form-control" accept="image/*">
              <?php if ($a->media): ?>
                <img src="<?php echo upload_url('ads', $a->media); ?>" class="mt-2 rounded" style="max-height:130px" alt="">
              <?php endif; ?>
            </div>
          </div>

          <button class="btn btn-primary mt-3"><i class="bi bi-check2"></i> <?php echo $mode === 'edit' ? 'Save Changes' : 'Create Ad'; ?></button>
          <a href="<?php echo base_url('admin/ads'); ?>" class="btn btn-outline-secondary mt-3">Cancel</a>
        <?php echo form_close(); ?>
      </div>
    </div>
  </div>
</div>
