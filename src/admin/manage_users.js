/*
 Admin Portal - Student Management System
*/
// --- Global Data Store ---
let students = [];
// --- Element Selections ---
const studentTableBody = document.querySelector('#student-table tbody');
const addStudentForm = document.querySelector('#add-student-form');
const changePasswordForm = document.querySelector('#password-form');
const searchInput = document.querySelector('#search-input');
const tableHeaders = document.querySelectorAll('#student-table thead th[data-sort]');
// --- Functions ---
/**
* Create a table row for a student
*/
function createStudentRow(student) {
 const row = document.createElement('tr');
 const nameCell = document.createElement('td');
 nameCell.textContent = student.name;
 row.appendChild(nameCell);
 const idCell = document.createElement('td');
 idCell.textContent = student.id;
 row.appendChild(idCell);
 const emailCell = document.createElement('td');
 emailCell.textContent = student.email;
 row.appendChild(emailCell);
 const actionsCell = document.createElement('td');
 const editButton = document.createElement('button');
 editButton.textContent = 'Edit';
 editButton.className = 'edit-btn btn btn-sm btn-warning me-2';
 editButton.setAttribute('data-id', student.id);
 actionsCell.appendChild(editButton);
 const deleteButton = document.createElement('button');
 deleteButton.textContent = 'Delete';
 deleteButton.className = 'delete-btn btn btn-sm btn-danger';
 deleteButton.setAttribute('data-id', student.id);
 actionsCell.appendChild(deleteButton);
 row.appendChild(actionsCell);
 return row;
}
/**
* Render the student table
*/
function renderTable(studentArray) {
 studentTableBody.innerHTML = '';
 if (studentArray.length === 0) {
   const emptyRow = document.createElement('tr');
   const emptyCell = document.createElement('td');
   emptyCell.colSpan = 4;
   emptyCell.textContent = 'No students found.';
   emptyCell.className = 'text-center text-muted py-4';
   emptyRow.appendChild(emptyCell);
   studentTableBody.appendChild(emptyRow);
   return;
 }
 studentArray.forEach(student => {
   const row = createStudentRow(student);
   studentTableBody.appendChild(row);
 });
}
/**
* Handle password change form submission
*/
function handleChangePassword(event) {
 event.preventDefault();
 const currentPassword = document.getElementById('current-password').value;
 const newPassword = document.getElementById('new-password').value;
 const confirmPassword = document.getElementById('confirm-password').value;
 if (newPassword !== confirmPassword) {
   alert('Passwords do not match.');
   return;
 }
 if (newPassword.length < 8) {
   alert('Password must be at least 8 characters.');
   return;
 }
 alert('Password updated successfully!');
 // Clear form fields
 document.getElementById('current-password').value = '';
 document.getElementById('new-password').value = '';
 document.getElementById('confirm-password').value = '';
}
/**
* Handle add student form submission
*/
function handleAddStudent(event) {
 event.preventDefault();
 const name = document.getElementById('student-name').value.trim();
 const id = document.getElementById('student-id').value.trim();
 const email = document.getElementById('student-email').value.trim();
 // Validation
 if (!name || !id || !email) {
   alert('Please fill out all required fields.');
   return;
 }
 // Check for duplicate ID
 if (students.some(student => student.id === id)) {
   alert('A student with this ID already exists.');
   return;
 }
 // Check for duplicate email
 if (students.some(student => student.email === email)) {
   alert('A student with this email already exists.');
   return;
 }
 // Validate email format
 if (!email.includes('@')) {
   alert('Please enter a valid email address.');
   return;
 }
 // Create new student object
 const newStudent = {
   name: name,
   id: id,
   email: email
 };
 // Add to students array
 students.push(newStudent);
 // Update table
 renderTable(students);
 // Clear form fields
 document.getElementById('student-name').value = '';
 document.getElementById('student-id').value = '';
 document.getElementById('student-email').value = '';
 document.getElementById('default-password').value = 'password123';
 // Close the details element
 const details = document.querySelector('details');
 if (details) {
   details.removeAttribute('open');
 }
 // Show success message
 alert(`Student "${name}" added successfully!`);
}
/**
* Handle table actions (edit/delete)
*/
function handleTableClick(event) {
 const target = event.target;
 // Delete button clicked
 if (target.classList.contains('delete-btn')) {
   const studentId = target.getAttribute('data-id');
   const student = students.find(s => s.id === studentId);
   if (student && confirm(`Are you sure you want to delete ${student.name}?`)) {
     students = students.filter(s => s.id !== studentId);
     renderTable(students);
     alert(`${student.name} has been deleted.`);
   }
 }
 // Edit button clicked
 if (target.classList.contains('edit-btn')) {
   const studentId = target.getAttribute('data-id');
   const student = students.find(s => s.id === studentId);
   if (student) {
     // Fill the form fields with the student data
     document.getElementById('student-name').value = student.name;
     document.getElementById('student-id').value = student.id;
     document.getElementById('student-email').value = student.email;
     document.getElementById('default-password').value = 'password123';
     // Disable editing the ID
     document.getElementById('student-id').setAttribute('disabled', 'disabled');
     // Open the details element if closed
     const details = document.querySelector('details');
     if (details) {
       details.setAttribute('open', 'open');
     }
     // Change the Add button to Update
     const addBtn = document.getElementById('add');
     addBtn.textContent = 'Update';
     addBtn.classList.remove('btn-success');
     addBtn.classList.add('btn-primary');
     addBtn.dataset.editing = 'true';
     addBtn.dataset.editId = studentId;
   }
 }
}
/**
* Handle search functionality
*/
function handleSearch(event) {
 const searchTerm = searchInput.value.toLowerCase().trim();
 if (!searchTerm) {
   renderTable(students);
   return;
 }
 const filteredStudents = students.filter(student =>
   student.name.toLowerCase().includes(searchTerm) ||
student.id.toLowerCase().includes(searchTerm) ||
   student.email.toLowerCase().includes(searchTerm)
 );
 renderTable(filteredStudents);
}
/**
* Handle table sorting
*/
function handleSort(event) {
 const th = event.currentTarget;
 const sortBy = th.getAttribute('data-sort');
 // Remove sort indicators from other headers
 tableHeaders.forEach(header => {
   header.classList.remove('sorted-asc', 'sorted-desc');
 });
 // Determine sort direction
 let sortDirection = 'asc';
 if (th.classList.contains('sorted-asc')) {
   sortDirection = 'desc';
   th.classList.remove('sorted-asc');
   th.classList.add('sorted-desc');
 } else if (th.classList.contains('sorted-desc')) {
   sortDirection = 'asc';
   th.classList.remove('sorted-desc');
   th.classList.add('sorted-asc');
 } else {
   th.classList.add('sorted-asc');
 }
 // Sort the students array
 students.sort((a, b) => {
   let aValue = a[sortBy];
   let bValue = b[sortBy];
   if (sortBy === 'id') {
     // For IDs, compare as numbers if possible
     const aNum = parseInt(aValue);
     const bNum = parseInt(bValue);
     if (!isNaN(aNum) && !isNaN(bNum)) {
       aValue = aNum;
       bValue = bNum;
     }
   }
   let comparison = 0;
   if (typeof aValue === 'string' && typeof bValue === 'string') {
     comparison = aValue.localeCompare(bValue, undefined, { sensitivity: 'base' });
   } else {
     if (aValue < bValue) comparison = -1;
     if (aValue > bValue) comparison = 1;
   }
   return sortDirection === 'asc' ? comparison : -comparison;
 });
 renderTable(students);
}
/**
* Load students data and initialize the application
*/
async function loadStudentsAndInitialize() {
 try {
   // Load student data from JSON file
   const response = await fetch('students.json');
   if (!response.ok) {
     throw new Error(`Failed to load student data: ${response.status}`);
   }
   students = await response.json();
   renderTable(students);
 } catch (error) {
   console.error('Error loading students data:', error);
   // Fallback data (Bahraini students)
   students = [
     {
       "name": "Ali Hassan",
       "id": "202101234",
       "email": "202101234@stu.uob.edu.bh"
     },
     {
       "name": "Fatema Ahmed",
       "id": "202205678",
       "email": "202205678@stu.uob.edu.bh"
     },
     {
       "name": "Mohamed Abdulla",
       "id": "202311001",
       "email": "202311001@stu.uob.edu.bh"
     },
     {
       "name": "Noora Salman",
       "id": "202100987",
       "email": "202100987@stu.uob.edu.bh"
     },
     {
       "name": "Zainab Ebrahim",
       "id": "202207766",
       "email": "202207766@stu.uob.edu.bh"
     }
   ];
   renderTable(students);
   alert('Loaded fallback student data. Some features may be limited.');
 }
 // Set up event listeners
 changePasswordForm.addEventListener('submit', handleChangePassword);
 addStudentForm.addEventListener('submit', function(event) {
   event.preventDefault();
   const addBtn = document.getElementById('add');
   if (addBtn.dataset.editing === 'true') {
     // Update mode
     const editId = addBtn.dataset.editId;
     const student = students.find(s => s.id === editId);
     if (student) {
       const name = document.getElementById('student-name').value.trim();
       const email = document.getElementById('student-email').value.trim();
       // Validation
       if (!name || !email) {
         alert('Please fill out all required fields.');
         return;
       }
       if (!email.includes('@')) {
         alert('Please enter a valid email address.');
         return;
       }
       // Check for duplicate email (except self)
       if (students.some(s => s.email === email && s.id !== editId)) {
         alert('A student with this email already exists.');
         return;
       }
       student.name = name;
       student.email = email;
       renderTable(students);
       alert('Student information updated successfully!');
       // Reset form
       addBtn.textContent = 'Add Student';
       addBtn.classList.remove('btn-primary');
       addBtn.classList.add('btn-success');
       addBtn.removeAttribute('data-editing');
       addBtn.removeAttribute('data-edit-id');
       document.getElementById('student-id').removeAttribute('disabled');
       addStudentForm.reset();
       document.getElementById('default-password').value = 'password123';
       // Close details
       const details = document.querySelector('details');
       if (details) {
         details.removeAttribute('open');
       }
     }
   } else {
     handleAddStudent(event);
   }
 });
 studentTableBody.addEventListener('click', handleTableClick);
 searchInput.addEventListener('input', handleSearch);
 tableHeaders.forEach(th => {
   th.addEventListener('click', handleSort);
 });
 // Add sort indicators
 tableHeaders.forEach(th => {
   th.innerHTML = `${th.textContent} <span class="sort-indicator">↕</span>`;
 });
}
// --- Initialize Application ---
document.addEventListener('DOMContentLoaded', loadStudentsAndInitialize);
