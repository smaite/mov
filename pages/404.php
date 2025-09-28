<?php
$pageTitle = "Page Not Found";
$pageDescription = "The requested page could not be found.";
?>

<div class="container mx-auto px-4 py-16">
    <div class="text-center">
        <div class="mb-8">
            <i class="fas fa-exclamation-triangle text-6xl text-primary mb-4"></i>
            <h1 class="text-6xl font-bold text-gray-800 mb-4">404</h1>
            <h2 class="text-2xl font-semibold text-gray-600 mb-4">Page Not Found</h2>
            <p class="text-gray-500 mb-8 max-w-md mx-auto">
                Sorry, the page you are looking for could not be found. It might have been moved, deleted, or never existed.
            </p>
        </div>
        
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="<?php echo SITE_URL; ?>" 
               class="bg-primary text-white px-6 py-3 rounded-md hover:bg-opacity-90 font-semibold">
                <i class="fas fa-home mr-2"></i>Go Home
            </a>
            <a href="?page=products" 
               class="bg-secondary text-white px-6 py-3 rounded-md hover:bg-opacity-90 font-semibold">
                <i class="fas fa-shopping-bag mr-2"></i>Browse Products
            </a>
        </div>
    </div>
</div>
