<?php
/* Public landing page.
 *
 * Structure follows what the established get-paid-to platforms do: hero, ways
 * to earn, what you need, a step explainer, payout section, house rules, FAQ,
 * repeated call to action. Nothing on the page asks for input - both calls to
 * action are a single button through to the register screen, which is the only
 * place that collects anything. What they also do - review counts, total-paid
 * counters, press logos, testimonials - is left out on purpose, because none of
 * it can be stated truthfully here.
 *
 * Nothing internal appears either: no member counts, no package pricing, no
 * return rates, no fee percentages, no notices. Concepts yes, figures no.
 * The mock-ups are empty rows and status words; never fill them with numbers. */

$ways = array(
	array('play-circle', 'accent', 'Watch the daily set',
	      'Short ads land in your dashboard every day. Watch them through and the day is banked.'),
	array('users',       'gold',   'Refer other people',
	      'Share your link. Anyone who joins through it stays tied to your account from day one.'),
	array('calendar-check', 'green', 'Come back tomorrow',
	      'The set refreshes daily. Turn up, clear it, and let the balance build at your own pace.'),
);

$steps = array(
	array('user-plus',   'Sign up free',       'An email address, a password, and the referral ID of whoever invited you.'),
	array('badge-check', 'Get your account on', 'Finish the setup from your dashboard and wait for it to be switched on.'),
	array('play',        'Clear the daily set', 'Watch the ads assigned to you. A finished set marks the day done.'),
	array('wallet',      'Cash out',            'Request a withdrawal and follow it through every stage until it lands.'),
);

$needs = array(
	array('mail',       'accent', 'An email address',
	      'That is the whole sign-up. No documents, no phone number, no card details.'),
	array('smartphone', 'green',  'A phone or a laptop',
	      'Any browser from the last few years. Nothing to download and nothing to keep updated.'),
	array('clock',      'gold',   'A few minutes a day',
	      'One short session clears the set. Do it whenever the day suits you.'),
);

$why = array(
	array('shield-check',   'Login-only access',   'Your balance and history are visible to you and nobody else.'),
	array('smartphone',     'Works on your phone', 'Same dashboard on a phone, a tablet or a desktop.'),
	array('download-cloud', 'Nothing to install',  'It runs in the browser you already have.'),
	array('eye',            'Status you can see',  'Every withdrawal shows where it has got to.'),
	array('moon-star',      'Light and dark',      'Pick the theme that suits the room; it is remembered.'),
	array('life-buoy',      'A person on the end', 'Support messages are read by someone, not a robot.'),
);

/* House rules. Behaviour only - no figures, and nothing here that the account
 * itself does not already show the member. */
$rules = array(
	array('moon-star',   'A day closes at midnight',
	      'The set resets then. Whatever was left unwatched closes with the day it belonged to.'),
	array('refresh-cw',  'A missed day does not roll over',
	      'It does not stack onto tomorrow and it does not extend the end of your plan. Nothing already earned is touched.'),
	array('lock',        'A withdrawal is held while it is reviewed',
	      'The amount leaves your balance the moment you request it, so the same balance cannot be requested twice.'),
	array('user-check',  'One account per person',
	      'Duplicates are closed. Referrals only count when they are a different person joining through your link.'),
);

$faqs = array(
	array('How does the earning actually work?',
	      'You watch the ads assigned to your account each day. Finishing the day\'s set is what makes that day count towards your balance.'),
	array('How much time does it take?',
	      'A short session. The set is built to fit into a coffee break, not to take over your afternoon.'),
	array('What happens if I miss a day?',
	      'That day simply does not count. Nothing already earned is taken away, and you carry on the next day as normal.'),
	array('How do I get paid?',
	      'Request a withdrawal from your account page once you are eligible. Every stage of the request stays visible to you.'),
	array('Is there anything to download?',
	      'No. It runs in the browser you already have, on desktop and on mobile.'),
	array('How do I reach a person?',
	      'The contact links in the footer go to a real inbox. Messages are read by someone, not filed away by a robot.'),
);
?>

