<div class="container section">
  <div class="section-head reveal" data-reveal-order="0">
    <h2>Notice Board</h2>
    <p>Announcements, updates and maintenance windows.</p>
  </div>

  <?php if (empty($notices)): ?>
    <div class="panel"><div class="empty-state"><i data-lucide="inbox"></i>No notices have been published yet.</div></div>
  <?php else: ?>
    <div class="row g-3">
      <?php foreach ($notices as $i => $n): ?>
        <div class="col-md-6">
          <div class="panel lift notice-card reveal" data-reveal-order="<?php echo $i + 1; ?>">
            <?php if ($n->image): ?>
              <img src="<?php echo upload_url('notices', $n->image); ?>" class="cover" alt="">
            <?php endif; ?>
            <div class="panel-body d-flex flex-column flex-fill">
              <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                <span class="chip chip-info"><?php echo html_escape(ucfirst($n->type)); ?></span>
                <?php if ($n->is_pinned): ?><span class="chip chip-warn"><i data-lucide="pin"></i> Pinned</span><?php endif; ?>
              </div>
              <h5 class="mb-2"><a href="<?php echo base_url('notices/'.$n->slug); ?>" class="text-reset"><?php echo html_escape($n->title); ?></a></h5>
              <p class="text-muted small"><?php echo html_escape(character_limiter(strip_tags($n->content), 150)); ?></p>
              <div class="d-flex justify-content-between align-items-center mt-auto pt-2">
                <small class="text-muted"><?php echo fmt_date($n->published_at, 'd M Y'); ?></small>
                <a href="<?php echo base_url('notices/'.$n->slug); ?>" class="btn btn-ghost">Read <i data-lucide="arrow-right"></i></a>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
