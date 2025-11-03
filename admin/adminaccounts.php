<?php
// Start output buffering to prevent header errors
ob_start();

session_start();
require_once '../includes/config.php';
require_once '../includes/adminheader.php';

// Ensure user is logged in as admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Enable PDO error reporting for debugging (optional but recommended)
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Initialize message variables
$message = '';
$messageType = '';

/* ==========================
   ADD ADMIN
========================== */
if (isset($_POST['add_admin'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    if (empty($username) || empty($password) || empty($confirm_password)) {
        $message = "All fields are required.";
        $messageType = "danger";
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match.";
        $messageType = "danger";
    } elseif (strlen($password) < 6) {
        $message = "Password must be at least 6 characters long.";
        $messageType = "danger";
    } else {
        try {
            // Check for existing username
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
            $stmt->execute([$username]);

            if ($stmt->fetch()) {
                $message = "Username already exists.";
                $messageType = "danger";
            } else {
                // Hash password (SHA-256)
                $hashed_password = hash('sha256', $password);

                // Insert new admin (no role column in your database)
                $query = "INSERT INTO users (username, password) VALUES (?, ?)";

                $stmt = $pdo->prepare($query);
                if ($stmt->execute([$username, $hashed_password])) {
                    ob_end_clean(); // Clear output buffer before redirect
                    header("Location: adminaccounts.php?success=1");
                    exit();
                } else {
                    $errorInfo = $stmt->errorInfo();
                    $message = "Error creating admin account: " . $errorInfo[2];
                    $messageType = "danger";
                }
            }
        } catch (PDOException $e) {
            $message = "Database error: " . $e->getMessage();
            $messageType = "danger";
        }
    }
}

/* ==========================
   UPDATE ADMIN
========================== */
if (isset($_POST['update_admin'])) {
    $user_id = $_POST['user_id'];
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username)) {
        $message = "Username is required.";
        $messageType = "danger";
    } else {
        try {
            // Check if username exists for others
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ? AND user_id != ?");
            $stmt->execute([$username, $user_id]);

            if ($stmt->fetch()) {
                $message = "Username already exists.";
                $messageType = "danger";
            } else {
                if (!empty($password)) {
                    if (strlen($password) < 6) {
                        $message = "Password must be at least 6 characters long.";
                        $messageType = "danger";
                    } else {
                        $hashed_password = hash('sha256', $password);
                        $stmt = $pdo->prepare("UPDATE users SET username = ?, password = ? WHERE user_id = ?");
                        $stmt->execute([$username, $hashed_password, $user_id]);
                        ob_end_clean(); // Clear output buffer before redirect
                        header("Location: adminaccounts.php?updated=1");
                        exit();
                    }
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET username = ? WHERE user_id = ?");
                    $stmt->execute([$username, $user_id]);
                    ob_end_clean(); // Clear output buffer before redirect
                    header("Location: adminaccounts.php?updated=1");
                    exit();
                }
            }
        } catch (PDOException $e) {
            $message = "Database error: " . $e->getMessage();
            $messageType = "danger";
        }
    }
}

/* ==========================
   DELETE ADMIN
========================== */
if (isset($_POST['delete_admin'])) {
    $user_id = $_POST['user_id'];

    if ($user_id == $_SESSION['admin_id']) {
        $message = "You cannot delete your own account.";
        $messageType = "danger";
    } else {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) AS total FROM users");
            $result = $stmt->fetch();

            if ($result['total'] <= 1) {
                $message = "Cannot delete the last admin account.";
                $messageType = "danger";
            } else {
                $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
                $stmt->execute([$user_id]);
                ob_end_clean(); // Clear output buffer before redirect
                header("Location: adminaccounts.php?deleted=1");
                exit();
            }
        } catch (PDOException $e) {
            $message = "Database error: " . $e->getMessage();
            $messageType = "danger";
        }
    }
}

/* ==========================
   SUCCESS MESSAGES
========================== */
if (isset($_GET['success'])) {
    $message = "Admin account created successfully.";
    $messageType = "success";
} elseif (isset($_GET['updated'])) {
    $message = "Admin account updated successfully.";
    $messageType = "success";
} elseif (isset($_GET['deleted'])) {
    $message = "Admin account deleted successfully.";
    $messageType = "success";
}

/* ==========================
   DISPLAY ADMIN LIST
========================== */
$search_term = $_GET['search'] ?? '';
$sort_by = $_GET['sort'] ?? 'newest';

