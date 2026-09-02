<?php
/* Generations come from referral_levels, so the rates shown here are whatever
 * the admin has set - never a hard-coded number. */
$paying    = array_values(array_filter($ladder, function ($g) { return $g->status === 'active' && (float) $g->percent > 0; }));
$direct    = 0;
$team_size = array_sum($gen_counts);

foreach ($ladder as $g)
{
	if ((int) $g->level === 1 && $g->status === 'active')
	{
		$direct = (float) $g->percent;
	}
}

$tiles = array(
	array('Direct Referrals',  (int) $total_count,     'users',       'grad-primary', 0),
	array('Team Size',         (int) $team_size,       'network',     'grad-info',    0),
	array('Commission Earned', (float) $earned_total,  'hand-coins',  'grad-success', 2),
	array('Direct Rate',       (float) $direct,        'percent',     'grad-warning', 0),
);
?>

<div class="page-head reveal" data-reveal-order="0">
  <div>
    <h1>Referral</h1>
    <p class="lede">
      <?php if (count($paying) > 1): ?>
        Earn on <?php echo count($paying); ?> generations of your team &mdash; <?php echo $direct; ?>% from
        the people you invite directly, and more from the people they invite. Paid once, when a deposit
        is approved.
      <?php else: ?>
        Earn <?php echo $direct; ?>% of a direct referral's deposit, paid once on approval.
      <?php endif; ?>
    </p>
  </div>
</div>

<div class="row g-3 mb-3">
  <?php foreach ($tiles as $i => $t): ?>
    <?php list($label, $value, $icon, $grad, $dp) = $t; ?>
    <div class="col-sm-6 col-xl-3">
      <div class="panel lift stat h-100 reveal" data-reveal-order="<?php echo $i + 1; ?>">
        <div class="stat-top">
          <div>
            <div class="stat-label"><?php echo $label; ?></div>
            <div class="stat-value num"
                 data-count="<?php echo $value; ?>"
                 data-count-decimals="<?php echo $dp; ?>"
                 data-count-prefix="<?php echo $dp ? html_escape(currency()) : ''; ?>"
                 data-count-suffix="<?php echo $label === 'Direct Rate' ? '%' : ''; ?>">
              <?php echo $dp ? money($value) : $value.($label === 'Direct Rate' ? '%' : ''); ?>
            </div>
          </div>
          <span class="icon-tile <?php echo $grad; ?>"><i data-lucide="<?php echo $icon; ?>"></i></span>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="panel mb-3 reveal" data-reveal-order="4">
  <div class="panel-head"><i data-lucide="share-2"></i> Your Referral Link</div>
  <div class="panel-body">
    <div class="row g-3">
      <div class="col-md-4">
        <label class="form-label">Referral ID</label>
        <div class="copy-field">
          <input type="text" class="form-control" id="refCode" readonly value="<?php echo html_escape($user->referral_code); ?>">
          <button class="btn btn-ghost" type="button" data-copy-target="#refCode" aria-label="Copy referral ID"><i data-lucide="copy"></i></button>
        </div>
      </div>
      <div class="col-md-8">
        <label class="form-label">Referral Link</label>
        <div class="copy-field">
          <input type="text" class="form-control" id="refLink" readonly value="<?php echo html_escape($referral_link); ?>">
          <button class="btn btn-ghost" type="button" data-copy-target="#refLink" aria-label="Copy referral link"><i data-lucide="copy"></i></button>
        </div>
      </div>
    </div>
  </div>
</div>

<?php if ($agent_enabled): ?>
  <?php
  $agent_pct  = $agent_threshold > 0 ? min(100, ($agent_team_active / $agent_threshold) * 100) : 100;
  $agent_open = $agent_application && in_array($agent_application->status, array('pending', 'approved'), TRUE);
  ?>
  <div class="panel mb-3 reveal" data-reveal-order="5">
    <div class="panel-head"><i data-lucide="badge-check"></i> Agentship</div>
    <div class="panel-body">

      <?php if ($agent_application && $agent_application->status === 'approved'): ?>
        <p class="mb-2">
          <?php echo chip('approved'); ?>
          You are an agent. Sign in to your panel with the credentials the admin gave you.
        </p>
        <a href="<?php echo base_url('agent/login'); ?>" class="btn btn-grad"><i data-lucide="log-in"></i> Agent Panel</a>

      <?php elseif ($agent_application && $agent_application->status === 'pending'): ?>
        <p class="mb-2">
          <?php echo chip('pending'); ?>
          Your application is with the admin. You will be notified when it is reviewed.
        </p>
        <a href="<?php echo base_url('agentship'); ?>" class="btn btn-quiet">View application</a>

      <?php else: ?>
        <p class="text-muted mb-3">
          Grow a team of <strong><?php echo (int) $agent_threshold; ?></strong> active accounts and you can
          apply to become an agent &mdash; your own panel, your own team screens, and commission on
          everything your team deposits and earns.
          <?php if ($agent_application && $agent_application->status === 'rejected'): ?>
            <br><?php echo chip('rejected'); ?>
            Your last application was declined<?php echo $agent_application->admin_note
              ? ': '.html_escape($agent_application->admin_note) : '.'; ?>
            You can apply again.
          <?php endif; ?>
        </p>

        <div class="d-flex justify-content-between small mb-1">
          <span class="text-muted">Active team members</span>
          <span class="fw-semibold"><?php echo (int) $agent_team_active; ?> / <?php echo (int) $agent_threshold; ?></span>
        </div>
        <div class="progress mb-3" style="height:8px">
          <div class="progress-bar" role="progressbar" style="width: <?php echo $agent_pct; ?>%"
               aria-valuenow="<?php echo (int) $agent_team_active; ?>" aria-valuemin="0"
               aria-valuemax="<?php echo (int) $agent_threshold; ?>"></div>
        </div>

        <?php if ($agent_team_active >= $agent_threshold && ! $agent_open): ?>
          <a href="<?php echo base_url('agentship/apply'); ?>" class="btn btn-grad">
            <i data-lucide="badge-check"></i> Apply for Agentship
          </a>
        <?php else: ?>
          <button class="btn btn-quiet" disabled>
            <?php echo (int) max(0, $agent_threshold - $agent_team_active); ?> more active members to go
          </button>
        <?php endif; ?>
      <?php endif; ?>

    </div>
  </div>
