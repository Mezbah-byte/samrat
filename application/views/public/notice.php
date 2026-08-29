<div class="container section">
  <div class="row g-4">
    <div class="col-lg-8">
      <nav aria-label="breadcrumb" class="reveal" data-reveal-order="0">
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="<?php echo base_url(); ?>">Home</a></li>
          <li class="breadcrumb-item"><a href="<?php echo base_url('notices'); ?>">Notices</a></li>
          <li class="breadcrumb-item active"><?php echo html_escape($notice->title); ?></li>
        </ol>
      </nav>

      <article class="panel reveal" data-reveal-order="1">
        <?php if ($notice->image): ?>
          <img src="<?php echo upload_url('notices', $notice->image); ?>" class="w-100" alt="">
        <?php endif; ?>
        <div class="panel-body">
          <div class="d-flex flex-wrap gap-2 mb-3">
            <span class="chip chip-info"><?php echo html_escape(ucfirst($notice->type)); ?></span>
            <?php if ($notice->is_pinned): ?><span class="chip chip-warn"><i data-lucide="pin"></i> Pinned</span><?php endif; ?>
            <span class="chip chip-mute"><i data-lucide="clock"></i> <?php echo fmt_date($notice->published_at); ?></span>
          </div>
          <h1 class="h3 mb-3"><?php echo html_escape($notice->title); ?></h1>
          <div class="notice-body">
            <?php echo $notice->content; ?>
          </div>
        </div>
      </article>
    </div>

    <div class="col-lg-4">
      <div class="panel reveal" data-reveal-order="2">
        <div class="panel-head"><i data-lucide="megaphone"></i> Recent Notices</div>
        <div class="feed">
          <?php foreach ($recent as $r): ?>
            <a class="feed-item" href="<?php echo base_url('notices/'.$r->slug); ?>">
              <span class="icon-tile sm <?php echo $r->id === $notice->id ? 'grad-primary' : 'grad-teal'; ?>">
                <i data-lucide="<?php echo $r->id === $notice->id ? 'bookmark' : 'megaphone'; ?>"></i>
              </span>
              <div class="feed-main">
                <div class="feed-title"><?php echo html_escape($r->title); ?></div>
                <div class="feed-sub"><?php echo fmt_date($r->published_at, 'd M Y'); ?></div>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>
