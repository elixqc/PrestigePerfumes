<?php
session_start();
require_once('../includes/config.php');
require_once('../includes/header.php');

if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit;
}

$customer_id = (int) $_SESSION['customer_id'];
$stmt = $conn->prepare("SELECT full_name, email, contact_number, address FROM customers WHERE customer_id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();
$customer = $result->fetch_assoc();
$stmt->close();

if (!$customer) {
    echo "<p class='center-text'>Customer not found.</p>";
    require_once('../includes/footer.php');
    exit;
}
?>

<!-- Load Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500&family=Playfair+Display:wght@400;500&display=swap" rel="stylesheet">

<main class="profile-dashboard">
  <div class="profile-container">
    <h1 class="lux-title">Your Profile</h1>
    <p class="lux-subtitle">Refined details, tailored for you</p>

    <div class="profile-card">
      <div class="profile-detail">
        <p><strong>Name:</strong> <?php echo htmlspecialchars($customer['full_name']); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($customer['email']); ?></p>
        <p><strong>Contact:</strong> <?php echo htmlspecialchars($customer['contact_number']); ?></p>
        <p><strong>Address:</strong> <?php echo htmlspecialchars($customer['address']); ?></p>
      </div>
    </div>

    <div class="profile-actions">
      <a href="edit_profile.php" class="btn"><span>Edit Info</span></a>
      <a href="../user/logout.php" class="btn"><span>Logout</span></a>
      <form action="delete_account.php" method="POST" style="display:inline;">
        <button type="submit" name="delete_account" class="btn btn-danger"
          onclick="return confirm('Delete your account permanently?');">
          <span>Delete</span>
        </button>
      </form>
    </div>
  </div>
</main>

<?php require_once('../includes/footer.php'); ?>

<style>
body {
  margin: 0;
  font-family: 'Montserrat', sans-serif;
  background: #ffffff;
  color: #0a0a0a;
}

/* Container */
.profile-dashboard {
  min-height: 85vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 80px 20px;
}

.profile-container {
  max-width: 900px;
  width: 100%;
  text-align: center;
}

/* Headings */
.lux-title {
  font-family: 'Playfair Display', serif;
  font-size: 40px;
  font-weight: 400;
  letter-spacing: 1px;
  margin-bottom: 10px;
  color: #0a0a0a;
}

.lux-subtitle {
  font-family: 'Montserrat', sans-serif;
  font-size: 14px;
  letter-spacing: 2px;
  text-transform: uppercase;
  color: rgba(0,0,0,0.6);
  margin-bottom: 50px;
}

/* Profile Card */
.profile-card {
  background: #fafafa;
  border-radius: 16px;
  padding: 40px 60px;
  box-shadow: 0 8px 30px rgba(0,0,0,0.05);
  text-align: left;
  margin: 0 auto 40px auto;
  max-width: 700px;
}

.profile-card p {
  font-size: 15px;
  margin: 15px 0;
  font-weight: 400;
  color: #333;
  letter-spacing: 0.3px;
}

.profile-card strong {
  font-weight: 500;
  color: #0a0a0a;
}

/* Actions */
.profile-actions {
  display: flex;
  justify-content: center;
  gap: 20px;
  flex-wrap: wrap;
}

/* BUTTON STYLES (same as header + cart theme) */
.btn {
  display: inline-block;
  padding: 14px 40px;
  text-decoration: none;
  font-family: 'Montserrat', sans-serif;
  font-size: 12px;
  letter-spacing: 2px;
  text-transform: uppercase;
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  font-weight: 400;
  position: relative;
  overflow: hidden;
  cursor: pointer;
  border: 1px solid rgba(0, 0, 0, 0.3);
  background: transparent;
  color: #0a0a0a;
  min-width: 160px;
  text-align: center;
}

.btn span {
  position: relative;
  z-index: 2;
}

.btn::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: #0a0a0a;
  transition: left 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  z-index: 1;
}

.btn:hover::before {
  left: 0;
}

.btn:hover {
  color: #ffffff;
  border-color: #0a0a0a;
}

/* Delete Button Variant */
.btn-danger {
  border: 1px solid rgba(176, 42, 55, 0.5);
  color: #b02a37;
}

.btn-danger::before {
  background: #b02a37;
}

.btn-danger:hover {
  color: #ffffff;
  border-color: #b02a37;
}

/* Responsive */
@media (max-width: 768px) {
  .lux-title { font-size: 30px; }
  .profile-card { padding: 30px; }
  .btn { width: 100%; }
}
</style>
