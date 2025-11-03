<?php
$pageTitle = 'Category Management';
$pageDescription = 'Manage product categories';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    echo '<script>window.location.href = "?page=login";</script>';
    exit();
}

global $database;

$success = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'add_category') {
            $name = trim($_POST['name'] ?? '');
            $slug = trim($_POST['slug'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $parentId = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
            $sortOrder = intval($_POST['sort_order'] ?? 0);
            
            if (empty($name)) {
                $error = 'Category name is required.';
            } else {
                // Auto-generate slug if not provided
                if (empty($slug)) {
                    $slug = strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $name));
                }
                
                // Check if slug already exists
                $existingSlug = $database->fetchOne("SELECT id FROM categories WHERE slug = ?", [$slug]);
                if ($existingSlug) {
                    $error = 'Category slug already exists. Please use a unique slug.';
                } else {
                    $categoryId = $database->insert('categories', [
                        'name' => $name,
                        'slug' => $slug,
                        'description' => $description,
                        'parent_id' => $parentId,
                        'sort_order' => $sortOrder,
                        'is_active' => true
                    ]);
                    
                    if ($categoryId) {
                        $success = 'Category added successfully!';
                    } else {
                        $error = 'Failed to add category. Please try again.';
                    }
                }
            }
        }
        
        elseif ($action === 'update_category') {
            $categoryId = intval($_POST['category_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $slug = trim($_POST['slug'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $parentId = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
            $sortOrder = intval($_POST['sort_order'] ?? 0);
            $isActive = isset($_POST['is_active']) ? 1 : 0;
            
            if (empty($name) || $categoryId <= 0) {
                $error = 'Invalid category data.';
            } else {
                // Check if slug already exists for other categories
                $existingSlug = $database->fetchOne("SELECT id FROM categories WHERE slug = ? AND id != ?", [$slug, $categoryId]);
                if ($existingSlug) {
                    $error = 'Category slug already exists. Please use a unique slug.';
                } else {
                    $updated = $database->update('categories', [
                        'name' => $name,
                        'slug' => $slug,
                        'description' => $description,
                        'parent_id' => $parentId,
                        'sort_order' => $sortOrder,
                        'is_active' => $isActive
                    ], 'id = ?', [$categoryId]);
                    
                    if ($updated) {
                        $success = 'Category updated successfully!';
                    } else {
                        $error = 'Failed to update category.';
                    }
                }
            }
        }
        
        elseif ($action === 'delete_category') {
            $categoryId = intval($_POST['category_id'] ?? 0);
            
            if ($categoryId <= 0) {
                $error = 'Invalid category ID.';
            } else {
                // Check if category has products
                $productCount = $database->count('products', 'category_id = ?', [$categoryId]);
                if ($productCount > 0) {
                    $error = 'Cannot delete category with existing products. Move products to another category first.';
                } else {
                    $deleted = $database->delete('categories', 'id = ?', [$categoryId]);
                    if ($deleted) {
                        $success = 'Category deleted successfully!';
                    } else {
                        $error = 'Failed to delete category.';
                    }
                }
            }
        }
    }
}