<section class="lp-hero">
  <?php /* Drifting wash. Decoration only, so it is hidden from assistive tech
           and the CSS stops it under prefers-reduced-motion. */ ?>
  <span class="lp-hero-bg" aria-hidden="true">
    <span class="lp-orb lp-orb-1"></span>
    <span class="lp-orb lp-orb-2"></span>
    <span class="lp-orb lp-orb-3"></span>
    <span class="lp-mesh"></span>
  </span>

  <div class="container">
    <div class="lp-hero-grid">
      <div class="lp-hero-copy reveal" data-reveal-order="0">
        <span class="lp-eyebrow"><span class="dot dot-ok pulse"></span> Open for new members</span>

        <h1>Watch a few ads.<br><em>Get paid for the day.</em></h1>

        <p class="lp-sub">
          A short set of ads is waiting in your dashboard every day. Clear it, and the day is
          banked. Withdraw whenever you are ready.
        </p>

        <?php /* One button. Asking for an email here only duplicated the field
                 the register screen already has. */ ?>
        <div class="lp-actions">
          <a class="lp-btn lp-btn-primary lp-btn-lg" href="<?php echo base_url('register'); ?>">
            Start earning <i data-lucide="arrow-right"></i>
          </a>
        </div>

        <p class="lp-form-note">
          Free to join. Already a member? <a href="<?php echo base_url('login'); ?>">Sign in</a>.
        </p>

        <ul class="lp-proof">
          <li><i data-lucide="clock"></i> Minutes a day</li>
          <li><i data-lucide="smartphone"></i> Phone or desktop</li>
          <li><i data-lucide="download-cloud"></i> Nothing to install</li>
        </ul>
      </div>

      <div class="lp-hero-art reveal" data-reveal-order="1">
        <?php /* Mock of the daily ad screen. Rows are empty on purpose. */ ?>
        <div class="lp-app" aria-hidden="true">
          <div class="lp-app-bar">
            <span class="lp-app-name">Today's set</span>
            <span class="lp-app-chip"><span class="dot dot-ok pulse"></span> In progress</span>
          </div>

          <div class="lp-app-body">
            <div class="lp-task done">
              <span class="lp-task-thumb"><i data-lucide="check"></i></span>
              <span class="lp-task-lines"><span class="l w-70"></span><span class="l s w-40"></span></span>
              <span class="lp-task-state">Done</span>
            </div>

            <div class="lp-task done">
              <span class="lp-task-thumb"><i data-lucide="check"></i></span>
              <span class="lp-task-lines"><span class="l w-55"></span><span class="l s w-35"></span></span>
              <span class="lp-task-state">Done</span>
            </div>

            <div class="lp-task active">
              <span class="lp-task-thumb"><i data-lucide="play"></i></span>
              <span class="lp-task-lines">
                <span class="l w-80"></span>
                <span class="lp-task-bar"><span></span></span>
              </span>
              <span class="lp-task-state now">Playing</span>
            </div>

            <div class="lp-task">
              <span class="lp-task-thumb"><i data-lucide="clock"></i></span>
              <span class="lp-task-lines"><span class="l w-65"></span><span class="l s w-45"></span></span>
              <span class="lp-task-state">Queued</span>
            </div>
          </div>

          <div class="lp-app-foot">
            <span class="lp-app-foot-label"><i data-lucide="coins"></i> Daily set</span>
            <span class="lp-app-foot-bar"><span></span></span>
          </div>
        </div>

        <div class="lp-float" aria-hidden="true">
          <span class="lp-float-icon"><i data-lucide="check"></i></span>
          <span class="lp-float-lines"><span class="l w-90"></span><span class="l s w-60"></span></span>
        </div>
      </div>
    </div>
  </div>
</section>

