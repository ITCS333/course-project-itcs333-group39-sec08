/*
  Client-side validation for login form
  Clean, modular implementation
*/

class LoginValidator {
  // Static configuration
  static config = {
    emailRegex: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
    minPasswordLength: 8,
    messageTimeout: 3000,
    animationDuration: 500
  };

  // DOM elements
  elements = {
    form: null,
    email: null,
    password: null,
    messageContainer: null,
    submitButton: null,
    buttonText: null,
    buttonSpinner: null
  };

  // State tracking
  state = {
    isValid: false,
    isSubmitting: false,
    lastValidation: null
  };

  constructor() {
    this.initializeElements();
    this.bindEvents();
    this.updateButtonState();
  }

  /**
   * Initialize DOM elements
   */
  initializeElements() {
    this.elements = {
      form: document.getElementById('login-form'),
      email: document.getElementById('email'),
      password: document.getElementById('password'),
      messageContainer: document.getElementById('message-container'),
      submitButton: document.getElementById('login-btn'),
      buttonText: document.getElementById('btn-text'),
      buttonSpinner: document.getElementById('btn-spinner')
    };

    if (!this.elements.form) {
      console.error('Login form not found');
      return;
    }
  }

  /**
   * Bind event listeners
   */
  bindEvents() {
    if (!this.elements.form) return;

    // Form submission
    this.elements.form.addEventListener('submit', this.handleSubmit.bind(this));

    // Real-time validation
    this.elements.email.addEventListener('input', this.validateEmail.bind(this));
    this.elements.password.addEventListener('input', this.validatePassword.bind(this));

    // Blur validation for final check
    this.elements.email.addEventListener('blur', this.validateEmailFinal.bind(this));
    this.elements.password.addEventListener('blur', this.validatePasswordFinal.bind(this));

    // Clear validation on focus
    this.elements.email.addEventListener('focus', this.clearFieldValidation.bind(this, 'email'));
    this.elements.password.addEventListener('focus', this.clearFieldValidation.bind(this, 'password'));
  }

  /**
   * Validate email format
   */
  validateEmail() {
    const email = this.elements.email.value.trim();
    const isValid = LoginValidator.config.emailRegex.test(email);
    
    this.updateFieldValidation('email', isValid, 
      isValid ? '' : 'Invalid email format'
    );
    
    this.checkFormValidity();
    return isValid;
  }

  /**
   * Final email validation on blur
   */
  validateEmailFinal() {
    const email = this.elements.email.value.trim();
    const isValid = email ? LoginValidator.config.emailRegex.test(email) : false;
    
    this.updateFieldValidation('email', isValid, 
      !email ? 'Email is required' : 
      !isValid ? 'Invalid email format' : ''
    );
  }

  /**
   * Validate password length
   */
  validatePassword() {
    const password = this.elements.password.value.trim();
    const isValid = password.length >= LoginValidator.config.minPasswordLength;
    
    this.updateFieldValidation('password', isValid,
      isValid ? '' : `Password must be at least ${LoginValidator.config.minPasswordLength} characters`
    );
    
    this.checkFormValidity();
    return isValid;
  }

  /**
   * Final password validation on blur
   */
  validatePasswordFinal() {
    const password = this.elements.password.value.trim();
    const isValid = password.length >= LoginValidator.config.minPasswordLength;
    
    this.updateFieldValidation('password', isValid,
      !password ? 'Password is required' :
      !isValid ? `Password must be at least ${LoginValidator.config.minPasswordLength} characters` : ''
    );
  }

  /**
   * Update field validation state
   */
  updateFieldValidation(field, isValid, message = '') {
    const input = this.elements[field];
    const feedback = input.nextElementSibling;
    
    input.classList.remove('is-valid', 'is-invalid');
    input.classList.add(isValid ? 'is-valid' : 'is-invalid');
    
    if (feedback && feedback.classList.contains('invalid-feedback')) {
      if (message) {
        feedback.textContent = message;
        feedback.style.display = 'block';
      } else {
        feedback.style.display = 'none';
      }
    }
  }

  /**
   * Clear field validation styling
   */
  clearFieldValidation(field) {
    const input = this.elements[field];
    const feedback = input.nextElementSibling;
    
    input.classList.remove('is-valid', 'is-invalid');
    
    if (feedback && feedback.classList.contains('invalid-feedback')) {
      feedback.style.display = 'none';
    }
  }

