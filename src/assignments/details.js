let currentAssignmentId = null;
let currentComments = [];

const assignmentTitle = document.querySelector("#assignment-title");
const assignmentDueDate = document.querySelector("#assignment-due-date");
const assignmentDescription = document.querySelector("#assignment-description");
const assignmentFilesList = document.querySelector("#assignment-files-list");
const commentList = document.querySelector("#comment-list");
const commentForm = document.querySelector("#comment-form");
const newCommentText = document.querySelector("#new-comment-text");

/**
 * Get assignment ID from URL
 */
function getAssignmentIdFromURL() {
  const queryString = window.location.search;
  const urlParams = new URLSearchParams(queryString);
  return urlParams.get("id");
}

/**
 * Render assignment details
 */
function renderAssignmentDetails(assignment) {
  assignmentTitle.textContent = assignment.title;
  assignmentDueDate.textContent = "Due: " + assignment.due_date;
  assignmentDescription.textContent = assignment.description;

  assignmentFilesList.innerHTML = "";

  const files = Array.isArray(assignment.files) ? assignment.files : [];
  files.forEach((file) => {
    const li = document.createElement("li");
    const a = document.createElement("a");
    a.href = "#";
    a.textContent = file;
    li.appendChild(a);
    assignmentFilesList.appendChild(li);
  });
}

/**
 * Create comment article
 */
function createCommentArticle(comment) {
  const article = document.createElement("article");

  const textP = document.createElement("p");
  textP.textContent = comment.text;

  const footer = document.createElement("footer");
  footer.textContent = "Posted by: " + comment.author;

  article.appendChild(textP);
  article.appendChild(footer);

  return article;
}

/**
 * Render comments
 */
function renderComments() {
  commentList.innerHTML = "";
  currentComments.forEach((comment) => {
    const article = createCommentArticle(comment);
    commentList.appendChild(article);
  });
}

/**
 * Add comment (DB)
 */
async function handleAddComment(event) {
  event.preventDefault();

  const commentText = newCommentText.value.trim();
  if (commentText === "") return;

  try {
    const response = await fetch("api/index.php?resource=comments", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        assignment_id: currentAssignmentId,
        author: "Student",
        text: commentText,
      }),
    });

    const result = await response.json();

    if (!result.error) {
      currentComments.unshift(result);
      renderComments();
      newCommentText.value = "";
    } else {
      console.error(result.error);
    }
  } catch (err) {
    console.error("Failed to add comment:", err);
  }
}

/**
 * Initialize page
 */
async function initializePage() {
  currentAssignmentId = getAssignmentIdFromURL();

  if (!currentAssignmentId) {
    assignmentTitle.textContent = "No assignment ID found in URL.";
    return;
  }

  try {
    // Fetch assignment
    const assignmentRes = await fetch(
      `api/index.php?resource=assignments&id=${currentAssignmentId}`
    );
    const assignment = await assignmentRes.json();

    if (!assignment || assignment.error) {
      assignmentTitle.textContent = assignment?.error || "Assignment not found.";
      return;
    }

    // Fetch comments
    const commentsRes = await fetch(
      `api/index.php?resource=comments&assignment_id=${currentAssignmentId}`
    );

    currentComments = commentsRes.ok ? await commentsRes.json() : [];

    // Render
    renderAssignmentDetails(assignment);
    renderComments();

    commentForm.addEventListener("submit", handleAddComment);
  } catch (error) {
    console.error(error);
    assignmentTitle.textContent =
      "Error: could not load assignment from server.";
  }
}

document.addEventListener("DOMContentLoaded", initializePage);
