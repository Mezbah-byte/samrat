<div class="row g-3 mb-3">
  <div class="col-md-6"><div class="card stat-card"><div class="stat-label">Active Ads</div><div class="stat-value fs-4"><?php echo (int) $stats['active']; ?></div></div></div>
  <div class="col-md-6"><div class="card stat-card"><div class="stat-label">Views Today</div><div class="stat-value fs-4"><?php echo (int) $stats['views_today']; ?></div></div></div>
</div>

<div class="card">
  <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
    <span><i class="bi bi-badge-ad"></i> Ads</span>
    <div class="d-flex gap-2">
      <?php echo form_open('admin/ads', array('method' => 'get', 'class' => 'd-flex gap-2 m-0')); ?>
        <input type="search" name="q" class="form-control form-control-sm" placeholder="Search title" value="<?php echo html_escape($search); ?>">
        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-search"></i></button>
      <?php echo form_close(); ?>
      <a href="<?php echo base_url('admin/ads/create'); ?>" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> New Ad</a>
    </div>
  </div>

  <?php if (empty($rows)): ?>
    <div class="empty-state"><i class="bi bi-badge-ad"></i>No ads yet. Users cannot complete their daily target without them.</div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table table-hover mb-0">
        <thead><tr><th>Order</th><th>Preview</th><th>Title</th><th>Type</th><th>Placement</th><th class="text-center">Watch</th><th class="text-end">Views</th><th>Schedule</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $a): ?>
          <tr>
            <td class="text-muted"><?php echo (int) $a->sort_order; ?></td>
            <td>
              <?php if ($a->media): ?>
                <img src="<?php echo upload_url('ads', $a->media); ?>" style="width:52px;height:36px;object-fit:cover" class="rounded" alt="">
              <?php else: ?><i class="bi bi-image text-muted"></i><?php endif; ?>
            </td>
            <td class="fw-semibold small">
              <?php echo html_escape($a->title); ?>
              <?php
              /* Ad_model refuses to serve a row with nothing to show, so flag it
                 here rather than leaving the admin wondering where it went. */
              $no_creative = ($a->source === 'vast'  && ! $a->vast_url)
                  || ($a->source === 'embed' && ! $a->embed_code)
                  || ($a->source === 'upload' && ! $a->media && ! $a->media_url && ! $a->body);
              ?>
              <?php if ($no_creative): ?>
                <div><span class="badge text-bg-danger-subtle border text-danger">no creative &mdash; never shown</span></div>
              <?php endif; ?>
            </td>
            <td>
              <span class="badge text-bg-light border"><?php echo html_escape($a->type); ?></span>
              <?php if ($a->source !== 'upload'): ?>
                <span class="badge text-bg-primary-subtle border ms-1"><?php echo $a->source === 'vast' ? 'VAST' : 'network'; ?></span>
              <?php endif; ?>
            </td>
            <td class="small"><?php echo $a->placement === 'daily_task' ? 'Daily task' : 'Global'; ?></td>
            <td class="text-center small"><?php echo (int) $a->watch_seconds; ?>s</td>
            <td class="text-end"><?php echo number_format($a->total_views); ?></td>
            <td class="small text-muted text-nowrap">
              <?php echo $a->starts_at ? fmt_date($a->starts_at, 'd M y') : 'Always'; ?>
              &rarr;
              <?php echo $a->ends_at ? fmt_date($a->ends_at, 'd M y') : 'Always'; ?>
            </td>
            <td><?php echo badge($a->status); ?></td>
            <td class="text-end text-nowrap">
              <a href="<?php echo base_url('admin/ads/edit/'.$a->id); ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
              <?php echo form_open('admin/ads/delete/'.$a->id, array('class' => 'd-inline')); ?>
                <button class="btn btn-sm btn-outline-danger" data-confirm="Delete this ad? Its view history will be removed too."><i class="bi bi-trash"></i></button>
              <?php echo form_close(); ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="card-footer d-flex justify-content-between align-items-center">
      <small class="text-muted"><?php echo (int) $total; ?> ads</small>
      <?php echo pager(base_url('admin/ads').'?q='.urlencode($search), $total, $per_page, $page); ?>
    </div>
  <?php endif; ?>
</div>
