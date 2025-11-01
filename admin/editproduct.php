<?php
ob_start();
require_once '../includes/config.php';
require_once '../includes/adminheader.php';

// Make sure PDO throws exceptions (required for try/catch reliability)
if ($pdo && $pdo->getAttribute(PDO::ATTR_ERRMODE) !== PDO::ERRMODE_EXCEPTION) {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}

$message = '';
$messageType = '';

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$product_id) {
    header('Location: manageproducts.php');
    exit;
}

// Helper: redirect with optional query params
function redirect_to($url, $params = []) {
    if (!empty($params)) {
        $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($params);
    }
    header('Location: ' . $url);
    exit;
}

// ---------- HANDLE PRODUCT UPDATE ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_product') {
    try {
        $pdo->beginTransaction();

        $product_name = trim($_POST['product_name'] ?? '');
        $category_id = (int)($_POST['category_id'] ?? 0);
        $supplier_id = (int)($_POST['supplier_id'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $variant = trim($_POST['variant'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        // Basic validation
        if (strlen($product_name) < 3) {
            throw new Exception('Product name must be at least 3 characters.');
        }
        if ($price <= 0) {
            throw new Exception('Price must be greater than 0.');
        }

        $stmt = $pdo->prepare("
            UPDATE products SET
                product_name = ?,
                category_id = ?,
                supplier_id = ?,
                description = ?,
                price = ?,
                variant = ?,
                is_active = ?
            WHERE product_id = ?
        ");
        $stmt->execute([
            $product_name,
            $category_id ?: null,
            $supplier_id ?: null,
            $description,
            $price,
            $variant,
            $is_active,
            $product_id
        ]);

        // Handle image upload (optional)
        if (!empty($_FILES['image_path']['name'])) {
            $allowed_types = ['image/jpeg','image/jpg','image/png','image/gif'];
            $file_type = $_FILES['image_path']['type'] ?? '';
            $file_size = $_FILES['image_path']['size'] ?? 0;

            if (!in_array($file_type, $allowed_types)) {
                throw new Exception("Invalid file type. JPG, PNG, GIF only.");
            }
            if ($file_size > 5242880) {
                throw new Exception("File too large (max 5MB).");
            }

            // fetch current image and delete
            $stmt = $pdo->prepare("SELECT image_path FROM products WHERE product_id = ?");
            $stmt->execute([$product_id]);
            $current_image = $stmt->fetchColumn();
            if ($current_image && file_exists("../" . $current_image)) {
                @unlink("../" . $current_image);
            }

            $ext = pathinfo($_FILES['image_path']['name'], PATHINFO_EXTENSION);
            $image_name = 'perfume_' . uniqid() . '.' . $ext;
            $target_path = "assets/images/perfumes/" . $image_name;

            if (!move_uploaded_file($_FILES['image_path']['tmp_name'], "../" . $target_path)) {
                throw new Exception("Failed to move uploaded file.");
            }

            $stmt = $pdo->prepare("UPDATE products SET image_path = ? WHERE product_id = ?");
            $stmt->execute([$target_path, $product_id]);
        }

        $pdo->commit();

        redirect_to('editproduct.php', ['id' => $product_id, 'success' => 1]);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log("EditProduct (update) error: " . $e->getMessage());
        $message = "Error updating product: " . $e->getMessage();
        $messageType = "danger";
    }
}

// ---------- HANDLE STOCK ADJUSTMENT ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'adjust_stock') {
    try {
        $pdo->beginTransaction();

        $adjustment_type = $_POST['adjustment_type'] ?? '';
        $adjustment_quantity = abs((int)($_POST['adjustment_quantity'] ?? 0));
        $supplier_id_stock = (int)($_POST['supplier_id_stock'] ?? 0);
        $supplier_price_stock = isset($_POST['supplier_price_stock']) ? (float)$_POST['supplier_price_stock'] : 0.00;
        $stock_remarks = trim($_POST['stock_remarks'] ?? '');

        // validation
        if ($adjustment_type !== 'add' && $adjustment_type !== 'remove') {
            throw new Exception("Please select adjustment type (add or remove).");
        }
        if ($adjustment_quantity <= 0) {
            throw new Exception("Quantity must be greater than 0.");
        }
        if ($supplier_id_stock <= 0) {
            throw new Exception("Please select a valid supplier.");
        }

        // fetch current stock
        $stmt = $pdo->prepare("SELECT stock_quantity FROM products WHERE product_id = ?");
        $stmt->execute([$product_id]);
        $current_stock = (int)$stmt->fetchColumn();

        if ($adjustment_type === 'add') {
            $new_stock = $current_stock + $adjustment_quantity;
            $log_qty = $adjustment_quantity;
            $remarks = $stock_remarks !== '' ? $stock_remarks : "Stock added via admin adjustment";
        } else {
            // remove
            if ($adjustment_quantity > $current_stock) {
                throw new Exception("Cannot remove {$adjustment_quantity} units. Only {$current_stock} available.");
            }
            $new_stock = $current_stock - $adjustment_quantity;
            $log_qty = -$adjustment_quantity; // negative value to indicate removal
            $remarks = $stock_remarks !== '' ? $stock_remarks : "Stock removed via admin adjustment";
        }

        // update product stock
        $stmt = $pdo->prepare("UPDATE products SET stock_quantity = ? WHERE product_id = ?");
        $stmt->execute([$new_stock, $product_id]);

        // insert into supply_logs
        $stmt = $pdo->prepare("
            INSERT INTO supply_logs (product_id, supplier_id, quantity_added, quantity_remaining, supplier_price, supply_date, remarks)
            VALUES (?, ?, ?, ?, ?, NOW(), ?)
        ");
        $stmt->execute([
            $product_id,
            $supplier_id_stock,
            $log_qty,
            $new_stock,
            $supplier_price_stock > 0 ? $supplier_price_stock : null,
            $remarks
        ]);

        $pdo->commit();

        // redirect with success
        $param = $adjustment_type === 'add' ? 'add' : 'remove';
        redirect_to('editproduct.php', ['id' => $product_id, 'stock_success' => $param]);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log("EditProduct (stock) error: " . $e->getMessage());
        $message = "Error adjusting stock: " . $e->getMessage();
        $messageType = "danger";
    }
}

// ---------- FETCH DATA FOR PAGE ----------
try {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if (!$product) {
        redirect_to('manageproducts.php');
    }

    $categories = $pdo->query("SELECT * FROM categories ORDER BY category_name")->fetchAll();
    $suppliers = $pdo->query("SELECT * FROM suppliers WHERE is_active = 1 ORDER BY supplier_name")->fetchAll();

    $stock_history = $pdo->prepare("
        SELECT sl.*, s.supplier_name
        FROM supply_logs sl
        LEFT JOIN suppliers s ON sl.supplier_id = s.supplier_id
        WHERE sl.product_id = ?
        ORDER BY sl.supply_date DESC, sl.supply_id DESC
        LIMIT 5
    ");
    $stock_history->execute([$product_id]);
    $history = $stock_history->fetchAll();
} catch (Exception $e) {
    error_log("EditProduct (fetch) error: " . $e->getMessage());
    $message = "Error loading product data: " . $e->getMessage();
    $messageType = "danger";
}

// success messages from redirects
if (isset($_GET['success'])) {
    $message = "Product updated successfully!";
    $messageType = "success";
}
if (isset($_GET['stock_success'])) {
    if ($_GET['stock_success'] === 'add') {
        $message = "Stock added successfully!";
    } else if ($_GET['stock_success'] === 'remove') {
        $message = "Stock removed successfully!";
    }
    $messageType = "success";
}
?>

<!-- ========== HTML / CSS (kept your original layout, only changed the forms to include hidden action fields) ========== -->
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600&family=Playfair+Display:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="admin-main">
    <div class="luxury-edit-container">
        <div class="page-header-lux">
            <div class="header-content">
                <h1 class="lux-admin-title">Edit Product</h1>
                <p class="lux-admin-subtitle">Refine Your Product Details</p>
            </div>
            <div class="header-actions">
                <a href="manageproducts.php" class="btn-lux btn-back">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back to Products</span>
                </a>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert-lux alert-<?php echo $messageType; ?>" id="alertMessage">
                <span class="alert-icon"><?= $messageType === 'success' ? '✓' : '✕'; ?></span>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="edit-grid">
            <!-- LEFT COLUMN: Product Information -->
            <div class="left-column">
                <form method="POST" enctype="multipart/form-data" class="product-info-form" id="productForm">
                    <input type="hidden" name="action" value="update_product">
                    <!-- Basic Information -->
                    <div class="form-card">
                        <h3 class="card-title">Basic Information</h3>
                        <div class="form-fields">
                            <div class="form-group">
                                <label class="form-label">Product Name *</label>
                                <input type="text" class="form-input" name="product_name" 
                                       value="<?php echo htmlspecialchars($product['product_name']); ?>" 
                                       required minlength="3">
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Category *</label>
                                    <select class="form-input" name="category_id" required>
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['category_id']; ?>"
                                                <?php echo ($category['category_id'] == $product['category_id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($category['category_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Variant</label>
                                    <input type="text" class="form-input" name="variant" 
                                           value="<?php echo htmlspecialchars($product['variant']); ?>" 
                                           placeholder="e.g., 50ml">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Description *</label>
                                <textarea class="form-input" name="description" rows="4" 
                                          required minlength="10"><?php 
                                    echo htmlspecialchars($product['description']); 
                                ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Pricing & Supplier -->
                    <div class="form-card">
                        <h3 class="card-title">Pricing & Supplier</h3>
                        <div class="form-fields">
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Retail Price (₱) *</label>
                                    <input type="number" class="form-input" name="price" 
                                           step="0.01" min="0.01" value="<?php echo $product['price']; ?>" required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Supplier *</label>
                                    <select class="form-input" name="supplier_id" required>
                                        <option value="">Select Supplier</option>
                                        <?php foreach ($suppliers as $supplier): ?>
                                            <option value="<?php echo $supplier['supplier_id']; ?>"
                                                <?php echo ($supplier['supplier_id'] == $product['supplier_id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($supplier['supplier_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Product Image -->
                    <div class="form-card">
                        <h3 class="card-title">Product Image</h3>
                        <div class="form-fields">
                            <div class="current-image-display">
                                <img src="<?php echo '../' . htmlspecialchars($product['image_path']); ?>" 
                                     alt="Product image" class="product-preview" id="imagePreview">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Replace Image (Optional)</label>
                                <input type="file" class="form-input file-input" name="image_path" 
                                       accept="image/jpeg,image/jpg,image/png,image/gif" id="imageInput">
                                <small class="input-hint">Upload JPG, PNG, or GIF (Max 5MB). Leave empty to keep current image.</small>
                            </div>
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="form-card">
                        <h3 class="card-title">Product Status</h3>
                        <div class="form-fields">
                            <label class="checkbox-label">
                                <input type="checkbox" name="is_active" value="1" 
                                       <?php echo $product['is_active'] ? 'checked' : ''; ?>>
                                <span class="checkbox-text">Active (Visible in store)</span>
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn-lux btn-primary btn-full" id="saveProductBtn">
                        <i class="fas fa-save"></i>
                        <span>Save Changes</span>
                    </button>
                </form>
            </div>

            <!-- RIGHT COLUMN: Stock Management -->
            <div class="right-column">
                <!-- Current Stock Display -->
                <div class="stock-card">
                    <div class="stock-header">
                        <h3 class="card-title">Current Stock</h3>
                        <div class="stock-badge-large <?= $product['stock_quantity'] < 10 ? 'low-stock' : ''; ?>">
                            <i class="fas fa-cubes"></i>
                            <span class="stock-number"><?= $product['stock_quantity']; ?></span>
                            <span class="stock-label">Units</span>
                        </div>
                    </div>
                </div>

                <!-- Stock Adjustment Form -->
                <div class="stock-card">
                    <h3 class="card-title">Adjust Inventory</h3>
                    <form method="POST" class="stock-adjustment-form" id="stockForm">
                        <input type="hidden" name="action" value="adjust_stock">
                        <div class="adjustment-type-selector">
                            <label class="radio-card">
                                <input type="radio" name="adjustment_type" value="add" checked>
                                <div class="radio-content">
                                    <i class="fas fa-plus-circle"></i>
                                    <span>Add Stock</span>
                                </div>
                            </label>
                            <label class="radio-card">
                                <input type="radio" name="adjustment_type" value="remove">
                                <div class="radio-content">
                                    <i class="fas fa-minus-circle"></i>
                                    <span>Remove Stock</span>
                                </div>
                            </label>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Quantity *</label>
                            <input type="number" class="form-input" name="adjustment_quantity" 
                                   min="1" placeholder="Enter quantity" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Supplier *</label>
                            <select class="form-input" name="supplier_id_stock" required>
                                <option value="">Select Supplier</option>
                                <?php foreach ($suppliers as $supplier): ?>
                                    <option value="<?php echo $supplier['supplier_id']; ?>"
                                        <?php echo ($supplier['supplier_id'] == $product['supplier_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($supplier['supplier_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Supplier Price (₱) <span class="optional">Optional</span></label>
                            <input type="number" class="form-input" name="supplier_price_stock" 
                                   step="0.01" min="0" placeholder="0.00">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Remarks <span class="optional">Optional</span></label>
                            <textarea class="form-input" name="stock_remarks" rows="2" 
                                      placeholder="Reason for adjustment..."></textarea>
                        </div>

                        <button type="submit" class="btn-lux btn-primary btn-full" id="adjustStockBtn">
                            <i class="fas fa-sync-alt"></i>
                            <span>Apply Adjustment</span>
                        </button>
                    </form>
                </div>

                <!-- Stock History -->
                <div class="stock-card">
                    <h3 class="card-title">Recent Stock Changes</h3>
                    <div class="history-list">
                        <?php if (empty($history)): ?>
                            <p class="no-history">No stock history available</p>
                        <?php else: ?>
                            <?php foreach ($history as $log): ?>
                                <div class="history-item">
                                    <div class="history-icon <?= $log['quantity_added'] > 0 ? 'added' : 'removed'; ?>">
                                        <i class="fas <?= $log['quantity_added'] > 0 ? 'fa-arrow-up' : 'fa-arrow-down'; ?>"></i>
                                    </div>
                                    <div class="history-content">
                                        <div class="history-quantity">
                                            <?= $log['quantity_added'] > 0 ? '+' : ''; ?><?= $log['quantity_added']; ?> units
                                        </div>
                                        <div class="history-details">
                                            <?= htmlspecialchars($log['supplier_name']); ?> • 
                                            <?= date('M d, Y', strtotime($log['supply_date'])); ?>
                                        </div>
                                        <?php if ($log['remarks']): ?>
                                            <div class="history-remarks"><?= htmlspecialchars($log['remarks']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="history-remaining">
                                        <?= $log['quantity_remaining']; ?> left
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
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

.luxury-edit-container {
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

/* ===== GRID LAYOUT ===== */
.edit-grid {
    display: grid;
    grid-template-columns: 1fr 450px;
    gap: 40px;
}

/* ===== FORM CARDS ===== */
.form-card,
.stock-card {
    background: #ffffff;
    border: 1px solid rgba(0,0,0,0.08);
    padding: 40px;
    margin-bottom: 30px;
    transition: all 0.3s ease;
}

.form-card:hover,
.stock-card:hover {
    border-color: rgba(0,0,0,0.12);
    box-shadow: 0 10px 40px rgba(0,0,0,0.06);
}

.card-title {
    font-family: 'Playfair Display', serif;
    font-size: 24px;
    font-weight: 400;
    margin-bottom: 30px;
    color: #0a0a0a;
    padding-bottom: 20px;
    border-bottom: 1px solid rgba(0,0,0,0.06);
}

/* ===== FORM FIELDS ===== */
.form-fields {
    display: flex;
    flex-direction: column;
    gap: 25px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.form-label {
    font-size: 10px;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: #0a0a0a;
    font-weight: 600;
}

.form-input {
    padding: 15px 20px;
    border: 1px solid rgba(0,0,0,0.15);
    background: #fafafa;
    font-size: 13px;
    letter-spacing: 0.3px;
    color: #0a0a0a;
    font-family: 'Montserrat', sans-serif;
    transition: all 0.3s ease;
}

.form-input:focus {
    outline: none;
    border-color: #0a0a0a;
    background: #ffffff;
}

.form-input::placeholder {
    color: rgba(0,0,0,0.4);
}

textarea.form-input {
    resize: vertical;
    min-height: 100px;
}

.input-hint {
    font-size: 11px;
    color: rgba(0,0,0,0.5);
    letter-spacing: 0.3px;
}

.optional {
    font-size: 9px;
    color: rgba(0,0,0,0.4);
    font-weight: 400;
}

/* ===== IMAGE DISPLAY ===== */
.current-image-display {
    width: 100%;
    max-width: 300px;
    margin: 0 auto 25px;
    border: 1px solid rgba(0,0,0,0.08);
    overflow: hidden;
    transition: all 0.3s ease;
}

.current-image-display:hover {
    transform: scale(1.02);
    border-color: #0a0a0a;
    box-shadow: 0 8px 30px rgba(0,0,0,0.1);
}

.product-preview {
    width: 100%;
    height: auto;
    display: block;
}

/* ===== CHECKBOX ===== */
.checkbox-label {
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    padding: 15px 0;
}

.checkbox-label input[type="checkbox"] {
    width: 20px;
    height: 20px;
    cursor: pointer;
}

.checkbox-text {
    font-size: 13px;
    letter-spacing: 0.3px;
    color: #0a0a0a;
}

/* ===== BUTTONS ===== */
.btn-lux {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 16px 35px;
    border: 1px solid rgba(0,0,0,0.2);
    background: transparent;
    color: #0a0a0a;
    font-size: 10px;
    font-weight: 500;
    letter-spacing: 2.5px;
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

.btn-lux:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn-lux i,
.btn-lux span {
    position: relative;
    z-index: 1;
}

.btn-full {
    width: 100%;
}

/* ===== STOCK CARD ===== */
.stock-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
}

.stock-badge-large {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 25px 35px;
    background: #f0f8f4;
    border: 2px solid rgba(21,87,36,0.2);
    transition: all 0.3s ease;
}

.stock-badge-large.low-stock {
    background: #fef5f5;
    border-color: rgba(176,42,55,0.3);
}

.stock-badge-large i {
    font-size: 24px;
    color: #155724;
}

.stock-badge-large.low-stock i {
    color: #b02a37;
}

.stock-number {
    font-size: 36px;
    font-weight: 600;
    color: #0a0a0a;
    line-height: 1;
}

.stock-label {
    font-size: 10px;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: rgba(0,0,0,0.5);
}

/* ===== ADJUSTMENT TYPE SELECTOR ===== */
.adjustment-type-selector {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-bottom: 25px;
}

.radio-card {
    cursor: pointer;
}

.radio-card input[type="radio"] {
    display: none;
}

.radio-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    padding: 20px;
    border: 2px solid rgba(0,0,0,0.1);
    background: #fafafa;
    transition: all 0.3s ease;
}

.radio-content i {
    font-size: 28px;
    color: rgba(0,0,0,0.4);
    transition: all 0.3s ease;
}

.radio-content span {
    font-size: 11px;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    font-weight: 500;
    color: rgba(0,0,0,0.6);
    transition: all 0.3s ease;
}

.radio-card input[type="radio"]:checked + .radio-content {
    border-color: #0a0a0a;
    background: #ffffff;
}

.radio-card input[type="radio"]:checked + .radio-content i {
    color: #0a0a0a;
    transform: scale(1.1);
}

.radio-card input[type="radio"]:checked + .radio-content span {
    color: #0a0a0a;
}

.radio-content:hover {
    border-color: rgba(0,0,0,0.2);
}

/* ===== HISTORY LIST ===== */
.history-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.history-item {
    display: grid;
    grid-template-columns: 40px 1fr auto;
    gap: 15px;
    align-items: center;
    padding: 15px;
    background: #fafafa;
    border-left: 3px solid transparent;
    transition: all 0.3s ease;
}

.history-item:hover {
    background: #f5f5f5;
    border-left-color: #0a0a0a;
}

.history-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid rgba(0,0,0,0.1);
    font-size: 14px;
}

.history-icon.added {
    background: #f0f8f4;
    color: #155724;
    border-color: rgba(21,87,36,0.2);
}

.history-icon.removed {
    background: #fef5f5;
    color: #b02a37;
    border-color: rgba(176,42,55,0.2);
}

.history-content {
    flex: 1;
}

.history-quantity {
    font-weight: 600;
    font-size: 14px;
    color: #0a0a0a;
    margin-bottom: 4px;
}

.history-details {
    font-size: 11px;
    color: rgba(0,0,0,0.5);
    letter-spacing: 0.3px;
}

.history-remarks {
    font-size: 11px;
    color: rgba(0,0,0,0.6);
    margin-top: 6px;
    font-style: italic;
}

.history-remaining {
    font-size: 12px;
    font-weight: 500;
    color: rgba(0,0,0,0.6);
    text-align: right;
}

.no-history {
    text-align: center;
    padding: 40px 20px;
    color: rgba(0,0,0,0.4);
    font-size: 12px;
    letter-spacing: 1px;
    text-transform: uppercase;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 1200px) {
    .edit-grid {
        grid-template-columns: 1fr 400px;
        gap: 30px;
    }

    .lux-admin-title {
        font-size: 40px;
    }
}

@media (max-width: 968px) {
    .admin-main {
        padding: 40px 20px;
    }

    .edit-grid {
        grid-template-columns: 1fr;
        gap: 40px;
    }

    .right-column {
        order: -1;
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

    .btn-back {
        width: 100%;
        justify-content: center;
    }

    .form-card,
    .stock-card {
        padding: 30px 25px;
    }

    .stock-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .stock-badge-large {
        width: 100%;
        flex-direction: row;
        justify-content: space-around;
    }
}

@media (max-width: 768px) {
    .page-header-lux {
        margin-bottom: 40px;
        padding-bottom: 30px;
    }

    .lux-admin-title {
        font-size: 28px;
    }

    .lux-admin-subtitle {
        font-size: 10px;
        letter-spacing: 2px;
    }

    .form-row {
        grid-template-columns: 1fr;
    }

    .adjustment-type-selector {
        grid-template-columns: 1fr;
    }

    .history-item {
        grid-template-columns: 40px 1fr;
        gap: 12px;
    }

    .history-remaining {
        grid-column: 2;
        text-align: left;
        margin-top: 8px;
    }
}

@media (max-width: 480px) {
    .lux-admin-title {
        font-size: 24px;
    }

    .form-card,
    .stock-card {
        padding: 25px 20px;
    }

    .card-title {
        font-size: 20px;
    }
}
</style>

<script>
// Auto-dismiss alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alert = document.getElementById('alertMessage');
    if (alert) {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateX(-20px)';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
        
        // Scroll to alert
        alert.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
});

// Preview new image before upload
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('imageInput');
    const imagePreview = document.getElementById('imagePreview');

    if (fileInput && imagePreview) {
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Validate file size (5MB)
                if (file.size > 5242880) {
                    alert('File size is too large. Maximum size is 5MB.');
                    this.value = '';
                    return;
                }
                
                // Validate file type
                const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!validTypes.includes(file.type)) {
                    alert('Invalid file type. Only JPG, PNG, and GIF are allowed.');
                    this.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(event) {
                    imagePreview.src = event.target.result;
                    imagePreview.parentElement.style.borderColor = '#0a0a0a';
                };
                reader.readAsDataURL(file);
            }
        });
    }
});

// Stock adjustment form validation
document.addEventListener('DOMContentLoaded', function() {
    const stockForm = document.getElementById('stockForm');
    
    if (stockForm) {
        stockForm.addEventListener('submit', function(e) {
            const adjustmentType = document.querySelector('input[name="adjustment_type"]:checked').value;
            const quantityInput = document.querySelector('input[name="adjustment_quantity"]');
            const quantity = parseInt(quantityInput.value);
            const currentStock = <?= $product['stock_quantity']; ?>;
            
            // Validate quantity
            if (isNaN(quantity) || quantity <= 0) {
                e.preventDefault();
                alert('Please enter a valid quantity greater than 0.');
                quantityInput.focus();
                return false;
            }

            if (adjustmentType === 'remove' && quantity > currentStock) {
                e.preventDefault();
                alert(`Cannot remove ${quantity} units. Current stock: ${currentStock} units.`);
                quantityInput.focus();
                return false;
            }

            // Calculate new stock
            const newStock = adjustmentType === 'add' ? 
                currentStock + quantity : 
                currentStock - quantity;

            const action = adjustmentType === 'add' ? 'add' : 'remove';
            const confirmed = confirm(
                `Are you sure you want to ${action} ${quantity} units?\n\n` +
                `Current Stock: ${currentStock}\n` +
                `New Stock: ${newStock}`
            );

            if (!confirmed) {
                e.preventDefault();
                return false;
            }
        });
    }
});

// Highlight adjustment type selection
document.addEventListener('DOMContentLoaded', function() {
    const radioCards = document.querySelectorAll('.radio-card');
    
    radioCards.forEach(card => {
        card.addEventListener('click', function() {
            const radio = this.querySelector('input[type="radio"]');
            if (radio) {
                radio.checked = true;
                // Trigger change event for any listeners
                radio.dispatchEvent(new Event('change'));
            }
        });
    });
});

// Product form validation
document.addEventListener('DOMContentLoaded', function() {
    const productForm = document.getElementById('productForm');
    
    if (productForm) {
        productForm.addEventListener('submit', function(e) {
            const productName = document.querySelector('input[name="product_name"]').value.trim();
            const price = parseFloat(document.querySelector('input[name="price"]').value);
            const description = document.querySelector('textarea[name="description"]').value.trim();
            
            if (productName.length < 3) {
                e.preventDefault();
                alert('Product name must be at least 3 characters long.');
                return false;
            }
            
            if (price <= 0) {
                e.preventDefault();
                alert('Price must be greater than 0.');
                return false;
            }
            
            if (description.length < 10) {
                e.preventDefault();
                alert('Description must be at least 10 characters long.');
                return false;
            }
            
            // Show confirmation
            const confirmed = confirm('Are you sure you want to save these changes?');
            if (!confirmed) {
                e.preventDefault();
                return false;
            }
        });
    }
});

// Form field validation feedback
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
        
        inputs.forEach(input => {
            input.addEventListener('invalid', function(e) {
                e.preventDefault();
                this.style.borderColor = '#b02a37';
                this.style.background = '#fef5f5';
                
                setTimeout(() => {
                    this.style.borderColor = '';
                    this.style.background = '';
                }, 3000);
            });

            input.addEventListener('input', function() {
                if (this.validity.valid) {
                    this.style.borderColor = '#155724';
                    this.style.background = '#f0f8f4';
                    
                    setTimeout(() => {
                        this.style.borderColor = '';
                        this.style.background = '';
                    }, 1000);
                }
            });
        });
    });
});

// Loading state for buttons
document.addEventListener('DOMContentLoaded', function() {
    const saveProductBtn = document.getElementById('saveProductBtn');
    const adjustStockBtn = document.getElementById('adjustStockBtn');
    
    if (saveProductBtn) {
        const productForm = document.getElementById('productForm');
        productForm.addEventListener('submit', function(e) {
            if (productForm.checkValidity()) {
                const originalHTML = saveProductBtn.innerHTML;
                saveProductBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Saving...</span>';
                saveProductBtn.disabled = true;
                
                // Re-enable after 15 seconds as fallback
                setTimeout(() => {
                    saveProductBtn.innerHTML = originalHTML;
                    saveProductBtn.disabled = false;
                }, 15000);
            }
        });
    }
    
    if (adjustStockBtn) {
        const stockForm = document.getElementById('stockForm');
        stockForm.addEventListener('submit', function(e) {
            if (stockForm.checkValidity()) {
                const originalHTML = adjustStockBtn.innerHTML;
                adjustStockBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Processing...</span>';
                adjustStockBtn.disabled = true;
                
                // Re-enable after 15 seconds as fallback
                setTimeout(() => {
                    adjustStockBtn.innerHTML = originalHTML;
                    adjustStockBtn.disabled = false;
                }, 15000);
            }
        });
    }
});

// Prevent form resubmission on page refresh
if (window.history.replaceState) {
    window.history.replaceState(null, null, window.location.href);
}

// Real-time price formatting
document.addEventListener('DOMContentLoaded', function() {
    const priceInputs = document.querySelectorAll('input[type="number"][step="0.01"]');
    
    priceInputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.value) {
                this.value = parseFloat(this.value).toFixed(2);
            }
        });
    });
});

// Show unsaved changes warning
let formChanged = false;

document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        const inputs = form.querySelectorAll('input, select, textarea');
        
        inputs.forEach(input => {
            input.addEventListener('change', function() {
                formChanged = true;
            });
        });
        
        form.addEventListener('submit', function() {
            formChanged = false;
        });
    });
});

window.addEventListener('beforeunload', function(e) {
    if (formChanged) {
        e.preventDefault();
        e.returnValue = '';
        return '';
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>

<?php ob_end_flush(); ?>
