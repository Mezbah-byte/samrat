<div class="page-head reveal" data-reveal-order="0">
  <div>
    <h1>Deposit History</h1>
    <p class="lede"><?php echo (int) $total; ?> deposit<?php echo $total == 1 ? '' : 's'; ?> on record.</p>
  </div>
  <a href="<?php echo base_url('deposit'); ?>" class="btn btn-grad"><i data-lucide="plus"></i> New Deposit</a>
</div>

<div class="panel reveal" data-reveal-order="1">
  <?php if (empty($rows)): ?>
    <div class="empty-state"><i data-lucide="inbox"></i>You have not made any deposits yet.</div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr><th>#</th><th>Package</th><th class="text-end">Amount</th><th>Network</th>
              <th>TXID</th><th>Status</th><th>Note</th><th>Submitted</th></tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $d): ?>
          <tr>
            <td class="text-dim">#<?php echo (int) $d->id; ?></td>
            <td class="fw-semibold"><?php echo html_escape($d->package_name); ?></td>
            <td class="text-end num fw-semibold"><?php echo money($d->amount); ?></td>
            <td><span class="chip chip-mute"><?php echo html_escape($d->network); ?></span></td>
            <td class="mono" title="<?php echo html_escape($d->txid); ?>"><?php echo html_escape(short_txt($d->txid)); ?></td>
            <td><?php echo chip($d->status); ?></td>
            <td class="small text-muted"><?php echo html_escape($d->admin_note ?: '-'); ?></td>
            <td class="small text-muted text-nowrap"><?php echo fmt_date($d->created_at, 'd M, H:i'); ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="panel-foot">
      <span><?php echo (int) $total; ?> total</span>
      <?php echo pager(base_url('deposit/history'), $total, $per_page, $page); ?>
    </div>
  <?php endif; ?>
</div>
