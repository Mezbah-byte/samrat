<div class="row g-3 mb-3">
  <div class="col-md-3 col-6"><div class="card stat-card"><div class="stat-label">Active</div><div class="stat-value fs-4"><?php echo number_format($stats['active_count']); ?></div></div></div>
  <div class="col-md-3 col-6"><div class="card stat-card"><div class="stat-label">Completed</div><div class="stat-value fs-4"><?php echo number_format($stats['completed_count']); ?></div></div></div>
  <div class="col-md-3 col-6"><div class="card stat-card"><div class="stat-label">Capital in Plans</div><div class="stat-value fs-4"><?php echo money($stats['active_invested']); ?></div></div></div>
  <div class="col-md-3 col-6"><div class="card stat-card"><div class="stat-label">Paid on Active</div><div class="stat-value fs-4 text-success"><?php echo money($stats['active_paid']); ?></div></div></div>
</div>

<div class="card">
  <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
    <span><i class="bi bi-graph-up-arrow"></i> Investments</span>
    <?php echo form_open('admin/investments', array('method' => 'get', 'class' => 'd-flex gap-2 m-0')); ?>
      <select name="status" class="form-select form-select-sm" data-autosubmit>
        <option value="">All statuses</option>
        <?php foreach (array('active', 'completed', 'cancelled') as $s): ?>
          <option value="<?php echo $s; ?>" <?php echo $status === $s ? 'selected' : ''; ?>><?php echo ucfirst($s); ?></option>
        <?php endforeach; ?>
      </select>
      <input type="search" name="q" class="form-control form-control-sm" placeholder="User or package" value="<?php echo html_escape($search); ?>" style="min-width:200px">
      <button class="btn btn-sm btn-primary"><i class="bi bi-search"></i></button>
    <?php echo form_close(); ?>
  </div>

  <?php if (empty($rows)): ?>
    <div class="empty-state"><i class="bi bi-inboxes"></i>No investments match this filter.</div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table table-hover mb-0">
        <thead>
          <tr><th>#</th><th>User</th><th>Package</th><th class="text-end">Amount</th><th class="text-end">Daily</th>
              <th class="text-center">Ads</th><th>Progress</th><th class="text-end">Earned</th><th>Period</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $i): ?>
          <?php $pct = round($i->days_credited / max(1, $i->duration_days) * 100); ?>
          <tr>
            <td class="text-muted">#<?php echo (int) $i->id; ?></td>
            <td><a href="<?php echo base_url('admin/users/view/'.$i->user_id); ?>" class="small fw-semibold"><?php echo html_escape($i->username); ?></a></td>
            <td class="small"><?php echo html_escape($i->package_name); ?></td>
            <td class="text-end"><?php echo money($i->amount); ?></td>
            <td class="text-end text-success"><?php echo money($i->daily_amount); ?></td>
            <td class="text-center small"><?php echo (int) $i->daily_ads; ?></td>
            <td style="min-width:140px">
              <div class="progress" style="height:6px"><div class="progress-bar" style="width:<?php echo $pct; ?>%"></div></div>
              <small class="text-muted">
                <?php echo (int) $i->days_credited; ?>/<?php echo (int) $i->duration_days; ?>
                <?php if ($i->days_missed > 0): ?><span class="text-danger">&bull; <?php echo (int) $i->days_missed; ?> missed</span><?php endif; ?>
              </small>
            </td>
            <td class="text-end fw-semibold"><?php echo money($i->total_earned); ?></td>
            <td class="small text-muted text-nowrap"><?php echo fmt_date($i->start_date, 'd M y'); ?> &rarr; <?php echo fmt_date($i->end_date, 'd M y'); ?></td>
            <td><?php echo badge($i->status); ?></td>
            <td class="text-end"><a href="<?php echo base_url('admin/investments/view/'.$i->id); ?>" class="btn btn-sm btn-outline-primary">Open</a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="card-footer d-flex justify-content-between align-items-center">
      <small class="text-muted"><?php echo (int) $total; ?> investments</small>
      <?php echo pager(base_url('admin/investments').'?status='.urlencode($status).'&q='.urlencode($search), $total, $per_page, $page); ?>
    </div>
  <?php endif; ?>
</div>
