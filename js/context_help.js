(function () {
  'use strict';
  const topics = {
    'project-controlling': ['Nachkalkulation', 'Vergleicht Auftragswert, Sollwerte, Ist-Zeiten, Projektkosten und Abrechnung.', 'project-controlling'],
    'internal-costs': ['Interne Personalkosten', 'Ist-Arbeitszeit × interner Kostensatz. Der Wert ist nicht der Verkaufspreis der Arbeitszeit.', 'project-controlling'],
    'billing-preparation': ['Abrechnung vorbereiten', 'Sammelt noch nicht berücksichtigte Zeiten, Material und Rapporte für die Rechnung.', 'billing'],
    'invoice-35a': ['Arbeitskosten nach § 35a EStG', 'Netto-Arbeitskosten eingeben; Betrio berechnet den Bruttoanteil mit MwSt. Der Rechnungsbetrag wird nicht erhöht.', 'invoice-35a'],
    'smart-inbox': ['Dokumentenerkennung', 'Betrio schlägt Belegart, Zuordnungen und bei Eingangsrechnungen eine Kostenart vor. Vorschläge vor dem Speichern prüfen.', 'smart-inbox'],
    'supplier-cockpit': ['Lieferanten & Wareneingang', 'Verknüpft Bestellung, Auftragsbestätigung, Lieferschein und Eingangsrechnung mit dem Projekt.', 'supplier-cockpit'],
    'datev': ['DATEV-Export', 'Exportiert festgeschriebene Ausgangsbelege. Kontierung und Import vor Produktiveinsatz mit dem Steuerbüro prüfen.', 'datev'],
    'internal-rate': ['Interner Kostensatz', 'Interne Kosten je Arbeitsstunde für die Nachkalkulation. Er wird nicht auf Kundenbelegen ausgegeben.', 'project-controlling'],
    'project-close': ['Projektabschluss', 'Vor dem Abschluss prüft Betrio offene Rapporte, Leistungen, Lieferungen, Belege und Abrechnungspunkte.', 'project-close']
  };

  let openButton = null;
  let openPop = null;
  let closeTimer = null;
  let pinned = false;
  const hoverCapable = window.matchMedia && window.matchMedia('(hover: hover) and (pointer: fine)').matches;

  function cancelClose() {
    if (closeTimer) window.clearTimeout(closeTimer);
    closeTimer = null;
  }

  function closeHelp(button) {
    if (!button || button !== openButton) return;
    cancelClose();
    button.classList.remove('is-open');
    button.setAttribute('aria-expanded', 'false');
    if (openPop) {
      openPop.hidden = true;
      openPop.classList.remove('is-open', 'is-above', 'is-below');
    }
    openButton = null;
    openPop = null;
    pinned = false;
  }

  function closeAll() {
    if (openButton) closeHelp(openButton);
  }

  function positionPop(button, pop) {
    if (!button || !pop || pop.hidden) return;
    const margin = 10;
    const gap = 8;
    const r = button.getBoundingClientRect();
    const pr = pop.getBoundingClientRect();
    let left = r.left + (r.width / 2) - (pr.width / 2);
    left = Math.max(margin, Math.min(left, window.innerWidth - pr.width - margin));
    const spaceBelow = window.innerHeight - r.bottom;
    const spaceAbove = r.top;
    let top;
    pop.classList.remove('is-above', 'is-below');
    if (spaceBelow >= pr.height + gap + margin || spaceBelow >= spaceAbove) {
      top = r.bottom + gap;
      pop.classList.add('is-below');
    } else {
      top = r.top - pr.height - gap;
      pop.classList.add('is-above');
    }
    top = Math.max(margin, Math.min(top, window.innerHeight - pr.height - margin));
    pop.style.left = Math.round(left) + 'px';
    pop.style.top = Math.round(top) + 'px';
  }

  function openHelp(button, keepPinned) {
    cancelClose();
    if (openButton && openButton !== button) closeHelp(openButton);
    const popId = button.getAttribute('aria-controls');
    const pop = popId ? document.getElementById(popId) : null;
    if (!pop) return;
    openButton = button;
    openPop = pop;
    if (keepPinned === true) pinned = true;
    button.classList.add('is-open');
    button.setAttribute('aria-expanded', 'true');
    pop.hidden = false;
    pop.classList.add('is-open');
    window.requestAnimationFrame(() => positionPop(button, pop));
  }

  function scheduleClose(button) {
    cancelClose();
    if (pinned) return;
    closeTimer = window.setTimeout(() => {
      if (button === openButton) closeHelp(button);
    }, 180);
  }

  function init() {
    const base = (window.OC && OC.generateUrl) ? OC.generateUrl('/apps/reinhardterp/documentation') : '/index.php/apps/reinhardterp/documentation';
    document.querySelectorAll('[data-betrio-help]').forEach((el, index) => {
      if (el.dataset.helpReady) return;
      const cfg = topics[el.dataset.betrioHelp]; if (!cfg) return;
      el.dataset.helpReady = '1';
      const b = document.createElement('button');
      b.type = 'button';
      b.className = 'erp-context-help';
      b.setAttribute('aria-label', 'Hilfe: ' + cfg[0]);
      b.setAttribute('aria-expanded', 'false');
      b.textContent = '?';

      const pop = document.createElement('div');
      const popId = 'betrio-help-' + el.dataset.betrioHelp + '-' + index;
      pop.id = popId;
      pop.className = 'erp-context-help-pop';
      pop.hidden = true;
      pop.setAttribute('role', 'tooltip');
      b.setAttribute('aria-controls', popId);
      const strong = document.createElement('strong'); strong.textContent = cfg[0];
      const txt = document.createElement('span'); txt.textContent = cfg[1];
      const a = document.createElement('a'); a.href = base + '#' + cfg[2]; a.textContent = 'Mehr erfahren';
      pop.append(strong, txt, a);
      el.appendChild(b);
      document.body.appendChild(pop);

      b.addEventListener('click', event => {
        event.preventDefault();
        event.stopPropagation();
        if (openButton === b && pinned) closeHelp(b);
        else openHelp(b, true);
      });

      if (hoverCapable) {
        b.addEventListener('mouseenter', () => openHelp(b, false));
        b.addEventListener('mouseleave', () => scheduleClose(b));
        pop.addEventListener('mouseenter', () => cancelClose());
        pop.addEventListener('mouseleave', () => scheduleClose(b));
      }

      pop.addEventListener('click', event => event.stopPropagation());
      b.addEventListener('keydown', event => {
        if (event.key === 'Escape') { closeHelp(b); b.blur(); }
      });
    });
  }

  document.addEventListener('click', event => {
    if (!event.target.closest('.erp-context-help') && !event.target.closest('.erp-context-help-pop')) closeAll();
  });
  document.addEventListener('keydown', event => { if (event.key === 'Escape') closeAll(); });
  window.addEventListener('resize', () => { if (openButton && openPop) positionPop(openButton, openPop); });
  window.addEventListener('scroll', () => { if (openButton && openPop) positionPop(openButton, openPop); }, true);
  document.addEventListener('DOMContentLoaded', init);
  init();
})();
