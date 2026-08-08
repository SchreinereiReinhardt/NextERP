(() => {
    const trigger = document.getElementById('erpCommandTrigger');
    const overlay = document.getElementById('erpCommandOverlay');
    const dialog = overlay?.querySelector('.erp-command-dialog');
    const input = document.getElementById('erpCommandInput');
    const results = document.getElementById('erpCommandResults');
    if (!trigger || !overlay || !dialog || !input || !results) return;

    const searchUrl = dialog.dataset.searchUrl;
    let controller = null;
    let activeIndex = -1;
    let items = [];

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>'"]/g, (char) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;',
    }[char]));

    const shortcuts = [
        ['Neuer Kunde', '👤', document.querySelector('a[href*="/customers/form"]')?.href],
        ['Neues Projekt', 'Projekt', document.querySelector('a[href*="/projects/form"]')?.href],
        ['Zeiterfassung öffnen', 'Zeit', document.querySelector('a[href*="/workdays"]')?.href],
        ['Rapporte öffnen', 'Rapport', document.querySelector('a[href*="/reports"]')?.href],
    ].filter((entry) => entry[2]);

    const open = () => {
        overlay.hidden = false;
        document.body.classList.add('erp-command-open');
        input.value = '';
        renderShortcuts();
        requestAnimationFrame(() => input.focus());
    };
    const close = () => {
        overlay.hidden = true;
        document.body.classList.remove('erp-command-open');
        controller?.abort();
    };

    const renderShortcuts = () => {
        items = shortcuts.map(([title, icon, url]) => ({ type: 'Schnellaktion', title, icon, subtitle: 'Direkt öffnen', url }));
        render(items, 'Schnellaktionen');
    };

    const render = (data, heading = 'Ergebnisse') => {
        activeIndex = data.length ? 0 : -1;
        items = data;
        if (!data.length) {
            results.innerHTML = '<p class="erp-command-empty">Keine passenden Ergebnisse gefunden.</p>';
            return;
        }
        results.innerHTML = `<div class="erp-command-group-title">${escapeHtml(heading)}</div>` + data.map((item, index) => `
            <a class="erp-command-result${index === activeIndex ? ' is-active' : ''}" href="${escapeHtml(item.url)}" data-index="${index}">
                <span class="erp-command-result-icon">${escapeHtml(item.icon)}</span>
                <span class="erp-command-result-main"><strong>${escapeHtml(item.title)}</strong><small>${escapeHtml(item.subtitle)}</small></span>
                <span class="erp-command-result-type">${escapeHtml(item.type)}</span>
            </a>`).join('');
    };

    const select = (index) => {
        if (!items.length) return;
        activeIndex = (index + items.length) % items.length;
        results.querySelectorAll('.erp-command-result').forEach((node, idx) => node.classList.toggle('is-active', idx === activeIndex));
        results.querySelector('.erp-command-result.is-active')?.scrollIntoView({ block: 'nearest' });
    };

    let timer = null;
    input.addEventListener('input', () => {
        clearTimeout(timer);
        const query = input.value.trim();
        if (query.length < 2) {
            renderShortcuts();
            return;
        }
        timer = setTimeout(async () => {
            controller?.abort();
            controller = new AbortController();
            results.innerHTML = '<p class="erp-command-hint">Suche läuft …</p>';
            try {
                const url = `${searchUrl}?q=${encodeURIComponent(query)}`;
                const response = await fetch(url, { headers: { Accept: 'application/json' }, signal: controller.signal });
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                const payload = await response.json();
                render(Array.isArray(payload.results) ? payload.results : []);
            } catch (error) {
                if (error.name !== 'AbortError') results.innerHTML = '<p class="erp-command-empty">Die Suche konnte nicht geladen werden.</p>';
            }
        }, 180);
    });

    input.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowDown') { event.preventDefault(); select(activeIndex + 1); }
        if (event.key === 'ArrowUp') { event.preventDefault(); select(activeIndex - 1); }
        if (event.key === 'Enter' && activeIndex >= 0 && items[activeIndex]) {
            event.preventDefault();
            window.location.href = items[activeIndex].url;
        }
        if (event.key === 'Escape') close();
    });

    trigger.addEventListener('click', open);
    overlay.addEventListener('click', (event) => { if (event.target === overlay) close(); });
    document.addEventListener('keydown', (event) => {
        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
            event.preventDefault();
            overlay.hidden ? open() : close();
        } else if (event.key === 'Escape' && !overlay.hidden) close();
    });
})();
