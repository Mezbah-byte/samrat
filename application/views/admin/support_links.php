<?php if ( ! $enabled): ?>
  <div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle"></i>
    The Support page is switched off, so none of these channels are visible to users.
    Turn it back on under <a href="<?php echo base_url('admin/settings/index/support'); ?>">Settings &rarr; Support</a>.
  </div>
<?php endif; ?>

<div class="alert alert-info">
  <i class="bi bi-info-circle"></i>
  These are the channels on the user Support page, the dashboard card and the public footer.
  Blank the list and the Support page tells users to check back later &mdash; it never shows a dead link.
</div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span><i class="bi bi-life-preserver"></i> Support Channels</span>
    <?php if (admin_can('support_links.manage')): ?>
    <a href="<?php echo base_url('admin/support-links/create'); ?>" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> Add Channel</a>
    <?php endif; ?>
  </div>

  <?php if (empty($rows)): ?>
    <div class="empty-state"><i class="bi bi-life-preserver"></i>No channels yet. Users have no way to reach you until you add one.</div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table table-hover mb-0">
        <thead><tr><th>Order</th><th>Icon</th><th>Label</th><th>Type</th><th>Value</th><th>Note</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <?php $url = support_channel_url($r->kind, $r->value); ?>
          <tr>
            <td class="text-muted"><?php echo (int) $r->sort_order; ?></td>
            <td class="mono small"><?php echo html_escape($r->icon); ?></td>
            <td class="fw-semibold"><?php echo html_escape($r->label); ?></td>
            <td><span class="badge text-bg-secondary"><?php echo html_escape($r->kind); ?></span></td>
            <td class="small" title="<?php echo html_escape($r->value); ?>">
              <?php if ($url === ''): ?>
                <span class="text-danger"><i class="bi bi-exclamation-triangle"></i> unusable &mdash; hidden from users</span>
              <?php else: ?>
                <a href="<?php echo html_escape($url); ?>" target="_blank" rel="noopener noreferrer"><?php echo html_escape(short_txt($r->value, 24, 10)); ?></a>
              <?php endif; ?>
            </td>
            <td class="small text-muted"><?php echo html_escape($r->note); ?></td>
            <td><?php echo badge($r->status); ?></td>
            <td class="text-end text-nowrap">
              <?php if (admin_can('support_links.manage')): ?>
              <a href="<?php echo base_url('admin/support-links/edit/'.$r->id); ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
              <?php echo form_open('admin/support-links/delete/'.$r->id, array('class' => 'd-inline')); ?>
                <button class="btn btn-sm btn-outline-danger" data-confirm="Delete the &quot;<?php echo html_escape($r->label); ?>&quot; channel?"><i class="bi bi-trash"></i></button>
              <?php echo form_close(); ?>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
