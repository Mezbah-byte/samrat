<?php if ( ! empty($impersonation)): ?>
<style>
.imp-bar{position:sticky;top:0;z-index:1080;background:linear-gradient(90deg,#b45309,#d97706);color:#fff;
	box-shadow:0 2px 10px rgba(0,0,0,.25);font-size:.8125rem;line-height:1.3}
.imp-bar-inner{display:flex;align-items:center;gap:.6rem;flex-wrap:wrap;
	padding:.45rem .85rem;max-width:1400px;margin:0 auto}
.imp-bar-tag{display:inline-flex;align-items:center;gap:.35rem;font-weight:700;letter-spacing:.04em;
	text-transform:uppercase;font-size:.6875rem;background:rgba(0,0,0,.22);border-radius:999px;padding:.2rem .6rem}
.imp-bar-text{flex:1 1 auto;min-width:12rem}
.imp-bar-text strong{font-weight:700}
.imp-bar form{margin:0}
.imp-bar-btn{display:inline-flex;align-items:center;gap:.35rem;border:1px solid rgba(255,255,255,.65);
	background:rgba(255,255,255,.12);color:#fff;border-radius:.4rem;padding:.25rem .7rem;
	font-size:.8125rem;font-weight:600;cursor:pointer}
.imp-bar-btn:hover{background:#fff;color:#7c2d12}
</style>
<div class="imp-bar" role="status">
  <div class="imp-bar-inner">
    <span class="imp-bar-tag"><i class="bi bi-incognito"></i> Impersonating</span>
    <span class="imp-bar-text">
      Viewing the <?php echo html_escape($impersonation['type']); ?> panel as
      <strong><?php echo html_escape($impersonation['label']); ?></strong>
      &middot; signed in as admin <strong><?php echo html_escape($impersonation['admin_name']); ?></strong>.
      Everything you do here is logged against your admin account.
    </span>
    <?php echo form_open('impersonate/stop'); ?>
      <button type="submit" class="imp-bar-btn"><i class="bi bi-box-arrow-left"></i> Return to admin</button>
    <?php echo form_close(); ?>
  </div>
</div>
<?php endif; ?>
