(function () {
    'use strict';
    const tabs = document.getElementById('erpCustomerTabs');
    if (!tabs) return;
    const buttons = Array.from(tabs.querySelectorAll('[data-customer-tab]'));
    const panels = Array.from(document.querySelectorAll('[data-customer-panel]'));
    const layout = document.querySelector('.erp-customer-layout');

    function activate(name, updateHash) {
        buttons.forEach((button) => button.classList.toggle('is-active', button.dataset.customerTab === name));
        panels.forEach((panel) => panel.classList.toggle('is-active', panel.dataset.customerPanel === name));
        if (layout) layout.classList.toggle('is-overview-hidden', name === 'overview');
        if (updateHash && history.replaceState) history.replaceState(null, '', '#'+name);
        tabs.scrollIntoView({block: 'nearest'});
    }
    tabs.addEventListener('click', (event) => {
        const button = event.target.closest('[data-customer-tab]');
        if (button) activate(button.dataset.customerTab, true);
    });
    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-open-customer-tab]');
        if (button) activate(button.dataset.openCustomerTab, true);
    });
    const initial = location.hash.slice(1);
    activate(buttons.some((b) => b.dataset.customerTab === initial) ? initial : 'overview', false);
})();
