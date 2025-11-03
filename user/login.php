<?php
// Start output buffering to prevent header errors
ob_start();

require_once('../includes/config.php');
session_start();

// Redirect if already logged in
if (isset($_SESSION['customer_id'])) {
    ob_end_clean();
    header("Location: ../index.php");
    exit();
}
// If already logged in as admin, send to admin dashboard
if (isset($_SESSION['admin_logged_in'])) {
    ob_end_clean();
    header("Location: ../admin/manageproducts.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['login'])) {
        // Login Logic (supports Customer or Admin)
        $role = isset($_POST['role']) && $_POST['role'] === 'admin' ? 'admin' : 'customer';
        $identifier = trim($_POST['identifier']); // email for customer, username for admin
        $password = $_POST['password'];

        if (empty($identifier) || empty($password)) {
            $error = "Please fill in all fields.";
        } else {
            if ($role === 'customer') {
                $stmt = $conn->prepare("SELECT customer_id, full_name, password FROM customers WHERE email = ?");
                $stmt->bind_param("s", $identifier);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows === 1) {
                    $customer = $result->fetch_assoc();
                    // Verify password (assuming hashed with password_hash)
                    if (password_verify($password, $customer['password'])) {
                        $_SESSION['customer_id'] = $customer['customer_id'];
                        $_SESSION['customer_name'] = $customer['full_name'];
                        ob_end_clean();
                        header("Location: ../index.php");
                        exit();
                    } else {
                        $error = "Invalid email or password.";
                    }
                } else {
                    $error = "Invalid email or password.";
                }

                $stmt->close();
            } else {
                // Admin login: check users table (username + SHA-256 password)
                $stmt = $conn->prepare("SELECT user_id, username, password FROM users WHERE username = ?");
                $stmt->bind_param("s", $identifier);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows === 1) {
                    $user = $result->fetch_assoc();
                    $hashed_input = hash('sha256', $password);
                    if ($hashed_input === $user['password']) {
                        // Set admin session and redirect to admin dashboard
                        $_SESSION['admin_logged_in'] = true;
                        $_SESSION['admin_username'] = $user['username'];
                        $_SESSION['admin_id'] = $user['user_id'];
                        ob_end_clean();
                        header("Location: ../admin/manageproducts.php");
                        exit();
                    } else {
                        $error = "Invalid username or password.";
                    }
                } else {
                    $error = "Invalid username or password.";
                }

                $stmt->close();
            }
        }
    } elseif (isset($_POST['register'])) {
        // Registration Logic
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $contact_number = trim($_POST['contact_number']);
        $address = trim($_POST['address']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (empty($full_name) || empty($email) || empty($password) || empty($confirm_password)) {
            $error = "Please fill in all required fields.";
        } elseif ($password !== $confirm_password) {
            $error = "Passwords do not match.";
        } elseif (strlen($password) < 6) {
            $error = "Password must be at least 6 characters long.";
        } else {
            // Check if email already exists
            $stmt = $conn->prepare("SELECT customer_id FROM customers WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = "Email already registered.";
            } else {
                // Hash password and insert
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO customers (full_name, email, contact_number, address, password) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $full_name, $email, $contact_number, $address, $hashed_password);
                
                if ($stmt->execute()) {
                    $success = "Registration successful! You can now login.";
                } else {
                    $error = "Registration failed. Please try again.";
                }
            }
            $stmt->close();
        }
    }
}

ob_end_flush();
require_once('../includes/header.php');
?>

<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600&family=Playfair+Display:wght@400;500&display=swap" rel="stylesheet">

