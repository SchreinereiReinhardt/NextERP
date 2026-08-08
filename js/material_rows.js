(function () {
'use strict';
const catalogNode = document.getElementById('erp-material-catalog');
if (!catalogNode) return;
let catalog = [];
try { catalog = JSON.parse(catalogNode.textContent || '[]'); } catch (e) { catalog = []; }
const normalize = value => String(value || '').trim().toLocaleLowerCase('de-DE');
const display = item => [item.article_no, item.name].filter(Boolean).join(' · ');
const findMaterial = value => {
 const needle = normalize(value);
 return catalog.find(item => normalize(display(item)) === needle || normalize(item.article_no) === needle || normalize(item.name) === needle) || null;
};
function createRow(container) {
 const row = document.createElement('div');
 row.className = 'erp-material-entry-row';
 row.innerHTML = `
  <div class="erp-material-search-cell"><label>Material suchen</label><input class="erp-material-search" list="erp-material-options" placeholder="Artikelnummer oder Bezeichnung"><input type="hidden" name="materialIds[]" class="erp-material-id"></div>
  <div><label>Beschreibung</label><input name="materialDescriptions[]" class="erp-material-description" placeholder="Freie Position möglich"></div>
  <div><label>Menge</label><input type="number" name="materialQuantities[]" class="erp-material-quantity" step="0.001" min="0" value="0"></div>
  <div><label>Einheit</label><input name="materialUnits[]" class="erp-material-unit" placeholder="Stk."></div>
  <div><label>VK netto</label><input type="number" name="materialUnitPrices[]" class="erp-material-price" step="0.01" min="0" value="0"></div>
  <button type="button" class="button erp-material-remove" title="Materialzeile entfernen">×</button>`;
 container.appendChild(row);
 bindRow(row, container);
 return row;
}
function ensureBlankRow(container) {
 const rows = [...container.querySelectorAll('.erp-material-entry-row')];
 if (!rows.length) { createRow(container); return; }
 const last = rows[rows.length - 1];
 const hasValue = [...last.querySelectorAll('input')].some(input => {
  if (input.classList.contains('erp-material-quantity') || input.classList.contains('erp-material-price')) return Number(input.value) > 0;
  return String(input.value || '').trim() !== '';
 });
 if (hasValue) createRow(container);
}
function bindRow(row, container) {
 const search = row.querySelector('.erp-material-search');
 const id = row.querySelector('.erp-material-id');
 const description = row.querySelector('.erp-material-description');
 const unit = row.querySelector('.erp-material-unit');
 const price = row.querySelector('.erp-material-price');
 const quantity = row.querySelector('.erp-material-quantity');
 const apply = () => {
  const item = findMaterial(search.value);
  if (item) {
   id.value = item.id;
   search.value = display(item);
   if (!description.value.trim()) description.value = item.name || '';
   if (!unit.value.trim()) unit.value = item.unit || '';
   if (!(Number(price.value) > 0)) price.value = Number(item.sale_price || item.price || 0).toFixed(2);
   if (!(Number(quantity.value) > 0)) quantity.value = '1';
  } else id.value = '';
  ensureBlankRow(container);
 };
 search.addEventListener('change', apply);
 search.addEventListener('blur', apply);
 [search, description, unit, price, quantity].forEach(input => input.addEventListener('input', () => ensureBlankRow(container)));
 row.querySelector('.erp-material-remove').addEventListener('click', () => {
  const rows = container.querySelectorAll('.erp-material-entry-row');
  if (rows.length > 1) row.remove(); else row.querySelectorAll('input').forEach(input => input.value = (input.type === 'number' ? '0' : ''));
  ensureBlankRow(container);
 });
}
document.querySelectorAll('[data-material-rows]').forEach(container => {
 if (!container.querySelector('.erp-material-entry-row')) createRow(container);
 else [...container.querySelectorAll('.erp-material-entry-row')].forEach(row => bindRow(row, container));
 ensureBlankRow(container);
});
})();
