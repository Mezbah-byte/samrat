<div class="row g-3 mb-3">
  <div class="col-md-8">
    <div class="card stat-card">
      <div class="card-body">
        <div class="stat-label">Deposit Float Available</div>
        <div class="stat-value"><?php echo money($balance); ?></div>
        <i class="bi bi-wallet2 stat-icon"></i>
        <div class="small text-muted mt-1">This is what you can settle user deposits with.</div>
      </div>
    </div>
  </div>
  <div class="col-md-4 d-flex align-items-stretch">
    <a href="<?php echo base_url('agent/float/create'); ?>" class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2">
      <i class="bi bi-plus-lg"></i> Buy More Float
    </a>
  </div>
</div>

<div class="card">
  <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
    <span><i class="bi bi-coin"></i> Float Orders</span>
    <?php echo form_open('agent/float', array('method' => 'get', 'class' => 'd-flex gap-2 m-0')); ?>
      <select name="status" class="form-select form-select-sm" data-autosubmit>
        <option value="">All statuses</option>
        <?php foreach (array('pending', 'approved', 'rejected') as $s): ?>
          <option value="<?php echo $s; ?>" <?php echo $status === $s ? 'selected' : ''; ?>><?php echo ucfirst($s); ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn btn-sm btn-primary"><i class="bi bi-funnel"></i></button>
    <?php echo form_close(); ?>
  </div>

  <?php if (empty($rows)): ?>
    <div class="empty-state">
      <i class="bi bi-coin"></i>
      No float orders yet. Buy float before accepting any deposit.
    </div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table table-hover mb-0">
        <thead><tr><th>#</th><th class="text-end">Amount</th><th>Sent to</th><th>TXID</th><th>Status</th><th>Submitted</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $o): ?>
          <tr>
            <td class="text-muted">#<?php echo (int) $o->id; ?></td>
            <td class="text-end fw-semibold"><?php echo money($o->amount); ?></td>
            <td class="small"><?php echo html_escape($o->method_name ?: '-'); ?></td>
            <td class="mono small" title="<?php echo html_escape($o->txid); ?>"><?php echo html_escape(short_txt($o->txid)); ?></td>
            <td><?php echo badge($o->status); ?></td>
            <td class="small text-muted text-nowrap"><?php echo fmt_date($o->created_at, 'd M, H:i'); ?></td>
            <td class="text-end"><a href="<?php echo base_url('agent/float/view/'.$o->id); ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="card-footer d-flex justify-content-between align-items-center">
      <small class="text-muted"><?php echo (int) $total; ?> orders</small>
      <?php echo pager(base_url('agent/float').'?status='.urlencode($status), $total, $per_page, $page); ?>
    </div>
  <?php endif; ?>
</div>
