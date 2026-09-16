<div class="page-head reveal" data-reveal-order="0">
  <div>
    <h1><?php echo html_escape($member->full_name); ?></h1>
    <p class="lede"><?php echo html_escape($member->username); ?> &middot; joined <?php echo fmt_date($member->created_at, 'd M Y'); ?></p>
  </div>
  <div class="d-flex gap-2 align-items-center">
    <?php echo chip($member->status); ?>
    <a href="<?php echo base_url('agent/team'); ?>" class="btn btn-ghost"><i data-lucide="arrow-left"></i> All members</a>
  </div>
</div>

<div class="row g-3">
  <div class="col-xl-4">
    <div class="panel mb-3 reveal" data-reveal-order="1">
      <div class="panel-head"><i data-lucide="user"></i> Profile</div>
      <div class="panel-body">
        <div class="tile">
          <div class="tile-row"><span class="text-muted">Username</span><strong><?php echo html_escape($member->username); ?></strong></div>
          <div class="tile-row"><span class="text-muted">Email</span><span class="text-break small"><?php echo html_escape($member->email); ?></span></div>
          <div class="tile-row"><span class="text-muted">Country</span><span><?php echo html_escape($member->country ?: '-'); ?></span></div>
          <div class="tile-row"><span class="text-muted">Referral code</span><span class="mono"><?php echo html_escape($member->referral_code); ?></span></div>
          <div class="tile-row"><span class="text-muted">Direct referrals</span><strong class="num"><?php echo (int) $direct; ?></strong></div>
          <div class="tile-row"><span class="text-muted">Last login</span><span class="text-muted small"><?php echo fmt_date($member->last_login_at); ?></span></div>
        </div>
      </div>
    </div>

    <div class="panel reveal" data-reveal-order="2">
      <div class="panel-head"><i data-lucide="wallet"></i> Money</div>
      <div class="panel-body">
        <div class="tile">
          <div class="tile-row"><span class="text-muted">Balance</span><strong class="num text-ok"><?php echo money($member->balance); ?></strong></div>
          <div class="tile-row"><span class="text-muted">Total deposited</span><strong class="num"><?php echo money($member->total_deposit); ?></strong></div>
          <div class="tile-row"><span class="text-muted">Total earned</span><strong class="num"><?php echo money($member->total_earned); ?></strong></div>
          <div class="tile-row"><span class="text-muted">Total withdrawn</span><strong class="num"><?php echo money($member->total_withdrawn); ?></strong></div>
        </div>
      </div>
      <div class="panel-foot">
        <span class="small text-muted"><i data-lucide="eye"></i> Read-only. Adjusting a balance is an admin action.</span>
      </div>
    </div>
  </div>

  <div class="col-xl-8">
    <div class="panel mb-3 reveal" data-reveal-order="3">
      <div class="panel-head"><i data-lucide="trending-up"></i> Active Plans</div>
      <?php if (empty($investments)): ?>
        <div class="empty-state"><i data-lucide="package-open"></i>No active plan.</div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="table">
            <thead><tr><th>#</th><th class="text-end">Amount</th><th class="text-end">Daily</th><th>Progress</th><th class="text-end">Earned</th><th>Ends</th></tr></thead>
            <tbody>
            <?php foreach ($investments as $i): ?>
              <?php $pct = $i->duration_days > 0 ? round($i->days_credited / $i->duration_days * 100) : 0; ?>
              <tr>
                <td class="text-dim">#<?php echo (int) $i->id; ?></td>
                <td class="text-end num"><?php echo money($i->amount); ?></td>
                <td class="text-end num text-dim"><?php echo money($i->daily_amount); ?></td>
                <td style="min-width:120px">
                  <div class="small text-muted mb-1"><?php echo (int) $i->days_credited; ?> / <?php echo (int) $i->duration_days; ?> days</div>
                  <div class="progress"><div class="progress-bar" data-bar="<?php echo $pct; ?>"></div></div>
                </td>
                <td class="text-end num text-ok"><?php echo money($i->total_earned); ?></td>
                <td class="text-muted text-nowrap small"><?php echo fmt_date($i->end_date, 'd M Y'); ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <div class="row g-3">
      <div class="col-xl-6">
        <div class="panel h-100 reveal" data-reveal-order="4">
          <div class="panel-head"><i data-lucide="inbox"></i> Recent Deposits</div>
          <?php if (empty($deposits)): ?>
            <div class="empty-state"><i data-lucide="inbox"></i>No deposits.</div>
          <?php else: ?>
            <div class="table-wrap">
              <table class="table">
                <thead><tr><th>#</th><th class="text-end">Amount</th><th>Status</th><th>Date</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($deposits as $d): ?>
                  <tr>
                    <td class="text-dim">#<?php echo (int) $d->id; ?></td>
                    <td class="text-end num"><?php echo money($d->amount); ?></td>
                    <td><?php echo chip($d->status); ?></td>
                    <td class="text-muted text-nowrap small"><?php echo fmt_date($d->created_at, 'd M Y'); ?></td>
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
        <div class="panel h-100 reveal" data-reveal-order="5">
          <div class="panel-head"><i data-lucide="hand-coins"></i> Recent Withdrawals</div>
          <?php if (empty($withdrawals)): ?>
            <div class="empty-state"><i data-lucide="inbox"></i>No withdrawals.</div>
          <?php else: ?>
            <div class="table-wrap">
              <table class="table">
                <thead><tr><th>#</th><th class="text-end">Net</th><th>Status</th><th>Date</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($withdrawals as $w): ?>
                  <tr>
                    <td class="text-dim">#<?php echo (int) $w->id; ?></td>
                    <td class="text-end num"><?php echo money($w->net_amount); ?></td>
                    <td><?php echo chip($w->status); ?></td>
                    <td class="text-muted text-nowrap small"><?php echo fmt_date($w->created_at, 'd M Y'); ?></td>
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
  </div>
</div>
