<div class="row g-3 mb-3">
  <div class="col-md-6"><div class="card stat-card"><div class="stat-label">Pending Review</div><div class="stat-value fs-4 <?php echo $stats['pending_count'] ? 'text-warning' : ''; ?>"><?php echo (int) $stats['pending_count']; ?></div></div></div>
  <div class="col-md-6"><div class="card stat-card"><div class="stat-label">Paid Out</div><div class="stat-value fs-4"><?php echo money($stats['paid_total']); ?></div></div></div>
</div>

<div class="card">
  <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
    <span><i class="bi bi-send"></i> Agent Payouts</span>
    <?php echo form_open('admin/agent-payouts', array('method' => 'get', 'class' => 'd-flex gap-2 m-0')); ?>
      <select name="status" class="form-select form-select-sm" data-autosubmit>
        <option value="">All statuses</option>
        <?php foreach (array('pending', 'approved', 'paid', 'rejected') as $s): ?>
          <option value="<?php echo $s; ?>" <?php echo $status === $s ? 'selected' : ''; ?>><?php echo ucfirst($s); ?></option>
        <?php endforeach; ?>
      </select>
      <input type="search" name="q" class="form-control form-control-sm" placeholder="Agent, wallet or TXID" value="<?php echo html_escape($search); ?>" style="min-width:200px">
      <button class="btn btn-sm btn-primary"><i class="bi bi-search"></i></button>
    <?php echo form_close(); ?>
  </div>

  <?php if (empty($rows)): ?>
    <div class="empty-state"><i class="bi bi-send"></i>No payouts match this filter.</div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table table-hover mb-0">
        <thead>
          <tr><th>#</th><th>Agent</th><th>From</th><th class="text-end">Requested</th><th class="text-end">To send</th>
              <th>Wallet</th><th>Status</th><th>Requested at</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $p): ?>
          <tr>
            <td class="text-muted">#<?php echo (int) $p->id; ?></td>
            <td>
              <a href="<?php echo base_url('admin/agents/wallets/'.$p->agent_id); ?>" class="small fw-semibold"><?php echo html_escape($p->agent_username); ?></a>
              <div class="small text-muted"><?php echo html_escape($p->agent_name); ?></div>
            </td>
            <td><span class="badge text-bg-light border"><?php echo ucfirst($p->source); ?> wallet</span></td>
            <td class="text-end"><?php echo money($p->amount); ?></td>
            <td class="text-end fw-semibold"><?php echo money($p->net_amount); ?></td>
            <td class="mono small" title="<?php echo html_escape($p->wallet_address); ?>"><?php echo html_escape(short_txt($p->wallet_address)); ?></td>
            <td><?php echo badge($p->status); ?></td>
            <td class="small text-muted text-nowrap"><?php echo fmt_date($p->created_at, 'd M, H:i'); ?></td>
            <td class="text-end">
              <a href="<?php echo base_url('admin/agent-payouts/view/'.$p->id); ?>"
                 class="btn btn-sm <?php echo in_array($p->status, array('pending', 'approved'), TRUE) ? 'btn-primary' : 'btn-outline-secondary'; ?>">
                <?php echo in_array($p->status, array('pending', 'approved'), TRUE) ? 'Review' : 'View'; ?>
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="card-footer d-flex justify-content-between align-items-center">
      <small class="text-muted"><?php echo (int) $total; ?> payouts</small>
      <?php echo pager(base_url('admin/agent-payouts').'?status='.urlencode($status).'&q='.urlencode($search), $total, $per_page, $page); ?>
    </div>
  <?php endif; ?>
</div>
