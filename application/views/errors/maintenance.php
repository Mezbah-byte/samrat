<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Maintenance - <?php echo html_escape($company_name); ?></title>
<link rel="stylesheet" href="<?php echo base_url('assets/vendor/bootstrap/css/bootstrap.min.css'); ?>">
<link rel="stylesheet" href="<?php echo base_url('assets/vendor/bootstrap-icons/font/bootstrap-icons.min.css'); ?>">
<link rel="stylesheet" href="<?php echo base_url('assets/css/app.css'); ?>">
</head>
<body>
<div class="auth-wrap">
  <div class="auth-card text-center">
    <i class="bi bi-cone-striped text-warning" style="font-size:3.5rem"></i>
    <h3 class="fw-bold mt-3"><?php echo html_escape($company_name); ?></h3>
    <p class="text-muted"><?php echo html_escape($message); ?></p>
    <a href="<?php echo base_url('admin/login'); ?>" class="btn btn-sm btn-outline-secondary mt-2">Admin login</a>
  </div>
</div>
</body>
</html>
