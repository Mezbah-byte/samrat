<a href="<?php echo base_url('admin/notices'); ?>" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> All notices</a>

<div class="row justify-content-center">
  <div class="col-lg-9">
    <div class="card">
      <div class="card-header"><i class="bi bi-megaphone"></i> <?php echo $mode === 'edit' ? 'Edit Notice' : 'New Notice'; ?></div>
      <div class="card-body">
        <?php echo validation_errors('<div class="alert alert-danger py-2 small">', '</div>'); ?>

        <?php echo form_open_multipart($mode === 'edit' ? 'admin/notices/edit/'.$n->id : 'admin/notices/create'); ?>
          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label">Title <span class="text-danger">*</span></label>
              <input type="text" name="title" class="form-control" value="<?php echo set_value('title', $n->title); ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Type</label>
              <select name="type" class="form-select">
                <?php foreach (array('announcement', 'notice', 'update', 'promotion') as $t): ?>
                  <option value="<?php echo $t; ?>" <?php echo $n->type === $t ? 'selected' : ''; ?>><?php echo ucfirst($t); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">Content <span class="text-danger">*</span></label>
              <textarea name="content" class="form-control" rows="10" required><?php echo set_value('content', $n->content); ?></textarea>
              <div class="form-text">Basic HTML is allowed (&lt;p&gt;, &lt;strong&gt;, &lt;ul&gt;, &lt;a&gt;). Rendered as-is on the notice page.</div>
            </div>
            <div class="col-md-4">
              <label class="form-label">Publish At</label>
              <input type="datetime-local" name="published_at" class="form-control"
                     value="<?php echo $n->published_at ? date('Y-m-d\TH:i', strtotime($n->published_at)) : date('Y-m-d\TH:i'); ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Status</label>
              <select name="status" class="form-select">
                <option value="published" <?php echo $n->status === 'published' ? 'selected' : ''; ?>>Published</option>
                <option value="draft" <?php echo $n->status === 'draft' ? 'selected' : ''; ?>>Draft</option>
              </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
              <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="is_pinned" id="isPinned" value="1" <?php echo $n->is_pinned ? 'checked' : ''; ?>>
                <label class="form-check-label" for="isPinned">Pin to top</label>
              </div>
            </div>
            <div class="col-12">
              <label class="form-label">Image</label>
              <input type="file" name="image" class="form-control" accept="image/*">
              <?php if ($n->image): ?>
                <img src="<?php echo upload_url('notices', $n->image); ?>" class="mt-2 rounded" style="max-height:130px" alt="">
              <?php endif; ?>
            </div>
          </div>

          <button class="btn btn-primary mt-3"><i class="bi bi-check2"></i> <?php echo $mode === 'edit' ? 'Save Changes' : 'Publish Notice'; ?></button>
          <a href="<?php echo base_url('admin/notices'); ?>" class="btn btn-outline-secondary mt-3">Cancel</a>
        <?php echo form_close(); ?>
      </div>
    </div>
  </div>
</div>
