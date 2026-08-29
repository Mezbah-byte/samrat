<div class="page-head reveal" data-reveal-order="0">
  <div>
    <h1>Deposit</h1>
    <p class="lede">Deposit amounts are fixed by the package. Pick one to get the wallet address.</p>
  </div>
  <a href="<?php echo base_url('deposit/history'); ?>" class="btn btn-ghost"><i data-lucide="history"></i> All deposits</a>
</div>

<div class="row g-3">
  <div class="col-xl-8">

    <div class="panel mb-3 reveal" data-reveal-order="1">
      <div class="panel-head"><i data-lucide="box"></i> Choose a package</div>
      <div class="panel-body">
        <div class="row g-3">
          <?php foreach ($packages as $p): ?>
            <?php $daily = (float) $p->price * (float) $p->daily_return_percent / 100; ?>
            <div class="col-md-6">
              <div class="tile h-100 d-flex flex-column">
                <div class="d-flex justify-content-between align-items-start">
                  <span class="fw-semibold"><?php echo html_escape($p->name); ?></span>
                  <span class="chip chip-mute"><?php echo (int) $p->daily_ads; ?> ads/day</span>
                </div>
                <div class="plan-price my-1" style="font-size:1.7rem"><?php echo money($p->price); ?></div>
                <div class="small text-muted mb-3">
                  <?php echo money($daily); ?>/day &middot; <?php echo (int) $p->duration_days; ?> days
                </div>
                <a href="<?php echo base_url('deposit/create/'.$p->id); ?>" class="btn btn-grad mt-auto">
                  <i data-lucide="arrow-right"></i> Deposit <?php echo money($p->price); ?>
                </a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <div class="panel reveal" data-reveal-order="2">
      <div class="panel-head">
        <i data-lucide="history"></i> Recent Deposits
        <span class="spacer"></span>
        <a href="<?php echo base_url('deposit/history'); ?>">View all</a>
      </div>
      <?php if (empty($recent)): ?>
        <div class="empty-state"><i data-lucide="inbox"></i>No deposits yet.</div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="table">
            <thead><tr><th>#</th><th>Package</th><th class="text-end">Amount</th><th>TXID</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($recent as $d): ?>
              <tr>
                <td class="text-dim">#<?php echo (int) $d->id; ?></td>
                <td><?php echo html_escape($d->package_name); ?></td>
                <td class="text-end num fw-semibold"><?php echo money($d->amount); ?></td>
                <td class="mono" title="<?php echo html_escape($d->txid); ?>"><?php echo html_escape(short_txt($d->txid)); ?></td>
                <td><?php echo chip($d->status); ?></td>
                <td class="small text-muted text-nowrap"><?php echo fmt_date($d->created_at, 'd M, H:i'); ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-xl-4">
    <div class="panel reveal" data-reveal-order="3">
      <div class="panel-head"><i data-lucide="wallet"></i> Company Wallets</div>
      <div class="panel-body">
        <?php if (empty($methods)): ?>
          <p class="text-muted small mb-0">No wallet has been configured yet. Please contact support.</p>
        <?php else: foreach ($methods as $m): ?>
          <div class="tile mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="fw-semibold"><?php echo html_escape($m->name); ?></span>
              <span class="chip chip-info"><?php echo html_escape($m->network); ?></span>
            </div>
            <?php if ($m->qr_image): ?>
              <div class="qr-box"><img src="<?php echo upload_url('qr', $m->qr_image); ?>" alt="QR code"></div>
            <?php endif; ?>
            <div class="copy-field">
              <input type="text" class="form-control form-control-sm" id="wallet<?php echo (int) $m->id; ?>" readonly value="<?php echo html_escape($m->wallet_address); ?>">
              <button class="btn btn-ghost" type="button" data-copy-target="#wallet<?php echo (int) $m->id; ?>" aria-label="Copy address"><i data-lucide="copy"></i></button>
            </div>
            <?php if ($m->instructions): ?>
              <p class="small text-muted mt-2 mb-0"><?php echo html_escape($m->instructions); ?></p>
            <?php endif; ?>
          </div>
        <?php endforeach; endif; ?>

        <div class="alert alert-warning small mb-0">
          <i data-lucide="triangle-alert"></i>
          Send only the token and network shown. Transfers on the wrong network are unrecoverable.
        </div>
      </div>
    </div>
  </div>
</div>
