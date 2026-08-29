<div class="page-head reveal" data-reveal-order="0">
  <div>
    <h1>Notifications</h1>
    <p class="lede"><?php echo (int) $total; ?> message<?php echo $total == 1 ? '' : 's'; ?><?php echo ! empty($unread_count) ? ', '.(int) $unread_count.' unread' : ''; ?>.</p>
  </div>
  <?php if ( ! empty($unread_count)): ?>
    <a href="<?php echo base_url('notifications/read_all'); ?>" class="btn btn-ghost"><i data-lucide="check-check"></i> Mark all read</a>
  <?php endif; ?>
</div>

<div class="panel reveal" data-reveal-order="1">
  <?php if (empty($rows)): ?>
    <div class="empty-state"><i data-lucide="bell-off"></i>No notifications yet.</div>
  <?php else: ?>
    <div class="feed">
      <?php foreach ($rows as $n): ?>
        <a class="feed-item" href="<?php echo base_url('notifications/read/'.$n->id); ?>">
          <span class="icon-tile sm <?php echo $n->is_read ? 'grad-teal' : 'grad-primary'; ?>">
            <i data-lucide="<?php echo $n->is_read ? 'mail-open' : 'mail'; ?>"></i>
          </span>
          <div class="feed-main">
            <div class="feed-title d-flex align-items-center gap-2">
              <?php if ( ! $n->is_read): ?><span class="dot dot-ok pulse"></span><?php endif; ?>
              <?php echo html_escape($n->title); ?>
            </div>
            <div class="feed-sub"><?php echo html_escape($n->message); ?></div>
          </div>
          <span class="feed-sub text-nowrap"><?php echo fmt_date($n->created_at, 'd M, H:i'); ?></span>
        </a>
      <?php endforeach; ?>
    </div>
    <div class="panel-foot">
      <span><?php echo (int) $total; ?> total</span>
      <?php echo pager(base_url('notifications'), $total, $per_page, $page); ?>
    </div>
  <?php endif; ?>
</div>
