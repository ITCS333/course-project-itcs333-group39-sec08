/*
  Requirement: Populate the resource detail page and discussion forum.

  Instructions:
  1. Link this file to `details.html` using:
     <script src="details.js" defer></script>

  2. In `details.html`, add the following IDs:
     - To the <h1>: `id="resource-title"`
     - To the description <p>: `id="resource-description"`
     - To the "Access Resource Material" <a> tag: `id="resource-link"`
     - To the <div> for comments: `id="comment-list"`
     - To the "Leave a Comment" <form>: `id="comment-form"`
     - To the <textarea>: `id="new-comment"`
    

  3. Implement the TODOs below.
*/

// --- Global Data Store ---
// These will hold the data related to *this* resource.
let currentResourceId = null;
let currentComments = [];

// --- Element Selections ---
// TODO: Select all the elements you added IDs for in step 2.
let resourceTitle = document.querySelector('#resource-title');
let resourceDescription = document.querySelector('#resource-description');
let resourceLink = document.querySelector('#resource-link');
let commentList = document.querySelector('#comment-list');
let commentForm = document.querySelector('#comment-form');
let newComment = document.querySelector('#new-comment');



// --- Functions ---

/**
 * TODO: Implement the getResourceIdFromURL function.
 * It should:
 * 1. Get the query string from `window.location.search`.
 * 2. Use the `URLSearchParams` object to get the value of the 'id' parameter.
 * 3. Return the id.
 */
function getResourceIdFromURL() {
 // 1. Get the query string
    const queryString = window.location.search;
    const urlParams = new URLSearchParams(queryString);
    const id = urlParams.get('id');
    // 3. Return the id
    return id;}

/**
 * TODO: Implement the renderResourceDetails function.
 * It takes one resource object.
 * It should:
 * 1. Set the `textContent` of `resourceTitle` to the resource's title.
 * 2. Set the `textContent` of `resourceDescription` to the resource's description.
 * 3. Set the `href` attribute of `resourceLink` to the resource's link.
 */
function renderResourceDetails(resource) {
 // 1. Set the textContent of resourceTitle to the resource's title
    if (resourceTitle && resource.title) {
        resourceTitle.textContent = resource.title;
    }
    
    // 2. Set the textContent of resourceDescription to the resource's description
    if (resourceDescription && resource.description) {
        resourceDescription.textContent = resource.description;
    }
    
    // 3. Set the href attribute of resourceLink to the resource's link
    if (resourceLink && resource.link) {
        resourceLink.href = resource.link;}
    }

/**
 * TODO: Implement the createCommentArticle function.
 * It takes one comment object {author, text}.
 * It should return an <article> element matching the structure in `details.html`.
 * (e.g., an <article> containing a <p> and a <footer>).
 */
function createCommentArticle(comment) {
     // Create article element
    const article = document.createElement('article');
    article.className = 'comment'; // Add class for styling
    
    // Create paragraph (p) for the comment text
    const textParagraph = document.createElement('p');
    textParagraph.textContent = comment.text;
    
    // Create footer for the author
    const footer = document.createElement('footer');
    footer.textContent = `Posted by: ${comment.author}`;
    
    // Append elements to article
    article.appendChild(textParagraph);
    article.appendChild(footer);
    
    return article;
}


/**
 * TODO: Implement the renderComments function.
 * It should:
 * 1. Clear the `commentList`.
 * 2. Loop through the global `currentComments` array.
 * 3. For each comment, call `createCommentArticle()`, and
 * append the resulting <article> to `commentList`.
 */
function renderComments() {
    // 1. Clear the commentList
    if (commentList) {
        commentList.innerHTML = '';
    } else {
        console.error('commentList element not found');
        return;
    }
    
    // 2. Loop through the global currentComments array
    currentComments.forEach(comment => {
        // 3. For each comment, call createCommentArticle()
        const commentArticle = createCommentArticle(comment);
        
        // Append the resulting <article> to commentList
        commentList.appendChild(commentArticle);
    });}

