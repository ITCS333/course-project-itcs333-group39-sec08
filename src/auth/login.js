async function handleLogin(event) {
  event.preventDefault();
  
  const email = emailInput.value.trim();
  const password = passwordInput.value.trim();
  
  // Client-side validation
  if (!isValidEmail(email)) {
    displayMessage("Invalid email format.", "error");
    return;
  }
  
  if (!isValidPassword(password)) {
    displayMessage("Password must be at least 8 characters.", "error");
    return;
  }
  
  // Send to backend API
  try {
    const response = await fetch('api/index.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ email, password })
    });
    
    const data = await response.json();
    
    if (data.success) {
      displayMessage("Login successful!", "success");
    } else {
      displayMessage(data.message, "error");
    }
  } catch (error) {
    displayMessage("Network error. Please try again.", "error");
  }
}
