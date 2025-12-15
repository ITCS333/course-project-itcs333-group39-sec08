// ================= GLOBAL STATE =================
let currentTopicId = null;
let currentReplies = [];

// ================= ELEMENTS =================
const topicSubject = document.getElementById('topic-subject');
const opMessage = document.getElementById('op-message');
const opFooter = document.getElementById('op-footer');
const replyListContainer = document.getElementById('reply-list-container');
const replyForm = document.getElementById('reply-form');
const newReplyText = document.getElementById('new-reply');

// ================= HELPERS =================
function getTopicIdFromURL() {
  const params = new URLSearchParams(window.location.search);
  return params.get('id');
}

// SAFE JSON FETCH (🔥 IMPORTANT)
async function fetchJSON(url, options = {}) {
  const res = await fetch(url, options);
  const text = await res.text();

  try {
    return JSON.parse(text);
  } catch (err) {
    console.error('❌ API did not return JSON:', text);
    throw new Error('Server error (not JSON)');
  }
}

// ================= RENDER =================
function renderOriginalPost(topic) {
  topicSubject.textContent = topic.subject;
  opMessage.textContent = topic.message;
  opFooter.textContent = `Posted by: ${topic.author} on ${topic.date}`;
}

function createReplyArticle(reply) {
  const article = document.createElement('article');

  const p = document.createElement('p');
  p.textContent = reply.text;

  const footer = document.createElement('footer');
  footer.textContent = `Posted by: ${reply.author} on ${reply.date}`;

  const actions = document.createElement('div');

  const editBtn = document.createElement('button');
  editBtn.type = 'button';
  editBtn.textContent = 'Edit';
  editBtn.className = 'edit-reply-btn';
  editBtn.dataset.id = reply.id;

  const deleteBtn = document.createElement('button');
  deleteBtn.type = 'button';
  deleteBtn.textContent = 'Delete';
  deleteBtn.className = 'secondary delete-reply-btn';
  deleteBtn.dataset.id = reply.id;

  actions.append(editBtn, deleteBtn);
  article.append(p, footer, actions);

  return article;
}

function renderReplies() {
  replyListContainer.innerHTML = '';
  currentReplies.forEach(r => {
    replyListContainer.appendChild(createReplyArticle(r));
  });
}

// ================= ADD REPLY =================
async function handleAddReply(e) {
  e.preventDefault();

  const text = newReplyText.value.trim();
  if (!text) return;

  const result = await fetchJSON('/src/discussion/api/comments.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      topic_id: currentTopicId,
      text,
      author: 'Student'
    })
  });

  currentReplies.push({
    id: result.id,
    text,
    author: 'Student',
    date: new Date().toISOString().slice(0, 10)
  });

  renderReplies();
  newReplyText.value = '';
}

// ================= EDIT / DELETE =================
async function handleReplyListClick(e) {
  const btn = e.target;
  if (btn.tagName !== 'BUTTON') return;

  const replyId = btn.dataset.id;
  if (!replyId) return;

  // DELETE
  if (btn.classList.contains('delete-reply-btn')) {
    if (!confirm('Delete this reply?')) return;

    await fetchJSON(`/src/discussion/api/comments.php?id=${replyId}`, {
      method: 'DELETE'
    });

    currentReplies = currentReplies.filter(r => r.id != replyId);
    renderReplies();
    return;
  }

  // EDIT
  if (btn.classList.contains('edit-reply-btn')) {
    const article = btn.closest('article');
    const reply = currentReplies.find(r => r.id == replyId);

    const textarea = document.createElement('textarea');
    textarea.value = reply.text;

    article.querySelector('p').replaceWith(textarea);

    btn.textContent = 'Update';
    btn.className = 'update-reply-btn';
    return;
  }

  // UPDATE
  if (btn.classList.contains('update-reply-btn')) {
    const article = btn.closest('article');
    const textarea = article.querySelector('textarea');
    const newText = textarea.value.trim();

    if (!newText) return alert('Text required');

    await fetchJSON('/src/discussion/api/comments.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        _method: 'PUT',
        id: replyId,
        text: newText
      })
    });

    const reply = currentReplies.find(r => r.id == replyId);
    reply.text = newText;
    renderReplies();
  }
}

// ================= INIT =================
async function initializePage() {
  try {
    currentTopicId = getTopicIdFromURL();

    if (!currentTopicId) {
      topicSubject.textContent = 'Invalid topic';
      return;
    }

    const topic = await fetchJSON(
      `/src/discussion/api/topics.php?id=${currentTopicId}`
    );

    currentReplies = await fetchJSON(
      `/src/discussion/api/comments.php?topic_id=${currentTopicId}`
    );

    renderOriginalPost(topic);
    renderReplies();

    replyForm.addEventListener('submit', handleAddReply);
    replyListContainer.addEventListener('click', handleReplyListClick);

  } catch (err) {
    console.error(err);
    topicSubject.textContent = 'Error loading topic';
  }
}

initializePage();
