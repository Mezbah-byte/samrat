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
              <?php
                $roles = array('admin' => 'Admin', 'moderator' => 'Moderator');
                if ($admin->role === 'super_admin')
                {
                  $roles = array('super_admin' => 'Super Admin') + $roles;
                }
              ?>
              <select name="role" id="roleSelect" class="form-select" <?php echo $is_self ? 'disabled' : ''; ?>>
                <?php foreach ($roles as $key => $label): ?>
                  <option value="<?php echo $key; ?>" <?php echo $a->role === $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
              </select>
              <?php if ($is_self): ?>
                <input type="hidden" name="role" value="<?php echo html_escape($a->role); ?>">
                <div class="form-text">You cannot change your own role.</div>
              <?php else: ?>
                <div class="form-text">Picks the starting permissions below.</div>
              <?php endif; ?>
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

          <hr class="my-4">

          <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
              <h6 class="mb-0"><i class="bi bi-key"></i> Permissions</h6>
              <div class="form-text mb-0">Exactly what this account can reach. The role only fills these in.</div>
            </div>
            <?php if ($perms_editable): ?>
              <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-outline-secondary" data-perm-bulk="view">View only</button>
                <button type="button" class="btn btn-outline-secondary" data-perm-bulk="all">Select all</button>
                <button type="button" class="btn btn-outline-secondary" data-perm-bulk="none">Clear</button>
              </div>
            <?php endif; ?>
          </div>

          <?php if ( ! $perms_editable): ?>
            <div class="alert alert-secondary py-2 small mb-3">
              <i class="bi bi-lock"></i>
              <?php if ($a->role === 'super_admin'): ?>
                A super admin holds every permission by default and cannot be limited.
              <?php elseif ($is_self): ?>
                You cannot change your own permissions. Ask another super admin.
              <?php else: ?>
                Only a super admin can change permissions. Saving applies the role preset.
              <?php endif; ?>
            </div>
          <?php endif; ?>

          <div class="row g-2" id="permMatrix">
            <?php foreach ($catalogue as $module => $group): ?>
              <div class="col-md-6">
                <div class="border rounded p-2 h-100">
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="small fw-semibold"><?php echo html_escape($group['label']); ?></span>
                    <?php if ($perms_editable): ?>
                      <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none"
                              data-perm-module="<?php echo html_escape($module); ?>">toggle</button>
                    <?php endif; ?>
                  </div>
                  <?php foreach ($group['perms'] as $key => $label): ?>
                    <div class="form-check form-check-sm">
                      <input class="form-check-input" type="checkbox"
                             name="perms[]" value="<?php echo html_escape($key); ?>"
                             id="perm_<?php echo html_escape(str_replace('.', '_', $key)); ?>"
                             data-module="<?php echo html_escape($module); ?>"
                             data-action="<?php echo html_escape(substr($key, strlen($module) + 1)); ?>"
                             <?php echo in_array($key, $granted, TRUE) ? 'checked' : ''; ?>
                             <?php echo $perms_editable ? '' : 'disabled'; ?>>
                      <label class="form-check-label small" for="perm_<?php echo html_escape(str_replace('.', '_', $key)); ?>">
                        <?php echo html_escape($label); ?>
                      </label>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <button class="btn btn-primary mt-3"><i class="bi bi-check2"></i> <?php echo $mode === 'edit' ? 'Save Changes' : 'Create Admin'; ?></button>
          <a href="<?php echo base_url('admin/admins'); ?>" class="btn btn-outline-secondary mt-3">Cancel</a>
        <?php echo form_close(); ?>
      </div>
    </div>
  </div>
</div>

<?php if ($perms_editable): ?>
<script>
(function () {
  var matrix = document.getElementById('permMatrix');
  if (!matrix) return;

  var boxes   = matrix.querySelectorAll('input[name="perms[]"]');
  var presets = <?php echo json_encode($presets); ?>;

  function apply(list) {
    var wanted = {};
    for (var i = 0; i < list.length; i++) wanted[list[i]] = true;
    boxes.forEach(function (box) { box.checked = !!wanted[box.value]; });
  }

  matrix.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-perm-module]');
    if (!btn) return;
    var mod  = btn.getAttribute('data-perm-module');
    var mine = matrix.querySelectorAll('input[data-module="' + mod + '"]');
    var on   = Array.prototype.every.call(mine, function (b) { return b.checked; });
    mine.forEach(function (b) { b.checked = !on; });
  });

  document.querySelectorAll('[data-perm-bulk]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var mode = btn.getAttribute('data-perm-bulk');
      boxes.forEach(function (box) {
        box.checked = (mode === 'all') || (mode === 'view' && box.dataset.action === 'view');
      });
    });
  });

  // Switching role reloads the preset. A super admin holds everything by
  // bypass, so its preset is the '*' sentinel rather than a list - tick the
  // lot and grey the matrix out so the form says what will actually happen.
  var role = document.getElementById('roleSelect');
  if (role) {
    role.addEventListener('change', function () {
      var preset = presets[role.value];
      var isStar = (preset === '*');
      boxes.forEach(function (box) {
        box.disabled = isStar;
        if (isStar) box.checked = true;
      });
      if (!isStar) apply(preset || []);
    });
  }
})();
</script>
<?php endif; ?>
