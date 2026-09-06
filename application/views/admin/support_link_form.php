<a href="<?php echo base_url('admin/support-links'); ?>" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> All channels</a>

<div class="row justify-content-center">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header"><i class="bi bi-life-preserver"></i> <?php echo $mode === 'edit' ? 'Edit Channel' : 'New Channel'; ?></div>
      <div class="card-body">
        <?php echo validation_errors('<div class="alert alert-danger py-2 small">', '</div>'); ?>

        <?php echo form_open($mode === 'edit' ? 'admin/support-links/edit/'.$m->id : 'admin/support-links/create'); ?>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Label <span class="text-danger">*</span></label>
              <input type="text" name="label" class="form-control" value="<?php echo set_value('label', $m->label); ?>" placeholder="Telegram" required maxlength="60">
              <div class="form-text">Shown to the user. Must be unique.</div>
            </div>

            <div class="col-md-3">
              <label class="form-label">Type <span class="text-danger">*</span></label>
              <select name="kind" class="form-select">
                <?php foreach (array('link' => 'Website / social link', 'mailto' => 'Email address', 'tel' => 'Phone number') as $k => $label): ?>
                  <option value="<?php echo $k; ?>" <?php echo set_select('kind', $k, $m->kind === $k); ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label">Sort Order</label>
              <input type="number" min="0" name="sort_order" class="form-control" value="<?php echo set_value('sort_order', $m->sort_order); ?>">
            </div>

            <div class="col-12">
              <label class="form-label">Value <span class="text-danger">*</span></label>
              <input type="text" name="value" class="form-control" value="<?php echo set_value('value', $m->value); ?>" placeholder="https://t.me/yourchannel" required maxlength="255">
              <div class="form-text">
                A link needs the full address &mdash; a bare domain gets <span class="mono">https://</span> added for you.
                Only <span class="mono">http</span> and <span class="mono">https</span> are accepted.
                Use the Email type for an address and the Phone type for a number.
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label">Icon <span class="text-danger">*</span></label>
              <input type="text" name="icon" class="form-control mono" list="iconList" value="<?php echo set_value('icon', $m->icon); ?>" required maxlength="40">
              <datalist id="iconList">
                <?php foreach ($icons as $name => $label): ?>
                  <option value="<?php echo html_escape($name); ?>"><?php echo html_escape($label); ?></option>
                <?php endforeach; ?>
              </datalist>
              <div class="form-text">A <a href="https://lucide.dev/icons" target="_blank" rel="noopener">lucide</a> icon name. Pick from the list unless you know the bundled set has the one you want.</div>
            </div>

            <div class="col-md-6">
              <label class="form-label">Status</label>
              <select name="status" class="form-select">
                <option value="active" <?php echo set_select('status', 'active', $m->status === 'active'); ?>>Active</option>
                <option value="inactive" <?php echo set_select('status', 'inactive', $m->status === 'inactive'); ?>>Inactive</option>
              </select>
              <div class="form-text">Inactive keeps the row but hides it from users.</div>
            </div>

            <div class="col-12">
              <label class="form-label">Note</label>
              <input type="text" name="note" class="form-control" value="<?php echo set_value('note', $m->note); ?>" placeholder="Fastest reply, 10am-7pm" maxlength="160">
              <div class="form-text">Optional one-liner under the channel on the Support page.</div>
            </div>
          </div>

          <button class="btn btn-primary mt-3"><i class="bi bi-check2"></i> <?php echo $mode === 'edit' ? 'Save Changes' : 'Add Channel'; ?></button>
          <a href="<?php echo base_url('admin/support-links'); ?>" class="btn btn-outline-secondary mt-3">Cancel</a>
        <?php echo form_close(); ?>
      </div>
    </div>
  </div>
</div>
