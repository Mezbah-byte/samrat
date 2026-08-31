<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo html_escape($page_title ? $page_title.' - '.$company_name : $company_name); ?></title>
<link rel="icon" href="<?php echo logo_url(); ?>">
<link rel="stylesheet" href="<?php echo base_url('assets/vendor/bootstrap/css/bootstrap.min.css'); ?>">
<link rel="stylesheet" href="<?php echo base_url('assets/vendor/bootstrap-icons/font/bootstrap-icons.min.css'); ?>">
<link rel="stylesheet" href="<?php echo base_url('assets/css/app.css').'?v='.filemtime(FCPATH.'assets/css/app.css'); ?>">
<link rel="stylesheet" href="<?php echo base_url('assets/css/auth.css').'?v='.filemtime(FCPATH.'assets/css/auth.css'); ?>">
</head>
<body class="auth-lamp" data-on="false">

<div class="auth-stage">
  <div class="auth-shell <?php echo ! empty($wide) ? 'wide' : ''; ?>">

    <div class="lamp-wrapper">
      <svg class="lamp-svg" viewBox="0 0 200 300" xmlns="http://www.w3.org/2000/svg">
        <ellipse class="inner-glow" cx="100" cy="110" rx="60" ry="30" />
        <rect class="lamp-base" x="92" y="100" width="16" height="160" rx="8" />
        <rect class="lamp-base" x="60" y="250" width="80" height="12" rx="6" />
        <g class="pull-cord">
          <line class="cord-line" x1="130" y1="110" x2="130" y2="180" />
          <circle class="cord-bead" cx="130" cy="190" r="6" />
          <circle class="cord-hit" cx="130" cy="190" r="25" fill="transparent"
                  data-click="<?php echo base_url('assets/media/lamp-click.mp3'); ?>"></circle>
        </g>
        <path class="lamp-shade" d="M30 110 C 30 50, 170 50, 170 110 C 170 125, 30 125, 30 110 Z" />
      </svg>
      <span class="lamp-hint">Pull the cord</span>
    </div>

    <div class="auth-panel">
      <a href="<?php echo base_url(); ?>" class="auth-brand">
        <img src="<?php echo logo_url(); ?>" alt="" width="30" height="30">
        <span><?php echo html_escape($company_name); ?></span>
      </a>

      <?php echo flash(); ?>
      <?php $this->load->view($_content); ?>
    </div>

  </div>

  <p class="auth-copyright">
    &copy; <?php echo date('Y'); ?> <?php echo html_escape($company_name); ?>
  </p>
</div>

<script src="<?php echo base_url('assets/vendor/bootstrap/js/bootstrap.bundle.min.js'); ?>"></script>
<script src="<?php echo base_url('assets/vendor/gsap/gsap.min.js'); ?>"></script>
<script src="<?php echo base_url('assets/vendor/gsap/Draggable.min.js'); ?>"></script>
<script src="<?php echo base_url('assets/js/app.js').'?v='.filemtime(FCPATH.'assets/js/app.js'); ?>"></script>
<script src="<?php echo base_url('assets/js/auth-lamp.js').'?v='.filemtime(FCPATH.'assets/js/auth-lamp.js'); ?>"></script>
</body>
</html>
