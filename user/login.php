<?php
require_once('../includes/config.php');
session_start();

// Redirect if already logged in
if (isset($_SESSION['customer_id'])) {
    header("Location: ../index.php");
    exit();
}
// If already logged in as admin, send to admin dashboard
if (isset($_SESSION['admin_logged_in'])) {
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

require_once('../includes/header.php');
?>

<section class="auth-section">
    <div class="auth-container">
        <div class="auth-toggle">
            <button class="auth-tab active" data-tab="login">Login</button>
            <button class="auth-tab" data-tab="register">Register</button>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <!-- Login Form -->
        <div class="auth-form-container active" id="login-form">
            <h2 class="auth-title">Welcome Back</h2>
            <p class="auth-subtitle">Sign in to your account</p>
            
            <form method="POST" action="" class="auth-form">
                <div class="form-group">
                    <label>Login as</label>
                    <div style="display:flex;gap:1rem;align-items:center;">
                        <label><input type="radio" name="role" value="customer" checked> Customer</label>
                        <label><input type="radio" name="role" value="admin"> Admin</label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="login-identifier" id="identifier-label">Email Address</label>
                    <input type="text" id="login-identifier" name="identifier" required>
                </div>
                
                <div class="form-group">
                    <label for="login-password">Password</label>
                    <input type="password" id="login-password" name="password" required>
                </div>
                
                <button type="submit" name="login" class="btn btn-auth">Sign In</button>
            </form>
        </div>

        <!-- Register Form -->
        <div class="auth-form-container" id="register-form">
            <h2 class="auth-title">Create Account</h2>
            <p class="auth-subtitle">Join Prestige Perfumery</p>
            
            <form method="POST" action="" class="auth-form">
                <div class="form-group">
                    <label for="register-name">Full Name *</label>
                    <input type="text" id="register-name" name="full_name" required>
                </div>
                
                <div class="form-group">
                    <label for="register-email">Email Address *</label>
                    <input type="email" id="register-email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="register-contact">Contact Number</label>
                    <input type="tel" id="register-contact" name="contact_number">
                </div>
                
                <div class="form-group">
                    <label for="register-address">Address</label>
                    <textarea id="register-address" name="address" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="register-password">Password *</label>
                    <input type="password" id="register-password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label for="register-confirm">Confirm Password *</label>
                    <input type="password" id="register-confirm" name="confirm_password" required>
                </div>
                
                <button type="submit" name="register" class="btn btn-auth">Create Account</button>
            </form>
        </div>
    </div>
</section>

<script>
// Tab switching
document.querySelectorAll('.auth-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        const targetTab = this.getAttribute('data-tab');
        
        // Update active tab
        document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        
        // Show corresponding form
        document.querySelectorAll('.auth-form-container').forEach(form => {
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
        input.placeholder = 'admin username';
    } else {
        label.textContent = 'Email Address';
        input.type = 'email';
        input.placeholder = 'you@example.com';
    }
}

document.querySelectorAll('input[name="role"]').forEach(r => {
    r.addEventListener('change', updateIdentifierLabel);
});

// initialize label on page load
updateIdentifierLabel();
</script>

<?php
require_once('../includes/footer.php');
?>