<section class="luxury-auth-section">
    <div class="auth-background-overlay"></div>
    
    <div class="luxury-auth-container">
        <!-- Tab Navigation -->
        <div class="luxury-auth-toggle">
            <button class="luxury-tab active" data-tab="login">
                <span>Sign In</span>
            </button>
            <button class="luxury-tab" data-tab="register">
                <span>Create Account</span>
            </button>
        </div>

        <!-- Alert Messages -->
        <?php if ($error): ?>
            <div class="luxury-alert luxury-alert-error">
                <i class="alert-icon">✕</i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="luxury-alert luxury-alert-success">
                <i class="alert-icon">✓</i>
                <span><?php echo htmlspecialchars($success); ?></span>
            </div>
        <?php endif; ?>

        <!-- Login Form -->
        <div class="luxury-form-wrapper active" id="login-form">
            <div class="form-header">
                <h2 class="luxury-auth-title">Welcome Back</h2>
                <p class="luxury-auth-subtitle">Sign in to continue your journey</p>
            </div>
            
            <form method="POST" action="" class="luxury-auth-form">
                <!-- Role Selection -->
                <div class="luxury-form-group role-selection">
                    <label class="luxury-label">Login As</label>
                    <div class="role-options">
                        <label class="role-option">
                            <input type="radio" name="role" value="customer" checked>
                            <span class="role-box">
                                <i class="role-icon">👤</i>
                                <span class="role-text">Customer</span>
                            </span>
                        </label>
                        <label class="role-option">
                            <input type="radio" name="role" value="admin">
                            <span class="role-box">
                                <i class="role-icon">🔐</i>
                                <span class="role-text">Admin</span>
                            </span>
                        </label>
                    </div>
                </div>

                <!-- Identifier Field -->
                <div class="luxury-form-group">
                    <label for="login-identifier" class="luxury-label" id="identifier-label">Email Address</label>
                    <input type="text" id="login-identifier" name="identifier" class="luxury-input" required placeholder="Enter your email">
                </div>
                
                <!-- Password Field -->
                <div class="luxury-form-group">
                    <label for="login-password" class="luxury-label">Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="login-password" name="password" class="luxury-input" required placeholder="Enter your password">
                        <button type="button" class="password-toggle" onclick="toggleLoginPassword()">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        </button>
                    </div>
                </div>
                
                <button type="submit" name="login" class="luxury-btn luxury-btn-primary">
                    <span>Sign In</span>
                </button>
            </form>
        </div>

        <!-- Register Form -->
        <div class="luxury-form-wrapper" id="register-form">
            <div class="form-header">
                <h2 class="luxury-auth-title">Create Account</h2>
                <p class="luxury-auth-subtitle">Join Prestige Perfumery</p>
            </div>
            
            <form method="POST" action="" class="luxury-auth-form">
                <div class="luxury-form-group">
                    <label for="register-name" class="luxury-label">Full Name <span class="required">*</span></label>
                    <input type="text" id="register-name" name="full_name" class="luxury-input" required placeholder="John Doe">
                </div>
                
                <div class="luxury-form-group">
                    <label for="register-email" class="luxury-label">Email Address <span class="required">*</span></label>
                    <input type="email" id="register-email" name="email" class="luxury-input" required placeholder="john@example.com">
                </div>
                
                <div class="luxury-form-group">
                    <label for="register-contact" class="luxury-label">Contact Number</label>
                    <input type="tel" id="register-contact" name="contact_number" class="luxury-input" placeholder="09XX XXX XXXX">
                </div>
                
                <div class="luxury-form-group">
                    <label for="register-address" class="luxury-label">Address</label>
                    <textarea id="register-address" name="address" rows="3" class="luxury-input luxury-textarea" placeholder="Your delivery address"></textarea>
                </div>
                
                <div class="luxury-form-group">
                    <label for="register-password" class="luxury-label">Password <span class="required">*</span></label>
                    <div class="password-wrapper">
                        <input type="password" id="register-password" name="password" class="luxury-input" required placeholder="Minimum 6 characters">
                        <button type="button" class="password-toggle" onclick="toggleRegisterPassword()">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        </button>
                    </div>
                </div>
                
                <div class="luxury-form-group">
                    <label for="register-confirm" class="luxury-label">Confirm Password <span class="required">*</span></label>
                    <div class="password-wrapper">
                        <input type="password" id="register-confirm" name="confirm_password" class="luxury-input" required placeholder="Re-enter password">
                        <button type="button" class="password-toggle" onclick="toggleConfirmPassword()">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        </button>
                    </div>
                </div>
                
                <button type="submit" name="register" class="luxury-btn luxury-btn-primary">
                    <span>Create Account</span>
                </button>
            </form>
        </div>
    </div>
</section>

<style>
/* ========================================
   LUXURY AUTHENTICATION SECTION
======================================== */

