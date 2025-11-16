class AppConstants {
  static const String appName = 'Sasto Hub';
  static const String apiBaseUrl = 'https://sastohub.com';
  static const String localApiBaseUrl = 'http://localhost/mov';
  static const String imageBaseUrl = '$apiBaseUrl/uploads/';
  
  // Theme Colors
  static const int primaryColor = 0xFFFF6B35;
  static const int secondaryColor = 0xFF004643;
  static const int accentColor = 0xFFF9BC60;
  static const int textColor = 0xFF333333;
  
  // API Endpoints
  static const String loginEndpoint = '/pages/auth/login.php';
  static const String registerEndpoint = '/pages/auth/register.php';
  static const String productEndpoint = '/';
  static const String addToCartEndpoint = '/ajax/cart.php?action=add';
  static const String cartCountEndpoint = '/ajax/cart.php?action=count';
  static const String wishlistToggleEndpoint = '/ajax/wishlist.php?action=toggle';
  static const String ordersEndpoint = '/pages/orders/index.php';
  
  // Storage Keys
  static const String tokenKey = 'user_token';
  static const String userKey = 'user_data';
  static const String cartCountKey = 'cart_count';
  static const String wishlistCountKey = 'wishlist_count';
  static const String themeKey = 'theme_mode';
  static const String languageKey = 'language';
  static const String searchHistoryKey = 'search_history';
  static const String viewPreferenceKey = 'view_preference';
  
  // Validation
  static const int minPasswordLength = 6;
  static const int maxPasswordLength = 30;
  static const int maxNameLength = 50;
  static const int maxAddressLength = 200;
  static const int maxSearchLength = 100;
  
  // Other
  static const String currencySymbol = 'रू';
  static const int defaultDecimalPlaces = 2;
  static const int connectTimeout = 30000; // 30 seconds
  static const int networkTimeout = 30000; // 30 seconds
  static const String appVersion = '1.0.0';
  static const bool isDebugMode = true;
  
  // Error Messages
  static const String networkErrorMessage = 'Network error occurred. Please check your connection.';
  static const String generalErrorMessage = 'An unexpected error occurred. Please try again.';
}