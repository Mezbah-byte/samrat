<a href="<?php echo base_url('agent/team'); ?>" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> All members</a>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="card">
      <div class="card-header"><i class="bi bi-person"></i> <?php echo html_escape($member->full_name); ?></div>
      <div class="card-body small">
        <div class="d-flex justify-content-between py-1"><span class="text-muted">Username</span><span class="fw-semibold"><?php echo html_escape($member->username); ?></span></div>
        <div class="d-flex justify-content-between py-1"><span class="text-muted">Email</span><span><?php echo html_escape($member->email); ?></span></div>
        <div class="d-flex justify-content-between py-1"><span class="text-muted">Country</span><span><?php echo html_escape($member->country ?: '-'); ?></span></div>
        <div class="d-flex justify-content-between py-1"><span class="text-muted">Status</span><span><?php echo badge($member->status); ?></span></div>
        <div class="d-flex justify-content-between py-1"><span class="text-muted">Referral code</span><span class="fw-semibold"><?php echo html_escape($member->referral_code); ?></span></div>
        <div class="d-flex justify-content-between py-1"><span class="text-muted">Direct referrals</span><span class="fw-semibold"><?php echo (int) $direct; ?></span></div>
        <hr class="my-2">
        <div class="d-flex justify-content-between py-1"><span class="text-muted">Balance</span><span class="fw-semibold"><?php echo money($member->balance); ?></span></div>
        <div class="d-flex justify-content-between py-1"><span class="text-muted">Total deposited</span><span class="fw-semibold"><?php echo money($member->total_deposit); ?></span></div>
        <div class="d-flex justify-content-between py-1"><span class="text-muted">Total earned</span><span class="fw-semibold"><?php echo money($member->total_earned); ?></span></div>
        <div class="d-flex justify-content-between py-1"><span class="text-muted">Total withdrawn</span><span class="fw-semibold"><?php echo money($member->total_withdrawn); ?></span></div>
        <hr class="my-2">
        <div class="d-flex justify-content-between py-1"><span class="text-muted">Joined</span><span><?php echo fmt_date($member->created_at); ?></span></div>
        <div class="d-flex justify-content-between py-1"><span class="text-muted">Last login</span><span><?php echo fmt_date($member->last_login_at); ?></span></div>
      </div>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="card mb-3">
      <div class="card-header"><i class="bi bi-graph-up-arrow"></i> Active Plans</div>
      <div class="table-wrap">
        <table class="table table-hover mb-0">
          <thead><tr><th>#</th><th>Amount</th><th>Daily</th><th>Days</th><th>Earned</th><th>Ends</th></tr></thead>
          <tbody>
          <?php if (empty($investments)): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">No active plan.</td></tr>
          <?php else: foreach ($investments as $i): ?>
            <tr>
              <td class="text-muted">#<?php echo (int) $i->id; ?></td>
              <td class="small text-nowrap"><?php echo money($i->amount); ?></td>
              <td class="small text-nowrap"><?php echo money($i->daily_amount); ?></td>
              <td class="small"><?php echo (int) $i->days_credited; ?> / <?php echo (int) $i->duration_days; ?></td>
              <td class="small text-nowrap"><?php echo money($i->total_earned); ?></td>
              <td class="small text-muted text-nowrap"><?php echo fmt_date($i->end_date, 'd M Y'); ?></td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card mb-3">
      <div class="card-header"><i class="bi bi-inbox-fill"></i> Recent Deposits</div>
      <div class="table-wrap">
        <table class="table table-hover mb-0">
          <thead><tr><th>#</th><th>Amount</th><th>Package</th><th>Status</th><th>Date</th><th></th></tr></thead>
          <tbody>
          <?php if (empty($deposits)): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">No deposits.</td></tr>
          <?php else: foreach ($deposits as $d): ?>
            <tr>
              <td class="text-muted">#<?php echo (int) $d->id; ?></td>
              <td class="small text-nowrap"><?php echo money($d->amount); ?></td>
              <td class="small"><?php echo html_escape($d->package_name); ?></td>
              <td><?php echo badge($d->status); ?></td>
              <td class="small text-muted text-nowrap"><?php echo fmt_date($d->created_at, 'd M Y'); ?></td>
              <td class="text-end"><a href="<?php echo base_url('agent/deposits/view/'.$d->id); ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a></td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><i class="bi bi-cash-stack"></i> Recent Withdrawals</div>
      <div class="table-wrap">
        <table class="table table-hover mb-0">
          <thead><tr><th>#</th><th>Amount</th><th>Net</th><th>Status</th><th>Date</th><th></th></tr></thead>
          <tbody>
          <?php if (empty($withdrawals)): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">No withdrawals.</td></tr>
          <?php else: foreach ($withdrawals as $w): ?>
            <tr>
              <td class="text-muted">#<?php echo (int) $w->id; ?></td>
              <td class="small text-nowrap"><?php echo money($w->amount); ?></td>
              <td class="small text-nowrap"><?php echo money($w->net_amount); ?></td>
              <td><?php echo badge($w->status); ?></td>
              <td class="small text-muted text-nowrap"><?php echo fmt_date($w->created_at, 'd M Y'); ?></td>
              <td class="text-end"><a href="<?php echo base_url('agent/withdrawals/view/'.$w->id); ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a></td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
