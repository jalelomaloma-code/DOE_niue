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
