
/*
  Requirement: Populate the single topic page and manage replies.

  Instructions:
  1. Link this file to `topic.html` using:
     <script src="topic.js" defer></script>

  2. In `topic.html`, add the following IDs:
     - To the <h1>: `id="topic-subject"`
     - To the <article id="original-post">:
       - Add a <p> with `id="op-message"` for the message text.
       - Add a <footer> with `id="op-footer"` for the metadata.
     - To the <div> for the list of replies: `id="reply-list-container"`
     - To the "Post a Reply" <form>: `id="reply-form"`

  3. Implement the TODOs below.
*/

// --- Global Data Store ---
let currentTopicId = null;
let currentReplies = []; // Will hold replies for *this* topic

// --- Element Selections ---
// TODO: Select all the elements you added IDs for in step 2.
const topicSubject = document.getElementById('topic-subject');
const opMessage = document.getElementById('op-message');
const opFooter = document.getElementById('op-footer');
const replyListContainer = document.getElementById('reply-list-container');
const replyForm = document.getElementById('reply-form');
const newReplyText = document.getElementById('new-reply');

// --- Functions ---

/**
 * TODO: Implement the getTopicIdFromURL function.
 * It should:
 * 1. Get the query string from `window.location.search`.
 * 2. Use the `URLSearchParams` object to get the value of the 'id' parameter.
 * 3. Return the id.
 */
function getTopicIdFromURL() {
  const params = new URLSearchParams(window.location.search);
  return params.get('id');
}

/**
 * TODO: Implement the renderOriginalPost function.
 * It takes one topic object.
 * It should:
 * 1. Set the `textContent` of `topicSubject` to the topic's subject.
 * 2. Set the `textContent` of `opMessage` to the topic's message.
 * 3. Set the `textContent` of `opFooter` to "Posted by: {author} on {date}".
 * 4. (Optional) Add a "Delete" button with `data-id="${topic.id}"` to the OP.
 */
function renderOriginalPost(topic) {
  topicSubject.textContent = topic.subject;
  opMessage.textContent = topic.message;
  opFooter.textContent = `Posted by: ${topic.author} on ${topic.date}`;
}

/**
 * TODO: Implement the createReplyArticle function.
 * It takes one reply object {id, author, date, text}.
 * It should return an <article> element matching the structure in `topic.html`.
 * - Include a <p> for the `text`.
 * - Include a <footer> for the `author` and `date`.
 * - Include a "Delete" button with class "delete-reply-btn" and `data-id="${id}"`.
 */
function createReplyArticle(reply) {
  const article = document.createElement('article');
  article.className = 'reply';
  
  const p = document.createElement('p');
  p.textContent = reply.text;
  
  const footer = document.createElement('footer');
  const footerP = document.createElement('p');
  footerP.textContent = `Replied by: ${reply.author} on ${reply.date}`;
  footer.appendChild(footerP);
  
  const div = document.createElement('div');
  
  const editBtn = document.createElement('button');
  editBtn.textContent = 'Edit';
  editBtn.className = 'edit-reply-btn';
  editBtn.setAttribute('data-id', reply.id);
  
  const deleteBtn = document.createElement('button');
  deleteBtn.textContent = 'Delete';
  deleteBtn.className = 'secondary delete-reply-btn';
  deleteBtn.setAttribute('data-id', reply.id);
  
  div.appendChild(editBtn);
  div.appendChild(deleteBtn);
  
  article.appendChild(p);
  article.appendChild(footer);
  article.appendChild(div);
  
  return article;
}

/**
 * TODO: Implement the renderReplies function.
 * It should:
 * 1. Clear the `replyListContainer`.
 * 2. Loop through the global `currentReplies` array.
 * 3. For each reply, call `createReplyArticle()`, and
 * append the resulting <article> to `replyListContainer`.
 */
function renderReplies() {
  replyListContainer.innerHTML = '';
  currentReplies.forEach(reply => {
    const replyArticle = createReplyArticle(reply);
    replyListContainer.appendChild(replyArticle);
  });
}

/**
 * TODO: Implement the handleAddReply function.
 * This is the event handler for the `replyForm` 'submit' event.
 * It should:
 * 1. Prevent the form's default submission.
 * 2. Get the text from `newReplyText.value`.
 * 3. If the text is empty, return.
 * 4. Create a new reply object:
 * {
 * id: `reply_${Date.now()}`,
 * author: 'Student' (hardcoded),
 * date: new Date().toISOString().split('T')[0],
 * text: (reply text value)
 * }
 * 5. Add this new reply to the global `currentReplies` array (in-memory only).
 * 6. Call `renderReplies()` to refresh the list.
 * 7. Clear the `newReplyText` textarea.
 */
