<?php
$categorySlug = $_GET['slug'] ?? '';

if (empty($categorySlug)) {
    redirectTo('?page=products');
}

global $database;

// Get category details
$category = $database->fetchOne("SELECT * FROM categories WHERE slug = ? AND is_active = 1", [$categorySlug]);

if (!$category) {
    redirectTo('?page=404');
}

// Get subcategories
$subcategories = $database->fetchAll("
    SELECT c.*, COUNT(p.id) as product_count
    FROM categories c 
    LEFT JOIN products p ON c.id = p.category_id AND p.status = 'active'
    WHERE c.parent_id = ? AND c.is_active = 1 
    GROUP BY c.id 
    ORDER BY c.sort_order, c.name
", [$category['id']]);

// Set up pagination and filtering
$page = max(1, intval($_GET['page_num'] ?? 1));
$sort = $_GET['sort'] ?? 'newest';
$minPrice = floatval($_GET['min_price'] ?? 0);
$maxPrice = floatval($_GET['max_price'] ?? 0);
$featured = isset($_GET['featured']) ? 1 : 0;
$sale = isset($_GET['sale']) ? 1 : 0;

// Build WHERE clause
$whereConditions = ["p.status = 'active'", "p.category_id = ?"];
$params = [$category['id']];

if ($minPrice > 0) {
    $whereConditions[] = "COALESCE(p.sale_price, p.price) >= ?";
    $params[] = $minPrice;
}

if ($maxPrice > 0) {
    $whereConditions[] = "COALESCE(p.sale_price, p.price) <= ?";
    $params[] = $maxPrice;
}

if ($featured) {
    $whereConditions[] = "p.featured = 1";
}

if ($sale) {
    $whereConditions[] = "p.sale_price IS NOT NULL AND p.sale_price < p.price";
}

$whereClause = implode(' AND ', $whereConditions);

// Build ORDER BY clause
$orderBy = "p.created_at DESC";
switch ($sort) {
    case 'price_low':
        $orderBy = "COALESCE(p.sale_price, p.price) ASC";
        break;
    case 'price_high':
        $orderBy = "COALESCE(p.sale_price, p.price) DESC";
        break;
    case 'name':
        $orderBy = "p.name ASC";
        break;
    case 'rating':
        $orderBy = "p.rating DESC, p.total_reviews DESC";
        break;
    case 'popular':
        $orderBy = "p.total_sales DESC";
        break;
    case 'newest':
    default:
        $orderBy = "p.created_at DESC";
        break;
}

// Pagination
$offset = ($page - 1) * PRODUCTS_PER_PAGE;

// Get total count
$countSql = "
    SELECT COUNT(p.id) as total
    FROM products p 
    WHERE {$whereClause}
";
$totalResult = $database->fetchOne($countSql, $params);
$totalProducts = $totalResult['total'];
$totalPages = ceil($totalProducts / PRODUCTS_PER_PAGE);

// Get products
$sql = "
    SELECT p.*, pi.image_url, v.shop_name, c.name as category_name,
           ROUND(((p.price - COALESCE(p.sale_price, p.price)) / p.price) * 100) as discount_percentage
    FROM products p 
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
    LEFT JOIN vendors v ON p.vendor_id = v.id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE {$whereClause}
    ORDER BY {$orderBy}
    LIMIT " . PRODUCTS_PER_PAGE . " OFFSET {$offset}
";

$products = $database->fetchAll($sql, $params);

// Get price range for this category
$priceRange = $database->fetchOne("
    SELECT 
        MIN(COALESCE(p.sale_price, p.price)) as min_price,
        MAX(COALESCE(p.sale_price, p.price)) as max_price
    FROM products p 
    WHERE p.category_id = ? AND p.status = 'active'
", [$category['id']]);

$pageTitle = $category['name'];
$pageDescription = $category['description'] ?: "Browse {$category['name']} products";
?>

<div class="container mx-auto px-4 py-8">
    <!-- Breadcrumbs -->
    <nav class="text-sm breadcrumbs mb-6">
        <ol class="flex items-center space-x-2 text-gray-500">
            <li><a href="<?php echo SITE_URL; ?>" class="hover:text-primary">Home</a></li>
            <li><i class="fas fa-chevron-right"></i></li>
            <li><a href="?page=products" class="hover:text-primary">Products</a></li>
            <li><i class="fas fa-chevron-right"></i></li>
            <li class="text-gray-800"><?php echo htmlspecialchars($category['name']); ?></li>
        </ol>
    </nav>

    <!-- Category Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($category['name']); ?></h1>
                <?php if ($category['description']): ?>
                    <p class="text-gray-600"><?php echo htmlspecialchars($category['description']); ?></p>
                <?php endif; ?>
                <p class="text-gray-600 mt-2"><?php echo number_format($totalProducts); ?> products found</p>
            </div>
            
            <?php if ($category['image']): ?>
                <div class="hidden md:block">
                    <img src="<?php echo SITE_URL . $category['image']; ?>" 
                         alt="<?php echo htmlspecialchars($category['name']); ?>"
                         class="w-32 h-32 object-cover rounded-lg">
                </div>
            <?php endif; ?>
        </div>

        <!-- Subcategories -->
        <?php if (!empty($subcategories)): ?>
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h3 class="text-lg font-semibold mb-4">Browse Subcategories</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    <?php foreach ($subcategories as $subcategory): ?>
                        <a href="?page=category&slug=<?php echo $subcategory['slug']; ?>" 
                           class="text-center p-3 rounded-lg border border-gray-200 hover:border-primary hover:shadow-md transition duration-200">
                            <div class="text-primary mb-2">
                                <i class="fas fa-tag text-2xl"></i>
                            </div>
                            <h4 class="font-semibold text-sm text-gray-800 mb-1"><?php echo htmlspecialchars($subcategory['name']); ?></h4>
                            <p class="text-xs text-gray-500"><?php echo $subcategory['product_count']; ?> products</p>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="flex flex-col lg:flex-row gap-8">
        <!-- Filters Sidebar -->
        <div class="lg:w-1/4">
            <div class="bg-white rounded-lg shadow-md p-6 sticky top-24">
                <h3 class="text-lg font-semibold mb-4">Filters</h3>
                
                <form method="GET" action="" id="filter-form">
                    <input type="hidden" name="page" value="category">
                    <input type="hidden" name="slug" value="<?php echo $categorySlug; ?>">
                    
                    <!-- Price Range -->
                    <div class="mb-6">
                        <h4 class="font-semibold mb-3">Price Range</h4>
                        <div class="flex items-center space-x-2">
                            <input type="number" name="min_price" placeholder="Min" 
                                   value="<?php echo $minPrice > 0 ? $minPrice : ''; ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                            <span>-</span>
                            <input type="number" name="max_price" placeholder="Max"
                                   value="<?php echo $maxPrice > 0 ? $maxPrice : ''; ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                        </div>
                        <div class="mt-2 text-xs text-gray-500">
                            Range: <?php echo formatPrice($priceRange['min_price'] ?? 0); ?> - <?php echo formatPrice($priceRange['max_price'] ?? 0); ?>
                        </div>
                        <button type="submit" class="mt-2 text-sm bg-primary text-white px-3 py-1 rounded">Apply</button>
                    </div>
                    
                    <!-- Special Filters -->
                    <div class="mb-6">
                        <h4 class="font-semibold mb-3">Special</h4>
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input type="checkbox" name="featured" value="1" <?php echo $featured ? 'checked' : ''; ?>
                                       class="mr-2" onchange="document.getElementById('filter-form').submit()">
                                <span>Featured Products</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="sale" value="1" <?php echo $sale ? 'checked' : ''; ?>
                                       class="mr-2" onchange="document.getElementById('filter-form').submit()">
                                <span>On Sale</span>
                            </label>
                        </div>
                    </div>
                </form>
                
                <!-- Clear Filters -->
                <?php if ($featured || $sale || $minPrice || $maxPrice): ?>
                    <a href="?page=category&slug=<?php echo $categorySlug; ?>" class="text-sm text-primary hover:text-opacity-80">
                        <i class="fas fa-times mr-1"></i>Clear All Filters
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Products Grid -->
        <div class="lg:w-3/4">
            <!-- Sort Options -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 bg-white rounded-lg shadow-sm p-4">
                <div class="flex items-center space-x-4 mb-4 sm:mb-0">
                    <span class="text-gray-600">Sort by:</span>
                    <select name="sort" onchange="updateSort(this.value)" 
                            class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                        <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest</option>
                        <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="name" <?php echo $sort === 'name' ? 'selected' : ''; ?>>Name A-Z</option>
                        <option value="rating" <?php echo $sort === 'rating' ? 'selected' : ''; ?>>Highest Rated</option>
                        <option value="popular" <?php echo $sort === 'popular' ? 'selected' : ''; ?>>Most Popular</option>
                    </select>
                </div>
                
                <div class="text-sm text-gray-600">
                    Showing <?php echo ($offset + 1); ?>-<?php echo min($offset + PRODUCTS_PER_PAGE, $totalProducts); ?> 
                    of <?php echo number_format($totalProducts); ?> products
                </div>
            </div>

            <!-- Products Grid -->
            <?php if (empty($products)): ?>
                <div class="text-center py-16">
                    <i class="fas fa-box-open text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-600 mb-2">No Products Found</h3>
                    <p class="text-gray-500 mb-6">Try adjusting your filters or browse other categories.</p>
                    <a href="?page=products" class="bg-primary text-white px-6 py-3 rounded-md hover:bg-opacity-90">
                        Browse All Products
                    </a>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 lg:gap-6 mb-8">
                    <?php foreach ($products as $product): ?>
                        <?php renderProductCard($product); ?>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="flex justify-center">
                        <nav class="flex items-center space-x-2">
                            <?php if ($page > 1): ?>
                                <a href="<?php echo buildCategoryPaginationUrl($page - 1); ?>" 
                                   class="px-3 py-2 border border-gray-300 rounded-md hover:bg-gray-50">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            <?php endif; ?>
                            
                            <?php
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            
                            if ($startPage > 1) {
                                echo '<a href="' . buildCategoryPaginationUrl(1) . '" class="px-3 py-2 border border-gray-300 rounded-md hover:bg-gray-50">1</a>';
                                if ($startPage > 2) {
                                    echo '<span class="px-3 py-2">...</span>';
                                }
                            }
                            
                            for ($i = $startPage; $i <= $endPage; $i++) {
                                $class = $i === $page ? 'bg-primary text-white' : 'border border-gray-300 hover:bg-gray-50';
                                echo '<a href="' . buildCategoryPaginationUrl($i) . '" class="px-3 py-2 rounded-md ' . $class . '">' . $i . '</a>';
                            }
                            
                            if ($endPage < $totalPages) {
                                if ($endPage < $totalPages - 1) {
                                    echo '<span class="px-3 py-2">...</span>';
                                }
                                echo '<a href="' . buildCategoryPaginationUrl($totalPages) . '" class="px-3 py-2 border border-gray-300 rounded-md hover:bg-gray-50">' . $totalPages . '</a>';
                            }
                            ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <a href="<?php echo buildCategoryPaginationUrl($page + 1); ?>" 
                                   class="px-3 py-2 border border-gray-300 rounded-md hover:bg-gray-50">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </nav>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function updateSort(sortValue) {
    const url = new URL(window.location);
    url.searchParams.set('sort', sortValue);
    url.searchParams.delete('page_num');
    window.location.href = url.toString();
}
</script>

<?php
function buildCategoryPaginationUrl($pageNum) {
    $params = $_GET;
    $params['page_num'] = $pageNum;
    return '?' . http_build_query($params);
}
?>
