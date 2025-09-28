<?php
$searchQuery = $_GET['q'] ?? '';
$searchQuery = trim($searchQuery);

if (empty($searchQuery)) {
    redirectTo('?page=products');
}

global $database;

// Get filter parameters
$category = $_GET['category'] ?? '';
$sort = $_GET['sort'] ?? 'relevance';
$minPrice = floatval($_GET['min_price'] ?? 0);
$maxPrice = floatval($_GET['max_price'] ?? 0);
$featured = isset($_GET['featured']) ? 1 : 0;
$sale = isset($_GET['sale']) ? 1 : 0;
$page = max(1, intval($_GET['page_num'] ?? 1));

// Build WHERE clause for search
$whereConditions = ["p.status = 'active'"];
$params = [];

// Add search conditions
$searchTerm = "%{$searchQuery}%";
$whereConditions[] = "(p.name LIKE ? OR p.description LIKE ? OR p.short_description LIKE ? OR c.name LIKE ? OR v.shop_name LIKE ?)";
$params[] = $searchTerm;
$params[] = $searchTerm;
$params[] = $searchTerm;
$params[] = $searchTerm;
$params[] = $searchTerm;

if ($category) {
    $whereConditions[] = "c.slug = ?";
    $params[] = $category;
}

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
    case 'relevance':
        // Simple relevance scoring based on title match
        $orderBy = "
            CASE 
                WHEN p.name LIKE '%{$searchQuery}%' THEN 1
                WHEN p.short_description LIKE '%{$searchQuery}%' THEN 2
                WHEN p.description LIKE '%{$searchQuery}%' THEN 3
                WHEN c.name LIKE '%{$searchQuery}%' THEN 4
                ELSE 5
            END ASC, p.rating DESC, p.total_sales DESC
        ";
        break;
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
        $orderBy = "p.created_at DESC";
        break;
}

// Pagination
$offset = ($page - 1) * PRODUCTS_PER_PAGE;

// Get total count
$countSql = "
    SELECT COUNT(DISTINCT p.id) as total
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN vendors v ON p.vendor_id = v.id
    WHERE {$whereClause}
";
$totalResult = $database->fetchOne($countSql, $params);
$totalProducts = $totalResult['total'];
$totalPages = ceil($totalProducts / PRODUCTS_PER_PAGE);

// Get products
$sql = "
    SELECT p.*, pi.image_url, v.shop_name, c.name as category_name, c.slug as category_slug,
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

