<?php
$pageTitle = 'Products';
$pageDescription = 'Browse our wide range of quality products';

global $database;

// Get filter parameters
$category = $_GET['category'] ?? '';
$search = $_GET['q'] ?? '';
$featured = isset($_GET['featured']) ? 1 : 0;
$sale = isset($_GET['sale']) ? 1 : 0;
$sort = $_GET['sort'] ?? 'newest';
$minPrice = floatval($_GET['min_price'] ?? 0);
$maxPrice = floatval($_GET['max_price'] ?? 0);
$page = max(1, intval($_GET['page_num'] ?? 1));

// Build WHERE clause
$whereConditions = ["p.status = 'active'"];
$params = [];

if ($category) {
    $whereConditions[] = "c.slug = ?";
    $params[] = $category;
}

if ($search) {
    $whereConditions[] = "(p.name LIKE ? OR p.description LIKE ? OR p.short_description LIKE ?)";
    $searchTerm = "%{$search}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($featured) {
    $whereConditions[] = "p.featured = 1";
}

if ($sale) {
    $whereConditions[] = "p.sale_price IS NOT NULL AND p.sale_price < p.price";
}

if ($minPrice > 0) {
    $whereConditions[] = "COALESCE(p.sale_price, p.price) >= ?";
    $params[] = $minPrice;
}

if ($maxPrice > 0) {
    $whereConditions[] = "COALESCE(p.sale_price, p.price) <= ?";
    $params[] = $maxPrice;
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

// Get categories for filter
$categories = $database->fetchAll("
    SELECT c.*, COUNT(p.id) as product_count
    FROM categories c 
    LEFT JOIN products p ON c.id = p.category_id AND p.status = 'active'
    WHERE c.parent_id IS NULL AND c.is_active = 1 
    GROUP BY c.id 
    ORDER BY c.sort_order, c.name
");

// Get price range
$priceRange = $database->fetchOne("
    SELECT 
        MIN(COALESCE(p.sale_price, p.price)) as min_price,
        MAX(COALESCE(p.sale_price, p.price)) as max_price
    FROM products p 
    WHERE p.status = 'active'
");

$currentCategory = null;
if ($category) {
    $currentCategory = $database->fetchOne("SELECT * FROM categories WHERE slug = ?", [$category]);
}
?>

<div class="container mx-auto px-4 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <nav class="text-sm breadcrumbs mb-4">
            <ol class="flex items-center space-x-2 text-gray-500">
                <li><a href="<?php echo SITE_URL; ?>" class="hover:text-primary">Home</a></li>
                <li><i class="fas fa-chevron-right"></i></li>
                <?php if ($currentCategory): ?>
                    <li><a href="?page=products" class="hover:text-primary">Products</a></li>
                    <li><i class="fas fa-chevron-right"></i></li>
                    <li class="text-gray-800"><?php echo htmlspecialchars($currentCategory['name']); ?></li>
                <?php else: ?>
                    <li class="text-gray-800">Products</li>
                <?php endif; ?>
            </ol>
        </nav>
        
        <h1 class="text-3xl font-bold text-gray-800">
            <?php if ($search): ?>
                Search Results for "<?php echo htmlspecialchars($search); ?>"
            <?php elseif ($currentCategory): ?>
                <?php echo htmlspecialchars($currentCategory['name']); ?>
            <?php elseif ($featured): ?>
                Featured Products
            <?php elseif ($sale): ?>
                Sale Products
            <?php else: ?>
                All Products
            <?php endif; ?>
        </h1>
        
        <?php if ($currentCategory && $currentCategory['description']): ?>
            <p class="text-gray-600 mt-2"><?php echo htmlspecialchars($currentCategory['description']); ?></p>
        <?php endif; ?>
        
        <p class="text-gray-600 mt-2"><?php echo number_format($totalProducts); ?> products found</p>
    </div>

    <div class="flex flex-col lg:flex-row gap-8">
        <!-- Filters Sidebar -->
        <div class="lg:w-1/4">
            <div class="bg-white rounded-lg shadow-md p-6 sticky top-24">
                <h3 class="text-lg font-semibold mb-4">Filters</h3>
                
                <form method="GET" action="" id="filter-form">
                    <input type="hidden" name="page" value="products">
                    <?php if ($search): ?>
                        <input type="hidden" name="q" value="<?php echo htmlspecialchars($search); ?>">
                    <?php endif; ?>
                    
                    <!-- Categories -->
                    <div class="mb-6">
                        <h4 class="font-semibold mb-3">Categories</h4>
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input type="radio" name="category" value="" <?php echo !$category ? 'checked' : ''; ?> 
                                       class="mr-2" onchange="document.getElementById('filter-form').submit()">
                                <span>All Categories</span>
                            </label>
                            <?php foreach ($categories as $cat): ?>
                                <label class="flex items-center">
                                    <input type="radio" name="category" value="<?php echo $cat['slug']; ?>" 
                                           <?php echo $category === $cat['slug'] ? 'checked' : ''; ?>
                                           class="mr-2" onchange="document.getElementById('filter-form').submit()">
                                    <span><?php echo htmlspecialchars($cat['name']); ?> (<?php echo $cat['product_count']; ?>)</span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
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
                <?php if ($category || $search || $featured || $sale || $minPrice || $maxPrice): ?>
                    <a href="?page=products" class="text-sm text-primary hover:text-opacity-80">
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
                    <i class="fas fa-search text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-600 mb-2">No Products Found</h3>
                    <p class="text-gray-500 mb-6">Try adjusting your search criteria or browse all products.</p>
                    <a href="?page=products" class="bg-primary text-white px-6 py-3 rounded-md hover:bg-opacity-90">
                        Browse All Products
                    </a>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-8">
                    <?php foreach ($products as $product): ?>
                        <?php include '../includes/product-card.php'; ?>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="flex justify-center">
                        <nav class="flex items-center space-x-2">
                            <?php if ($page > 1): ?>
                                <a href="<?php echo buildPaginationUrl($page - 1); ?>" 
                                   class="px-3 py-2 border border-gray-300 rounded-md hover:bg-gray-50">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            <?php endif; ?>
                            
                            <?php
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            
                            if ($startPage > 1) {
                                echo '<a href="' . buildPaginationUrl(1) . '" class="px-3 py-2 border border-gray-300 rounded-md hover:bg-gray-50">1</a>';
                                if ($startPage > 2) {
                                    echo '<span class="px-3 py-2">...</span>';
                                }
                            }
                            
                            for ($i = $startPage; $i <= $endPage; $i++) {
                                $class = $i === $page ? 'bg-primary text-white' : 'border border-gray-300 hover:bg-gray-50';
                                echo '<a href="' . buildPaginationUrl($i) . '" class="px-3 py-2 rounded-md ' . $class . '">' . $i . '</a>';
                            }
                            
                            if ($endPage < $totalPages) {
                                if ($endPage < $totalPages - 1) {
                                    echo '<span class="px-3 py-2">...</span>';
                                }
                                echo '<a href="' . buildPaginationUrl($totalPages) . '" class="px-3 py-2 border border-gray-300 rounded-md hover:bg-gray-50">' . $totalPages . '</a>';
                            }
                            ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <a href="<?php echo buildPaginationUrl($page + 1); ?>" 
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
function buildPaginationUrl($pageNum) {
    $params = $_GET;
    $params['page_num'] = $pageNum;
    return '?' . http_build_query($params);
}
?>
