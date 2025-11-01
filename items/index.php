<?php
// /items/index.php
require_once('../includes/config.php');
require_once('../includes/header.php');

if (!isset($conn)) {
    die("Database connection not established. Please check config.php");
}

// Get filter parameters
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Fetch categories for filter
$categories_sql = "SELECT category_id, category_name FROM categories ORDER BY category_name ASC";
$categories_result = $conn->query($categories_sql);
?>

<!-- Hero Section -->
<section class="hero hero-compact">
    <div class="hero-overlay">
        <div class="hero-content">
            <h1>Our Signature Collection</h1>
            <p>Explore refined scents crafted for timeless elegance.</p>
            <a href="#collections" class="btn btn-primary">Browse the Collection</a>
        </div>
    </div>
</section>

<!-- Collections Section -->
<section class="featured-products" id="collections">
    <div class="container">
        <h2>All Collections</h2>

        <!-- Refined Filter Bar -->
        <div class="collections-header">
            <div class="filter-controls">
                <div class="filter-item">
                    <select id="categoryFilter" class="elegant-select">
                        <option value="0">All Collections</option>
                        <?php 
                        if ($categories_result && $categories_result->num_rows > 0) {
                            while($cat = $categories_result->fetch_assoc()) {
                                $selected = ($category_filter == $cat['category_id']) ? 'selected' : '';
                                echo '<option value="' . $cat['category_id'] . '" ' . $selected . '>' . 
                                     htmlspecialchars($cat['category_name']) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="filter-item">
                    <select id="sortFilter" class="elegant-select">
                        <option value="newest" <?= $sort_by === 'newest' ? 'selected' : ''; ?>>Newest</option>
                        <option value="oldest" <?= $sort_by === 'oldest' ? 'selected' : ''; ?>>Oldest</option>
                        <option value="price_asc" <?= $sort_by === 'price_asc' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_desc" <?= $sort_by === 'price_desc' ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="name" <?= $sort_by === 'name' ? 'selected' : ''; ?>>Alphabetical</option>
                    </select>
                </div>

                <div class="filter-item filter-search-wrapper">
                    <input type="text" 
                           id="searchInput" 
                           class="elegant-search" 
                           placeholder="Search fragrances..." 
                           value="<?= htmlspecialchars($search_query); ?>">
                    <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/>
                        <path d="m21 21-4.35-4.35"/>
                    </svg>
                </div>

                <?php if ($category_filter > 0 || $sort_by !== 'newest' || !empty($search_query)): ?>
                    <button class="elegant-reset" onclick="resetFilters()">Clear All</button>
                <?php endif; ?>
            </div>
        </div>

        <div class="product-grid">
            <?php
            try {
                // Build SQL query with filters
                $sql = "SELECT p.product_id, p.product_name, p.price, p.image_path, c.category_name
                        FROM products p
                        LEFT JOIN categories c ON p.category_id = c.category_id
                        WHERE p.is_active = 1";
                
                // Add category filter
                if ($category_filter > 0) {
                    $sql .= " AND p.category_id = " . $category_filter;
                }
                
                // Add search filter
                if (!empty($search_query)) {
                    $sql .= " AND (p.product_name LIKE '%" . $conn->real_escape_string($search_query) . "%' 
                              OR p.description LIKE '%" . $conn->real_escape_string($search_query) . "%')";
                }
                
                // Add sorting
                switch ($sort_by) {
                    case 'oldest':
                        $sql .= " ORDER BY p.product_id ASC";
                        break;
                    case 'price_asc':
                        $sql .= " ORDER BY p.price ASC";
                        break;
                    case 'price_desc':
                        $sql .= " ORDER BY p.price DESC";
                        break;
                    case 'name':
                        $sql .= " ORDER BY p.product_name ASC";
                        break;
                    default: // newest
                        $sql .= " ORDER BY p.product_id DESC";
                }

                $stmt = $conn->prepare($sql);

                if (!$stmt) {
                    throw new Exception("Failed to prepare statement: " . $conn->error);
                }

                if (!$stmt->execute()) {
                    throw new Exception("Failed to execute query: " . $stmt->error);
                }

                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $productId = htmlspecialchars($row['product_id']);
                        $productName = htmlspecialchars($row['product_name']);
                        $imagePath = htmlspecialchars($row['image_path']);
                        $price = number_format($row['price'], 2);
                        $categoryName = htmlspecialchars($row['category_name'] ?? '');
                        ?>
                        <div class="product-card">
                            <a href="../product.php?id=<?php echo $productId; ?>" class="product-link">
                                <img src="<?php echo '../' . ltrim($imagePath, '/'); ?>" 
                                    alt="<?php echo $productName; ?>" 
                                    loading="lazy">

                                <div class="product-info">
                                    <?php if ($categoryName): ?>
                                        <p class="product-category"><?php echo $categoryName; ?></p>
                                    <?php endif; ?>
                                    <h3><?php echo $productName; ?></h3>
                                    <p class="price">₱<?php echo $price; ?></p>
                                </div>
                            </a>
                        </div>
                        <?php
                    }
                } else {
                    echo '<div class="no-products">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <circle cx="11" cy="11" r="8"/>
                                <path d="m21 21-4.35-4.35"/>
                            </svg>
                            <p>No products found matching your criteria.</p>
                            <button class="elegant-reset" onclick="resetFilters()">Clear Filters</button>
                          </div>';
                }

                $stmt->close();
            } catch (Exception $e) {
                echo '<p class="text-center">Unable to load collections. Please try again later.</p>';
                error_log("Error loading collections: " . $e->getMessage());
            }
            ?>
        </div>
    </div>
