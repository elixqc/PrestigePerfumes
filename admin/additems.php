<?php
include('../includes/config.php');
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: ../admin/login.php");
    exit();
}

include('../includes/adminheader.php');

$success_message = '';
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $product_name = $_POST['product_name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $stock_quantity = $_POST['stock_quantity'];
    $category_id = $_POST['category_id'];
    $supplier_id = $_POST['supplier_id'];
    $supplier_price = $_POST['supplier_price'];
    $supply_date = $_POST['supply_date'] ?? date('Y-m-d');
    $remarks = $_POST['remarks'] ?: "Initial stock addition for new product";
    
    // Start transaction
    $conn->begin_transaction();
    
    // Validate inputs
    if (!is_numeric($price) || $price < 0) {
        throw new Exception("Invalid price amount");
    }
    if (!is_numeric($stock_quantity) || $stock_quantity < 0) {
        throw new Exception("Invalid stock quantity");
    }
    if (!is_numeric($supplier_price) || $supplier_price < 0) {
        throw new Exception("Invalid supplier price");
    }

    try {
        // Handle file upload
        $target_dir = "../assets/images/perfumes/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        // Generate a unique filename to prevent overwriting
        $file_extension = strtolower(pathinfo($_FILES["image_path"]["name"], PATHINFO_EXTENSION));
        $unique_filename = uniqid('perfume_', true) . '.' . $file_extension;
        
        // Check if image file is actual image
        if(isset($_POST["submit"])) {
            $check = getimagesize($_FILES["image_path"]["tmp_name"]);
            if($check !== false) {
                // Verify file type
                if(!in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                    throw new Exception("Only JPG, JPEG, PNG & GIF files are allowed.");
                }
                
                // Upload file
                if (move_uploaded_file($_FILES["image_path"]["tmp_name"], $target_file)) {
                    $image_path = "assets/images/perfumes/" . $unique_filename;
                    
                    // Get the variant from POST or set default
                    $variant = isset($_POST['variant']) ? $_POST['variant'] : null;
                    $is_active = 1; // New products are active by default

                    // Insert into products table
                    $sql = "INSERT INTO products (product_name, description, category_id, supplier_id, price, 
                            stock_quantity, image_path, variant, is_active) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ssiiidssi", $product_name, $description, $category_id, $supplier_id, 
                                    $price, $stock_quantity, $image_path, $variant, $is_active);
                    
                    if ($stmt->execute()) {
                        $product_id = $conn->insert_id;
                        
                        // Insert into supply_logs table with all required fields
                        $supply_sql = "INSERT INTO supply_logs (product_id, supplier_id, quantity_added, 
                                     quantity_remaining, supplier_price, supply_date, remarks) 
                                     VALUES (?, ?, ?, ?, ?, ?, ?)";
                        $supply_stmt = $conn->prepare($supply_sql);
                        $supply_stmt->bind_param("iiiddss", $product_id, $supplier_id, $stock_quantity, 
                                               $stock_quantity, $supplier_price, $supply_date, $remarks);
                        
                        if ($supply_stmt->execute()) {
                            $conn->commit();
                            $success_message = "Product added successfully and supply log recorded!";
                        } else {
                            throw new Exception("Error recording supply log");
                        }
                    } else {
                        throw new Exception("Error adding product");
                    }
                } else {
                    throw new Exception("Error uploading image");
                }
            } else {
                throw new Exception("File is not an image");
            }
        }
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = $e->getMessage();
    }
}

// Fetch categories for the dropdown
$categories = [];
$category_query = "SELECT category_id, category_name FROM categories";
$result = $conn->query($category_query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Fetch suppliers for the dropdown
$suppliers = [];
$supplier_query = "SELECT supplier_id, supplier_name FROM suppliers WHERE is_active = 1";
$result = $conn->query($supplier_query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $suppliers[] = $row;
    }
}
?>

<style>
.product-form-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 40px;
    background: #fff;
    box-shadow: 0 0 40px rgba(0,0,0,0.03);
    border-radius: 8px;
}

.page-header {
    text-align: center;
    margin-bottom: 50px;
    padding-bottom: 30px;
    border-bottom: 1px solid rgba(0,0,0,0.05);
}

.page-title {
    font-family: "Playfair Display", serif;
    font-size: 2.5rem;
    font-weight: 300;
    color: #1a1a1a;
    margin-bottom: 10px;
    letter-spacing: 1px;
}

.page-subtitle {
    color: #666;
    font-size: 1rem;
    font-weight: 300;
    letter-spacing: 1px;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 30px;
    margin-bottom: 40px;
}

.form-group {
    margin-bottom: 0;
}

.form-group.full-width {
    grid-column: 1 / -1;
}

.form-label {
    display: block;
    margin-bottom: 8px;
    font-size: 0.85rem;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: #333;
}

.form-control {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid rgba(0,0,0,0.1);
    border-radius: 4px;
    font-size: 0.95rem;
    transition: all 0.3s ease;
    background: #fff;
}

.form-control:focus {
    outline: none;
    border-color: #c5a253;
    box-shadow: 0 0 0 3px rgba(197, 162, 83, 0.1);
}

