(function () {
    'use strict';

    const grid = document.getElementById('erpCustomerGrid');
    const search = document.getElementById('erpCustomerSearch');
    const clear = document.getElementById('erpCustomerSearchClear');
    const alpha = document.getElementById('erpCustomerAlpha');
    const count = document.getElementById('erpCustomerVisibleCount');
    const empty = document.getElementById('erpCustomerEmptyFilter');

    if (!grid || !search || !alpha) return;

    const cards = Array.from(grid.querySelectorAll('.erp-customer-card'));
    let selectedLetter = 'all';

    const normalize = (value) => (value || '')
        .toLocaleLowerCase('de-DE')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .trim();

    function applyFilter() {
        const query = normalize(search.value);
        let visible = 0;

        cards.forEach((card) => {
            const letterMatches = selectedLetter === 'all' || card.dataset.letter === selectedLetter;
            const searchMatches = query === '' || normalize(card.dataset.search).includes(query);
            const show = letterMatches && searchMatches;
            card.hidden = !show;
            if (show) visible += 1;
        });

        if (count) count.textContent = String(visible);
        if (empty) empty.hidden = visible !== 0;
        clear.hidden = search.value.length === 0;
    }

    alpha.addEventListener('click', (event) => {
        const button = event.target.closest('button[data-letter]');
        if (!button) return;
        selectedLetter = button.dataset.letter;
        alpha.querySelectorAll('button').forEach((item) => item.classList.toggle('is-active', item === button));
        applyFilter();
    });

    search.addEventListener('input', applyFilter);
    clear.addEventListener('click', () => {
        search.value = '';
        search.focus();
        applyFilter();
    });

    applyFilter();
})();
