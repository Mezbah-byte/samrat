<?php
/* Referral rates come from the admin's ladder, so the wording has to survive
 * one generation, several, or none at all. */
$ref_rates = array();
foreach ($ref_ladder as $level => $pct)
{
	$ref_rates[] = 'generation '.$level.' '.((float) $pct).'%';
}

$ref_line = empty($ref_rates)
	? 'Not running at the moment.'
	: (count($ref_rates) === 1
		? ((float) reset($ref_ladder)).'% of a direct referral\'s deposit, paid once when that deposit is approved.'
		: 'Paid up the referral tree on an approved deposit &mdash; '.implode(', ', $ref_rates).'. Paid once each.');

$terms = array(
	array('Daily return',        "Set per package, credited only on days the ad quota is completed."),
	array('Missed days',         "Forfeited. The plan term is a fixed number of calendar days and does not extend."),
	array('Withdrawal fee',      ((float) setting('withdrawal_fee_percent', 5)).'% of the requested amount.'),
	array('Minimum withdrawal',  "Determined by your package. Larger packages have a higher minimum."),
	array('Referral commission', $ref_line),
	array('Deposits',            "Manual USDT transfer to the company wallet, verified on-chain by an administrator before the plan activates."),
);
?>

<div class="container section">
  <div class="row justify-content-center">
    <div class="col-xl-8 col-lg-10">

      <div class="section-head text-start reveal" data-reveal-order="0" style="margin-left:0">
        <h2>About <?php echo html_escape($company_name); ?></h2>
        <p><?php echo html_escape(setting('company_tagline', '')); ?></p>
      </div>

      <div class="panel mb-4 reveal" data-reveal-order="1">
        <div class="panel-body">
          <p class="mb-0 text-muted">
            <?php echo html_escape($company_name); ?> runs a package-based earning platform. Members deposit in USDT,
            activate a plan, and earn a fixed daily percentage for the plan's term as long as they complete
            their daily advertisement views.
          </p>
        </div>
      </div>

      <div class="panel mb-4 reveal" data-reveal-order="2">
        <div class="panel-head"><i data-lucide="scroll-text"></i> Key terms</div>
        <div class="panel-body">
          <?php foreach ($terms as $t): ?>
            <div class="row g-2 tile-row">
              <div class="col-sm-4 fw-semibold"><?php echo $t[0]; ?></div>
              <div class="col-sm-8 text-muted"><?php echo $t[1]; ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- <div class="panel mb-4 reveal" data-reveal-order="3">
        <div class="panel-head text-warn"><i data-lucide="triangle-alert"></i> Risk notice</div>
        <div class="panel-body">
          <p class="mb-0 small text-muted">
            Returns are not guaranteed by any regulator or deposit insurance scheme. Cryptocurrency transfers are
            irreversible. Only participate with funds you can afford to lose, and make sure this kind of scheme is
            legal in your jurisdiction before depositing.
          </p>
        </div>
      </div> -->

      <div class="d-flex flex-wrap gap-3 reveal" data-reveal-order="4">
        <?php if ($mail = setting('support_email')): ?>
          <a href="mailto:<?php echo html_escape($mail); ?>" class="btn btn-ghost"><i data-lucide="mail"></i> <?php echo html_escape($mail); ?></a>
        <?php endif; ?>
        <?php if ($tg = setting('support_telegram')): ?>
          <a href="<?php echo html_escape($tg); ?>" target="_blank" rel="noopener" class="btn btn-ghost"><i data-lucide="send"></i> Telegram</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
