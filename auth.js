function showLogin() {
    document.getElementById('login-form').classList.add('active');
    document.getElementById('signup-form').classList.remove('active');
    document.getElementById('login-toggle').classList.add('active');
    document.getElementById('signup-toggle').classList.remove('active');
}

function showSignup() {
    document.getElementById('signup-form').classList.add('active');
    document.getElementById('login-form').classList.remove('active');
    document.getElementById('signup-toggle').classList.add('active');
    document.getElementById('login-toggle').classList.remove('active');
}

// Attach toggle button event listeners
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('login-toggle')?.addEventListener('click', showLogin);
    document.getElementById('signup-toggle')?.addEventListener('click', showSignup);
    
    // Add real-time validation to LOGIN form
    const loginEmail = document.getElementById('login-email');
    const loginPassword = document.getElementById('login-password');
    
    if (loginEmail) {
        loginEmail.addEventListener('input', validateLoginEmail);
        loginEmail.addEventListener('blur', validateLoginEmail);
    }
    
    if (loginPassword) {
        loginPassword.addEventListener('input', validateLoginPassword);
        loginPassword.addEventListener('blur', validateLoginPassword);
    }
});

// ============ LOGIN FORM VALIDATION ============

function validateLoginEmail() {
    const input = document.getElementById('login-email');
    const value = input.value.trim();
    
    if (value.length === 0) {
        input.classList.remove('valid', 'invalid');
        return;
    }
    
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (emailRegex.test(value)) {
        input.classList.add('valid');
        input.classList.remove('invalid');
    } else {
        input.classList.add('invalid');
        input.classList.remove('valid');
    }
}

function validateLoginPassword() {
    const input = document.getElementById('login-password');
    const value = input.value;
    
    if (value.length === 0) {
        input.classList.remove('valid', 'invalid');
        return;
    }
    
    if (value.length >= 6) {
        input.classList.add('valid');
        input.classList.remove('invalid');
    } else {
        input.classList.add('invalid');
        input.classList.remove('valid');
    }
}

function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    input.type = input.type === 'password' ? 'text' : 'password';
}

// ============ VALIDATION STATE ============

const validationState = {
    fullname: false,
    email: false,
    password: false,
    confirmPassword: false
};

// ============ REAL-TIME VALIDATION - FULL NAME ============

let fullnameTimeout;
document.getElementById('signup-fullname')?.addEventListener('input', function() {
    clearTimeout(fullnameTimeout);
    fullnameTimeout = setTimeout(() => validateFullName(), 300);
});

document.getElementById('signup-fullname')?.addEventListener('blur', validateFullName);

function validateFullName() {
    const input = document.getElementById('signup-fullname');
    const value = input.value.trim();
    const errorEl = document.getElementById('signup-fullname-error');
    const successEl = document.getElementById('signup-fullname-success');
    const icon = document.getElementById('fullname-icon');
    
    // Clear previous messages
    errorEl.textContent = '';
    successEl.textContent = '';
    icon.textContent = '';
    
    if (value.length === 0) {
        input.classList.remove('valid', 'invalid');
        validationState.fullname = false;
        updateSignupButton();
        return;
    }
    
    // Validation rules
    if (value.length < 3) {
        showError(input, errorEl, icon, 'Name must be at least 3 characters');
        validationState.fullname = false;
    } else if (!/^[a-zA-Z\s]+$/.test(value)) {
        showError(input, errorEl, icon, 'Name can only contain letters and spaces');
        validationState.fullname = false;
    } else {
        showSuccess(input, successEl, icon, '✓ Valid name');
        validationState.fullname = true;
    }
    
    updateSignupButton();
}

// ============ REAL-TIME VALIDATION - EMAIL ============

let emailTimeout;
document.getElementById('signup-email')?.addEventListener('input', function() {
    clearTimeout(emailTimeout);
    emailTimeout = setTimeout(() => validateEmail(), 500);
});

document.getElementById('signup-email')?.addEventListener('blur', validateEmail);

