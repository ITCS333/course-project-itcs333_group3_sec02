/*
  Requirement: Add interactivity and data management to the Admin Portal.

  Instructions:
  1. Link this file to your HTML using a <script> tag with the 'defer' attribute.
     Example: <script src="manage_users.js" defer></script>
  2. Implement the JavaScript functionality as described in the TODO comments.
  3. All data management will be done by manipulating the 'students' array
     and re-rendering the table.
*/

// --- Global Data Store ---
// This array will be populated with data fetched from 'students.json'.
let students = [];

// --- Element Selections ---
// We can safely select elements here because 'defer' guarantees
// the HTML document is parsed before this script runs.

// TODO: Select the student table body (tbody).
const studentTableBody = document.querySelector('#student-table tbody');
// TODO: Select the "Add Student" form.
// (You'll need to add id="add-student-form" to this form in your HTML).
const addStudentForm = document.getElementById('add-student-form');
// TODO: Select the "Change Password" form.
// (You'll need to add id="password-form" to this form in your HTML).
const changePasswordForm = document.getElementById('password-form');
// TODO: Select the search input field.
// (You'll need to add id="search-input" to this input in your HTML).
const searchInput = document.getElementById('search-input');
// TODO: Select all table header (th) elements in thead.
const tableHeaders = document.querySelectorAll('#student-table thead th');
// --- Functions ---

/**
 * TODO: Implement the createStudentRow function.
 * This function should take a student object {name, id, email} and return a <tr> element.
 * The <tr> should contain:
 * 1. A <td> for the student's name.
 * 2. A <td> for the student's ID.
 * 3. A <td> for the student's email.
 * 4. A <td> containing two buttons:
 * - An "Edit" button with class "edit-btn" and a data-id attribute set to the student's ID.
 * - A "Delete" button with class "delete-btn" and a data-id attribute set to the student's ID.
 */
function createStudentRow(student) {
  // ... your implementation here ...
  const tr = document.createElement('tr');
  // Name cell
  const nameTd = document.createElement('td');
  nameTd.textContent = student.name;
  tr.appendChild(nameTd);
  // ID cell
  const idTd = document.createElement('td');
  idTd.textContent = student.id;
  tr.appendChild(idTd);
  // Email cell
  const emailTd = document.createElement('td');
  emailTd.textContent = student.email;
  tr.appendChild(emailTd);
  // Actions cell
  const actionsTd = document.createElement('td');
  const editBtn = document.createElement('button');
  editBtn.textContent = 'Edit';
  editBtn.classList.add('edit-btn');
  editBtn.dataset.id = student.id;
  const deleteBtn = document.createElement('button');
  deleteBtn.textContent = 'Delete';
  deleteBtn.classList.add('delete-btn');
  deleteBtn.dataset.id = student.id;
  actionsTd.appendChild(editBtn);
  actionsTd.appendChild(deleteBtn);
  tr.appendChild(actionsTd);
  return tr;
}
/**
 * TODO: Implement the renderTable function.
 * This function takes an array of student objects.
 * It should:
 * 1. Clear the current content of the `studentTableBody`.
 * 2. Loop through the provided array of students.
 * 3. For each student, call `createStudentRow` and append the returned <tr> to `studentTableBody`.
 */
function renderTable(studentArray) {
  // ... your implementation here ...
  studentTableBody.innerHTML = '';
  studentArray.forEach((student) => {
    const row = createStudentRow(student);
    studentTableBody.appendChild(row);
  });
}

/**
 * TODO: Implement the handleChangePassword function.
 * This function will be called when the "Update Password" button is clicked.
 * It should:
 * 1. Prevent the form's default submission behavior.
 * 2. Get the values from "current-password", "new-password", and "confirm-password" inputs.
 * 3. Perform validation:
 * - If "new-password" and "confirm-password" do not match, show an alert: "Passwords do not match."
 * - If "new-password" is less than 8 characters, show an alert: "Password must be at least 8 characters."
 * 4. If validation passes, show an alert: "Password updated successfully!"
 * 5. Clear all three password input fields.
 */