$query = "SELECT * FROM users WHERE 1=1";
$params = [];

if (!empty($search_term)) {
    $query .= " AND username LIKE ?";
    $params[] = "%$search_term%";
}

switch ($sort_by) {
    case 'oldest':
        $query .= " ORDER BY user_id ASC";
        break;
    case 'username_asc':
        $query .= " ORDER BY username ASC";
        break;
    case 'username_desc':
        $query .= " ORDER BY username DESC";
        break;
    default:
        $query .= " ORDER BY user_id DESC";
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$admins = $stmt->fetchAll();

$total_admins = count($admins);

// Flush output buffer at the end of processing
ob_end_flush();
?>


<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600&family=Playfair+Display:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="admin-main">
    <div class="luxury-accounts-container">
        <div class="page-header-lux">
            <div class="header-content">
                <h1 class="lux-admin-title">Admin Accounts</h1>
                <p class="lux-admin-subtitle">Manage Administrative Access</p>
            </div>
            <div class="header-actions">
                <button class="btn-lux btn-primary" onclick="openAddModal()">
                    <i class="fas fa-user-plus"></i>
                    <span>Add Admin</span>
                </button>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert-lux alert-<?php echo $messageType; ?>">
                <span class="alert-icon"><?= $messageType === 'success' ? '✓' : '✕'; ?></span>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Statistics Dashboard -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-users-cog"></i></div>
                <div class="stat-content">
                    <div class="stat-value"><?= number_format($total_admins); ?></div>
                    <div class="stat-label">Total Admins</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-user-shield"></i></div>
                <div class="stat-content">
                    <div class="stat-value">1</div>
                    <div class="stat-label">Active Session</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-shield-alt"></i></div>
                <div class="stat-content">
                    <div class="stat-value">SHA-256</div>
                    <div class="stat-label">Encryption</div>
                </div>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="filters-section">
            <form method="GET" class="filters-form">
                <div class="search-box">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" 
                           name="search" 
                           id="search" 
                           class="search-input" 
                           placeholder="Search by username..." 
                           value="<?= htmlspecialchars($search_term); ?>">
                </div>

                <div class="filter-controls">
                    <div class="filter-group">
                        <label class="filter-label">Sort By</label>
                        <select name="sort" id="sort" class="filter-select">
                            <option value="newest" <?= $sort_by === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                            <option value="oldest" <?= $sort_by === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                            <option value="username_asc" <?= $sort_by === 'username_asc' ? 'selected' : ''; ?>>Username (A-Z)</option>
                            <option value="username_desc" <?= $sort_by === 'username_desc' ? 'selected' : ''; ?>>Username (Z-A)</option>
                        </select>
                    </div>

                    <button type="submit" class="btn-lux btn-filter">
                        <i class="fas fa-filter"></i>
                        <span>Apply</span>
                    </button>
                    <a href="adminaccounts.php" class="btn-lux btn-reset">
                        <i class="fas fa-redo"></i>
                        <span>Reset</span>
                    </a>
                </div>
            </form>
        </div>

        <div class="results-info">
            <p>Showing <strong><?= count($admins); ?></strong> admin account<?= count($admins) !== 1 ? 's' : ''; ?></p>
            <?php if (!empty($search_term) || $sort_by !== 'newest'): ?>
                <p class="active-filters">Active filters applied</p>
            <?php endif; ?>
        </div>

        <div class="accounts-table-wrapper">
            <table class="luxury-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($admins)): ?>
                        <tr>
                            <td colspan="4" class="empty-state">
                                <i class="fas fa-user-slash"></i>
                                <p>No admin accounts found.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($admins as $admin): ?>
                            <tr class="account-row">
                                <td class="account-id">
                                    <span class="id-badge">#<?= $admin['user_id']; ?></span>
                                </td>
                                <td class="account-username">
                                    <div class="username-wrapper">
                                        <i class="fas fa-user-circle"></i>
                                        <span><?= htmlspecialchars($admin['username']); ?></span>
                                        <?php if ($admin['user_id'] == $_SESSION['admin_id']): ?>
                                            <span class="you-badge">You</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="account-status">
                                    <span class="status-badge active">
                                        <i class="fas fa-circle"></i>
                                        Active
                                    </span>
                                </td>
                                <td class="actions-cell">
                                    <button class="btn-lux btn-edit" 
                                            onclick="openEditModal(<?= $admin['user_id']; ?>, '<?= htmlspecialchars($admin['username'], ENT_QUOTES); ?>')"
                                            title="Edit Admin">
                                        <i class="fas fa-edit"></i>
                                        <span>Edit</span>
                                    </button>
                                    <?php if ($admin['user_id'] != $_SESSION['admin_id']): ?>
                                        <form method="POST" class="delete-form" style="display: inline;">
                                            <input type="hidden" name="user_id" value="<?= $admin['user_id']; ?>">
                                            <button type="submit" name="delete_admin" 
                                                    class="btn-lux btn-delete" 
                                                    onclick="return confirm('Are you sure you want to delete this admin account? This action cannot be undone.')"
                                                    title="Delete Admin">
                                                <i class="fas fa-trash"></i>
                                                <span>Delete</span>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Admin Modal -->
<div id="addModal" class="modal-overlay">
    <div class="modal-container">
        <div class="modal-header">
            <h2 class="modal-title">Add New Admin</h2>
            <button class="modal-close" onclick="closeAddModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" class="modal-form" id="addAdminForm">
            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-input" required 
                       placeholder="Enter username" autocomplete="off">
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <div class="password-wrapper">
                    <input type="password" name="password" id="add_password" class="form-input" required 
                           placeholder="Enter password (min. 6 characters)" minlength="6">
                    <button type="button" class="password-toggle" onclick="togglePassword('add_password')">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Confirm Password</label>
                <div class="password-wrapper">
                    <input type="password" name="confirm_password" id="add_confirm_password" class="form-input" required 
                           placeholder="Re-enter password" minlength="6">
                    <button type="button" class="password-toggle" onclick="togglePassword('add_confirm_password')">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-lux btn-secondary" onclick="closeAddModal()">
                    <span>Cancel</span>
                </button>
                <button type="submit" name="add_admin" value="1" class="btn-lux btn-primary">
                    <i class="fas fa-plus"></i>
                    <span>Add Admin</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Admin Modal -->
<div id="editModal" class="modal-overlay">
    <div class="modal-container">
        <div class="modal-header">
            <h2 class="modal-title">Edit Admin Account</h2>
            <button class="modal-close" onclick="closeEditModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" class="modal-form" id="editAdminForm">
            <input type="hidden" name="user_id" id="edit_user_id">
            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" name="username" id="edit_username" class="form-input" required 
                       placeholder="Enter username" autocomplete="off">
            </div>
            <div class="form-group">
                <label class="form-label">New Password (leave blank to keep current)</label>
                <div class="password-wrapper">
                    <input type="password" name="password" id="edit_password" class="form-input" 
                           placeholder="Enter new password (min. 6 characters)" minlength="6">
                    <button type="button" class="password-toggle" onclick="togglePassword('edit_password')">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <small class="form-hint">Leave empty to keep the current password</small>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-lux btn-secondary" onclick="closeEditModal()">
                    <span>Cancel</span>
                </button>
                <button type="submit" name="update_admin" value="1" class="btn-lux btn-primary">
                    <i class="fas fa-save"></i>
                    <span>Save Changes</span>
                </button>
            </div>
        </form>
    </div>
</div>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

.admin-main {
    font-family: 'Montserrat', sans-serif;
    background: linear-gradient(to bottom, #fafafa 0%, #ffffff 100%);
    min-height: 100vh;
    padding: 60px 40px;
}

.luxury-accounts-container {
    max-width: 1400px;
    margin: 0 auto;
}

/* ===== HEADER ===== */
.page-header-lux {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 60px;
    padding-bottom: 40px;
    border-bottom: 1px solid rgba(0,0,0,0.08);
    flex-wrap: wrap;
    gap: 20px;
}

.header-content {
    flex: 1;
}

.lux-admin-title {
    font-family: 'Playfair Display', serif;
    font-size: 48px;
    font-weight: 400;
    letter-spacing: 0.5px;
    margin-bottom: 12px;
    color: #0a0a0a;
}

.lux-admin-subtitle {
    font-size: 11px;
    letter-spacing: 3px;
    text-transform: uppercase;
    color: rgba(0,0,0,0.5);
    font-weight: 400;
}

.header-actions {
    display: flex;
    gap: 15px;
}

/* ===== ALERTS ===== */
.alert-lux {
    padding: 20px 30px;
    margin-bottom: 40px;
    border: 1px solid;
    display: flex;
    align-items: center;
    gap: 15px;
    font-size: 13px;
    letter-spacing: 0.3px;
    transition: all 0.3s ease;
}

.alert-lux .alert-icon {
    font-size: 18px;
    font-weight: 600;
}

.alert-success {
    background: #f0f8f4;
    border-color: rgba(21,87,36,0.2);
    color: #155724;
}

.alert-danger {
    background: #fef5f5;
    border-color: rgba(176,42,55,0.2);
    color: #b02a37;
}

.alert-lux:hover {
    transform: translateX(5px);
}

/* ===== STATISTICS GRID ===== */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 25px;
    margin-bottom: 60px;
}

.stat-card {
    background: #ffffff;
    border: 1px solid rgba(0,0,0,0.08);
    padding: 30px;
    display: flex;
    align-items: center;
    gap: 20px;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 0;
    height: 2px;
    background: #0a0a0a;
    transition: width 0.4s ease;
}

.stat-card:hover::before {
    width: 100%;
}

.stat-card:hover {
    transform: translateY(-3px);
    border-color: rgba(0,0,0,0.15);
    box-shadow: 0 15px 45px rgba(0,0,0,0.08);
}

.stat-icon {
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fafafa;
    border: 1px solid rgba(0,0,0,0.08);
    font-size: 20px;
    color: #0a0a0a;
    transition: all 0.3s ease;
}

.stat-card:hover .stat-icon {
    background: #0a0a0a;
    color: #ffffff;
    transform: scale(1.1);
}

.stat-content {
    flex: 1;
}

.stat-value {
    font-size: 28px;
    font-weight: 600;
    color: #0a0a0a;
    margin-bottom: 5px;
    letter-spacing: 0.5px;
}

.stat-label {
    font-size: 10px;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: rgba(0,0,0,0.5);
    font-weight: 500;
}

/* ===== FILTERS SECTION ===== */
.filters-section {
    background: #ffffff;
    border: 1px solid rgba(0,0,0,0.08);
    padding: 35px;
    margin-bottom: 30px;
    transition: all 0.3s ease;
}

.filters-section:hover {
    border-color: rgba(0,0,0,0.12);
}

.filters-form {
    display: flex;
    flex-direction: column;
    gap: 25px;
}

.search-box {
    position: relative;
}

.search-icon {
    position: absolute;
    left: 18px;
    top: 50%;
    transform: translateY(-50%);
    color: rgba(0,0,0,0.4);
    font-size: 14px;
}

.search-input {
    width: 100%;
    padding: 15px 20px 15px 48px;
    border: 1px solid rgba(0,0,0,0.15);
    background: #fafafa;
    font-size: 13px;
    letter-spacing: 0.3px;
    font-family: 'Montserrat', sans-serif;
    transition: all 0.3s ease;
}

.search-input:focus {
    outline: none;
    border-color: #0a0a0a;
    background: #ffffff;
}

.search-input::placeholder {
    color: rgba(0,0,0,0.4);
}

.filter-controls {
    display: flex;
    gap: 20px;
    align-items: flex-end;
    flex-wrap: wrap;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
    flex: 1;
    min-width: 180px;
}

.filter-label {
    font-size: 10px;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: #0a0a0a;
    font-weight: 600;
}

.filter-select {
    padding: 15px 20px;
    border: 1px solid rgba(0,0,0,0.15);
    background: #fafafa;
    font-size: 12px;
    letter-spacing: 0.5px;
    color: #0a0a0a;
    cursor: pointer;
    transition: all 0.3s ease;
    font-family: 'Montserrat', sans-serif;
}

.filter-select:hover,
.filter-select:focus {
    border-color: #0a0a0a;
    outline: none;
    background: #ffffff;
}

/* ===== BUTTONS ===== */
.btn-lux {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 15px 30px;
    border: 1px solid rgba(0,0,0,0.2);
    background: transparent;
    color: #0a0a0a;
    font-size: 10px;
    font-weight: 500;
    letter-spacing: 2px;
    text-transform: uppercase;
    cursor: pointer;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
    text-decoration: none;
    font-family: 'Montserrat', sans-serif;
}

.btn-lux::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 0;
    height: 100%;
    background: #0a0a0a;
    transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 0;
}

