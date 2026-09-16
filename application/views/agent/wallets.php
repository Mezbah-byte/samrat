<?php
$active = 0;
foreach ($rows as $w) { if ($w->status === 'active') { $active++; } }
?>
<div class="page-head reveal" data-reveal-order="0">
  <div>
    <h1>My Wallets</h1>
    <p class="lede">The addresses users see after picking you. Only active ones are shown to them.</p>
  </div>
  <a href="<?php echo base_url('agent/wallets/create'); ?>" class="btn btn-grad"><i data-lucide="plus"></i> New Wallet</a>
</div>

<?php if (empty($rows)): ?>
  <div class="panel reveal" data-reveal-order="1">
    <div class="empty-state">
      <i data-lucide="wallet"></i>
      <p class="mb-2 fw-semibold">No wallets yet</p>
      <p class="small text-muted mb-3">
        Users cannot pay you until you publish at least one active address, so you will not appear
        in their agent list.
      </p>
      <a href="<?php echo base_url('agent/wallets/create'); ?>" class="btn btn-grad"><i data-lucide="plus"></i> Add Your First Wallet</a>
    </div>
  </div>
<?php else: ?>

  <?php if ( ! $active): ?>
    <div class="panel mb-3 reveal" data-reveal-order="1">
      <div class="panel-body d-flex gap-3 align-items-start">
        <span class="icon-tile grad-danger"><i data-lucide="eye-off"></i></span>
        <div>
          <div class="fw-semibold mb-1">Every wallet is inactive</div>
          <p class="small text-muted mb-0">You will not be offered to any user until one is active.</p>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <div class="row g-3">
    <?php foreach ($rows as $i => $w): ?>
      <div class="col-xl-6">
        <div class="panel lift h-100 reveal" data-reveal-order="<?php echo $i + 1; ?>">
          <div class="panel-head">
            <span class="icon-tile sm <?php echo $w->status === 'active' ? 'grad-success' : 'grad-warning'; ?>">
              <i data-lucide="wallet"></i>
            </span>
            <?php echo html_escape($w->label); ?>
            <span class="spacer"></span>
            <?php echo chip($w->status); ?>
          </div>
          <div class="panel-body">
            <div class="d-flex gap-2 mb-2">
              <span class="chip chip-info"><?php echo html_escape($w->network); ?></span>
              <span class="chip chip-mute"><?php echo html_escape($w->currency); ?></span>
              <?php if ((int) $w->sort_order): ?>
                <span class="chip chip-mute">order <?php echo (int) $w->sort_order; ?></span>
              <?php endif; ?>
            </div>

            <div class="copy-field mb-3">
              <input type="text" class="form-control form-control-sm mono" id="w<?php echo (int) $w->id; ?>" readonly value="<?php echo html_escape($w->wallet_address); ?>">
              <button class="btn btn-ghost" type="button" data-copy-target="#w<?php echo (int) $w->id; ?>" aria-label="Copy address"><i data-lucide="copy"></i></button>
            </div>

            <div class="tile">
              <div class="tile-row">
                <span class="text-muted">Accepts</span>
                <span class="num">
                  <?php echo money($w->min_amount); ?> &ndash;
                  <?php echo (float) $w->max_amount > 0 ? money($w->max_amount) : 'no cap'; ?>
                </span>
              </div>
            </div>

            <?php if ($w->instructions): ?>
              <p class="small text-muted mt-3 mb-0"><i data-lucide="info"></i> <?php echo html_escape($w->instructions); ?></p>
            <?php endif; ?>
          </div>
          <div class="panel-foot">
            <span class="text-dim small">Added <?php echo fmt_date($w->created_at, 'd M Y'); ?></span>
            <span class="d-flex gap-2">
              <a href="<?php echo base_url('agent/wallets/edit/'.$w->id); ?>" class="btn btn-quiet btn-sm"><i data-lucide="pencil"></i> Edit</a>
              <?php echo form_open('agent/wallets/delete/'.$w->id, array('class' => 'd-inline')); ?>
                <button class="btn btn-ghost btn-icon text-bad"
                        data-confirm="Delete this wallet? Past deposits keep their history, but the address is removed from them."
                        aria-label="Delete"><i data-lucide="trash-2"></i></button>
              <?php echo form_close(); ?>
            </span>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="panel mt-3 reveal" data-reveal-order="9">
    <div class="panel-body d-flex gap-3 align-items-start">
      <span class="icon-tile sm grad-info"><i data-lucide="lightbulb"></i></span>
      <p class="small text-muted mb-0">
        Deactivate a wallet instead of deleting it if you only want to stop receiving on that
        address for a while &mdash; deleting it strips the address from every past deposit that
        used it.
      </p>
    </div>
  </div>
<?php endif; ?>
