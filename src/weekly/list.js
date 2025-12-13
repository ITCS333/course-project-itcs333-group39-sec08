/*
  Requirement: Populate the "Weekly Course Breakdown" list page.

  Instructions:
  1. Link this file to `list.html` using:
     <script src="list.js" defer></script>

  2. In `list.html`, add an `id="week-list-section"` to the
     <section> element that will contain the weekly articles.

  3. Implement the TODOs below.
*/

// --- Element Selections ---
// TODO: Select the section for the week list ('#week-list-section').
const listSection = document.querySelector('#week-list-section');

// --- Functions ---

/**
 * TODO: Implement the createWeekArticle function.
 * It takes one week object {id, title, startDate, description}.
 * It should return an <article> element matching the structure in `list.html`.
 * - The "View Details & Discussion" link's `href` MUST be set to `details.html?id=${id}`.
 * (This is how the detail page will know which week to load).
 */
function createWeekArticle(week) {
  // Create article element matching list.html structure
  const article = document.createElement('article');

  // Title
  const h3 = document.createElement('h3');
  h3.textContent = week.title || '';
  article.appendChild(h3);

  // Start date (small paragraph)
  const pDate = document.createElement('p');
  pDate.className = 'muted';
  pDate.textContent = week.startDate || week.start_date || '';
  article.appendChild(pDate);

  // Description (shortened if long)
  const pDesc = document.createElement('p');
  const desc = week.description || '';
  pDesc.textContent = desc.length > 200 ? desc.slice(0, 200) + '…' : desc;
  article.appendChild(pDesc);

  // Link to details
  const a = document.createElement('a');
  a.href = `details.html?id=${encodeURIComponent(week.id || week.week_id || '')}`;
  a.textContent = 'View Details & Discussion';
  article.appendChild(a);

  return article;
}

/**
 * TODO: Implement the loadWeeks function.
 * This function needs to be 'async'.
 * It should:
 * 1. Use `fetch()` to get data from 'weeks.json'.
 * 2. Parse the JSON response into an array.
 * 3. Clear any existing content from `listSection`.
 * 4. Loop through the weeks array. For each week:
 * - Call `createWeekArticle()`.
 * - Append the returned <article> element to `listSection`.
 */
async function loadWeeks() {
  if (!listSection) {
    console.warn('week list section (#week-list-section) not found');
    return;
  }

  try {
    const res = await fetch('api/weeks.json');
    if (!res.ok) {
      console.error('Failed to load weeks.json', res.status);
      listSection.innerHTML = '<p>Unable to load weeks.</p>';
      return;
    }
    const data = await res.json();
    // Clear existing content
    listSection.innerHTML = '';

    if (!Array.isArray(data) || data.length === 0) {
      listSection.innerHTML = '<p>No weeks available.</p>';
      return;
    }

    data.forEach(week => {
      const art = createWeekArticle(week);
      listSection.appendChild(art);
    });
  } catch (err) {
    console.error('Error loading weeks', err);
    listSection.innerHTML = '<p>Error loading weeks.</p>';
  }
}

// --- Initial Page Load ---
// Call the function to populate the page.
loadWeeks();