</section>

<!-- Newsletter -->
<section class="newsletter">
    <div class="container">
        <h2>Stay Updated</h2>
        <p>Subscribe to our newsletter to get the latest scent releases and special offers.</p>
        <form action="../subscribe.php" method="post" class="newsletter-form">
            <input type="email" name="email" placeholder="Enter your email address" required>
            <button type="submit" class="btn btn-primary">Subscribe</button>
        </form>
    </div>
</section>

<?php require_once('../includes/footer.php'); ?>

<style>
/* ========================================
   HERO COMPACT
======================================== */
.hero-compact {
    height: 60vh;
    background: url('../assets/images/hero-perfume.jpg') center/cover no-repeat;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 20px;
}

.hero-compact .hero-overlay {
    background: rgba(0, 0, 0, 0.45);
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
}

.hero-compact .hero-content h1 {
    font-size: 3rem;
    font-weight: 200;
    color: #ffffff;
    margin-bottom: 10px;
    font-family: 'Playfair Display', serif;
}

.hero-compact .hero-content p {
    font-size: 1.2rem;
    color: rgba(255, 255, 255, 0.9);
    margin-bottom: 25px;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}

.hero-compact .btn.btn-primary {
    padding: 14px 40px;
    letter-spacing: 2px;
    font-size: 11px;
}

/* ========================================
   REFINED COLLECTIONS HEADER
======================================== */
.collections-header {
    margin-bottom: 60px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.08);
    padding-bottom: 30px;
}

.filter-controls {
    display: flex;
    align-items: center;
    gap: 15px;
    justify-content: center;
    flex-wrap: wrap;
}

.filter-item {
    position: relative;
}

.filter-search-wrapper {
    flex: 1;
    max-width: 280px;
    min-width: 200px;
}

