<?php
/* Every number here comes out of Team_bonus_lib::progress() - the tiers, their
 * targets and their amounts are whatever the admin has set, never hard-coded. */
$tiers = $progress['tiers'];
$next  = $progress['next'];

$tiles = array(
	array('Team Volume',   (float) $progress['team_volume'],     'trending-up',  'grad-primary', 2),
	array('Direct Buyers', (int)   $progress['team_buyers'],     'user-check',   'grad-info',    0),
	array('Ready to Claim',(float) $progress['claimable_total'], 'gift',         'grad-warning', 2),
	array('Bonus Earned',  (float) $progress['claimed_total'],   'hand-coins',   'grad-success', 2),
);
?>

<div class="page-head reveal" data-reveal-order="0">
  <div>
    <h1>Team Bonus</h1>
    <p class="lede">
      <?php if (empty($tiers)): ?>
        Milestone bonuses on what your team buys. No tiers are running right now &mdash; check back soon.
      <?php else: ?>
        Every package your direct referrals buy adds to your team volume. Hit a milestone and the
        bonus is yours to claim &mdash; on top of the referral commission you already earn.
        Volume never resets, so it keeps counting toward the next tier.
      <?php endif; ?>
    </p>
  </div>
</div>

<div class="row g-3 mb-3">
  <?php foreach ($tiles as $i => $t): ?>
    <?php list($label, $value, $icon, $grad, $dp) = $t; ?>
    <div class="col-sm-6 col-xl-3">
      <div class="panel lift stat h-100 reveal" data-reveal-order="<?php echo $i + 1; ?>">
        <div class="stat-top">
          <div>
            <div class="stat-label"><?php echo $label; ?></div>
            <div class="stat-value num"
                 data-count="<?php echo $value; ?>"
                 data-count-decimals="<?php echo $dp; ?>"
                 data-count-prefix="<?php echo $dp ? html_escape(currency()) : ''; ?>">
              <?php echo $dp ? money($value) : $value; ?>
            </div>
          </div>
          <span class="icon-tile <?php echo $grad; ?>"><i data-lucide="<?php echo $icon; ?>"></i></span>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php if ($progress['claimable_count'] > 0): ?>
  <div class="panel is-live mb-3 reveal" data-reveal-order="5">
    <div class="panel-body d-flex flex-wrap align-items-center gap-3">
      <span class="icon-tile grad-success"><i data-lucide="party-popper"></i></span>
      <div class="flex-grow-1">
        <div class="fw-bold mb-1">
          <?php echo (int) $progress['claimable_count']; ?>
          bonus<?php echo $progress['claimable_count'] > 1 ? 'es' : ''; ?> ready to claim
        </div>
        <p class="mb-0 small text-muted">
          <span class="text-ok fw-bold"><?php echo money($progress['claimable_total']); ?></span>
          is waiting. Claim it below and it lands in your balance straight away.
        </p>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php if ($next !== NULL): ?>
  <div class="panel mb-3 reveal" data-reveal-order="6">
    <div class="panel-head"><i data-lucide="target"></i> Next Milestone &mdash; <?php echo html_escape($next['name']); ?></div>
    <div class="panel-body">

      <div class="d-flex justify-content-between align-items-end mb-1">
        <span class="small text-muted">
          <?php if ($next['mode'] === 'single'): ?>
            Biggest single purchase in your team
          <?php else: ?>
            Total bought by your direct referrals
          <?php endif; ?>
        </span>
        <span class="fw-semibold num">
          <?php echo money($next['reached']); ?> / <?php echo money($next['target']); ?>
        </span>
      </div>

      <div class="progress mb-3" style="height:10px">
        <div class="progress-bar" role="progressbar" data-bar="<?php echo (int) $next['percent']; ?>"
             style="width: <?php echo (int) $next['percent']; ?>%"
             aria-valuenow="<?php echo (int) $next['percent']; ?>" aria-valuemin="0" aria-valuemax="100"></div>
      </div>

      <p class="mb-0">
        <?php if ($next['remaining'] > 0): ?>
          <span class="fw-bold"><?php echo money($next['remaining']); ?></span> more
          <?php if ($next['mode'] === 'single'): ?>
            in a <em>single</em> purchase by one team member
          <?php else: ?>
            in team purchases
          <?php endif; ?>
          to unlock <span class="text-ok fw-bold"><?php echo money($next['bonus']); ?></span>.
        <?php elseif ($next['buyers_short'] > 0): ?>
          Volume reached. You still need
          <span class="fw-bold"><?php echo (int) $next['buyers_short']; ?></span>
          more direct referral<?php echo $next['buyers_short'] > 1 ? 's' : ''; ?> to buy a package
          before this unlocks.
        <?php else: ?>
          Target reached &mdash; reload the page to claim it.
        <?php endif; ?>
      </p>

      <?php if ($direct_count < 1): ?>
        <hr class="my-3">
        <p class="mb-2 small text-muted">
          You have no direct referrals yet. Share your link and their purchases start counting here.
        </p>
        <a href="<?php echo base_url('referral'); ?>" class="btn btn-grad btn-sm">
          <i data-lucide="share-2"></i> Get your referral link
        </a>
      <?php endif; ?>

    </div>
  </div>
