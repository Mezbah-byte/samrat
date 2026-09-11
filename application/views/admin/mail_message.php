<?php
/**
 * The message body is attacker-controlled markup, so it is never placed in
 * this document. It goes into a sandboxed iframe with no `allow-scripts` and
 * no `allow-same-origin`, which gives it an opaque origin of its own: even if
 * something executable survived Mail_sanitizer_lib, it has no access to this
 * page, its cookies or its storage. The CSP inside repeats the refusal, and
 * pins image loading to whether the reader asked for remote images.
 */
// The frame runs in an opaque origin, so `'self'` would match nothing - the
// attachment route has to be named by its own URL for inline images to load.
$own = rtrim(base_url('admin/mail/attachment'), '/');

$img_src = $show_images
	? 'img-src '.$own.' data: https: http:;'
	: 'img-src '.$own.' data:;';

$frame_doc = '<!doctype html><html><head><meta charset="utf-8">'
	.'<meta http-equiv="Content-Security-Policy" content="default-src \'none\'; '.$img_src
	.' style-src \'unsafe-inline\'; font-src data:; media-src \'none\'; frame-src \'none\';">'
	.'<base target="_blank">'
	.'<style>'
	.'html,body{margin:0}'
	.'body{padding:14px;font:14px/1.55 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;color:#212529;overflow-wrap:anywhere}'
	.'img{max-width:100%;height:auto}'
	.'table{max-width:100%}'
	.'pre.mail-plain{white-space:pre-wrap;font:13px/1.5 ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;margin:0}'
	.'.mail-quote{color:#6c757d}'
	.'blockquote{margin:0 0 0 .5rem;padding-left:.75rem;border-left:3px solid #dee2e6;color:#6c757d}'
	.'a{color:#0d6efd}'
	.'</style></head><body>'.$body.'</body></html>';

$qs      = http_build_query(array('f' => $folder));
$back    = base_url('admin/mail/open/'.$account->id.'?'.$qs);
$in_trash = strcasecmp($folder, (string) $trash) === 0;
?>

