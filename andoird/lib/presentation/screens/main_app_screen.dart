import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/cart_provider.dart';
import '../../app/theme.dart';
import 'home/modern_home_screen.dart';
import 'search/search_screen.dart';
import 'cart/cart_screen.dart';
import 'profile/profile_screen.dart';

class MainAppScreen extends StatefulWidget {
  const MainAppScreen({super.key});

  @override
  State<MainAppScreen> createState() => _MainAppScreenState();
}

class _MainAppScreenState extends State<MainAppScreen> {
  int _selectedIndex = 0;
  late PageController _pageController;

  final List<Widget> _screens = [
    const ModernHomeScreen(),
    const SearchScreen(),
    const CartScreen(),
    const ProfileScreen(),
  ];

  @override
  void initState() {
    super.initState();
    _pageController = PageController(initialPage: _selectedIndex);
  }

  @override
  void dispose() {
    _pageController.dispose();
    super.dispose();
  }

  void _onItemTapped(int index) {
    setState(() {
      _selectedIndex = index;
    });
    _pageController.animateToPage(
      index,
      duration: const Duration(milliseconds: 300),
      curve: Curves.easeInOut,
    );
  }

  void _onPageChanged(int index) {
    setState(() {
      _selectedIndex = index;
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: context.theme.scaffoldBackgroundColor,
      body: PageView(
        controller: _pageController,
        onPageChanged: _onPageChanged,
        children: _screens,
      ),
      bottomNavigationBar: Consumer<CartProvider>(
        builder: (context, cartProvider, child) {
          return BottomNavigationBar(
            type: BottomNavigationBarType.fixed,
            currentIndex: _selectedIndex,
            onTap: _onItemTapped,
            selectedItemColor: context.primaryColor,
            unselectedItemColor: context.colorScheme.onSurface.withOpacity(0.6),
            backgroundColor: context.colorScheme.surface,
            elevation: 8,
            items: [
              BottomNavigationBarItem(
                icon: const Icon(Icons.home_outlined),
                activeIcon: const Icon(Icons.home),
                label: 'Home',
              ),
              BottomNavigationBarItem(
                icon: const Icon(Icons.search_outlined),
                activeIcon: const Icon(Icons.search),
                label: 'Search',
              ),
              BottomNavigationBarItem(
                icon: Stack(
                  children: [
                    const Icon(Icons.shopping_cart_outlined),
                    if (cartProvider.cartCount > 0)
                      Positioned(
                        right: -6,
                        top: -6,
                        child: Container(
                          padding: EdgeInsets.all(context.spacing1),
                          decoration: BoxDecoration(
                            color: context.errorColor,
                            borderRadius: BorderRadius.circular(context.radiusLg),
                          ),
                          constraints: const BoxConstraints(
                            minWidth: 16,
                            minHeight: 16,
                          ),
                          child: Text(
                            '${cartProvider.cartCount}',
                            style: context.textTheme.labelSmall?.copyWith(
                              color: context.colorScheme.onPrimary,
                              fontWeight: FontWeight.bold,
                            ),
                            textAlign: TextAlign.center,
                          ),
                        ),
                      ),
                  ],
                ),
                activeIcon: Stack(
                  children: [
                    const Icon(Icons.shopping_cart),
                    if (cartProvider.cartCount > 0)
                      Positioned(
                        right: -6,
                        top: -6,
                        child: Container(
                          padding: EdgeInsets.all(context.spacing1),
                          decoration: BoxDecoration(
                            color: context.errorColor,
                            borderRadius: BorderRadius.circular(context.radiusLg),
                          ),
                          constraints: const BoxConstraints(
                            minWidth: 16,
                            minHeight: 16,
                          ),
                          child: Text(
                            '${cartProvider.cartCount}',
                            style: context.textTheme.labelSmall?.copyWith(
                              color: context.colorScheme.onPrimary,
                              fontWeight: FontWeight.bold,
                            ),
                            textAlign: TextAlign.center,
                          ),
                        ),
                      ),
                  ],
                ),
                label: 'Cart',
              ),
              BottomNavigationBarItem(
                icon: const Icon(Icons.person_outline),
                activeIcon: const Icon(Icons.person),
                label: 'Profile',
              ),
            ],
          );
        },
      ),
    );
  }
}