.btn-lux:hover::before {
    width: 100%;
}

.btn-lux:hover {
    color: #ffffff;
    border-color: #0a0a0a;
}

.btn-lux i,
.btn-lux span {
    position: relative;
    z-index: 1;
}

.btn-delete {
    border-color: rgba(176,42,55,0.3);
    color: #b02a37;
}

.btn-delete::before {
    background: #b02a37;
}

.btn-delete:hover {
    border-color: #b02a37;
}

.btn-secondary {
    border-color: rgba(0,0,0,0.15);
    color: rgba(0,0,0,0.6);
}

.btn-secondary::before {
    background: rgba(0,0,0,0.05);
}

.btn-secondary:hover {
    color: #0a0a0a;
    border-color: rgba(0,0,0,0.3);
}

/* ===== RESULTS INFO ===== */
.results-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 0;
    font-size: 12px;
    letter-spacing: 0.3px;
    color: rgba(0,0,0,0.6);
    margin-bottom: 15px;
}

.results-info strong {
    color: #0a0a0a;
    font-weight: 600;
}

.active-filters {
    font-size: 11px;
    color: #0a0a0a;
    font-weight: 500;
    letter-spacing: 1.5px;
    text-transform: uppercase;
}

/* ===== TABLE WRAPPER ===== */
.accounts-table-wrapper {
    background: #ffffff;
    border: 1px solid rgba(0,0,0,0.08);
    overflow: hidden;
    transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

.accounts-table-wrapper:hover {
    border-color: rgba(0,0,0,0.12);
    box-shadow: 0 20px 60px rgba(0,0,0,0.06);
}

/* ===== LUXURY TABLE ===== */
.luxury-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}

