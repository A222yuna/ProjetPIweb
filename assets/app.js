import './bootstrap.js';
import './stimulus_bootstrap.js';
import './styles/app.css';

function initUi() {
    document.querySelectorAll('[data-nav-toggle]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const open = document.body.classList.toggle('nav-open');
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    });

    document.querySelectorAll('[data-open-modal]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const id = btn.getAttribute('data-open-modal');
            const d = id ? document.getElementById(id) : null;
            if (d && typeof d.showModal === 'function') {
                d.showModal();
            }
        });
    });

    document.querySelectorAll('[data-close-modal]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const id = btn.getAttribute('data-close-modal');
            const d = id ? document.getElementById(id) : null;
            if (d && typeof d.close === 'function') {
                d.close();
            }
        });
    });
}

document.addEventListener('DOMContentLoaded', initUi);
