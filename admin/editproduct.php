<?php
require_once '../includes/config.php';
require_once '../includes/adminheader.php';

// Initialize variables
$message = '';
$messageType = '';

// Get product ID from URL
$product_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$product_id) {
    header('Location: manageproducts.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // Update product information
        $stmt = $pdo->prepare("
            UPDATE products SET 
            product_name = ?,
            category_id = ?,
            supplier_id = ?,
            description = ?,
            price = ?,
            stock_quantity = ?,
            variant = ?,
            is_active = ?
            WHERE product_id = ?
        ");

        $stmt->execute([
            $_POST['product_name'],
            $_POST['category_id'],
            $_POST['supplier_id'],
            $_POST['description'],
            $_POST['price'],
            $_POST['stock_quantity'],
            $_POST['variant'],
            isset($_POST['is_active']) ? 1 : 0,
            $product_id
        ]);

        // Handle image upload if new image is provided
        if (!empty($_FILES['image_path']['name'])) {
            // Get current image path
            $stmt = $pdo->prepare("SELECT image_path FROM products WHERE product_id = ?");
            $stmt->execute([$product_id]);
            $current_image = $stmt->fetchColumn();

            // Delete old image
            if ($current_image && file_exists("../" . $current_image)) {
                unlink("../" . $current_image);
            }

            // Upload new image
            $image_name = time() . '_' . basename($_FILES['image_path']['name']);
            $target_path = "assets/images/perfumes/" . $image_name;
            
            if (move_uploaded_file($_FILES['image_path']['tmp_name'], "../" . $target_path)) {
                $stmt = $pdo->prepare("UPDATE products SET image_path = ? WHERE product_id = ?");
                $stmt->execute([$target_path, $product_id]);
            }
        }

        // Add to supply logs if stock quantity is changed
        if ($_POST['stock_quantity'] != $_POST['original_stock']) {
            $quantity_change = $_POST['stock_quantity'] - $_POST['original_stock'];
            if ($quantity_change != 0) {
                $stmt = $pdo->prepare("
                    INSERT INTO supply_logs (
                        product_id, supplier_id, quantity_added, quantity_remaining,
                        supplier_price, supply_date, remarks
                    ) VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $product_id,
                    $_POST['supplier_id'],
                    abs($quantity_change),
                    $_POST['stock_quantity'],
                    $_POST['supplier_price'] ?? 0.00,
                    date('Y-m-d'),
                    "Stock adjustment through product edit"
                ]);
            }
        }

        $pdo->commit();
        $message = "Product updated successfully.";
        $messageType = "success";
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "Error updating product: " . $e->getMessage();
        $messageType = "danger";
    }
}

// Fetch product data
$stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: manageproducts.php');
    exit();
}

// Fetch categories and suppliers
$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
$suppliers = $pdo->query("SELECT * FROM suppliers")->fetchAll();
?>

<div class="admin-main">
    <div class="product-form-container">
        <div class="form-header">
            <h2>Edit Product</h2>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="edit-product-form">
            <!-- Product Information Section -->
            <div class="form-section">
                <h3 class="section-title">Basic Information</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="product_name" class="form-label">Product Name</label>
                        <input type="text" class="form-control" id="product_name" name="product_name" 
                               value="<?php echo htmlspecialchars($product['product_name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="category_id" class="form-label">Category</label>
                        <select class="form-control" id="category_id" name="category_id" required>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['category_id']; ?>"
                                    <?php echo ($category['category_id'] == $product['category_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="variant" class="form-label">Variant</label>
                        <input type="text" class="form-control" id="variant" name="variant" 
                               value="<?php echo htmlspecialchars($product['variant']); ?>" 
                               placeholder="e.g., 50ml, 100ml">
                    </div>

                    <div class="form-group full-width">
                        <label for="description" class="form-label">Product Description</label>
                        <textarea class="form-control" id="description" name="description" rows="4" required><?php 
                            echo htmlspecialchars($product['description']); 
                        ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Pricing & Stock Section -->
            <div class="form-section">
                <h3 class="section-title">Pricing & Stock Information</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="price" class="form-label">Retail Price (₱)</label>
                        <input type="number" class="form-control" id="price" name="price" 
                               step="0.01" min="0" value="<?php echo $product['price']; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="stock_quantity" class="form-label">Current Stock</label>
                        <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" 
                               min="0" value="<?php echo $product['stock_quantity']; ?>" required>
                        <input type="hidden" name="original_stock" 
                               value="<?php echo $product['stock_quantity']; ?>">
                    </div>

                    <div class="form-group">
                        <label for="supplier_id" class="form-label">Supplier</label>
                        <select class="form-control" id="supplier_id" name="supplier_id" required>
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

            <!-- Image Section -->
            <div class="form-section">
                <h3 class="section-title">Product Image</h3>
                <div class="form-grid">
                    <div class="form-group full-width">
                        <div class="current-image">
                            <label class="form-label">Current Image</label>
                            <img src="<?php echo '../' . htmlspecialchars($product['image_path']); ?>" 
                                 alt="Current product image" class="preview-image">
                        </div>
                        <label for="image_path" class="form-label">New Image (optional)</label>
                        <div class="file-upload">
                            <input type="file" class="form-control" id="image_path" 
                                   name="image_path" accept="image/*">
                        </div>
                        <small class="text-muted">Upload a new image only if you want to replace the current one</small>
                    </div>
                </div>
            </div>

            <!-- Additional Settings Section -->
            <div class="form-section">
                <h3 class="section-title">Additional Settings</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="supplier_price" class="form-label">Supplier Price (₱)</label>
                        <input type="number" class="form-control" id="supplier_price" name="supplier_price" 
                               step="0.01" min="0" placeholder="0.00">
                    </div>

                    <div class="form-group">
                        <label class="form-label status-toggle">
                            <input type="checkbox" name="is_active" value="1" 
                                   <?php echo $product['is_active'] ? 'checked' : ''; ?>>
                            Active Status
                        </label>
                        <small class="text-muted">Inactive products won't be visible in the store</small>
                    </div>
                </div>
            </div>

            <div class="btn-container">
                <button type="submit" class="btn btn-primary">Update Product</button>
                <a href="manageproducts.php" class="btn btn-primary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>