<?php
$tiles = array(
	array('label' => 'Pending', 'value' => $stats['pending_count'], 'icon' => 'hourglass',
	      'grad' => 'grad-primary', 'money' => FALSE, 'note' => 'Awaiting an admin decision'),
	array('label' => 'Awaiting Your Review', 'value' => $stats['awaiting_review'], 'icon' => 'clipboard-check',
	      'grad' => $stats['awaiting_review'] ? 'grad-warning' : 'grad-info', 'money' => FALSE,
	      'note' => 'You have not weighed in', 'tone' => $stats['awaiting_review'] ? 'text-warn' : ''),
	array('label' => 'Paid to Team', 'value' => $stats['paid_total'], 'icon' => 'hand-coins',
	      'grad' => 'grad-teal', 'note' => 'Lifetime payouts below you'),
);
?>

<div class="page-head reveal" data-reveal-order="0">
  <div>
    <h1>Team Withdrawals</h1>
    <p class="lede">Payout requests from your downline. You advise &mdash; an admin approves and sends.</p>
  </div>
</div>

<?php $this->load->view('agent/_tiles', array('tiles' => $tiles, 'tiles_offset' => 1, 'tiles_cols' => 'col-md-4')); ?>

<div class="panel reveal" data-reveal-order="4">
  <div class="panel-head">
    <i data-lucide="hand-coins"></i> Withdrawals
    <span class="spacer"></span>
    <?php echo form_open('agent/withdrawals', array('method' => 'get', 'class' => 'd-flex gap-2 m-0')); ?>
      <select name="status" class="form-select form-select-sm" data-autosubmit style="width:auto">
        <option value="">All statuses</option>
        <?php foreach (array('pending', 'approved', 'paid', 'rejected') as $s): ?>
          <option value="<?php echo $s; ?>" <?php echo $status === $s ? 'selected' : ''; ?>><?php echo ucfirst($s); ?></option>
        <?php endforeach; ?>
      </select>
      <input type="search" name="q" class="form-control form-control-sm" placeholder="User or wallet"
             value="<?php echo html_escape($search); ?>" style="min-width:170px">
      <button class="btn btn-quiet btn-icon" aria-label="Search"><i data-lucide="search"></i></button>
    <?php echo form_close(); ?>
  </div>

  <?php if (empty($rows)): ?>
    <div class="empty-state"><i data-lucide="inbox"></i>No withdrawals match this filter.</div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>#</th><th>User</th><th class="text-end">Requested</th><th class="text-end">Fee</th>
                   <th class="text-end">Net</th><th>Status</th><th>My call</th><th>Date</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $w): ?>
          <tr>
            <td class="text-dim">#<?php echo (int) $w->id; ?></td>
            <td class="fw-semibold"><?php echo html_escape($w->username); ?></td>
            <td class="text-end num"><?php echo money($w->amount); ?></td>
            <td class="text-end num text-dim"><?php echo money($w->fee); ?></td>
            <td class="text-end num fw-semibold"><?php echo money($w->net_amount); ?></td>
            <td><?php echo chip($w->status); ?></td>
            <td><?php echo $w->agent_recommendation
                  ? chip($w->agent_recommendation === 'approve' ? 'approved' : 'rejected')
                  : '<span class="text-dim small">Not reviewed</span>'; ?></td>
            <td class="text-muted text-nowrap small"><?php echo fmt_date($w->created_at, 'd M Y'); ?></td>
            <td class="text-end">
              <a href="<?php echo base_url('agent/withdrawals/view/'.$w->id); ?>"
                 class="btn btn-sm <?php echo ($w->status === 'pending' && ! $w->agent_recommendation) ? 'btn-grad' : 'btn-quiet'; ?>">
                <?php echo ($w->status === 'pending' && ! $w->agent_recommendation) ? 'Review' : 'View'; ?>
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="panel-foot">
      <span><?php echo (int) $total; ?> request<?php echo $total == 1 ? '' : 's'; ?></span>
      <?php echo pager(base_url('agent/withdrawals').'?status='.urlencode($status).'&q='.urlencode($search), $total, $per_page, $page); ?>
    </div>
  <?php endif; ?>
</div>