/* ========================================
   ELEGANT SELECT STYLING
======================================== */
.elegant-select {
    appearance: none;
    background: transparent;
    border: none;
    border-bottom: 1px solid rgba(0, 0, 0, 0.15);
    padding: 12px 30px 12px 0;
    font-size: 13px;
    font-weight: 300;
    letter-spacing: 0.5px;
    color: #0a0a0a;
    cursor: pointer;
    transition: all 0.3s ease;
    font-family: 'Lato', sans-serif;
    min-width: 160px;
    background-image: url("data:image/svg+xml,%3Csvg width='10' height='6' viewBox='0 0 10 6' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1 1L5 5L9 1' stroke='%230a0a0a' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right center;
}

.elegant-select:hover,
.elegant-select:focus {
    border-bottom-color: #0a0a0a;
    outline: none;
}

.elegant-select option {
    padding: 10px;
    background: #ffffff;
}

/* ========================================
   ELEGANT SEARCH STYLING
======================================== */
.elegant-search {
    width: 100%;
    background: transparent;
    border: none;
    border-bottom: 1px solid rgba(0, 0, 0, 0.15);
    padding: 12px 35px 12px 0;
    font-size: 13px;
    font-weight: 300;
    letter-spacing: 0.5px;
    color: #0a0a0a;
    transition: all 0.3s ease;
    font-family: 'Lato', sans-serif;
}

.elegant-search:hover,
.elegant-search:focus {
    border-bottom-color: #0a0a0a;
    outline: none;
}

.elegant-search::placeholder {
    color: rgba(0, 0, 0, 0.35);
    font-style: italic;
}

.search-icon {
    position: absolute;
    right: 0;
    top: 50%;
    transform: translateY(-50%);
    pointer-events: none;
    opacity: 0.4;
    transition: opacity 0.3s ease;
}

.filter-search-wrapper:hover .search-icon,
.elegant-search:focus ~ .search-icon {
    opacity: 0.7;
}

/* ========================================
   ELEGANT RESET BUTTON
======================================== */
.elegant-reset {
    background: transparent;
    border: 1px solid rgba(0, 0, 0, 0.2);
    padding: 11px 24px;
    font-size: 11px;
    font-weight: 400;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    color: rgba(0, 0, 0, 0.6);
    cursor: pointer;
    transition: all 0.3s ease;
    font-family: 'Lato', sans-serif;
}

.elegant-reset:hover {
    border-color: #0a0a0a;
    color: #0a0a0a;
    background: rgba(0, 0, 0, 0.02);
}

/* ========================================
   PRODUCT CATEGORY TAG
======================================== */
.product-category {
    font-size: 10px;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: rgba(0, 0, 0, 0.45);
    margin-bottom: 8px;
    font-weight: 400;
}

/* ========================================
   NO PRODUCTS STATE - REFINED
======================================== */
.no-products {
    grid-column: 1 / -1;
    text-align: center;
    padding: 100px 20px;
}

.no-products svg {
    margin-bottom: 25px;
    opacity: 0.2;
    stroke: #0a0a0a;
}

.no-products p {
    font-size: 13px;
    letter-spacing: 0.5px;
    margin-bottom: 30px;
    color: rgba(0, 0, 0, 0.5);
    font-weight: 300;
}

.no-products .elegant-reset {
    margin-top: 0;
}

/* ========================================
   RESPONSIVE DESIGN
======================================== */
@media (max-width: 968px) {
    .filter-controls {
        gap: 20px;
    }

    .filter-item {
        flex: 1 1 calc(50% - 10px);
        min-width: 150px;
    }

    .filter-search-wrapper {
        flex: 1 1 100%;
        max-width: 100%;
    }

    .elegant-reset {
        flex: 1 1 100%;
        width: 100%;
    }
}

@media (max-width: 768px) {
    .hero-compact {
        height: 50vh;
    }

    .hero-compact .hero-content h1 {
        font-size: 2.5rem;
    }

    .hero-compact .hero-content p {
        font-size: 1rem;
    }

    .collections-header {
        margin-bottom: 50px;
        padding-bottom: 25px;
    }

    .filter-controls {
        gap: 15px;
    }

    .filter-item {
        flex: 1 1 100%;
        min-width: 100%;
    }

    .elegant-select {
        width: 100%;
    }

    .no-products {
        padding: 80px 20px;
    }
}

@media (max-width: 480px) {
    .hero-compact .hero-content h1 {
        font-size: 2rem;
    }

    .collections-header {
        margin-bottom: 40px;
        padding-bottom: 20px;
    }

    .elegant-select,
    .elegant-search {
        font-size: 12px;
        padding: 10px 30px 10px 0;
    }

    .elegant-reset {
        padding: 10px 20px;
        font-size: 10px;
    }

    .no-products {
        padding: 60px 20px;
    }
}
</style>

<script>
// Refined filter functionality
let searchTimeout;

document.getElementById('categoryFilter').addEventListener('change', applyFilters);
document.getElementById('sortFilter').addEventListener('change', applyFilters);

document.getElementById('searchInput').addEventListener('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 600); // Slightly longer delay for more elegant feel
});

function applyFilters() {
    const category = document.getElementById('categoryFilter').value;
    const sort = document.getElementById('sortFilter').value;
    const search = document.getElementById('searchInput').value.trim();
    
    const params = new URLSearchParams();
    
    if (category !== '0') params.append('category', category);
    if (sort !== 'newest') params.append('sort', sort);
    if (search) params.append('search', search);
    
    const queryString = params.toString();
    window.location.href = 'index.php' + (queryString ? '?' + queryString : '');
}

function resetFilters() {
    window.location.href = 'index.php';
}

// Optional: Add smooth fade-in for search icon
document.getElementById('searchInput').addEventListener('focus', function() {
    this.nextElementSibling.style.opacity = '0.7';
});

document.getElementById('searchInput').addEventListener('blur', function() {
    if (!this.value) {
        this.nextElementSibling.style.opacity = '0.4';
    }
});
</script>