<?php endif; ?>

<div class="panel mb-3 reveal" data-reveal-order="7">
  <div class="panel-head"><i data-lucide="trophy"></i> Bonus Ladder</div>
  <div class="panel-body">

    <?php if (empty($tiers)): ?>
      <p class="text-muted mb-0">No bonus tiers are active at the moment.</p>
    <?php else: ?>
      <div class="row g-3">
        <?php foreach ($tiers as $t): ?>
          <?php
          $done    = ($t['status'] === 'claimed');
          $ready   = ($t['status'] === 'unlocked');
          $blocked = ($t['buyers_short'] > 0);
          ?>
          <div class="col-md-6 col-xl-4">
            <div class="panel h-100 <?php echo $ready ? 'is-live' : ''; ?>">
              <div class="panel-body">

                <div class="d-flex justify-content-between align-items-start mb-2">
                  <div>
                    <div class="fw-bold"><?php echo html_escape($t['name']); ?></div>
                    <div class="small text-muted">
                      <?php if ($t['mode'] === 'single'): ?>
                        One member buys <?php echo money($t['target']); ?>
                      <?php else: ?>
                        Team buys <?php echo money($t['target']); ?> in total
                      <?php endif; ?>
                      <?php if ($t['min_referrals'] > 0): ?>
                        <br>from at least <?php echo (int) $t['min_referrals']; ?> buyers
                      <?php endif; ?>
                    </div>
                  </div>
                  <span class="fw-bold text-ok num"><?php echo money($t['bonus']); ?></span>
                </div>

                <?php if ($done): ?>
                  <span class="chip"><i data-lucide="check"></i> Claimed</span>
                  <div class="small text-muted mt-2">
                    <?php echo $t['claimed_at'] ? date('d M Y', strtotime($t['claimed_at'])) : ''; ?>
                  </div>

                <?php elseif ($ready): ?>
                  <div class="progress mb-3" style="height:8px">
                    <div class="progress-bar bg-success" style="width:100%"></div>
                  </div>
                  <?php echo form_open('team-bonus/claim/'.$t['id'], array('class' => 'm-0')); ?>
                    <button type="submit" class="btn btn-grad w-100">
                      <i data-lucide="gift"></i> Claim <?php echo money($t['bonus']); ?>
                    </button>
                  <?php echo form_close(); ?>

                <?php else: ?>
                  <div class="d-flex justify-content-between small text-muted mb-1">
                    <span class="num"><?php echo money($t['reached']); ?></span>
                    <span><?php echo (int) $t['percent']; ?>%</span>
                  </div>
                  <div class="progress mb-2" style="height:8px">
                    <div class="progress-bar" style="width: <?php echo (int) $t['percent']; ?>%"></div>
                  </div>
                  <div class="small text-muted">
                    <?php if ($t['remaining'] > 0): ?>
                      <?php echo money($t['remaining']); ?> to go
                    <?php elseif ($blocked): ?>
                      <?php echo (int) $t['buyers_short']; ?> more buyer<?php echo $t['buyers_short'] > 1 ? 's' : ''; ?> needed
                    <?php else: ?>
                      Unlocking&hellip;
                    <?php endif; ?>
                  </div>
                <?php endif; ?>

              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </div>
</div>

<?php if ( ! empty($history)): ?>
  <div class="panel reveal" data-reveal-order="8">
    <div class="panel-head"><i data-lucide="history"></i> Claim History</div>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Tier</th>
            <th>Target</th>
            <th class="text-end">Bonus</th>
            <th class="text-end">Claimed</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($history as $h): ?>
          <tr>
            <td class="fw-semibold"><?php echo html_escape($h->tier_name ?: 'Tier #'.$h->tier_id); ?></td>
            <td class="num text-muted"><?php echo money($h->target_volume); ?></td>
            <td class="text-end num fw-semibold text-ok"><?php echo money($h->bonus_amount); ?></td>
            <td class="text-end small text-muted">
              <?php echo $h->claimed_at ? date('d M Y, H:i', strtotime($h->claimed_at)) : '&mdash;'; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>
