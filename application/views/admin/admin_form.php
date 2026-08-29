<a href="<?php echo base_url('admin/admins'); ?>" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> All admins</a>

<div class="row justify-content-center">
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header"><i class="bi bi-person-badge"></i> <?php echo $mode === 'edit' ? 'Edit Admin' : 'New Admin'; ?></div>
      <div class="card-body">
        <?php echo validation_errors('<div class="alert alert-danger py-2 small">', '</div>'); ?>

        <?php echo form_open($mode === 'edit' ? 'admin/admins/edit/'.$a->id : 'admin/admins/create'); ?>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Name <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control" value="<?php echo set_value('name', $a->name); ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Username <span class="text-danger">*</span></label>
              <input type="text" name="username" class="form-control" value="<?php echo set_value('username', $a->username); ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Email <span class="text-danger">*</span></label>
              <input type="email" name="email" class="form-control" value="<?php echo set_value('email', $a->email); ?>" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">Role</label>
              <select name="role" class="form-select">
                <?php foreach (array('super_admin' => 'Super Admin', 'admin' => 'Admin', 'moderator' => 'Moderator') as $key => $label): ?>
                  <option value="<?php echo $key; ?>" <?php echo $a->role === $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Status</label>
              <select name="status" class="form-select">
                <option value="active" <?php echo $a->status === 'active' ? 'selected' : ''; ?>>Active</option>
                <option value="blocked" <?php echo $a->status === 'blocked' ? 'selected' : ''; ?>>Blocked</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">
                Password <?php echo $mode === 'edit' ? '<span class="text-muted">(leave blank to keep current)</span>' : '<span class="text-danger">*</span>'; ?>
              </label>
              <input type="password" name="password" class="form-control" minlength="8" <?php echo $mode === 'create' ? 'required' : ''; ?>>
              <div class="form-text">At least 8 characters.</div>
            </div>
          </div>

          <button class="btn btn-primary mt-3"><i class="bi bi-check2"></i> <?php echo $mode === 'edit' ? 'Save Changes' : 'Create Admin'; ?></button>
          <a href="<?php echo base_url('admin/admins'); ?>" class="btn btn-outline-secondary mt-3">Cancel</a>
        <?php echo form_close(); ?>
      </div>
    </div>
  </div>
</div>