.luxury-table thead {
    background: #fafafa;
    border-bottom: 2px solid rgba(0,0,0,0.1);
}

.luxury-table thead th {
    padding: 25px 20px;
    text-align: left;
    font-size: 10px;
    font-weight: 600;
    letter-spacing: 2.5px;
    text-transform: uppercase;
    color: #0a0a0a;
    border-right: 1px solid rgba(0,0,0,0.05);
}

.luxury-table thead th:last-child {
    border-right: none;
}

.luxury-table tbody tr {
    border-bottom: 1px solid rgba(0,0,0,0.05);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
}

.luxury-table tbody tr::after {
    content: '';
    position: absolute;
    left: 0;
    bottom: 0;
    width: 0;
    height: 1px;
    background: #0a0a0a;
    transition: width 0.4s ease;
}

.luxury-table tbody tr:hover::after {
    width: 100%;
}

.luxury-table tbody tr:hover {
    background: #fafafa;
    transform: translateX(3px);
}

.luxury-table tbody td {
    padding: 25px 20px;
    vertical-align: middle;
    color: #2a2a2a;
    letter-spacing: 0.3px;
}

/* ===== TABLE CELLS ===== */
.account-id {
    width: 100px;
}

.id-badge {
    display: inline-block;
    padding: 6px 14px;
    background: #fafafa;
    border: 1px solid rgba(0,0,0,0.08);
    font-size: 11px;
    letter-spacing: 1px;
    font-weight: 600;
    color: #0a0a0a;
}

