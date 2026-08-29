<?php
$stats = array(
	array('Balance',       $user->balance,         'wallet',           'grad-primary', 'Available to withdraw'),
	array('Total Deposit', $user->total_deposit,   'arrow-down-to-line', 'grad-info',   'Lifetime funded'),
	array('Total Earned',  $user->total_earned,    'trending-up',      'grad-success', 'Profit + commission'),
	array('Withdrawn',     $user->total_withdrawn, 'banknote',         'grad-teal',    'Paid out to you'),
);

$ring_pct = ($progress['required'] > 0)
	? min(100, round($progress['watched'] / max(1, $progress['required']) * 100))
	: 0;
?>

<div class="page-head reveal" data-reveal-order="0">
  <div>
    <h1>Dashboard</h1>
    <p class="lede">Welcome back, <?php echo html_escape($user->full_name ?: $user->username); ?>. Here is today's picture.</p>
  </div>
  <div class="d-flex gap-2">
    <a href="<?php echo base_url('transactions'); ?>" class="btn btn-ghost"><i data-lucide="receipt-text"></i> Statement</a>
    <a href="<?php echo base_url('packages'); ?>" class="btn btn-grad"><i data-lucide="plus"></i> New Plan</a>
  </div>
</div>

<!-- ============ stat tiles ============ -->
<div class="row g-3 mb-3">
  <?php foreach ($stats as $i => $s): ?>
    <?php list($label, $value, $icon, $grad, $note) = $s; ?>
    <div class="col-6 col-xl-3">
      <div class="panel lift stat h-100 reveal" data-reveal-order="<?php echo $i + 1; ?>">
        <div class="stat-top">
          <div>
            <div class="stat-label"><?php echo $label; ?></div>
            <div class="stat-value num" data-count="<?php echo (float) $value; ?>" data-count-prefix="<?php echo html_escape(currency()); ?>"><?php echo money($value); ?></div>
          </div>
          <span class="icon-tile <?php echo $grad; ?>"><i data-lucide="<?php echo $icon; ?>"></i></span>
        </div>
        <div class="stat-foot"><span><?php echo $note; ?></span></div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="row g-3">
  <div class="col-xl-8">

    <!-- ============ today's target ============ -->
    <div class="panel <?php echo $progress['complete'] ? 'is-live' : ''; ?> mb-3 reveal" data-reveal-order="5">
      <div class="panel-head">
        <i data-lucide="target"></i> Today's Target
        <span class="spacer"></span>
        <span class="text-muted small"><?php echo date('d M Y'); ?></span>
      </div>
      <div class="panel-body">
        <?php if ($progress['required'] < 1): ?>
          <div class="empty-state">
            <i data-lucide="package-open"></i>
            <p class="mb-3">No active plan yet. Buy a package to start earning daily.</p>
            <a href="<?php echo base_url('packages'); ?>" class="btn btn-grad"><i data-lucide="box"></i> Browse Packages</a>
          </div>
        <?php else: ?>
          <div class="d-flex flex-wrap align-items-center gap-4">
            <div class="ring">
              <svg viewBox="0 0 132 132" aria-hidden="true">
                <defs>
                  <linearGradient id="ringGrad" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0%"   stop-color="<?php echo $progress['complete'] ? '#10b981' : '#575beb'; ?>" />
                    <stop offset="100%" stop-color="<?php echo $progress['complete'] ? '#06b6d4' : '#8b5cf6'; ?>" />
                  </linearGradient>
                </defs>
                <circle class="ring-bg"   cx="66" cy="66" r="57" />
                <circle class="ring-fill" cx="66" cy="66" r="57" data-pct="<?php echo $ring_pct; ?>" />
              </svg>
              <div class="ring-center">
                <span class="ring-num num"><?php echo (int) $progress['watched']; ?>/<?php echo (int) $progress['required']; ?></span>
                <span class="ring-cap">ads watched</span>
              </div>
            </div>

            <div class="flex-fill" style="min-width:220px">
              <?php if ($progress['complete']): ?>
                <span class="chip chip-ok mb-2"><i data-lucide="check"></i> Target complete</span>
                <p class="mb-3"><span class="text-ok fw-bold"><?php echo money($progress['earned']); ?></span> credited to your balance today.</p>
              <?php else: ?>
                <span class="chip chip-warn mb-2"><i data-lucide="hourglass"></i> <?php echo (int) $progress['remaining']; ?> ads remaining</span>
                <p class="mb-3"><span class="fw-bold"><?php echo money($progress['pending']); ?></span> unlocks once you finish today's ads.</p>
              <?php endif; ?>

              <div class="progress mb-3"><div class="progress-bar <?php echo $progress['complete'] ? 'bg-success' : ''; ?>" data-bar="<?php echo $ring_pct; ?>"></div></div>

              <a href="<?php echo base_url('ads'); ?>" class="btn <?php echo $progress['complete'] ? 'btn-ghost' : 'btn-grad'; ?>">
                <i data-lucide="play-circle"></i> <?php echo $progress['complete'] ? 'View ads' : 'Watch ads now'; ?>
              </a>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- ============ earnings chart ============ -->
    <div class="panel mb-3 reveal" data-reveal-order="6">
      <div class="panel-head">
        <i data-lucide="chart-line"></i> Earnings
        <span class="spacer"></span>
        <span class="chip chip-mute">Last 30 days</span>
      </div>
      <div class="panel-body">
        <?php if (array_sum($earning_series['values']) <= 0): ?>
          <div class="empty-state"><i data-lucide="chart-no-axes-column"></i>No credited earnings in the last 30 days.</div>
        <?php else: ?>
          <div style="height:260px"><canvas data-chart="line" data-source="#earningSeries"></canvas></div>
          <script type="application/json" id="earningSeries"><?php echo json_encode($earning_series); ?></script>
        <?php endif; ?>
      </div>
    </div>

    <!-- ============ active plans ============ -->
    <div class="panel mb-3 reveal" data-reveal-order="7">
      <div class="panel-head">
        <i data-lucide="box"></i> Active Plans
        <span class="spacer"></span>
        <a href="<?php echo base_url('packages'); ?>">All packages</a>
      </div>
      <?php if (empty($investments)): ?>
        <div class="empty-state"><i data-lucide="inbox"></i>No active plan.</div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="table">
            <thead>
              <tr>
                <th>Plan</th><th class="text-end">Invested</th><th class="text-end">Daily</th>
                <th style="min-width:170px">Progress</th><th class="text-end">Earned</th><th>Ends</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($investments as $inv): ?>
              <?php $pct = round($inv->days_credited / max(1, $inv->duration_days) * 100); ?>
              <tr>
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <span class="dot dot-ok pulse"></span>
                    <span class="fw-semibold"><?php echo html_escape($inv->package_name); ?></span>
                  </div>
                </td>
                <td class="text-end num"><?php echo money($inv->amount); ?></td>
                <td class="text-end num text-ok"><?php echo money($inv->daily_amount); ?></td>
                <td>
                  <div class="progress mb-1"><div class="progress-bar" data-bar="<?php echo $pct; ?>"></div></div>
                  <small class="text-muted">
                    <?php echo (int) $inv->days_credited; ?>/<?php echo (int) $inv->duration_days; ?> days
                    <?php if ($inv->days_missed > 0): ?><span class="text-bad">&bull; <?php echo (int) $inv->days_missed; ?> missed</span><?php endif; ?>
                  </small>
                </td>
                <td class="text-end num fw-semibold"><?php echo money($inv->total_earned); ?></td>
                <td class="small text-muted text-nowrap"><?php echo fmt_date($inv->end_date, 'd M Y'); ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <!-- ============ recent activity ============ -->
    <div class="panel reveal" data-reveal-order="8">
      <div class="panel-head">
        <i data-lucide="activity"></i> Recent Activity
        <span class="spacer"></span>
        <a href="<?php echo base_url('transactions'); ?>">View all</a>
      </div>
      <?php if (empty($recent_tx)): ?>
        <div class="empty-state"><i data-lucide="receipt"></i>No transactions yet.</div>
      <?php else: ?>
        <div class="feed">
          <?php foreach ($recent_tx as $t): ?>
            <?php $up = $t->amount >= 0; ?>
            <div class="feed-item">
              <span class="icon-tile sm <?php echo $up ? 'grad-success' : 'grad-danger'; ?>">
                <i data-lucide="<?php echo $up ? 'arrow-down-left' : 'arrow-up-right'; ?>"></i>
              </span>
              <div class="feed-main">
                <div class="feed-title"><?php echo html_escape(tx_label($t->type)); ?></div>
                <div class="feed-sub text-truncate"><?php echo html_escape($t->description); ?> &middot; <?php echo fmt_date($t->created_at, 'd M, H:i'); ?></div>
              </div>
              <div class="text-end">
                <div class="feed-amount <?php echo $up ? 'text-ok' : 'text-bad'; ?>"><?php echo ($up ? '+' : '-').money(abs($t->amount)); ?></div>
                <div class="feed-sub num"><?php echo money($t->balance_after); ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- ============ right rail ============ -->
  <div class="col-xl-4">

    <?php if ( ! empty($plan_split['values'])): ?>
      <div class="panel mb-3 reveal" data-reveal-order="6">
        <div class="panel-head"><i data-lucide="chart-pie"></i> Plan Allocation</div>
        <div class="panel-body">
          <div style="height:240px"><canvas data-chart="doughnut" data-source="#planSplit"></canvas></div>
          <script type="application/json" id="planSplit"><?php echo json_encode($plan_split); ?></script>
        </div>
      </div>
    <?php endif; ?>

    <div class="panel mb-3 reveal" data-reveal-order="7">
      <div class="panel-head"><i data-lucide="users"></i> Referral</div>
      <div class="panel-body">
        <div class="d-flex justify-content-between mb-2"><span class="text-muted">Your ID</span><strong class="mono"><?php echo html_escape($user->referral_code); ?></strong></div>
        <div class="d-flex justify-content-between mb-2"><span class="text-muted">Referrals</span><strong class="num"><?php echo (int) $referral_count; ?></strong></div>
        <div class="d-flex justify-content-between mb-3"><span class="text-muted">Commission</span><strong class="num text-ok"><?php echo money($referral_total); ?></strong></div>
        <div class="copy-field">
          <input type="text" class="form-control form-control-sm" id="refLink" readonly value="<?php echo base_url('register/'.$user->referral_code); ?>">
          <button class="btn btn-ghost" type="button" data-copy-target="#refLink" aria-label="Copy referral link"><i data-lucide="copy"></i></button>
        </div>
        <a href="<?php echo base_url('referral'); ?>" class="btn btn-ghost w-100 mt-3"><i data-lucide="arrow-right"></i> Referral details</a>
      </div>
    </div>

    <div class="panel mb-3 reveal" data-reveal-order="8">
      <div class="panel-head"><i data-lucide="calendar-days"></i> Last 7 Days</div>
      <?php if (empty($recent_days['rows'])): ?>
        <div class="empty-state"><i data-lucide="calendar-x"></i>Nothing yet.</div>
      <?php else: ?>
        <div class="feed">
          <?php foreach ($recent_days['rows'] as $d): ?>
            <div class="feed-item">
              <div class="feed-main">
                <div class="feed-title"><?php echo fmt_date($d->earn_date, 'd M Y'); ?></div>
                <div class="feed-sub"><?php echo (int) $d->ads_watched; ?>/<?php echo (int) $d->ads_required; ?> ads</div>
              </div>
              <div class="text-end">
                <div class="feed-amount num"><?php echo money($d->amount); ?></div>
                <?php echo chip($d->status); ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="panel reveal" data-reveal-order="8">
      <div class="panel-head">
        <i data-lucide="megaphone"></i> Notices
        <span class="spacer"></span>
        <a href="<?php echo base_url('notices'); ?>">All</a>
      </div>
      <?php if (empty($notices)): ?>
        <div class="empty-state"><i data-lucide="inbox"></i>No notices.</div>
      <?php else: ?>
        <div class="feed">
          <?php foreach ($notices as $n): ?>
            <a class="feed-item" href="<?php echo base_url('notices/'.$n->slug); ?>">
              <span class="icon-tile sm grad-info"><i data-lucide="megaphone"></i></span>
              <div class="feed-main">
                <div class="feed-title text-truncate"><?php echo html_escape($n->title); ?></div>
                <div class="feed-sub"><?php echo fmt_date($n->published_at, 'd M Y'); ?></div>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