// Get categories for filter (only those with matching products)
$categoriesWithProducts = $database->fetchAll("
    SELECT c.*, COUNT(DISTINCT p.id) as product_count
    FROM categories c 
    INNER JOIN products p ON c.id = p.category_id
    LEFT JOIN vendors v ON p.vendor_id = v.id
    WHERE p.status = 'active' AND (
        p.name LIKE ? OR p.description LIKE ? OR p.short_description LIKE ? OR 
        c.name LIKE ? OR v.shop_name LIKE ?
    )
    GROUP BY c.id 
    ORDER BY product_count DESC, c.name
    LIMIT 10
", [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);

// Get suggested search terms (categories and popular searches)
$suggestions = $database->fetchAll("
    SELECT DISTINCT c.name as suggestion, 'category' as type
    FROM categories c 
    WHERE c.name LIKE ? AND c.is_active = 1
    LIMIT 5
", [$searchTerm]);

$pageTitle = "Search Results for \"{$searchQuery}\"";
$pageDescription = "Search results for {$searchQuery} - {$totalProducts} products found";
?>

<div class="container mx-auto px-4 py-8">
    <!-- Breadcrumbs -->
    <nav class="text-sm breadcrumbs mb-6">
        <ol class="flex items-center space-x-2 text-gray-500">
            <li><a href="<?php echo SITE_URL; ?>" class="hover:text-primary">Home</a></li>
            <li><i class="fas fa-chevron-right"></i></li>
            <li><a href="?page=products" class="hover:text-primary">Products</a></li>
            <li><i class="fas fa-chevron-right"></i></li>
            <li class="text-gray-800">Search Results</li>
        </ol>
    </nav>

    <!-- Search Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">
            Search Results for "<span class="text-primary"><?php echo htmlspecialchars($searchQuery); ?></span>"
        </h1>
        <p class="text-gray-600"><?php echo number_format($totalProducts); ?> products found</p>
        
        <!-- Search Suggestions -->
        <?php if (!empty($suggestions) && $totalProducts === 0): ?>
            <div class="mt-4 p-4 bg-blue-50 rounded-lg">
                <p class="text-sm text-blue-800 mb-2">Did you mean:</p>
                <div class="flex flex-wrap gap-2">
                    <?php foreach ($suggestions as $suggestion): ?>
                        <a href="?page=search&q=<?php echo urlencode($suggestion['suggestion']); ?>" 
                           class="text-sm bg-blue-100 text-blue-800 px-3 py-1 rounded-full hover:bg-blue-200">
                            <?php echo htmlspecialchars($suggestion['suggestion']); ?>
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
                <h3 class="text-lg font-semibold mb-4">Refine Search</h3>
                
                <form method="GET" action="" id="filter-form">
                    <input type="hidden" name="page" value="search">
                    <input type="hidden" name="q" value="<?php echo htmlspecialchars($searchQuery); ?>">
                    
                    <!-- Categories -->
                    <?php if (!empty($categoriesWithProducts)): ?>
                        <div class="mb-6">
                            <h4 class="font-semibold mb-3">Categories</h4>
                            <div class="space-y-2">
                                <label class="flex items-center">
                                    <input type="radio" name="category" value="" <?php echo !$category ? 'checked' : ''; ?> 
                                           class="mr-2" onchange="document.getElementById('filter-form').submit()">
                                    <span>All Categories</span>
                                </label>
                                <?php foreach ($categoriesWithProducts as $cat): ?>
                                    <label class="flex items-center">
                                        <input type="radio" name="category" value="<?php echo $cat['slug']; ?>" 
                                               <?php echo $category === $cat['slug'] ? 'checked' : ''; ?>
                                               class="mr-2" onchange="document.getElementById('filter-form').submit()">
                                        <span><?php echo htmlspecialchars($cat['name']); ?> (<?php echo $cat['product_count']; ?>)</span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
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
                <?php if ($category || $featured || $sale || $minPrice || $maxPrice): ?>
                    <a href="?page=search&q=<?php echo urlencode($searchQuery); ?>" class="text-sm text-primary hover:text-opacity-80">
                        <i class="fas fa-times mr-1"></i>Clear All Filters
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Search Results -->
        <div class="lg:w-3/4">
            <!-- Sort Options -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 bg-white rounded-lg shadow-sm p-4">
                <div class="flex items-center space-x-4 mb-4 sm:mb-0">
                    <span class="text-gray-600">Sort by:</span>
                    <select name="sort" onchange="updateSort(this.value)" 
                            class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                        <option value="relevance" <?php echo $sort === 'relevance' ? 'selected' : ''; ?>>Relevance</option>
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
                    of <?php echo number_format($totalProducts); ?> results
                </div>
            </div>

            <!-- Search Results -->
            <?php if (empty($products)): ?>
                <div class="text-center py-16">
                    <i class="fas fa-search text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-600 mb-2">No Products Found</h3>
                    <p class="text-gray-500 mb-6">
                        Sorry, we couldn't find any products matching "<strong><?php echo htmlspecialchars($searchQuery); ?></strong>".
                    </p>
                    
                    <div class="space-y-4">
                        <div>
                            <p class="text-gray-600 mb-3">Try searching for:</p>
                            <div class="flex flex-wrap justify-center gap-2">
                                <a href="?page=search&q=electronics" class="bg-gray-100 text-gray-700 px-3 py-1 rounded-full text-sm hover:bg-gray-200">Electronics</a>
                                <a href="?page=search&q=fashion" class="bg-gray-100 text-gray-700 px-3 py-1 rounded-full text-sm hover:bg-gray-200">Fashion</a>
                                <a href="?page=search&q=home" class="bg-gray-100 text-gray-700 px-3 py-1 rounded-full text-sm hover:bg-gray-200">Home & Garden</a>
                                <a href="?page=search&q=sports" class="bg-gray-100 text-gray-700 px-3 py-1 rounded-full text-sm hover:bg-gray-200">Sports</a>
                            </div>
                        </div>
                        
                        <a href="?page=products" class="bg-primary text-white px-6 py-3 rounded-md hover:bg-opacity-90 inline-block">
                            Browse All Products
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-8">
                    <?php foreach ($products as $product): ?>
                        <?php renderProductCard($product); ?>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="flex justify-center">
                        <nav class="flex items-center space-x-2">
                            <?php if ($page > 1): ?>
                                <a href="<?php echo buildSearchPaginationUrl($page - 1); ?>" 
                                   class="px-3 py-2 border border-gray-300 rounded-md hover:bg-gray-50">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            <?php endif; ?>
                            
                            <?php
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            
                            if ($startPage > 1) {
                                echo '<a href="' . buildSearchPaginationUrl(1) . '" class="px-3 py-2 border border-gray-300 rounded-md hover:bg-gray-50">1</a>';
                                if ($startPage > 2) {
                                    echo '<span class="px-3 py-2">...</span>';
                                }
                            }
                            
                            for ($i = $startPage; $i <= $endPage; $i++) {
                                $class = $i === $page ? 'bg-primary text-white' : 'border border-gray-300 hover:bg-gray-50';
                                echo '<a href="' . buildSearchPaginationUrl($i) . '" class="px-3 py-2 rounded-md ' . $class . '">' . $i . '</a>';
                            }
                            
                            if ($endPage < $totalPages) {
                                if ($endPage < $totalPages - 1) {
                                    echo '<span class="px-3 py-2">...</span>';
                                }
                                echo '<a href="' . buildSearchPaginationUrl($totalPages) . '" class="px-3 py-2 border border-gray-300 rounded-md hover:bg-gray-50">' . $totalPages . '</a>';
                            }
                            ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <a href="<?php echo buildSearchPaginationUrl($page + 1); ?>" 
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
function buildSearchPaginationUrl($pageNum) {
    $params = $_GET;
    $params['page_num'] = $pageNum;
    return '?' . http_build_query($params);
}
?>