function handleChangePassword(event) {
  // ... your implementation here ...
  event.preventDefault();
  const currentPasswordInput = document.getElementById('current-password');
  const newPasswordInput = document.getElementById('new-password');
  const confirmPasswordInput = document.getElementById('confirm-password');
  const currentPassword = currentPasswordInput.value.trim();
  const newPassword = newPasswordInput.value.trim();
  const confirmPassword = confirmPasswordInput.value.trim();
  if (newPassword !== confirmPassword) {
    alert('Passwords do not match.');
    return;
  }
  if (newPassword.length < 8) {
    alert('Password must be at least 8 characters.');
    return;
  }
  // (No real backend, so just show success)
  alert('Password updated successfully!');
  // Clear fields
  currentPasswordInput.value = '';
  newPasswordInput.value = '';
  confirmPasswordInput.value = '';
}
/**
 * TODO: Implement the handleAddStudent function.
 * This function will be called when the "Add Student" button is clicked.
 * It should:
 * 1. Prevent the form's default submission behavior.
 * 2. Get the values from "student-name", "student-id", and "student-email".
 * 3. Perform validation:
 * - If any of the three fields are empty, show an alert: "Please fill out all required fields."
 * - (Optional) Check if a student with the same ID already exists in the 'students' array.
 * 4. If validation passes:
 * - Create a new student object: { name, id, email }.
 * - Add the new student object to the global 'students' array.
 * - Call `renderTable(students)` to update the view.
 * 5. Clear the "student-name", "student-id", "student-email", and "default-password" input fields.
 */
function handleAddStudent(event) {
  // ... your implementation here ...
   event.preventDefault();
  const nameInput = document.getElementById('student-name');
  const idInput = document.getElementById('student-id');
  const emailInput = document.getElementById('student-email');
  const defaultPasswordInput = document.getElementById('default-password');
  const name = nameInput.value.trim();
  const id = idInput.value.trim();
  const email = emailInput.value.trim();
 
  if (!name || !id || !email) {
    alert('Please fill out all required fields.');
    return;
  }
  //check duplicate ID
  const existingStudent = students.find((s) => s.id === id);
  if (existingStudent) {
    alert('A student with this ID already exists.');
    return;
  }
  // Create new student object
  const newStudent = {
    name: name,
    id: id,
    email: email
  };
  // Add to global array and re-render
  students.push(newStudent);
  renderTable(students);
  // Clear form fields
  nameInput.value = '';
  idInput.value = '';
  emailInput.value = '';
  if (defaultPasswordInput) {
    defaultPasswordInput.value = '';
  }
}
/**
 * TODO: Implement the handleTableClick function.
 * This function will be an event listener on the `studentTableBody` (event delegation).
 * It should:
 * 1. Check if the clicked element (`event.target`) has the class "delete-btn".
 * 2. If it is a "delete-btn":
 * - Get the `data-id` attribute from the button.
 * - Update the global 'students' array by filtering out the student with the matching ID.
 * - Call `renderTable(students)` to update the view.
 * 3. (Optional) Check for "edit-btn" and implement edit logic.
 */
function handleTableClick(event) {
  // ... your implementation here ...
  const target = event.target;
  // Only handle buttons
  if (target.tagName.toLowerCase() !== 'button') return;
  const studentId = target.dataset.id;
  if (target.classList.contains('delete-btn')) {
    // Delete logic
    students = students.filter((student) => student.id !== studentId);
    renderTable(students);
  }
  // Edit logic
  if (target.classList.contains('edit-btn')) {
    // Simple example: prompt for a new name
    const student = students.find((s) => s.id === studentId);
    if (!student) return;
    const newName = prompt('Enter new name for this student:', student.name);
    if (newName && newName.trim() !== '') {
      student.name = newName.trim();
      renderTable(students);
    }
  }
}
/**
 * TODO: Implement the handleSearch function.
 * This function will be called on the "input" event of the `searchInput`.
 * It should:
 * 1. Get the search term from `searchInput.value` and convert it to lowercase.
 * 2. If the search term is empty, call `renderTable(students)` to show all students.
 * 3. If the search term is not empty:
 * - Filter the global 'students' array to find students whose name (lowercase)
 * includes the search term.
 * - Call `renderTable` with the *filtered array*.
 */
function handleSearch(event) {
  // ... your implementation here ...
  const target = event.target;
  // Only handle buttons
  if (target.tagName.toLowerCase() !== 'button') return;
  const studentId = target.dataset.id;
  if (target.classList.contains('delete-btn')) {
    // Delete logic
    students = students.filter((student) => student.id !== studentId);
    renderTable(students);
  }
  // Edit logic
  if (target.classList.contains('edit-btn')) {
    // Simple example: prompt for a new name
    const student = students.find((s) => s.id === studentId);
    if (!student) return;
    const newName = prompt('Enter new name for this student:', student.name);
    if (newName && newName.trim() !== '') {
      student.name = newName.trim();
      renderTable(students);
    }
  }
}

