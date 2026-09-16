<?php
/**
 * Agent panel shell.
 *
 * Same design system as the user panel - ui.css + shell.css, lucide icons,
 * theme-aware - rather than the plain bootstrap admin chrome. An agent is a
 * front-of-house operator handling other people's money all day; the panel
 * should read like a product, not like a back office.
 *
 * The Money section only exists while the float system is on. With it off the
 * panel is the review-only one it has always been.
 */
$float_on = ! empty($float_on);
$bal      = ! empty($agent_wallets_balances)
	? $agent_wallets_balances
	: array('deposit' => 0, 'withdraw' => 0, 'commission' => 0);

$nav = array(
	'Overview' => array(
		array('dashboard', 'agent/dashboard', 'layout-dashboard', 'Dashboard'),
	),
);

if ($float_on)
{
	$nav['Money'] = array(
		array('req_deposits',    'agent/requests/deposits',    'arrow-down-to-line', 'Deposit Requests',
			! empty($agent_stats['req_deposits']) ? (int) $agent_stats['req_deposits'] : 0, 'alert'),
		array('req_withdrawals', 'agent/requests/withdrawals', 'arrow-up-from-line', 'Withdraw Requests',
			! empty($agent_stats['req_withdrawals']) ? (int) $agent_stats['req_withdrawals'] : 0, 'alert'),
		array('float',   'agent/float',   'coins',   'Buy Float',
			! empty($agent_stats['float']) ? (int) $agent_stats['float'] : 0, ''),
		array('payouts', 'agent/payouts', 'banknote', 'Cash Out',
			! empty($agent_stats['payouts']) ? (int) $agent_stats['payouts'] : 0, ''),
		array('wallets', 'agent/wallets', 'wallet',   'My Wallets'),
		array('ledger',  'agent/ledger',  'receipt-text', 'Wallet Ledger'),
	);
}

$nav['My Team'] = array(
	array('team',        'agent/team',        'users',      'Members'),
	array('deposits',    'agent/deposits',    'inbox',      'Team Deposits',
		! empty($agent_stats['deposits']) ? (int) $agent_stats['deposits'] : 0, ''),
	array('withdrawals', 'agent/withdrawals', 'hand-coins', 'Team Withdrawals',
		! empty($agent_stats['withdrawals']) ? (int) $agent_stats['withdrawals'] : 0, ''),
);

$nav['Account'] = array(
	array('earnings', 'agent/earnings', 'trending-up', 'Earnings'),
	array('profile',  'agent/profile',  'user-cog',    'My Profile'),
);
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo html_escape($page_title ? $page_title.' - Agent' : 'Agent Panel'); ?></title>
<link rel="icon" href="<?php echo logo_url(); ?>">
<link rel="stylesheet" href="<?php echo base_url('assets/vendor/bootstrap/css/bootstrap.min.css'); ?>">
<link rel="stylesheet" href="<?php echo base_url('assets/css/fonts.css'); ?>">
<link rel="stylesheet" href="<?php echo base_url('assets/css/ui.css').'?v='.filemtime(FCPATH.'assets/css/ui.css'); ?>">
<link rel="stylesheet" href="<?php echo base_url('assets/css/shell.css').'?v='.filemtime(FCPATH.'assets/css/shell.css'); ?>">
<script>
// Before first paint: honour the stored theme so a light-theme agent never
// sees a dark flash, and mark the document scripted so the entrance
// animations only hide content when JS is there to reveal it.
document.documentElement.classList.add('js');
try { document.documentElement.setAttribute('data-theme', localStorage.getItem('samrat.theme') === 'light' ? 'light' : 'dark'); } catch (e) {}
</script>
</head>
<body class="ui">

<?php $this->load->view('partials/impersonate_banner'); ?>

<div class="bg-fx" aria-hidden="true">
  <span class="fx-sheen"></span>
  <span class="fx-sheen fx-sheen-2"></span>
  <canvas class="fx-net" id="fxNet"></canvas>
</div>

