<a href="<?php echo base_url('admin/mail'); ?>" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> Mailboxes</a>

<div class="alert alert-info">
  <i class="bi bi-info-circle"></i>
  A domain holds the mail server addresses its mailboxes share, so they are typed once here rather than
  on every mailbox. Adding a domain does not create anything on the server &mdash; it only tells the panel
  where to connect.
</div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span><i class="bi bi-globe2"></i> Mail Domains</span>
    <?php if ($can_manage): ?>
    <a href="<?php echo base_url('admin/mail-domains/create'); ?>" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> Add Domain</a>
    <?php endif; ?>
  </div>

  <?php if (empty($rows)): ?>
    <div class="empty-state">
      <i class="bi bi-globe2"></i>
      No domains yet. Add one before you can register a mailbox.
    </div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table table-hover mb-0 align-middle">
        <thead><tr><th>Domain</th><th>IMAP</th><th>SMTP</th><th>Mailboxes</th><th>Status</th><th>Note</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td class="fw-semibold"><?php echo html_escape($r->domain); ?></td>
            <td class="small mono">
              <?php echo html_escape($r->imap_host); ?>:<?php echo (int) $r->imap_port; ?>
              <span class="badge text-bg-secondary"><?php echo html_escape($r->imap_encryption); ?></span>
              <?php if ( ! $r->imap_validate_cert): ?>
                <span class="badge text-bg-warning" title="Certificate is not checked on this domain">no cert check</span>
              <?php endif; ?>
            </td>
            <td class="small mono">
              <?php echo html_escape($r->smtp_host); ?>:<?php echo (int) $r->smtp_port; ?>
              <span class="badge text-bg-secondary"><?php echo html_escape($r->smtp_encryption); ?></span>
            </td>
            <td>
              <a href="<?php echo base_url('admin/mail?domain='.$r->id); ?>"><?php echo (int) $r->account_count; ?></a>
            </td>
            <td><?php echo badge($r->status); ?></td>
            <td class="small text-muted"><?php echo html_escape($r->note); ?></td>
            <td class="text-end text-nowrap">
              <?php if ($can_manage): ?>
              <a href="<?php echo base_url('admin/mail-domains/edit/'.$r->id); ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
              <?php echo form_open('admin/mail-domains/delete/'.$r->id, array('class' => 'd-inline')); ?>
                <button class="btn btn-sm btn-outline-danger" data-confirm="Delete <?php echo html_escape($r->domain); ?>?"><i class="bi bi-trash"></i></button>
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
