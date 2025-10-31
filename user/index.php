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

<main class="profile-dashboard">
  <div class="profile-container">
    <h1 class="brand-heading">Your Profile</h1>
    <p class="brand-subheading">Refined details, tailored for you</p>

    <div class="profile-card">
      <p><strong>Name:</strong> <?php echo htmlspecialchars($customer['full_name']); ?></p>
      <p><strong>Email:</strong> <?php echo htmlspecialchars($customer['email']); ?></p>
      <p><strong>Contact:</strong> <?php echo htmlspecialchars($customer['contact_number']); ?></p>
      <p><strong>Address:</strong> <?php echo htmlspecialchars($customer['address']); ?></p>
    </div>

    <div class="profile-actions">
      <a href="edit_profile.php" class="btn btn-gold">Edit Info</a>
      <a href="../user/logout.php" class="btn btn-outline">Logout</a>
      <form action="delete_account.php" method="POST" style="display:inline;">
        <button type="submit" name="delete_account" class="btn btn-danger"
          onclick="return confirm('Delete your account permanently?');">
          Delete
        </button>
      </form>
    </div>
  </div>
</main>

<?php require_once('../includes/footer.php'); ?>

<style>
body {
  margin: 0;
  font-family: 'Didot', serif;
  background: #fff;
  color: #111;
}

.profile-dashboard {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
}

.profile-container {
  max-width: 700px;
  width: 100%;
  text-align: center;
}

.brand-heading {
  font-size: 3rem;
  font-weight: 400;
  letter-spacing: 2px;
  margin-bottom: 10px;
  text-transform: uppercase;
}

.brand-subheading {
  font-size: 1rem;
  color: #666;
  margin-bottom: 40px;
  font-family: 'Helvetica Neue', sans-serif;
}

.profile-card {
  background: #fafafa;
  border-radius: 16px;
  padding: 50px;
  margin-bottom: 40px;
  box-shadow: 0 8px 30px rgba(0,0,0,0.05);
  text-align: left;
}

.profile-card p {
  font-size: 1rem;
  margin: 18px 0;
  color: #333;
}

.profile-card strong {
  font-weight: 600;
  color: #000;
}

.profile-actions {
  display: flex;
  justify-content: center;
  gap: 20px;
  flex-wrap: wrap;
}

/* Buttons */
.btn {
  padding: 12px 32px;
  border-radius: 40px;
  font-size: 0.85rem;
  text-transform: uppercase;
  letter-spacing: 1px;
  font-weight: 500;
  text-decoration: none;
  transition: all 0.3s ease;
  cursor: pointer;
}

.btn-gold {
  background: #c5a253;
  color: #fff;
  border: none;
}
.btn-gold:hover { background: #b89345; }

.btn-danger {
  background: #b02a37;
  color: #fff;
  border: none;
}
.btn-danger:hover { background: #a32632; }

.btn-outline {
  background: transparent;
  border: 2px solid #111;
  color: #111;
}
.btn-outline:hover {
  background: #111;
  color: #fff;
}

/* Responsive */
@media (max-width: 768px) {
  .brand-heading { font-size: 2rem; }
  .profile-card { padding: 30px; }
}
</style>