<div class="app">

  <aside class="side" id="side">
    <div class="side-brand">
      <span class="mark"><img src="<?php echo logo_url(); ?>" alt="" width="40" height="40"></span>
      <span class="words">
        <span class="name d-block"><?php echo html_escape($company_name); ?></span>
        <span class="tag">Agent Panel</span>
      </span>
    </div>

    <div class="side-scroll">
      <?php foreach ($nav as $section => $links): ?>
        <div class="side-sec"><?php echo $section; ?></div>
        <nav>
          <?php foreach ($links as $l): ?>
            <?php list($key, $route, $icon, $label) = $l; ?>
            <?php $count = isset($l[4]) ? $l[4] : 0; $tone = isset($l[5]) ? $l[5] : ''; ?>
            <a class="side-link <?php echo active_if($active_menu, $key); ?>"
               href="<?php echo base_url($route); ?>" data-label="<?php echo html_escape($label); ?>">
              <i data-lucide="<?php echo $icon; ?>"></i>
              <span class="label"><?php echo html_escape($label); ?></span>
              <?php if ($count > 0): ?><span class="count <?php echo $tone; ?>"><?php echo $count; ?></span><?php endif; ?>
            </a>
          <?php endforeach; ?>
        </nav>
      <?php endforeach; ?>

      <div class="side-sec">Session</div>
      <nav>
        <a class="side-link danger" href="<?php echo base_url('agent/logout'); ?>" data-label="Logout">
          <i data-lucide="log-out"></i>
          <span class="label">Logout</span>
        </a>
      </nav>
    </div>
  </aside>

  <div class="side-backdrop" id="sideBackdrop"></div>

  <div class="main">

    <header class="topbar">
      <button class="btn btn-ghost btn-icon" data-sidebar-collapse aria-label="Toggle sidebar">
        <i data-lucide="panel-left"></i>
      </button>

      <div class="page-name"><?php echo html_escape($page_title ?: 'Dashboard'); ?></div>

      <div class="tools">
        <?php if ($float_on): ?>
          <div class="balance-pill d-none d-sm-flex" title="Float you can settle deposits with">
            <span class="icon-tile grad-primary"><i data-lucide="coins"></i></span>
            <span>
              <span class="cap d-block">Float</span>
              <span class="amt"><?php echo money($bal['deposit']); ?></span>
            </span>
          </div>
          <div class="balance-pill d-none d-xl-flex" title="Collected from withdrawals you paid, plus commission">
            <span class="icon-tile grad-success"><i data-lucide="piggy-bank"></i></span>
            <span>
              <span class="cap d-block">To cash out</span>
              <span class="amt"><?php echo money($bal['withdraw'] + $bal['commission']); ?></span>
            </span>
          </div>
        <?php endif; ?>

        <button class="btn btn-ghost btn-icon" data-theme-toggle aria-label="Switch theme">
          <i data-lucide="sun" data-theme-icon></i>
        </button>

        <div class="dropdown">
          <a href="#" class="user-chip dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
            <img src="<?php echo base_url('assets/img/avatar.svg'); ?>" alt="">
            <span class="d-none d-md-inline small fw-semibold"><?php echo html_escape($agent->username); ?></span>
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="<?php echo base_url('agent/profile'); ?>"><i data-lucide="user"></i> My Profile</a></li>
            <?php if ($float_on): ?>
              <li><a class="dropdown-item" href="<?php echo base_url('agent/wallets'); ?>"><i data-lucide="wallet"></i> My Wallets</a></li>
              <li><a class="dropdown-item" href="<?php echo base_url('agent/ledger'); ?>"><i data-lucide="receipt-text"></i> Wallet Ledger</a></li>
            <?php endif; ?>
            <li><a class="dropdown-item" href="<?php echo base_url(); ?>" target="_blank"><i data-lucide="external-link"></i> View Site</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-bad" href="<?php echo base_url('agent/logout'); ?>"><i data-lucide="log-out"></i> Logout</a></li>
          </ul>
        </div>
      </div>
    </header>

    <main class="content">
      <?php echo flash(); ?>
      <?php $this->load->view($_content); ?>
    </main>

    <footer class="foot">
      &copy; <?php echo date('Y'); ?> <?php echo html_escape($company_name); ?>. <?php echo html_escape(setting('footer_text', '')); ?>
    </footer>
  </div>
</div>

<div class="toast-stack" id="toastStack" aria-live="polite"></div>

<script src="<?php echo base_url('assets/vendor/bootstrap/js/bootstrap.bundle.min.js'); ?>"></script>
<script src="<?php echo base_url('assets/vendor/lucide/lucide.min.js'); ?>"></script>
<script src="<?php echo base_url('assets/js/app.js').'?v='.filemtime(FCPATH.'assets/js/app.js'); ?>"></script>
<script src="<?php echo base_url('assets/js/ui.js').'?v='.filemtime(FCPATH.'assets/js/ui.js'); ?>"></script>
<script src="<?php echo base_url('assets/js/bg-net.js').'?v='.filemtime(FCPATH.'assets/js/bg-net.js'); ?>"></script>
</body>
</html>