// Get all categories with parent names and product counts
$categories = $database->fetchAll("
    SELECT c.*, 
           p.name as parent_name,
           (SELECT COUNT(*) FROM products WHERE category_id = c.id) as product_count
    FROM categories c
    LEFT JOIN categories p ON c.parent_id = p.id
    ORDER BY c.sort_order, c.name
");

// Get categories for parent dropdown (excluding the current one being edited)
$parentCategories = $database->fetchAll("SELECT id, name FROM categories WHERE parent_id IS NULL ORDER BY name");

// Check if editing a category
$editingCategory = null;
if (isset($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    $editingCategory = $database->fetchOne("SELECT * FROM categories WHERE id = ?", [$editId]);
}
?>

<!-- Modern Admin Categories Page -->
<div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-50" style="padding-top: 80px;">
    
    <!-- Mobile Header -->
    <div class="lg:hidden fixed top-16 left-0 right-0 bg-white shadow-lg border-b px-4 py-4 z-30">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-lg flex items-center justify-center">
                    <i class="fas fa-tags text-white text-sm"></i>
                </div>
                <h1 class="text-lg font-bold text-gray-800">Categories</h1>
            </div>
            <button onclick="toggleAdminSidebar()" class="p-2 rounded-lg bg-gray-100 hover:bg-gray-200 transition-colors">
                <i class="fas fa-bars text-gray-600"></i>
            </button>
        </div>
    </div>

    <div class="flex">
        <!-- Modern Admin Sidebar -->
        <div id="admin-sidebar" class="fixed top-16 bottom-0 left-0 z-20 w-72 bg-white shadow-2xl transform -translate-x-full transition-all duration-300 ease-in-out lg:translate-x-0 lg:static lg:h-auto border-r border-gray-200">
            
            <!-- Admin Profile Header -->
            <div class="p-6 bg-gradient-to-r from-blue-500 to-indigo-500">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 bg-white/20 rounded-2xl flex items-center justify-center">
                            <i class="fas fa-user-shield text-white text-xl"></i>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-white">Admin Panel</h2>
                            <div class="flex items-center text-blue-100 text-sm">
                                <i class="fas fa-check-circle mr-1"></i>Administrator
                            </div>
                        </div>
                    </div>
                    <button onclick="toggleAdminSidebar()" class="lg:hidden p-1 rounded-lg bg-white/20 hover:bg-white/30 transition-colors">
                        <i class="fas fa-times text-white"></i>
                    </button>
                </div>
            </div>
            
            <!-- Navigation Menu -->
            <nav class="p-4 space-y-2">
                <div class="px-3 py-2">
                    <span class="text-xs uppercase text-gray-500 font-semibold tracking-wider">Dashboard</span>
                </div>
                
                <a href="?page=admin" class="group flex items-center px-4 py-3 rounded-xl transition-all duration-200 text-gray-600 hover:text-blue-600 hover:bg-blue-50">
                    <div class="w-9 h-9 rounded-lg bg-gray-100 group-hover:bg-blue-100 flex items-center justify-center mr-3 transition-colors">
                        <i class="fas fa-tachometer-alt text-sm"></i>
                    </div>
                    <span class="font-medium">Overview</span>
                </a>
                
                <div class="pt-4 mt-4 border-t border-gray-200">
                    <div class="px-3 py-2">
                        <span class="text-xs uppercase text-gray-500 font-semibold tracking-wider">Management</span>
                    </div>
                    
                    <a href="?page=admin&section=vendors" class="group flex items-center px-4 py-3 rounded-xl transition-all duration-200 text-gray-600 hover:text-blue-600 hover:bg-blue-50">
                        <div class="w-9 h-9 rounded-lg bg-gray-100 group-hover:bg-blue-100 flex items-center justify-center mr-3 transition-colors">
                            <i class="fas fa-store text-sm"></i>
                        </div>
                        <span class="font-medium">Vendors</span>
                    </a>
                    
                    <a href="?page=admin&section=products" class="group flex items-center px-4 py-3 rounded-xl transition-all duration-200 text-gray-600 hover:text-blue-600 hover:bg-blue-50">
                        <div class="w-9 h-9 rounded-lg bg-gray-100 group-hover:bg-blue-100 flex items-center justify-center mr-3 transition-colors">
                            <i class="fas fa-box text-sm"></i>
                        </div>
                        <span class="font-medium">Products</span>
                    </a>
                    
                    <a href="?page=admin&section=categories" class="group flex items-center px-4 py-3 rounded-xl transition-all duration-200 text-white bg-gradient-to-r from-blue-500 to-indigo-500 shadow-lg">
                        <div class="w-9 h-9 rounded-lg bg-white/20 flex items-center justify-center mr-3">
                            <i class="fas fa-tags text-sm"></i>
                        </div>
                        <span class="font-medium">Categories</span>
                        <div class="ml-auto w-2 h-2 bg-white rounded-full"></div>
                    </a>
                    
                    <a href="?page=admin&section=users" class="group flex items-center px-4 py-3 rounded-xl transition-all duration-200 text-gray-600 hover:text-blue-600 hover:bg-blue-50">
                        <div class="w-9 h-9 rounded-lg bg-gray-100 group-hover:bg-blue-100 flex items-center justify-center mr-3 transition-colors">
                            <i class="fas fa-users text-sm"></i>
                        </div>
                        <span class="font-medium">Users</span>
                    </a>
                </div>
                
                <div class="pt-4 mt-4 border-t border-gray-200">
                    <div class="px-3 py-2">
                        <span class="text-xs uppercase text-gray-500 font-semibold tracking-wider">Account</span>
                    </div>
                    
                    <a href="?page=logout" class="group flex items-center px-4 py-3 rounded-xl transition-all duration-200 text-red-600 hover:text-red-700 hover:bg-red-50">
                        <div class="w-9 h-9 rounded-lg bg-red-100 group-hover:bg-red-200 flex items-center justify-center mr-3 transition-colors">
                            <i class="fas fa-sign-out-alt text-sm"></i>
                        </div>
                        <span class="font-medium">Logout</span>
                    </a>
                </div>
            </nav>
        </div>

        <!-- Sidebar Overlay for Mobile -->
        <div id="admin-overlay" class="fixed inset-0 bg-black/50 z-10 lg:hidden hidden backdrop-blur-sm" onclick="toggleAdminSidebar()"></div>

        <!-- Main Content Area -->
        <div class="flex-1 lg:ml-0 pt-20 lg:pt-0">
            <div class="p-6 lg:p-8 max-w-7xl mx-auto">
                
                <!-- Page Header -->
                <div class="mb-8">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-6">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-2xl flex items-center justify-center shadow-lg">
                                <i class="fas fa-tags text-white text-xl"></i>
                            </div>
                            <div>
                                <h1 class="text-3xl font-bold text-gray-900">Category Management</h1>
                                <p class="text-gray-600 mt-1">Manage product categories and organization</p>
                            </div>
                        </div>
                        <div class="mt-4 lg:mt-0">
                            <a href="?page=admin&section=categories&action=add" 
                               class="inline-flex items-center bg-gradient-to-r from-blue-500 to-indigo-500 text-white px-6 py-3 rounded-2xl font-semibold hover:shadow-lg transition-all duration-200">
                                <i class="fas fa-plus mr-2"></i>Add New Category
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Success/Error Messages -->
                <?php if ($success): ?>
                    <div class="mb-6 bg-green-100 border-l-4 border-green-500 text-green-700 p-6 rounded-2xl shadow-lg">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-2xl mr-3"></i>
                            <div>
                                <p class="font-semibold"><?php echo htmlspecialchars($success); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="mb-6 bg-red-100 border-l-4 border-red-500 text-red-700 p-6 rounded-2xl shadow-lg">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle text-2xl mr-3"></i>
                            <div>
                                <p class="font-semibold"><?php echo htmlspecialchars($error); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['action']) && $_GET['action'] === 'add' || $editingCategory): ?>
                    <!-- Add/Edit Category Form -->
                    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 mb-8">
                        <div class="p-8">
                            <div class="flex items-center space-x-4 mb-8">
                                <div class="w-12 h-12 bg-gradient-to-r from-green-500 to-green-600 rounded-2xl flex items-center justify-center shadow-lg">
                                    <i class="fas fa-<?php echo $editingCategory ? 'edit' : 'plus'; ?> text-white text-xl"></i>
                                </div>
                                <div>
                                    <h2 class="text-2xl font-bold text-gray-900">
                                        <?php echo $editingCategory ? 'Edit Category' : 'Add New Category'; ?>
                                    </h2>
                                    <p class="text-gray-600">Fill in the category details below</p>
                                </div>
                            </div>
                            
                            <form method="POST" class="space-y-6">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                <input type="hidden" name="action" value="<?php echo $editingCategory ? 'update_category' : 'add_category'; ?>">
                                <?php if ($editingCategory): ?>
                                    <input type="hidden" name="category_id" value="<?php echo $editingCategory['id']; ?>">
                                <?php endif; ?>
                                
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                    <!-- Category Name -->
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Category Name *</label>
                                        <input type="text" name="name" required
                                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                               value="<?php echo $editingCategory ? htmlspecialchars($editingCategory['name']) : ''; ?>"
                                               placeholder="Enter category name">
                                    </div>
                                    
                                    <!-- Category Slug -->
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">URL Slug *</label>
                                        <input type="text" name="slug" required
                                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                               value="<?php echo $editingCategory ? htmlspecialchars($editingCategory['slug']) : ''; ?>"
                                               placeholder="category-url-slug">
                                    </div>
                                    
                                    <!-- Parent Category -->
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Parent Category</label>
                                        <select name="parent_id"
                                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
                                            <option value="">None (Top Level)</option>
                                            <?php foreach ($parentCategories as $parent): ?>
                                                <?php if (!$editingCategory || $parent['id'] != $editingCategory['id']): ?>
                                                    <option value="<?php echo $parent['id']; ?>" 
                                                            <?php echo ($editingCategory && $editingCategory['parent_id'] == $parent['id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($parent['name']); ?>
                                                    </option>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <!-- Sort Order -->
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Sort Order</label>
                                        <input type="number" name="sort_order" min="0"
                                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                               value="<?php echo $editingCategory ? $editingCategory['sort_order'] : '0'; ?>"
                                               placeholder="0">
                                    </div>
                                </div>
                                
                                <!-- Description -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Description</label>
                                    <textarea name="description" rows="3"
                                              class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                              placeholder="Describe this category..."><?php echo $editingCategory ? htmlspecialchars($editingCategory['description']) : ''; ?></textarea>
                                </div>
                                
                                <?php if ($editingCategory): ?>
                                    <!-- Active Status -->
                                    <div>
                                        <label class="flex items-center">
                                            <input type="checkbox" name="is_active" 
                                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                                                   <?php echo $editingCategory['is_active'] ? 'checked' : ''; ?>>
                                            <span class="ml-2 text-sm font-semibold text-gray-700">Category is active</span>
                                        </label>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Action Buttons -->
                                <div class="flex flex-col sm:flex-row gap-4 pt-6 border-t border-gray-200">
                                    <button type="submit" 
                                            class="flex-1 bg-gradient-to-r from-blue-500 to-indigo-500 text-white py-3 px-6 rounded-2xl font-semibold hover:shadow-lg transition-all duration-200 flex items-center justify-center">
                                        <i class="fas fa-save mr-2"></i>
                                        <?php echo $editingCategory ? 'Update Category' : 'Add Category'; ?>
                                    </button>
                                    <a href="?page=admin&section=categories" 
                                       class="flex-1 bg-gray-600 text-white py-3 px-6 rounded-2xl font-semibold text-center hover:bg-gray-700 transition-all duration-200 flex items-center justify-center">
                                        <i class="fas fa-times mr-2"></i>Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Categories List -->
                    <div class="bg-white rounded-2xl shadow-lg border border-gray-100">
                        <?php if (empty($categories)): ?>
                            <div class="p-12 text-center">
                                <div class="w-24 h-24 mx-auto mb-6 bg-gray-100 rounded-3xl flex items-center justify-center">
                                    <i class="fas fa-tags text-4xl text-gray-400"></i>
                                </div>
                                <h3 class="text-xl font-semibold text-gray-600 mb-2">No Categories Yet</h3>
                                <p class="text-gray-500 mb-6">Start organizing your products by adding categories</p>
                                <a href="?page=admin&section=categories&action=add" 
                                   class="inline-flex items-center bg-gradient-to-r from-blue-500 to-indigo-500 text-white px-6 py-3 rounded-2xl font-semibold hover:shadow-lg transition-all duration-200">
                                    <i class="fas fa-plus mr-2"></i>Add First Category
                                </a>
                            </div>
                        <?php else: ?>
                            <!-- Table Header -->
                            <div class="p-6 border-b border-gray-200">
                                <div class="grid grid-cols-6 gap-4 text-sm font-semibold text-gray-700 uppercase tracking-wider">
                                    <div class="col-span-2">Category</div>
                                    <div>Parent</div>
                                    <div>Products</div>
                                    <div>Status</div>
                                    <div class="text-right">Actions</div>
                                </div>
                            </div>
                            
                            <!-- Categories List -->
                            <div class="divide-y divide-gray-200">
                                <?php foreach ($categories as $category): ?>
                                    <div class="p-6 hover:bg-gray-50 transition-colors duration-200">
                                        <div class="grid grid-cols-6 gap-4 items-center">
                                            <div class="col-span-2">
                                                <div class="flex items-center space-x-3">
                                                    <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-xl flex items-center justify-center">
                                                        <i class="fas fa-tag text-white"></i>
                                                    </div>
                                                    <div>
                                                        <h3 class="font-semibold text-gray-900"><?php echo htmlspecialchars($category['name']); ?></h3>
                                                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($category['slug']); ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div>
                                                <span class="text-sm text-gray-600">
                                                    <?php echo $category['parent_name'] ? htmlspecialchars($category['parent_name']) : 'Top Level'; ?>
                                                </span>
                                            </div>
                                            <div>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                    <?php echo $category['product_count']; ?> products
                                                </span>
                                            </div>
                                            <div>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                    <?php echo $category['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                                    <?php echo $category['is_active'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </div>
                                            <div class="text-right">
                                                <div class="flex items-center justify-end space-x-2">
                                                    <a href="?page=admin&section=categories&edit=<?php echo $category['id']; ?>" 
                                                       class="p-2 text-blue-600 hover:bg-blue-100 rounded-lg transition-colors">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <?php if ($category['product_count'] == 0): ?>
                                                        <button onclick="deleteCategory(<?php echo $category['id']; ?>)" 
                                                                class="p-2 text-red-600 hover:bg-red-100 rounded-lg transition-colors">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function toggleAdminSidebar() {
    const sidebar = document.getElementById('admin-sidebar');
    const overlay = document.getElementById('admin-overlay');
    
    sidebar.classList.toggle('-translate-x-full');
    overlay.classList.toggle('hidden');
}

function deleteCategory(categoryId) {
    if (confirm('Are you sure you want to delete this category? This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="delete_category">
            <input type="hidden" name="category_id" value="${categoryId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
