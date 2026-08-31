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

  /* ---------- ad player ----------
   *
   * Three kinds of creative, picked by the ad's `source`:
   *
   *   upload - an uploaded image, or an image/video file URL. Countdown gates
   *            the confirm button; a video also has to reach its end.
   *   embed  - an ad network's own HTML/JS tag (Adsterra, PropellerAds, AdSense,
   *            Monetag, ...). It runs inside a sandboxed iframe with no
   *            same-origin access, so the network's script cannot read the
   *            session or reach into this page. Countdown gates the confirm.
   *   vast   - a VAST/VPAID tag played through Google IMA. The confirm unlocks
   *            on the SDK's COMPLETE event, so the quota only clears when the
   *            network's video actually finished. If the SDK or the tag fails
   *            (offline, blocked, no fill) it degrades to the countdown rather
   *            than trapping the user.
   *
   * None of this is the real gate. Investment_lib::register_ad_view is: it
   * checks the active plan and the one-view-per-ad-per-day rule server-side.
   */
  var IMA_SDK = 'https://imasdk.googleapis.com/js/sdkloader/ima3.js';
  var imaLoad = null;

  function loadIma() {
    if (window.google && window.google.ima) return Promise.resolve(true);
    if (imaLoad) return imaLoad;

    imaLoad = new Promise(function (resolve) {
      var s = document.createElement('script');
      s.src = IMA_SDK;
      s.async = true;
      s.onload = function () { resolve(!!(window.google && window.google.ima)); };
      s.onerror = function () { resolve(false); };
      document.head.appendChild(s);
    });

    return imaLoad;
  }

  var adModal = document.getElementById('adModal');
  if (adModal) {
    var timer   = null;
    var imaMgr  = null;   // google.ima.AdsManager for the open ad
    var imaLdr  = null;   // google.ima.AdsLoader
    var session = 0;      // bumped on every open, so a late SDK callback for a
                          // closed ad cannot unlock the button

    var stage    = adModal.querySelector('.ad-stage');
    var stageMsg = adModal.querySelector('.ad-stage-note');
    var mediaBox = adModal.querySelector('.ad-media-box');
    var visit    = adModal.querySelector('.ad-visit');
    var form     = adModal.querySelector('.ad-form');
    var btn      = adModal.querySelector('.ad-claim-btn');
    var count    = adModal.querySelector('.ad-countdown');

    function teardown() {
      clearInterval(timer);

      if (imaMgr) { try { imaMgr.destroy(); } catch (e) {} imaMgr = null; }
      if (imaLdr) { try { imaLdr.destroy(); } catch (e) {} imaLdr = null; }

      stage.innerHTML = '';
      stage.classList.add('d-none');
      stageMsg.classList.add('d-none');
      mediaBox.innerHTML = '';
      mediaBox.classList.add('d-none');
    }

    function unlock() {
      btn.disabled = false;
      count.parentElement.classList.add('d-none');
    }

    /** Countdown. `onDone` decides whether reaching zero is enough to unlock. */
    function countdown(seconds, onDone) {
      var left = seconds;

      count.textContent = left;
      count.parentElement.classList.remove('d-none');

      clearInterval(timer);
      timer = setInterval(function () {
        left -= 1;
        count.textContent = left > 0 ? left : 0;
        if (left <= 0) {
          clearInterval(timer);
          onDone();
        }
      }, 1000);

      if (seconds <= 0) { clearInterval(timer); onDone(); }
    }

    /**
     * Google's ad server dedupes on `correlator`: send the same value twice and
     * the second request comes back as an empty VAST, which reads on this page
     * as "the network had nothing to show". Sample and GAM tags are normally
     * pasted with the parameter left blank, so a fresh one is filled in per
     * request. A tag that already carries a value is left alone.
     */
    function freshCorrelator(tag) {
      var value = String(Date.now()) + String(Math.floor(Math.random() * 1000000));

      if (/[?&]correlator=(&|$)/.test(tag)) {
        return tag.replace(/([?&]correlator=)(&|$)/, '$1' + value + '$2');
      }
      if (/[?&]correlator=/.test(tag)) {
        return tag;
      }
      // Only Google's endpoints take the parameter; other networks sign their
      // tags and an extra query string can invalidate them.
      if (/(doubleclick\.net|googlesyndication\.com|googleadservices\.com)/.test(tag)) {
        return tag + (tag.indexOf('?') === -1 ? '?' : '&') + 'correlator=' + value;
      }
      return tag;
    }

    /**
     * VAST through IMA.
     *
     * The countdown always has to run out - no event can unlock the confirm
     * early. On top of that, a creative that did start has to report COMPLETE,
     * so skipping or closing the ad halfway leaves the button locked.
     *
     * ALL_ADS_COMPLETED on its own proves nothing: IMA fires it for an empty
     * response too, which would hand out a free view for an ad nobody saw. It
     * only counts once STARTED has been seen.
     */
    function playVast(tag, seconds, mine) {
      stage.classList.remove('d-none');
      stage.innerHTML =
        '<div class="ad-video-wrap">' +
          '<video class="ad-video" playsinline></video>' +
          '<div class="ad-ima"></div>' +
        '</div>';

      var video  = stage.querySelector('.ad-video');
      var holder = stage.querySelector('.ad-ima');

      var started  = false;
      var finished = false;
      var timeUp   = false;
      var dead     = false;   // no fill, blocked SDK, playback error

      function settle() {
        if (session !== mine || !timeUp) return;
        // Either the network had nothing to show (dead), or what it showed ran
        // to the end. A half-watched ad satisfies neither.
        if (dead || finished) unlock();
      }

      function degrade() {
        if (session !== mine) return;
        dead = true;
        stageMsg.classList.remove('d-none');
        settle();
      }

      countdown(seconds, function () { timeUp = true; settle(); });

      loadIma().then(function (ok) {
        if (session !== mine) return;
        if (!ok) { degrade(); return; }

        var ima = window.google.ima;

        try {
          var display = new ima.AdDisplayContainer(holder, video);
          display.initialize();

          imaLdr = new ima.AdsLoader(display);

          imaLdr.addEventListener(ima.AdsManagerLoadedEvent.Type.ADS_MANAGER_LOADED, function (e) {
            if (session !== mine) return;

            imaMgr = e.getAdsManager(video);

            imaMgr.addEventListener(ima.AdErrorEvent.Type.AD_ERROR, degrade);

            imaMgr.addEventListener(ima.AdEvent.Type.STARTED, function () {
              started = true;
            });

            imaMgr.addEventListener(ima.AdEvent.Type.COMPLETE, function () {
              finished = true;
              settle();
            });

            imaMgr.addEventListener(ima.AdEvent.Type.ALL_ADS_COMPLETED, function () {
              if (started) { finished = true; settle(); }
              else { degrade(); }
            });

            try {
              imaMgr.init(holder.clientWidth || 640, 360, ima.ViewMode.NORMAL);
              imaMgr.start();
            } catch (err) {
              degrade();
            }
          }, false);

          imaLdr.addEventListener(ima.AdErrorEvent.Type.AD_ERROR, degrade, false);

          var req = new ima.AdsRequest();
          req.adTagUrl = freshCorrelator(tag);
          req.linearAdSlotWidth  = 640;
          req.linearAdSlotHeight = 360;
          imaLdr.requestAds(req);
        } catch (err) {
          degrade();
        }
      });
    }

    /** A network's own tag, contained in a sandboxed iframe. */
    function playEmbed(code, seconds) {
      stage.classList.remove('d-none');

      var frame = document.createElement('iframe');
      // No allow-same-origin: with allow-scripts that pair would let the tag
      // out of the sandbox and back into this document.
      frame.setAttribute('sandbox', 'allow-scripts allow-popups allow-popups-to-escape-sandbox allow-forms');
      frame.setAttribute('referrerpolicy', 'no-referrer-when-downgrade');
      frame.className = 'ad-frame';
      frame.srcdoc =
        '<!doctype html><meta charset="utf-8">' +
        '<meta name="viewport" content="width=device-width,initial-scale=1">' +
        '<style>html,body{margin:0;height:100%;display:grid;place-items:center;' +
        'background:#0b0e14;color:#94a3b8;font:14px system-ui}</style>' +
        '<body>' + code + '</body>';

      stage.appendChild(frame);
      countdown(seconds, unlock);
    }

    /** An uploaded or linked file. A video also has to finish. */
    function playFile(url, isVideo, seconds) {
      if (isVideo && url) {
        stage.classList.remove('d-none');
        stage.innerHTML = '<div class="ad-video-wrap"><video class="ad-video" src="'
          + encodeURI(url) + '" playsinline autoplay controls></video></div>';

        var video = stage.querySelector('.ad-video');
        var ended = false;
        var timeUp = false;

        video.addEventListener('ended', function () {
          ended = true;
          if (timeUp) unlock();
        });
        video.addEventListener('error', function () {
          // Broken file - do not strand the user on a video that cannot play.
          ended = true;
          stageMsg.classList.remove('d-none');
          if (timeUp) unlock();
        });

        countdown(seconds, function () {
          timeUp = true;
          if (ended) unlock();
        });
        return;
      }

      mediaBox.classList.remove('d-none');
      mediaBox.innerHTML = url
        ? '<img src="' + encodeURI(url) + '" class="img-fluid rounded" alt="">'
        : '<div class="p-5 text-center text-muted bg-light rounded"><i class="bi bi-badge-ad fs-1"></i></div>';

      countdown(seconds, unlock);
    }

    adModal.addEventListener('show.bs.modal', function (event) {
      var trigger = event.relatedTarget;
      if (!trigger) return;

      teardown();
      session += 1;
      var mine = session;

      var seconds = parseInt(trigger.getAttribute('data-seconds') || '10', 10);
      var adId    = trigger.getAttribute('data-ad-id');
      var title   = trigger.getAttribute('data-ad-title') || 'Advertisement';
      var source  = trigger.getAttribute('data-ad-source') || 'upload';
      var type    = trigger.getAttribute('data-ad-type') || 'image';
      var media   = trigger.getAttribute('data-ad-media') || '';
      var vast    = trigger.getAttribute('data-ad-vast') || '';
      var embed   = trigger.getAttribute('data-ad-embed') || '';
      var link    = trigger.getAttribute('data-ad-link') || '';
      var body    = trigger.getAttribute('data-ad-body') || '';

      adModal.querySelector('.ad-title').textContent = title;
      adModal.querySelector('.ad-body-text').textContent = body;

      if (link) { visit.href = link; visit.classList.remove('d-none'); }
      else { visit.classList.add('d-none'); }

      form.action = form.getAttribute('data-base') + '/' + adId;
      btn.disabled = true;

      if (source === 'vast' && vast) {
        playVast(vast, seconds, mine);
      } else if (source === 'embed' && embed) {
        playEmbed(embed, seconds);
      } else {
        playFile(media, type === 'video', seconds);
      }
    });

    adModal.addEventListener('hidden.bs.modal', function () {
      session += 1;
      teardown();
    });
  }

  /* ---------- auto-submit filter selects ---------- */
  document.querySelectorAll('[data-autosubmit]').forEach(function (el) {
    el.addEventListener('change', function () { el.form.submit(); });
  });
})();