<div class="lp-strip">
  <div class="container">
    <ul>
      <li><i data-lucide="shield-check"></i> Login-only account</li>
      <li><i data-lucide="eye"></i> Visible payout status</li>
      <li><i data-lucide="smartphone"></i> Works on any phone</li>
      <li><i data-lucide="life-buoy"></i> Support run by people</li>
    </ul>
  </div>
</div>

<section class="lp-section">
  <div class="container">
    <header class="lp-head center reveal" data-reveal-order="0">
      <span class="lp-eyebrow plain">What you need</span>
      <h2>Three things, and you have all of them</h2>
      <p>No fee to join, no equipment to buy, no approval queue to sit in before you can look around.</p>
    </header>

    <div class="lp-ways">
      <?php foreach ($needs as $i => $n): ?>
        <?php list($icon, $tone, $title, $text) = $n; ?>
        <article class="lp-way tone-<?php echo $tone; ?> reveal" data-reveal-order="<?php echo $i + 1; ?>">
          <span class="lp-way-icon"><i data-lucide="<?php echo $icon; ?>"></i></span>
          <h3><?php echo $title; ?></h3>
          <p><?php echo $text; ?></p>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="lp-section lp-section-alt">
  <div class="container">
    <header class="lp-head center reveal" data-reveal-order="0">
      <span class="lp-eyebrow plain">Ways to earn</span>
      <h2>Three ways the balance moves</h2>
      <p>All of them run from the same dashboard. No extra apps, no separate accounts.</p>
    </header>

    <div class="lp-ways">
      <?php foreach ($ways as $i => $w): ?>
        <?php list($icon, $tone, $title, $text) = $w; ?>
        <article class="lp-way tone-<?php echo $tone; ?> reveal" data-reveal-order="<?php echo $i + 1; ?>">
          <span class="lp-way-icon"><i data-lucide="<?php echo $icon; ?>"></i></span>
          <h3><?php echo $title; ?></h3>
          <p><?php echo $text; ?></p>
          <a class="lp-link" href="<?php echo base_url('register'); ?>">Get started <i data-lucide="arrow-right"></i></a>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="lp-section">
  <div class="container">
    <header class="lp-head center reveal" data-reveal-order="0">
      <span class="lp-eyebrow plain">How it works</span>
      <h2>Four steps to your first payout</h2>
      <p>No paperwork, no waiting on a queue you cannot see.</p>
    </header>

    <ol class="lp-flow">
      <?php foreach ($steps as $i => $s): ?>
        <?php list($icon, $title, $text) = $s; ?>
        <li class="reveal" data-reveal-order="<?php echo $i + 1; ?>">
          <span class="lp-flow-mark">
            <i data-lucide="<?php echo $icon; ?>"></i>
            <span class="lp-flow-n"><?php echo $i + 1; ?></span>
          </span>
          <h3><?php echo $title; ?></h3>
          <p><?php echo $text; ?></p>
        </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>

