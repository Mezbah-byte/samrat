<div class="card">
  <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
    <span><i class="bi bi-megaphone"></i> Notices</span>
    <div class="d-flex gap-2">
      <?php echo form_open('admin/notices', array('method' => 'get', 'class' => 'd-flex gap-2 m-0')); ?>
        <input type="search" name="q" class="form-control form-control-sm" placeholder="Search title" value="<?php echo html_escape($search); ?>">
        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-search"></i></button>
      <?php echo form_close(); ?>
      <a href="<?php echo base_url('admin/notices/create'); ?>" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> New Notice</a>
    </div>
  </div>

  <?php if (empty($rows)): ?>
    <div class="empty-state"><i class="bi bi-megaphone"></i>No notices yet.</div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table table-hover mb-0">
        <thead><tr><th>#</th><th>Title</th><th>Type</th><th>Pinned</th><th>Status</th><th>Published</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $n): ?>
          <tr>
            <td class="text-muted">#<?php echo (int) $n->id; ?></td>
            <td>
              <div class="fw-semibold small"><?php echo html_escape($n->title); ?></div>
              <div class="small text-muted"><?php echo html_escape(character_limiter(strip_tags($n->content), 80)); ?></div>
            </td>
            <td><span class="badge text-bg-light border"><?php echo html_escape(ucfirst($n->type)); ?></span></td>
            <td><?php echo $n->is_pinned ? '<i class="bi bi-pin-angle-fill text-warning"></i>' : '<span class="text-muted">-</span>'; ?></td>
            <td><?php echo badge($n->status); ?></td>
            <td class="small text-muted text-nowrap"><?php echo fmt_date($n->published_at, 'd M Y'); ?></td>
            <td class="text-end text-nowrap">
              <a href="<?php echo base_url('notices/'.$n->slug); ?>" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
              <a href="<?php echo base_url('admin/notices/edit/'.$n->id); ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
              <?php echo form_open('admin/notices/delete/'.$n->id, array('class' => 'd-inline')); ?>
                <button class="btn btn-sm btn-outline-danger" data-confirm="Delete this notice?"><i class="bi bi-trash"></i></button>
              <?php echo form_close(); ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="card-footer d-flex justify-content-between align-items-center">
      <small class="text-muted"><?php echo (int) $total; ?> notices</small>
      <?php echo pager(base_url('admin/notices').'?q='.urlencode($search), $total, $per_page, $page); ?>
    </div>
  <?php endif; ?>
</div>
