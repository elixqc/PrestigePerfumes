<?php
// user/edit_profile.php
session_start();
require_once('../includes/config.php');
require_once('../includes/header.php');

if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit;
}

$customer_id = (int) $_SESSION['customer_id'];
$error = '';
$success = '';

// Handle POST (update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if ($full_name === '' || $email === '') {
        $error = "Full name and email are required.";
    } else {
        // Check email uniqueness (if changed)
        $check = $conn->prepare("SELECT customer_id FROM customers WHERE email = ? AND customer_id <> ?");
        $check->bind_param("si", $email, $customer_id);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $error = "Email is already in use by another account.";
        }
        $check->close();
    }

    if ($error === '') {
        $up = $conn->prepare("UPDATE customers SET full_name = ?, email = ?, contact_number = ?, address = ? WHERE customer_id = ?");
        if (!$up) {
            $error = "Database error: " . $conn->error;
        } else {
            $up->bind_param("ssssi", $full_name, $email, $contact_number, $address, $customer_id);
            if ($up->execute()) {
                $success = "Profile updated successfully.";
                // Update session display name if you store it
                $_SESSION['customer_name'] = $full_name;
                // redirect back to profile after save
                header("Location: index.php");
                exit;
            } else {
                $error = "Unable to update profile. Please try again.";
            }
            $up->close();
        }
    }
}

// Load current values for the form (GET or after failed POST)
$stmt = $conn->prepare("SELECT full_name, email, contact_number, address FROM customers WHERE customer_id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$current = $stmt->get_result()->fetch_assoc();
$stmt->close();

?>

<section class="auth-section">
    <div class="auth-container">
        <h2 class="auth-title">Edit Profile</h2>
        <p class="auth-subtitle">Update your personal details below.</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="POST" class="auth-form" novalidate>
            <div class="form-group">
                <label for="full_name">Full Name *</label>
                <input id="full_name" name="full_name" type="text" value="<?php echo htmlspecialchars($current['full_name'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email Address *</label>
                <input id="email" name="email" type="email" value="<?php echo htmlspecialchars($current['email'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="contact_number">Contact Number</label>
                <input id="contact_number" name="contact_number" type="text" value="<?php echo htmlspecialchars($current['contact_number'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="address">Address</label>
                <textarea id="address" name="address" rows="3"><?php echo htmlspecialchars($current['address'] ?? ''); ?></textarea>
            </div>

            <div style="display:flex; gap:12px; align-items:center; margin-top:12px;">
                <button type="submit" class="btn btn-auth">Save Changes</button>
                <a href="index.php" class="btn btn-primary" style="background:#fff; color:#0a0a0a; border:1px solid rgba(0,0,0,0.08);">Cancel</a>
            </div>
        </form>
    </div>
</section>

<?php require_once('../includes/footer.php'); ?>
