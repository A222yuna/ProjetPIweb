import './stimulus_bootstrap.js';
import './styles/app.css';

function initUi() {
    // We use a custom property to avoid double initialization
    // However, event listeners are normally cleared or replaced if we are not careful.
    // In this simple implementation, we just attach them.
    // To be perfectly safe with Turbo, we can use delegation or just remove old listeners if needed.
    
    // Toggle navigation
    const navToggles = document.querySelectorAll('[data-nav-toggle]');
    navToggles.forEach((btn) => {
        // Remove existing listener to avoid duplicates
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