.username-wrapper {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 14px;
    font-weight: 500;
}

.username-wrapper i {
    font-size: 20px;
    color: rgba(0,0,0,0.4);
}

.you-badge {
    display: inline-block;
    padding: 4px 10px;
    background: #0a0a0a;
    color: #ffffff;
    font-size: 9px;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    font-weight: 600;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 14px;
    background: #f0f8f4;
    border: 1px solid rgba(21,87,36,0.2);
    color: #155724;
    font-size: 11px;
    letter-spacing: 0.5px;
    font-weight: 500;
}

.status-badge i {
    font-size: 8px;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.3; }
}

.actions-cell {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
}

.empty-state {
    text-align: center;
    padding: 80px 20px !important;
    color: rgba(0,0,0,0.4);
}

.empty-state i {
    font-size: 48px;
    margin-bottom: 20px;
    display: block;
    color: rgba(0,0,0,0.2);
}

.empty-state p {
    font-size: 13px;
    letter-spacing: 1.5px;
    text-transform: uppercase;
}

/* ===== MODAL ===== */
.modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.6);
    backdrop-filter: blur(5px);
    z-index: 10000;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.modal-overlay.active {
    display: flex;
    opacity: 1;
}

.modal-container {
    background: #ffffff;
    width: 90%;
    max-width: 600px;
    border: 1px solid rgba(0,0,0,0.1);
    transform: translateY(20px);
    transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.modal-overlay.active .modal-container {
    transform: translateY(0);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 30px 40px;
    border-bottom: 1px solid rgba(0,0,0,0.08);
}

.modal-title {
    font-family: 'Playfair Display', serif;
    font-size: 28px;
    font-weight: 400;
    color: #0a0a0a;
    letter-spacing: 0.5px;
}

.modal-close {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: transparent;
    border: 1px solid rgba(0,0,0,0.1);
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 16px;
    color: rgba(0,0,0,0.5);
}

.modal-close:hover {
    background: #0a0a0a;
    color: #ffffff;
    border-color: #0a0a0a;
    transform: rotate(90deg);
}

.modal-form {
    padding: 40px;
}

.form-group {
    margin-bottom: 25px;
}

.form-label {
    display: block;
    font-size: 10px;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: #0a0a0a;
    font-weight: 600;
    margin-bottom: 10px;
}

.form-input {
    width: 100%;
    padding: 15px 20px;
    border: 1px solid rgba(0,0,0,0.15);
    background: #fafafa;
    font-size: 13px;
    letter-spacing: 0.3px;
    font-family: 'Montserrat', sans-serif;
    transition: all 0.3s ease;
    color: #0a0a0a;
}

.form-input:focus {
    outline: none;
    border-color: #0a0a0a;
    background: #ffffff;
}

.form-input::placeholder {
    color: rgba(0,0,0,0.4);
}

.password-wrapper {
    position: relative;
}

.password-toggle {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    background: transparent;
    border: none;
    color: rgba(0,0,0,0.4);
    cursor: pointer;
    padding: 5px;
    transition: color 0.3s ease;
}

.password-toggle:hover {
    color: #0a0a0a;
}

.form-hint {
    display: block;
    margin-top: 8px;
    font-size: 11px;
    color: rgba(0,0,0,0.5);
    letter-spacing: 0.3px;
}

.modal-actions {
    display: flex;
    gap: 15px;
    justify-content: flex-end;
    margin-top: 35px;
    padding-top: 25px;
    border-top: 1px solid rgba(0,0,0,0.08);
}

/* ===== RESPONSIVE ===== */
@media (max-width: 1200px) {
    .luxury-table {
        font-size: 12px;
    }
    
    .luxury-table thead th,
    .luxury-table tbody td {
        padding: 20px 15px;
    }

    .stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    }
}

