<?php
// Professional Hero Banners - Sasto Hub Original Design
// Simply change the image URLs and links below
?>

<section class="hero-banners mb-6 lg:mb-10">
    <!-- Mobile: Full-width Slider -->
    <div class="lg:hidden relative">
        <div class="hero-slider overflow-hidden">
            <!-- Banner 1: Featured Products -->
            <div class="hero-slide active relative">
                <a href="?page=products&featured=1" class="block">
                    <div class="relative h-60 bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-500 overflow-hidden">
                        <!-- Change this image URL -->
                        <img src="https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=800&h=240&fit=crop&q=80" 
                             alt="Featured Products" 
                             class="absolute inset-0 w-full h-full object-cover opacity-80">
                        
                        <!-- Overlay Content -->
                        <div class="absolute inset-0 bg-gradient-to-r from-black/60 via-black/40 to-transparent"></div>
                        <div class="absolute inset-0 p-6 flex flex-col justify-center">
                            <div class="max-w-xs">
                                <div class="inline-flex items-center bg-white/20 backdrop-blur-sm rounded-full px-4 py-2 mb-3">
                                    <span class="text-white text-sm font-medium">⭐ FEATURED</span>
                                </div>
                                <h1 class="text-white text-2xl font-bold mb-2 leading-tight">Premium Quality Products</h1>
                                <p class="text-white/90 text-sm mb-4">Handpicked items for our valued customers</p>
                                <div class="inline-flex items-center text-white font-semibold">
                                    <span class="mr-2">Explore Collection</span>
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Decorative Elements -->
                        <div class="absolute top-4 right-4 w-20 h-20 bg-white/10 rounded-full blur-xl"></div>
                        <div class="absolute bottom-6 right-6 w-32 h-32 bg-gradient-to-br from-white/20 to-transparent rounded-full blur-2xl"></div>
                    </div>
                </a>
            </div>

            <!-- Banner 2: Flash Sale -->
            <div class="hero-slide relative">
                <a href="?page=products&sale=1" class="block">
                    <div class="relative h-60 bg-gradient-to-br from-red-500 via-orange-500 to-yellow-500 overflow-hidden">
                        <!-- Change this image URL -->
                        <img src="https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?w=800&h=240&fit=crop&q=80" 
                             alt="Flash Sale" 
                             class="absolute inset-0 w-full h-full object-cover opacity-75">
                        
                        <!-- Overlay Content -->
                        <div class="absolute inset-0 bg-gradient-to-r from-red-900/70 via-red-800/50 to-transparent"></div>
                        <div class="absolute inset-0 p-6 flex flex-col justify-center">
                            <div class="max-w-xs">
                                <div class="inline-flex items-center bg-yellow-400 text-red-900 rounded-full px-4 py-2 mb-3">
                                    <span class="text-sm font-bold">🔥 FLASH SALE</span>
                                </div>
                                <h1 class="text-white text-2xl font-bold mb-2 leading-tight">Up to 70% OFF</h1>
                                <p class="text-white/90 text-sm mb-4">Limited time offers on top brands</p>
                                <div class="inline-flex items-center text-white font-semibold">
                                    <span class="mr-2">Shop Now</span>
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Animated Elements -->
                        <div class="absolute top-2 right-4 w-16 h-16 bg-yellow-400/30 rounded-full animate-pulse"></div>
                        <div class="absolute bottom-4 right-8 w-24 h-24 bg-orange-400/20 rounded-full animate-bounce"></div>
                    </div>
                </a>
            </div>

            <!-- Banner 3: Electronics -->
            <div class="hero-slide relative">
                <a href="?page=category&slug=electronics" class="block">
                    <div class="relative h-60 bg-gradient-to-br from-blue-600 via-cyan-500 to-teal-400 overflow-hidden">
                        <!-- Change this image URL -->
                        <img src="https://images.unsplash.com/photo-1498049794561-7780e7231661?w=800&h=240&fit=crop&q=80" 
                             alt="Electronics" 
                             class="absolute inset-0 w-full h-full object-cover opacity-85">
                        
                        <!-- Overlay Content -->
                        <div class="absolute inset-0 bg-gradient-to-r from-blue-900/60 via-blue-800/40 to-transparent"></div>
                        <div class="absolute inset-0 p-6 flex flex-col justify-center">
                            <div class="max-w-xs">
                                <div class="inline-flex items-center bg-cyan-400/20 backdrop-blur-sm text-white rounded-full px-4 py-2 mb-3">
                                    <span class="text-sm font-medium">⚡ TECH</span>
                                </div>
                                <h1 class="text-white text-2xl font-bold mb-2 leading-tight">Latest Electronics</h1>
                                <p class="text-white/90 text-sm mb-4">Cutting-edge gadgets & technology</p>
                                <div class="inline-flex items-center text-white font-semibold">
                                    <span class="mr-2">Browse Tech</span>
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tech Elements -->
                        <div class="absolute top-6 right-6 w-12 h-12 border-2 border-white/30 rounded-lg rotate-12"></div>
                        <div class="absolute bottom-8 right-4 w-8 h-8 border-2 border-cyan-300/40 rounded-full"></div>
                    </div>
                </a>
            </div>
        </div>

        <!-- Mobile Navigation Dots -->
        <div class="flex justify-center mt-4 space-x-2">
            <button onclick="showSlide(0)" class="slide-dot w-2 h-2 bg-gray-400 rounded-full transition-all duration-300 active"></button>
            <button onclick="showSlide(1)" class="slide-dot w-2 h-2 bg-gray-400 rounded-full transition-all duration-300"></button>
            <button onclick="showSlide(2)" class="slide-dot w-2 h-2 bg-gray-400 rounded-full transition-all duration-300"></button>
        </div>
    </div>

    <!-- Desktop: Grid Layout -->
    <div class="hidden lg:block">
        <div class="container mx-auto px-4">
            <!-- Main Large Banner -->
            <div class="mb-6">
                <a href="?page=products&featured=1" class="block group">
                    <div class="relative h-80 rounded-2xl overflow-hidden bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-500">
                        <!-- Change this image URL -->
                        <img src="https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=1200&h=320&fit=crop&q=80" 
                             alt="Featured Products" 
                             class="absolute inset-0 w-full h-full object-cover opacity-90 group-hover:scale-105 transition-transform duration-700">
                        
                        <div class="absolute inset-0 bg-gradient-to-r from-black/50 via-transparent to-black/30"></div>
                        
                        <!-- Content -->
                        <div class="absolute inset-0 flex items-center">
                            <div class="px-12 max-w-2xl">
                                <div class="inline-flex items-center bg-white/20 backdrop-blur-md rounded-full px-6 py-3 mb-6">
                                    <span class="text-white font-semibold">⭐ FEATURED COLLECTION</span>
                                </div>
                                <h1 class="text-white text-5xl font-bold mb-4 leading-tight">Premium Quality Products</h1>
                                <p class="text-white/90 text-lg mb-8 max-w-lg">Discover our handpicked selection of premium products from trusted vendors worldwide</p>
                                <div class="inline-flex items-center bg-white text-gray-900 px-8 py-4 rounded-xl font-bold text-lg group-hover:bg-yellow-400 transition-colors duration-300">
                                    <span class="mr-3">Explore Collection</span>
                                    <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Decorative Elements -->
                        <div class="absolute top-8 right-8 w-32 h-32 bg-white/10 rounded-full blur-3xl"></div>
                        <div class="absolute bottom-12 right-12 w-48 h-48 bg-gradient-to-br from-pink-400/30 to-purple-400/30 rounded-full blur-3xl"></div>
                    </div>
                </a>
            </div>
            
            <!-- Two Smaller Banners -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Flash Sale Banner -->
                <a href="?page=products&sale=1" class="block group">
                    <div class="relative h-48 rounded-xl overflow-hidden bg-gradient-to-br from-red-500 via-orange-500 to-yellow-500">
                        <!-- Change this image URL -->
                        <img src="https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?w=600&h=200&fit=crop&q=80" 
                             alt="Flash Sale" 
                             class="absolute inset-0 w-full h-full object-cover opacity-85 group-hover:scale-105 transition-transform duration-500">
                        
                        <div class="absolute inset-0 bg-gradient-to-r from-red-900/60 to-transparent"></div>
                        
                        <div class="absolute inset-0 flex items-center px-8">
                            <div>
                                <div class="inline-flex items-center bg-yellow-400 text-red-900 rounded-full px-4 py-2 mb-3">
                                    <span class="font-bold text-sm">🔥 FLASH SALE</span>
                                </div>
                                <h2 class="text-white text-3xl font-bold mb-2">Up to 70% OFF</h2>
                                <p class="text-white/90 mb-4">Limited time offers</p>
                                <div class="inline-flex items-center text-white font-semibold group-hover:translate-x-1 transition-transform duration-300">
                                    <span class="mr-2">Shop Deals</span>
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        
                        <div class="absolute top-4 right-4 w-20 h-20 bg-yellow-400/30 rounded-full animate-pulse"></div>
                    </div>
                </a>
                
                <!-- Electronics Banner -->
                <a href="?page=category&slug=electronics" class="block group">
                    <div class="relative h-48 rounded-xl overflow-hidden bg-gradient-to-br from-blue-600 via-cyan-500 to-teal-400">
                        <!-- Change this image URL -->
                        <img src="https://images.unsplash.com/photo-1498049794561-7780e7231661?w=600&h=200&fit=crop&q=80" 
                             alt="Electronics" 
                             class="absolute inset-0 w-full h-full object-cover opacity-90 group-hover:scale-105 transition-transform duration-500">
                        
                        <div class="absolute inset-0 bg-gradient-to-r from-blue-900/60 to-transparent"></div>
                        
                        <div class="absolute inset-0 flex items-center px-8">
                            <div>
                                <div class="inline-flex items-center bg-cyan-400/20 backdrop-blur-sm text-white rounded-full px-4 py-2 mb-3">
                                    <span class="font-medium text-sm">⚡ TECHNOLOGY</span>
                                </div>
                                <h2 class="text-white text-3xl font-bold mb-2">Latest Electronics</h2>
                                <p class="text-white/90 mb-4">Cutting-edge gadgets</p>
                                <div class="inline-flex items-center text-white font-semibold group-hover:translate-x-1 transition-transform duration-300">
                                    <span class="mr-2">Browse Tech</span>
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        
                        <div class="absolute top-6 right-6 w-16 h-16 border-2 border-white/30 rounded-lg rotate-12 group-hover:rotate-45 transition-transform duration-500"></div>
                    </div>
                </a>
            </div>
        </div>
    </div>
