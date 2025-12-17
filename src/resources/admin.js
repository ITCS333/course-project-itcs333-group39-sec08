/*
  Requirement: Make the "Manage Resources" page interactive.

  Instructions:
  1. Link this file to `admin.html` using:
     <script src="admin.js" defer></script>
  
  2. In `admin.html`, add an `id="resources-tbody"` to the <tbody> element
     inside your `resources-table`.
  
  3. Implement the TODOs below.
*/

// --- Global Data Store ---
// This will hold the resources loaded from the JSON file.
let resources = [];
let editingResourceId = null;

// --- Element Selections ---
// TODO: Select the resource form ('#resource-form').
let resourceForm = document.querySelector('#resource-form');



// TODO: Select the resources table body ('#resources-tbody').
let resourcetbody = document.querySelector('#resources-tbody');

// --- Functions ---

/**
 * TODO: Implement the createResourceRow function.
 * It takes one resource object {id, title, description}.
 * It should return a <tr> element with the following <td>s:
 * 1. A <td> for the `title`.
 * 2. A <td> for the `description`.
 * 3. A <td> containing two buttons:
 * - An "Edit" button with class "edit-btn" and `data-id="${id}"`.
 * - A "Delete" button with class "delete-btn" and `data-id="${id}"`.
 */
function createResourceRow(resource) {

    // Create table row
    const tr = document.createElement('tr');

    // ---- Title TD ----
    const titleTd = document.createElement('td');
    titleTd.textContent = resource.title;
  

    // ---- Description TD ----
    const descTd = document.createElement('td');
    descTd.textContent = resource.description;
   

    // ---- Actions TD ----
    const actionsTd = document.createElement('td');

    // Edit button
    const editBtn = document.createElement('button');
    editBtn.textContent = "Edit";
    editBtn.classList.add("edit-btn");
    editBtn.dataset.id = resource.id;

    // Delete button
    const deleteBtn = document.createElement('button');
    deleteBtn.textContent = "Delete";
    deleteBtn.classList.add("delete-btn");
    deleteBtn.dataset.id = resource.id;

    // Append buttons to actions TD
    actionsTd.appendChild(editBtn);
    actionsTd.appendChild(deleteBtn);

    // Append all tds to row
    tr.appendChild(titleTd);
    tr.appendChild(descTd);
    tr.appendChild(actionsTd);

    return tr; // return the finished <tr>
}

 


/**
 * TODO: Implement the renderTable function.
 * It should:
 * 1. Clear the `resourcesTableBody`.
 * 2. Loop through the global `resources` array.
 * 3. For each resource, call `createResourceRow()`, and
 * append the resulting <tr> to `resourcesTableBody`.
 */
function renderTable() {
  // 1. Clear the resourcesTableBody
    resourcetbody.innerHTML = '';
    
    // 2. Loop through the global resources array
    resources.forEach(resource => {
        // 3. For each resource, call createResourceRow()
        const row = createResourceRow(resource);
        
        // Append the resulting <tr> to resourcesTableBody
        resourcetbody.appendChild(row);
    });
  
}


/**
 * TODO: Implement the handleAddResource function.
 * This is the event handler for the form's 'submit' event.
 * It should:
 * 1. Prevent the form's default submission.
 * 2. Get the values from the title, description, and link inputs.
 * 3. IF in edit mode (editingId not null): update the existing resource.
 * 4. ELSE: Create a new resource object with a unique ID.
 * 5. Call `renderTable()` to refresh the list.
 * 6. Reset the form and exit edit mode if needed.
 */
function handleAddResource(event) {
  // 1. Prevent the form's default submission.
  event.preventDefault();

  // 2. Get the values from the title, description, and link inputs.
  const title = document.getElementById('resource-title').value.trim();
  const description = document.getElementById('resource-description').value.trim();
  const link = document.getElementById('resource-link').value.trim();

  // 3. IF in edit mode: update the existing resource (in-memory only).
  if (editingId) {
    resources = resources.map((r) => {
      if (r.id === editingId) {
        return { ...r, title, description, link };
      }
      return r;
    });

    // Exit edit mode
    editingId = null;
    document.getElementById('add-resource').textContent = "Add Resource";
    const formTitle = document.getElementById('form-title');
    if (formTitle) formTitle.textContent = "Add a New Resource";
  } else {
    // 4. ELSE: Create a new resource object with a unique ID.
    const newResource = {
      id: `res_${Date.now()}`,
      title,
      description,
      link
    };

    // Add it to the global resources array (in-memory only).
    resources.push(newResource);
  }

  // 5. Call `renderTable()` to refresh the list.
  renderTable();

  // 6. Reset the form.
  event.target.reset();
}


