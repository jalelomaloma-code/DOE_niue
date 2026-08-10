import './bootstrap';

// Mobile navigation disclosure. The header markup is a native
// <details>/<summary> pair, so the menu opens and closes with zero
// JavaScript if this script fails to load — this is progressive
// enhancement on top of that, not the mechanism itself. Deliberately
// framework-free: the public site ships no Alpine, React or Vue.
document.addEventListener('DOMContentLoaded', () => {
    const disclosure = document.getElementById('nav-disclosure');
    const toggle = document.getElementById('menu-toggle');

    if (!disclosure || !toggle) return;

    // <summary> already carries an implicit "button" role whose
    // expanded/collapsed state most assistive tech computes straight from
    // the parent <details> "open" attribute — aria-expanded isn't load
    // -bearing here. We mirror it anyway as a cheap defensive enhancement
    // for user agents that don't compute it, synced off the native
    // "toggle" event so it can never drift, whether state changed by a
    // click or by our own Escape handler below.
    const syncExpanded = () => toggle.setAttribute('aria-expanded', String(disclosure.open));
    disclosure.addEventListener('toggle', syncExpanded);
    syncExpanded();

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && disclosure.open) {
            disclosure.open = false;
            toggle.focus();
        }
    });
});

document.addEventListener('DOMContentLoaded', () => {
    const slider = document.querySelector('[data-hero-slider]');

    if (!slider) return;

    const slides = [...slider.querySelectorAll('[data-hero-slide]')];
    const dots = [...slider.querySelectorAll('[data-hero-dot]')];
    const previous = slider.querySelector('[data-hero-prev]');
    const next = slider.querySelector('[data-hero-next]');

    if (slides.length < 2) return;

    let current = 0;
    let timer = null;
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const show = (index) => {
        current = (index + slides.length) % slides.length;

        slides.forEach((slide, slideIndex) => {
            slide.classList.toggle('opacity-45', slideIndex === current);
            slide.classList.toggle('opacity-0', slideIndex !== current);
        });

        dots.forEach((dot, dotIndex) => {
            dot.classList.toggle('bg-white', dotIndex === current);
            dot.classList.toggle('bg-white/20', dotIndex !== current);

            if (dotIndex === current) {
                dot.setAttribute('aria-current', 'true');
            } else {
                dot.removeAttribute('aria-current');
            }
        });
    };

    const stop = () => {
        if (timer) {
            window.clearInterval(timer);
            timer = null;
        }
    };

    const start = () => {
        if (!reduceMotion) {
            stop();
            timer = window.setInterval(() => show(current + 1), 6500);
        }
    };

    previous?.addEventListener('click', () => {
        show(current - 1);
        start();
    });

    next?.addEventListener('click', () => {
        show(current + 1);
        start();
    });

    dots.forEach((dot) => {
        dot.addEventListener('click', () => {
            show(Number(dot.dataset.heroDot));
            start();
        });
    });

    slider.addEventListener('mouseenter', stop);
    slider.addEventListener('mouseleave', start);
    slider.addEventListener('focusin', stop);
    slider.addEventListener('focusout', start);

    show(0);
    start();
});
