/*
  admin.js - Manage Weekly Breakdown (client-side)
  - Add / Edit / Delete weeks in-memory
  - Validation and a modal confirmation for deletes
*/

let weeks = [];
const __DEBUG = false;

const weekForm = document.querySelector('#week-form');
const weeksTableBody = document.querySelector('#weeks-tbody');

function createWeekRow(week) {
  const tr = document.createElement('tr');
  if (week && week.id) tr.setAttribute('data-id', week.id);
  const tdTitle = document.createElement('td');
  tdTitle.textContent = week.title || '';
  tr.appendChild(tdTitle);

  const tdDesc = document.createElement('td');
  tdDesc.textContent = week.description || '';
  tr.appendChild(tdDesc);

  const tdActions = document.createElement('td');
  const group = document.createElement('div');
  group.className = 'button-group';

  const editBtn = document.createElement('button');
  editBtn.type = 'button';
  editBtn.className = 'edit edit-btn';
  editBtn.setAttribute('data-id', week.id || '');
  editBtn.textContent = 'Edit';

  const deleteBtn = document.createElement('button');
  deleteBtn.type = 'button';
  deleteBtn.className = 'delete delete-btn secondary';
  deleteBtn.setAttribute('data-id', week.id || '');
  deleteBtn.textContent = 'Delete';

  group.appendChild(editBtn);
  group.appendChild(deleteBtn);
  tdActions.appendChild(group);
  tr.appendChild(tdActions);
  return tr;
}

function renderTable() {
  if (!weeksTableBody) return;
  weeksTableBody.innerHTML = '';
  weeks.forEach(w => weeksTableBody.appendChild(createWeekRow(w)));
}

function handleAddWeek(event) {
  event.preventDefault();
  if (!weekForm) return;
  const titleInput = weekForm.querySelector('#week-title');
  const startDateInput = weekForm.querySelector('#week-start-date');
  const descInput = weekForm.querySelector('#week-description');
  const linksInput = weekForm.querySelector('#week-links');

  const title = titleInput ? titleInput.value.trim() : '';
  const startDate = startDateInput ? startDateInput.value : '';
  const description = descInput ? descInput.value.trim() : '';
  const linksRaw = linksInput ? linksInput.value.trim() : '';
  const links = linksRaw ? linksRaw.split(/\r?\n/).map(s => s.trim()).filter(Boolean) : [];

  const newWeek = { id: `week_${Date.now()}`, title, startDate, description, links };

  // editing
  if (typeof window !== 'undefined' && window.__editingWeekId) {
    const editId = window.__editingWeekId;
    const idx = weeks.findIndex(w => String(w.id) === String(editId));
    if (idx >= 0) {
      weeks[idx] = Object.assign({}, weeks[idx], { title, startDate, description, links });
      window.__editingWeekId = null;
      const addBtn = document.getElementById('add-week'); if (addBtn) addBtn.textContent = 'Add Week';
      // remove editing highlight if present
      try { if (window.__editingRowElement && window.__editingRowElement.classList) window.__editingRowElement.classList.remove('editing'); window.__editingRowElement = null; } catch (err) {}
      renderTable(); weekForm.reset(); return;
    }
  }

  weeks.push(newWeek);
  renderTable();
  weekForm.reset();
}

function showConfirmModal(message) {
  return new Promise((resolve) => {
    const modal = document.getElementById('confirm-modal');
    const msgEl = document.getElementById('confirm-message');
    const confirmBtn = document.getElementById('confirm-delete');
    const cancelBtn = document.getElementById('cancel-delete');
    if (!modal || !msgEl || !confirmBtn || !cancelBtn) {
      resolve(Boolean(window.confirm(message)));
      return;
    }
    msgEl.textContent = message;
    modal.style.display = 'block';
    modal.setAttribute('aria-hidden', 'false');

    const cleanup = () => {
      modal.style.display = 'none';
      modal.setAttribute('aria-hidden', 'true');
      confirmBtn.removeEventListener('click', onConfirm);
      cancelBtn.removeEventListener('click', onCancel);
      modal.removeEventListener('click', onBackdrop);
    };

    const onConfirm = () => { cleanup(); resolve(true); };
    const onCancel = () => { cleanup(); resolve(false); };
    const onBackdrop = (e) => { if (e.target === modal || e.target.classList.contains('confirm-backdrop')) onCancel(); };

    confirmBtn.addEventListener('click', onConfirm);
    cancelBtn.addEventListener('click', onCancel);
    modal.addEventListener('click', onBackdrop);
    confirmBtn.focus();
  });
}

