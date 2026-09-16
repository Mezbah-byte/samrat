<?php
$on      = ! empty($float['on']);
$queue   = (int) (isset($agent_stats['req_deposits']) ? $agent_stats['req_deposits'] : 0)
         + (int) (isset($agent_stats['req_withdrawals']) ? $agent_stats['req_withdrawals'] : 0);
$timeout = (int) setting('agent_accept_timeout_hours', 6);

$tiles = $on
	? array(
		array('Deposit Float',   $float['balances']['deposit'],    'coins',      'grad-primary', 'Spendable on user deposits', 'agent/float'),
		array('Collected',       $float['balances']['withdraw'],   'piggy-bank', 'grad-teal',    'From withdrawals you paid',  'agent/payouts'),
		array('Commission',      $float['balances']['commission'], 'sparkles',   'grad-success', 'Cash out or top up float',   'agent/payouts'),
		array('Earned This Month', $earned_month,                  'trending-up','grad-info',    'Across every source',        'agent/earnings'),
	)
	: array(
		array('Team Members',     $team['total'],  'users',       'grad-primary', 'Everyone below you',        'agent/team'),
		array('Active Members',   $team['active'], 'user-check',  'grad-success', 'Accounts in good standing', 'agent/team'),
		array('Earned This Month', $earned_month,  'trending-up', 'grad-info',    'Team commission',           'agent/earnings'),
		array('Total Commission', $earned_total,   'coins',       'grad-teal',    'Lifetime',                  'agent/earnings'),
	);
?>

<div class="page-head reveal" data-reveal-order="0">
  <div>
    <h1>Dashboard</h1>
    <p class="lede">
      Welcome back, <?php echo html_escape($agent->name ?: $agent->username); ?>.
      <?php if ($on && $queue > 0): ?>
        <span class="text-warn fw-semibold"><?php echo $queue; ?> request<?php echo $queue > 1 ? 's' : ''; ?></span> waiting on you.
      <?php elseif ($on): ?>
        Nothing in your queue right now.
      <?php else: ?>
        Here is your team at a glance.
      <?php endif; ?>
    </p>
  </div>
  <?php if ($on): ?>
    <div class="d-flex gap-2">
      <a href="<?php echo base_url('agent/payouts'); ?>" class="btn btn-ghost"><i data-lucide="banknote"></i> Cash Out</a>
      <a href="<?php echo base_url('agent/float/create'); ?>" class="btn btn-grad"><i data-lucide="plus"></i> Buy Float</a>
    </div>
  <?php endif; ?>
</div>

<?php if ( ! $agent->user_id): ?>
  <div class="panel mb-3 reveal" data-reveal-order="1">
    <div class="panel-body d-flex gap-3 align-items-start">
      <span class="icon-tile grad-warning"><i data-lucide="link-2-off"></i></span>
      <div>
        <div class="fw-semibold mb-1">No linked user account</div>
        <p class="text-muted small mb-0">
          This agent is not linked to a user, so it has no team. Team screens stay empty and no
          team commission accrues<?php echo $on ? ' - the float system still works normally' : ''; ?>.
          Ask an admin to link an active account.
        </p>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php if ($on && empty($float['wallet_count'])): ?>
  <div class="panel mb-3 reveal" data-reveal-order="1">
    <div class="panel-body d-flex flex-wrap gap-3 align-items-center">
      <span class="icon-tile grad-danger"><i data-lucide="wallet"></i></span>
      <div class="flex-fill">
        <div class="fw-semibold mb-1">You have no active receive wallet</div>
        <p class="text-muted small mb-0">Users cannot select you for a deposit until you publish at least one address.</p>
      </div>
      <a href="<?php echo base_url('agent/wallets/create'); ?>" class="btn btn-grad"><i data-lucide="plus"></i> Add Wallet</a>
    </div>
  </div>
<?php endif; ?>

