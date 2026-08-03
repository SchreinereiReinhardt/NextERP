(() => {
    const storageKey = 'nexterp.navigation.openGroups';
    const groups = [...document.querySelectorAll('.erp-nav-group[data-nav-key]')];

    let stored = [];
    try { stored = JSON.parse(localStorage.getItem(storageKey) || '[]'); } catch (_) {}

    groups.forEach((group) => {
        const key = group.dataset.navKey;
        if (!group.open && stored.includes(key)) group.open = true;
        group.addEventListener('toggle', () => {
            const openKeys = groups.filter((item) => item.open).map((item) => item.dataset.navKey);
            localStorage.setItem(storageKey, JSON.stringify(openKeys));
        });
    });

    document.addEventListener('click', (event) => {
        document.querySelectorAll('.erp-create-menu[open]').forEach((menu) => {
            if (!menu.contains(event.target)) menu.removeAttribute('open');
        });
    });
})();
