<a href="<?php echo base_url('admin/mail/open/'.$account->id.'?'.http_build_query(array('f' => $folder))); ?>" class="btn btn-sm btn-outline-secondary mb-3">
  <i class="bi bi-arrow-left"></i> Back to <?php echo html_escape($folder); ?>
</a>

<div class="card">
  <div class="card-header py-2">
    <i class="bi bi-pencil-square"></i> New message
    <span class="text-muted small">as <?php echo html_escape($account->display_name ? $account->display_name.' <'.$account->email.'>' : $account->email); ?></span>
  </div>

  <div class="card-body">
    <?php echo validation_errors('<div class="alert alert-danger py-2 small">', '</div>'); ?>

    <?php echo form_open_multipart('admin/mail/send/'.$account->id, array('id' => 'composeForm')); ?>
      <input type="hidden" name="in_reply_to" value="<?php echo html_escape($d['in_reply_to']); ?>">
      <input type="hidden" name="references" value="<?php echo html_escape($d['references']); ?>">

      <div class="mb-2">
        <label class="form-label small mb-1">To <span class="text-danger">*</span></label>
        <input type="text" name="to" class="form-control" value="<?php echo set_value('to', $d['to']); ?>" required placeholder="someone@example.com, another@example.com">
        <div class="form-text">Separate several with a comma. <span class="mono">Name &lt;address&gt;</span> is understood.</div>
      </div>

      <div class="row g-2 mb-2">
        <div class="col-md-6">
          <label class="form-label small mb-1">Cc</label>
          <input type="text" name="cc" class="form-control" value="<?php echo set_value('cc', $d['cc']); ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label small mb-1">Bcc</label>
          <input type="text" name="bcc" class="form-control" value="<?php echo set_value('bcc', $d['bcc']); ?>">
          <div class="form-text">Hidden from every other recipient.</div>
        </div>
      </div>

      <div class="mb-2">
        <label class="form-label small mb-1">Subject</label>
        <input type="text" name="subject" class="form-control" value="<?php echo set_value('subject', $d['subject']); ?>" maxlength="255">
      </div>

      <div class="mb-2">
        <label class="form-label small mb-1">Message</label>
        <textarea name="body" id="composeBody" class="form-control" rows="12" placeholder="Write your message&hellip;"><?php echo set_value('body', $d['body'].$quote); ?></textarea>
        <div class="form-text">
          Basic HTML is allowed &mdash; <span class="mono">&lt;b&gt;</span>, <span class="mono">&lt;a&gt;</span>,
          <span class="mono">&lt;br&gt;</span> and the like. Scripts are stripped before sending.
          A plain-text version is generated automatically.
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label small mb-1">Attachments</label>
        <input type="file" name="attachments[]" class="form-control" multiple>
        <div class="form-text">Up to <?php echo round($max_bytes / 1048576); ?> MB in total.</div>
      </div>

      <?php if ($account->signature): ?>
      <div class="alert alert-secondary py-2 small">
        <i class="bi bi-pen"></i> The mailbox signature is appended automatically.
      </div>
      <?php endif; ?>

      <button class="btn btn-primary"><i class="bi bi-send"></i> Send</button>
      <a href="<?php echo base_url('admin/mail/open/'.$account->id.'?'.http_build_query(array('f' => $folder))); ?>" class="btn btn-outline-secondary">Discard</a>
    <?php echo form_close(); ?>
  </div>
</div>
