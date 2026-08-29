<div class="row g-3">
  <div class="col-lg-5">
    <div class="card">
      <div class="card-header"><i class="bi bi-send"></i> Send Notification</div>
      <div class="card-body">
        <?php echo validation_errors('<div class="alert alert-danger py-2 small">', '</div>'); ?>

        <?php echo form_open('admin/notifications'); ?>
          <div class="mb-3">
            <label class="form-label">Audience</label>
            <select name="audience" class="form-select" id="audienceSelect">
              <option value="all">All active users</option>
              <option value="one">One user</option>
            </select>
          </div>

          <div class="mb-3 d-none" id="usernameField">
            <label class="form-label">Username or Email</label>
            <input type="text" name="username" class="form-control">
          </div>

          <div class="mb-3">
            <label class="form-label">Title <span class="text-danger">*</span></label>
            <input type="text" name="title" class="form-control" maxlength="180" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Message <span class="text-danger">*</span></label>
            <textarea name="message" class="form-control" rows="4" required></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label">Link <span class="text-muted">(optional)</span></label>
            <input type="text" name="link" class="form-control" placeholder="dashboard">
            <div class="form-text">Relative path inside the site, e.g. <code>packages</code>.</div>
          </div>

          <button class="btn btn-primary w-100" data-confirm="Send this notification?"><i class="bi bi-send"></i> Send</button>
        <?php echo form_close(); ?>
      </div>
    </div>
  </div>

  <div class="col-lg-7">
    <div class="card">
      <div class="card-header"><i class="bi bi-bell"></i> Sent Notifications</div>
      <?php if (empty($rows)): ?>
        <div class="empty-state"><i class="bi bi-bell-slash"></i>Nothing sent yet.</div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="table mb-0">
            <thead><tr><th>User</th><th>Title</th><th>Message</th><th>Read</th><th>Sent</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $n): ?>
              <tr>
                <td class="small fw-semibold"><?php echo html_escape($n->username ?: '-'); ?></td>
                <td class="small"><?php echo html_escape($n->title); ?></td>
                <td class="small text-muted"><?php echo html_escape(character_limiter($n->message, 70)); ?></td>
                <td><?php echo $n->is_read ? '<i class="bi bi-check2-all text-success"></i>' : '<span class="text-muted small">-</span>'; ?></td>
                <td class="small text-muted text-nowrap"><?php echo fmt_date($n->created_at, 'd M, H:i'); ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center">
          <small class="text-muted"><?php echo (int) $total; ?> notifications</small>
          <?php echo pager(base_url('admin/notifications'), $total, $per_page, $page); ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
document.getElementById('audienceSelect').addEventListener('change', function () {
  document.getElementById('usernameField').classList.toggle('d-none', this.value !== 'one');
});
</script>
