<div class="card">
  <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
    <span><i class="bi bi-clock-history"></i> Admin Activity Log</span>
    <?php echo form_open('admin/logs', array('method' => 'get', 'class' => 'd-flex gap-2 m-0')); ?>
      <input type="search" name="q" class="form-control form-control-sm" placeholder="Action, module or admin" value="<?php echo html_escape($search); ?>" style="min-width:220px">
      <button class="btn btn-sm btn-primary"><i class="bi bi-search"></i></button>
    <?php echo form_close(); ?>
  </div>

  <?php if (empty($rows)): ?>
    <div class="empty-state"><i class="bi bi-clock"></i>No activity recorded.</div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table mb-0">
        <thead><tr><th>#</th><th>Admin</th><th>Action</th><th>Module</th><th>Ref</th><th>Detail</th><th>IP</th><th>When</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $l): ?>
          <tr>
            <td class="text-muted">#<?php echo (int) $l->id; ?></td>
            <td class="small fw-semibold"><?php echo html_escape($l->admin_username ?: 'deleted'); ?></td>
            <td class="small"><?php echo html_escape($l->action); ?></td>
            <td><span class="badge text-bg-light border"><?php echo html_escape($l->module); ?></span></td>
            <td class="small text-muted"><?php echo $l->reference_id ? '#'.(int) $l->reference_id : '-'; ?></td>
            <td class="small text-muted"><?php echo html_escape($l->detail ?: '-'); ?></td>
            <td class="mono small text-muted"><?php echo html_escape($l->ip_address); ?></td>
            <td class="small text-muted text-nowrap"><?php echo fmt_date($l->created_at, 'd M, H:i'); ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="card-footer d-flex justify-content-between align-items-center">
      <small class="text-muted"><?php echo (int) $total; ?> entries</small>
      <?php echo pager(base_url('admin/logs').'?q='.urlencode($search), $total, $per_page, $page); ?>
    </div>
  <?php endif; ?>
</div>
