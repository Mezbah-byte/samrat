/* Pull-cord lamp on the auth pages.
 *
 * Progressive enhancement: the markup ships with the form visible, so if this
 * script (or GSAP) never runs, people can still sign in. Only once we know the
 * toggle works do we hide the form and make the lamp reveal it. */
(function () {
    'use strict';

    var body    = document.body;
    var panel   = document.querySelector('.auth-panel');
    var bead    = document.querySelector('.cord-bead');
    var line    = document.querySelector('.cord-line');
    var hit     = document.querySelector('.cord-hit');
    var hasGsap = typeof window.gsap !== 'undefined' && typeof window.Draggable !== 'undefined';

    if (! panel || ! bead || ! line || ! hit) {
        return;
    }

    var CORD_Y   = 180;   // resting y2 of the cord line, matches the SVG
    var PULL_MAX = 60;    // how far the bead can be dragged
    var PULL_MIN = 30;    // drag past this and the lamp toggles

    var isOn = false;
    var click = new Audio(hit.getAttribute('data-click') || '');
    click.volume = 0.35;

    function setState(on) {
        isOn = on;
        body.setAttribute('data-on', on ? 'true' : 'false');
        body.style.setProperty('--on', on ? 1 : 0);
        body.style.backgroundColor = on ? '#1c1f24' : '#121417';
        panel.classList.toggle('is-hidden', ! on);
        hit.setAttribute('aria-pressed', on ? 'true' : 'false');
        hit.setAttribute('aria-label', on ? 'Turn the lamp off' : 'Turn the lamp on');
    }

    function toggle() {
        click.currentTime = 0;
        click.play().catch(function () {});   // blocked autoplay is not an error worth surfacing
        setState(! isOn);

        if (isOn) {
            // Focus the first field so the keyboard path continues naturally.
            var first = panel.querySelector('input:not([type=hidden]), select');
            if (first) {
                window.setTimeout(function () { first.focus(); }, 350);
            }
        }
    }

    // Start dark only now that we know the toggle is wired up.
    setState(false);

    hit.setAttribute('role', 'button');
    hit.setAttribute('tabindex', '0');
    hit.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ' || e.key === 'Spacebar') {
            e.preventDefault();
            toggle();
        }
    });

    if (! hasGsap) {
        // No drag library, so a plain click has to do the switching.
        hit.addEventListener('click', toggle);
        return;
    }

    // With Draggable active every pointer event on the cord is captured and
    // stopped at the document level, so the tap has to be handled by its own
    // callbacks rather than by a listener on the element.
    var dragged = false;

    gsap.registerPlugin(Draggable);

    Draggable.create(hit, {
        type: 'y',
        bounds: { minY: 0, maxY: PULL_MAX },
        onPress: function () {
            dragged = false;
        },
        onDrag: function () {
            dragged = true;
            gsap.set(bead, { y: this.y });
            gsap.set(line, { attr: { y2: CORD_Y + this.y } });
        },
        onRelease: function () {
            // A tap switches it; a drag only counts once it is pulled far enough.
            if (! dragged || this.y > PULL_MIN) {
                toggle();
            }

            gsap.to([bead, hit], { y: 0, duration: .5, ease: 'back.out(2.5)' });
            gsap.to(line, { attr: { y2: CORD_Y }, duration: .5, ease: 'back.out(2.5)' });
        }
    });
}());
