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

    initEmojiPicker();
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

function initEmojiPicker() {
    const emojiTriggers = document.querySelectorAll('[data-emoji-picker]');
    if (emojiTriggers.length === 0) return;

    // Remove existing picker if any
    let picker = document.querySelector('.emoji-picker-dropdown');
    if (picker) picker.remove();

    picker = document.createElement('div');
    picker.className = 'emoji-picker-dropdown';
    
    const emojis = ['😊', '😂', '😍', '🤔', '👍', '🙏', '❤️', '✨', '🔥', '👏', '🙌', '😢', '😎', '💡', '✅', '🌈', '⭐', '🎉', '💪', '🚀'];
    
    emojis.forEach(emoji => {
        const span = document.createElement('span');
        span.innerHTML = emoji;
        span.addEventListener('click', (e) => {
            e.stopPropagation();
            const targetId = picker.getAttribute('data-current-target');
            const targetInput = document.getElementById(targetId);
            if (targetInput) {
                const start = targetInput.selectionStart;
                const end = targetInput.selectionEnd;
                const text = targetInput.value;
                targetInput.value = text.substring(0, start) + emoji + text.substring(end);
                targetInput.focus();
                // Set cursor position after inserted emoji
                const newPos = start + emoji.length;
                targetInput.setSelectionRange(newPos, newPos);
                targetInput.dispatchEvent(new Event('input'));
            }
            picker.style.display = 'none';
        });
        picker.appendChild(span);
    });

    document.body.appendChild(picker);

    emojiTriggers.forEach(trigger => {
        trigger.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            
            const targetId = trigger.getAttribute('data-emoji-target');
            const rect = trigger.getBoundingClientRect();
            
            // If clicking the same trigger that's already open, close it
            if (picker.style.display === 'grid' && picker.getAttribute('data-current-target') === targetId) {
                picker.style.display = 'none';
                return;
            }

            picker.setAttribute('data-current-target', targetId);
            picker.style.display = 'grid';
            
            // Position logic: prefer bottom, fallback to top if no space
            let top = rect.bottom + 5;
            let left = rect.left;

            // Check if picker goes off screen
            if (top + picker.offsetHeight > window.innerHeight) {
                top = rect.top - picker.offsetHeight - 5;
            }
            if (left + picker.offsetWidth > window.innerWidth) {
                left = window.innerWidth - picker.offsetWidth - 10;
            }

            picker.style.top = `${top}px`;
            picker.style.left = `${left}px`;
        });
    });

    document.addEventListener('click', (e) => {
        if (!picker.contains(e.target)) {
            picker.style.display = 'none';
        }
    });
}

document.addEventListener('turbo:load', initUi);
document.addEventListener('DOMContentLoaded', initUi);
