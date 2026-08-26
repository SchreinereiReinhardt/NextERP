(() => {
    const storageKey = 'nexterp.navigation.openGroup';
    const groups = [...document.querySelectorAll('.erp-nav-group[data-nav-key]')];

    let stored = '';
    try { stored = localStorage.getItem(storageKey) || ''; } catch (_) {}

    const activeGroup = groups.find((group) => group.open);
    if (!activeGroup && stored) {
        const remembered = groups.find((group) => group.dataset.navKey === stored);
        if (remembered) remembered.open = true;
    }

    groups.forEach((group) => {
        group.addEventListener('toggle', () => {
            if (!group.open) return;
            groups.forEach((other) => {
                if (other !== group && other.open) other.open = false;
            });
            try { localStorage.setItem(storageKey, group.dataset.navKey || ''); } catch (_) {}
        });
    });

    document.addEventListener('click', (event) => {
        document.querySelectorAll('.erp-create-menu[open]').forEach((menu) => {
            if (!menu.contains(event.target)) menu.removeAttribute('open');
        });
    });
})();
