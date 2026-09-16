<?php
$tiles = array(
	array('label' => 'Waiting on You', 'value' => $stats['pending_count'], 'icon' => 'hourglass',
	      'grad' => $stats['pending_count'] ? 'grad-warning' : 'grad-primary', 'money' => FALSE,
	      'note' => 'Send, then confirm', 'tone' => $stats['pending_count'] ? 'text-warn' : ''),
	array('label' => 'Paid Out Lifetime', 'value' => $stats['paid_total'], 'icon' => 'send',
	      'grad' => 'grad-teal', 'note' => 'From your own pocket'),
	array('label' => 'Earned on Withdrawals', 'value' => $stats['earned_total'], 'icon' => 'sparkles',
	      'grad' => 'grad-success', 'note' => 'Commission booked'),
);

$filters = array('' => 'All', 'pending' => 'Waiting', 'accepted' => 'Paid', 'rejected' => 'Declined', 'expired' => 'Expired');
?>

<div class="page-head reveal" data-reveal-order="0">
  <div>
    <h1>Withdraw Requests</h1>
    <p class="lede">Users who chose you to pay them. Send first, confirm second &mdash; then collect it back.</p>
  </div>
  <a href="<?php echo base_url('agent/payouts'); ?>" class="btn btn-ghost"><i data-lucide="banknote"></i> Cash Out</a>
</div>

<?php $this->load->view('agent/_tiles', array('tiles' => $tiles, 'tiles_offset' => 1, 'tiles_cols' => 'col-md-4')); ?>

<div class="panel reveal" data-reveal-order="4">
  <div class="panel-head">
    <i data-lucide="arrow-up-from-line"></i> Requests
    <span class="spacer"></span>
    <?php echo form_open('agent/requests/withdrawals', array('method' => 'get', 'class' => 'd-flex gap-2 m-0')); ?>
      <select name="status" class="form-select form-select-sm" data-autosubmit style="width:auto">
        <?php foreach ($filters as $k => $label): ?>
          <option value="<?php echo $k; ?>" <?php echo $status === $k ? 'selected' : ''; ?>><?php echo $label; ?></option>
        <?php endforeach; ?>
      </select>
      <input type="search" name="q" class="form-control form-control-sm" placeholder="User or address"
             value="<?php echo html_escape($search); ?>" style="min-width:170px">
      <button class="btn btn-quiet btn-icon" aria-label="Search"><i data-lucide="search"></i></button>
    <?php echo form_close(); ?>
  </div>

  <?php if (empty($rows)): ?>
    <div class="empty-state">
      <i data-lucide="inbox"></i>
      <p class="mb-2">Nothing here yet.</p>
      <p class="small text-muted mb-0">Users pick you when they request a withdrawal.</p>
    </div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr><th>#</th><th>User</th><th class="text-end">You send</th><th>Network</th>
              <th>Send to</th><th>Request</th><th>Asked</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $w): ?>
          <tr>
            <td class="text-dim">#<?php echo (int) $w->id; ?></td>
            <td>
              <div class="fw-semibold"><?php echo html_escape($w->username); ?></div>
              <div class="small text-dim"><?php echo html_escape($w->full_name); ?></div>
            </td>
            <td class="text-end num fw-semibold"><?php echo money($w->net_amount); ?></td>
            <td><span class="chip chip-mute"><?php echo html_escape($w->network); ?></span></td>
            <td class="mono small" title="<?php echo html_escape($w->wallet_address); ?>"><?php echo html_escape(short_txt($w->wallet_address)); ?></td>
            <td><?php echo agent_request_chip($w->agent_status, 'withdrawal'); ?></td>
            <td class="text-muted text-nowrap small"><?php echo fmt_date($w->created_at, 'd M, H:i'); ?></td>
            <td class="text-end">
              <a href="<?php echo base_url('agent/requests/withdrawal/'.$w->id); ?>"
                 class="btn btn-sm <?php echo $w->agent_status === 'pending' ? 'btn-grad' : 'btn-quiet'; ?>">
                <?php echo $w->agent_status === 'pending' ? 'Handle' : 'View'; ?>
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="panel-foot">
      <span><?php echo (int) $total; ?> request<?php echo $total == 1 ? '' : 's'; ?></span>
      <?php echo pager(base_url('agent/requests/withdrawals').'?status='.urlencode($status).'&q='.urlencode($search), $total, $per_page, $page); ?>
    </div>
  <?php endif; ?>
</div>
