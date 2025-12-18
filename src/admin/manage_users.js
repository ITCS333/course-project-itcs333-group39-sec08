const API_BASE_URL = '/src/admin/api';
let students = [];
const studentTableBody = document.querySelector('#student-table tbody');
const addStudentForm = document.querySelector('#add-student-form');
const changePasswordForm = document.querySelector('#password-form');
const searchInput = document.querySelector('#search-input');
const tableHeaders = document.querySelectorAll('#student-table thead th[data-sort]');

async function apiRequest(endpoint, method = 'GET', data = null) {
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json',
        },
        credentials: 'same-origin'
    };

    if (data && (method === 'POST' || method === 'PUT')) {
        options.body = JSON.stringify(data);
    }

    try {
        let url = `${API_BASE_URL}/${endpoint}`;
        if (method === 'GET' && searchInput.value.trim()) {
            const searchTerm = searchInput.value.trim();
            url += (url.includes('?') ? '&' : '?') + `search=${encodeURIComponent(searchTerm)}`;
        }

        const response = await fetch(url, options);

        if (response.status === 401) {
            window.location.href = '/src/auth/login.html';
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
        students = [];
        renderTable(students);
    }
}

async function createStudent(studentData) {
    try {
        const result = await apiRequest('', 'POST', studentData);
        if (result && result.success) {
            await loadStudents();
            return result;
        }
    } catch (error) {
        console.error('Failed to create student:', error);
        throw error;
    }
}

async function updateStudent(studentId, studentData) {
    try {
        const payload = {
            student_id: parseInt(studentId),
            ...studentData
        };
        console.log('Sending update payload:', payload);
        const result = await apiRequest('', 'PUT', payload);
        if (result && result.success) {
            await loadStudents();
            return result;
        }
    } catch (error) {
        console.error('Failed to update student:', error);
        throw error;
    }
}

async function deleteStudent(studentId) {
    try {
        const result = await apiRequest(`?student_id=${studentId}`, 'DELETE');
        if (result && result.success) {
            await loadStudents();
            return result;
        }
    } catch (error) {
        console.error('Failed to delete student:', error);
        throw error;
    }
}

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

function createStudentRow(student) {
    const row = document.createElement('tr');

    const nameCell = document.createElement('td');
    nameCell.textContent = student.name || 'N/A';
    row.appendChild(nameCell);

    const idCell = document.createElement('td');
    idCell.textContent = student.student_id || student.id;
    row.appendChild(idCell);

    const emailCell = document.createElement('td');
    emailCell.textContent = student.email || 'N/A';
    row.appendChild(emailCell);

    const actionsCell = document.createElement('td');

    const editButton = document.createElement('button');
    editButton.textContent = 'Edit';
    editButton.className = 'edit-btn btn btn-sm btn-warning me-2';
    editButton.setAttribute('data-id', student.student_id || student.id);
    actionsCell.appendChild(editButton);

    const deleteButton = document.createElement('button');
    deleteButton.textContent = 'Delete';
    deleteButton.className = 'delete-btn btn btn-sm btn-danger';
    deleteButton.setAttribute('data-id', student.student_id || student.id);
    actionsCell.appendChild(deleteButton);

    row.appendChild(actionsCell);
    return row;
}

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
        const passwordData = {
            current_password: currentPassword,
            new_password: newPassword
        };

        await changePassword(passwordData);
        alert('Password updated successfully!');

        document.getElementById('current-password').value = '';
        document.getElementById('new-password').value = '';
        document.getElementById('confirm-password').value = '';

    } catch (error) {
        alert('Failed to change password: ' + error.message);
    }
}

async function handleAddStudent(event) {
    event.preventDefault();

    const name = document.getElementById('student-name').value.trim();
    const studentId = document.getElementById('student-id').value.trim();
    const email = document.getElementById('student-email').value.trim();
    const password = document.getElementById('default-password').value;

    if (!name || !studentId || !email || !password) {
        alert('Please fill out all required fields.');
        return;
    }

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

        addStudentForm.reset();
        document.getElementById('default-password').value = 'password123';

        const details = document.querySelector('details');
        if (details) {
            details.removeAttribute('open');
        }

        alert(`Student "${name}" added successfully!`);

    } catch (error) {
        alert('Failed to add student: ' + error.message);
    }
}