<?php endif; ?>

<div class="panel mb-3 reveal" data-reveal-order="6">
  <div class="panel-head"><i data-lucide="network"></i> Your Generations</div>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Generation</th>
          <th>Who counts</th>
          <th class="text-center">Rate</th>
          <th class="text-end">People</th>
          <th class="text-end">Earned</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($ladder as $g): ?>
        <?php
        $lvl  = (int) $g->level;
        $off  = $g->status !== 'active' || (float) $g->percent <= 0;
        $mine = isset($earned_levels[$lvl]) ? $earned_levels[$lvl] : array('deals' => 0, 'earned' => 0);
        ?>
        <tr<?php echo $off ? ' class="text-muted"' : ''; ?>>
          <td class="fw-semibold">G<?php echo $lvl; ?></td>
          <td class="small text-muted">
            <?php echo $lvl === 1 ? 'People who join with your ID' : 'People invited by your G'.($lvl - 1); ?>
          </td>
          <td class="text-center"><?php echo $off ? '&mdash;' : percent($g->percent); ?></td>
          <td class="text-end num"><?php echo (int) (isset($gen_counts[$lvl]) ? $gen_counts[$lvl] : 0); ?></td>
          <td class="text-end num fw-semibold <?php echo $mine['earned'] > 0 ? 'text-ok' : ''; ?>">
            <?php echo money($mine['earned']); ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="panel-foot">
    <span class="small text-muted">Total across every generation: <?php echo rtrim(rtrim(number_format($total_pct, 2), '0'), '.'); ?>% of each approved deposit.</span>
  </div>
</div>

<div class="row g-3">
  <div class="col-xl-6">
    <div class="panel h-100 reveal" data-reveal-order="7">
      <div class="panel-head"><i data-lucide="users"></i> My Referrals</div>
      <?php if (empty($downline)): ?>
        <div class="empty-state"><i data-lucide="user-plus"></i>No one has signed up with your ID yet.</div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="table">
            <thead><tr><th>User</th><th>Country</th><th class="text-end">Deposited</th><th>Status</th><th>Joined</th></tr></thead>
            <tbody>
            <?php foreach ($downline as $d): ?>
              <tr>
                <td>
                  <div class="fw-semibold"><?php echo html_escape($d->username); ?></div>
                  <div class="small text-muted"><?php echo html_escape($d->full_name); ?></div>
                </td>
                <td class="small"><?php echo html_escape($d->country); ?></td>
                <td class="text-end num fw-semibold"><?php echo money($d->total_deposit); ?></td>
                <td><?php echo chip($d->status); ?></td>
                <td class="small text-muted text-nowrap"><?php echo fmt_date($d->created_at, 'd M y'); ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-xl-6">
    <div class="panel h-100 reveal" data-reveal-order="8">
      <div class="panel-head"><i data-lucide="hand-coins"></i> Commission History</div>
      <?php if (empty($rows)): ?>
        <div class="empty-state"><i data-lucide="receipt"></i>No commission earned yet.</div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="table">
            <thead><tr><th>From</th><th class="text-center">Gen</th><th class="text-center">Rate</th><th class="text-end">Amount</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $c): ?>
              <tr>
                <td class="fw-semibold"><?php echo html_escape($c->referred_username); ?></td>
                <td class="text-center small fw-semibold">G<?php echo (int) $c->level; ?></td>
                <td class="text-center small"><?php echo percent($c->percent); ?></td>
                <td class="text-end num fw-semibold text-ok">+<?php echo money($c->amount); ?></td>
                <td class="small text-muted text-nowrap"><?php echo fmt_date($c->created_at, 'd M, H:i'); ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="panel-foot">
          <span><?php echo (int) $total; ?> total</span>
          <?php echo pager(base_url('referral'), $total, $per_page, $page); ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
