/* Ambient network behind the user panel.
 *
 * Nodes drift, near ones link up, and packets travel along the links now and
 * then. Purely decorative: the canvas ships empty, so nothing here is needed
 * for the page to work. Colours come from the CSS tokens, so the theme toggle
 * repaints it through the `samrat:theme` event. */
(function () {
    'use strict';

    var canvas = document.getElementById('fxNet');

    if (! canvas || ! canvas.getContext) {
        return;
    }

    var ctx  = canvas.getContext('2d');
    var root = document.documentElement;
    var calm = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    var LINK      = 148;    // px between nodes before a link is drawn
    var DENSITY   = 15000;  // one node per this many css px of area
    var MAX_NODES = 90;     // ceiling so a 4K window does not melt a laptop
    var PACKETS   = 5;      // packets in flight at once

    var nodes   = [];
    var packets = [];
    var w = 0, h = 0, dpr = 1;
    var frame = null;
    var ink = {};

    function token(name, fallback) {
        var v = getComputedStyle(root).getPropertyValue(name).trim();
        return v || fallback;
    }

    function readInk() {
        ink = {
            node:   token('--fx-node',   'rgba(148,163,184,.55)'),
            link:   token('--fx-link',   '99,102,241'),
            packet: token('--fx-packet', 'rgba(139,92,246,.9)'),
            glow:   token('--fx-glow',   'rgba(87,91,235,.5)')
        };
    }

    function rand(min, max) {
        return min + Math.random() * (max - min);
    }

    function build() {
        var count = Math.min(MAX_NODES, Math.round((w * h) / DENSITY));
        nodes = [];

        for (var i = 0; i < count; i++) {
            nodes.push({
                x:  rand(0, w),
                y:  rand(0, h),
                vx: rand(-.16, .16),
                vy: rand(-.16, .16),
                r:  rand(1, 2.4)
            });
        }

        packets = [];
        for (var p = 0; p < PACKETS; p++) {
            packets.push(spawn());
        }
    }

    /* A packet is just a pair of node indices plus progress along the pair. */
    function spawn() {
        return {
            a: Math.floor(rand(0, nodes.length)),
            b: Math.floor(rand(0, nodes.length)),
            t: -rand(0, 1),                 // negative start staggers the launches
            speed: rand(.0022, .0055)
        };
    }

    function resize() {
        var rect = canvas.getBoundingClientRect();

        dpr = Math.min(window.devicePixelRatio || 1, 2);
        w   = Math.max(1, Math.round(rect.width));
        h   = Math.max(1, Math.round(rect.height));

        canvas.width  = Math.round(w * dpr);
        canvas.height = Math.round(h * dpr);
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

        build();
    }

    function step() {
        var i, j, a, b, dx, dy, dist;

        for (i = 0; i < nodes.length; i++) {
            a = nodes[i];
            a.x += a.vx;
            a.y += a.vy;

            // Bounce rather than wrap, so links never snap across the screen.
            if (a.x < 0 || a.x > w) { a.vx *= -1; a.x = Math.max(0, Math.min(w, a.x)); }
            if (a.y < 0 || a.y > h) { a.vy *= -1; a.y = Math.max(0, Math.min(h, a.y)); }
        }

        ctx.clearRect(0, 0, w, h);

        /* links */
        ctx.lineWidth = 1;
        for (i = 0; i < nodes.length; i++) {
            a = nodes[i];
            for (j = i + 1; j < nodes.length; j++) {
                b  = nodes[j];
                dx = a.x - b.x;
                dy = a.y - b.y;
                dist = Math.sqrt(dx * dx + dy * dy);

                if (dist < LINK) {
                    ctx.strokeStyle = 'rgba(' + ink.link + ',' + (.3 * (1 - dist / LINK)).toFixed(3) + ')';
                    ctx.beginPath();
                    ctx.moveTo(a.x, a.y);
                    ctx.lineTo(b.x, b.y);
                    ctx.stroke();
                }
            }
        }

        /* nodes */
        ctx.fillStyle = ink.node;
        for (i = 0; i < nodes.length; i++) {
            a = nodes[i];
            ctx.beginPath();
            ctx.arc(a.x, a.y, a.r, 0, Math.PI * 2);
            ctx.fill();
        }

        /* packets */
        for (i = 0; i < packets.length; i++) {
            var pk = packets[i];
            pk.t += pk.speed;

            if (pk.t >= 1) {
                packets[i] = spawn();
                continue;
            }
            if (pk.t < 0) {
                continue;
            }

            a = nodes[pk.a];
            b = nodes[pk.b];
            if (! a || ! b) {
                packets[i] = spawn();
                continue;
            }

            var x = a.x + (b.x - a.x) * pk.t;
            var y = a.y + (b.y - a.y) * pk.t;

            // Fade in and out at the ends so packets do not pop.
            var fade = Math.sin(pk.t * Math.PI);

            ctx.beginPath();
            ctx.fillStyle = ink.glow;
            ctx.globalAlpha = .35 * fade;
            ctx.arc(x, y, 7, 0, Math.PI * 2);
            ctx.fill();

            ctx.beginPath();
            ctx.fillStyle = ink.packet;
            ctx.globalAlpha = fade;
            ctx.arc(x, y, 2.2, 0, Math.PI * 2);
            ctx.fill();
            ctx.globalAlpha = 1;
        }
    }

    function loop() {
        step();
        frame = window.requestAnimationFrame(loop);
    }

    function start() {
        if (frame === null) {
            frame = window.requestAnimationFrame(loop);
        }
    }

    function stop() {
        if (frame !== null) {
            window.cancelAnimationFrame(frame);
            frame = null;
        }
    }

    readInk();
    resize();

    if (calm) {
        step();          // one still frame: the constellation without the motion
    } else {
        start();
    }

    var pending = null;
    window.addEventListener('resize', function () {
        window.clearTimeout(pending);
        pending = window.setTimeout(function () {
            resize();
            if (calm) { step(); }
        }, 180);
    });

    window.addEventListener('samrat:theme', function () {
        readInk();
        if (calm) { step(); }
    });

    // Nothing to animate for a hidden tab.
    document.addEventListener('visibilitychange', function () {
        if (calm) { return; }
        if (document.hidden) { stop(); } else { start(); }
    });
}());
