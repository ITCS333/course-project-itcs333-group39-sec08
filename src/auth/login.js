/*
 Admin Portal - Student Management System
 Connected to PHP API Backend
*/

// --- API Configuration ---
const API_BASE_URL = '/admin/api'; // Adjust based on your directory structure

// --- Global Data Store ---
let students = [];

// --- Element Selections ---
const studentTableBody = document.querySelector('#student-table tbody');
const addStudentForm = document.querySelector('#add-student-form');
const changePasswordForm = document.querySelector('#password-form');
const searchInput = document.querySelector('#search-input');
const tableHeaders = document.querySelectorAll('#student-table thead th[data-sort]');

// --- API Helper Functions ---

/**
 * Make API request with error handling
 */
async function apiRequest(endpoint, method = 'GET', data = null) {
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json',
        },
        credentials: 'same-origin' // Include session cookies
    };

    if (data && (method === 'POST' || method === 'PUT')) {
        options.body = JSON.stringify(data);
    }

    try {
        const response = await fetch(`${API_BASE_URL}/${endpoint}`, options);
        
        if (response.status === 401) {
            // Unauthorized - redirect to login
            window.location.href = '/login.php';
            return null;
        }

        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.message || 'API request failed');
        }
        
        return result;
    } catch (error) {
        console.error('API Request Error:', error);
        throw error;
    }
}

/**
 * Load all students from API
 */
async function loadStudents() {
    try {
        const result = await apiRequest('', 'GET');
        if (result && result.data) {
            students = result.data;
            renderTable(students);
        }
    } catch (error) {
        console.error('Failed to load students:', error);
        alert('Failed to load students. Please check your connection.');
        // Fallback to empty array
        students = [];
        renderTable(students);
    }
}

/**
 * Create a new student via API
 */
async function createStudent(studentData) {
    try {
        const result = await apiRequest('', 'POST', studentData);
        if (result && result.success) {
            // Reload students to get updated list
            await loadStudents();
            return result;
        }
    } catch (error) {
        console.error('Failed to create student:', error);
        throw error;
    }
}

/**
 * Update student via API
 */
async function updateStudent(studentId, studentData) {
    try {
        const result = await apiRequest('', 'PUT', {
            student_id: studentId,
            ...studentData
        });
        if (result && result.success) {
            await loadStudents(); // Reload to get updated data
            return result;
        }
    } catch (error) {
        console.error('Failed to update student:', error);
        throw error;
    }
}

/**
 * Delete student via API
 */
async function deleteStudent(studentId) {
    try {
        const result = await apiRequest(`?student_id=${studentId}`, 'DELETE');
        if (result && result.success) {
            await loadStudents(); // Reload to get updated data
            return result;
        }
    } catch (error) {
        console.error('Failed to delete student:', error);
        throw error;
    }
}

/**
 * Change password via API
 */
async function changePassword(passwordData) {
    try {
        const result = await apiRequest('?action=change_password', 'POST', passwordData);
        if (result && result.success) {
            return result;
        }
    } catch (error) {
        console.error('Failed to change password:', error);
        throw error;
    }
}

// --- UI Functions ---

/**
 * Create a table row for a student
 */
function createStudentRow(student) {
    const row = document.createElement('tr');
    
    // Name cell
    const nameCell = document.createElement('td');
    nameCell.textContent = student.name;
    row.appendChild(nameCell);
    
    // ID cell
    const idCell = document.createElement('td');
    idCell.textContent = student.student_id;
    row.appendChild(idCell);
    
    // Email cell
    const emailCell = document.createElement('td');
    emailCell.textContent = student.email;
    row.appendChild(emailCell);
    
    // Actions cell
    const actionsCell = document.createElement('td');
    
    // Edit button
    const editButton = document.createElement('button');
    editButton.textContent = 'Edit';
    editButton.className = 'edit-btn btn btn-sm btn-warning me-2';
    editButton.setAttribute('data-id', student.student_id);
    actionsCell.appendChild(editButton);
    
    // Delete button
    const deleteButton = document.createElement('button');
    deleteButton.textContent = 'Delete';
    deleteButton.className = 'delete-btn btn btn-sm btn-danger';
    deleteButton.setAttribute('data-id', student.student_id);
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
async function handleChangePassword(event) {
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
    
    try {
        // For admin changing own password, you might need a different endpoint
        // This currently uses the student password change endpoint
        const passwordData = {
            student_id: 'admin', // You need to handle admin password separately
            current_password: currentPassword,
            new_password: newPassword
        };
        
        await changePassword(passwordData);
        alert('Password updated successfully!');
        
        // Clear form fields
        document.getElementById('current-password').value = '';
        document.getElementById('new-password').value = '';
        document.getElementById('confirm-password').value = '';
        
    } catch (error) {
        alert('Failed to change password: ' + error.message);
    }
}

/**
 * Handle add student form submission
 */
async function handleAddStudent(event) {
    event.preventDefault();
    
    const name = document.getElementById('student-name').value.trim();
    const studentId = document.getElementById('student-id').value.trim();
    const email = document.getElementById('student-email').value.trim();
    const password = document.getElementById('default-password').value;
    
    // Validation
    if (!name || !studentId || !email || !password) {
        alert('Please fill out all required fields.');
        return;
    }
    
    // Validate email format
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        alert('Please enter a valid email address.');
        return;
    }
    
    try {
        const studentData = {
            student_id: studentId,
            name: name,
            email: email,
            password: password
        };
        
        await createStudent(studentData);
        
        // Clear form fields
        addStudentForm.reset();
        document.getElementById('default-password').value = 'password123';
        
        // Close the details element
        const details = document.querySelector('details');
        if (details) {
            details.removeAttribute('open');
        }
        
        alert(`Student "${name}" added successfully!`);
        
    } catch (error) {
        alert('Failed to add student: ' + error.message);
    }
}

