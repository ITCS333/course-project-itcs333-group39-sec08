
/*
  Requirement: Make the "Discussion Board" page interactive.

  Instructions:
  1. Link this file to `board.html` (or `baord.html`) using:
     <script src="board.js" defer></script>
  
  2. In `board.html`, add an `id="topic-list-container"` to the 'div'
     that holds the list of topic articles.
  
  3. Implement the TODOs below.
*/

// --- Global Data Store ---
// This will hold the topics loaded from the JSON file.
let topics = [];

// --- Element Selections ---
// TODO: Select the new topic form ('#new-topic-form').
const newTopicForm = document.getElementById('new-topic-form');
const topicSubject = document.getElementById('topic-subject');
const topicMessage = document.getElementById('topic-message');

// TODO: Select the topic list container ('#topic-list-container').
const topicListContainer = document.getElementById('topic-list-container');

// --- Functions ---

/**
 * TODO: Implement the createTopicArticle function.
 * It takes one topic object {id, subject, author, date}.
 * It should return an <article> element matching the structure in `board.html`.
 * - The main link's `href` MUST be `topic.html?id=${id}`.
 * - The footer should contain the author and date.
 * - The actions div should contain an "Edit" button and a "Delete" button.
 * - The "Delete" button should have a class "delete-btn" and `data-id="${id}"`.
 */
function createTopicArticle(topic) {
  const article = document.createElement('article');
  article.setAttribute('data-id', topic.id);
  
  const h3 = document.createElement('h3');
  const link = document.createElement('a');
  link.href = `topic.html?id=${topic.id}`;
  link.textContent = topic.subject;
  h3.appendChild(link);
  
  const p = document.createElement('p');
  p.textContent = topic.message;
  p.style.cssText = 'color: #666; margin: 0.5rem 0 1rem 0;';
  
  const footer = document.createElement('footer');
  footer.textContent = `Posted by: ${topic.author} on ${topic.date}`;
  
  const div = document.createElement('div');
  
  const editBtn = document.createElement('button');
  editBtn.textContent = 'Edit';
  editBtn.setAttribute('data-id', topic.id);
  editBtn.classList.add('edit-btn');
  
  const deleteBtn = document.createElement('button');
  deleteBtn.textContent = 'Delete';
  deleteBtn.setAttribute('data-id', topic.id);
  deleteBtn.classList.add('delete-btn', 'secondary');
  
  div.appendChild(editBtn);
  div.appendChild(deleteBtn);
  
  article.appendChild(h3);
  article.appendChild(p);
  article.appendChild(footer);
  article.appendChild(div);
  
  return article;
}

/**
 * TODO: Implement the renderTopics function.
 * It should:
 * 1. Clear the `topicListContainer`.
 * 2. Loop through the global `topics` array.
 * 3. For each topic, call `createTopicArticle()`, and
 * append the resulting <article> to `topicListContainer`.
 */
function renderTopics() {
  topicListContainer.innerHTML = '';
  topics.forEach(topic => {
    const article = createTopicArticle(topic);
    topicListContainer.appendChild(article);
  });
}

/**
 * TODO: Implement the handleCreateTopic function.
 * This is the event handler for the form's 'submit' event.
 * It should:
 * 1. Prevent the form's default submission.
 * 2. Get the values from the '#topic-subject' and '#topic-message' inputs.
 * 3. Create a new topic object with the structure:
 * {
 * id: `topic_${Date.now()}`,
 * subject: (subject value),
 * message: (message value),
 * author: 'Student' (use a hardcoded author for this exercise),
 * date: new Date().toISOString().split('T')[0] // Gets today's date YYYY-MM-DD
 * }
 * 4. Add this new topic object to the global `topics` array (in-memory only).
 * 5. Call `renderTopics()` to refresh the list.
 * 6. Reset the form.
 */
function handleCreateTopic(event) {
  event.preventDefault();
  
  const subject = topicSubject.value.trim();
  const message = topicMessage.value.trim();
  
  if (!subject || !message) {
    return;
  }
  
  const newTopic = {
    id: `topic_${Date.now()}`,
    subject: subject,
    message: message,
    author: 'Student',
    date: new Date().toISOString().split('T')[0]
  };
  
  topics.push(newTopic);
  renderTopics();
  newTopicForm.reset();
}