async function validateEmail() {
    const input = document.getElementById('signup-email');
    const value = input.value.trim();
    const errorEl = document.getElementById('signup-email-error');
    const successEl = document.getElementById('signup-email-success');
    const icon = document.getElementById('email-icon');
    
    errorEl.textContent = '';
    successEl.textContent = '';
    icon.textContent = '';
    
    if (value.length === 0) {
        input.classList.remove('valid', 'invalid');
        validationState.email = false;
        updateSignupButton();
        return;
    }
    
    // Email format validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(value)) {
        showError(input, errorEl, icon, 'Invalid email format');
        validationState.email = false;
        updateSignupButton();
        return;
    }
    
    // Check availability via AJAX
    icon.textContent = '⏳';
    try {
        const response = await fetch(`auth.php?action=check_email&email=${encodeURIComponent(value)}`, {
            credentials: 'same-origin'
        });
        const data = await response.json();
        
        if (data.available) {
            showSuccess(input, successEl, icon, '✓ Email is available');
            validationState.email = true;
        } else {
            showError(input, errorEl, icon, 'Email already exists');
            validationState.email = false;
        }
    } catch (error) {
        showError(input, errorEl, icon, 'Could not verify email');
        validationState.email = false;
    }
    
    updateSignupButton();
}

// ============ REAL-TIME VALIDATION - PASSWORD ============

document.getElementById('signup-password')?.addEventListener('input', validatePassword);
document.getElementById('signup-password')?.addEventListener('blur', validatePassword);

function validatePassword() {
    const input = document.getElementById('signup-password');
    if (!input) {
        console.error('signup-password input not found');
        return;
    }
    const value = input.value;
    const errorEl = document.getElementById('signup-password-error');
    
    errorEl.textContent = '';
    
    if (value.length === 0) {
        input.classList.remove('valid', 'invalid');
        resetPasswordStrength();
        validationState.password = false;
        updateSignupButton();
        return;
    }
    
    // Check requirements
    const requirements = {
        length: value.length >= 8,
        uppercase: /[A-Z]/.test(value),
        lowercase: /[a-z]/.test(value),
        number: /[0-9]/.test(value),
        special: /[!@#$%^&*(),.?":{}|<>]/.test(value)
    };
    
    // Update requirement indicators
    updateRequirement('req-length', requirements.length);
    updateRequirement('req-uppercase', requirements.uppercase);
    updateRequirement('req-lowercase', requirements.lowercase);
    updateRequirement('req-number', requirements.number);
    updateRequirement('req-special', requirements.special);
    
    // Calculate strength
    const strength = Object.values(requirements).filter(Boolean).length;
    updatePasswordStrength(strength);
    
    // Check if all requirements met
    const allValid = Object.values(requirements).every(Boolean);
    if (allValid) {
        input.classList.add('valid');
        input.classList.remove('invalid');
        validationState.password = true;
    } else {
        input.classList.add('invalid');
        input.classList.remove('valid');
        validationState.password = false;
    }
    
    // Also re-validate confirm password if it has a value
    if (document.getElementById('signup-confirm-password').value) {
        validateConfirmPassword();
    }
    
    updateSignupButton();
}

function updateRequirement(id, valid) {
    const el = document.getElementById(id);
    if (valid) {
        el.classList.add('met');
        el.classList.remove('unmet');
    } else {
        el.classList.add('unmet');
        el.classList.remove('met');
    }
}

function updatePasswordStrength(strength) {
    const fill = document.getElementById('strength-fill');
    const text = document.getElementById('strength-text');
    
    fill.className = 'strength-fill';
    
    if (strength <= 2) {
        fill.classList.add('weak');
        text.textContent = 'Strength: Weak';
    } else if (strength <= 4) {
        fill.classList.add('medium');
        text.textContent = 'Strength: Medium';
    } else {
        fill.classList.add('strong');
        text.textContent = 'Strength: Strong';
    }
}

function resetPasswordStrength() {
    document.getElementById('strength-fill').className = 'strength-fill';
    document.getElementById('strength-text').textContent = 'Password strength';
    
    ['req-length', 'req-uppercase', 'req-lowercase', 'req-number', 'req-special'].forEach(id => {
        const el = document.getElementById(id);
        el.classList.remove('met', 'unmet');
    });
}

// ============ REAL-TIME VALIDATION - CONFIRM PASSWORD ============

document.getElementById('signup-confirm-password')?.addEventListener('input', validateConfirmPassword);

function validateConfirmPassword() {
    const password = document.getElementById('signup-password').value;
    const confirm = document.getElementById('signup-confirm-password').value;
    const errorEl = document.getElementById('signup-confirm-error');
    const successEl = document.getElementById('signup-confirm-success');
    const icon = document.getElementById('confirm-icon');
    const input = document.getElementById('signup-confirm-password');
    
    errorEl.textContent = '';
    successEl.textContent = '';
    icon.textContent = '';
    
    if (confirm.length === 0) {
        input.classList.remove('valid', 'invalid');
        validationState.confirmPassword = false;
        updateSignupButton();
        return;
    }
    
    if (password !== confirm) {
        showError(input, errorEl, icon, 'Passwords do not match');
        validationState.confirmPassword = false;
    } else {
        showSuccess(input, successEl, icon, '✓ Passwords match');
        validationState.confirmPassword = true;
    }
    
    updateSignupButton();
}

// ============ HELPER FUNCTIONS ============

function showError(input, errorEl, icon, message) {
    input.classList.add('invalid');
    input.classList.remove('valid');
    errorEl.textContent = message;
    if (icon) icon.textContent = '✗';
}

function showSuccess(input, successEl, icon, message) {
    input.classList.add('valid');
    input.classList.remove('invalid');
    successEl.textContent = message;
    if (icon) icon.textContent = '✓';
}

function updateSignupButton() {
    const btn = document.getElementById('signup-btn');
    const allValid = Object.values(validationState).every(Boolean);
    btn.disabled = !allValid;
}

// ============ FORM SUBMISSIONS ============

// Login Form
document.getElementById('login-form')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const email = document.getElementById('login-email').value.trim();
    const password = document.getElementById('login-password').value;
    const rememberMe = document.getElementById('remember-me').checked;
    
    const btn = document.getElementById('login-btn');
    const btnText = document.getElementById('login-btn-text');
    const btnLoader = document.getElementById('login-btn-loader');
    const alert = document.getElementById('login-alert');
    
    // Disable button and show loader
    btn.disabled = true;
    btnText.style.display = 'none';
    btnLoader.style.display = 'inline-block';
    alert.className = 'alert';
    alert.textContent = '';
    
    try {
        const response = await fetch('auth.php?action=login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ email, password, remember_me: rememberMe })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert(alert, 'success', data.message);
            setTimeout(() => {
                window.location.href = data.redirect;
            }, 1000);
        } else {
            showAlert(alert, 'error', data.message);
            btn.disabled = false;
            btnText.style.display = 'inline';
            btnLoader.style.display = 'none';
        }
    } catch (error) {
        showAlert(alert, 'error', 'Connection error. Please try again.');
        btn.disabled = false;
        btnText.style.display = 'inline';
        btnLoader.style.display = 'none';
    }
});

