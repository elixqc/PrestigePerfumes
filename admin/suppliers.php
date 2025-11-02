<?php
ob_start();
require_once '../includes/config.php';
require_once '../includes/adminheader.php';

// Make sure PDO throws exceptions
if ($pdo && $pdo->getAttribute(PDO::ATTR_ERRMODE) !== PDO::ERRMODE_EXCEPTION) {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}

$message = '';
$messageType = '';

// ---------- HANDLE ADD SUPPLIER ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_supplier') {
    try {
        $supplier_name = trim($_POST['supplier_name'] ?? '');
        $contact_person = trim($_POST['contact_person'] ?? '');
        $contact_number = trim($_POST['contact_number'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        // Validation
        if (strlen($supplier_name) < 3) {
            throw new Exception('Supplier name must be at least 3 characters.');
        }

        $stmt = $pdo->prepare("
            INSERT INTO suppliers (supplier_name, contact_person, contact_number, address, is_active)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $supplier_name,
            $contact_person ?: null,
            $contact_number ?: null,
            $address ?: null,
            $is_active
        ]);

        $message = "Supplier added successfully!";
        $messageType = "success";
    } catch (Exception $e) {
        error_log("Add Supplier error: " . $e->getMessage());
        $message = "Error adding supplier: " . $e->getMessage();
        $messageType = "danger";
    }
}

