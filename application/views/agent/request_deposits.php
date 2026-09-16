<?php
$tiles = array(
	array('label' => 'Waiting on You', 'value' => $stats['pending_count'], 'icon' => 'hourglass',
	      'grad' => $stats['pending_count'] ? 'grad-warning' : 'grad-primary', 'money' => FALSE,
	      'note' => 'Confirm or hand back', 'tone' => $stats['pending_count'] ? 'text-warn' : ''),
	array('label' => 'Float Available', 'value' => $balance, 'icon' => 'coins',
	      'grad' => 'grad-primary', 'note' => 'Spendable right now', 'link' => 'agent/float'),
	array('label' => 'Settled Lifetime', 'value' => $stats['settled_total'], 'icon' => 'check-check',
	      'grad' => 'grad-success', 'note' => 'Deposits you funded'),
	array('label' => 'Earned on Deposits', 'value' => $stats['earned_total'], 'icon' => 'sparkles',
	      'grad' => 'grad-teal', 'note' => 'Commission booked'),
);

$filters = array('' => 'All', 'pending' => 'Waiting', 'accepted' => 'Settled', 'rejected' => 'Declined', 'expired' => 'Expired');
?>

<div class="page-head reveal" data-reveal-order="0">
  <div>
    <h1>Deposit Requests</h1>
    <p class="lede">Users who paid your wallet. Confirm the money landed, and your float funds their plan.</p>
  </div>
  <a href="<?php echo base_url('agent/float/create'); ?>" class="btn btn-grad"><i data-lucide="plus"></i> Buy Float</a>
</div>

<?php $this->load->view('agent/_tiles', array('tiles' => $tiles, 'tiles_offset' => 1)); ?>

<div class="panel reveal" data-reveal-order="5">
  <div class="panel-head">
    <i data-lucide="arrow-down-to-line"></i> Requests
    <span class="spacer"></span>
    <?php echo form_open('agent/requests/deposits', array('method' => 'get', 'class' => 'd-flex gap-2 m-0')); ?>
      <select name="status" class="form-select form-select-sm" data-autosubmit style="width:auto">
        <?php foreach ($filters as $k => $label): ?>
          <option value="<?php echo $k; ?>" <?php echo $status === $k ? 'selected' : ''; ?>><?php echo $label; ?></option>
        <?php endforeach; ?>
      </select>
      <input type="search" name="q" class="form-control form-control-sm" placeholder="User or TXID"
             value="<?php echo html_escape($search); ?>" style="min-width:170px">
      <button class="btn btn-quiet btn-icon" aria-label="Search"><i data-lucide="search"></i></button>
    <?php echo form_close(); ?>
  </div>

  <?php if (empty($rows)): ?>
    <div class="empty-state">
      <i data-lucide="inbox"></i>
      <p class="mb-2">Nothing here.</p>
      <p class="small text-muted mb-3">
        Users can pick you only while you hold enough float and have an active receive wallet.
      </p>
      <a href="<?php echo base_url('agent/wallets'); ?>" class="btn btn-quiet"><i data-lucide="wallet"></i> My Wallets</a>
    </div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr><th>#</th><th>User</th><th>Package</th><th class="text-end">Amount</th>
              <th>TXID</th><th>Request</th><th>Submitted</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $d): ?>
          <tr>
            <td class="text-dim">#<?php echo (int) $d->id; ?></td>
            <td>
              <div class="fw-semibold"><?php echo html_escape($d->username); ?></div>
              <div class="small text-dim"><?php echo html_escape($d->full_name); ?></div>
            </td>
            <td class="text-muted"><?php echo html_escape($d->package_name); ?></td>
            <td class="text-end num fw-semibold"><?php echo money($d->amount); ?></td>
            <td class="mono small" title="<?php echo html_escape($d->txid); ?>"><?php echo html_escape(short_txt($d->txid)); ?></td>
            <td><?php echo agent_request_chip($d->agent_status, 'deposit'); ?></td>
            <td class="text-muted text-nowrap small"><?php echo fmt_date($d->created_at, 'd M, H:i'); ?></td>
            <td class="text-end">
              <a href="<?php echo base_url('agent/requests/deposit/'.$d->id); ?>"
                 class="btn btn-sm <?php echo $d->agent_status === 'pending' ? 'btn-grad' : 'btn-quiet'; ?>">
                <?php echo $d->agent_status === 'pending' ? 'Handle' : 'View'; ?>
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="panel-foot">
      <span><?php echo (int) $total; ?> request<?php echo $total == 1 ? '' : 's'; ?></span>
      <?php echo pager(base_url('agent/requests/deposits').'?status='.urlencode($status).'&q='.urlencode($search), $total, $per_page, $page); ?>
    </div>
  <?php endif; ?>
</div>
