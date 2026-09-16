<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <span><i class="bi bi-wallet2"></i> My Receive Wallets</span>
    <a href="<?php echo base_url('agent/wallets/create'); ?>" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> New Wallet</a>
  </div>

  <?php if (empty($rows)): ?>
    <div class="empty-state">
      <i class="bi bi-wallet2"></i>
      You have no wallets yet. Users cannot pay you until you add at least one active wallet.
    </div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table table-hover mb-0">
        <thead><tr><th>Label</th><th>Network</th><th>Address</th><th class="text-end">Limits</th><th>Status</th><th>Order</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $w): ?>
          <tr>
            <td class="fw-semibold"><?php echo html_escape($w->label); ?></td>
            <td><span class="badge text-bg-light border"><?php echo html_escape($w->network); ?> <?php echo html_escape($w->currency); ?></span></td>
            <td class="mono small" title="<?php echo html_escape($w->wallet_address); ?>"><?php echo html_escape(short_txt($w->wallet_address, 14, 8)); ?></td>
            <td class="text-end small text-muted text-nowrap">
              <?php echo money($w->min_amount); ?> &ndash;
              <?php echo (float) $w->max_amount > 0 ? money($w->max_amount) : 'no cap'; ?>
            </td>
            <td><?php echo badge($w->status); ?></td>
            <td class="small text-muted"><?php echo (int) $w->sort_order; ?></td>
            <td class="text-end text-nowrap">
              <a href="<?php echo base_url('agent/wallets/edit/'.$w->id); ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
              <?php echo form_open('agent/wallets/delete/'.$w->id, array('class' => 'd-inline')); ?>
                <button class="btn btn-sm btn-outline-danger" data-confirm="Delete this wallet? Past deposits keep their history, but the address is removed from them."><i class="bi bi-trash"></i></button>
              <?php echo form_close(); ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<div class="alert alert-info mt-3 small">
  <i class="bi bi-info-circle"></i>
  Only <strong>active</strong> wallets are shown to users. Deactivate one instead of deleting it
  if you just want to stop receiving on that address for a while.
</div>
