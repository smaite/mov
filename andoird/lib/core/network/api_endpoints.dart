import '../../app/constants.dart';

class ApiEndpoints {
  // Base URLs
  static String get baseUrl => AppConstants.baseUrl;
  static String get imageBaseUrl => AppConstants.imageBaseUrl;

  // Authentication Endpoints
  static const String login = AppConstants.loginEndpoint;
  static const String register = AppConstants.registerEndpoint;
  static const String logout = '/pages/auth/logout.php';
  static const String forgotPassword = '/pages/auth/forgot_password.php';
  static const String resetPassword = '/pages/auth/reset_password.php';
  static const String changePassword = '/pages/auth/change_password.php';
  static const String updateProfile = '/pages/auth/update_profile.php';

  // Product Endpoints
  static const String home = AppConstants.productsEndpoint;
  static const String products = '/?page=products';
  static const String productDetail = '/?page=product';
  static const String search = AppConstants.searchEndpoint;
  static const String categories = '/?page=categories';
  static const String brands = '/?page=brands';
  static const String featuredProducts = '/?page=featured';
  static const String newArrivals = '/?page=new_arrivals';
  static const String bestSellers = '/?page=best_sellers';
  static const String deals = '/?page=deals';
  static const String relatedProducts = '/?page=related_products';
  static const String productReviews = '/?page=product_reviews';

  // Cart Endpoints
  static const String cart = AppConstants.cartEndpoint;
  static const String cartAdd = '$cart?action=add';
  static const String cartUpdate = '$cart?action=update';
  static const String cartRemove = '$cart?action=remove';
  static const String cartClear = '$cart?action=clear';
  static const String cartCount = '$cart?action=count';
  static const String cartView = '$cart?action=view';
  static const String cartApplyCoupon = '$cart?action=apply_coupon';
  static const String cartRemoveCoupon = '$cart?action=remove_coupon';

  // Wishlist Endpoints
  static const String wishlist = AppConstants.wishlistEndpoint;
  static const String wishlistToggle = '$wishlist?action=toggle';
  static const String wishlistAdd = '$wishlist?action=add';
  static const String wishlistRemove = '$wishlist?action=remove';
  static const String wishlistView = '$wishlist?action=view';
  static const String wishlistCount = '$wishlist?action=count';
  static const String wishlistClear = '$wishlist?action=clear';

  // Order Endpoints
  static const String orders = AppConstants.ordersEndpoint;
  static const String placeOrder = '/pages/orders/place_order.php';
  static const String orderDetail = '/pages/orders/order_detail.php';
  static const String orderTracking = '/pages/orders/tracking.php';
  static const String cancelOrder = '/pages/orders/cancel.php';
  static const String returnOrder = '/pages/orders/return.php';
  static const String orderHistory = '/pages/orders/history.php';

  // Payment Endpoints
  static const String paymentInitiate = '/pages/payment/initiate.php';
  static const String paymentVerify = '/pages/payment/verify.php';
  static const String paymentCallback = '/pages/payment/callback.php';
  static const String paymentMethods = '/pages/payment/methods.php';

  // Address Endpoints
  static const String addresses = '/pages/address/index.php';
  static const String addAddress = '/pages/address/add.php';
  static const String updateAddress = '/pages/address/update.php';
  static const String deleteAddress = '/pages/address/delete.php';
  static const String setDefaultAddress = '/pages/address/set_default.php';

  // Review Endpoints
  static const String addReview = '/pages/reviews/add.php';
  static const String updateReview = '/pages/reviews/update.php';
  static const String deleteReview = '/pages/reviews/delete.php';
  static const String userReviews = '/pages/reviews/user_reviews.php';

  // Notification Endpoints
  static const String notifications = '/pages/notifications/index.php';
  static const String markNotificationRead = '/pages/notifications/mark_read.php';
  static const String clearNotifications = '/pages/notifications/clear.php';

  // Settings Endpoints
  static const String settings = '/pages/settings/index.php';
  static const String appSettings = '/pages/settings/app_settings.php';
  static const String userSettings = '/pages/settings/user_settings.php';

  // Utility Methods for Building URLs
  static String buildProductUrl({String? category, String? brand, int? page, int? limit}) {
    final params = <String, String>{};
    if (category != null) params['category'] = category;
    if (brand != null) params['brand'] = brand;
    if (page != null) params['page'] = page.toString();
    if (limit != null) params['limit'] = limit.toString();

    final queryString = params.entries
        .map((e) => '${e.key}=${e.value}')
        .join('&');

    return queryString.isNotEmpty ? '$products?$queryString' : products;
  }

  static String buildSearchUrl({
    required String query,
    String? category,
    String? brand,
    double? minPrice,
    double? maxPrice,
    String? sortBy,
    String? sortOrder,
    int? page,
    int? limit,
  }) {
    final params = <String, String>{
      'q': query,
    };
    if (category != null) params['category'] = category;
    if (brand != null) params['brand'] = brand;
    if (minPrice != null) params['min_price'] = minPrice.toString();
    if (maxPrice != null) params['max_price'] = maxPrice.toString();
    if (sortBy != null) params['sort_by'] = sortBy;
    if (sortOrder != null) params['sort_order'] = sortOrder;
    if (page != null) params['page'] = page.toString();
    if (limit != null) params['limit'] = limit.toString();

    final queryString = params.entries
        .map((e) => '${e.key}=${Uri.encodeComponent(e.value)}')
        .join('&');

    return '$search?$queryString';
  }

  static String buildProductDetailUrl(String productId) {
    return '$productDetail&id=$productId';
  }

  static String buildOrderDetailUrl(String orderId) {
    return '$orderDetail&id=$orderId';
  }

  static String buildCategoryProductsUrl(String categoryId, {int? page, int? limit}) {
    return buildProductUrl(category: categoryId, page: page, limit: limit);
  }

  static String buildBrandProductsUrl(String brandId, {int? page, int? limit}) {
    return buildProductUrl(brand: brandId, page: page, limit: limit);
  }

  static String buildRelatedProductsUrl(String productId) {
    return '$relatedProducts&product_id=$productId';
  }

  static String buildProductReviewsUrl(String productId, {int? page, int? limit}) {
    final params = <String, String>{
      'product_id': productId,
    };
    if (page != null) params['page'] = page.toString();
    if (limit != null) params['limit'] = limit.toString();

    final queryString = params.entries
        .map((e) => '${e.key}=${e.value}')
        .join('&');

    return '$productReviews?$queryString';
  }

  static String buildImageUrl(String imagePath) {
    if (imagePath.startsWith('http')) {
      return imagePath;
    }
    return '$imageBaseUrl$imagePath';
  }

  static String buildNotificationsUrl({bool? unreadOnly}) {
    if (unreadOnly == true) {
      return '$notifications?unread=true';
    }
    return notifications;
  }

  static String buildOrderTrackingUrl(String orderId) {
    return '$orderTracking?id=$orderId';
  }
}