/**
 * TODO: Implement the handleSort function.
 * This function will be called when any `th` in the `thead` is clicked.
 * It should:
 * 1. Identify which column was clicked (e.g., `event.currentTarget.cellIndex`).
 * 2. Determine the property to sort by ('name', 'id', 'email') based on the index.
 * 3. Determine the sort direction. Use a data-attribute (e.g., `data-sort-dir="asc"`) on the `th`
 * to track the current direction. Toggle between "asc" and "desc".
 * 4. Sort the global 'students' array *in place* using `array.sort()`.
 * - For 'name' and 'email', use `localeCompare` for string comparison.
 * - For 'id', compare the values as numbers.
 * 5. Respect the sort direction (ascending or descending).
 * 6. After sorting, call `renderTable(students)` to update the view.
 */
function handleSort(event) {
  // ... your implementation here ...
  const th = event.currentTarget;
  const columnIndex = th.cellIndex;
  // We only sort by Name (0), ID (1), Email (2)
  let property;
  if (columnIndex === 0) {
    property = 'name';
  } else if (columnIndex === 1) {
    property = 'id';
  } else if (columnIndex === 2) {
    property = 'email';
  } else {
    return; // "Actions" column or something else
  }
  // Toggle sort direction
  const currentDir = th.dataset.sortDir || 'asc';
  const newDir = currentDir === 'asc' ? 'desc' : 'asc';
  th.dataset.sortDir = newDir;
  // Clear sort direction on other headers
  tableHeaders.forEach((header) => {
    if (header !== th) {
      delete header.dataset.sortDir;
    }
  });
  // Sort in place
  students.sort((a, b) => {
    let cmp;
    if (property === 'id') {
      const numA = parseFloat(a.id);
      const numB = parseFloat(b.id);
      if (!isNaN(numA) && !isNaN(numB)) {
        cmp = numA - numB;
      } else {
        cmp = a.id.localeCompare(b.id);
      }
    } else {
      cmp = a[property].localeCompare(b[property]);
    }
    return newDir === 'asc' ? cmp : -cmp;
  });
  renderTable(students);
}

/**
 * TODO: Implement the loadStudentsAndInitialize function.
 * This function needs to be 'async'.
 * It should:
 * 1. Use the `fetch()` API to get data from 'students.json'.
 * 2. Check if the response is 'ok'. If not, log an error.
 * 3. Parse the JSON response (e.g., `await response.json()`).
 * 4. Assign the resulting array to the global 'students' variable.
 * 5. Call `renderTable(students)` to populate the table for the first time.
 * 6. After data is loaded, set up all the event listeners:
 * - "submit" on `changePasswordForm` -> `handleChangePassword`
 * - "submit" on `addStudentForm` -> `handleAddStudent`
 * - "click" on `studentTableBody` -> `handleTableClick`
 * - "input" on `searchInput` -> `handleSearch`
 * - "click" on each header in `tableHeaders` -> `handleSort`
 */
async function loadStudentsAndInitialize() {
  // ... your implementation here ...
  try {
    const response = await fetch('students.json');
    if (!response.ok) {
      console.error('Failed to load students.json:', response.status);
    } else {
      const data = await response.json();
      if (Array.isArray(data)) {
        students = data;
      } else {
        console.error('students.json is not an array');
        students = [];
      }
    }
  } catch (error) {
    console.error('Error fetching students.json:', error);
    students = [];
  }
  // Initial table render
  renderTable(students);
  // Event listeners
  if (changePasswordForm) {
    changePasswordForm.addEventListener('submit', handleChangePassword);
  }
  if (addStudentForm) {
    addStudentForm.addEventListener('submit', handleAddStudent);
  }
  if (studentTableBody) {
    studentTableBody.addEventListener('click', handleTableClick);
  }
  if (searchInput) {
    searchInput.addEventListener('input', handleSearch);
  }
  tableHeaders.forEach((th) => {
    th.addEventListener('click', handleSort);
  });
}

// --- Initial Page Load ---
// Call the main async function to start the application.
loadStudentsAndInitialize();
