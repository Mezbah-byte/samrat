<a href="<?php echo base_url($application ? 'admin/agent-applications' : 'admin/agents'); ?>" class="btn btn-sm btn-outline-secondary mb-3">
  <i class="bi bi-arrow-left"></i> <?php echo $application ? 'All applications' : 'All agents'; ?>
</a>

<div class="row justify-content-center">
  <div class="col-lg-9">

    <?php if ($application): ?>
      <div class="alert alert-warning small">
        <i class="bi bi-clipboard-check"></i>
        Approving application <strong>#<?php echo (int) $application->id; ?></strong> from
        <strong><?php echo html_escape($application->full_name); ?></strong>
        (<?php echo (int) $application->team_active_count; ?> active team members at submission).
        Set a password below and pass it to them yourself &mdash; it is never emailed.
        Saving this form creates the agent and marks the application approved.
      </div>
    <?php endif; ?>

    <div class="card">
      <div class="card-header"><i class="bi bi-person-vcard"></i> <?php echo $mode === 'edit' ? 'Edit Agent' : 'New Agent'; ?></div>
      <div class="card-body">

        <?php echo validation_errors('<div class="alert alert-danger py-2 small">', '</div>'); ?>

        <?php echo form_open_multipart($mode === 'edit'
          ? 'admin/agents/edit/'.$a->id
          : 'admin/agents/create'.($application ? '/'.(int) $application->id : '')); ?>

          <?php if ($application): ?>
            <input type="hidden" name="application_id" value="<?php echo (int) $application->id; ?>">
          <?php endif; ?>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Name <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control" value="<?php echo set_value('name', $a->name); ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Username <span class="text-danger">*</span></label>
              <input type="text" name="username" class="form-control" value="<?php echo set_value('username', $a->username); ?>" required>
              <div class="form-text">Letters, numbers, dash and underscore only. Used to sign in.</div>
            </div>

            <div class="col-md-6">
              <label class="form-label">Email <span class="text-danger">*</span></label>
              <input type="email" name="email" class="form-control" value="<?php echo set_value('email', $a->email); ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Country</label>
              <select name="country" class="form-select">
                <option value="">Select a country</option>
                <?php foreach (country_list() as $c): ?>
                  <option value="<?php echo html_escape($c); ?>" <?php echo set_value('country', $a->country) === $c ? 'selected' : ''; ?>><?php echo html_escape($c); ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label">NID Number <span class="text-danger">*</span></label>
              <input type="text" name="nid_number" class="form-control" value="<?php echo set_value('nid_number', $a->nid_number); ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Linked User <span class="text-muted">(optional)</span></label>
              <input type="text" name="linked_username" class="form-control"
                     value="<?php echo set_value('linked_username', $linked ? $linked->username : ''); ?>"
                     placeholder="username or email">
              <div class="form-text">
                This user's downline becomes the agent's team. Leave blank for a standalone agent
                &mdash; they will have no team and earn no automatic commission.
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label">
                NID Front <?php echo ($mode === 'edit' || $application) ? '<span class="text-muted">(leave blank to keep current)</span>' : '<span class="text-danger">*</span>'; ?>
              </label>
              <input type="file" name="nid_front" class="form-control" accept="image/*" <?php echo ($mode === 'create' && ! $application) ? 'required' : ''; ?>>
              <div class="form-text">JPG, PNG, GIF or WEBP, up to 4 MB.</div>
              <?php if ($mode === 'edit' && $a->nid_front): ?>
                <a href="<?php echo base_url('admin/agents/nid/'.$a->id.'/front'); ?>" target="_blank" rel="noopener" class="d-inline-block mt-2">
                  <img src="<?php echo base_url('admin/agents/nid/'.$a->id.'/front'); ?>" alt="NID front" class="img-thumbnail" style="max-height:120px">
                </a>
              <?php endif; ?>
            </div>
            <div class="col-md-6">
              <label class="form-label">
                NID Back <?php echo ($mode === 'edit' || $application) ? '<span class="text-muted">(leave blank to keep current)</span>' : '<span class="text-danger">*</span>'; ?>
              </label>
              <input type="file" name="nid_back" class="form-control" accept="image/*" <?php echo ($mode === 'create' && ! $application) ? 'required' : ''; ?>>
              <div class="form-text">JPG, PNG, GIF or WEBP, up to 4 MB.</div>
              <?php if ($mode === 'edit' && $a->nid_back): ?>
                <a href="<?php echo base_url('admin/agents/nid/'.$a->id.'/back'); ?>" target="_blank" rel="noopener" class="d-inline-block mt-2">
                  <img src="<?php echo base_url('admin/agents/nid/'.$a->id.'/back'); ?>" alt="NID back" class="img-thumbnail" style="max-height:120px">
                </a>
              <?php endif; ?>
            </div>

            <div class="col-md-4">
              <label class="form-label">Deposit Commission (%)</label>
              <input type="number" step="0.0001" min="0" max="100" name="commission_deposit_percent" class="form-control"
                     value="<?php echo set_value('commission_deposit_percent', $a->commission_deposit_percent); ?>"
                     placeholder="<?php echo html_escape(setting('agent_deposit_percent', '1')); ?>">
              <div class="form-text">Blank = platform default.</div>
            </div>
            <div class="col-md-4">
              <label class="form-label">Daily Profit Commission (%)</label>
              <input type="number" step="0.0001" min="0" max="100" name="commission_profit_percent" class="form-control"
                     value="<?php echo set_value('commission_profit_percent', $a->commission_profit_percent); ?>"
                     placeholder="<?php echo html_escape(setting('agent_profit_percent', '0.5')); ?>">
              <div class="form-text">Blank = platform default.</div>
            </div>
            <div class="col-md-4">
              <label class="form-label">Status</label>
              <select name="status" class="form-select">
                <option value="active"  <?php echo $a->status === 'active'  ? 'selected' : ''; ?>>Active</option>
                <option value="blocked" <?php echo $a->status === 'blocked' ? 'selected' : ''; ?>>Blocked</option>
              </select>
            </div>

            <div class="col-12">
              <label class="form-label">
                Password <?php echo $mode === 'edit' ? '<span class="text-muted">(leave blank to keep current)</span>' : '<span class="text-danger">*</span>'; ?>
              </label>
              <input type="password" name="password" class="form-control" minlength="8" <?php echo $mode === 'create' ? 'required' : ''; ?>>
              <div class="form-text">At least 8 characters. Hand it to the agent yourself; nothing mails it out.</div>
            </div>

            <?php if ($application): ?>
              <div class="col-12">
                <label class="form-label">Review Note <span class="text-muted">(optional)</span></label>
                <input type="text" name="admin_note" class="form-control" maxlength="500" value="<?php echo set_value('admin_note'); ?>">
              </div>
            <?php endif; ?>
          </div>

          <button class="btn btn-primary mt-3">
            <i class="bi bi-check2"></i>
            <?php echo $mode === 'edit' ? 'Save Changes' : ($application ? 'Approve and Create Agent' : 'Create Agent'); ?>
          </button>
          <a href="<?php echo base_url($application ? 'admin/agent-applications' : 'admin/agents'); ?>" class="btn btn-outline-secondary mt-3">Cancel</a>
        <?php echo form_close(); ?>

      </div>
    </div>
  </div>
</div>
