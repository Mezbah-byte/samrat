<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span><i class="bi bi-box-seam"></i> Packages</span>
    <a href="<?php echo base_url('admin/packages/create'); ?>" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> New Package</a>
  </div>

  <?php if (empty($rows)): ?>
    <div class="empty-state"><i class="bi bi-box"></i>No packages yet.</div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table table-hover mb-0">
        <thead>
          <tr><th>Order</th><th>Name</th><th class="text-end">Deposit</th><th class="text-end">Daily %</th>
              <th class="text-end">Daily Income</th><th class="text-center">Duration</th><th class="text-center">Daily Ads</th>
              <th class="text-end">Min. Withdraw</th><th class="text-end">Total Return</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $p): ?>
          <?php $daily = (float) $p->price * (float) $p->daily_return_percent / 100; ?>
          <tr>
            <td class="text-muted"><?php echo (int) $p->sort_order; ?></td>
            <td class="fw-semibold"><?php echo html_escape($p->name); ?></td>
            <td class="text-end"><?php echo money($p->price); ?></td>
            <td class="text-end"><?php echo percent($p->daily_return_percent); ?></td>
            <td class="text-end text-success"><?php echo money($daily); ?></td>
            <td class="text-center"><?php echo (int) $p->duration_days; ?>d</td>
            <td class="text-center"><span class="badge text-bg-light border"><?php echo (int) $p->daily_ads; ?></span></td>
            <td class="text-end"><?php echo money($p->min_withdraw); ?></td>
            <td class="text-end fw-semibold"><?php echo money((float) $p->price + $daily * (int) $p->duration_days); ?></td>
            <td><?php echo badge($p->status); ?></td>
            <td class="text-end text-nowrap">
              <a href="<?php echo base_url('admin/packages/edit/'.$p->id); ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
              <?php echo form_open('admin/packages/delete/'.$p->id, array('class' => 'd-inline')); ?>
                <button class="btn btn-sm btn-outline-danger" data-confirm="Delete this package? If it has linked deposits it will be deactivated instead."><i class="bi bi-trash"></i></button>
              <?php echo form_close(); ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
