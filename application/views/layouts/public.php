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
<link rel="stylesheet" href="<?php echo base_url('assets/css/site.css').'?v='.filemtime(FCPATH.'assets/css/site.css'); ?>">
<?php if ($active_menu === 'home'): /* Landing-only decoration - no other page uses it. */ ?>
  <link rel="stylesheet" href="<?php echo base_url('assets/css/landing.css').'?v='.filemtime(FCPATH.'assets/css/landing.css'); ?>">
<?php endif; ?>
<script>
// Runs before first paint: picks the stored theme so a light-theme visitor never
// sees a dark flash, and marks the document as scripted so the entrance
// animations only hide content when JS is actually there to reveal it.
document.documentElement.classList.add('js');
try { document.documentElement.setAttribute('data-theme', localStorage.getItem('samrat.theme') === 'light' ? 'light' : 'dark'); } catch (e) {}
</script>
</head>
<body class="ui">

<nav class="site-nav navbar navbar-expand-lg">
  <div class="container">
    <a class="navbar-brand" href="<?php echo base_url(); ?>">
      <img src="<?php echo logo_url(); ?>" alt="" width="32" height="32">
      <?php echo html_escape($company_name); ?>
    </a>

    <button class="btn btn-ghost btn-icon navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMain" aria-label="Menu">
      <i data-lucide="menu"></i>
    </button>

    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
        <li class="nav-item"><a class="nav-link <?php echo active_if($active_menu, 'home'); ?>" href="<?php echo base_url(); ?>">Home</a></li>
        <li class="nav-item"><a class="nav-link <?php echo active_if($active_menu, 'plans'); ?>" href="<?php echo base_url('plans'); ?>">Plans</a></li>
        <li class="nav-item"><a class="nav-link <?php echo active_if($active_menu, 'notices'); ?>" href="<?php echo base_url('notices'); ?>">Notices</a></li>
        <li class="nav-item"><a class="nav-link <?php echo active_if($active_menu, 'about'); ?>" href="<?php echo base_url('about'); ?>">About</a></li>

        <li class="nav-item ms-lg-2">
          <button class="btn btn-ghost btn-icon" data-theme-toggle aria-label="Switch theme">
            <i data-lucide="sun" data-theme-icon></i>
          </button>
        </li>

        <?php if ( ! empty($current_user)): ?>
          <li class="nav-item"><a class="btn btn-grad px-3" href="<?php echo base_url('dashboard'); ?>"><i data-lucide="layout-dashboard"></i> Dashboard</a></li>
        <?php else: ?>
          <li class="nav-item"><a class="btn btn-ghost px-3" href="<?php echo base_url('login'); ?>">Login</a></li>
          <li class="nav-item"><a class="btn btn-grad px-3" href="<?php echo base_url('register'); ?>">Register</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<?php if ( ! empty($ticker_notices)): ?>
<div class="site-strip">
  <div class="container inner">
    <span class="tag"><i data-lucide="megaphone"></i> Notice</span>
    <div class="text-truncate">
      <?php foreach ($ticker_notices as $i => $n): ?>
        <?php if ($i > 0): ?><span class="text-dim mx-2">&bull;</span><?php endif; ?>
        <a href="<?php echo base_url('notices/'.$n->slug); ?>"><?php echo html_escape($n->title); ?></a>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<main>
  <?php if ($msg = flash()): ?>
    <div class="container pt-3"><?php echo $msg; ?></div>
  <?php endif; ?>
  <?php $this->load->view($_content); ?>
</main>

<footer class="site-foot">
  <div class="container d-flex flex-wrap justify-content-between gap-3">
    <div>&copy; <?php echo date('Y'); ?> <?php echo html_escape($company_name); ?>. <?php echo html_escape(setting('footer_text', '')); ?></div>
    <div class="d-flex flex-wrap gap-3">
      <?php if ($mail = setting('support_email')): ?>
        <a href="mailto:<?php echo html_escape($mail); ?>"><i data-lucide="mail"></i> <?php echo html_escape($mail); ?></a>
      <?php endif; ?>
      <?php if ($tg = setting('support_telegram')): ?>
        <a href="<?php echo html_escape($tg); ?>" target="_blank" rel="noopener"><i data-lucide="send"></i> Telegram</a>
      <?php endif; ?>
    </div>
  </div>
</footer>

<div class="toast-stack" id="toastStack" aria-live="polite"></div>

<script src="<?php echo base_url('assets/vendor/bootstrap/js/bootstrap.bundle.min.js'); ?>"></script>
<script src="<?php echo base_url('assets/vendor/lucide/lucide.min.js'); ?>"></script>
<script src="<?php echo base_url('assets/js/app.js').'?v='.filemtime(FCPATH.'assets/js/app.js'); ?>"></script>
<script src="<?php echo base_url('assets/js/ui.js').'?v='.filemtime(FCPATH.'assets/js/ui.js'); ?>"></script>
</body>
</html>
