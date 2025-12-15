// ================= GLOBAL STATE =================
let topics = [];

// ================= ELEMENTS =================
const topicListContainer = document.getElementById('topic-list-container');
const newTopicForm = document.getElementById('new-topic-form');
const newSubject = document.getElementById('topic-subject');
const newMessage = document.getElementById('topic-message');

// ================= RENDER =================
function createTopicArticle(topic) {
  const article = document.createElement('article');

  const h3 = document.createElement('h3');
  const link = document.createElement('a');
  link.href = `topic.html?id=${topic.id}`;
  link.textContent = topic.subject;
  h3.appendChild(link);

  const footer = document.createElement('footer');
  footer.textContent = `Posted by: ${topic.author} on ${topic.date}`;

  const actions = document.createElement('div');

  const editBtn = document.createElement('button');
  editBtn.type = 'button';
  editBtn.textContent = 'Edit';
  editBtn.className = 'edit-btn';
  editBtn.dataset.id = topic.id;

  const deleteBtn = document.createElement('button');
  deleteBtn.type = 'button';
  deleteBtn.textContent = 'Delete';
  deleteBtn.className = 'delete-btn';
  deleteBtn.dataset.id = topic.id;

  actions.append(editBtn, deleteBtn);
  article.append(h3, footer, actions);

  return article;
}

function renderTopics() {
  topicListContainer.innerHTML = '';
  topics.forEach(t => {
    topicListContainer.appendChild(createTopicArticle(t));
  });
}

// ================= ADD TOPIC =================
async function handleCreateTopic(e) {
  e.preventDefault();

  const subject = newSubject.value.trim();
  const message = newMessage.value.trim();
  if (!subject || !message) return;

  let res;
  try {
    res = await fetch('/src/discussion/api/topics.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        subject,
        message,
        author: 'Student'
      })
    });
  } catch (err) {
    res = null;
  }

  if (!res || typeof res.json !== 'function') {
    alert('Cannot reach server. Topic not created.');
    return;
  }

  const result = await res.json();

  topics.unshift({
    id: result.id,
    subject,
    message,
    author: 'Student',
    date: new Date().toISOString().slice(0,10)
  });

  renderTopics();
  newTopicForm.reset();
}

// ================= EDIT / DELETE =================
async function handleTopicListClick(e) {
  const btn = e.target;
  if (btn.tagName !== 'BUTTON') return;

  const topicId = btn.dataset.id;
  if (!topicId) return;

  // DELETE
  if (btn.classList.contains('delete-btn')) {
    if (!confirm('Delete this topic?')) return;

    await fetch(`/src/discussion/api/topics.php?id=${topicId}`, {
      method: 'DELETE'
    });

    topics = topics.filter(t => t.id != topicId);
    renderTopics();
    return;
  }

  // EDIT
  if (btn.classList.contains('edit-btn')) {
    const article = btn.closest('article');
    const topic = topics.find(t => t.id == topicId);

    const subjectInput = document.createElement('input');
    subjectInput.value = topic.subject;

    const messageTextarea = document.createElement('textarea');
    messageTextarea.value = topic.message;

    article.innerHTML = '';
    article.append(subjectInput, messageTextarea);

    const updateBtn = document.createElement('button');
    updateBtn.textContent = 'Update';
    updateBtn.className = 'update-btn';
    updateBtn.dataset.id = topicId;

    article.appendChild(updateBtn);
  }

  // UPDATE
  if (btn.classList.contains('update-btn')) {
    const article = btn.closest('article');
    const inputs = article.querySelectorAll('input, textarea');

    const newSubjectVal = inputs[0].value.trim();
    const newMessageVal = inputs[1].value.trim();

    if (!newSubjectVal || !newMessageVal) return;

    await fetch('/src/discussion/api/topics.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        _method: 'PUT',
        id: topicId,
        subject: newSubjectVal,
        message: newMessageVal
      })
    });

    const topic = topics.find(t => t.id == topicId);
    topic.subject = newSubjectVal;
    topic.message = newMessageVal;

    renderTopics();
  }
}

// ================= INIT =================
async function loadAndInitialize() {
  let res;
  try {
    res = await fetch('/src/discussion/api/topics.php');
  } catch (e) {
    res = null;
  }

  if (!res || typeof res.json !== 'function') {
    topics = [];
  } else {
    topics = await res.json();
  }

  renderTopics();

  newTopicForm.addEventListener('submit', handleCreateTopic);
  topicListContainer.addEventListener('click', handleTopicListClick);
}

loadAndInitialize();
