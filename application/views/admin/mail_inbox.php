<?php $base = base_url('admin/mail/open/'.$account->id).'?'.http_build_query(array('f' => $folder, 'q' => $search)); ?>

<div class="row g-3">

  <div class="col-lg-3">
    <?php $this->load->view('admin/_mail_folders'); ?>
  </div>

  <div class="col-lg-9">
    <div class="card">
      <div class="card-header py-2 d-flex flex-wrap gap-2 align-items-center">
        <span class="fw-semibold text-truncate"><i class="bi bi-folder2-open"></i> <?php echo html_escape($folder); ?></span>
        <span class="text-muted small"><?php echo (int) $total; ?> message<?php echo $total === 1 ? '' : 's'; ?></span>

        <form method="get" action="<?php echo base_url('admin/mail/open/'.$account->id); ?>" class="ms-auto d-flex gap-1">
          <input type="hidden" name="f" value="<?php echo html_escape($folder); ?>">
          <input type="search" name="q" class="form-control form-control-sm" style="max-width:220px" value="<?php echo html_escape($search); ?>" placeholder="Search this folder">
          <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-search"></i></button>
          <?php if ($search !== ''): ?>
            <a href="<?php echo base_url('admin/mail/open/'.$account->id.'?'.http_build_query(array('f' => $folder))); ?>" class="btn btn-sm btn-outline-secondary" title="Clear"><i class="bi bi-x-lg"></i></a>
          <?php endif; ?>
        </form>
      </div>

      <?php if (empty($rows)): ?>
        <div class="empty-state">
          <i class="bi bi-inbox"></i>
          <?php echo $search !== '' ? 'Nothing in this folder matches that search.' : 'This folder is empty.'; ?>
        </div>
      <?php else: ?>

        <?php echo form_open('admin/mail/action/'.$account->id, array('id' => 'mailListForm')); ?>
        <input type="hidden" name="folder" value="<?php echo html_escape($folder); ?>">
        <input type="hidden" name="page" value="<?php echo (int) $page; ?>">

        <div class="p-2 border-bottom d-flex flex-wrap gap-1 align-items-center bg-body-tertiary">
          <div class="form-check me-2 ms-1">
            <input type="checkbox" class="form-check-input" id="mailCheckAll">
            <label class="form-check-label small" for="mailCheckAll">All</label>
          </div>

          <button name="op" value="read"   class="btn btn-sm btn-outline-secondary" title="Mark read"><i class="bi bi-envelope-open"></i></button>
          <button name="op" value="unread" class="btn btn-sm btn-outline-secondary" title="Mark unread"><i class="bi bi-envelope"></i></button>
          <button name="op" value="star"   class="btn btn-sm btn-outline-secondary" title="Star"><i class="bi bi-star"></i></button>
          <button name="op" value="unstar" class="btn btn-sm btn-outline-secondary" title="Unstar"><i class="bi bi-star-fill"></i></button>

          <div class="d-flex gap-1 ms-2">
            <select name="target" class="form-select form-select-sm" style="max-width:200px">
              <option value="">Move to&hellip;</option>
              <?php foreach ($folders as $f): ?>
                <?php if ($f['selectable'] && $f['path'] !== $folder): ?>
                  <option value="<?php echo html_escape($f['path']); ?>"><?php echo html_escape($f['path']); ?></option>
                <?php endif; ?>
              <?php endforeach; ?>
            </select>
            <button name="op" value="move" class="btn btn-sm btn-outline-secondary"><i class="bi bi-folder-symlink"></i></button>
          </div>

          <button name="op" value="delete" class="btn btn-sm btn-outline-danger ms-auto"
                  data-confirm="<?php echo strcasecmp($folder, (string) $trash) === 0
                    ? 'Permanently delete the selected messages? This cannot be undone.'
                    : 'Move the selected messages to '.html_escape($trash).'?'; ?>">
            <i class="bi bi-trash"></i> Delete
          </button>
        </div>

        <div class="table-wrap">
          <table class="table table-hover mb-0 align-middle mail-list">
            <tbody>
            <?php foreach ($rows as $r): ?>
              <?php $url = base_url('admin/mail/message/'.$account->id.'/'.$r['uid'].'?'.http_build_query(array('f' => $folder))); ?>
              <tr class="<?php echo $r['seen'] ? '' : 'mail-unread'; ?>">
                <td style="width:2.5rem">
                  <input type="checkbox" class="form-check-input mail-check" name="uids[]" value="<?php echo (int) $r['uid']; ?>">
                </td>
                <td style="width:2rem" class="text-center">
                  <?php if ($r['flagged']): ?><i class="bi bi-star-fill text-warning" title="Starred"></i><?php endif; ?>
                  <?php if ($r['answered']): ?><i class="bi bi-reply text-muted" title="Answered"></i><?php endif; ?>
                </td>
                <td class="text-truncate" style="max-width:200px">
                  <a href="<?php echo $url; ?>" class="text-decoration-none text-body">
                    <?php echo html_escape($r['from'] !== '' ? $r['from'] : '(unknown sender)'); ?>
                  </a>
                </td>
                <td class="text-truncate">
                  <a href="<?php echo $url; ?>" class="text-decoration-none text-body">
                    <?php echo html_escape($r['subject'] !== '' ? $r['subject'] : '(no subject)'); ?>
                  </a>
                </td>
                <td style="width:2rem" class="text-center text-muted">
                  <?php if ($r['attachments']): ?><i class="bi bi-paperclip" title="Has an attachment"></i><?php endif; ?>
                </td>
                <td class="small text-muted text-nowrap text-end" style="width:9rem">
                  <?php echo $r['date'] ? fmt_date(date('Y-m-d H:i:s', $r['date']), 'd M Y, H:i') : ''; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <?php echo form_close(); ?>

        <?php if ($total > $per_page): ?>
          <div class="card-footer d-flex justify-content-end">
            <?php echo pager($base, $total, $per_page, $page); ?>
          </div>
        <?php endif; ?>

      <?php endif; ?>
    </div>
  </div>
</div>

<script>
(function () {
  var all = document.getElementById('mailCheckAll');
  if (!all) { return; }
  all.addEventListener('change', function () {
    document.querySelectorAll('.mail-check').forEach(function (box) { box.checked = all.checked; });
  });
})();
</script>
