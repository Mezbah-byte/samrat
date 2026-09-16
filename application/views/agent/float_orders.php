<?php
$pending = 0;
foreach ($rows as $r) { if ($r->status === 'pending') { $pending++; } }
?>
<div class="page-head reveal" data-reveal-order="0">
  <div>
    <h1>Buy Float</h1>
    <p class="lede">Float is what pays for user deposits you accept. Buy it from the platform at par.</p>
  </div>
  <a href="<?php echo base_url('agent/float/create'); ?>" class="btn btn-grad"><i data-lucide="plus"></i> Buy Float</a>
</div>

<div class="row g-3 mb-3">
  <div class="col-xl-5">
    <div class="panel lift stat h-100 reveal" data-reveal-order="1">
      <div class="stat-top">
        <div>
          <div class="stat-label">Deposit Float Available</div>
          <div class="stat-value num" data-count="<?php echo (float) $balance; ?>" data-count-prefix="<?php echo html_escape(currency()); ?>"><?php echo money($balance); ?></div>
        </div>
        <span class="icon-tile grad-primary"><i data-lucide="coins"></i></span>
      </div>
      <div class="stat-foot">
        <span>Spendable on user deposits right now</span>
      </div>
    </div>
  </div>

  <div class="col-xl-7">
    <div class="panel h-100 reveal" data-reveal-order="2">
      <div class="panel-head"><i data-lucide="info"></i> How Float Works</div>
      <div class="panel-body">
        <div class="feed">
          <div class="feed-item">
            <span class="icon-tile sm grad-primary"><i data-lucide="send"></i></span>
            <div class="feed-main">
              <div class="feed-title">1. Send USDT to a company wallet</div>
              <div class="feed-sub">Off-platform, from your own wallet or exchange.</div>
            </div>
          </div>
          <div class="feed-item">
            <span class="icon-tile sm grad-info"><i data-lucide="file-text"></i></span>
            <div class="feed-main">
              <div class="feed-title">2. Record the transfer here</div>
              <div class="feed-sub">Amount and transaction hash, screenshot optional.</div>
            </div>
          </div>
          <div class="feed-item">
            <span class="icon-tile sm grad-success"><i data-lucide="check-check"></i></span>
            <div class="feed-main">
              <div class="feed-title">3. Admin verifies, float lands</div>
              <div class="feed-sub">At par &mdash; <?php echo money(100); ?> sent becomes <?php echo money(100); ?> of float. You earn from commission, not a discount.</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="panel reveal" data-reveal-order="3">
  <div class="panel-head">
    <i data-lucide="receipt-text"></i> Float Orders
    <span class="spacer"></span>
    <?php echo form_open('agent/float', array('method' => 'get', 'class' => 'd-flex gap-2 m-0')); ?>
      <select name="status" class="form-select form-select-sm" data-autosubmit style="width:auto">
        <option value="">All statuses</option>
        <?php foreach (array('pending', 'approved', 'rejected') as $s): ?>
          <option value="<?php echo $s; ?>" <?php echo $status === $s ? 'selected' : ''; ?>><?php echo ucfirst($s); ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn btn-quiet btn-icon" aria-label="Filter"><i data-lucide="filter"></i></button>
    <?php echo form_close(); ?>
  </div>

  <?php if (empty($rows)): ?>
    <div class="empty-state">
      <i data-lucide="coins"></i>
      <p class="mb-3">No float orders yet. Buy float before accepting any deposit.</p>
      <a href="<?php echo base_url('agent/float/create'); ?>" class="btn btn-grad"><i data-lucide="plus"></i> Buy Float</a>
    </div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>#</th><th class="text-end">Amount</th><th>Sent to</th><th>TXID</th><th>Status</th><th>Submitted</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $o): ?>
          <tr>
            <td class="text-dim">#<?php echo (int) $o->id; ?></td>
            <td class="text-end num fw-semibold"><?php echo money($o->amount); ?></td>
            <td class="text-muted"><?php echo html_escape($o->method_name ?: '-'); ?></td>
            <td class="mono small" title="<?php echo html_escape($o->txid); ?>"><?php echo html_escape(short_txt($o->txid)); ?></td>
            <td><?php echo chip($o->status); ?></td>
            <td class="text-muted text-nowrap small"><?php echo fmt_date($o->created_at, 'd M, H:i'); ?></td>
            <td class="text-end"><a href="<?php echo base_url('agent/float/view/'.$o->id); ?>" class="btn btn-ghost btn-icon"><i data-lucide="eye"></i></a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="panel-foot">
      <span><?php echo (int) $total; ?> order<?php echo $total == 1 ? '' : 's'; ?><?php echo $pending ? ' &middot; '.$pending.' awaiting review' : ''; ?></span>
      <?php echo pager(base_url('agent/float').'?status='.urlencode($status), $total, $per_page, $page); ?>
    </div>
  <?php endif; ?>
</div>
