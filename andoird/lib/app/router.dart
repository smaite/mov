import 'package:flutter/material.dart';
import '../presentation/screens/auth/welcome_screen.dart';
import '../presentation/screens/auth/login_screen.dart';
import '../presentation/screens/auth/register_screen.dart';
import '../presentation/screens/main_app_screen.dart';
import '../presentation/screens/home/home_screen.dart';
import '../presentation/screens/home/modern_home_screen.dart';
import '../presentation/screens/search/search_screen.dart';
import '../presentation/screens/cart/cart_screen.dart';
import '../presentation/screens/profile/profile_screen.dart';
import '../presentation/screens/home/product_detail_screen.dart';
import '../presentation/screens/cart/checkout_screen.dart';
import '../presentation/screens/profile/orders_screen.dart';
import '../../data/models/product.dart';

class AppRouter {
  static const String welcome = '/welcome';
  static const String login = '/login';
  static const String register = '/register';
  static const String home = '/home';
  static const String search = '/search';
  static const String cart = '/cart';
  static const String profile = '/profile';
  static const String productDetail = '/product-detail';
  static const String checkout = '/checkout';
  static const String orders = '/orders';

  static Route<dynamic> generateRoute(RouteSettings settings) {
    switch (settings.name) {
      case welcome:
        return MaterialPageRoute(
          builder: (_) => const WelcomeScreen(),
          settings: settings,
        );

      case login:
        return MaterialPageRoute(
          builder: (_) => const LoginScreen(),
          settings: settings,
        );

      case register:
        return MaterialPageRoute(
          builder: (_) => const RegisterScreen(),
          settings: settings,
        );

      case home:
        return MaterialPageRoute(
          builder: (_) => const MainAppScreen(),
          settings: settings,
        );

      case search:
        return MaterialPageRoute(
          builder: (_) => const SearchScreen(),
          settings: settings,
        );

      case cart:
        return MaterialPageRoute(
          builder: (_) => const CartScreen(),
          settings: settings,
        );

      case profile:
        return MaterialPageRoute(
          builder: (_) => const ProfileScreen(),
          settings: settings,
        );

      case productDetail:
        final product = settings.arguments as Product;
        return MaterialPageRoute(
          builder: (_) => ProductDetailScreen(product: product),
          settings: settings,
        );

      case checkout:
        return MaterialPageRoute(
          builder: (_) => const CheckoutScreen(),
          settings: settings,
        );

      case orders:
        return MaterialPageRoute(
          builder: (_) => const OrdersScreen(),
          settings: settings,
        );

      default:
        return MaterialPageRoute(
          builder: (_) => const WelcomeScreen(),
          settings: settings,
        );
    }
  }
}

// MainScreen class removed - using MainAppScreen instead