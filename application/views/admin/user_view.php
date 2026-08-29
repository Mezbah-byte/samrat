<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <a href="<?php echo base_url('admin/users'); ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> All users</a>
  <div class="d-flex gap-2">
    <?php if ($u->status !== 'active'): ?>
      <?php echo form_open('admin/users/status/'.$u->id.'/active', array('class' => 'm-0')); ?>
        <button class="btn btn-sm btn-success" data-confirm="Activate this account?"><i class="bi bi-check2"></i> Activate</button>
      <?php echo form_close(); ?>
    <?php endif; ?>
    <?php if ($u->status !== 'blocked'): ?>
      <?php echo form_open('admin/users/status/'.$u->id.'/blocked', array('class' => 'm-0')); ?>
        <button class="btn btn-sm btn-danger" data-confirm="Block this account? The user will be signed out."><i class="bi bi-slash-circle"></i> Block</button>
      <?php echo form_close(); ?>
    <?php endif; ?>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="card mb-3">
      <div class="card-body text-center">
        <img src="<?php echo avatar_url($u->avatar); ?>" class="avatar-lg mb-3" alt="">
        <h5 class="mb-0"><?php echo html_escape($u->full_name); ?></h5>
        <div class="text-muted small mb-2">@<?php echo html_escape($u->username); ?></div>
        <?php echo badge($u->status); ?>
      </div>
      <ul class="list-group list-group-flush">
        <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Email</span><span class="small"><?php echo html_escape($u->email); ?></span></li>
        <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Mobile</span><span class="small"><?php echo html_escape($u->mobile); ?></span></li>
        <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Country</span><span class="small"><?php echo html_escape($u->country); ?></span></li>
        <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Referral ID</span><span class="mono small"><?php echo html_escape($u->referral_code); ?></span></li>
        <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Referred by</span>
          <span class="small"><?php echo $referrer ? '<a href="'.base_url('admin/users/view/'.$referrer->id).'">'.html_escape($referrer->username).'</a>' : '-'; ?></span></li>
        <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Joined</span><span class="small"><?php echo fmt_date($u->created_at); ?></span></li>
        <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Last login</span><span class="small"><?php echo fmt_date($u->last_login_at); ?></span></li>
      </ul>
    </div>

    <div class="card mb-3">
      <div class="card-header"><i class="bi bi-wallet2"></i> Money</div>
      <ul class="list-group list-group-flush">
        <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Balance</span><strong class="text-brand"><?php echo money($u->balance); ?></strong></li>
        <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Total Deposit</span><strong><?php echo money($u->total_deposit); ?></strong></li>
        <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Total Earned</span><strong class="text-success"><?php echo money($u->total_earned); ?></strong></li>
        <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Total Withdrawn</span><strong><?php echo money($u->total_withdrawn); ?></strong></li>
        <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Referral Bonus</span><strong><?php echo money($earned_ref); ?></strong></li>
        <li class="list-group-item">
          <div class="d-flex justify-content-between">
            <span class="text-muted">Ledger check</span>
            <?php if ($reconcile['balanced']): ?>
              <span class="badge text-bg-success"><i class="bi bi-check2"></i> Balanced</span>
            <?php else: ?>
              <span class="badge text-bg-danger">Drift <?php echo money($reconcile['drift']); ?></span>
            <?php endif; ?>
          </div>
          <div class="small text-muted mt-1">Ledger sum <?php echo money($reconcile['ledger']); ?></div>
        </li>
      </ul>
    </div>

    <div class="card">
      <div class="card-header"><i class="bi bi-sliders"></i> Adjust Balance</div>
      <div class="card-body">
        <?php echo form_open('admin/users/adjust/'.$u->id); ?>
          <div class="mb-2">
            <label class="form-label small">Direction</label>
            <select name="direction" class="form-select form-select-sm" required>
              <option value="credit">Credit (add funds)</option>
              <option value="debit">Debit (remove funds)</option>
            </select>
          </div>
          <div class="mb-2">
            <label class="form-label small">Amount</label>
            <div class="input-group input-group-sm">
              <span class="input-group-text"><?php echo html_escape(currency()); ?></span>
              <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required>
            </div>
          </div>
          <div class="mb-2">
            <label class="form-label small">Reason <span class="text-danger">*</span></label>
            <input type="text" name="reason" class="form-control form-control-sm" maxlength="200" required>
            <div class="form-text">Recorded on the transaction and in the activity log.</div>
          </div>
          <button class="btn btn-sm btn-primary w-100" data-confirm="Apply this balance adjustment?">Apply Adjustment</button>
        <?php echo form_close(); ?>
      </div>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="card mb-3">
      <div class="card-header"><i class="bi bi-pencil-square"></i> Edit Account</div>
      <div class="card-body">
        <?php echo form_open('admin/users/edit/'.$u->id); ?>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Full Name</label>
              <input type="text" name="full_name" class="form-control" value="<?php echo html_escape($u->full_name); ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-control" value="<?php echo html_escape($u->email); ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Mobile</label>
              <input type="text" name="mobile" class="form-control" value="<?php echo html_escape($u->mobile); ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Country</label>
              <select name="country" class="form-select">
                <option value="">-</option>
                <?php foreach ($countries as $c): ?>
                  <option value="<?php echo html_escape($c); ?>" <?php echo $c === $u->country ? 'selected' : ''; ?>><?php echo html_escape($c); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Status</label>
              <select name="status" class="form-select">
                <?php foreach (array('active', 'pending', 'blocked') as $s): ?>
                  <option value="<?php echo $s; ?>" <?php echo $u->status === $s ? 'selected' : ''; ?>><?php echo ucfirst($s); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Reset Password <span class="text-muted">(optional)</span></label>
              <input type="password" name="password" class="form-control" minlength="6" placeholder="Leave blank to keep current">
            </div>
          </div>
          <button class="btn btn-primary mt-3"><i class="bi bi-check2"></i> Save Changes</button>
        <?php echo form_close(); ?>
      </div>
    </div>

    <ul class="nav nav-tabs" role="tablist">
      <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabPlans">Plans</button></li>
      <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabDeps">Deposits</button></li>
      <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabWds">Withdrawals</button></li>
      <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabTx">Ledger</button></li>
      <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabRef">Referrals</button></li>
    </ul>

    <div class="tab-content card border-top-0 rounded-top-0">
      <div class="tab-pane fade show active" id="tabPlans">
        <?php if (empty($investments)): ?><div class="empty-state py-4"><i class="bi bi-inboxes"></i>No plans.</div><?php else: ?>
        <div class="table-wrap"><table class="table mb-0">
          <thead><tr><th>#</th><th>Plan</th><th class="text-end">Amount</th><th class="text-end">Daily</th><th class="text-center">Days</th><th class="text-end">Earned</th><th>Status</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($investments as $i): ?>
            <tr>
              <td class="text-muted">#<?php echo (int) $i->id; ?></td>
              <td class="small fw-semibold"><?php echo html_escape($i->package_name); ?></td>
              <td class="text-end"><?php echo money($i->amount); ?></td>
              <td class="text-end"><?php echo money($i->daily_amount); ?></td>
              <td class="text-center small"><?php echo (int) $i->days_credited; ?>/<?php echo (int) $i->duration_days; ?></td>
              <td class="text-end text-success"><?php echo money($i->total_earned); ?></td>
              <td><?php echo badge($i->status); ?></td>
              <td class="text-end"><a href="<?php echo base_url('admin/investments/view/'.$i->id); ?>" class="btn btn-sm btn-outline-secondary">Open</a></td>
            </tr>
          <?php endforeach; ?>
          </tbody></table></div>
        <?php endif; ?>
      </div>

      <div class="tab-pane fade" id="tabDeps">
        <?php if (empty($deposits)): ?><div class="empty-state py-4"><i class="bi bi-inbox"></i>No deposits.</div><?php else: ?>
        <div class="table-wrap"><table class="table mb-0">
          <thead><tr><th>#</th><th>Package</th><th class="text-end">Amount</th><th>TXID</th><th>Status</th><th>Date</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($deposits as $d): ?>
            <tr>
              <td class="text-muted">#<?php echo (int) $d->id; ?></td>
              <td class="small"><?php echo html_escape($d->package_name); ?></td>
              <td class="text-end"><?php echo money($d->amount); ?></td>
              <td class="mono small"><?php echo html_escape(short_txt($d->txid)); ?></td>
              <td><?php echo badge($d->status); ?></td>
              <td class="small text-muted text-nowrap"><?php echo fmt_date($d->created_at, 'd M y'); ?></td>
              <td class="text-end"><a href="<?php echo base_url('admin/deposits/view/'.$d->id); ?>" class="btn btn-sm btn-outline-secondary">Open</a></td>
            </tr>
          <?php endforeach; ?>
          </tbody></table></div>
        <?php endif; ?>
      </div>

      <div class="tab-pane fade" id="tabWds">
        <?php if (empty($withdrawals)): ?><div class="empty-state py-4"><i class="bi bi-inbox"></i>No withdrawals.</div><?php else: ?>
        <div class="table-wrap"><table class="table mb-0">
          <thead><tr><th>#</th><th class="text-end">Amount</th><th class="text-end">Net</th><th>Network</th><th>Status</th><th>Date</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($withdrawals as $w): ?>
            <tr>
              <td class="text-muted">#<?php echo (int) $w->id; ?></td>
              <td class="text-end"><?php echo money($w->amount); ?></td>
              <td class="text-end fw-semibold"><?php echo money($w->net_amount); ?></td>
              <td class="small"><?php echo html_escape($w->network); ?></td>
              <td><?php echo badge($w->status); ?></td>
              <td class="small text-muted text-nowrap"><?php echo fmt_date($w->created_at, 'd M y'); ?></td>
              <td class="text-end"><a href="<?php echo base_url('admin/withdrawals/view/'.$w->id); ?>" class="btn btn-sm btn-outline-secondary">Open</a></td>
            </tr>
          <?php endforeach; ?>
          </tbody></table></div>
        <?php endif; ?>
      </div>

      <div class="tab-pane fade" id="tabTx">
        <?php if (empty($transactions)): ?><div class="empty-state py-4"><i class="bi bi-receipt"></i>No transactions.</div><?php else: ?>
        <div class="table-wrap"><table class="table mb-0">
          <thead><tr><th>Type</th><th>Description</th><th class="text-end">Amount</th><th class="text-end">Balance</th><th>Date</th></tr></thead>
          <tbody>
          <?php foreach ($transactions as $t): ?>
            <tr>
              <td class="small"><?php echo html_escape(tx_label($t->type)); ?></td>
              <td class="small text-muted"><?php echo html_escape($t->description); ?></td>
              <td class="text-end fw-semibold <?php echo $t->amount >= 0 ? 'text-success' : 'text-danger'; ?>"><?php echo ($t->amount >= 0 ? '+' : '-').money(abs($t->amount)); ?></td>
              <td class="text-end text-muted"><?php echo money($t->balance_after); ?></td>
              <td class="small text-muted text-nowrap"><?php echo fmt_date($t->created_at, 'd M, H:i'); ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody></table></div>
        <?php endif; ?>
      </div>

      <div class="tab-pane fade" id="tabRef">
        <?php if (empty($downline)): ?><div class="empty-state py-4"><i class="bi bi-people"></i>No referrals.</div><?php else: ?>
        <div class="table-wrap"><table class="table mb-0">
          <thead><tr><th>User</th><th>Country</th><th class="text-end">Deposited</th><th>Status</th><th>Joined</th></tr></thead>
          <tbody>
          <?php foreach ($downline as $d): ?>
            <tr>
              <td><a href="<?php echo base_url('admin/users/view/'.$d->id); ?>" class="small fw-semibold"><?php echo html_escape($d->username); ?></a></td>
              <td class="small"><?php echo html_escape($d->country); ?></td>
              <td class="text-end"><?php echo money($d->total_deposit); ?></td>
              <td><?php echo badge($d->status); ?></td>
              <td class="small text-muted text-nowrap"><?php echo fmt_date($d->created_at, 'd M y'); ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody></table></div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
