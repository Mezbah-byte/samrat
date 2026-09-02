<a href="<?php echo base_url('admin/agent-applications'); ?>" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> All applications</a>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-clipboard-check"></i> Application #<?php echo (int) $application->id; ?></span>
        <?php echo badge($application->status); ?>
      </div>
      <div class="card-body small">
        <div class="d-flex justify-content-between py-1"><span class="text-muted">Full name</span><span class="fw-semibold"><?php echo html_escape($application->full_name); ?></span></div>
        <div class="d-flex justify-content-between py-1"><span class="text-muted">Requested agent username</span><span class="fw-semibold"><?php echo html_escape($application->username); ?></span></div>
        <div class="d-flex justify-content-between py-1"><span class="text-muted">Email</span><span><?php echo html_escape($application->email); ?></span></div>
        <div class="d-flex justify-content-between py-1"><span class="text-muted">Country</span><span><?php echo html_escape($application->country ?: '-'); ?></span></div>
        <div class="d-flex justify-content-between py-1"><span class="text-muted">NID number</span><span class="fw-semibold"><?php echo html_escape($application->nid_number); ?></span></div>
        <div class="d-flex justify-content-between py-1"><span class="text-muted">Submitted</span><span><?php echo fmt_date($application->created_at); ?></span></div>
        <?php if ($application->reviewed_at): ?>
          <div class="d-flex justify-content-between py-1"><span class="text-muted">Reviewed</span><span><?php echo fmt_date($application->reviewed_at); ?> by <?php echo html_escape($application->reviewer_name ?: 'admin'); ?></span></div>
        <?php endif; ?>
        <?php if ($application->admin_note): ?>
          <hr class="my-2">
          <div class="text-muted">Review note</div>
          <div><?php echo html_escape($application->admin_note); ?></div>
        <?php endif; ?>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><i class="bi bi-person-badge"></i> NID Documents</div>
      <div class="card-body">
        <p class="small text-muted">
          These are stored privately &mdash; <code>uploads/nid/</code> is blocked at the webserver and
          these images are served only to a signed-in admin.
        </p>
        <div class="row g-3">
          <div class="col-md-6">
            <div class="text-muted small mb-2">Front</div>
            <?php if ($application->nid_front): ?>
              <a href="<?php echo base_url('admin/agent-applications/nid/'.$application->id.'/front'); ?>" target="_blank" rel="noopener">
                <img src="<?php echo base_url('admin/agent-applications/nid/'.$application->id.'/front'); ?>" alt="NID front" class="img-thumbnail w-100">
              </a>
            <?php else: ?>
              <div class="text-muted small">Not provided.</div>
            <?php endif; ?>
          </div>
          <div class="col-md-6">
            <div class="text-muted small mb-2">Back</div>
            <?php if ($application->nid_back): ?>
              <a href="<?php echo base_url('admin/agent-applications/nid/'.$application->id.'/back'); ?>" target="_blank" rel="noopener">
                <img src="<?php echo base_url('admin/agent-applications/nid/'.$application->id.'/back'); ?>" alt="NID back" class="img-thumbnail w-100">
              </a>
            <?php else: ?>
              <div class="text-muted small">Not provided.</div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card mb-3">
      <div class="card-header"><i class="bi bi-people-fill"></i> Applicant Account</div>
      <div class="card-body small">
        <div class="d-flex justify-content-between py-1">
          <span class="text-muted">User</span>
          <span class="fw-semibold">
            <?php if ($application->user_username): ?>
              <a href="<?php echo base_url('admin/users/view/'.(int) $application->user_id); ?>"><?php echo html_escape($application->user_username); ?></a>
            <?php else: ?>
              Deleted
            <?php endif; ?>
          </span>
        </div>
        <div class="d-flex justify-content-between py-1"><span class="text-muted">Account status</span><span><?php echo $application->user_status ? badge($application->user_status) : '-'; ?></span></div>
        <div class="d-flex justify-content-between py-1"><span class="text-muted">Referral code</span><span><?php echo html_escape($application->referral_code ?: '-'); ?></span></div>
        <div class="d-flex justify-content-between py-1"><span class="text-muted">Balance</span><span><?php echo money($application->balance); ?></span></div>
        <div class="d-flex justify-content-between py-1"><span class="text-muted">Total deposited</span><span><?php echo money($application->total_deposit); ?></span></div>
        <div class="d-flex justify-content-between py-1"><span class="text-muted">Joined</span><span><?php echo fmt_date($application->user_joined, 'd M Y'); ?></span></div>
        <hr class="my-2">
        <div class="d-flex justify-content-between py-1">
          <span class="text-muted">Active team at submit</span>
          <span class="fw-semibold"><?php echo (int) $application->team_active_count; ?></span>
        </div>
        <div class="d-flex justify-content-between py-1">
          <span class="text-muted">Active team now</span>
          <span class="fw-semibold <?php echo $team_now >= $threshold ? 'text-success' : 'text-danger'; ?>">
            <?php echo (int) $team_now; ?> / <?php echo (int) $threshold; ?>
          </span>
        </div>
        <?php if ($team_now < $threshold): ?>
          <div class="alert alert-warning py-2 small mt-2 mb-0">
            Their team has dropped below the threshold since they applied.
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><i class="bi bi-gavel"></i> Decision</div>
      <div class="card-body">
        <?php if ($application->status !== 'pending'): ?>
          <p class="text-muted small mb-0">
            This application is already <?php echo html_escape($application->status); ?>.
            <?php if ($application->agent_id): ?>
              <a href="<?php echo base_url('admin/agents/edit/'.(int) $application->agent_id); ?>">View the agent account</a>.
            <?php endif; ?>
          </p>
        <?php else: ?>
          <?php if ($admin->role === 'super_admin'): ?>
            <p class="text-muted small">
              Approving opens the agent create form prefilled from this application. The account is
              created &mdash; and this application marked approved &mdash; only when you save that form
              with a password you set yourself.
            </p>
            <?php echo form_open('admin/agent-applications/approve/'.$application->id, array('class' => 'mb-3')); ?>
              <button class="btn btn-success w-100"><i class="bi bi-check2-circle"></i> Approve and Create Agent</button>
            <?php echo form_close(); ?>
          <?php else: ?>
            <div class="alert alert-secondary py-2 small">Only a super admin can approve an application.</div>
          <?php endif; ?>

          <?php echo form_open('admin/agent-applications/reject/'.$application->id); ?>
            <div class="mb-2">
              <label class="form-label">Reason <span class="text-danger">*</span></label>
              <textarea name="admin_note" class="form-control" rows="3" maxlength="500" required></textarea>
              <div class="form-text">Sent to the applicant so they know what to fix.</div>
            </div>
            <button class="btn btn-outline-danger w-100" data-confirm="Reject this application?">
              <i class="bi bi-x-circle"></i> Reject
            </button>
          <?php echo form_close(); ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