// ---------- HANDLE UPDATE SUPPLIER ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_supplier') {
    try {
        $supplier_id = (int)($_POST['supplier_id'] ?? 0);
        $supplier_name = trim($_POST['supplier_name'] ?? '');
        $contact_person = trim($_POST['contact_person'] ?? '');
        $contact_number = trim($_POST['contact_number'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if (strlen($supplier_name) < 3) {
            throw new Exception('Supplier name must be at least 3 characters.');
        }

        $stmt = $pdo->prepare("
            UPDATE suppliers SET
                supplier_name = ?,
                contact_person = ?,
                contact_number = ?,
                address = ?,
                is_active = ?
            WHERE supplier_id = ?
        ");
        $stmt->execute([
            $supplier_name,
            $contact_person ?: null,
            $contact_number ?: null,
            $address ?: null,
            $is_active,
            $supplier_id
        ]);

        $message = "Supplier updated successfully!";
        $messageType = "success";
    } catch (Exception $e) {
        error_log("Update Supplier error: " . $e->getMessage());
        $message = "Error updating supplier: " . $e->getMessage();
        $messageType = "danger";
    }
}

// ---------- HANDLE DELETE SUPPLIER ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_supplier') {
    try {
        $supplier_id = (int)($_POST['supplier_id'] ?? 0);

        // Check if supplier has products
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE supplier_id = ?");
        $stmt->execute([$supplier_id]);
        $product_count = $stmt->fetchColumn();

        if ($product_count > 0) {
            throw new Exception("Cannot delete supplier with existing products. Please reassign products first.");
        }

        $stmt = $pdo->prepare("DELETE FROM suppliers WHERE supplier_id = ?");
        $stmt->execute([$supplier_id]);

        $message = "Supplier deleted successfully!";
        $messageType = "success";
    } catch (Exception $e) {
        error_log("Delete Supplier error: " . $e->getMessage());
        $message = "Error deleting supplier: " . $e->getMessage();
        $messageType = "danger";
    }
}

// ---------- FETCH SUPPLIERS ----------
try {
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
    
    $query = "
        SELECT s.*, 
               COUNT(DISTINCT p.product_id) as product_count,
               COUNT(DISTINCT sl.supply_id) as supply_count
        FROM suppliers s
        LEFT JOIN products p ON s.supplier_id = p.supplier_id
        LEFT JOIN supply_logs sl ON s.supplier_id = sl.supplier_id
        WHERE 1=1
    ";
    
    if (!empty($search)) {
        $query .= " AND (s.supplier_name LIKE :search OR s.contact_person LIKE :search)";
    }
    
    if ($status_filter === 'active') {
        $query .= " AND s.is_active = 1";
    } elseif ($status_filter === 'inactive') {
        $query .= " AND s.is_active = 0";
    }
    
    $query .= " GROUP BY s.supplier_id ORDER BY s.supplier_name ASC";
    
    $stmt = $pdo->prepare($query);
    
    if (!empty($search)) {
        $stmt->bindValue(':search', '%' . $search . '%');
    }
    
    $stmt->execute();
    $suppliers = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Fetch Suppliers error: " . $e->getMessage());
    $message = "Error loading suppliers: " . $e->getMessage();
    $messageType = "danger";
    $suppliers = [];
}
?>

<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600&family=Playfair+Display:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="admin-main">
    <div class="luxury-container">
        <!-- Page Header -->
        <div class="page-header-lux">
            <div class="header-content">
                <h1 class="lux-admin-title">Supplier Management</h1>
                <p class="lux-admin-subtitle">Manage Your Supply Network</p>
            </div>
            <div class="header-actions">
                <button class="btn-lux btn-primary" id="addSupplierBtn">
                    <i class="fas fa-plus"></i>
                    <span>Add Supplier</span>
                </button>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert-lux alert-<?php echo $messageType; ?>" id="alertMessage">
                <span class="alert-icon"><?= $messageType === 'success' ? '✓' : '✕'; ?></span>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Search & Filter Bar -->
        <div class="filter-bar">
            <form method="GET" class="filter-form">
                <div class="search-group">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" placeholder="Search suppliers..." 
                           value="<?php echo htmlspecialchars($search); ?>" class="search-input">
                </div>
                
                <div class="filter-group">
                    <select name="status" class="filter-select" onchange="this.form.submit()">
                        <option value="all" <?= $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="active" <?= $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?= $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>

                <button type="submit" class="btn-lux btn-filter">
                    <i class="fas fa-filter"></i>
                    <span>Filter</span>
                </button>

                <?php if (!empty($search) || $status_filter !== 'all'): ?>
                    <a href="suppliers.php" class="btn-lux btn-clear">
                        <i class="fas fa-times"></i>
                        <span>Clear</span>
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Results Info -->
        <div class="results-info">
            <p>Showing <strong><?php echo count($suppliers); ?></strong> supplier<?php echo count($suppliers) !== 1 ? 's' : ''; ?></p>
            <?php if (!empty($search) || $status_filter !== 'all'): ?>
                <p class="active-filters">Active filters applied</p>
            <?php endif; ?>
        </div>

        <!-- Suppliers Table -->
        <div class="table-card">
            <table class="luxury-table">
                <thead>
                    <tr>
                        <th>Supplier Name</th>
                        <th>Contact Person</th>
                        <th>Contact Number</th>
                        <th>Address</th>
                        <th>Products</th>
                        <th>Supplies</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($suppliers)): ?>
                        <tr>
                            <td colspan="8" class="empty-state">
                                <i class="fas fa-box-open"></i>
                                <p>No suppliers found</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($suppliers as $supplier): ?>
                            <tr class="supplier-row">
                                <td class="supplier-name-cell">
                                    <div class="supplier-name">
                                        <i class="fas fa-truck"></i>
                                        <strong><?php echo htmlspecialchars($supplier['supplier_name']); ?></strong>
                                    </div>
                                </td>
                                <td class="contact-person-cell">
                                    <?php echo htmlspecialchars($supplier['contact_person'] ?? '-'); ?>
                                </td>
                                <td class="contact-number-cell">
                                    <?php echo htmlspecialchars($supplier['contact_number'] ?? '-'); ?>
                                </td>
                                <td class="address-cell">
                                    <span class="address-text" title="<?php echo htmlspecialchars($supplier['address'] ?? ''); ?>">
                                        <?php echo htmlspecialchars($supplier['address'] ?? '-'); ?>
                                    </span>
                                </td>
                                <td class="products-cell">
                                    <span class="badge badge-info">
                                        <?php echo $supplier['product_count']; ?>
                                    </span>
                                </td>
                                <td class="supplies-cell">
                                    <span class="badge badge-secondary">
                                        <?php echo $supplier['supply_count']; ?>
                                    </span>
                                </td>
                                <td class="status-cell">
                                    <span class="status-badge-lux <?php echo $supplier['is_active'] ? 'active' : 'inactive'; ?>">
                                        <?php echo $supplier['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td class="actions-cell">
                                    <button class="btn-lux btn-view" 
                                            onclick="editSupplier(<?php echo htmlspecialchars(json_encode($supplier)); ?>)"
                                            title="Edit Supplier">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn-lux btn-delete" 
                                            onclick="deleteSupplier(<?php echo $supplier['supplier_id']; ?>, '<?php echo htmlspecialchars($supplier['supplier_name']); ?>')"
                                            title="Delete Supplier">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add/Edit Supplier Modal -->
<div class="modal" id="supplierModal">
    <div class="modal-overlay" onclick="closeModal()"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title" id="modalTitle">Add Supplier</h2>
            <button class="modal-close" onclick="closeModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form method="POST" id="supplierForm" class="modal-form">
            <input type="hidden" name="action" id="formAction" value="add_supplier">
            <input type="hidden" name="supplier_id" id="supplierId">
            
            <div class="form-group">
                <label class="form-label">Supplier Name *</label>
                <input type="text" class="form-input" name="supplier_name" id="supplierName" 
                       required minlength="3" placeholder="Enter supplier name">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Contact Person</label>
                    <input type="text" class="form-input" name="contact_person" id="contactPerson" 
                           placeholder="Enter contact person">
                </div>

                <div class="form-group">
                    <label class="form-label">Contact Number</label>
                    <input type="text" class="form-input" name="contact_number" id="contactNumber" 
                           placeholder="e.g., 09171234567">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Address</label>
                <textarea class="form-input" name="address" id="address" rows="3" 
                          placeholder="Enter complete address"></textarea>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="is_active" id="isActive" value="1" checked>
                    <span class="checkbox-text">Active Supplier</span>
                </label>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-lux btn-secondary" onclick="closeModal()">
                    <span>Cancel</span>
                </button>
                <button type="submit" class="btn-lux btn-primary" id="submitBtn">
                    <i class="fas fa-save"></i>
                    <span>Save Supplier</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal" id="deleteModal">
    <div class="modal-overlay" onclick="closeDeleteModal()"></div>
    <div class="modal-content modal-small">
        <div class="modal-header">
            <h2 class="modal-title">Confirm Deletion</h2>
            <button class="modal-close" onclick="closeDeleteModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form method="POST" id="deleteForm">
            <input type="hidden" name="action" value="delete_supplier">
            <input type="hidden" name="supplier_id" id="deleteId">
            
            <div class="delete-message">
                <i class="fas fa-exclamation-triangle"></i>
                <p>Are you sure you want to delete <strong id="deleteName"></strong>?</p>
                <p class="warning-text">This action cannot be undone.</p>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-lux btn-secondary" onclick="closeDeleteModal()">
                    <span>Cancel</span>
                </button>
                <button type="submit" class="btn-lux btn-danger">
                    <i class="fas fa-trash"></i>
                    <span>Delete</span>
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

.luxury-container {
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
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateX(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
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

/* ===== FILTER BAR ===== */
.filter-bar {
    background: #ffffff;
    border: 1px solid rgba(0,0,0,0.08);
    padding: 30px;
    margin-bottom: 40px;
    transition: all 0.3s ease;
}

.filter-bar:hover {
    border-color: rgba(0,0,0,0.12);
    box-shadow: 0 10px 40px rgba(0,0,0,0.06);
}

.filter-form {
    display: flex;
    gap: 15px;
    align-items: center;
    flex-wrap: wrap;
}

.search-group {
    flex: 1;
    min-width: 250px;
    position: relative;
}

.search-group i {
    position: absolute;
    left: 20px;
    top: 50%;
    transform: translateY(-50%);
    color: rgba(0,0,0,0.4);
    font-size: 14px;
}

.search-input {
    width: 100%;
    padding: 15px 20px 15px 50px;
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

.filter-group {
    min-width: 150px;
}

.filter-select {
    width: 100%;
    padding: 15px 20px;
    border: 1px solid rgba(0,0,0,0.15);
    background: #fafafa;
    font-size: 13px;
    letter-spacing: 0.3px;
    font-family: 'Montserrat', sans-serif;
    cursor: pointer;
    transition: all 0.3s ease;
}

.filter-select:focus {
    outline: none;
    border-color: #0a0a0a;
    background: #ffffff;
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

.btn-lux i,
.btn-lux span {
    position: relative;
    z-index: 1;
}

.btn-danger::before {
    background: #b02a37;
}

.btn-danger:hover {
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

/* ===== TABLE ===== */
.table-card {
    background: #ffffff;
    border: 1px solid rgba(0,0,0,0.08);
    overflow: hidden;
    transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

.table-card:hover {
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
.supplier-name-cell {
    width: 16%;
}

.supplier-name {
    display: flex;
    align-items: center;
    gap: 12px;
}

.supplier-name i {
    color: rgba(0,0,0,0.4);
    font-size: 18px;
    flex-shrink: 0;
}

.supplier-name strong {
    font-weight: 500;
    color: #0a0a0a;
    font-size: 14px;
}

.contact-person-cell {
    width: 13%;
    font-size: 13px;
}

.contact-number-cell {
    width: 11%;
    font-size: 13px;
}

.address-cell {
    width: 24%;
}

.address-text {
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 12px;
    color: rgba(0,0,0,0.6);
}

.products-cell,
.supplies-cell {
    width: 8%;
    text-align: center;
}

.status-cell {
    width: 10%;
}

.actions-cell {
    width: 10%;
}

.badge {
    display: inline-block;
    padding: 6px 14px;
    font-size: 10px;
    font-weight: 600;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    border-radius: 0;
}

.badge-info {
    background: #e3f2fd;
    color: #1976d2;
}

.badge-secondary {
    background: #f5f5f5;
    color: #616161;
}

.status-badge-lux {
    display: inline-block;
    padding: 8px 20px;
    font-size: 9px;
    font-weight: 600;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: #ffffff;
    transition: all 0.3s ease;
}

.status-badge-lux:hover {
    transform: scale(1.05);
    letter-spacing: 2.5px;
}

.status-badge-lux.active {
    background: #0a0a0a;
}

.status-badge-lux.inactive {
    background: #b02a37;
}

.actions-cell {
    display: flex;
    gap: 10px;
    justify-content: center;
    align-items: center;
}

/* ===== ACTION BUTTONS ===== */
.btn-lux {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 14px;
    border: 1px solid rgba(0,0,0,0.2);
    background: transparent;
    color: #0a0a0a;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
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

.btn-lux i {
    position: relative;
    z-index: 1;
    font-size: 12px;
}

.btn-delete::before {
    background: #b02a37;
}

.btn-delete:hover {
    border-color: #b02a37;
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
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 10000;
    overflow-y: auto;
    animation: fadeIn 0.3s ease;
}

.modal.active {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.6);
    backdrop-filter: blur(5px);
}

.modal-content {
    position: relative;
    background: #ffffff;
    width: 100%;
    max-width: 700px;
    z-index: 10001;
    animation: slideUp 0.3s ease;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.modal-small {
    max-width: 500px;
}

.modal-header {
    padding: 40px;
    border-bottom: 1px solid rgba(0,0,0,0.08);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-title {
    font-family: 'Playfair Display', serif;
    font-size: 28px;
    font-weight: 400;
    color: #0a0a0a;
}

.modal-close {
    width: 40px;
    height: 40px;
    border: 1px solid rgba(0,0,0,0.15);
    background: transparent;
    color: #0a0a0a;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
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

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
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
    min-height: 80px;
}

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

.modal-actions {
    display: flex;
    gap: 15px;
    justify-content: flex-end;
    margin-top: 30px;
}

.delete-message {
    padding: 40px;
    text-align: center;
}

.delete-message i {
    font-size: 48px;
    color: #b02a37;
    margin-bottom: 20px;
}

.delete-message p {
    font-size: 14px;
    color: #0a0a0a;
    margin-bottom: 10px;
    line-height: 1.6;
}

.delete-message strong {
    color: #b02a37;
}

.warning-text {
    font-size: 12px;
    color: rgba(0,0,0,0.5);
    font-style: italic;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 1200px) {
    .lux-admin-title {
        font-size: 40px;
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

    .header-actions .btn-lux {
        width: 100%;
    }

    .filter-form {
        flex-direction: column;
    }

    .search-group,
    .filter-group {
        width: 100%;
    }

    .btn-filter,
    .btn-clear {
        width: 100%;
    }

    .table-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }

    .modal-content {
        margin: 20px;
    }

    .modal-header,
    .modal-form {
        padding: 30px 25px;
    }

    .form-row {
        grid-template-columns: 1fr;
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

    .filter-bar {
        padding: 20px;
    }

    .table-card {
        border: none;
        overflow-x: auto;
    }

    .luxury-table {
        font-size: 12px;
        min-width: 900px;
    }

    .luxury-table thead th,
    .luxury-table tbody td {
        padding: 15px 10px;
    }

    .actions-cell {
        flex-direction: row;
        gap: 5px;
    }

    .btn-lux {
        padding: 8px 10px;
    }
}

@media (max-width: 480px) {
    .lux-admin-title {
        font-size: 24px;
    }

    .modal-title {
        font-size: 22px;
    }

    .modal-actions {
        flex-direction: column;
    }

    .modal-actions .btn-lux {
        width: 100%;
    }
}
</style>

<script>
// Modal Functions
function openModal() {
    document.getElementById('supplierModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    document.getElementById('supplierModal').classList.remove('active');
    document.body.style.overflow = '';
    resetForm();
}

function resetForm() {
    document.getElementById('supplierForm').reset();
    document.getElementById('formAction').value = 'add_supplier';
    document.getElementById('supplierId').value = '';
    document.getElementById('modalTitle').textContent = 'Add Supplier';
    document.getElementById('isActive').checked = true;
}

function openDeleteModal() {
    document.getElementById('deleteModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('active');
    document.body.style.overflow = '';
}

// Add Supplier
document.getElementById('addSupplierBtn').addEventListener('click', function() {
    resetForm();
    openModal();
});

// Edit Supplier
function editSupplier(supplier) {
    document.getElementById('formAction').value = 'update_supplier';
    document.getElementById('supplierId').value = supplier.supplier_id;
    document.getElementById('supplierName').value = supplier.supplier_name;
    document.getElementById('contactPerson').value = supplier.contact_person || '';
    document.getElementById('contactNumber').value = supplier.contact_number || '';
    document.getElementById('address').value = supplier.address || '';
    document.getElementById('isActive').checked = supplier.is_active == 1;
    document.getElementById('modalTitle').textContent = 'Edit Supplier';
    openModal();
}

// Delete Supplier
function deleteSupplier(id, name) {
    document.getElementById('deleteId').value = id;
    document.getElementById('deleteName').textContent = name;
    openDeleteModal();
}

// Close modals on ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
        closeDeleteModal();
    }
});

// Auto-dismiss alerts
document.addEventListener('DOMContentLoaded', function() {
    const alert = document.getElementById('alertMessage');
    if (alert) {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateX(-20px)';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
        
        alert.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
});

// Form validation
document.getElementById('supplierForm').addEventListener('submit', function(e) {
    const supplierName = document.getElementById('supplierName').value.trim();
    
    if (supplierName.length < 3) {
        e.preventDefault();
        alert('Supplier name must be at least 3 characters long.');
        return false;
    }
    
    // Show loading state
    const submitBtn = document.getElementById('submitBtn');
    const originalHTML = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Saving...</span>';
    submitBtn.disabled = true;
    
    // Re-enable after timeout as fallback
    setTimeout(() => {
        submitBtn.innerHTML = originalHTML;
        submitBtn.disabled = false;
    }, 10000);
});

// Delete form confirmation
document.getElementById('deleteForm').addEventListener('submit', function(e) {
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalHTML = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Deleting...</span>';
    submitBtn.disabled = true;
    
    setTimeout(() => {
        submitBtn.innerHTML = originalHTML;
        submitBtn.disabled = false;
    }, 10000);
});

// Contact number validation (Philippine format)
document.getElementById('contactNumber').addEventListener('input', function() {
    let value = this.value.replace(/\D/g, '');
    
    // Limit to 11 digits for Philippine numbers
    if (value.length > 11) {
        value = value.slice(0, 11);
    }
    
    this.value = value;
});

// Real-time form validation feedback
document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('.form-input[required]');
    
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

// Prevent form resubmission on page refresh
if (window.history.replaceState) {
    window.history.replaceState(null, null, window.location.href);
}

// Unsaved changes warning
let formChanged = false;

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('supplierForm');
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

// Close modal warning if form changed
const originalCloseModal = closeModal;
closeModal = function() {
    if (formChanged) {
        if (confirm('You have unsaved changes. Are you sure you want to close?')) {
            formChanged = false;
            originalCloseModal();
        }
    } else {
        originalCloseModal();
    }
};

// Smooth scroll for alerts
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('success') || urlParams.has('error')) {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
});

// Table row animation on load
document.addEventListener('DOMContentLoaded', function() {
    const rows = document.querySelectorAll('.lux-table tbody tr');
    rows.forEach((row, index) => {
        row.style.opacity = '0';
        row.style.transform = 'translateY(20px)';
        setTimeout(() => {
            row.style.transition = 'all 0.3s ease';
            row.style.opacity = '1';
            row.style.transform = 'translateY(0)';
        }, index * 50);
    });
});

// Enhanced button hover effects
document.addEventListener('DOMContentLoaded', function() {
    const buttons = document.querySelectorAll('.btn-lux');
    
    buttons.forEach(button => {
        button.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
        });
        
        button.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>

<?php ob_end_flush(); ?>