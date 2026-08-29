<div class="page-head reveal" data-reveal-order="0">
  <div>
    <h1>Withdrawal History</h1>
    <p class="lede"><?php echo (int) $total; ?> request<?php echo $total == 1 ? '' : 's'; ?> on record.</p>
  </div>
  <a href="<?php echo base_url('withdraw'); ?>" class="btn btn-grad"><i data-lucide="plus"></i> New Request</a>
</div>

<div class="panel reveal" data-reveal-order="1">
  <?php if (empty($rows)): ?>
    <div class="empty-state"><i data-lucide="inbox"></i>No withdrawal requests yet.</div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr><th>#</th><th class="text-end">Requested</th><th class="text-end">Fee</th><th class="text-end">Net</th>
              <th>Network</th><th>Address</th><th>Status</th><th>Payout TXID</th><th>Date</th></tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $w): ?>
          <tr>
            <td class="text-dim">#<?php echo (int) $w->id; ?></td>
            <td class="text-end num"><?php echo money($w->amount); ?></td>
            <td class="text-end num text-bad"><?php echo money($w->fee); ?></td>
            <td class="text-end num fw-semibold"><?php echo money($w->net_amount); ?></td>
            <td><span class="chip chip-mute"><?php echo html_escape($w->network); ?></span></td>
            <td class="mono" title="<?php echo html_escape($w->wallet_address); ?>"><?php echo html_escape(short_txt($w->wallet_address)); ?></td>
            <td><?php echo chip($w->status); ?></td>
            <td class="mono" title="<?php echo html_escape($w->txid); ?>"><?php echo $w->txid ? html_escape(short_txt($w->txid)) : '-'; ?></td>
            <td class="small text-muted text-nowrap"><?php echo fmt_date($w->created_at, 'd M, H:i'); ?></td>
          </tr>
          <?php if ($w->admin_note): ?>
            <tr>
              <td></td>
              <td colspan="8" class="small text-muted"><i data-lucide="message-square"></i> <?php echo html_escape($w->admin_note); ?></td>
            </tr>
          <?php endif; ?>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="panel-foot">
      <span><?php echo (int) $total; ?> total</span>
      <?php echo pager(base_url('withdraw/history'), $total, $per_page, $page); ?>
    </div>
  <?php endif; ?>
</div>