<div class="row g-3">

  <div class="col-lg-3 d-none d-lg-block">
    <?php $this->load->view('admin/_mail_folders'); ?>
  </div>

  <div class="col-lg-9">
    <div class="card">

      <div class="card-header py-2 d-flex flex-wrap gap-2 align-items-center">
        <a href="<?php echo $back; ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> <?php echo html_escape($folder); ?></a>

        <?php if ($can_send): ?>
          <a href="<?php echo base_url('admin/mail/compose/'.$account->id.'?'.http_build_query(array('reply' => $m['uid'], 'mode' => 'reply', 'f' => $folder))); ?>" class="btn btn-sm btn-primary"><i class="bi bi-reply"></i> Reply</a>
          <a href="<?php echo base_url('admin/mail/compose/'.$account->id.'?'.http_build_query(array('reply' => $m['uid'], 'mode' => 'replyall', 'f' => $folder))); ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-reply-all"></i> Reply all</a>
          <a href="<?php echo base_url('admin/mail/compose/'.$account->id.'?'.http_build_query(array('reply' => $m['uid'], 'mode' => 'forward', 'f' => $folder))); ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-right"></i> Forward</a>
        <?php endif; ?>

        <div class="ms-auto d-flex gap-1">
          <?php echo form_open('admin/mail/action/'.$account->id, array('class' => 'd-flex gap-1')); ?>
            <input type="hidden" name="folder" value="<?php echo html_escape($folder); ?>">
            <input type="hidden" name="uids[]" value="<?php echo (int) $m['uid']; ?>">
            <button name="op" value="<?php echo $m['flagged'] ? 'unstar' : 'star'; ?>" class="btn btn-sm btn-outline-secondary" title="<?php echo $m['flagged'] ? 'Remove star' : 'Star'; ?>">
              <i class="bi bi-star<?php echo $m['flagged'] ? '-fill text-warning' : ''; ?>"></i>
            </button>
            <button name="op" value="unread" class="btn btn-sm btn-outline-secondary" title="Mark unread"><i class="bi bi-envelope"></i></button>
            <button name="op" value="delete" class="btn btn-sm btn-outline-danger"
                    data-confirm="<?php echo $in_trash ? 'Permanently delete this message? This cannot be undone.' : 'Move this message to '.html_escape($trash).'?'; ?>">
              <i class="bi bi-trash"></i>
            </button>
          <?php echo form_close(); ?>
        </div>
      </div>

      <div class="card-body border-bottom">
        <h5 class="mb-2"><?php echo html_escape($m['subject'] !== '' ? $m['subject'] : '(no subject)'); ?></h5>

        <div class="small">
          <div><span class="text-muted" style="display:inline-block;min-width:3.5rem">From</span> <?php echo html_escape($m['from']); ?></div>
          <div><span class="text-muted" style="display:inline-block;min-width:3.5rem">To</span> <?php echo html_escape($m['to']); ?></div>
          <?php if ($m['cc'] !== ''): ?>
            <div><span class="text-muted" style="display:inline-block;min-width:3.5rem">Cc</span> <?php echo html_escape($m['cc']); ?></div>
          <?php endif; ?>
          <div><span class="text-muted" style="display:inline-block;min-width:3.5rem">Date</span> <?php echo $m['date'] ? fmt_date(date('Y-m-d H:i:s', $m['date'])) : ''; ?></div>
        </div>
      </div>

      <?php if ( ! empty($m['attachments'])): ?>
      <div class="p-3 border-bottom bg-body-tertiary">
        <div class="small fw-semibold text-uppercase text-muted mb-2">
          <i class="bi bi-paperclip"></i> <?php echo count($m['attachments']); ?> attachment<?php echo count($m['attachments']) === 1 ? '' : 's'; ?>
        </div>
        <div class="d-flex flex-wrap gap-2">
          <?php foreach ($m['attachments'] as $a): ?>
            <a class="btn btn-sm btn-outline-secondary"
               href="<?php echo base_url('admin/mail/attachment/'.$account->id.'/'.$m['uid'].'/'.rawurlencode($a['part']).'?'.$qs); ?>">
              <i class="bi bi-download"></i>
              <?php echo html_escape($a['filename'] !== '' ? $a['filename'] : 'attachment'); ?>
              <span class="text-muted">(<?php echo $a['size'] > 1048576
                ? round($a['size'] / 1048576, 1).' MB'
                : max(1, round($a['size'] / 1024)).' KB'; ?>)</span>
            </a>
          <?php endforeach; ?>
        </div>
        <div class="form-text mt-2">
          Attachments come from outside the company. They download as files and are never opened by the panel &mdash;
          check anything executable before running it.
        </div>
      </div>
      <?php endif; ?>

      <?php if ($blocked_images && ! $show_images): ?>
      <div class="alert alert-warning rounded-0 border-0 border-bottom mb-0 py-2 small d-flex align-items-center gap-2">
        <i class="bi bi-image"></i>
        <span>Remote images are blocked. Loading them tells the sender the message was read.</span>
        <a class="btn btn-sm btn-outline-dark ms-auto" href="<?php echo base_url('admin/mail/message/'.$account->id.'/'.$m['uid'].'?'.$qs.'&images=1'); ?>">Show images</a>
      </div>
      <?php endif; ?>

      <div class="mail-body-wrap">
        <iframe id="mailBody"
                class="mail-body-frame"
                sandbox="allow-popups allow-popups-to-escape-sandbox"
                referrerpolicy="no-referrer"
                title="Message body"
                srcdoc="<?php echo html_escape($frame_doc); ?>"></iframe>
      </div>

      <div class="card-footer py-2 d-flex justify-content-between align-items-center">
        <span class="small text-muted">
          <?php echo $m['size'] > 1048576 ? round($m['size'] / 1048576, 1).' MB' : max(1, round($m['size'] / 1024)).' KB'; ?>
        </span>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="mailExpand">
          <i class="bi bi-arrows-expand"></i> Taller
        </button>
      </div>

    </div>
  </div>
</div>

<script>
(function () {
  var frame = document.getElementById('mailBody');
  var btn   = document.getElementById('mailExpand');
  if (!frame || !btn) { return; }

  // The frame has no allow-scripts and no allow-same-origin, so nothing inside
  // it can report its height and nothing outside can measure it. Growing it is
  // a manual step rather than a broken automatic one.
  btn.addEventListener('click', function () {
    frame.style.height = (frame.offsetHeight + 600) + 'px';
  });
})();
</script>
