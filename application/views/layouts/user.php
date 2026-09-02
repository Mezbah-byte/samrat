<?php
$nav = array(
	'Main' => array(
		array('dashboard',     'dashboard',     'layout-dashboard', 'Dashboard'),
		array('packages',      'packages',      'box',              'Packages'),
		array('ads',           'ads',           'play-circle',      'Daily Ads', ! empty($ads_remaining) ? (int) $ads_remaining : 0, ''),
	),
	'Money' => array(
		array('deposit',       'deposit',       'wallet',           'Deposit'),
		array('withdraw',      'withdraw',      'banknote',         'Withdraw'),
		array('transactions',  'transactions',  'receipt-text',     'Transactions'),
		array('referral',      'referral',      'users',            'Referral'),
		array('team_bonus',    'team-bonus',    'trophy',           'Team Bonus', ! empty($team_bonus_claimable) ? (int) $team_bonus_claimable : 0, 'alert'),
	),
	'Account' => array(
		array('profile',       'profile',       'user-cog',         'Profile'),
		// Always listed while the panel is on: the page itself explains where
		// the user stands. Gating on a live team count would run a downline
		// walk on every single page render.
		array('agentship',     'agentship',     'badge-check',      'Agentship'),
		array('notifications', 'notifications', 'bell',             'Notifications', ! empty($unread_count) ? (int) $unread_count : 0, 'alert'),
		array('notices',       'notices',       'megaphone',        'Notice Board'),
	),
);

if (setting('agent_panel_enabled', '1') !== '1')
{
	$nav['Account'] = array_values(array_filter($nav['Account'], function ($l) {
		return $l[0] !== 'agentship';
	}));
}

if (setting('team_bonus_enabled', '1') !== '1')
{
	$nav['Money'] = array_values(array_filter($nav['Money'], function ($l) {
		return $l[0] !== 'team_bonus';
	}));
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo html_escape($page_title ? $page_title.' - '.$company_name : $company_name); ?></title>
<link rel="icon" href="<?php echo logo_url(); ?>">
<link rel="stylesheet" href="<?php echo base_url('assets/vendor/bootstrap/css/bootstrap.min.css'); ?>">
<link rel="stylesheet" href="<?php echo base_url('assets/css/fonts.css'); ?>">
<link rel="stylesheet" href="<?php echo base_url('assets/css/ui.css').'?v='.filemtime(FCPATH.'assets/css/ui.css'); ?>">
<link rel="stylesheet" href="<?php echo base_url('assets/css/shell.css').'?v='.filemtime(FCPATH.'assets/css/shell.css'); ?>">
<script>
// Runs before first paint: picks the stored theme so a light-theme user never
// sees a dark flash, and marks the document as scripted so the entrance
// animations only hide content when JS is actually there to reveal it.
document.documentElement.classList.add('js');
try { document.documentElement.setAttribute('data-theme', localStorage.getItem('samrat.theme') === 'light' ? 'light' : 'dark'); } catch (e) {}
</script>
</head>
<body class="ui">

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
        <span class="tag"><?php echo html_escape(setting('company_tagline', 'Investment Platform')); ?></span>
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
        <a class="side-link danger" href="<?php echo base_url('logout'); ?>" data-label="Logout">
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
        <div class="balance-pill d-none d-sm-flex">
          <span class="icon-tile grad-success"><i data-lucide="wallet"></i></span>
          <span>
            <span class="cap d-block">Balance</span>
            <span class="amt"><?php echo money($user->balance); ?></span>
          </span>
        </div>

        <a class="btn btn-ghost btn-icon bell" href="<?php echo base_url('notifications'); ?>" aria-label="Notifications">
          <i data-lucide="bell"></i>
          <?php if ( ! empty($unread_count)): ?><span class="count"><?php echo (int) $unread_count; ?></span><?php endif; ?>
        </a>

        <button class="btn btn-ghost btn-icon" data-theme-toggle aria-label="Switch theme">
          <i data-lucide="sun" data-theme-icon></i>
        </button>

        <div class="dropdown">
          <a href="#" class="user-chip dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
            <img src="<?php echo avatar_url($user->avatar); ?>" alt="">
            <span class="d-none d-md-inline small fw-semibold"><?php echo html_escape($user->username); ?></span>
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="<?php echo base_url('profile'); ?>"><i data-lucide="user"></i> Profile</a></li>
            <li><a class="dropdown-item" href="<?php echo base_url('profile/password'); ?>"><i data-lucide="key-round"></i> Change Password</a></li>
            <li><a class="dropdown-item" href="<?php echo base_url('referral'); ?>"><i data-lucide="users"></i> Referral</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-bad" href="<?php echo base_url('logout'); ?>"><i data-lucide="log-out"></i> Logout</a></li>
          </ul>
        </div>
      </div>
    </header>

    <?php if ( ! empty($ticker_notices)): ?>
      <div class="ticker">
        <span class="ticker-tag"><i data-lucide="megaphone"></i> Notice</span>
        <?php if (count($ticker_notices) < 3): ?>
          <?php /* Too few to scroll without visibly repeating - list them instead. */ ?>
          <div class="ticker-static">
            <?php foreach ($ticker_notices as $n): ?>
              <a href="<?php echo base_url('notices/'.$n->slug); ?>"><?php echo html_escape($n->title); ?></a>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="ticker-view">
            <div class="ticker-track">
              <?php for ($pass = 0; $pass < 2; $pass++): ?>
                <?php foreach ($ticker_notices as $n): ?>
                  <a href="<?php echo base_url('notices/'.$n->slug); ?>" <?php echo $pass ? 'aria-hidden="true" tabindex="-1"' : ''; ?>>
                    <?php echo html_escape($n->title); ?>
                  </a>
                <?php endforeach; ?>
              <?php endfor; ?>
            </div>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

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
<?php if ( ! empty($use_charts)): ?>
  <script src="<?php echo base_url('assets/vendor/chartjs/chart.umd.min.js'); ?>"></script>
  <script src="<?php echo base_url('assets/js/charts.js').'?v='.filemtime(FCPATH.'assets/js/charts.js'); ?>"></script>
<?php endif; ?>
</body>
</html>
