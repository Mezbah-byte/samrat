<?php
/**
 * The folder pane, shared by the list and the message screens.
 *
 * Expects $account, $folders and $folder.
 */
$icons = array(
	'inbox'   => 'inbox',
	'sent'    => 'send',
	'drafts'  => 'file-earmark-text',
	'trash'   => 'trash',
	'junk'    => 'shield-exclamation',
	'archive' => 'archive',
);
?>
<div class="card mail-folders">
  <div class="card-header py-2 d-flex justify-content-between align-items-center">
    <span class="text-truncate" title="<?php echo html_escape($account->email); ?>">
      <i class="bi bi-person-circle"></i> <?php echo html_escape($account->email); ?>
    </span>
    <a href="<?php echo base_url('admin/mail'); ?>" class="btn btn-sm btn-outline-secondary" title="All mailboxes"><i class="bi bi-arrow-left-right"></i></a>
  </div>

  <?php if (admin_can('mail.send')): ?>
  <div class="p-2 border-bottom">
    <a href="<?php echo base_url('admin/mail/compose/'.$account->id); ?>" class="btn btn-sm btn-primary w-100">
      <i class="bi bi-pencil-square"></i> Compose
    </a>
  </div>
  <?php endif; ?>

  <nav class="nav flex-column mail-folder-nav">
    <?php foreach ($folders as $f): ?>
      <?php $icon = isset($icons[$f['special']]) ? $icons[$f['special']] : 'folder'; ?>
      <?php if ( ! $f['selectable']): ?>
        <span class="nav-link disabled text-muted" style="padding-left: <?php echo 0.75 + $f['depth'] * 0.75; ?>rem">
          <i class="bi bi-folder2"></i> <?php echo html_escape($f['name']); ?>
        </span>
      <?php else: ?>
        <a class="nav-link d-flex align-items-center <?php echo $f['path'] === $folder ? 'active' : ''; ?>"
           style="padding-left: <?php echo 0.75 + $f['depth'] * 0.75; ?>rem"
           href="<?php echo base_url('admin/mail/open/'.$account->id.'?'.http_build_query(array('f' => $f['path']))); ?>">
          <i class="bi bi-<?php echo $icon; ?> me-2"></i>
          <span class="text-truncate flex-grow-1"><?php echo html_escape($f['name']); ?></span>
          <?php if ($f['unseen'] > 0): ?>
            <span class="badge text-bg-primary ms-2"><?php echo (int) $f['unseen']; ?></span>
          <?php endif; ?>
        </a>
      <?php endif; ?>
    <?php endforeach; ?>
  </nav>
</div>