</section>

<style>
.hero-slide {
    display: none;
}

.hero-slide.active {
    display: block;
    animation: slideInFade 0.6s ease-out;
}

.slide-dot.active {
    background-color: #ff6b35 !important;
    transform: scale(1.2);
}

@keyframes slideInFade {
    0% {
        opacity: 0;
        transform: translateX(20px) scale(0.98);
    }
    100% {
        opacity: 1;
        transform: translateX(0) scale(1);
    }
}

/* Backdrop blur support */
@supports (backdrop-filter: blur(10px)) {
    .backdrop-blur-sm {
        backdrop-filter: blur(4px);
    }
    .backdrop-blur-md {
        backdrop-filter: blur(12px);
    }
}

/* Hover effects */
@media (hover: hover) {
    .group:hover .group-hover\:scale-105 {
        transform: scale(1.05);
    }
    .group:hover .group-hover\:translate-x-1 {
        transform: translateX(0.25rem);
    }
    .group:hover .group-hover\:rotate-45 {
        transform: rotate(45deg);
    }
    .group:hover .group-hover\:bg-yellow-400 {
        background-color: #fbbf24;
    }
}
</style>

<script>
let currentSlide = 0;
let slideInterval;

function showSlide(index) {
    const slides = document.querySelectorAll('.hero-slide');
    const dots = document.querySelectorAll('.slide-dot');
    
    // Remove active class from all
    slides.forEach(slide => slide.classList.remove('active'));
    dots.forEach(dot => dot.classList.remove('active'));
    
    // Add active class to selected
    if (slides[index]) {
        slides[index].classList.add('active');
        dots[index].classList.add('active');
        currentSlide = index;
    }
}

function nextSlide() {
    const totalSlides = document.querySelectorAll('.hero-slide').length;
    const next = (currentSlide + 1) % totalSlides;
    showSlide(next);
}

function startSlider() {
    slideInterval = setInterval(nextSlide, 5000);
}

function stopSlider() {
    if (slideInterval) {
        clearInterval(slideInterval);
    }
}

// Initialize slider
document.addEventListener('DOMContentLoaded', function() {
    if (window.innerWidth < 1024) {
        startSlider();
    }
});

// Handle resize
window.addEventListener('resize', function() {
    if (window.innerWidth < 1024) {
        startSlider();
    } else {
        stopSlider();
    }
});

// Pause on interaction
document.querySelectorAll('.slide-dot').forEach(dot => {
    dot.addEventListener('click', function() {
        stopSlider();
        setTimeout(startSlider, 8000); // Restart after 8 seconds
    });
});
</script>
