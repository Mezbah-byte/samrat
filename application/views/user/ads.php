<?php $pct = ($progress['required'] > 0) ? min(100, round($progress['watched'] / max(1, $progress['required']) * 100)) : 0; ?>

<div class="page-head reveal" data-reveal-order="0">
  <div>
    <h1>Daily Ads</h1>
    <p class="lede"><?php echo date('l, d M Y'); ?> &middot; finish the quota to unlock today's profit.</p>
  </div>
  <a href="<?php echo base_url('dashboard'); ?>" class="btn btn-ghost"><i data-lucide="layout-dashboard"></i> Dashboard</a>
</div>

<div class="panel <?php echo $progress['complete'] ? 'is-live' : ''; ?> mb-3 reveal" data-reveal-order="1">
  <div class="panel-body">
    <?php if ($progress['required'] < 1): ?>
      <div class="empty-state">
        <i data-lucide="package-open"></i>
        <p class="mb-3">You need an active plan before ads count toward anything.</p>
        <a href="<?php echo base_url('packages'); ?>" class="btn btn-grad"><i data-lucide="box"></i> Browse Packages</a>
      </div>
    <?php else: ?>
      <div class="d-flex flex-wrap align-items-center gap-4">
        <div class="ring">
          <svg viewBox="0 0 132 132" aria-hidden="true">
            <defs>
              <linearGradient id="ringGrad" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0%"   stop-color="<?php echo $progress['complete'] ? '#10b981' : '#575beb'; ?>" />
                <stop offset="100%" stop-color="<?php echo $progress['complete'] ? '#06b6d4' : '#8b5cf6'; ?>" />
              </linearGradient>
            </defs>
            <circle class="ring-bg"   cx="66" cy="66" r="57" />
            <circle class="ring-fill" cx="66" cy="66" r="57" data-pct="<?php echo $pct; ?>" />
          </svg>
          <div class="ring-center">
            <span class="ring-num num"><?php echo (int) $progress['watched']; ?>/<?php echo (int) $progress['required']; ?></span>
            <span class="ring-cap">ads watched</span>
          </div>
        </div>

        <div class="flex-fill" style="min-width:240px">
          <?php if ($progress['complete']): ?>
            <span class="chip chip-ok mb-2"><i data-lucide="check"></i> Complete</span>
            <p class="mb-3"><span class="text-ok fw-bold"><?php echo money($progress['earned']); ?></span> credited today.</p>
          <?php else: ?>
            <span class="chip chip-warn mb-2"><i data-lucide="hourglass"></i> <?php echo (int) $progress['remaining']; ?> to go</span>
            <p class="mb-3"><span class="fw-bold"><?php echo money($progress['pending']); ?></span> locked until the quota is met.</p>
          <?php endif; ?>

          <div class="progress mb-3"><div class="progress-bar <?php echo $progress['complete'] ? 'bg-success' : ''; ?>" data-bar="<?php echo $pct; ?>"></div></div>

          <?php if ( ! $progress['complete']): ?>
            <p class="small text-muted mb-0">
              <i data-lucide="info"></i>
              Finish all <?php echo (int) $progress['required']; ?> ads before midnight. A missed day is forfeited and the plan's end date does not move.
            </p>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php if ( ! empty($today_rows)): ?>
<div class="panel mb-3 reveal" data-reveal-order="2">
  <div class="panel-head"><i data-lucide="calendar-check"></i> Today's Plan Payouts</div>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Plan Day</th><th class="text-center">Required</th><th class="text-center">Watched</th><th class="text-end">Amount</th><th>Status</th></tr></thead>
      <tbody>
      <?php foreach ($today_rows as $r): ?>
        <tr>
          <td><?php echo fmt_date($r->earn_date, 'd M Y'); ?></td>
          <td class="text-center num"><?php echo (int) $r->ads_required; ?></td>
          <td class="text-center num"><?php echo (int) $r->ads_watched; ?></td>
          <td class="text-end num fw-semibold"><?php echo money($r->amount); ?></td>
          <td><?php echo chip($r->status); ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<div class="panel reveal" data-reveal-order="3">
  <div class="panel-head">
    <i data-lucide="play-circle"></i> Available Ads
    <span class="spacer"></span>
    <span class="chip chip-mute"><?php echo count($ads); ?> live</span>
  </div>
  <div class="panel-body">
    <?php if (empty($ads)): ?>
      <div class="empty-state"><i data-lucide="tv-minimal"></i>No ads are available right now. Check back later.</div>
    <?php else: ?>
      <div class="row g-3">
        <?php foreach ($ads as $ad): ?>
          <?php $done = in_array((int) $ad->id, $watched_ids, TRUE); ?>
          <div class="col-sm-6 col-xl-4">
            <div class="panel <?php echo $done ? 'done' : 'lift'; ?> ad-card">
              <div class="ad-media">
                <?php if ($ad->media): ?>
                  <img src="<?php echo upload_url('ads', $ad->media); ?>" alt="<?php echo html_escape($ad->title); ?>">
                <?php else: ?>
                  <i data-lucide="tv-minimal"></i>
                <?php endif; ?>
              </div>
              <div class="ad-body">
                <div class="fw-semibold text-truncate mb-1" title="<?php echo html_escape($ad->title); ?>"><?php echo html_escape($ad->title); ?></div>
                <div class="small text-muted mb-3 d-flex align-items-center gap-1">
                  <i data-lucide="timer"></i> <?php echo (int) $ad->watch_seconds; ?>s watch time
                </div>
                <?php if ($done): ?>
                  <button class="btn btn-ghost w-100 mt-auto" disabled><i data-lucide="check-check"></i> Watched today</button>
                <?php else: ?>
                  <button class="btn btn-grad w-100 mt-auto" data-bs-toggle="modal" data-bs-target="#adModal"
                          data-ad-id="<?php echo (int) $ad->id; ?>"
                          data-seconds="<?php echo (int) $ad->watch_seconds; ?>"
                          data-ad-title="<?php echo html_escape($ad->title); ?>"
                          data-ad-media="<?php echo $ad->media ? upload_url('ads', $ad->media) : ''; ?>"
                          data-ad-link="<?php echo html_escape($ad->target_url); ?>"
                          data-ad-body="<?php echo html_escape($ad->body); ?>">
                    <i data-lucide="play"></i> Watch
                  </button>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Watch modal. The countdown is a courtesy; the real gate is server-side. -->
<div class="modal fade" id="adModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title ad-title">Advertisement</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="ad-media-box mb-3"></div>
        <p class="ad-body-text text-muted small"></p>
        <a href="#" target="_blank" rel="noopener nofollow" class="ad-visit btn btn-ghost d-none">
          <i data-lucide="external-link"></i> Visit advertiser
        </a>
      </div>
      <div class="modal-footer justify-content-between">
        <span class="small text-muted ad-progress-ring num">
          Please wait <strong class="ad-countdown">0</strong>s
        </span>
        <?php echo form_open('ads/complete', array('class' => 'ad-form m-0', 'data-base' => base_url('ads/complete'))); ?>
          <button type="submit" class="btn btn-grad ad-claim-btn" disabled>
            <i data-lucide="check"></i> Confirm view
          </button>
        <?php echo form_close(); ?>
      </div>
    </div>
  </div>
</div>