/**
 * TODO: Implement the handleAddComment function.
 * This is the event handler for the `commentForm` 'submit' event.
 * It should:
 * 1. Prevent the form's default submission.
 * 2. Get the text from `newComment.value`.
 * 3. If the text is empty, return.
 * 4. Create a new comment object: { author: 'Student', text: commentText }
 * (For this exercise, 'Student' is a fine hardcoded author).
 * 5. Add the new comment to the global `currentComments` array (in-memory only).
 * 6. Call `renderComments()` to refresh the list.
 * 7. Clear the `newComment` textarea.
 */
function handleAddComment(event) {
    // 1. Prevent the form's default submission
    event.preventDefault();
    
    // 2. Get the text from newComment.value
    const commentText = newComment.value.trim();
    
    // 3. If the text is empty, return
    if (!commentText) {
        console.log('Comment text is empty, not submitting');
        return;
    }
    
    // 4. Create a new comment object
    const newCommentObj = {
        author: 'Student', // Hardcoded as 'Student'
        text: commentText,
        timestamp: new Date().toISOString() // Optional: add timestamp
    };
    
    // 5. Add the new comment to the global currentComments array
    currentComments.push(newCommentObj);
    
    // 6. Call renderComments() to refresh the list
    renderComments();
    
    // 7. Clear the newComment textarea
    newComment.value = '';}

/**
 * TODO: Implement an `initializePage` function.
 * This function needs to be 'async'.
 * It should:
 * 1. Get the `currentResourceId` by calling `getResourceIdFromURL()`.
 * 2. If no ID is found, set `resourceTitle.textContent = "Resource not found."` and stop.
 * 3. `fetch` both 'resources.json' and 'resource-comments.json' (you can use `Promise.all`).
 * 4. Parse both JSON responses.
 * 5. Find the correct resource from the resources array using the `currentResourceId`.
 * 6. Get the correct comments array from the comments object using the `currentResourceId`.
 * Store this in the global `currentComments` variable. (If no comments exist, use an empty array).
 * 7. If the resource is found:
 * - Call `renderResourceDetails()` with the resource object.
 * - Call `renderComments()` to show the initial comments.
 * - Add the 'submit' event listener to `commentForm` (calls `handleAddComment`).
 * 8. If the resource is not found, display an error in `resourceTitle`.
 */
async function initializePage() {
 try {
        // 1. Get the currentResourceId
        currentResourceId = getResourceIdFromURL();
        
        // 2. If no ID is found, display error and stop
        if (!currentResourceId) {
            if (resourceTitle) {
                resourceTitle.textContent = "Resource not found.";
                resourceTitle.style.color = "red";
            }
            console.error('No resource ID found in URL');
            return;
        }
        
        console.log(`Loading resource ID: ${currentResourceId}`);
        
        // 3. Fetch both JSON files using Promise.all
        const [resourcesResponse, commentsResponse] = await Promise.all([
             fetch('API/resources.json'),
             fetch('API/comments.json'),

        ]);
        
        // Check if responses are successful
        if (!resourcesResponse.ok) {
            throw new Error(`Failed to load resources: ${resourcesResponse.status}`);
        }
        
        // 4. Parse both JSON responses
        const resources = await resourcesResponse.json();
        const allComments = await commentsResponse.json();
        
        const resource = resources.find(r => String(r.id) === String(currentResourceId));
         currentComments = allComments[String(currentResourceId)] || [];
        
        // 7. If resource is found
        if (resource) {
            // Call renderResourceDetails()
            renderResourceDetails(resource);
            
            // Call renderComments()
            renderComments();
            
            // Add event listener to commentForm
            if (commentForm) {
                commentForm.addEventListener('submit', handleAddComment);
                console.log('Comment form event listener added');
            }
            
            console.log('Page initialized successfully');
            
        } else {
            // 8. If resource not found, display error
            if (resourceTitle) {
                resourceTitle.textContent = "Error: Resource not found.";
                resourceTitle.style.color = "red";
            }
            console.error(`Resource with ID ${currentResourceId} not found`);
        }
        
    } catch (error) {
        console.error('Error initializing page:', error);
        
        // Display error message
        if (resourceTitle) {
            resourceTitle.textContent = "Error loading resource. Please try again.";
            resourceTitle.style.color = "red";
        }
        
        // Initialize with empty comments
        currentComments = [];
        renderComments();
    }
}

// --- Initial Page Load ---
initializePage();
