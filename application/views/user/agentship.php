<?php
$pct    = $threshold > 0 ? min(100, ($team_active / $threshold) * 100) : 100;
$status = $application ? $application->status : '';
?>

<div class="page-head reveal" data-reveal-order="0">
  <div>
    <h1>Agentship</h1>
    <p class="lede">
      Agents get their own panel: their team at a glance, review of their team's deposits and
      withdrawals, and commission on everything that team deposits and earns.
    </p>
  </div>
</div>

<div class="row g-3 mb-3">
  <div class="col-sm-6">
    <div class="panel lift stat h-100 reveal" data-reveal-order="1">
      <div class="stat-top">
        <div>
          <div class="stat-label">Active Team Members</div>
          <div class="stat-value num"><?php echo (int) $team_active; ?></div>
        </div>
        <span class="icon-tile grad-primary"><i data-lucide="users"></i></span>
      </div>
    </div>
  </div>
  <div class="col-sm-6">
    <div class="panel lift stat h-100 reveal" data-reveal-order="2">
      <div class="stat-top">
        <div>
          <div class="stat-label">Required to Apply</div>
          <div class="stat-value num"><?php echo (int) $threshold; ?></div>
        </div>
        <span class="icon-tile grad-warning"><i data-lucide="target"></i></span>
      </div>
    </div>
  </div>
</div>

<div class="panel mb-3 reveal" data-reveal-order="3">
  <div class="panel-head"><i data-lucide="badge-check"></i> Your Status</div>
  <div class="panel-body">

    <?php if ($status === 'approved'): ?>
      <p class="mb-3"><?php echo chip('approved'); ?> Your application was approved on <?php echo fmt_date($application->reviewed_at); ?>.</p>
      <p class="text-muted">
        Your agent username is <strong><?php echo html_escape($application->username); ?></strong>.
        The admin passes you the password directly &mdash; it is never emailed.
      </p>
      <a href="<?php echo base_url('agent/login'); ?>" class="btn btn-grad"><i data-lucide="log-in"></i> Go to Agent Panel</a>

    <?php elseif ($status === 'pending'): ?>
      <p class="mb-3"><?php echo chip('pending'); ?> Submitted on <?php echo fmt_date($application->created_at); ?>.</p>
      <p class="text-muted mb-0">
        An admin is reviewing your documents. You will get a notification as soon as there is a decision.
        You cannot submit another application while this one is open.
      </p>

    <?php else: ?>

      <?php if ($status === 'rejected'): ?>
        <p class="mb-3"><?php echo chip('rejected'); ?> Declined on <?php echo fmt_date($application->reviewed_at); ?>.</p>
        <?php if ($application->admin_note): ?>
          <p class="text-muted">Reason: <?php echo html_escape($application->admin_note); ?></p>
        <?php endif; ?>
        <p class="text-muted">You are welcome to fix what was wrong and apply again.</p>
      <?php endif; ?>

      <div class="d-flex justify-content-between small mb-1">
        <span class="text-muted">Progress</span>
        <span class="fw-semibold"><?php echo (int) $team_active; ?> / <?php echo (int) $threshold; ?></span>
      </div>
      <div class="progress mb-3" style="height:8px">
        <div class="progress-bar" role="progressbar" style="width: <?php echo $pct; ?>%"
             aria-valuenow="<?php echo (int) $team_active; ?>" aria-valuemin="0"
             aria-valuemax="<?php echo (int) $threshold; ?>"></div>
      </div>

      <?php if ($team_active >= $threshold && ! $open): ?>
        <a href="<?php echo base_url('agentship/apply'); ?>" class="btn btn-grad">
          <i data-lucide="badge-check"></i> Apply for Agentship
        </a>
      <?php else: ?>
        <button class="btn btn-quiet" disabled>
          <?php echo (int) max(0, $threshold - $team_active); ?> more active members to go
        </button>
        <p class="text-muted small mt-2 mb-0">
          Your team is every account below you across all generations. Only accounts with an
          active status count toward the total.
        </p>
      <?php endif; ?>

    <?php endif; ?>

  </div>
</div>

<div class="panel reveal" data-reveal-order="4">
  <div class="panel-head"><i data-lucide="list-checks"></i> What an Agent Can Do</div>
  <div class="panel-body">
    <ul class="mb-0 text-muted">
      <li>Sign in to a separate agent panel with its own username and password.</li>
      <li>See every member of their team, and each member's plans, deposits and withdrawals.</li>
      <li>Review their team's pending deposits and withdrawals and recommend approve or reject.
          The admin still makes every final decision and moves the money.</li>
      <li>Earn a percentage of every deposit their team makes and every daily profit their team earns.</li>
    </ul>
  </div>
</div>

<p class="text-muted small mt-3">
  <a href="<?php echo base_url('referral'); ?>"><i data-lucide="arrow-left"></i> Back to referral</a>
</p>
