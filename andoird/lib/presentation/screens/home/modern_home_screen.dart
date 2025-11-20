import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../../app/theme.dart';
import '../../providers/auth_provider.dart';
import '../../providers/product_provider.dart';
import '../search/search_screen.dart';
import '../cart/cart_screen.dart';
import '../profile/profile_screen.dart';
import '../home/product_detail_screen.dart';
import '../home/widgets/product_card.dart';
import '../home/widgets/category_card.dart';
import '../home/widgets/hero_banner.dart';
import '../home/widgets/deal_of_day.dart';
import '../home/widgets/flash_sale_banner.dart';

class ModernHomeScreen extends StatefulWidget {
  const ModernHomeScreen({super.key});

  @override
  State<ModernHomeScreen> createState() => _ModernHomeScreenState();
}

class _ModernHomeScreenState extends State<ModernHomeScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;
  final ScrollController _scrollController = ScrollController();
  bool _isScrolled = false;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 5, vsync: this);
    _scrollController.addListener(_onScroll);
    
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<ProductProvider>().loadHomeData();
    });
  }

  @override
  void dispose() {
    _tabController.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  void _onScroll() {
    if (_scrollController.offset > 50 && !_isScrolled) {
      setState(() => _isScrolled = true);
    } else if (_scrollController.offset <= 50 && _isScrolled) {
      setState(() => _isScrolled = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: NestedScrollView(
        controller: _scrollController,
        headerSliverBuilder: (context, innerBoxIsScrolled) {
          return [
            SliverAppBar(
              expandedHeight: 120,
              floating: false,
              pinned: true,
              backgroundColor: _isScrolled ? Colors.white : Colors.transparent,
              elevation: _isScrolled ? 4 : 0,
              flexibleSpace: FlexibleSpaceBar(
                background: Container(
                  decoration: BoxDecoration(
                    gradient: AppTheme.primaryGradient,
                  ),
                  child: SafeArea(
                    child: Padding(
                      padding: const EdgeInsets.all(16),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const SizedBox(height: 20),
                          Row(
                            children: [
                              Expanded(
                                child: Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                                  decoration: BoxDecoration(
                                    color: Colors.white.withOpacity(0.9),
                                    borderRadius: BorderRadius.circular(25),
                                  ),
                                  child: Row(
                                    children: [
                                      const Icon(Icons.search, color: Colors.grey),
                                      const SizedBox(width: 8),
                                      Expanded(
                                        child: GestureDetector(
                                          onTap: () {
                                            Navigator.push(
                                              context,
                                              MaterialPageRoute(
                                                builder: (context) => const SearchScreen(),
                                              ),
                                            );
                                          },
                                          child: Text(
                                            'Search products...',
                                            style: TextStyle(
                                              color: Colors.grey[600],
                                              fontSize: 14,
                                            ),
                                          ),
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                              ),
                              const SizedBox(width: 12),
                              Consumer<AuthProvider>(
                                builder: (context, authProvider, child) {
                                  return Stack(
                                    children: [
                                      IconButton(
                                        icon: const Icon(Icons.notifications_outlined, color: Colors.white),
                                        onPressed: () {
                                          // TODO: Implement notifications
                                        },
                                      ),
                                      if (authProvider.isAuthenticated)
                                        Positioned(
                                          right: 8,
                                          top: 8,
                                          child: Container(
                                            width: 8,
                                            height: 8,
                                            decoration: const BoxDecoration(
                                              color: Colors.red,
                                              shape: BoxShape.circle,
                                            ),
                                          ),
                                        ),
                                    ],
                                  );
                                },
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
              ),
              actions: [
                Consumer<AuthProvider>(
                  builder: (context, authProvider, child) {
                    if (authProvider.isAuthenticated) {
                      return PopupMenuButton<String>(
                        icon: CircleAvatar(
                          radius: 16,
                          backgroundImage: authProvider.user?.profileImage != null
                              ? CachedNetworkImageProvider(authProvider.user!.profileImage!)
                              : null,
                          child: authProvider.user?.profileImage == null
                              ? Text(
                                  authProvider.user?.firstName?.substring(0, 1).toUpperCase() ?? 'U',
                                  style: const TextStyle(
                                    color: Colors.white,
                                    fontWeight: FontWeight.bold,
                                  ),
                                )
                              : null,
                        ),
                        onSelected: (value) {
                          if (value == 'profile') {
                            Navigator.push(
                              context,
                              MaterialPageRoute(
                                builder: (context) => const ProfileScreen(),
                              ),
                            );
                          } else if (value == 'orders') {
                            // TODO: Navigate to orders
                          } else if (value == 'logout') {
                            authProvider.logout();
                          }
                        },
                        itemBuilder: (context) => [
                          const PopupMenuItem(
                            value: 'profile',
                            child: Row(
                              children: [
                                Icon(Icons.person),
                                SizedBox(width: 8),
                                Text('My Profile'),
                              ],
                            ),
                          ),
                          const PopupMenuItem(
                            value: 'orders',
                            child: Row(
                              children: [
                                Icon(Icons.shopping_bag_outlined),
                                SizedBox(width: 8),
                                Text('My Orders'),
                              ],
                            ),
                          ),
                          const PopupMenuItem(
                            value: 'logout',
                            child: Row(
                              children: [
                                Icon(Icons.logout),
                                SizedBox(width: 8),
                                Text('Logout'),
                              ],
                            ),
                          ),
                        ],
                      );
                    } else {
                      return IconButton(
                        icon: const Icon(Icons.person_outline, color: Colors.white),
                        onPressed: () {
                          Navigator.pushNamed(context, '/welcome');
                        },
                      );
                    }
                  },
                ),
              ],
              bottom: TabBar(
                controller: _tabController,
                isScrollable: true,
                labelColor: Colors.white,
                unselectedLabelColor: Colors.white70,
                indicatorColor: Colors.white,
                indicatorWeight: 3,
                tabs: const [
                  Tab(text: 'Home'),
                  Tab(text: 'Categories'),
                  Tab(text: 'Deals'),
                  Tab(text: 'New'),
                  Tab(text: 'Trending'),
                ],
              ),
            ),
          ];
        },
        body: TabBarView(
          controller: _tabController,
          children: [
            _buildHomeTab(),
            _buildCategoriesTab(),
            _buildDealsTab(),
            _buildNewTab(),
            _buildTrendingTab(),
          ],
        ),
      ),
      bottomNavigationBar: _buildBottomNavigationBar(),
    );
  }

  Widget _buildBottomNavigationBar() {
    return Consumer<AuthProvider>(
      builder: (context, authProvider, child) {
        return BottomNavigationBar(
          type: BottomNavigationBarType.fixed,
          selectedItemColor: AppTheme.primaryColor,
          unselectedItemColor: Colors.grey,
          currentIndex: 0,
          onTap: (index) {
            switch (index) {
              case 1:
                Navigator.push(
                  context,
                  MaterialPageRoute(builder: (context) => const SearchScreen()),
                );
                break;
              case 2:
                Navigator.push(
                  context,
                  MaterialPageRoute(builder: (context) => const CartScreen()),
                );
                break;
              case 3:
                if (authProvider.isAuthenticated) {
                  Navigator.push(
                    context,
                    MaterialPageRoute(builder: (context) => const ProfileScreen()),
                  );
                } else {
                  Navigator.pushNamed(context, '/welcome');
                }
                break;
            }
          },
          items: const [
            BottomNavigationBarItem(
              icon: Icon(Icons.home_outlined),
              activeIcon: Icon(Icons.home),
              label: 'Home',
            ),
            BottomNavigationBarItem(
              icon: Icon(Icons.search_outlined),
              activeIcon: Icon(Icons.search),
              label: 'Search',
            ),
            BottomNavigationBarItem(
              icon: Icon(Icons.shopping_cart_outlined),
              activeIcon: Icon(Icons.shopping_cart),
              label: 'Cart',
            ),
            BottomNavigationBarItem(
              icon: Icon(Icons.person_outline),
              activeIcon: Icon(Icons.person),
              label: 'Profile',
            ),
          ],
        );
      },
    );
  }

  Widget _buildHomeTab() {
    return Consumer<ProductProvider>(
      builder: (context, productProvider, child) {
        if (productProvider.isLoading) {
          return _buildLoadingState();
        }

        if (productProvider.errorMessage != null) {
          return _buildErrorState(productProvider.errorMessage!);
        }

        return RefreshIndicator(
          onRefresh: () => productProvider.refresh(),
          child: SingleChildScrollView(
            physics: const AlwaysScrollableScrollPhysics(),
            child: Column(
              children: [
                // Hero Banner
                const HeroBanner(),
                
                // Flash Sale Banner
                const FlashSaleBanner(),
                
                // Categories Horizontal Scroll
                if (productProvider.categories.isNotEmpty) ...[
                  const SizedBox(height: 16),
                  _buildSectionHeader('Categories', 'See all'),
                  SizedBox(
                    height: 120,
                    child: ListView.builder(
                      scrollDirection: Axis.horizontal,
                      padding: const EdgeInsets.symmetric(horizontal: 16),
                      itemCount: productProvider.categories.length,
                      itemBuilder: (context, index) {
                        final category = productProvider.categories[index];
                        return CategoryCard(category: category);
                      },
                    ),
                  ),
                ],
                
                // Deal of the Day
                const DealOfDay(),
                
                // Featured Products
                if (productProvider.featuredProducts.isNotEmpty) ...[
                  const SizedBox(height: 24),
                  _buildSectionHeader('Featured Products', 'See all'),
                  GridView.builder(
                    shrinkWrap: true,
                    physics: const NeverScrollableScrollPhysics(),
                    padding: const EdgeInsets.all(16),
                    gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                      crossAxisCount: 2,
                      childAspectRatio: 0.75,
                      crossAxisSpacing: 12,
                      mainAxisSpacing: 12,
                    ),
                    itemCount: productProvider.featuredProducts.length.clamp(0, 6),
                    itemBuilder: (context, index) {
                      final product = productProvider.featuredProducts[index];
                      return ProductCard(
                        product: product,
                        onTap: () {
                          Navigator.push(
                            context,
                            MaterialPageRoute(
                              builder: (context) => ProductDetailScreen(product: product),
                            ),
                          );
                        },
                      );
                    },
                  ),
                ],
                
                // Recently Viewed
                const SizedBox(height: 24),
                _buildSectionHeader('Recently Viewed', null),
                SizedBox(
                  height: 200,
                  child: ListView.builder(
                    scrollDirection: Axis.horizontal,
                    padding: const EdgeInsets.symmetric(horizontal: 16),
                    itemCount: 5, // Placeholder
                    itemBuilder: (context, index) {
                      return Container(
                        width: 140,
                        margin: const EdgeInsets.only(right: 12),
                        decoration: BoxDecoration(
                          color: Colors.grey[100],
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: const Center(
                          child: Icon(Icons.image, color: Colors.grey),
                        ),
                      );
                    },
                  ),
                ),
                
                const SizedBox(height: 100), // Bottom padding
              ],
            ),
          ),
        );
      },
    );
  }

  Widget _buildCategoriesTab() {
    return Consumer<ProductProvider>(
      builder: (context, productProvider, child) {
        if (productProvider.categories.isEmpty) {
          return const Center(
            child: Text('No categories available'),
          );
        }

        return GridView.builder(
          padding: const EdgeInsets.all(16),
          gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
            crossAxisCount: 3,
            childAspectRatio: 1,
            crossAxisSpacing: 12,
            mainAxisSpacing: 12,
          ),
          itemCount: productProvider.categories.length,
          itemBuilder: (context, index) {
            final category = productProvider.categories[index];
            return CategoryCard(category: category);
          },
        );
      },
    );
  }

  Widget _buildDealsTab() {
    return const Center(
      child: Text('Deals coming soon!'),
    );
  }

  Widget _buildNewTab() {
    return Consumer<ProductProvider>(
      builder: (context, productProvider, child) {
        return GridView.builder(
          padding: const EdgeInsets.all(16),
          gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
            crossAxisCount: 2,
            childAspectRatio: 0.75,
            crossAxisSpacing: 12,
            mainAxisSpacing: 12,
          ),
          itemCount: productProvider.featuredProducts.length,
          itemBuilder: (context, index) {
            final product = productProvider.featuredProducts[index];
            return ProductCard(
              product: product,
              onTap: () {
                Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (context) => ProductDetailScreen(product: product),
                  ),
                );
              },
            );
          },
        );
      },
    );
  }

  Widget _buildTrendingTab() {
    return const Center(
      child: Text('Trending products coming soon!'),
    );
  }

  Widget _buildSectionHeader(String title, String? actionText) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            title,
            style: const TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
            ),
          ),
          if (actionText != null)
            TextButton(
              onPressed: () {
                // TODO: Navigate to see all
              },
              child: Text(actionText),
            ),
        ],
      ),
    );
  }

  Widget _buildLoadingState() {
    return const Center(
      child: CircularProgressIndicator(),
    );
  }

  Widget _buildErrorState(String error) {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(
            Icons.error_outline,
            size: 64,
            color: Colors.grey[400],
          ),
          const SizedBox(height: 16),
          Text(
            'Something went wrong',
            style: Theme.of(context).textTheme.headlineSmall,
          ),
          const SizedBox(height: 8),
          Text(
            error,
            textAlign: TextAlign.center,
            style: Theme.of(context).textTheme.bodyMedium?.copyWith(
              color: Colors.grey[600],
            ),
          ),
          const SizedBox(height: 24),
          ElevatedButton(
            onPressed: () {
              context.read<ProductProvider>().refresh();
            },
            child: const Text('Try Again'),
          ),
        ],
      ),
    );
  }
}