/**
 * TODO: Implement the handleTableClick function.
 * This is an event listener on the `resourcesTableBody` (for delegation).
 * It should:
 * 1. If clicked is "delete-btn": delete resource then render.
 * 2. If clicked is "edit-btn": load data into the form + enter edit mode.
 */
function handleTableClick(event) {

  // ---- DELETE ----
  // 1. Check if the clicked element has the class "delete-btn".
  if (event.target.classList.contains('delete-btn')) {
    // 2. Get the `data-id` attribute from the button.
    const resourceId = event.target.getAttribute('data-id');

    // 3. Filter out the resource with matching ID (in-memory only).
    resources = resources.filter((r) => r.id !== resourceId);

    // 4. Refresh the table.
    renderTable();

    // If we were editing the deleted item, exit edit mode
    if (editingId === resourceId) {
      editingId = null;
      document.getElementById('add-resource').textContent = "Add Resource";
      const formTitle = document.getElementById('form-title');
      if (formTitle) formTitle.textContent = "Add a New Resource";
      resourceForm.reset();
    }

    return;
  }

  // ---- EDIT ----
  // 1. Check if the clicked element has the class "edit-btn".
  if (event.target.classList.contains('edit-btn')) {
    // 2. Get the `data-id` attribute from the button.
    const resourceId = event.target.getAttribute('data-id');

    // 3. Find the matching resource in the global array.
    const resource = resources.find((r) => r.id === resourceId);
    if (!resource) return;

    // 4. Enter edit mode.
    editingId = resourceId;

    // 5. Fill the form inputs with the resource data.
    document.getElementById('resource-title').value = resource.title || '';
    document.getElementById('resource-description').value = resource.description || '';
    document.getElementById('resource-link').value = resource.link || '';

    // 6. Change the button text to "Update Resource".
    document.getElementById('add-resource').textContent = "Update Resource";}
}



/**
 * TODO: Implement the loadAndInitialize function.
 * This function needs to be 'async'.
 * It should:
 * 1. Use `fetch()` to get data from 'resources.json'.
 * 2. Parse the JSON response and store the result in the global `resources` array.
 * 3. Call `renderTable()` to populate the table for the first time.
 * 4. Add the 'submit' event listener to `resourceForm` (calls `handleAddResource`).
 * 5. Add the 'click' event listener to `resourcesTableBody` (calls `handleTableClick`).
 */
async function loadAndInitialize() {
   try {
        // 1. Use fetch() to get data from 'resources.json'
        const response = await fetch('API/resources.json');
        
        // Check if the response is successful
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        // 2. Parse the JSON response and store the result in the global `resources` array
        const data = await response.json();
        resources = data; // Store in global resources array
        
        // 3. Call renderTable() to populate the table for the first time
        renderTable();
        
        // 4. Add the 'submit' event listener to `resourceForm` (calls `handleAddResource`)
        const resourceForm = document.getElementById('resource-form');
        if (resourceForm) {
            resourceForm.addEventListener('submit', handleAddResource);
        }
        
        // 5. Add the 'click' event listener to `resourcesTableBody` (calls `handleTableClick`)
        const resourcesTableBody = document.getElementById('resources-tbody');
        if (resourcesTableBody) {
            resourcesTableBody.addEventListener('click', handleTableClick);
        }
        
        console.log('Application initialized successfully!');
        
    } catch (error) {
        // Handle errors (e.g., file not found, network error)
        console.error('Error loading resources:', error);
        
        // Fallback: Use default data if JSON file is not available
        resources = [
            { id: 'res_1', title: 'Chapter 1 Slides', description: 'Introduction to Software Engineering', link: '#' },
            { id: 'res_2', title: 'Chapter 2 Slides', description: 'Life cycle of Software Engineering', link: '#' }
        ];
        
        // Still render the table with fallback data
        renderTable();
        
        // Still add event listeners
        const resourceForm = document.getElementById('resource-form');
        if (resourceForm) {
            resourceForm.addEventListener('submit', handleAddResource);
        }
        
        const resourcesTableBody = document.getElementById('resources-tbody');
        if (resourcesTableBody) {
            resourcesTableBody.addEventListener('click', handleTableClick);
        }
        
        alert('Could not load resources from file. Using default data instead.');
    }
}

// --- Initial Page Load ---
// Call the main async function to start the application.
loadAndInitialize();