@media (max-width: 968px) {
    .admin-main {
        padding: 40px 20px;
    }

    .lux-admin-title {
        font-size: 36px;
    }

    .page-header-lux {
        flex-direction: column;
        align-items: flex-start;
    }

    .header-actions {
        width: 100%;
    }

    .btn-primary {
        width: 100%;
        justify-content: center;
    }

    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .filter-controls {
        flex-direction: column;
        width: 100%;
        gap: 15px;
    }

    .filter-group {
        width: 100%;
    }

    .btn-lux {
        width: 100%;
        justify-content: center;
    }

    .accounts-table-wrapper {
        overflow-x: auto;
    }

    .luxury-table {
        min-width: 700px;
    }
}

@media (max-width: 768px) {
    .page-header-lux {
        margin-bottom: 50px;
        padding-bottom: 30px;
    }

    .lux-admin-title {
        font-size: 28px;
    }

    .lux-admin-subtitle {
        font-size: 10px;
        letter-spacing: 2px;
    }

    .stats-grid {
        grid-template-columns: 1fr;
    }

    .actions-cell {
        flex-direction: column;
        align-items: flex-start;
        width: 100%;
    }

    .modal-container {
        width: 95%;
        max-height: 90vh;
        overflow-y: auto;
    }

    .modal-header,
    .modal-form {
        padding: 25px;
    }

    .modal-title {
        font-size: 24px;
    }

    .modal-actions {
        flex-direction: column;
    }

    .modal-actions .btn-lux {
        width: 100%;
    }
}