async function handleTableClick(event) {
    const target = event.target;

    if (target.classList.contains('delete-btn')) {
        const studentId = target.getAttribute('data-id');
        const student = students.find(s => (s.student_id === studentId) || (s.id == studentId));

        if (student && confirm(`Are you sure you want to delete ${student.name}?`)) {
            try {
                await deleteStudent(studentId);
                alert(`${student.name} has been deleted.`);
            } catch (error) {
                alert('Failed to delete student: ' + error.message);
            }
        }
    }

    if (target.classList.contains('edit-btn')) {
        const studentId = target.getAttribute('data-id');
        const student = students.find(s => (s.student_id === studentId) || (s.id == studentId));

        if (student) {
            document.getElementById('student-name').value = student.name;
            document.getElementById('student-id').value = student.student_id || student.id;
            document.getElementById('student-email').value = student.email;
            document.getElementById('default-password').value = '';

            document.getElementById('student-id').setAttribute('disabled', 'disabled');

            const details = document.querySelector('details');
            if (details) {
                details.setAttribute('open', 'open');
            }

            const addBtn = document.getElementById('add');
            addBtn.textContent = 'Update';
            addBtn.classList.remove('btn-success');
            addBtn.classList.add('btn-primary');
            addBtn.dataset.editing = 'true';
            addBtn.dataset.editId = student.student_id || student.id;
        }
    }
}

function handleSearch(event) {
    const searchTerm = searchInput.value.toLowerCase().trim();

    if (!searchTerm) {
        renderTable(students);
        return;
    }

    const filteredStudents = students.filter(student =>
        student.name.toLowerCase().includes(searchTerm) ||
        (student.student_id && student.student_id.toLowerCase().includes(searchTerm)) ||
        (student.email && student.email.toLowerCase().includes(searchTerm))
    );

    renderTable(filteredStudents);
}

function handleSort(event) {
    const th = event.currentTarget;
    const sortBy = th.getAttribute('data-sort');

    const fieldMap = {
        'name': 'name',
        'id': 'student_id',
        'email': 'email'
    };

    const apiField = fieldMap[sortBy] || sortBy;

    tableHeaders.forEach(header => {
        header.classList.remove('sorted-asc', 'sorted-desc');
    });

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

    students.sort((a, b) => {
        let aValue = a[apiField];
        let bValue = b[apiField];

        if (apiField === 'student_id') {
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

function initializeApp() {
    loadStudents();

    if (changePasswordForm) {
        changePasswordForm.addEventListener('submit', handleChangePassword);
    }

    if (addStudentForm) {
        addStudentForm.addEventListener('submit', async function(event) {
            event.preventDefault();

            const addBtn = document.getElementById('add');

            if (addBtn.dataset.editing === 'true') {
                const editId = addBtn.dataset.editId;
                const student = students.find(s => (s.student_id === editId) || (s.id == editId));

                if (student) {
                    const name = document.getElementById('student-name').value.trim();
                    const email = document.getElementById('student-email').value.trim();
                    const password = document.getElementById('default-password').value;

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
                        const updateData = { 
                            name: name,
                            email: email
                        };
                        if (password && password.trim()) {
                            updateData.password = password;
                        }

                        console.log('Edit mode - updating student ID:', editId, 'with data:', updateData);
                        await updateStudent(editId, updateData);

                        alert('Student information updated successfully!');

                        addBtn.textContent = 'Add Student';
                        addBtn.classList.remove('btn-primary');
                        addBtn.classList.add('btn-success');
                        delete addBtn.dataset.editing;
                        delete addBtn.dataset.editId;

                        document.getElementById('student-id').removeAttribute('disabled');
                        addStudentForm.reset();
                        document.getElementById('default-password').value = '';

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
    }

    if (studentTableBody) {
        studentTableBody.addEventListener('click', handleTableClick);
    }

    if (searchInput) {
        searchInput.addEventListener('input', handleSearch);
    }

    tableHeaders.forEach(th => {
        th.addEventListener('click', handleSort);
    });

    tableHeaders.forEach(th => {
        th.innerHTML = `${th.textContent} <span class="sort-indicator">↕</span>`;
    });

    const logoutBtn = document.getElementById('logout-btn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', async () => {
            try {
                sessionStorage.clear();
                localStorage.clear();
                // Call logout endpoint to destroy session
                await fetch('/src/auth/logout.php', {
                    method: 'POST',
                    credentials: 'same-origin'
                }).catch(() => {});
                window.location.href = '/src/auth/login.html';
            } catch (error) {
                console.error('Logout error:', error);
                window.location.href = '/src/auth/login.html';
            }
        });
    }
}

document.addEventListener('DOMContentLoaded', initializeApp);
