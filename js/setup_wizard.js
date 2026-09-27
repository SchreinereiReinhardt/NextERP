document.addEventListener('DOMContentLoaded', () => {
  const nav = document.querySelector('.erp-wizard-steps');
  if (!nav) return;
  const links = [...nav.querySelectorAll('a[href^="#"]')];
  const sections = links.map(link => document.querySelector(link.getAttribute('href'))).filter(Boolean);
  if (!sections.length) return;

  const showStep = (index, updateHash = true) => {
    index = Math.max(0, Math.min(index, sections.length - 1));
    sections.forEach((section, i) => {
      const active = i === index;
      section.classList.toggle('is-active', active);
      section.hidden = !active;
    });
    links.forEach((link, i) => link.classList.toggle('active', i === index));
    if (updateHash && history.replaceState) history.replaceState(null, '', links[index].getAttribute('href'));
    const main = document.querySelector('.erp-setup-wizard');
    if (main) main.scrollIntoView({block: 'start'});
  };

  links.forEach((link, index) => link.addEventListener('click', e => {
    e.preventDefault();
    showStep(index);
  }));
  document.addEventListener('click', e => {
    const button = e.target.closest('.erp-wizard-prev,.erp-wizard-next');
    if (!button) return;
    e.preventDefault();
    showStep(parseInt(button.dataset.step || '0', 10));
  });
  let initial = links.findIndex(link => link.getAttribute('href') === location.hash);
  showStep(initial >= 0 ? initial : 0, false);
});
