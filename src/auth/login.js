const loginForm = document.getElementById("login-form");
const emailInput = document.getElementById("email");
const passwordInput = document.getElementById("password");
const messageContainer = document.getElementById("message-container");

const LOGIN_API_URL = "/src/auth/api/index.php";

function displayMessage(message, type) {
  messageContainer.textContent = message;
  messageContainer.className = type;
  messageContainer.style.display = 'block';

  if (type === 'error') {
    setTimeout(() => {
      messageContainer.style.display = 'none';
    }, 5000);
  }
}

function isValidEmail(email) {
  const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return emailPattern.test(email);
}

function handleLogin(event) {
  event.preventDefault();

  const email = emailInput.value.trim();
  const password = passwordInput.value.trim();

  if (!email || !password) {
    displayMessage("Email and password are required.", "error");
    return;
  }

  if (!isValidEmail(email)) {
    displayMessage("Invalid email format.", "error");
    return;
  }

  displayMessage("Logging in...", "success");

  fetch(LOGIN_API_URL, {
    method: "POST",
    headers: { 
      "Content-Type": "application/json"
    },
    body: JSON.stringify({ 
      email: email, 
      password: password 
    })
  })
    .then(response => {
      return response.json().then(data => ({ status: response.status, data }));
    })
    .then(({ status, data }) => {
      if (data.success) {
        displayMessage("Login successful! Redirecting...", "success");

        emailInput.value = "";
        passwordInput.value = "";

        setTimeout(() => {
          if (data.redirect) {
            window.location.href = data.redirect;
          } else {
            window.location.href = "/";
          }
        }, 1000);

      } else {
        console.error("Login failed:", data);
        displayMessage(data.message || "Login failed.", "error");
      }
    })
    .catch((error) => {
      console.error('Login error:', error);
      displayMessage("An error occurred. Please try again.", "error");
    });
}

function setupLoginForm() {
  if (loginForm) {
    loginForm.addEventListener("submit", handleLogin);

    emailInput.addEventListener("keypress", function(e) {
      if (e.key === "Enter") {
        e.preventDefault();
        if (passwordInput.value) {
          handleLogin(e);
        } else {
          passwordInput.focus();
        }
      }
    });

    passwordInput.addEventListener("keypress", function(e) {
      if (e.key === "Enter") {
        e.preventDefault();
        handleLogin(e);
      }
    });
  }
}

document.addEventListener("DOMContentLoaded", function() {
  setupLoginForm();

  const urlParams = new URLSearchParams(window.location.search);
  const error = urlParams.get('error');
  if (error) {
    displayMessage(decodeURIComponent(error), "error");
  }

  const success = urlParams.get('success');
  if (success) {
    displayMessage(decodeURIComponent(success), "success");
  }
});
