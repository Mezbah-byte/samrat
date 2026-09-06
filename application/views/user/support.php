<?php
/* Every channel here is a row in `support_links` - nothing is hard-coded, and
 * a row whose value cannot become a safe href never reaches this view because
 * support_channels() has already dropped it. */
$grads = array('grad-primary', 'grad-info', 'grad-success', 'grad-warning');
?>

<div class="page-head reveal" data-reveal-order="0">
  <div>
    <h1>Support</h1>
    <p class="lede">Stuck on a deposit, a withdrawal or your daily ads? Reach the team on any channel below.</p>
  </div>
  <a href="<?php echo base_url('dashboard'); ?>" class="btn btn-ghost"><i data-lucide="layout-dashboard"></i> Dashboard</a>
</div>

<?php if ($hours !== '' || $note !== ''): ?>
<div class="panel mb-3 reveal" data-reveal-order="1">
  <div class="panel-head"><i data-lucide="clock"></i> Before you write</div>
  <div class="panel-body">
    <?php if ($hours !== ''): ?>
      <p class="mb-<?php echo $note !== '' ? '2' : '0'; ?>">
        <span class="chip chip-info"><i data-lucide="clock"></i> Support hours</span>
        <span class="ms-2"><?php echo html_escape($hours); ?></span>
      </p>
    <?php endif; ?>
    <?php if ($note !== ''): ?>
      <p class="small text-muted mb-0"><?php echo nl2br(html_escape($note)); ?></p>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<div class="panel reveal" data-reveal-order="2">
  <div class="panel-head">
    <i data-lucide="life-buoy"></i> Contact Channels
    <span class="spacer"></span>
    <span class="chip chip-mute"><?php echo count($channels); ?> available</span>
  </div>
  <div class="panel-body">
    <?php if (empty($channels)): ?>
      <div class="empty-state">
        <i data-lucide="headset"></i>
        <p class="mb-0">No support channel has been published yet. Please check back shortly.</p>
      </div>
    <?php else: ?>
      <div class="row g-3">
        <?php foreach ($channels as $i => $c): ?>
          <?php
            // mailto:/tel: open in place; a social profile is another site and
            // gets the usual new-tab + noopener treatment.
            $external = $c->kind === 'link';
            $copyable = in_array($c->kind, array('mailto', 'tel'), TRUE);
          ?>
          <div class="col-sm-6 col-xl-4">
            <div class="panel lift h-100">
              <div class="panel-body d-flex flex-column">
                <div class="d-flex align-items-center gap-3 mb-3">
                  <span class="icon-tile <?php echo $grads[$i % count($grads)]; ?>"><i data-lucide="<?php echo html_escape($c->icon); ?>"></i></span>
                  <div style="min-width:0">
                    <div class="fw-semibold"><?php echo html_escape($c->label); ?></div>
                    <div class="small text-muted text-truncate" title="<?php echo html_escape($c->value); ?>"><?php echo html_escape($c->value); ?></div>
                    <?php if ( ! empty($c->note)): ?>
                      <div class="small text-muted"><?php echo html_escape($c->note); ?></div>
                    <?php endif; ?>
                  </div>
                </div>

                <?php if ($copyable): ?>
                  <div class="copy-field mb-2">
                    <input type="text" class="form-control form-control-sm" id="sc_<?php echo (int) $c->id; ?>" readonly value="<?php echo html_escape($c->value); ?>">
                    <button class="btn btn-sm btn-ghost" type="button" data-copy-target="#sc_<?php echo (int) $c->id; ?>" aria-label="Copy <?php echo html_escape($c->label); ?>">
                      <i data-lucide="copy"></i>
                    </button>
                  </div>
                <?php endif; ?>

                <a class="btn btn-grad w-100 mt-auto" href="<?php echo html_escape($c->url); ?>"
                   <?php echo $external ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>>
                  <i data-lucide="<?php echo $external ? 'external-link' : ($c->kind === 'tel' ? 'phone' : 'mail'); ?>"></i>
                  <?php echo $external ? 'Visit' : ($c->kind === 'tel' ? 'Call' : 'Write'); ?>
                </a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<p class="small text-muted mt-3 mb-0">
  <i data-lucide="shield-alert"></i>
  Staff never ask for your password or a wallet seed phrase. Only trust the channels listed on this page.
</p>
