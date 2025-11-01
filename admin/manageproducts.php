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

$query .= " ORDER BY p.product_id DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();

$categories = $pdo->query("SELECT * FROM categories ORDER BY category_name")->fetchAll();
$suppliers = $pdo->query("SELECT * FROM suppliers ORDER BY supplier_name")->fetchAll();
?>

<div class="admin-main">
    <div class="product-management-container">
        <div class="page-header">
            <h2>Manage Products</h2>
            <div class="header-actions">
                <a href="additems.php" class="btn-primary">
                    <i class="fas fa-plus"></i> Add New Product
                </a>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="filters-section">
            <form method="GET" class="filters-form">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label for="category">Category</label>
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
                        <label for="supplier">Supplier</label>
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
                        <label for="search">Search</label>
                        <input type="text" name="search" id="search" class="filter-input" 
                               placeholder="Search products..." 
                               value="<?= htmlspecialchars($search_term); ?>">
                    </div>

                    <div class="filter-actions">
                        <button type="submit" class="btn-filter">
                            <i class="fas fa-search"></i> Apply Filters
                        </button>
                        <a href="manageproducts.php" class="btn-clear">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <div class="products-table-container">
            <table class="products-table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Product Name</th>
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
                            <td colspan="7" style="text-align:center; color:#6c757d; padding:2rem;">
                                No products found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td class="product-image">
                                    <img src="../<?= htmlspecialchars($product['image_path']); ?>" 
                                         alt="<?= htmlspecialchars($product['product_name']); ?>">
                                </td>
                                <td><?= htmlspecialchars($product['product_name']); ?></td>
                                <td><?= htmlspecialchars($product['category_name'] ?? '—'); ?></td>
                                <td><?= htmlspecialchars($product['supplier_name'] ?? '—'); ?></td>
                                <td>₱<?= number_format($product['price'], 2); ?></td>
                                <td><?= $product['stock_quantity']; ?></td>
                                <td class="actions">
                                    <a href="editproduct.php?id=<?= $product['product_id']; ?>" 
                                       class="btn-edit" title="Edit Product">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <form method="POST" class="delete-form"
                                          onsubmit="return confirm('Are you sure you want to delete this product? This action cannot be undone.');">
                                        <input type="hidden" name="product_id" value="<?= $product['product_id']; ?>">
                                        <button type="submit" name="delete_product" 
                                                class="btn-delete" title="Delete Product">
                                            <i class="fas fa-trash"></i> Delete
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

<?php require_once '../includes/footer.php'; ?>