async function handleTableClick(event) {
  const btn = event.target.closest && event.target.closest('button');
  const target = btn || event.target;
  if (!target) return;
  if (__DEBUG) console.debug('[admin] click', target, 'classes=', target.className);

  // Delete
  if (target.classList.contains('delete-btn') || target.classList.contains('delete')) {
    const id = target.getAttribute('data-id');
    let titleToDelete = null;
    if (id) {
      const wk = weeks.find(w => String(w.id) === String(id)); if (wk) titleToDelete = wk.title || id;
    }
    if (!titleToDelete) {
      const tr = target.closest && target.closest('tr'); if (tr) { const firstTd = tr.querySelector('td'); if (firstTd) titleToDelete = firstTd.textContent.trim(); }
    }
    const promptText = titleToDelete ? `Are you sure you want to delete the week "${titleToDelete}"?` : 'Are you sure you want to delete this week?';
    const ok = await showConfirmModal(promptText);
    if (!ok) return;
    if (id) { weeks = weeks.filter(w => String(w.id) !== String(id)); renderTable(); return; }
    const tr = target.closest && target.closest('tr'); if (tr) tr.remove(); return;
  }

  // Edit
  if (target.classList.contains('edit-btn') || target.classList.contains('edit')) {
    const id = target.getAttribute('data-id');
    if (!id || !weekForm) return;
    const wk = weeks.find(w => String(w.id) === String(id));
    if (!wk) return;
    const titleInput = weekForm.querySelector('#week-title');
    const startDateInput = weekForm.querySelector('#week-start-date');
    const descInput = weekForm.querySelector('#week-description');
    const linksInput = weekForm.querySelector('#week-links');
    if (titleInput) titleInput.value = wk.title || '';
    if (startDateInput) startDateInput.value = wk.startDate || '';
    if (descInput) descInput.value = wk.description || '';
    if (linksInput) linksInput.value = Array.isArray(wk.links) ? wk.links.join('\n') : '';
    if (typeof window !== 'undefined') window.__editingWeekId = id;
    const addBtn = document.getElementById('add-week');
    if (addBtn) addBtn.textContent = 'Update';

    // Highlight the row being edited
    try {
      if (window.__editingRowElement && window.__editingRowElement.classList) {
        window.__editingRowElement.classList.remove('editing');
      }
      const tr = target.closest && target.closest('tr');
      if (tr) {
        tr.classList.add('editing');
        window.__editingRowElement = tr;
      }
    } catch (err) { /* ignore */ }

    // Smoothly scroll the form into view but keep the edited row visible by offsetting for the header height
    (function scrollToFormWithOffset(){
      const form = document.getElementById('form-section');
      if (!form) return;
      const header = document.querySelector('header');
      const headerHeight = header ? header.offsetHeight + 12 : 12; // small gap
      const top = form.getBoundingClientRect().top + window.pageYOffset - headerHeight;
      window.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });
    })();

    return;
  }
}

async function loadAndInitialize() {
  try {
    const res = await fetch('api/weeks.json');
    if (!res.ok) { weeks = []; }
    else { const data = await res.json(); weeks = Array.isArray(data) ? data : []; }
  } catch (err) { console.warn('Failed to load weeks.json', err); weeks = []; }
  renderTable();
  if (weekForm) weekForm.addEventListener('submit', handleAddWeek);
  if (weeksTableBody) weeksTableBody.addEventListener('click', handleTableClick);
  document.addEventListener('click', function (e) {
    const b = e.target.closest && e.target.closest('button'); if (!b) return; const cls = b.className || '';
    if (cls.includes('delete') || cls.includes('edit')) { try { handleTableClick(e); } catch (err) { console.error(err); } }
  });
}

loadAndInitialize();
