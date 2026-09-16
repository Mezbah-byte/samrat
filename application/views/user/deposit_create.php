<?php
  // What the form can offer. `route` is the admin setting; the arrays are what
  // actually survived the availability checks in the controller.
  $has_admin = ! empty($methods);
  $has_agent = ! empty($agents);
  $default   = ($has_agent && ($route === 'agent' || ! $has_admin)) ? 'agent' : 'admin';
  $chosen    = set_value('pay_via', $default);
?>
<div class="page-head reveal" data-reveal-order="0">
  <div>
    <h1>Submit Deposit</h1>
    <p class="lede"><?php echo html_escape($package->name); ?> &middot; <?php echo money($package->price); ?></p>
  </div>
  <a href="<?php echo base_url('deposit'); ?>" class="btn btn-ghost"><i data-lucide="arrow-left"></i> Back</a>
</div>

<div class="row g-3">
  <div class="col-xl-7">
    <div class="panel reveal" data-reveal-order="1">
      <div class="panel-head"><i data-lucide="upload"></i> Deposit Proof</div>
      <div class="panel-body">
        <?php echo validation_errors('<div class="alert alert-danger py-2 small">', '</div>'); ?>

        <?php echo form_open_multipart('deposit/create/'.$package->id, array('id' => 'depositForm')); ?>

          <?php if ($has_admin && $has_agent): ?>
            <div class="mb-3">
              <label class="form-label">How do you want to pay?</label>
              <div class="d-flex gap-2 flex-wrap">
                <label class="btn btn-quiet flex-fill">
                  <input type="radio" name="pay_via" value="admin" class="form-check-input me-2" <?php echo $chosen === 'admin' ? 'checked' : ''; ?>>
                  Company wallet
                </label>
                <label class="btn btn-quiet flex-fill">
                  <input type="radio" name="pay_via" value="agent" class="form-check-input me-2" <?php echo $chosen === 'agent' ? 'checked' : ''; ?>>
                  Pay an agent
                </label>
              </div>
            </div>
          <?php else: ?>
            <input type="hidden" name="pay_via" value="<?php echo $has_agent ? 'agent' : 'admin'; ?>">
          <?php endif; ?>

          <div class="mb-3">
            <label class="form-label">Package</label>
            <input type="text" class="form-control" value="<?php echo html_escape($package->name).' - '.money($package->price); ?>" readonly>
            <div class="form-text">The amount is fixed by the package price.</div>
          </div>

          <?php if ($has_admin): ?>
            <div class="route-block" data-route="admin" <?php echo $chosen !== 'admin' ? 'hidden' : ''; ?>>
              <div class="alert alert-info small">
                <ol class="mb-0 ps-3">
                  <li>Send exactly <strong><?php echo money($package->price); ?></strong> in USDT to the wallet shown.</li>
                  <li>Copy the transaction hash (TXID) from your wallet or exchange.</li>
                  <li>Submit it here. An admin verifies it on-chain and activates your plan.</li>
                </ol>
              </div>
              <div class="mb-3">
                <label class="form-label">Payment Wallet <span class="text-bad">*</span></label>
                <select name="deposit_method_id" class="form-select" id="methodSelect">
                  <?php foreach ($methods as $m): ?>
                    <option value="<?php echo (int) $m->id; ?>" <?php echo set_select('deposit_method_id', $m->id); ?>>
                      <?php echo html_escape($m->name.' ('.$m->network.')'); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
          <?php endif; ?>

          <?php if ($has_agent): ?>
            <div class="route-block" data-route="agent" <?php echo $chosen !== 'agent' ? 'hidden' : ''; ?>>
              <div class="alert alert-info small">
                <ol class="mb-0 ps-3">
                  <li>Pick an agent, then send exactly <strong><?php echo money($package->price); ?></strong> to their wallet.</li>
                  <li>Submit the transaction hash here.</li>
                  <li>The agent confirms the payment and your plan activates straight away.</li>
                </ol>
              </div>
              <div class="mb-3">
                <label class="form-label">Agent <span class="text-bad">*</span></label>
                <select name="agent_id" class="form-select" id="agentSelect">
                  <?php foreach ($agents as $a): ?>
                    <option value="<?php echo (int) $a->id; ?>" <?php echo set_select('agent_id', $a->id); ?>>
                      <?php echo html_escape($a->username); ?> &mdash; <?php echo html_escape($a->name); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label">Agent Wallet <span class="text-bad">*</span></label>
                <select name="agent_wallet_id" class="form-select" id="agentWalletSelect"></select>
              </div>
            </div>
          <?php endif; ?>

          <div class="mb-3">
            <label class="form-label">Transaction Hash (TXID) <span class="text-bad">*</span></label>
            <input type="text" name="txid" class="form-control mono" value="<?php echo set_value('txid'); ?>"
                   placeholder="e.g. 8f3c1b...d92a" required minlength="10" maxlength="191">
            <div class="form-text">Each TXID can only be submitted once.</div>
          </div>

          <div class="mb-3">
            <label class="form-label">Payment Screenshot <span class="text-muted">(optional)</span></label>
            <input type="file" name="proof_image" class="form-control" accept="image/*">
            <div class="form-text">JPG, PNG, GIF or WEBP, up to 4 MB.</div>
          </div>

          <div class="d-flex gap-2">
            <button class="btn btn-grad"><i data-lucide="send"></i> Submit Deposit</button>
            <a href="<?php echo base_url('deposit'); ?>" class="btn btn-quiet">Cancel</a>
          </div>
        <?php echo form_close(); ?>
      </div>
    </div>
  </div>

  <div class="col-xl-5">
    <div class="panel reveal" data-reveal-order="2">
      <div class="panel-head"><i data-lucide="qr-code"></i> Send Payment To</div>
      <div class="panel-body">

        <?php if ($has_admin): ?>
          <div class="route-block" data-route="admin" <?php echo $chosen !== 'admin' ? 'hidden' : ''; ?>>
            <?php foreach ($methods as $i => $m): ?>
              <div class="wallet-block" data-method="<?php echo (int) $m->id; ?>" <?php echo $i > 0 ? 'hidden' : ''; ?>>
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <span class="fw-semibold"><?php echo html_escape($m->name); ?></span>
                  <span class="chip chip-info"><?php echo html_escape($m->network); ?></span>
                </div>
                <?php if ($m->qr_image): ?>
                  <div class="qr-box"><img src="<?php echo upload_url('qr', $m->qr_image); ?>" alt="QR code"></div>
                <?php endif; ?>
                <label class="form-label">Wallet address</label>
                <div class="copy-field mb-2">
                  <input type="text" class="form-control form-control-sm" id="addr<?php echo (int) $m->id; ?>" readonly value="<?php echo html_escape($m->wallet_address); ?>">
                  <button class="btn btn-ghost" type="button" data-copy-target="#addr<?php echo (int) $m->id; ?>" aria-label="Copy address"><i data-lucide="copy"></i></button>
                </div>
                <?php if ($m->instructions): ?>
                  <p class="small text-muted mb-0"><?php echo html_escape($m->instructions); ?></p>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php if ($has_agent): ?>
          <div class="route-block" data-route="agent" <?php echo $chosen !== 'agent' ? 'hidden' : ''; ?>>
            <?php foreach ($agents as $a): ?>
              <?php foreach ($a->wallets as $w): ?>
                <div class="agent-wallet-block" data-agent="<?php echo (int) $a->id; ?>" data-wallet="<?php echo (int) $w->id; ?>" hidden>
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-semibold"><?php echo html_escape($w->label); ?></span>
                    <span class="chip chip-info"><?php echo html_escape($w->network); ?></span>
                  </div>
                  <?php if ($w->qr_image): ?>
                    <div class="qr-box"><img src="<?php echo upload_url('qr', $w->qr_image); ?>" alt="QR code"></div>
                  <?php endif; ?>
                  <label class="form-label">Send to <?php echo html_escape($a->username); ?></label>
                  <div class="copy-field mb-2">
                    <input type="text" class="form-control form-control-sm" id="aw<?php echo (int) $w->id; ?>" readonly value="<?php echo html_escape($w->wallet_address); ?>">
                    <button class="btn btn-ghost" type="button" data-copy-target="#aw<?php echo (int) $w->id; ?>" aria-label="Copy address"><i data-lucide="copy"></i></button>
                  </div>
                  <?php if ($w->instructions): ?>
                    <p class="small text-muted mb-0"><?php echo html_escape($w->instructions); ?></p>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            <?php endforeach; ?>
            <p class="small text-muted mb-0">
              You are paying this agent directly. They confirm the transfer, and the platform
              activates your plan from their balance the moment they do.
            </p>
          </div>
        <?php endif; ?>

        <div class="tile mt-3">
          <div class="d-flex justify-content-between align-items-center">
            <span class="text-muted">Amount to send</span>
            <strong class="plan-price" style="font-size:1.5rem"><?php echo money($package->price); ?></strong>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  // Wallets per agent, so the second select can be rebuilt without a round trip.
  var agentWallets = <?php
    $map = array();
    foreach ($agents as $a)
    {
      $map[(int) $a->id] = array();
      foreach ($a->wallets as $w)
      {
        $map[(int) $a->id][] = array(
          'id'    => (int) $w->id,
          'label' => $w->label.' ('.$w->network.')',
        );
      }
    }
    echo json_encode($map);
  ?>;

  var form         = document.getElementById('depositForm');
  var methodSelect = document.getElementById('methodSelect');
  var agentSelect  = document.getElementById('agentSelect');
  var walletSelect = document.getElementById('agentWalletSelect');
  var preselect    = <?php echo json_encode(set_value('agent_wallet_id')); ?>;

  function show(selector, match) {
    document.querySelectorAll(selector).forEach(function (el) {
      el.hidden = !match(el);
    });
  }

  function applyRoute() {
    var picked = form.querySelector('input[name="pay_via"]:checked');
    var route  = picked ? picked.value : form.querySelector('input[name="pay_via"]').value;

    show('.route-block', function (el) { return el.getAttribute('data-route') === route; });

    // A hidden required field would block submit with no visible error, so
    // only the active route's selects carry `required`.
    if (methodSelect) { methodSelect.required = (route === 'admin'); }
    if (agentSelect)  { agentSelect.required  = (route === 'agent'); }
    if (walletSelect) { walletSelect.required = (route === 'agent'); }
  }

  function applyAgent() {
    if (!agentSelect || !walletSelect) { return; }

    var list = agentWallets[agentSelect.value] || [];
    walletSelect.innerHTML = '';

    list.forEach(function (w) {
      var opt = document.createElement('option');
      opt.value = w.id;
      opt.textContent = w.label;
      if (String(preselect) === String(w.id)) { opt.selected = true; }
      walletSelect.appendChild(opt);
    });

    preselect = null;
    applyAgentWallet();
  }

  function applyAgentWallet() {
    show('.agent-wallet-block', function (el) {
      return el.getAttribute('data-agent') === agentSelect.value
          && el.getAttribute('data-wallet') === walletSelect.value;
    });
  }

  form.querySelectorAll('input[name="pay_via"]').forEach(function (el) {
    el.addEventListener('change', applyRoute);
  });

  if (methodSelect) {
    methodSelect.addEventListener('change', function () {
      var id = this.value;
      show('.wallet-block', function (el) { return el.getAttribute('data-method') === id; });
    });
  }

  if (agentSelect)  { agentSelect.addEventListener('change', applyAgent); }
  if (walletSelect) { walletSelect.addEventListener('change', applyAgentWallet); }

  applyRoute();
  applyAgent();
})();
</script>
