class AppConstants {
  // Environment
  static const bool isDebugMode = true;

  // API Configuration
  static const String baseUrlDev = 'http://localhost/mov';
  static const String baseUrlProd = 'https://sastoub';
  static const String baseUrl = isDebugMode ? baseUrlDev : baseUrlProd;

  // API Endpoints
  static const String loginEndpoint = '/pages/auth/login.php';
  static const String registerEndpoint = '/pages/auth/register.php';
  static const String productsEndpoint = '/';
  static const String searchEndpoint = '/?page=search';
  static const String cartEndpoint = '/ajax/cart.php';
  static const String wishlistEndpoint = '/ajax/wishlist.php';
  static const String ordersEndpoint = '/pages/orders/index.php';

  // Image URLs
  static const String imageBaseUrl = '$baseUrl/uploads/';

  // App Settings
  static const String appName = 'Sasto Hub';
  static const String appVersion = '1.0.0';
  static const String appTagline = 'Your trusted marketplace in Nepal';

  // Storage Keys
  static const String tokenKey = 'auth_token';
  static const String userKey = 'user_data';
  static const String cartCountKey = 'cart_count';
  static const String wishlistCountKey = 'wishlist_count';
  static const String themeKey = 'theme_mode';
  static const String languageKey = 'language';
  static const String searchHistoryKey = 'search_history';
  static const String viewPreferenceKey = 'product_view_preference';

  // Pagination
  static const int defaultPageSize = 20;
  static const int maxPageSize = 100;

  // Image Dimensions
  static const double productImageWidth = 200.0;
  static const double productImageHeight = 200.0;
  static const double categoryImageWidth = 80.0;
  static const double categoryImageHeight = 80.0;

  // Animation Durations
  static const Duration shortAnimation = Duration(milliseconds: 200);
  static const Duration mediumAnimation = Duration(milliseconds: 300);
  static const Duration longAnimation = Duration(milliseconds: 500);

  // Network Settings
  static const Duration networkTimeout = Duration(seconds: 30);
  static const Duration connectTimeout = Duration(seconds: 10);
  static const int maxRetries = 3;

  // Social Login
  static const String googleClientId = 'your-google-client-id';
  static const String facebookAppId = 'your-facebook-app-id';

  // Error Messages
  static const String networkErrorMessage = 'Please check your internet connection';
  static const String serverErrorMessage = 'Server is temporarily unavailable';
  static const String generalErrorMessage = 'Something went wrong. Please try again';
  static const String authErrorMessage = 'Authentication failed. Please try again';
  static const String sessionExpiredMessage = 'Your session has expired. Please login again';

  // Validation
  static const int minPasswordLength = 6;
  static const int maxPasswordLength = 50;
  static const int maxNameLength = 50;
  static const int maxAddressLength = 200;
  static const int maxSearchLength = 100;

  // UI Constants
  static const double borderRadius = 8.0;
  static const double largeBorderRadius = 12.0;
  static const double cardElevation = 4.0;
  static const double bottomNavHeight = 60.0;
  static const double appBarHeight = 56.0;

  // Price and Currency
  static const String currencySymbol = 'Rs.';
  static const String currencyCode = 'NPR';
  static const int defaultDecimalPlaces = 2;

  // Delivery
  static const double defaultDeliveryFee = 50.0;
  static const double freeDeliveryThreshold = 1000.0;
  static const Duration maxDeliveryTime = Duration(days: 7);
  static const Duration minDeliveryTime = Duration(days: 1);
}