<section class="lp-section lp-section-alt">
  <div class="container">
    <div class="lp-split">
      <div class="lp-split-copy reveal" data-reveal-order="0">
        <span class="lp-eyebrow plain">Payouts</span>
        <h2>You can always see where your money is</h2>
        <p>
          Every withdrawal moves through the same stages, and each one appears on screen the
          moment it happens. No guessing, and no support ticket needed to find out where a
          request got to.
        </p>
        <ul class="lp-checks">
          <li><i data-lucide="check"></i> Request it from your account page</li>
          <li><i data-lucide="check"></i> Watch each stage as it clears</li>
          <li><i data-lucide="check"></i> Full history kept for you</li>
        </ul>
        <a href="<?php echo base_url('register'); ?>" class="lp-btn lp-btn-primary">
          Create free account <i data-lucide="arrow-right"></i>
        </a>
      </div>

      <div class="lp-split-art reveal" data-reveal-order="1">
        <?php /* Mock of a payout timeline. Stages only - never amounts. */ ?>
        <div class="lp-app" aria-hidden="true">
          <div class="lp-app-bar">
            <span class="lp-app-name">Withdrawal</span>
            <span class="lp-app-chip"><span class="dot dot-warn pulse"></span> In review</span>
          </div>

          <ol class="lp-timeline">
            <li class="done">
              <span class="lp-tl-mark"><i data-lucide="check"></i></span>
              <span class="lp-tl-text"><strong>Requested</strong><span class="l s w-50"></span></span>
            </li>
            <li class="now">
              <span class="lp-tl-mark"><i data-lucide="loader"></i></span>
              <span class="lp-tl-text"><strong>Under review</strong><span class="l s w-65"></span></span>
            </li>
            <li>
              <span class="lp-tl-mark"><i data-lucide="send"></i></span>
              <span class="lp-tl-text"><strong>Sent</strong><span class="l s w-40"></span></span>
            </li>
            <li>
              <span class="lp-tl-mark"><i data-lucide="wallet"></i></span>
              <span class="lp-tl-text"><strong>Received</strong><span class="l s w-45"></span></span>
            </li>
          </ol>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="lp-section">
  <div class="container">
    <header class="lp-head center reveal" data-reveal-order="0">
      <span class="lp-eyebrow plain">Why here</span>
      <h2>The boring parts, done properly</h2>
      <p>Nothing flashy. The things that make a daily routine bearable.</p>
    </header>

    <div class="lp-why">
      <?php foreach ($why as $i => $w): ?>
        <?php list($icon, $title, $text) = $w; ?>
        <div class="lp-why-item reveal" data-reveal-order="<?php echo $i + 1; ?>">
          <span class="lp-why-icon"><i data-lucide="<?php echo $icon; ?>"></i></span>
          <div>
            <h3><?php echo $title; ?></h3>
            <p><?php echo $text; ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="lp-section lp-section-alt">
  <div class="container">
    <header class="lp-head center reveal" data-reveal-order="0">
      <span class="lp-eyebrow plain">Good to know</span>
      <h2>The rules, before you sign up rather than after</h2>
      <p>Four things that catch people out. None of them change once you are in.</p>
    </header>

    <div class="lp-why lp-rules">
      <?php foreach ($rules as $i => $r): ?>
        <?php list($icon, $title, $text) = $r; ?>
        <div class="lp-why-item reveal" data-reveal-order="<?php echo $i + 1; ?>">
          <span class="lp-why-icon"><i data-lucide="<?php echo $icon; ?>"></i></span>
          <div>
            <h3><?php echo $title; ?></h3>
            <p><?php echo $text; ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="lp-section">
  <div class="container">
    <div class="lp-faq-wrap">
      <header class="lp-head reveal" data-reveal-order="0">
        <span class="lp-eyebrow plain">FAQ</span>
        <h2>Questions people ask first</h2>
        <p>Anything not covered here, the contact links below reach a person.</p>
      </header>

      <div class="lp-faq">
        <?php foreach ($faqs as $i => $q): ?>
          <details class="lp-q reveal" data-reveal-order="<?php echo $i + 1; ?>">
            <summary>
              <span><?php echo $q[0]; ?></span>
              <i data-lucide="plus"></i>
            </summary>
            <div class="lp-a"><?php echo $q[1]; ?></div>
          </details>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<section class="lp-cta-section">
  <div class="container">
    <div class="lp-cta reveal" data-reveal-order="0">
      <span class="lp-cta-bg" aria-hidden="true"></span>
      <div class="lp-cta-inner">
        <h2>Today's set is waiting</h2>
        <p>Create an account, get set up, and start clearing your daily ads.</p>

        <div class="lp-actions">
          <a class="lp-btn lp-btn-light lp-btn-lg" href="<?php echo base_url('register'); ?>">
            Start earning <i data-lucide="arrow-right"></i>
          </a>
        </div>

        <p class="lp-form-note">Free to join. Already a member? <a href="<?php echo base_url('login'); ?>">Sign in</a>.</p>
      </div>
    </div>
  </div>
</section>