function handleAddReply(event) {
  event.preventDefault();
  
  const text = newReplyText.value.trim();
  if (!text) {
    return;
  }
  
  const newReply = {
    id: `reply_${Date.now()}`,
    author: 'Student',
    date: new Date().toISOString().split('T')[0],
    text: text
  };
  
  currentReplies.push(newReply);
  renderReplies();
  newReplyText.value = '';
}

/**
 * TODO: Implement the handleReplyListClick function.
 * This is an event listener on the `replyListContainer` (for delegation).
 * It should:
 * 1. Check if the clicked element (`event.target`) has the class "delete-reply-btn".
 * 2. If it does, get the `data-id` attribute from the button.
 * 3. Update the global `currentReplies` array by filtering out the reply
 * with the matching ID (in-memory only).
 * 4. Call `renderReplies()` to refresh the list.
 */
function handleReplyListClick(event) {
  console.log('Reply list clicked!', event.target);
  const target = event.target;
  
  // Check if clicked element is a button
  if (target.tagName !== 'BUTTON') {
    console.log('Not a button, ignoring');
    return;
  }
  
  const replyId = target.getAttribute('data-id');
  console.log('Reply ID:', replyId);
  if (!replyId) {
    console.log('No data-id found');
    return;
  }
  
  // Handle Delete button
  if (target.classList.contains('delete-reply-btn')) {
    if (confirm('Are you sure you want to delete this reply?')) {
      currentReplies = currentReplies.filter(reply => reply.id !== replyId);
      renderReplies();
    }
    return;
  }
  
  // Handle Edit button  
  if (target.classList.contains('edit-reply-btn')) {
    const reply = currentReplies.find(r => r.id === replyId);
    const article = target.closest('article');
    
    if (!reply || !article) {
      return;
    }
    
    const p = article.querySelector('p');
    if (!p) {
      return;
    }
    
    // Create textarea for editing
    const textarea = document.createElement('textarea');
    textarea.value = reply.text;
    textarea.className = 'edit-reply-textarea';
    textarea.style.cssText = 'width: 100%; min-height: 80px; padding: 0.75rem; font-size: 0.95rem; border: 2px solid #0066cc; border-radius: 6px; font-family: inherit; resize: vertical;';
    
    // Replace paragraph with textarea
    p.replaceWith(textarea);
    
    // Change Edit button to Update
    target.textContent = 'Update';
    target.classList.remove('edit-reply-btn');
    target.classList.add('update-reply-btn');
    
    textarea.focus();
    textarea.select();
    return;
  }
  
  // Handle Update button
  if (target.classList.contains('update-reply-btn')) {
    const reply = currentReplies.find(r => r.id === replyId);
    const article = target.closest('article');
    
    if (!reply || !article) {
      return;
    }
    
    const textarea = article.querySelector('.edit-reply-textarea');
    if (!textarea) {
      return;
    }
    
    const newText = textarea.value.trim();
    
    if (newText) {
      reply.text = newText;
      renderReplies();
    } else {
      alert('Reply text cannot be empty!');
    }
    return;
  }
}

/**
 * TODO: Implement an `initializePage` function.
 * This function needs to be 'async'.
 * It should:
 * 1. Get the `currentTopicId` by calling `getTopicIdFromURL()`.
 * 2. If no ID is found, set `topicSubject.textContent = "Topic not found."` and stop.
 * 3. `fetch` both 'topics.json' and 'replies.json' (you can use `Promise.all`).
 * 4. Parse both JSON responses.
 * 5. Find the correct topic from the topics array using the `currentTopicId`.
 * 6. Get the correct replies array from the replies object using the `currentTopicId`.
 * Store this in the global `currentReplies` variable. (If no replies exist, use an empty array).
 * 7. If the topic is found:
 * - Call `renderOriginalPost()` with the topic object.
 * - Call `renderReplies()` to show the initial replies.
 * - Add the 'submit' event listener to `replyForm` (calls `handleAddReply`).
 * - Add the 'click' event listener to `replyListContainer` (calls `handleReplyListClick`).
 * 8. If the topic is not found, display an error in `topicSubject`.
 */