textarea.form-control {
    min-height: 120px;
    resize: vertical;
}

.file-upload {
    position: relative;
    margin-top: 10px;
}

.file-upload .form-control {
    padding: 12px;
    background: #f8f9fa;
}

.btn-container {
    display: flex;
    justify-content: center;
    gap: 15px;
    margin-top: 40px;
    padding-top: 30px;
    border-top: 1px solid rgba(0,0,0,0.05);
}

.btn {
    padding: 14px 35px;
    font-size: 0.85rem;
    letter-spacing: 2px;
    text-transform: uppercase;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: 500;
}

.btn-primary {
    background: #1a1a1a;
    color: #fff;
}

.btn-primary:hover {
    background: #c5a253;
    transform: translateY(-1px);
}

.btn-secondary {
    background: #f8f9fa;
    color: #1a1a1a;
}

.btn-secondary:hover {
    background: #e9ecef;
}

.alert {
    text-align: center;
    padding: 15px 20px;
    margin-bottom: 30px;
    border-radius: 4px;
    font-size: 0.9rem;
    letter-spacing: 0.5px;
}

.alert-success {
    background-color: rgba(25, 135, 84, 0.1);
    border: 1px solid rgba(25, 135, 84, 0.2);
    color: #198754;
}

.alert-danger {
    background-color: rgba(220, 53, 69, 0.1);
    border: 1px solid rgba(220, 53, 69, 0.2);
    color: #dc3545;
}

/* Section Dividers */
.form-section {
    margin-bottom: 40px;
}

.section-title {
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 2px;
    color: #c5a253;
    margin-bottom: 20px;
    font-weight: 600;
}

/* Custom Select Styling */
select.form-control {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%23333' viewBox='0 0 16 16'%3E%3Cpath d='M8 11L3 6h10l-5 5z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 15px center;
    padding-right: 40px;
}

@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .product-form-container {
        padding: 20px;
    }
    
    .page-title {
        font-size: 2rem;
    }
}
</style>

<div class="product-form-container">
    <div class="page-header">
        <h2 class="page-title">Add New Product</h2>
        <p class="page-subtitle">Create a new fragrance entry in your collection</p>
    </div>
    
    <?php if ($success_message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="product-form">
        <!-- Product Information Section -->
        <div class="form-section">
            <h3 class="section-title">Product Information</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label for="product_name" class="form-label">Product Name</label>
                    <input type="text" class="form-control" id="product_name" name="product_name" placeholder="Enter product name" required>
                </div>

                <div class="form-group">
                    <label for="category_id" class="form-label">Category</label>
                    <select class="form-control" id="category_id" name="category_id" required>
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category['category_id']); ?>">
                                <?php echo htmlspecialchars($category['category_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="variant" class="form-label">Variant</label>
                    <input type="text" class="form-control" id="variant" name="variant" 
                           placeholder="e.g., 50ml, 100ml">
                </div>

                <div class="form-group full-width">
                    <label for="description" class="form-label">Product Description</label>
                    <textarea class="form-control" id="description" name="description" 
                              placeholder="Enter a detailed description of the fragrance, including its notes and character" 
                              rows="4" required></textarea>
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
                           step="0.01" min="0" placeholder="0.00" required>
                </div>

                <div class="form-group">
                    <label for="supplier_price" class="form-label">Supply Price (₱)</label>
                    <input type="number" class="form-control" id="supplier_price" 
                           name="supplier_price" step="0.01" min="0" placeholder="0.00" required>
                </div>

                <div class="form-group">
                    <label for="stock_quantity" class="form-label">Initial Stock</label>
                    <input type="number" class="form-control" id="stock_quantity" 
                           name="stock_quantity" min="0" placeholder="0" required>
                </div>

                <div class="form-group">
                    <label for="supplier_id" class="form-label">Supplier</label>
                    <select class="form-control" id="supplier_id" name="supplier_id" required>
                        <option value="">Select Supplier</option>
                        <?php foreach ($suppliers as $supplier): ?>
                            <option value="<?php echo htmlspecialchars($supplier['supplier_id']); ?>">
                                <?php echo htmlspecialchars($supplier['supplier_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Supply Details Section -->
        <div class="form-section">
            <h3 class="section-title">Supply Details</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label for="supply_date" class="form-label">Supply Date</label>
                    <input type="date" class="form-control" id="supply_date" 
                           name="supply_date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div class="form-group">
                    <label for="remarks" class="form-label">Remarks</label>
                    <input type="text" class="form-control" id="remarks" name="remarks" 
                           placeholder="Optional notes about this supply">
                </div>

                <div class="form-group full-width">
                    <label for="image_path" class="form-label">Product Image</label>
                    <div class="file-upload">
                        <input type="file" class="form-control" id="image_path" 
                               name="image_path" accept="image/*" required>
                    </div>
                    <small class="text-muted">Upload a high-quality image of the product (Recommended: 1200x1200px)</small>
                </div>
            </div>
        </div>

        <div class="btn-container">
            <button type="submit" name="submit" class="btn btn-primary">Add Product</button>
            <a href="products.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php include('../includes/footer.php'); ?>