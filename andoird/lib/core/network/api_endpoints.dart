import '../../app/constants.dart';

class ApiEndpoints {
  static String get baseUrl => AppConstants.apiBaseUrl;
  
  // Authentication
  static String get login => AppConstants.loginEndpoint;
  static String get register => AppConstants.registerEndpoint;
  
  // Products
  static const String home = AppConstants.productEndpoint;
  static const String categoryProducts = '/?page=products&category=';
  static const String search = '/?page=search&q=';
  static const String productDetails = '/?page=product&id=';
  
  // Cart
  static const String cart = AppConstants.addToCartEndpoint;
  static const String cartCount = AppConstants.cartCountEndpoint;
  static const String cartUpdate = '/ajax/cart.php?action=update';
  static const String cartRemove = '/ajax/cart.php?action=remove';
  
  // Wishlist
  static const String wishlist = AppConstants.wishlistToggleEndpoint;
  
  // Orders
  static const String orders = AppConstants.ordersEndpoint;
  
  // User
  static const String userProfile = '/pages/user/profile.php';
  
  // Static helper method for building query URLs
  static String buildQueryUrl(String base, Map<String, dynamic> params) {
    final uri = Uri.parse(base);
    return uri.replace(queryParameters: params).toString();
  }
}