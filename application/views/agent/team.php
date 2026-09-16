<?php
$tiles = array(
	array('label' => 'Total Members', 'value' => $stats['total'], 'icon' => 'users',
	      'grad' => 'grad-primary', 'money' => FALSE, 'note' => 'Everyone below you'),
	array('label' => 'Active', 'value' => $stats['active'], 'icon' => 'user-check',
	      'grad' => 'grad-success', 'money' => FALSE, 'note' => 'In good standing'),
	array('label' => 'Joined (30d)', 'value' => $stats['joined_30d'], 'icon' => 'user-plus',
	      'grad' => 'grad-info', 'money' => FALSE, 'note' => 'Last month'),
	array('label' => 'Team Deposits', 'value' => $stats['total_deposit'], 'icon' => 'trending-up',
	      'grad' => 'grad-teal', 'note' => 'Lifetime volume'),
);
?>

<div class="page-head reveal" data-reveal-order="0">
  <div>
    <h1>My Team</h1>
    <p class="lede">Your downline, read-only. Blocking or adjusting a member is an admin action.</p>
  </div>
</div>

<?php $this->load->view('agent/_tiles', array('tiles' => $tiles, 'tiles_offset' => 1)); ?>

<div class="panel reveal" data-reveal-order="5">
  <div class="panel-head">
    <i data-lucide="users"></i> Members
    <span class="spacer"></span>
    <?php echo form_open('agent/team', array('method' => 'get', 'class' => 'd-flex gap-2 m-0')); ?>
      <select name="status" class="form-select form-select-sm" data-autosubmit style="width:auto">
        <option value="">All statuses</option>
        <?php foreach (array('active', 'pending', 'blocked') as $s): ?>
          <option value="<?php echo $s; ?>" <?php echo $status === $s ? 'selected' : ''; ?>><?php echo ucfirst($s); ?></option>
        <?php endforeach; ?>
      </select>
      <input type="search" name="q" class="form-control form-control-sm" placeholder="Name or username"
             value="<?php echo html_escape($search); ?>" style="min-width:170px">
      <button class="btn btn-quiet btn-icon" aria-label="Search"><i data-lucide="search"></i></button>
    <?php echo form_close(); ?>
  </div>

  <?php if (empty($rows)): ?>
    <div class="empty-state">
      <i data-lucide="users"></i>
      <?php if ($agent->user_id): ?>
        <p class="mb-0">No members match this filter.</p>
      <?php else: ?>
        <p class="mb-1 fw-semibold">No linked user account</p>
        <p class="small text-muted mb-0">This agent has no downline until an admin links an active user account.</p>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>#</th><th>Name</th><th>Username</th><th>Country</th><th>Status</th>
                   <th class="text-end">Deposited</th><th class="text-end">Balance</th><th>Joined</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $m): ?>
          <tr>
            <td class="text-dim">#<?php echo (int) $m->id; ?></td>
            <td class="fw-semibold"><?php echo html_escape($m->full_name); ?></td>
            <td class="text-muted"><?php echo html_escape($m->username); ?></td>
            <td class="text-muted"><?php echo html_escape($m->country ?: '-'); ?></td>
            <td><?php echo chip($m->status); ?></td>
            <td class="text-end num"><?php echo money($m->total_deposit); ?></td>
            <td class="text-end num text-dim"><?php echo money($m->balance); ?></td>
            <td class="text-muted text-nowrap small"><?php echo fmt_date($m->created_at, 'd M Y'); ?></td>
            <td class="text-end"><a href="<?php echo base_url('agent/team/view/'.$m->id); ?>" class="btn btn-ghost btn-icon"><i data-lucide="eye"></i></a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="panel-foot">
      <span><?php echo (int) $total; ?> member<?php echo $total == 1 ? '' : 's'; ?></span>
      <?php echo pager(base_url('agent/team').'?status='.urlencode($status).'&q='.urlencode($search), $total, $per_page, $page); ?>
    </div>
  <?php endif; ?>
</div>