<!-- ============ headline tiles ============ -->
<div class="row g-3 mb-3">
  <?php foreach ($tiles as $i => $t): ?>
    <?php list($label, $value, $icon, $grad, $note, $link) = $t; ?>
    <div class="col-6 col-xl-3">
      <a href="<?php echo base_url($link); ?>" class="text-decoration-none d-block h-100">
        <div class="panel lift stat h-100 reveal" data-reveal-order="<?php echo $i + 2; ?>">
          <div class="stat-top">
            <div>
              <div class="stat-label"><?php echo $label; ?></div>
              <div class="stat-value num"
                   <?php if ($label === 'Team Members' || $label === 'Active Members'): ?>
                     data-count="<?php echo (int) $value; ?>"><?php echo (int) $value; ?>
                   <?php else: ?>
                     data-count="<?php echo (float) $value; ?>" data-count-prefix="<?php echo html_escape(currency()); ?>"><?php echo money($value); ?>
                   <?php endif; ?>
              </div>
            </div>
            <span class="icon-tile <?php echo $grad; ?>"><i data-lucide="<?php echo $icon; ?>"></i></span>
          </div>
          <div class="stat-foot"><span><?php echo $note; ?></span><i data-lucide="arrow-right"></i></div>
        </div>
      </a>
    </div>
  <?php endforeach; ?>
</div>

<?php if ($on): ?>
  <!-- ============ work queue + ledger ============ -->
  <div class="row g-3 mb-3">
    <div class="col-xl-5">
      <div class="panel h-100 <?php echo $queue > 0 ? 'is-live' : ''; ?> reveal" data-reveal-order="6">
        <div class="panel-head"><i data-lucide="list-checks"></i> Your Queue</div>
        <div class="panel-body">
          <div class="tile mb-2">
            <div class="tile-row">
              <span class="d-flex align-items-center gap-2">
                <span class="icon-tile sm grad-primary"><i data-lucide="arrow-down-to-line"></i></span>
                Deposits to accept
              </span>
              <strong class="num <?php echo ! empty($agent_stats['req_deposits']) ? 'text-warn' : 'text-dim'; ?>">
                <?php echo (int) $agent_stats['req_deposits']; ?>
              </strong>
            </div>
            <div class="tile-row">
              <span class="d-flex align-items-center gap-2">
                <span class="icon-tile sm grad-teal"><i data-lucide="arrow-up-from-line"></i></span>
                Withdrawals to pay
              </span>
              <strong class="num <?php echo ! empty($agent_stats['req_withdrawals']) ? 'text-warn' : 'text-dim'; ?>">
                <?php echo (int) $agent_stats['req_withdrawals']; ?>
              </strong>
            </div>
          </div>

          <p class="small text-muted mb-3">
            <i data-lucide="clock"></i>
            Anything left <?php echo $timeout; ?>h escalates to an admin automatically.
          </p>

          <div class="d-flex gap-2">
            <a href="<?php echo base_url('agent/requests/deposits'); ?>" class="btn btn-quiet flex-fill">Deposits</a>
            <a href="<?php echo base_url('agent/requests/withdrawals'); ?>" class="btn btn-quiet flex-fill">Withdrawals</a>
          </div>

          <div class="stat-foot mt-3">
            <span>Settled lifetime</span>
            <strong class="num"><?php echo money($float['settled']); ?></strong>
          </div>
          <div class="stat-foot" style="margin-top:.4rem;padding-top:.4rem">
            <span>Paid out lifetime</span>
            <strong class="num"><?php echo money($float['collected']); ?></strong>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-7">
      <div class="panel h-100 reveal" data-reveal-order="7">
        <div class="panel-head">
          <i data-lucide="activity"></i> Wallet Activity
          <span class="spacer"></span>
          <a href="<?php echo base_url('agent/ledger'); ?>">Full ledger</a>
        </div>
        <?php if (empty($float['recent'])): ?>
          <div class="empty-state">
            <i data-lucide="receipt-text"></i>
            <p class="mb-3">No movements yet. Buy float to get started.</p>
            <a href="<?php echo base_url('agent/float/create'); ?>" class="btn btn-grad"><i data-lucide="coins"></i> Buy Float</a>
          </div>
        <?php else: ?>
          <div class="feed">
            <?php foreach ($float['recent'] as $t): ?>
              <?php $neg = (float) $t->amount < 0; ?>
              <div class="feed-item">
                <span class="icon-tile sm <?php echo $neg ? 'grad-danger' : 'grad-success'; ?>">
                  <i data-lucide="<?php echo $neg ? 'arrow-up-right' : 'arrow-down-left'; ?>"></i>
                </span>
                <div class="feed-main">
                  <div class="feed-title"><?php echo agent_tx_label($t->type); ?></div>
                  <div class="feed-sub">
                    <?php echo fmt_date($t->created_at, 'd M, H:i'); ?> &middot; <?php echo agent_wallet_label($t->wallet); ?>
                  </div>
                </div>
                <strong class="num <?php echo $neg ? 'text-bad' : 'text-ok'; ?>">
                  <?php echo ($neg ? '-' : '+').money(abs((float) $t->amount)); ?>
                </strong>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