// Signup Form
document.getElementById('signup-form')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const fullName = document.getElementById('signup-fullname').value.trim();
    const email = document.getElementById('signup-email').value.trim();
    const password = document.getElementById('signup-password').value;
    const confirmPassword = document.getElementById('signup-confirm-password').value;
    
    const btn = document.getElementById('signup-btn');
    const btnText = document.getElementById('signup-btn-text');
    const btnLoader = document.getElementById('signup-btn-loader');
    const alert = document.getElementById('signup-alert');
    
    // Disable button and show loader
    btn.disabled = true;
    btnText.style.display = 'none';
    btnLoader.style.display = 'inline-block';
    alert.className = 'alert';
    alert.textContent = '';
    
    try {
        const response = await fetch('auth.php?action=signup', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({
                full_name: fullName,
                email,
                password,
                confirm_password: confirmPassword
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert(alert, 'success', data.message);
            setTimeout(() => {
                window.location.href = data.redirect;
            }, 1500);
        } else {
            const errorMessage = data.errors ? data.errors.join(', ') : data.message;
            showAlert(alert, 'error', errorMessage);
            btn.disabled = false;
            btnText.style.display = 'inline';
            btnLoader.style.display = 'none';
        }
    } catch (error) {
        showAlert(alert, 'error', 'Connection error. Please try again.');
        btn.disabled = false;
        btnText.style.display = 'inline';
        btnLoader.style.display = 'none';
    }
});

function showAlert(element, type, message) {
    element.className = `alert ${type} show`;
    element.textContent = message;
}

// ============ CHECK EXISTING SESSION ============

async function checkSession() {
    try {
        const response = await fetch('auth.php?action=check_session', {
            credentials: 'same-origin'
        });
        const data = await response.json();
        
        if (data.authenticated) {
            window.location.href = 'dashboard.php';
        }
    } catch (error) {
        console.log('No existing session');
    }
}

// Check session on page load
window.addEventListener('DOMContentLoaded', checkSession);