/**
 * Handle table actions (edit/delete)
 */
async function handleTableClick(event) {
    const target = event.target;
    
    // Delete button clicked
    if (target.classList.contains('delete-btn')) {
        const studentId = target.getAttribute('data-id');
        const student = students.find(s => s.student_id === studentId);
        
        if (student && confirm(`Are you sure you want to delete ${student.name}?`)) {
            try {
                await deleteStudent(studentId);
                alert(`${student.name} has been deleted.`);
            } catch (error) {
                alert('Failed to delete student: ' + error.message);
            }
        }
    }
    
    // Edit button clicked
    if (target.classList.contains('edit-btn')) {
        const studentId = target.getAttribute('data-id');
        const student = students.find(s => s.student_id === studentId);
        
        if (student) {
            // Fill the form fields with the student data
            document.getElementById('student-name').value = student.name;
            document.getElementById('student-id').value = student.student_id;
            document.getElementById('student-email').value = student.email;
            document.getElementById('default-password').value = ''; // Clear password field for editing
            
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
        student.student_id.toLowerCase().includes(searchTerm) ||
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
    
    // Map UI sort field to API field names
    const fieldMap = {
        'name': 'name',
        'id': 'student_id',
        'email': 'email'
    };
    
    const apiField = fieldMap[sortBy] || sortBy;
    
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
        let aValue = a[apiField];
        let bValue = b[apiField];
        
        if (apiField === 'student_id') {
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
 * Initialize the application
 */
function initializeApp() {
    // Load initial data
    loadStudents();
    
    // Set up event listeners
    changePasswordForm.addEventListener('submit', handleChangePassword);
    
    addStudentForm.addEventListener('submit', async function(event) {
        event.preventDefault();
        
        const addBtn = document.getElementById('add');
        
        if (addBtn.dataset.editing === 'true') {
            // Update mode
            const editId = addBtn.dataset.editId;
            const student = students.find(s => s.student_id === editId);
            
            if (student) {
                const name = document.getElementById('student-name').value.trim();
                const email = document.getElementById('student-email').value.trim();
                
                // Validation
                if (!name || !email) {
                    alert('Please fill out all required fields.');
                    return;
                }
                
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email)) {
                    alert('Please enter a valid email address.');
                    return;
                }
                
                try {
                    await updateStudent(editId, { name, email });
                    
                    alert('Student information updated successfully!');
                    
                    // Reset form
                    addBtn.textContent = 'Add Student';
                    addBtn.classList.remove('btn-primary');
                    addBtn.classList.add('btn-success');
                    delete addBtn.dataset.editing;
                    delete addBtn.dataset.editId;
                    
                    document.getElementById('student-id').removeAttribute('disabled');
                    addStudentForm.reset();
                    document.getElementById('default-password').value = 'password123';
                    
                    // Close details
                    const details = document.querySelector('details');
                    if (details) {
                        details.removeAttribute('open');
                    }
                    
                } catch (error) {
                    alert('Failed to update student: ' + error.message);
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
    
    // Add logout button functionality
    const logoutBtn = document.getElementById('logout-btn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', async () => {
            try {
                // Call logout API if you have one
                // await fetch('/logout.php', { method: 'POST' });
                
                // Clear session and redirect
                sessionStorage.clear();
                localStorage.clear();
                window.location.href = '/login.php';
            } catch (error) {
                console.error('Logout error:', error);
                window.location.href = '/login.php';
            }
        });
    }
}

// --- Initialize Application ---
document.addEventListener('DOMContentLoaded', initializeApp);
