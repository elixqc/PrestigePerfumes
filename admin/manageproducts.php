<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/adminheader.php';

// Check if user is logged in as admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Initialize messages
$message = '';
$messageType = '';

// Handle delete
if (isset($_POST['delete_product'])) {
    $product_id = $_POST['product_id'];
    
    // Delete product image first
    $stmt = $pdo->prepare("SELECT image_path FROM products WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if ($product && !empty($product['image_path'])) {
        $image_path = "../" . $product['image_path'];
        if (file_exists($image_path)) unlink($image_path);
    }

    // Delete product from DB
    $stmt = $pdo->prepare("DELETE FROM products WHERE product_id = ?");
    if ($stmt->execute([$product_id])) {
        $message = "Product deleted successfully.";
        $messageType = "success";
    } else {
        $message = "Error deleting product.";
        $messageType = "danger";
    }
}

// Filters
$category_filter = $_GET['category'] ?? '';
$supplier_filter = $_GET['supplier'] ?? '';
$search_term = $_GET['search'] ?? '';
$sort_by = $_GET['sort'] ?? 'newest';

$query = "
    SELECT p.*, c.category_name, s.supplier_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.category_id 
    LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id 
    WHERE 1=1
";

$params = [];
if ($category_filter) {
    $query .= " AND p.category_id = ?";
    $params[] = $category_filter;
}
if ($supplier_filter) {
    $query .= " AND p.supplier_id = ?";
    $params[] = $supplier_filter;
}
if ($search_term) {
    $query .= " AND (p.product_name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search_term%";
    $params[] = "%$search_term%";
}