  /**
   * Check overall form validity
   */
  checkFormValidity() {
    const emailValid = LoginValidator.config.emailRegex.test(this.elements.email.value.trim());
    const passwordValid = this.elements.password.value.trim().length >= LoginValidator.config.minPasswordLength;
    
    this.state.isValid = emailValid && passwordValid;
    this.updateButtonState();
    
    return this.state.isValid;
  }

  /**
   * Update submit button state
   */
  updateButtonState() {
    if (!this.elements.submitButton) return;
    
    this.elements.submitButton.disabled = !this.state.isValid || this.state.isSubmitting;
  }

  /**
   * Display message to user
   */
  displayMessage(message, type = 'error') {
    if (!this.elements.messageContainer) return;

    const container = this.elements.messageContainer;
    
    // Set message and styling
    container.textContent = message;
    container.className = `alert alert-${type === 'success' ? 'success' : 'danger'} d-block`;
    container.setAttribute('role', 'alert');
    
    // Auto-hide success messages
    if (type === 'success') {
      setTimeout(() => {
        container.classList.remove('d-block');
        container.classList.add('d-none');
      }, LoginValidator.config.messageTimeout);
    }
    
    // Add animation
    container.classList.add('fade-in');
    setTimeout(() => container.classList.remove('fade-in'), 300);
  }

  /**
   * Add shake animation to form
   */
  addShakeAnimation() {
    this.elements.form.classList.add('shake');
    setTimeout(() => {
      this.elements.form.classList.remove('shake');
    }, LoginValidator.config.animationDuration);
  }

  /**
   * Show loading state
   */
  showLoading() {
    this.state.isSubmitting = true;
    
    if (this.elements.buttonText && this.elements.buttonSpinner) {
      this.elements.buttonText.textContent = 'Signing in...';
      this.elements.buttonSpinner.classList.remove('d-none');
    }
    
    this.updateButtonState();
  }

  /**
   * Hide loading state
   */
  hideLoading() {
    this.state.isSubmitting = false;
    
    if (this.elements.buttonText && this.elements.buttonSpinner) {
      this.elements.buttonText.textContent = 'Sign In';
      this.elements.buttonSpinner.classList.add('d-none');
    }
    
    this.updateButtonState();
  }

  /**
   * Handle form submission
   */
  async handleSubmit(event) {
    event.preventDefault();
    
    // Final validation
    if (!this.checkFormValidity()) {
      this.displayMessage('Please fix the errors above before submitting.', 'error');
      this.addShakeAnimation();
      return;
    }
    
    // Show loading state
    this.showLoading();
    
    try {
      // Prepare form data
      const formData = {
        email: this.elements.email.value.trim(),
        password: this.elements.password.value.trim()
      };
      
      // Simulate API call (replace with actual API call)
      await this.simulateApiCall(formData);
      
      // Success handling
      this.displayMessage('Login successful! Redirecting...', 'success');
      
      // Simulate redirect (replace with actual redirect)
      setTimeout(() => {
        window.location.href = '/dashboard.html';
      }, 1500);
      
    } catch (error) {
      // Error handling
      this.displayMessage(error.message || 'Login failed. Please try again.', 'error');
      this.addShakeAnimation();
      this.hideLoading();
    }
  }

  /**
   * Simulate API call (replace with actual API integration)
   */
  async simulateApiCall(formData) {
    return new Promise((resolve, reject) => {
      setTimeout(() => {
        // Simulate 80% success rate for demo
        const isSuccess = Math.random() < 0.8;
        
        if (isSuccess) {
          resolve({
            success: true,
            message: 'Login successful',
            user: { email: formData.email }
          });
        } else {
          reject(new Error('Invalid email or password'));
        }
      }, 1500);
    });
  }

  /**
   * Real API integration example
   */
  async callLoginApi(formData) {
    try {
      const response = await fetch('/auth/login.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(formData)
      });
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      
      const data = await response.json();
      
      if (!data.success) {
        throw new Error(data.message || 'Login failed');
      }
      
      return data;
      
    } catch (error) {
      console.error('API Error:', error);
      throw error;
    }
  }

  /**
   * Public validation methods for testing
   */
  static validateEmail(email) {
    return LoginValidator.config.emailRegex.test(email);
  }

  static validatePassword(password) {
    return password.length >= LoginValidator.config.minPasswordLength;
  }
}

// Initialize the validator when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
  const validator = new LoginValidator();
  
  // Expose for testing if needed
  window.loginValidator = validator;
});

// Export for testing (if using modules)
// export { LoginValidator };
