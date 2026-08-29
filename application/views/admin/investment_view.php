<a href="<?php echo base_url('admin/investments'); ?>" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> All investments</a>

<div class="row g-3">
  <div class="col-lg-5">
    <div class="card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span>Investment #<?php echo (int) $i->id; ?></span>
        <?php echo badge($i->status); ?>
      </div>
      <ul class="list-group list-group-flush">
        <li class="list-group-item d-flex justify-content-between">
          <span class="text-muted">User</span>
          <?php if ($owner): ?>
            <a href="<?php echo base_url('admin/users/view/'.$owner->id); ?>"><?php echo html_escape($owner->username); ?></a>
          <?php else: ?><span>-</span><?php endif; ?>
        </li>
        <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Package</span><strong><?php echo $package ? html_escape($package->name) : '-'; ?></strong></li>
        <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Invested</span><strong><?php echo money($i->amount); ?></strong></li>
        <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Daily rate</span><strong><?php echo percent($i->daily_return_percent); ?></strong></li>
        <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Daily amount</span><strong class="text-success"><?php echo money($i->daily_amount); ?></strong></li>
        <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Ads per day</span><strong><?php echo (int) $i->daily_ads; ?></strong></li>
        <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Term</span><strong><?php echo (int) $i->duration_days; ?> days</strong></li>
        <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Days credited</span><strong><?php echo (int) $i->days_credited; ?></strong></li>
        <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Days missed</span><strong class="<?php echo $i->days_missed ? 'text-danger' : ''; ?>"><?php echo (int) $i->days_missed; ?></strong></li>
        <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Total earned</span><strong class="text-success"><?php echo money($i->total_earned); ?></strong></li>
        <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Period</span><span class="small"><?php echo fmt_date($i->start_date, 'd M Y'); ?> &rarr; <?php echo fmt_date($i->end_date, 'd M Y'); ?></span></li>
      </ul>
    </div>

    <?php if ($i->status === 'active'): ?>
      <div class="card border-danger">
        <div class="card-header text-danger-emphasis bg-danger-subtle"><i class="bi bi-x-octagon"></i> Cancel Plan</div>
        <div class="card-body">
          <p class="small text-muted">
            Stops the plan and closes every future day. Earnings already credited are not reversed.
          </p>
          <?php echo form_open('admin/investments/cancel/'.$i->id); ?>
            <input type="text" name="reason" class="form-control form-control-sm mb-2" maxlength="200" placeholder="Reason" required>
            <button class="btn btn-outline-danger w-100" data-confirm="Cancel this plan? This cannot be undone.">Cancel Plan</button>
          <?php echo form_close(); ?>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <div class="col-lg-7">
    <div class="card">
      <div class="card-header"><i class="bi bi-calendar3"></i> Day-by-day Log</div>
      <?php if (empty($days)): ?>
        <div class="empty-state"><i class="bi bi-calendar-x"></i>No day rows yet.</div>
      <?php else: ?>
        <div class="table-wrap" style="max-height:600px;overflow-y:auto">
          <table class="table table-sm mb-0">
            <thead class="sticky-top bg-white"><tr><th>Date</th><th class="text-center">Ads</th><th class="text-end">Amount</th><th>Status</th><th>Credited</th></tr></thead>
            <tbody>
            <?php foreach ($days as $d): ?>
              <tr>
                <td><?php echo fmt_date($d->earn_date, 'd M Y'); ?></td>
                <td class="text-center small"><?php echo (int) $d->ads_watched; ?>/<?php echo (int) $d->ads_required; ?></td>
                <td class="text-end"><?php echo money($d->amount); ?></td>
                <td><?php echo badge($d->status); ?></td>
                <td class="small text-muted"><?php echo $d->credited_at ? fmt_date($d->credited_at, 'd M, H:i') : '-'; ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
