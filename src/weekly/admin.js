let weeks = [];
let editingWeekId = null;

const form = document.getElementById('week-form');
const tbody = document.getElementById('weeks-tbody');
const addBtn = document.getElementById('add-week');

function must(el, name) {
  if (!el) throw new Error(`${name} is missing in HTML`);
  return el;
}

must(form, '#week-form');
must(tbody, '#weeks-tbody');
must(addBtn, '#add-week');

async function fetchJson(url, options = {}) {
  const res = await fetch(url, options);
  const text = await res.text();

  try {
    return { ok: res.ok, json: JSON.parse(text), raw: text };
  } catch {
    console.error('❌ Non-JSON response:', text);
    return { ok: false, json: { success: false, error: 'API returned non-JSON' }, raw: text };
  }
}

function renderTable() {
  tbody.innerHTML = '';

  weeks.forEach(w => {
    const tr = document.createElement('tr');
    tr.dataset.id = w.id; // ✅ REQUIRED for edit/delete

    tr.innerHTML = `
      <td>${w.title ?? ''}</td>
      <td>${w.description ?? ''}</td>
      <td>
        <div class="button-group">
          <button type="button" class="edit" data-id="${w.id}">Edit</button>
          <button type="button" class="delete" data-id="${w.id}">Delete</button>
        </div>
      </td>
    `;

    tbody.appendChild(tr);
  });
}

async function loadWeeks() {
  const r = await fetchJson('./api/api.php?resource=weeks');
  console.log('LOAD weeks:', r);

  if (!r.ok || !r.json.success) {
    alert(r.json.error || 'Failed to load weeks');
    return;
  }

  weeks = Array.isArray(r.json.data) ? r.json.data : [];
  renderTable();
}

form.addEventListener('submit', async (e) => {
  e.preventDefault();

  const titleEl = must(document.getElementById('week-title'), '#week-title');
  const dateEl = must(document.getElementById('week-start-date'), '#week-start-date');
  const descEl = must(document.getElementById('week-description'), '#week-description');

  const payload = {
    title: titleEl.value.trim(),
    start_date: dateEl.value,
    description: descEl.value.trim()
  };

  if (!payload.title || !payload.start_date) {
    alert('Title and start date are required');
    return;
  }

  // UPDATE
  if (editingWeekId) {
    payload.id = editingWeekId;
    console.log('PUT payload:', payload);

    const r = await fetchJson('./api/api.php?resource=weeks', {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });

    console.log('PUT response:', r);

    if (!r.ok || !r.json.success) {
      alert(r.json.error || 'Update failed');
      return;
    }

    editingWeekId = null;
    addBtn.textContent = 'Add Week';
    form.reset();
    loadWeeks();
    return;
  }

  // CREATE
  console.log('POST payload:', payload);

  const r = await fetchJson('./api/api.php?resource=weeks', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  });

  console.log('POST response:', r);

  if (!r.ok || !r.json.success) {
    alert(r.json.error || 'Create failed');
    return;
  }

  form.reset();
  loadWeeks();
});

tbody.addEventListener('click', async (e) => {
  const btn = e.target.closest('button.edit, button.delete');
  if (!btn) return;

  const id = btn.dataset.id || btn.closest('tr')?.dataset?.id;
  console.log('CLICK btn:', btn.className, 'id:', id);

  if (!id) {
    alert('Missing week id on row/button (data-id). Remove static rows and reload.');
    return;
  }

  // DELETE
  if (btn.classList.contains('delete')) {
    if (!confirm('Delete this week?')) return;

    const r = await fetchJson(`./api/api.php?resource=weeks&id=${id}`, { method: 'DELETE' });
    console.log('DELETE response:', r);

    if (!r.ok || !r.json.success) {
      alert(r.json.error || 'Delete failed');
      return;
    }

    loadWeeks();
    return;
  }

  // EDIT
  if (btn.classList.contains('edit')) {
    const w = weeks.find(x => String(x.id) === String(id));
    console.log('EDIT found week:', w);

    if (!w) {
      alert('Week not found in loaded data. Refresh page.');
      return;
    }

    document.getElementById('week-title').value = w.title || '';
    document.getElementById('week-start-date').value = w.start_date || '';
    document.getElementById('week-description').value = w.description || '';

    editingWeekId = w.id;
    addBtn.textContent = 'Update Week';
  }
});

loadWeeks();
