(function () {
  'use strict';

  /* ---------- responsive sidebar ---------- */
  var sidebar  = document.getElementById('appSidebar');
  var toggle   = document.getElementById('sidebarToggle');
  var backdrop = document.getElementById('sidebarBackdrop');

  function closeSidebar() {
    if (sidebar) sidebar.classList.remove('open');
    if (backdrop) backdrop.classList.remove('show');
  }

  if (toggle && sidebar) {
    toggle.addEventListener('click', function () {
      sidebar.classList.toggle('open');
      if (backdrop) backdrop.classList.toggle('show');
    });
  }
  if (backdrop) backdrop.addEventListener('click', closeSidebar);

  /* ---------- copy-to-clipboard ---------- */
  document.querySelectorAll('[data-copy]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var value  = btn.getAttribute('data-copy');
      var target = document.querySelector(btn.getAttribute('data-copy-target') || '');
      var text   = value || (target ? target.value : '');
      if (!text) return;

      var done = function () {
        var original = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check2"></i>';
        setTimeout(function () { btn.innerHTML = original; }, 1400);
      };

      if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(done);
      } else if (target) {
        // http://localhost has no async clipboard, so fall back to execCommand
        target.select();
        document.execCommand('copy');
        done();
      }
    });
  });

  /* ---------- confirm before destructive submits ---------- */
  document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('click', function (e) {
      if (!window.confirm(el.getAttribute('data-confirm'))) {
        e.preventDefault();
        e.stopPropagation();
      }
    });
  });

  /* ---------- withdrawal fee preview ---------- */
  var wAmount = document.getElementById('withdrawAmount');
  if (wAmount) {
    var feePct  = parseFloat(wAmount.getAttribute('data-fee') || '0');
    var symbol  = wAmount.getAttribute('data-symbol') || '$';
    var feeOut  = document.getElementById('withdrawFee');
    var netOut  = document.getElementById('withdrawNet');

    var recalc = function () {
      var amount = parseFloat(wAmount.value || '0');
      if (isNaN(amount) || amount <= 0) amount = 0;
      var fee = amount * feePct / 100;
      if (feeOut) feeOut.textContent = symbol + fee.toFixed(2);
      if (netOut) netOut.textContent = symbol + (amount - fee).toFixed(2);
    };

    wAmount.addEventListener('input', recalc);
    recalc();
  }

  /* ---------- ad watch countdown ---------- */
  var adModal = document.getElementById('adModal');
  if (adModal) {
    var timer = null;

    adModal.addEventListener('show.bs.modal', function (event) {
      var trigger = event.relatedTarget;
      if (!trigger) return;

      var seconds = parseInt(trigger.getAttribute('data-seconds') || '10', 10);
      var adId    = trigger.getAttribute('data-ad-id');
      var title   = trigger.getAttribute('data-ad-title') || 'Advertisement';
      var media   = trigger.getAttribute('data-ad-media') || '';
      var link    = trigger.getAttribute('data-ad-link') || '';
      var body    = trigger.getAttribute('data-ad-body') || '';

      adModal.querySelector('.ad-title').textContent = title;
      adModal.querySelector('.ad-body-text').textContent = body;

      var mediaBox = adModal.querySelector('.ad-media-box');
      mediaBox.innerHTML = media
        ? '<img src="' + media + '" class="img-fluid rounded" alt="">'
        : '<div class="p-5 text-center text-muted bg-light rounded"><i class="bi bi-badge-ad fs-1"></i></div>';

      var visit = adModal.querySelector('.ad-visit');
      if (link) { visit.href = link; visit.classList.remove('d-none'); }
      else { visit.classList.add('d-none'); }

      var form  = adModal.querySelector('.ad-form');
      var btn   = adModal.querySelector('.ad-claim-btn');
      var count = adModal.querySelector('.ad-countdown');

      form.action = form.getAttribute('data-base') + '/' + adId;
      btn.disabled = true;

      var left = seconds;
      count.textContent = left;
      count.parentElement.classList.remove('d-none');

      clearInterval(timer);
      timer = setInterval(function () {
        left -= 1;
        count.textContent = left > 0 ? left : 0;
        if (left <= 0) {
          clearInterval(timer);
          btn.disabled = false;
          count.parentElement.classList.add('d-none');
        }
      }, 1000);
    });

    adModal.addEventListener('hidden.bs.modal', function () {
      clearInterval(timer);
    });
  }

  /* ---------- auto-submit filter selects ---------- */
  document.querySelectorAll('[data-autosubmit]').forEach(function (el) {
    el.addEventListener('change', function () { el.form.submit(); });
  });
})();
