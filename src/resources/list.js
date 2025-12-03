/*
  Requirement: Populate the "Course Resources" list page.

  Instructions:
  1. Link this file to `list.html` using:
     <script src="list.js" defer></script>

  2. In `list.html`, add an `id="resource-list-section"` to the
     <section> element that will contain the resource articles.

  3. Implement the TODOs below.
*/

// --- Element Selections ---
// TODO: Select the section for the resource list ('#resource-list-section').
let resourcelistsection = document.querySelector('#resource-list-section');
// --- Functions ---

/**
 * TODO: Implement the createResourceArticle function.
 * It takes one resource object {id, title, description}.
 * It should return an <article> element matching the structure in `list.html`.
 * The "View Resource & Discussion" link's `href` MUST be set to `details.html?id=${id}`.
 * (This is how the detail page will know which resource to load).
 */
function createResourceArticle(resource) {
 // Create article element
    const article = document.createElement('article');
    
    // Create heading (h2) for the resource title
    const heading = document.createElement('h2');
    heading.textContent = resource.title;
    
    // Create paragraph (p) for the resource description
    const description = document.createElement('p');
    description.textContent = resource.description;
    
    // Create anchor tag (a) for the link
    const link = document.createElement('a');
    link.textContent = "View Resource & Discussion";
    link.href = `details.html?id=${resource.id}`; // ✅ MUST be set to details.html?id=${id}
     // Append all elements to article
    article.appendChild(heading);
    article.appendChild(description);
    article.appendChild(link);
    
    return article;
    }

/**
 * TODO: Implement the loadResources function.
 * This function needs to be 'async'.
 * It should:
 * 1. Use `fetch()` to get data from 'resources.json'.
 * 2. Parse the JSON response into an array.
 * 3. Clear any existing content from `listSection`.
 * 4. Loop through the resources array. For each resource:
 * - Call `createResourceArticle()`.
 * - Append the returned <article> element to `listSection`.
 */
async function loadResources(){
 try {
        
        // 1. Fetch data from resources.json
        const response = await fetch('resources.json');
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        // 2. Parse the JSON response into an array
        const resources = await response.json();
        
        // 3. Clear existing content from the section
        resourcelistsection.innerHTML = '';

        // 4. Loop through resources and append articles
        resources.forEach(resource => {
            const article = createResourceArticle(resource);
            resourcelistsection.appendChild(article);
        });

    } catch (error) {
        console.error('Error loading resources:', error);
        resourcelistsection.innerHTML = '<p>Failed to load resources.</p>';
    }
}


// --- Initial Page Load ---
// Call the function to populate the page.
loadResources();