<?php endif; ?>

<!-- ============ team ============ -->
<div class="row g-3 mb-3">
  <div class="col-xl-4">
    <div class="panel h-100 reveal" data-reveal-order="8">
      <div class="panel-head">
        <i data-lucide="users"></i> Team
        <span class="spacer"></span>
        <a href="<?php echo base_url('agent/team'); ?>">All</a>
      </div>
      <div class="panel-body">
        <div class="tile">
          <div class="tile-row"><span class="text-muted">Active</span><strong class="num text-ok"><?php echo (int) $team['active']; ?></strong></div>
          <div class="tile-row"><span class="text-muted">Pending</span><strong class="num"><?php echo (int) $team['pending']; ?></strong></div>
          <div class="tile-row"><span class="text-muted">Blocked</span><strong class="num text-bad"><?php echo (int) $team['blocked']; ?></strong></div>
          <div class="tile-row"><span class="text-muted">Joined last 30 days</span><strong class="num"><?php echo (int) $team['joined_30d']; ?></strong></div>
        </div>
        <div class="stat-foot">
          <span>Team deposits</span>
          <strong class="num"><?php echo money($team['total_deposit']); ?></strong>
        </div>
      </div>
    </div>
  </div>

  <div class="col-xl-4">
    <div class="panel h-100 reveal" data-reveal-order="9">
      <div class="panel-head"><i data-lucide="clipboard-check"></i> Awaiting Your Review</div>
      <div class="panel-body">
        <div class="tile mb-3">
          <div class="tile-row"><span class="text-muted">Team deposits</span><strong class="num"><?php echo (int) $deposits['awaiting_review']; ?></strong></div>
          <div class="tile-row"><span class="text-muted">Team withdrawals</span><strong class="num"><?php echo (int) $withdrawals['awaiting_review']; ?></strong></div>
        </div>
        <p class="small text-muted mb-0">
          Your recommendation is advisory. An admin makes the final call and moves the money on
          these&nbsp;&mdash; separate from the requests routed to you directly.
        </p>
      </div>
    </div>
  </div>

  <div class="col-xl-4">
    <div class="panel h-100 reveal" data-reveal-order="10">
      <div class="panel-head">
        <i data-lucide="coins"></i> Commission Sources
        <span class="spacer"></span>
        <a href="<?php echo base_url('agent/earnings'); ?>">History</a>
      </div>
      <div class="panel-body">
        <div class="tile">
          <div class="tile-row">
            <span class="text-muted">Team deposits <span class="text-dim">(<?php echo (int) $by_source['deposit']['deals']; ?>)</span></span>
            <strong class="num"><?php echo money($by_source['deposit']['earned']); ?></strong>
          </div>
          <div class="tile-row">
            <span class="text-muted">Daily profit <span class="text-dim">(<?php echo (int) $by_source['daily_profit']['deals']; ?>)</span></span>
            <strong class="num"><?php echo money($by_source['daily_profit']['earned']); ?></strong>
          </div>
          <?php if ($on): ?>
            <div class="tile-row">
              <span class="text-muted">Deposits settled <span class="text-dim">(<?php echo (int) $by_source['agent_deposit']['deals']; ?>)</span></span>
              <strong class="num"><?php echo money($by_source['agent_deposit']['earned']); ?></strong>
            </div>
            <div class="tile-row">
              <span class="text-muted">Withdrawals paid <span class="text-dim">(<?php echo (int) $by_source['agent_withdraw']['deals']; ?>)</span></span>
              <strong class="num"><?php echo money($by_source['agent_withdraw']['earned']); ?></strong>
            </div>
          <?php endif; ?>
        </div>
        <?php if ($unsettled > 0 && ! $agent->user_id): ?>
          <div class="stat-foot">
            <span>Unsettled</span>
            <strong class="num text-warn"><?php echo money($unsettled); ?></strong>
          </div>
          <p class="small text-muted mb-0 mt-2">Paid out by hand by an admin.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- ============ pending team work ============ -->
