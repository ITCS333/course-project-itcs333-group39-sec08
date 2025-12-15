const titleEl = document.getElementById('week-title');
const dateEl = document.getElementById('week-start-date');
const descEl = document.getElementById('week-description');
const linksEl = document.getElementById('week-links-list');

const commentList = document.getElementById('comment-list');
const form = document.getElementById('comment-form');
const textarea = document.getElementById('new-comment-text');

const params = new URLSearchParams(window.location.search);
const weekId = params.get('id');

// ---------------- LOAD WEEK ----------------
async function loadWeek() {
  const res = await fetch(`./api/api.php?resource=weeks&id=${weekId}`);
  const json = await res.json();

  if (!json.success) {
    titleEl.textContent = 'Week not found';
    return;
  }

  const w = json.data;
  titleEl.textContent = w.title;
  dateEl.textContent = `Starts on: ${w.start_date}`;
  descEl.textContent = w.description || '';

  linksEl.innerHTML = '';
  (w.links || []).forEach(link => {
    const li = document.createElement('li');
    li.innerHTML = `<a href="${link}" target="_blank">${link}</a>`;
    linksEl.appendChild(li);
  });
}

// ---------------- LOAD COMMENTS ----------------
async function loadComments() {
  const res = await fetch(`./api/api.php?resource=comments&id=${weekId}`);
  const json = await res.json();

  commentList.innerHTML = '';

  json.data.forEach(c => {
    const art = document.createElement('article');
    art.innerHTML = `
      <p>${c.text}</p>
      <footer>Posted by: ${c.author}</footer>
    `;
    commentList.appendChild(art);
  });
}

// ---------------- ADD COMMENT ----------------
form.addEventListener('submit', async e => {
  e.preventDefault();

  const text = textarea.value.trim();
  if (!text) return;

  await fetch('./api/api.php?resource=comments', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      week_id: weekId,
      author: 'Student',
      text
    })
  });

  textarea.value = '';
  loadComments();
});

// INIT
loadWeek();
loadComments();