.luxury-auth-section {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #0a0a0a 0%, #1a1a1a 100%);
    padding: 140px 20px 80px;
    position: relative;
    overflow: hidden;
}

.auth-background-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: 
        radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.03) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(255, 255, 255, 0.02) 0%, transparent 50%);
    pointer-events: none;
}

.luxury-auth-container {
    max-width: 550px;
    width: 100%;
    background: #ffffff;
    border: 1px solid rgba(0, 0, 0, 0.08);
    position: relative;
    z-index: 1;
}

/* ===== TAB NAVIGATION ===== */
.luxury-auth-toggle {
    display: flex;
    border-bottom: 1px solid rgba(0, 0, 0, 0.08);
}

.luxury-tab {
    flex: 1;
    padding: 25px 20px;
    background: transparent;
    border: none;
    color: rgba(0, 0, 0, 0.4);
    font-size: 11px;
    letter-spacing: 2.5px;
    text-transform: uppercase;
    cursor: pointer;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    font-weight: 500;
    font-family: 'Montserrat', sans-serif;
    position: relative;
}

.luxury-tab::before {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 0;
    height: 2px;
    background: #0a0a0a;
    transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.luxury-tab.active {
    color: #0a0a0a;
    background: #fafafa;
}

.luxury-tab.active::before {
    width: 100%;
}

.luxury-tab:hover {
    color: #0a0a0a;
}

/* ===== ALERTS ===== */
.luxury-alert {
    padding: 18px 25px;
    margin: 25px 40px 0;
    border: 1px solid;
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 13px;
    letter-spacing: 0.3px;
    animation: slideDown 0.4s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.luxury-alert .alert-icon {
    font-size: 16px;
    font-weight: 600;
}

.luxury-alert-error {
    background: #fef5f5;
    border-color: rgba(176, 42, 55, 0.2);
    color: #b02a37;
}

.luxury-alert-success {
    background: #f0f8f4;
    border-color: rgba(21, 87, 36, 0.2);
    color: #155724;
}

/* ===== FORM WRAPPER ===== */
.luxury-form-wrapper {
    display: none;
    padding: 50px 40px;
    animation: fadeInForm 0.5s ease;
}

.luxury-form-wrapper.active {
    display: block;
}

@keyframes fadeInForm {
    from {
        opacity: 0;
        transform: translateY(15px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.form-header {
    margin-bottom: 40px;
    text-align: center;
}

.luxury-auth-title {
    font-family: 'Playfair Display', serif;
    font-size: 36px;
    font-weight: 400;
    letter-spacing: 0.5px;
    margin-bottom: 12px;
    color: #0a0a0a;
}

.luxury-auth-subtitle {
    font-size: 13px;
    color: rgba(0, 0, 0, 0.5);
    letter-spacing: 1px;
    font-weight: 300;
}

/* ===== FORM ELEMENTS ===== */
.luxury-auth-form {
    display: flex;
    flex-direction: column;
    gap: 25px;
}

.luxury-form-group {
    display: flex;
    flex-direction: column;
}

.luxury-label {
    font-size: 10px;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: #0a0a0a;
    margin-bottom: 10px;
    font-weight: 600;
    font-family: 'Montserrat', sans-serif;
}

.required {
    color: #b02a37;
}

.luxury-input {
    padding: 16px 20px;
    border: 1px solid rgba(0, 0, 0, 0.15);
    background: #fafafa;
    font-size: 14px;
    letter-spacing: 0.3px;
    transition: all 0.3s ease;
    font-weight: 300;
    font-family: 'Lato', sans-serif;
    color: #0a0a0a;
}

.luxury-input:focus {
    outline: none;
    border-color: #0a0a0a;
    background: #ffffff;
}

.luxury-input::placeholder {
    color: rgba(0, 0, 0, 0.3);
}

.luxury-textarea {
    resize: vertical;
    min-height: 90px;
}

/* ===== PASSWORD WRAPPER ===== */
.password-wrapper {
    position: relative;
}

.password-toggle {
    position: absolute;
    right: 18px;
    top: 50%;
    transform: translateY(-50%);
    background: transparent;
    border: none;
    color: rgba(0, 0, 0, 0.4);
    cursor: pointer;
    padding: 8px;
    transition: color 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.password-toggle:hover {
    color: #0a0a0a;
}

/* ===== ROLE SELECTION ===== */
.role-selection .luxury-label {
    margin-bottom: 15px;
}

.role-options {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.role-option {
    cursor: pointer;
}

.role-option input[type="radio"] {
    display: none;
}

.role-box {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 20px 15px;
    border: 1px solid rgba(0, 0, 0, 0.15);
    background: #fafafa;
    transition: all 0.3s ease;
}

.role-option input[type="radio"]:checked + .role-box {
    border-color: #0a0a0a;
    background: #ffffff;
}

.role-icon {
    font-size: 24px;
}

.role-text {
    font-size: 11px;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    color: rgba(0, 0, 0, 0.6);
    font-weight: 500;
    font-family: 'Montserrat', sans-serif;
}

.role-option input[type="radio"]:checked + .role-box .role-text {
    color: #0a0a0a;
}

.role-box:hover {
    border-color: #0a0a0a;
}

/* ===== BUTTON ===== */
.luxury-btn {
    padding: 18px 40px;
    border: 1px solid rgba(0, 0, 0, 0.2);
    background: transparent;
    color: #0a0a0a;
    font-size: 11px;
    letter-spacing: 3px;
    text-transform: uppercase;
    cursor: pointer;
    transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    font-weight: 500;
    font-family: 'Montserrat', sans-serif;
    margin-top: 10px;
    position: relative;
    overflow: hidden;
}

.luxury-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: #0a0a0a;
    transition: left 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 0;
}

.luxury-btn:hover {
    color: #ffffff;
    border-color: #0a0a0a;
}

.luxury-btn:hover::before {
    left: 0;
}

.luxury-btn span {
    position: relative;
    z-index: 1;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 768px) {
    .luxury-auth-section {
        padding: 120px 20px 60px;
    }

    .luxury-auth-container {
        max-width: 100%;
    }

    .luxury-form-wrapper {
        padding: 40px 30px;
    }

    .luxury-auth-title {
        font-size: 28px;
    }

    .luxury-auth-subtitle {
        font-size: 12px;
    }

    .luxury-tab {
        font-size: 10px;
        padding: 20px 15px;
    }

    .luxury-alert {
        margin: 20px 25px 0;
        padding: 15px 20px;
    }
}

@media (max-width: 480px) {
    .luxury-form-wrapper {
        padding: 35px 25px;
    }

    .luxury-auth-title {
        font-size: 24px;
    }

    .role-options {
        grid-template-columns: 1fr;
    }

    .luxury-alert {
        margin: 15px 20px 0;
    }
}
</style>

<script>
// Tab switching
document.querySelectorAll('.luxury-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        const targetTab = this.getAttribute('data-tab');
        
        // Update active tab
        document.querySelectorAll('.luxury-tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        
        // Show corresponding form
        document.querySelectorAll('.luxury-form-wrapper').forEach(form => {
            form.classList.remove('active');
        });
        document.getElementById(targetTab + '-form').classList.add('active');
    });
});

// Toggle identifier label between Email and Username when selecting role
function updateIdentifierLabel() {
    const role = document.querySelector('input[name="role"]:checked').value;
    const label = document.getElementById('identifier-label');
    const input = document.getElementById('login-identifier');

    if (role === 'admin') {
        label.textContent = 'Username';
        input.type = 'text';
        input.placeholder = 'Enter admin username';
    } else {
        label.textContent = 'Email Address';
        input.type = 'email';
        input.placeholder = 'Enter your email';
    }
}

document.querySelectorAll('input[name="role"]').forEach(r => {
    r.addEventListener('change', updateIdentifierLabel);
});

// Initialize label on page load
updateIdentifierLabel();

// Password toggle functions
function toggleLoginPassword() {
    const input = document.getElementById('login-password');
    input.type = input.type === 'password' ? 'text' : 'password';
}

function toggleRegisterPassword() {
    const input = document.getElementById('register-password');
    input.type = input.type === 'password' ? 'text' : 'password';
}

function toggleConfirmPassword() {
    const input = document.getElementById('register-confirm');
    input.type = input.type === 'password' ? 'text' : 'password';
}

// Auto-dismiss alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.luxury-alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
});
</script>

<?php
require_once('../includes/footer.php');
?>