@media (max-width: 480px) {
    .lux-admin-title {
        font-size: 24px;
    }

    .filters-section {
        padding: 25px 20px;
    }

    .username-wrapper {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
}
</style>

<script>
// Modal Functions
function openAddModal() {
    const modal = document.getElementById('addModal');
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeAddModal() {
    const modal = document.getElementById('addModal');
    modal.classList.remove('active');
    document.body.style.overflow = '';
}

function openEditModal(userId, username) {
    const modal = document.getElementById('editModal');
    document.getElementById('edit_user_id').value = userId;
    document.getElementById('edit_username').value = username;
    document.getElementById('edit_password').value = '';
    
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeEditModal() {
    const modal = document.getElementById('editModal');
    modal.classList.remove('active');
    document.body.style.overflow = '';
}

// Close modal when clicking outside
document.addEventListener('DOMContentLoaded', function() {
    const modals = document.querySelectorAll('.modal-overlay');
    modals.forEach(modal => {
        modal.addEventListener('click', function(e) {
            // Only close if clicking the overlay itself, not the container
            if (e.target === modal) {
                modal.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    });
});

// Password toggle
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const button = input.nextElementSibling;
    const icon = button.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Auto-dismiss alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert-lux');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateX(-20px)';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
});

// Auto-submit form on filter change
document.addEventListener('DOMContentLoaded', function() {
    const sortSelect = document.getElementById('sort');
    const searchInput = document.getElementById('search');
    const form = document.querySelector('.filters-form');

    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            form.submit();
        });
    }

    // Search with debounce
    let searchTimeout;
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                form.submit();
            }, 500);
        });
    }
});

// Password strength indicator
document.addEventListener('DOMContentLoaded', function() {
    const passwordInputs = document.querySelectorAll('input[type="password"]');
    
    passwordInputs.forEach(input => {
        if (input.name === 'password' || input.id.includes('password')) {
            input.addEventListener('input', function() {
                const value = this.value;
                const parent = this.closest('.form-group');
                
                // Remove existing strength indicator
                const existing = parent.querySelector('.password-strength');
                if (existing) existing.remove();
                
                if (value.length > 0) {
                    let strength = 'weak';
                    let color = '#b02a37';
                    
                    if (value.length >= 8 && /[A-Z]/.test(value) && /[0-9]/.test(value)) {
                        strength = 'strong';
                        color = '#155724';
                    } else if (value.length >= 6) {
                        strength = 'medium';
                        color = '#856404';
                    }
                    
                    const indicator = document.createElement('small');
                    indicator.className = 'password-strength';
                    indicator.style.cssText = `display: block; margin-top: 8px; font-size: 11px; color: ${color}; letter-spacing: 0.3px;`;
                    indicator.textContent = `Password strength: ${strength}`;
                    
                    this.closest('.password-wrapper').parentNode.appendChild(indicator);
                }
            });
        }
    });
});

// Confirm password validation
document.addEventListener('DOMContentLoaded', function() {
    const addForm = document.getElementById('addAdminForm');
    
    if (addForm) {
        addForm.addEventListener('submit', function(e) {
            const password = this.querySelector('input[name="password"]').value;
            const confirmPassword = this.querySelector('input[name="confirm_password"]').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match. Please try again.');
                return false;
            }
            
            // Allow form to submit normally
            return true;
        });
    }
    
    // Edit form - no special validation needed, just submit
    const editForm = document.getElementById('editAdminForm');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            // Just let it submit normally
            return true;
        });
    }
});

// Add loading state to buttons
document.addEventListener('DOMContentLoaded', function() {
    console.log('Admin Accounts page loaded successfully');
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // ESC to close modals
    if (e.key === 'Escape') {
        const activeModal = document.querySelector('.modal-overlay.active');
        if (activeModal) {
            activeModal.classList.remove('active');
            document.body.style.overflow = '';
        }
    }
    
    // Ctrl/Cmd + K to open add modal
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        openAddModal();
    }
});

// Enhanced delete confirmation
document.addEventListener('DOMContentLoaded', function() {
    const deleteForms = document.querySelectorAll('.delete-form');
    deleteForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const username = this.closest('tr').querySelector('.username-wrapper span').textContent.trim();
            const confirmed = confirm(`Are you sure you want to delete the admin account "${username}"?\n\nThis action cannot be undone and will permanently remove this administrator's access.`);
            if (!confirmed) {
                e.preventDefault();
            }
        });
    });
});

// Add smooth scrolling
document.addEventListener('DOMContentLoaded', function() {
    const style = document.createElement('style');
    style.textContent = `
        html {
            scroll-behavior: smooth;
        }
    `;
    document.head.appendChild(style);
});
</script>

<?php require_once '../includes/footer.php'; ?>