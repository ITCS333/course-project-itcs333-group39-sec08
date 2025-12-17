/*
  Requirement: Add client-side validation to the login form.
*/

// Create a sandbox object to make functions accessible to tests
const sandbox = {};

// --- Element Selections ---
// We can safely select elements here because 'defer' guarantees
// the HTML document is parsed before this script runs.

// TODO: Select the login form. (You'll need to add id="login-form" to the <form> in your HTML).
const loginForm = document.getElementById('login-form');

// TODO: Select the email input element by its ID.
const emailInput = document.getElementById('email');

// TODO: Select the password input element by its ID.
const passwordInput = document.getElementById('password');

// TODO: Select the message container element by its ID.
const messageContainer = document.getElementById('message-container');

// --- Functions ---

/**
 * TODO: Implement the displayMessage function.
 */
function displayMessage(message, type) {
  if (messageContainer) {
    messageContainer.textContent = message;
    messageContainer.className = type;
  }
}
// Export to sandbox
sandbox.displayMessage = displayMessage;

/**
 * TODO: Implement the isValidEmail function.
 */
function isValidEmail(email) {
  const emailRegex = /\S+@\S+\.\S+/;
  return emailRegex.test(email);
}
sandbox.isValidEmail = isValidEmail;

/**
 * TODO: Implement the isValidPassword function.
 */
function isValidPassword(password) {
  return password.length >= 8;
}
sandbox.isValidPassword = isValidPassword;

/**
 * TODO: Implement the handleLogin function.
 */
function handleLogin(event) {
  event.preventDefault();
  
  const email = emailInput.value.trim();
  const password = passwordInput.value.trim();
  
  if (!isValidEmail(email)) {
    displayMessage("Invalid email format.", "error");
    return;
  }
  
  if (!isValidPassword(password)) {
    displayMessage("Password must be at least 8 characters.", "error");
    return;
  }
  
  displayMessage("Login successful!", "success");
}
sandbox.handleLogin = handleLogin;

/**
 * TODO: Implement the setupLoginForm function.
 */
function setupLoginForm() {
  if (loginForm) {
    loginForm.addEventListener('submit', handleLogin);
  }
}
sandbox.setupLoginForm = setupLoginForm;

// --- Initial Page Load ---
// Call the main setup function to attach the event listener.
setupLoginForm();

// Also add setupLoginForm to sandbox for test access
sandbox.setupLoginForm = setupLoginForm;
