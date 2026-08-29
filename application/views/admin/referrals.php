<div class="row g-3 mb-3">
  <div class="col-md-6"><div class="card stat-card"><div class="stat-label">Commission Paid</div><div class="stat-value fs-4"><?php echo money($paid_total); ?></div></div></div>
  <div class="col-md-6"><div class="card stat-card"><div class="stat-label">Current Rate</div><div class="stat-value fs-4 text-brand"><?php echo $percent; ?>%</div><div class="small text-muted">Change it under Settings &rarr; Finance.</div></div></div>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card h-100">
      <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span><i class="bi bi-diagram-3"></i> Commission History</span>
        <?php echo form_open('admin/referrals', array('method' => 'get', 'class' => 'd-flex gap-2 m-0')); ?>
          <input type="search" name="q" class="form-control form-control-sm" placeholder="Referrer or referred" value="<?php echo html_escape($search); ?>" style="min-width:200px">
          <button class="btn btn-sm btn-primary"><i class="bi bi-search"></i></button>
        <?php echo form_close(); ?>
      </div>

      <?php if (empty($rows)): ?>
        <div class="empty-state"><i class="bi bi-people"></i>No commission has been paid yet.</div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="table table-hover mb-0">
            <thead><tr><th>#</th><th>Referrer</th><th>Referred</th><th>Deposit</th><th class="text-center">Rate</th><th class="text-end">Commission</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $c): ?>
              <tr>
                <td class="text-muted">#<?php echo (int) $c->id; ?></td>
                <td><a href="<?php echo base_url('admin/users/view/'.$c->referrer_id); ?>" class="small fw-semibold"><?php echo html_escape($c->referrer_username); ?></a></td>
                <td><a href="<?php echo base_url('admin/users/view/'.$c->referred_id); ?>" class="small"><?php echo html_escape($c->referred_username); ?></a></td>
                <td><a href="<?php echo base_url('admin/deposits/view/'.$c->deposit_id); ?>" class="small">#<?php echo (int) $c->deposit_id; ?></a></td>
                <td class="text-center small"><?php echo percent($c->percent); ?></td>
                <td class="text-end fw-semibold text-success"><?php echo money($c->amount); ?></td>
                <td class="small text-muted text-nowrap"><?php echo fmt_date($c->created_at, 'd M, H:i'); ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center">
          <small class="text-muted"><?php echo (int) $total; ?> payouts</small>
          <?php echo pager(base_url('admin/referrals').'?q='.urlencode($search), $total, $per_page, $page); ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card h-100">
      <div class="card-header"><i class="bi bi-trophy"></i> Top Referrers</div>
      <?php if (empty($leaderboard)): ?>
        <div class="empty-state py-4"><i class="bi bi-people"></i>Nothing yet.</div>
      <?php else: ?>
        <ol class="list-group list-group-numbered list-group-flush">
          <?php foreach ($leaderboard as $l): ?>
            <li class="list-group-item d-flex justify-content-between align-items-start">
              <div class="ms-2 me-auto">
                <div class="fw-semibold small"><?php echo html_escape($l->username); ?></div>
                <div class="small text-muted"><?php echo (int) $l->deals; ?> deposits</div>
              </div>
              <span class="text-success fw-semibold"><?php echo money($l->earned); ?></span>
            </li>
          <?php endforeach; ?>
        </ol>
      <?php endif; ?>
    </div>
  </div>
</div>
