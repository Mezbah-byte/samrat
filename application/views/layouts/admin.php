<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo html_escape($page_title ? $page_title.' - Admin' : 'Admin'); ?></title>
<link rel="icon" href="<?php echo logo_url(); ?>">
<link rel="stylesheet" href="<?php echo base_url('assets/vendor/bootstrap/css/bootstrap.min.css'); ?>">
<link rel="stylesheet" href="<?php echo base_url('assets/vendor/bootstrap-icons/font/bootstrap-icons.min.css'); ?>">
<link rel="stylesheet" href="<?php echo base_url('assets/css/app.css'); ?>">
</head>
<body>

<aside class="app-sidebar" id="appSidebar">
  <div class="brand">
    <i class="bi bi-shield-lock-fill"></i>
    <span class="text-truncate">Admin Panel</span>
  </div>

  <div class="nav-section">Overview</div>
  <nav class="nav flex-column">
    <a class="nav-link <?php echo active_if($active_menu, 'dashboard'); ?>" href="<?php echo base_url('admin/dashboard'); ?>"><i class="bi bi-speedometer2"></i> Dashboard</a>
  </nav>

  <div class="nav-section">Operations</div>
  <nav class="nav flex-column">
    <a class="nav-link <?php echo active_if($active_menu, 'deposits'); ?>" href="<?php echo base_url('admin/deposits'); ?>">
      <i class="bi bi-inbox-fill"></i> Deposits
      <?php if ( ! empty($admin_stats['deposits'])): ?><span class="badge text-bg-warning ms-auto"><?php echo (int) $admin_stats['deposits']; ?></span><?php endif; ?>
    </a>
    <a class="nav-link <?php echo active_if($active_menu, 'withdrawals'); ?>" href="<?php echo base_url('admin/withdrawals'); ?>">
      <i class="bi bi-cash-stack"></i> Withdrawals
      <?php if ( ! empty($admin_stats['withdrawals'])): ?><span class="badge text-bg-warning ms-auto"><?php echo (int) $admin_stats['withdrawals']; ?></span><?php endif; ?>
    </a>
    <a class="nav-link <?php echo active_if($active_menu, 'investments'); ?>" href="<?php echo base_url('admin/investments'); ?>"><i class="bi bi-graph-up-arrow"></i> Investments</a>
    <a class="nav-link <?php echo active_if($active_menu, 'transactions'); ?>" href="<?php echo base_url('admin/transactions'); ?>"><i class="bi bi-list-columns-reverse"></i> Transactions</a>
    <a class="nav-link <?php echo active_if($active_menu, 'referrals'); ?>" href="<?php echo base_url('admin/referrals'); ?>"><i class="bi bi-diagram-3"></i> Referrals</a>
  </nav>

  <div class="nav-section">Manage</div>
  <nav class="nav flex-column">
    <a class="nav-link <?php echo active_if($active_menu, 'users'); ?>" href="<?php echo base_url('admin/users'); ?>"><i class="bi bi-people-fill"></i> Users</a>
    <a class="nav-link <?php echo active_if($active_menu, 'packages'); ?>" href="<?php echo base_url('admin/packages'); ?>"><i class="bi bi-box-seam"></i> Packages</a>
    <a class="nav-link <?php echo active_if($active_menu, 'deposit_methods'); ?>" href="<?php echo base_url('admin/deposit-methods'); ?>"><i class="bi bi-wallet2"></i> Wallets</a>
    <a class="nav-link <?php echo active_if($active_menu, 'ads'); ?>" href="<?php echo base_url('admin/ads'); ?>"><i class="bi bi-badge-ad"></i> Ads</a>
    <a class="nav-link <?php echo active_if($active_menu, 'notices'); ?>" href="<?php echo base_url('admin/notices'); ?>"><i class="bi bi-megaphone"></i> Notices</a>
    <a class="nav-link <?php echo active_if($active_menu, 'notifications'); ?>" href="<?php echo base_url('admin/notifications'); ?>"><i class="bi bi-bell"></i> Notifications</a>
  </nav>

  <div class="nav-section">System</div>
  <nav class="nav flex-column">
    <a class="nav-link <?php echo active_if($active_menu, 'settings'); ?>" href="<?php echo base_url('admin/settings'); ?>"><i class="bi bi-sliders"></i> Settings</a>
    <?php if ($admin->role === 'super_admin'): ?>
      <a class="nav-link <?php echo active_if($active_menu, 'admins'); ?>" href="<?php echo base_url('admin/admins'); ?>"><i class="bi bi-person-badge"></i> Admins</a>
    <?php endif; ?>
    <a class="nav-link <?php echo active_if($active_menu, 'logs'); ?>" href="<?php echo base_url('admin/logs'); ?>"><i class="bi bi-clock-history"></i> Activity Log</a>
    <a class="nav-link" href="<?php echo base_url('admin/logout'); ?>"><i class="bi bi-box-arrow-right"></i> Logout</a>
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
          <img src="<?php echo upload_url('avatars', $admin->avatar, 'assets/img/avatar.svg'); ?>" class="avatar-sm" alt="">
          <span class="d-none d-md-inline small fw-semibold"><?php echo html_escape($admin->name); ?></span>
          <span class="badge text-bg-secondary d-none d-lg-inline"><?php echo html_escape(str_replace('_', ' ', $admin->role)); ?></span>
        </a>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><a class="dropdown-item" href="<?php echo base_url('admin/admins/profile'); ?>"><i class="bi bi-person"></i> My Profile</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item text-danger" href="<?php echo base_url('admin/logout'); ?>"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
        </ul>
      </div>
    </div>
  </header>

  <div class="app-content">
    <?php echo flash(); ?>
    <?php $this->load->view($_content); ?>
  </div>

  <footer class="app-footer">
    <?php echo html_escape($company_name); ?> admin &mdash; <?php echo date('d M Y'); ?>
  </footer>
</div>

<script src="<?php echo base_url('assets/vendor/bootstrap/js/bootstrap.bundle.min.js'); ?>"></script>
<script src="<?php echo base_url('assets/js/app.js'); ?>"></script>
</body>
</html>