async function initializePage() {
  console.log('Initializing page...');
  console.log('Elements:', { topicSubject, opMessage, opFooter, replyListContainer, replyForm, newReplyText });
  
  currentTopicId = getTopicIdFromURL();
  console.log('Topic ID from URL:', currentTopicId);
  
  if (!currentTopicId) {
    topicSubject.textContent = "Topic not found.";
    return;
  }
  
  try {
    const [topicsResponse, repliesResponse] = await Promise.all([
      fetch('api/topics.json'),
      fetch('api/comments.json')
    ]);
    
    const topics = await topicsResponse.json();
    const repliesData = await repliesResponse.json();
    
    console.log('Topics loaded:', topics);
    console.log('Replies data:', repliesData);
    
    const topic = topics.find(t => t.id === currentTopicId);
    console.log('Found topic:', topic);
    
    if (topic) {
      currentReplies = repliesData[currentTopicId] || [];
      console.log('Current replies:', currentReplies);
      
      renderOriginalPost(topic);
      renderReplies();
      
      console.log('Adding event listeners...');
      
      // Add submit listener for reply form
      if (replyForm) {
        replyForm.addEventListener('submit', handleAddReply);
        console.log('Submit listener added');
      }
      
      // Add click listener to entire body to catch all button clicks
      document.body.addEventListener('click', handleAllButtonClicks);
      console.log('Click listener added to body');
      
    } else {
      topicSubject.textContent = "Topic not found.";
    }
  } catch (error) {
    console.error('Error loading topic:', error);
    topicSubject.textContent = "Error loading topic.";
  }
}

// Handle all button clicks on the page
function handleAllButtonClicks(event) {
  const target = event.target;
  
  console.log('Clicked:', target);
  
  // Check if clicked element is a button
  if (target.tagName !== 'BUTTON') {
    return;
  }
  
  console.log('Button clicked!', target.textContent, target.className);
  
  // Get the closest article and its data
  const article = target.closest('article');
  if (!article) {
    return;
  }
  
  const isOriginalPost = article.id === 'original-post';
  const replyId = target.getAttribute('data-id');
  
  // Handle Delete button
  if (target.classList.contains('delete-btn') || target.classList.contains('secondary') || target.textContent.trim() === 'Delete') {
    if (isOriginalPost) {
      if (confirm('Are you sure you want to delete this topic?')) {
        alert('Topic deleted! (In a real app, this would redirect back to the board)');
        // window.location.href = 'baord.html';
      }
    } else if (replyId) {
      if (confirm('Are you sure you want to delete this reply?')) {
        currentReplies = currentReplies.filter(reply => reply.id !== replyId);
        renderReplies();
      }
    }
    return;
  }
  
  // Handle Edit button
  if (target.classList.contains('edit-btn') || target.classList.contains('edit-reply-btn') || target.textContent.trim() === 'Edit') {
    if (isOriginalPost) {
      // Edit original post
      const p = article.querySelector('#op-message');
      if (!p) return;
      
      const textarea = document.createElement('textarea');
      textarea.value = p.textContent;
      textarea.className = 'edit-op-textarea';
      textarea.style.cssText = 'width: 100%; min-height: 100px; padding: 0.75rem; font-size: 0.95rem; border: 2px solid #0066cc; border-radius: 6px; font-family: inherit; resize: vertical;';
      
      p.replaceWith(textarea);
      target.textContent = 'Update';
      target.classList.add('update-btn');
      textarea.focus();
      textarea.select();
    } else if (replyId) {
      // Edit reply
      const reply = currentReplies.find(r => r.id === replyId);
      if (!reply) return;
      
      const p = article.querySelector('p');
      if (!p) return;
      
      const textarea = document.createElement('textarea');
      textarea.value = reply.text;
      textarea.className = 'edit-reply-textarea';
      textarea.style.cssText = 'width: 100%; min-height: 80px; padding: 0.75rem; font-size: 0.95rem; border: 2px solid #0066cc; border-radius: 6px; font-family: inherit; resize: vertical;';
      
      p.replaceWith(textarea);
      target.textContent = 'Update';
      target.classList.remove('edit-btn', 'edit-reply-btn');
      target.classList.add('update-reply-btn');
      target.setAttribute('data-id', replyId);
      textarea.focus();
      textarea.select();
    }
    return;
  }
  
  // Handle Update button
  if (target.classList.contains('update-btn') || target.classList.contains('update-reply-btn') || target.textContent.trim() === 'Update') {
    if (isOriginalPost) {
      // Update original post
      const textarea = article.querySelector('.edit-op-textarea');
      if (!textarea) return;
      
      const newText = textarea.value.trim();
      if (newText) {
        const p = document.createElement('p');
        p.id = 'op-message';
        p.textContent = newText;
        textarea.replaceWith(p);
        target.textContent = 'Edit';
        target.classList.remove('update-btn');
      } else {
        alert('Message cannot be empty!');
      }
    } else if (replyId) {
      // Update reply
      const reply = currentReplies.find(r => r.id === replyId);
      if (!reply) return;
      
      const textarea = article.querySelector('.edit-reply-textarea');
      if (!textarea) return;
      
      const newText = textarea.value.trim();
      if (newText) {
        reply.text = newText;
        renderReplies();
      } else {
        alert('Reply text cannot be empty!');
      }
    }
    return;
  }
}

// --- Initial Page Load ---
initializePage();