// Add sorting
switch ($sort_by) {
    case 'oldest':
        $query .= " ORDER BY p.product_id ASC";
        break;
    case 'name_asc':
        $query .= " ORDER BY p.product_name ASC";
        break;
    case 'name_desc':
        $query .= " ORDER BY p.product_name DESC";
        break;
    case 'price_asc':
        $query .= " ORDER BY p.price ASC";
        break;
    case 'price_desc':
        $query .= " ORDER BY p.price DESC";
        break;
    case 'stock_asc':
        $query .= " ORDER BY p.stock_quantity ASC";
        break;
    case 'stock_desc':
        $query .= " ORDER BY p.stock_quantity DESC";
        break;
    default:
        $query .= " ORDER BY p.product_id DESC";
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();

$categories = $pdo->query("SELECT * FROM categories ORDER BY category_name")->fetchAll();
$suppliers = $pdo->query("SELECT * FROM suppliers ORDER BY supplier_name")->fetchAll();

// Get statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_products,
        SUM(stock_quantity) as total_stock,
        AVG(price) as avg_price,
        SUM(CASE WHEN stock_quantity < 10 THEN 1 ELSE 0 END) as low_stock,
        COUNT(DISTINCT category_id) as total_categories
    FROM products
";
$stats = $pdo->query($stats_query)->fetch(PDO::FETCH_ASSOC);
?>

<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600&family=Playfair+Display:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="admin-main">
    <div class="luxury-products-container">
        <div class="page-header-lux">
            <div class="header-content">
                <h1 class="lux-admin-title">Product Management</h1>
                <p class="lux-admin-subtitle">Curate Your Luxury Collection</p>
            </div>
            <div class="header-actions">
                <a href="additems.php" class="btn-lux btn-primary">
                    <i class="fas fa-plus"></i>
                    <span>Add Product</span>
                </a>
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
                <div class="stat-icon"><i class="fas fa-box"></i></div>
                <div class="stat-content">
                    <div class="stat-value"><?= number_format($stats['total_products']); ?></div>
                    <div class="stat-label">Total Products</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-cubes"></i></div>
                <div class="stat-content">
                    <div class="stat-value"><?= number_format($stats['total_stock']); ?></div>
                    <div class="stat-label">Total Stock</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-tag"></i></div>
                <div class="stat-content">
                    <div class="stat-value">₱<?= number_format($stats['avg_price'], 2); ?></div>
                    <div class="stat-label">Avg. Price</div>
                </div>
            </div>
            <div class="stat-card stat-warning">
                <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="stat-content">
                    <div class="stat-value"><?= number_format($stats['low_stock']); ?></div>
                    <div class="stat-label">Low Stock Items</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-layer-group"></i></div>
                <div class="stat-content">
                    <div class="stat-value"><?= number_format($stats['total_categories']); ?></div>
                    <div class="stat-label">Categories</div>
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
                           placeholder="Search products by name or description..." 
                           value="<?= htmlspecialchars($search_term); ?>">
                </div>

                <div class="filter-controls">
                    <div class="filter-group">
                        <label class="filter-label">Category</label>
                        <select name="category" id="category" class="filter-select">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['category_id']; ?>" 
                                    <?= $category_filter == $category['category_id'] ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($category['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label class="filter-label">Supplier</label>
                        <select name="supplier" id="supplier" class="filter-select">
                            <option value="">All Suppliers</option>
                            <?php foreach ($suppliers as $supplier): ?>
                                <option value="<?= $supplier['supplier_id']; ?>" 
                                    <?= $supplier_filter == $supplier['supplier_id'] ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($supplier['supplier_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label class="filter-label">Sort By</label>
                        <select name="sort" id="sort" class="filter-select">
                            <option value="newest" <?= $sort_by === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                            <option value="oldest" <?= $sort_by === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                            <option value="name_asc" <?= $sort_by === 'name_asc' ? 'selected' : ''; ?>>Name (A-Z)</option>
                            <option value="name_desc" <?= $sort_by === 'name_desc' ? 'selected' : ''; ?>>Name (Z-A)</option>
                            <option value="price_asc" <?= $sort_by === 'price_asc' ? 'selected' : ''; ?>>Price (Low to High)</option>
                            <option value="price_desc" <?= $sort_by === 'price_desc' ? 'selected' : ''; ?>>Price (High to Low)</option>
                            <option value="stock_asc" <?= $sort_by === 'stock_asc' ? 'selected' : ''; ?>>Stock (Low to High)</option>
                            <option value="stock_desc" <?= $sort_by === 'stock_desc' ? 'selected' : ''; ?>>Stock (High to Low)</option>
                        </select>
                    </div>

                    <button type="submit" class="btn-lux btn-filter">
                        <i class="fas fa-filter"></i>
                        <span>Apply</span>
                    </button>
                    <a href="manageproducts.php" class="btn-lux btn-reset">
                        <i class="fas fa-redo"></i>
                        <span>Reset</span>
                    </a>
                </div>
            </form>
        </div>

        <div class="results-info">
            <p>Showing <strong><?= count($products); ?></strong> product<?= count($products) !== 1 ? 's' : ''; ?></p>
            <?php if (!empty($search_term) || $category_filter || $supplier_filter || $sort_by !== 'newest'): ?>
                <p class="active-filters">Active filters applied</p>
            <?php endif; ?>
        </div>

        <div class="products-table-wrapper">
            <table class="luxury-table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Product Details</th>
                        <th>Category</th>
                        <th>Supplier</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="7" class="empty-state">
                                <i class="fas fa-box-open"></i>
                                <p>No products found matching your criteria.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <tr class="product-row">
                                <td class="product-image">
                                    <div class="image-wrapper">
                                        <img src="../<?= htmlspecialchars($product['image_path']); ?>" 
                                             alt="<?= htmlspecialchars($product['product_name']); ?>">
                                    </div>
                                </td>
                                <td class="product-details">
                                    <div class="product-name"><?= htmlspecialchars($product['product_name']); ?></div>
                                    <?php if (!empty($product['description'])): ?>
                                        <div class="product-desc">
                                            <?= htmlspecialchars(substr($product['description'], 0, 60)) . (strlen($product['description']) > 60 ? '...' : ''); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="product-category">
                                    <span class="category-badge">
                                        <?= htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?>
                                    </span>
                                </td>
                                <td class="product-supplier">
                                    <?= htmlspecialchars($product['supplier_name'] ?? '—'); ?>
                                </td>
                                <td class="product-price">
                                    <span class="price-value">₱<?= number_format($product['price'], 2); ?></span>
                                </td>
                                <td class="product-stock">
                                    <span class="stock-badge <?= $product['stock_quantity'] < 10 ? 'low-stock' : ''; ?>">
                                        <?= $product['stock_quantity']; ?> units
                                    </span>
                                </td>
                                <td class="actions-cell">
                                    <a href="editproduct.php?id=<?= $product['product_id']; ?>" 
                                       class="btn-lux btn-edit" title="Edit Product">
                                        <i class="fas fa-edit"></i>
                                        <span>Edit</span>
                                    </a>
                                    <form method="POST" class="delete-form"
                                          onsubmit="return confirm('Are you sure you want to delete this product? This action cannot be undone.');">
                                        <input type="hidden" name="product_id" value="<?= $product['product_id']; ?>">
                                        <button type="submit" name="delete_product" 
                                                class="btn-lux btn-delete" title="Delete Product">
                                            <i class="fas fa-trash"></i>
                                            <span>Delete</span>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
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

.luxury-products-container {
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

.stat-warning::before {
    background: #b02a37;
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

.stat-warning:hover .stat-icon {
    background: #b02a37;
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
.products-table-wrapper {
    background: #ffffff;
    border: 1px solid rgba(0,0,0,0.08);
    overflow: hidden;
    transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

.products-table-wrapper:hover {
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
.product-image {
    width: 100px;
}

.image-wrapper {
    width: 80px;
    height: 80px;
    overflow: hidden;
    border: 1px solid rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.image-wrapper:hover {
    transform: scale(1.05);
    border-color: #0a0a0a;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.image-wrapper img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.product-details {
    max-width: 300px;
}

.product-name {
    font-weight: 500;
    color: #0a0a0a;
    margin-bottom: 6px;
    font-size: 14px;
}

.product-desc {
    font-size: 11px;
    color: rgba(0,0,0,0.5);
    line-height: 1.5;
}

.category-badge {
    display: inline-block;
    padding: 6px 14px;
    background: #fafafa;
    border: 1px solid rgba(0,0,0,0.08);
    font-size: 11px;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: #0a0a0a;
    font-weight: 500;
    transition: all 0.3s ease;
}

.category-badge:hover {
    background: #0a0a0a;
    color: #ffffff;
    border-color: #0a0a0a;
}

.product-supplier {
    font-size: 12px;
    color: rgba(0,0,0,0.6);
}

.price-value {
    font-weight: 600;
    font-size: 15px;
    letter-spacing: 0.5px;
    color: #0a0a0a;
}

.stock-badge {
    display: inline-block;
    padding: 6px 14px;
    background: #f0f8f4;
    border: 1px solid rgba(21,87,36,0.2);
    color: #155724;
    font-size: 11px;
    letter-spacing: 0.5px;
    font-weight: 500;
}

.stock-badge.low-stock {
    background: #fef5f5;
    border-color: rgba(176,42,55,0.2);
    color: #b02a37;
}

.actions-cell {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
}

.delete-form {
    display: inline;
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

    .products-table-wrapper {
        overflow-x: auto;
    }

    .luxury-table {
        min-width: 900px;
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

    .btn-lux {
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
}
</style>

<script>
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
    const categorySelect = document.getElementById('category');
    const supplierSelect = document.getElementById('supplier');
    const sortSelect = document.getElementById('sort');
    const searchInput = document.getElementById('search');
    const form = document.querySelector('.filters-form');

    if (categorySelect) {
        categorySelect.addEventListener('change', function() {
            form.submit();
        });
    }

    if (supplierSelect) {
        supplierSelect.addEventListener('change', function() {
            form.submit();
        });
    }

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

// Confirm delete with enhanced message
document.addEventListener('DOMContentLoaded', function() {
    const deleteForms = document.querySelectorAll('.delete-form');
    deleteForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const productName = this.closest('tr').querySelector('.product-name').textContent;
            const confirmed = confirm(`Are you sure you want to delete "${productName}"?\n\nThis action cannot be undone and will permanently remove the product from your inventory.`);
            if (!confirmed) {
                e.preventDefault();
            }
        });
    });
});

// Add loading state to filter button
document.addEventListener('DOMContentLoaded', function() {
    const filterBtn = document.querySelector('.btn-filter');
    const form = document.querySelector('.filters-form');
    
    if (form && filterBtn) {
        form.addEventListener('submit', function() {
            filterBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Loading...</span>';
            filterBtn.disabled = true;
        });
    }
});

// Highlight low stock items
document.addEventListener('DOMContentLoaded', function() {
    const stockBadges = document.querySelectorAll('.stock-badge.low-stock');
    stockBadges.forEach(badge => {
        const row = badge.closest('tr');
        if (row) {
            row.style.borderLeft = '3px solid #b02a37';
        }
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>