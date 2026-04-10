import './stimulus_bootstrap.js';
import './styles/app.css';

function initUi() {
    // Toggle navigation
    const navToggles = document.querySelectorAll('[data-nav-toggle]');
    navToggles.forEach((btn) => {
        btn.removeEventListener('click', handleNavToggle);
        btn.addEventListener('click', handleNavToggle);
    });

    // Open Modal
    const modalOpeners = document.querySelectorAll('[data-open-modal]');
    modalOpeners.forEach((btn) => {
        btn.removeEventListener('click', handleModalOpen);
        btn.addEventListener('click', handleModalOpen);
    });

    // Close Modal
    const modalClosers = document.querySelectorAll('[data-close-modal]');
    modalClosers.forEach((btn) => {
        btn.removeEventListener('click', handleModalClose);
        btn.addEventListener('click', handleModalClose);
    });

    // Emoji picker is now handled in base.html.twig setupEmojiPicker()
}

function handleNavToggle(e) {
    const btn = e.currentTarget;
    const open = document.body.classList.toggle('nav-open');
    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
}

function handleModalOpen(e) {
    const btn = e.currentTarget;
    const id = btn.getAttribute('data-open-modal');
    const d = id ? document.getElementById(id) : null;
    if (d && typeof d.showModal === 'function') {
        d.showModal();
    }
}

function handleModalClose(e) {
    const btn = e.currentTarget;
    const id = btn.getAttribute('data-close-modal');
    const d = id ? document.getElementById(id) : null;
    if (d && typeof d.close === 'function') {
        d.close();
    }
}

document.addEventListener('turbo:load', initUi);
document.addEventListener('DOMContentLoaded', initUi);