<div class="row g-3">
  <div class="col-xl-6">
    <div class="panel h-100 reveal" data-reveal-order="11">
      <div class="panel-head">
        <i data-lucide="inbox"></i> Pending Team Deposits
        <span class="spacer"></span>
        <a href="<?php echo base_url('agent/deposits?status=pending'); ?>">All</a>
      </div>
      <?php if (empty($pending_deps)): ?>
        <div class="empty-state"><i data-lucide="inbox"></i>Nothing pending.</div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="table">
            <thead><tr><th>#</th><th>User</th><th class="text-end">Amount</th><th>Reviewed</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($pending_deps as $d): ?>
              <tr>
                <td class="text-dim">#<?php echo (int) $d->id; ?></td>
                <td class="fw-semibold"><?php echo html_escape($d->username); ?></td>
                <td class="text-end num"><?php echo money($d->amount); ?></td>
                <td><?php echo $d->agent_recommendation
                      ? chip($d->agent_recommendation === 'approve' ? 'approved' : 'rejected')
                      : '<span class="text-dim">&mdash;</span>'; ?></td>
                <td class="text-end"><a href="<?php echo base_url('agent/deposits/view/'.$d->id); ?>" class="btn btn-ghost btn-icon"><i data-lucide="eye"></i></a></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-xl-6">
    <div class="panel h-100 reveal" data-reveal-order="12">
      <div class="panel-head">
        <i data-lucide="hand-coins"></i> Pending Team Withdrawals
        <span class="spacer"></span>
        <a href="<?php echo base_url('agent/withdrawals?status=pending'); ?>">All</a>
      </div>
      <?php if (empty($pending_wds)): ?>
        <div class="empty-state"><i data-lucide="inbox"></i>Nothing pending.</div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="table">
            <thead><tr><th>#</th><th>User</th><th class="text-end">Net</th><th>Reviewed</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($pending_wds as $w): ?>
              <tr>
                <td class="text-dim">#<?php echo (int) $w->id; ?></td>
                <td class="fw-semibold"><?php echo html_escape($w->username); ?></td>
                <td class="text-end num"><?php echo money($w->net_amount); ?></td>
                <td><?php echo $w->agent_recommendation
                      ? chip($w->agent_recommendation === 'approve' ? 'approved' : 'rejected')
                      : '<span class="text-dim">&mdash;</span>'; ?></td>
                <td class="text-end"><a href="<?php echo base_url('agent/withdrawals/view/'.$w->id); ?>" class="btn btn-ghost btn-icon"><i data-lucide="eye"></i></a></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="panel mt-3 reveal" data-reveal-order="13">
  <div class="panel-head">
    <i data-lucide="user-plus"></i> Newest Team Members
    <span class="spacer"></span>
    <a href="<?php echo base_url('agent/team'); ?>">All members</a>
  </div>
  <?php if (empty($recent_members)): ?>
    <div class="empty-state"><i data-lucide="users"></i>No team members yet.</div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>#</th><th>Name</th><th>Username</th><th>Status</th><th class="text-end">Deposited</th><th>Joined</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($recent_members as $m): ?>
          <tr>
            <td class="text-dim">#<?php echo (int) $m->id; ?></td>
            <td class="fw-semibold"><?php echo html_escape($m->full_name); ?></td>
            <td class="text-muted"><?php echo html_escape($m->username); ?></td>
            <td><?php echo chip($m->status); ?></td>
            <td class="text-end num"><?php echo money($m->total_deposit); ?></td>
            <td class="text-muted text-nowrap"><?php echo fmt_date($m->created_at, 'd M Y'); ?></td>
            <td class="text-end"><a href="<?php echo base_url('agent/team/view/'.$m->id); ?>" class="btn btn-ghost btn-icon"><i data-lucide="eye"></i></a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
