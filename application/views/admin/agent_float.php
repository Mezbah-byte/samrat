<div class="row g-3 mb-3">
  <div class="col-md-6"><div class="card stat-card"><div class="stat-label">Pending Review</div><div class="stat-value fs-4 <?php echo $stats['pending_count'] ? 'text-warning' : ''; ?>"><?php echo (int) $stats['pending_count']; ?></div></div></div>
  <div class="col-md-6"><div class="card stat-card"><div class="stat-label">Float Sold</div><div class="stat-value fs-4"><?php echo money($stats['approved_total']); ?></div></div></div>
</div>

<div class="card">
  <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
    <span><i class="bi bi-coin"></i> Agent Float Orders</span>
    <?php echo form_open('admin/agent-float', array('method' => 'get', 'class' => 'd-flex gap-2 m-0')); ?>
      <select name="status" class="form-select form-select-sm" data-autosubmit>
        <option value="">All statuses</option>
        <?php foreach (array('pending', 'approved', 'rejected') as $s): ?>
          <option value="<?php echo $s; ?>" <?php echo $status === $s ? 'selected' : ''; ?>><?php echo ucfirst($s); ?></option>
        <?php endforeach; ?>
      </select>
      <input type="search" name="q" class="form-control form-control-sm" placeholder="Agent or TXID" value="<?php echo html_escape($search); ?>" style="min-width:200px">
      <button class="btn btn-sm btn-primary"><i class="bi bi-search"></i></button>
    <?php echo form_close(); ?>
  </div>

  <?php if (empty($rows)): ?>
    <div class="empty-state"><i class="bi bi-coin"></i>No float orders match this filter.</div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table table-hover mb-0">
        <thead>
          <tr><th>#</th><th>Agent</th><th class="text-end">Amount</th>
              <th>Network</th><th>TXID</th><th>Status</th><th>Submitted</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $o): ?>
          <tr>
            <td class="text-muted">#<?php echo (int) $o->id; ?></td>
            <td>
              <a href="<?php echo base_url('admin/agents/wallets/'.$o->agent_id); ?>" class="small fw-semibold"><?php echo html_escape($o->agent_username); ?></a>
              <div class="small text-muted"><?php echo html_escape($o->agent_name); ?></div>
            </td>
            <td class="text-end fw-semibold"><?php echo money($o->amount); ?></td>
            <td><span class="badge text-bg-light border"><?php echo html_escape($o->network ?: '-'); ?></span></td>
            <td class="mono small" title="<?php echo html_escape($o->txid); ?>"><?php echo html_escape(short_txt($o->txid)); ?></td>
            <td><?php echo badge($o->status); ?></td>
            <td class="small text-muted text-nowrap"><?php echo fmt_date($o->created_at, 'd M, H:i'); ?></td>
            <td class="text-end">
              <a href="<?php echo base_url('admin/agent-float/view/'.$o->id); ?>"
                 class="btn btn-sm <?php echo $o->status === 'pending' ? 'btn-primary' : 'btn-outline-secondary'; ?>">
                <?php echo $o->status === 'pending' ? 'Review' : 'View'; ?>
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="card-footer d-flex justify-content-between align-items-center">
      <small class="text-muted"><?php echo (int) $total; ?> orders</small>
      <?php echo pager(base_url('admin/agent-float').'?status='.urlencode($status).'&q='.urlencode($search), $total, $per_page, $page); ?>
    </div>
  <?php endif; ?>
</div>
