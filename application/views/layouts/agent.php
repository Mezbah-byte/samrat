<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo html_escape($page_title ? $page_title.' - Agent' : 'Agent'); ?></title>
<link rel="icon" href="<?php echo logo_url(); ?>">
<link rel="stylesheet" href="<?php echo base_url('assets/vendor/bootstrap/css/bootstrap.min.css'); ?>">
<link rel="stylesheet" href="<?php echo base_url('assets/vendor/bootstrap-icons/font/bootstrap-icons.min.css'); ?>">
<link rel="stylesheet" href="<?php echo base_url('assets/css/app.css').'?v='.filemtime(FCPATH.'assets/css/app.css'); ?>">
</head>
<body>

<?php $this->load->view('partials/impersonate_banner'); ?>

<aside class="app-sidebar" id="appSidebar">
  <div class="brand">
    <i class="bi bi-person-vcard-fill"></i>
    <span class="text-truncate">Agent Panel</span>
  </div>

  <div class="nav-section">Overview</div>
  <nav class="nav flex-column">
    <a class="nav-link <?php echo active_if($active_menu, 'dashboard'); ?>" href="<?php echo base_url('agent/dashboard'); ?>"><i class="bi bi-speedometer2"></i> Dashboard</a>
  </nav>

  <div class="nav-section">My Team</div>
  <nav class="nav flex-column">
    <a class="nav-link <?php echo active_if($active_menu, 'team'); ?>" href="<?php echo base_url('agent/team'); ?>"><i class="bi bi-people-fill"></i> Members</a>
    <a class="nav-link <?php echo active_if($active_menu, 'deposits'); ?>" href="<?php echo base_url('agent/deposits'); ?>">
      <i class="bi bi-inbox-fill"></i> Deposits
      <?php if ( ! empty($agent_stats['deposits'])): ?><span class="badge text-bg-warning ms-auto"><?php echo (int) $agent_stats['deposits']; ?></span><?php endif; ?>
    </a>
    <a class="nav-link <?php echo active_if($active_menu, 'withdrawals'); ?>" href="<?php echo base_url('agent/withdrawals'); ?>">
      <i class="bi bi-cash-stack"></i> Withdrawals
      <?php if ( ! empty($agent_stats['withdrawals'])): ?><span class="badge text-bg-warning ms-auto"><?php echo (int) $agent_stats['withdrawals']; ?></span><?php endif; ?>
    </a>
  </nav>

  <div class="nav-section">Account</div>
  <nav class="nav flex-column">
    <a class="nav-link <?php echo active_if($active_menu, 'earnings'); ?>" href="<?php echo base_url('agent/earnings'); ?>"><i class="bi bi-coin"></i> Earnings</a>
    <a class="nav-link <?php echo active_if($active_menu, 'profile'); ?>" href="<?php echo base_url('agent/profile'); ?>"><i class="bi bi-person"></i> My Profile</a>
    <a class="nav-link" href="<?php echo base_url('agent/logout'); ?>"><i class="bi bi-box-arrow-right"></i> Logout</a>
  </nav>
  <div class="p-3"></div>
</aside>

<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<div class="app-main">
  <header class="app-topbar">
    <button class="btn btn-sm btn-outline-secondary d-lg-none" id="sidebarToggle"><i class="bi bi-list"></i></button>
    <div class="fw-semibold"><?php echo html_escape($page_title ?: 'Dashboard'); ?></div>
    <div class="ms-auto d-flex align-items-center gap-3">
      <a href="<?php echo base_url(); ?>" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-box-arrow-up-right"></i> View Site</a>
      <div class="dropdown">
        <a href="#" class="d-flex align-items-center gap-2 text-decoration-none text-dark dropdown-toggle" data-bs-toggle="dropdown">
          <img src="<?php echo base_url('assets/img/avatar.svg'); ?>" class="avatar-sm" alt="">
          <span class="d-none d-md-inline small fw-semibold"><?php echo html_escape($agent->name); ?></span>
          <span class="badge text-bg-secondary d-none d-lg-inline">Agent</span>
        </a>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><a class="dropdown-item" href="<?php echo base_url('agent/profile'); ?>"><i class="bi bi-person"></i> My Profile</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item text-danger" href="<?php echo base_url('agent/logout'); ?>"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
        </ul>
      </div>
    </div>
  </header>

  <div class="app-content">
    <?php echo flash(); ?>
    <?php $this->load->view($_content); ?>
  </div>

  <footer class="app-footer">
    <?php echo html_escape($company_name); ?> agent &mdash; <?php echo date('d M Y'); ?>
  </footer>
</div>

<script src="<?php echo base_url('assets/vendor/bootstrap/js/bootstrap.bundle.min.js'); ?>"></script>
<script src="<?php echo base_url('assets/js/app.js').'?v='.filemtime(FCPATH.'assets/js/app.js'); ?>"></script>
</body>
</html>
