(function () {
    'use strict';

    function initProjectPermissions() {
        const toggle = document.querySelector('.erp-permission-toggle');
        const editor = document.getElementById('erp-permission-editor');

        if (!toggle || !editor || toggle.dataset.erpBound === '1') {
            return;
        }

        toggle.dataset.erpBound = '1';
        toggle.addEventListener('click', function (event) {
            event.preventDefault();

            const isHidden = editor.hasAttribute('hidden');
            if (isHidden) {
                editor.removeAttribute('hidden');
            } else {
                editor.setAttribute('hidden', '');
            }

            toggle.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
            toggle.textContent = isHidden ? 'Schließen' : 'Bearbeiten';
        });

        editor.querySelectorAll('[data-permission-user]').forEach(function (row) {
            row.querySelectorAll('[data-action]').forEach(function (button) {
                button.addEventListener('click', function () {
                    const checked = button.dataset.action === 'all';
                    row.querySelectorAll('.erp-folder-permissions input[type="checkbox"]').forEach(function (box) { box.checked = checked; });
                });
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initProjectPermissions, { once: true });
    } else {
        initProjectPermissions();
    }
})();