/**
 * TODO: Implement the handleTopicListClick function.
 * This is an event listener on the `topicListContainer` (for delegation).
 * It should:
 * 1. Check if the clicked element (`event.target`) has the class "delete-btn".
 * 2. If it does, get the `data-id` attribute from the button.
 * 3. Update the global `topics` array by filtering out the topic
 * with the matching ID (in-memory only).
 * 4. Call `renderTopics()` to refresh the list.
 */
function handleTopicListClick(event) {
  const target = event.target;
  
  // Check if clicked element is a button
  if (target.tagName !== 'BUTTON') {
    return;
  }
  
  const topicId = target.getAttribute('data-id');
  if (!topicId) {
    console.log('No data-id found on button');
    return;
  }
  
  // Handle Delete button
  if (target.classList.contains('delete-btn')) {
    const topic = topics.find(t => t.id === topicId);
    
    if (topic && confirm(`Are you sure you want to delete the topic "${topic.subject}"?`)) {
      topics = topics.filter(t => t.id !== topicId);
      renderTopics();
    }
    return;
  }
  
  // Handle Edit button
  if (target.classList.contains('edit-btn')) {
    const topic = topics.find(t => t.id === topicId);
    const article = target.closest('article');
    
    if (!topic || !article) {
      console.log('Topic or article not found');
      return;
    }
    
    const h3 = article.querySelector('h3');
    const p = article.querySelector('p');
    if (!h3 || !p) {
      console.log('h3 or p not found');
      return;
    }
    
    // Create input for editing subject
    const input = document.createElement('input');
    input.type = 'text';
    input.value = topic.subject;
    input.className = 'edit-subject-input';
    input.style.cssText = 'width: 100%; font-size: 1.25rem; padding: 0.5rem; margin-bottom: 0.5rem; border: 2px solid #0066cc; border-radius: 6px; font-family: inherit;';
    
    // Create textarea for editing message
    const textarea = document.createElement('textarea');
    textarea.value = topic.message;
    textarea.className = 'edit-message-textarea';
    textarea.style.cssText = 'width: 100%; min-height: 80px; font-size: 0.95rem; padding: 0.75rem; margin-bottom: 0.5rem; border: 2px solid #0066cc; border-radius: 6px; font-family: inherit; resize: vertical;';
    
    // Replace h3 content with input
    h3.innerHTML = '';
    h3.appendChild(input);
    
    // Replace p with textarea
    p.replaceWith(textarea);
    
    // Change Edit button to Update
    target.textContent = 'Update';
    target.classList.remove('edit-btn');
    target.classList.add('update-btn');
    
    input.focus();
    input.select();
    return;
  }
  
  // Handle Update button
  if (target.classList.contains('update-btn')) {
    const topic = topics.find(t => t.id === topicId);
    const article = target.closest('article');
    
    if (!topic || !article) {
      return;
    }
    
    const input = article.querySelector('.edit-subject-input');
    const textarea = article.querySelector('.edit-message-textarea');
    
    if (!input || !textarea) {
      return;
    }
    
    const newSubject = input.value.trim();
    const newMessage = textarea.value.trim();
    
    if (newSubject && newMessage) {
      topic.subject = newSubject;
      topic.message = newMessage;
      renderTopics();
    } else {
      alert('Subject and message cannot be empty!');
    }
    return;
  }
}

/**
 * TODO: Implement the loadAndInitialize function.
 * This function needs to be 'async'.
 * It should:
 * 1. Use `fetch()` to get data from 'topics.json'.
 * 2. Parse the JSON response and store the result in the global `topics` array.
 * 3. Call `renderTopics()` to populate the list for the first time.
 * 4. Add the 'submit' event listener to `newTopicForm` (calls `handleCreateTopic`).
 * 5. Add the 'click' event listener to `topicListContainer` (calls `handleTopicListClick`).
 */
async function loadAndInitialize() {
  try {
    const response = await fetch('api/topics.json');
    topics = await response.json();
    
    renderTopics();
    
    newTopicForm.addEventListener('submit', handleCreateTopic);
    topicListContainer.addEventListener('click', handleTopicListClick);
  } catch (error) {
    console.error('Error loading topics:', error);
  }
}

// --- Initial Page Load ---
// Call the main async function to start the application.
loadAndInitialize();

