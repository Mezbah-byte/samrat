<ul class="nav nav-tabs mb-3">
  <?php foreach ($groups as $g): ?>
    <li class="nav-item">
      <a class="nav-link <?php echo $g === $group ? 'active' : ''; ?>" href="<?php echo base_url('admin/settings/index/'.$g); ?>">
        <?php echo html_escape(ucfirst($g)); ?>
      </a>
    </li>
  <?php endforeach; ?>
</ul>

<div class="row justify-content-center">
  <div class="col-lg-9">
    <div class="card">
      <div class="card-header"><i class="bi bi-sliders"></i> <?php echo html_escape(ucfirst($group)); ?> Settings</div>
      <div class="card-body">
        <?php echo form_open_multipart('admin/settings/index/'.$group); ?>
          <?php foreach ($rows as $row): ?>
            <div class="mb-3">
              <label class="form-label"><?php echo html_escape($row->label ?: $row->key); ?></label>

              <?php if ($row->type === 'boolean'): ?>
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" name="<?php echo html_escape($row->key); ?>"
                         id="set_<?php echo html_escape($row->key); ?>" value="1" <?php echo $row->value === '1' ? 'checked' : ''; ?>>
                  <label class="form-check-label small text-muted" for="set_<?php echo html_escape($row->key); ?>">Enabled</label>
                </div>

              <?php elseif ($row->type === 'textarea'): ?>
                <textarea name="<?php echo html_escape($row->key); ?>" class="form-control" rows="3"><?php echo html_escape($row->value); ?></textarea>

              <?php elseif ($row->type === 'number'): ?>
                <input type="number" step="0.01" name="<?php echo html_escape($row->key); ?>" class="form-control" value="<?php echo html_escape($row->value); ?>">

              <?php elseif ($row->type === 'image'): ?>
                <?php if ($row->value): ?>
                  <div class="d-flex align-items-center gap-3 mb-2">
                    <img src="<?php echo upload_url('logo', $row->value); ?>" style="max-height:56px" class="rounded border p-1" alt="">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" name="remove_<?php echo html_escape($row->key); ?>" id="rm_<?php echo html_escape($row->key); ?>" value="1">
                      <label class="form-check-label small text-danger" for="rm_<?php echo html_escape($row->key); ?>">Remove</label>
                    </div>
                  </div>
                <?php endif; ?>
                <input type="file" name="<?php echo html_escape($row->key); ?>" class="form-control" accept="image/*">

              <?php else: ?>
                <input type="text" name="<?php echo html_escape($row->key); ?>" class="form-control" value="<?php echo html_escape($row->value); ?>">
              <?php endif; ?>

              <div class="form-text mono small"><?php echo html_escape($row->key); ?></div>
            </div>
          <?php endforeach; ?>

          <button class="btn btn-primary"><i class="bi bi-check2"></i> Save <?php echo html_escape(ucfirst($group)); ?> Settings</button>
        <?php echo form_close(); ?>

        <?php if ($group === 'system' && $admin->role === 'super_admin'): ?>
          <hr>
          <h6 class="fw-semibold">Cron</h6>
          <p class="small text-muted">The secret key authorises the HTTP cron URL. Rotate it if the URL has been exposed.</p>
          <div class="copy-field mb-2">
            <input type="text" class="form-control form-control-sm" id="cronUrl" readonly value="<?php echo base_url('cron/run?key='.setting('cron_secret')); ?>">
            <button class="btn btn-sm btn-outline-secondary" type="button" data-copy-target="#cronUrl"><i class="bi bi-clipboard"></i></button>
          </div>
          <?php echo form_open('admin/settings/regenerate_cron_secret'); ?>
            <button class="btn btn-sm btn-outline-danger" data-confirm="Generate a new cron secret? Any scheduled task using the old URL will stop working.">
              <i class="bi bi-arrow-repeat"></i> Regenerate Secret
            </button>
          <?php echo form_close(); ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
