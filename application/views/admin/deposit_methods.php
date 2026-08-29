<div class="alert alert-info">
  <i class="bi bi-info-circle"></i>
  These are the wallets users send deposits to. A network and address are required &mdash; an exchange account ID alone
  is not enough to verify a transfer on-chain.
</div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span><i class="bi bi-wallet2"></i> Deposit Wallets</span>
    <a href="<?php echo base_url('admin/deposit-methods/create'); ?>" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> Add Wallet</a>
  </div>

  <?php if (empty($rows)): ?>
    <div class="empty-state"><i class="bi bi-wallet"></i>No wallets configured. Users cannot deposit until you add one.</div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table table-hover mb-0">
        <thead><tr><th>Order</th><th>QR</th><th>Name</th><th>Network</th><th>Currency</th><th>Address</th><th class="text-end">Min</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $m): ?>
          <tr>
            <td class="text-muted"><?php echo (int) $m->sort_order; ?></td>
            <td>
              <?php if ($m->qr_image): ?>
                <img src="<?php echo upload_url('qr', $m->qr_image); ?>" style="width:38px;height:38px;object-fit:cover" class="rounded" alt="">
              <?php else: ?><span class="text-muted small">-</span><?php endif; ?>
            </td>
            <td class="fw-semibold"><?php echo html_escape($m->name); ?></td>
            <td><span class="badge text-bg-secondary"><?php echo html_escape($m->network); ?></span></td>
            <td class="small"><?php echo html_escape($m->currency); ?></td>
            <td class="mono small" title="<?php echo html_escape($m->wallet_address); ?>"><?php echo html_escape(short_txt($m->wallet_address, 14, 8)); ?></td>
            <td class="text-end"><?php echo money($m->min_amount); ?></td>
            <td><?php echo badge($m->status); ?></td>
            <td class="text-end text-nowrap">
              <a href="<?php echo base_url('admin/deposit-methods/edit/'.$m->id); ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
              <?php echo form_open('admin/deposit-methods/delete/'.$m->id, array('class' => 'd-inline')); ?>
                <button class="btn btn-sm btn-outline-danger" data-confirm="Delete this wallet? Past deposits keep their history."><i class="bi bi-trash"></i></button>
              <?php echo form_close(); ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
