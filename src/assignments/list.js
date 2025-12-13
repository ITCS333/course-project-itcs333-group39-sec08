
const listSection = document.querySelector("#assignment-list-section");

/**
 * TODO: Implement the createAssignmentArticle function.
 * It takes one assignment object {id, title, dueDate, description}.
 * It should return an <article> element matching the structure in `list.html`.
 * The "View Details" link's `href` MUST be set to `details.html?id=${id}`.
 * This is how the detail page will know which assignment to load.
 */
function createAssignmentArticle(assignment) {
  const { id, title, due_date, description } = assignment;
  const article = document.createElement("article");
  const h2 = document.createElement("h2");
    h2.textContent = title;

    const dueP = document.createElement("p");
    dueP.textContent = "Due: " + due_date;

    const descP = document.createElement("p");
    descP.textContent = description;

    const link = document.createElement("a");
    link.href = `details.html?id=${id}`;
    link.textContent = "View Details";

    article.appendChild(h2);
    article.appendChild(dueP);
    article.appendChild(descP);
    article.appendChild(link);

    return article;

}

/**
 * TODO: Implement the loadAssignments function.
 * This function needs to be 'async'.
 * It should:
 * 1. Use `fetch()` to get data from 'assignments.json'.
 * 2. Parse the JSON response into an array.
 * 3. Clear any existing content from `listSection`.
 * 4. Loop through the assignments array. For each assignment:
 * - Call `createAssignmentArticle()`.
 * - Append the returned <article> element to `listSection`.
 */
async function loadAssignments() {
  try {
    const response = await fetch("api/index.php?resource=assignments");
    const assignments = await response.json();

    listSection.innerHTML = "";

    assignments.forEach((assignment) => {
      const article = createAssignmentArticle(assignment);
      listSection.appendChild(article);
    });
  } catch (error) {
    console.error("Failed to load assignments:", error);
    listSection.innerHTML = `
      <p>Could not load assignments from the server. Please try again later.</p>
    `;
  }
}

loadAssignments();
