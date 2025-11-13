import 'dart:convert';
import 'dart:math';
import 'package:intl/intl.dart';
import '../../app/constants.dart';

class Helpers {
  // Price Formatting
  static String formatPrice(double price, {String? currency}) {
    final formatter = NumberFormat.currency(
      symbol: currency ?? AppConstants.currencySymbol,
      decimalDigits: AppConstants.defaultDecimalPlaces,
    );
    return formatter.format(price);
  }

  static double parsePrice(String priceString) {
    try {
      // Remove currency symbol and any non-numeric characters except decimal point
      final cleanString = priceString
          .replaceAll(RegExp(r'[^0-9.]'), '')
          .trim();
      return double.parse(cleanString);
    } catch (e) {
      return 0.0;
    }
  }

  // Date Formatting
  static String formatDate(DateTime date, {String format = 'yyyy-MM-dd'}) {
    return DateFormat(format).format(date);
  }

  static String formatDateTime(DateTime dateTime, {String format = 'yyyy-MM-dd HH:mm'}) {
    return DateFormat(format).format(dateTime);
  }

  static DateTime? parseDate(String dateString, {String format = 'yyyy-MM-dd'}) {
    try {
      return DateFormat(format).parse(dateString);
    } catch (e) {
      return null;
    }
  }

  static DateTime? parseDateTime(String dateTimeString, {String format = 'yyyy-MM-dd HH:mm:ss'}) {
    try {
      return DateFormat(format).parse(dateTimeString);
    } catch (e) {
      return null;
    }
  }

  // Relative Time Formatting
  static String formatRelativeTime(DateTime dateTime) {
    final now = DateTime.now();
    final difference = now.difference(dateTime);

    if (difference.inDays > 365) {
      final years = (difference.inDays / 365).floor();
      return '$years year${years > 1 ? 's' : ''} ago';
    } else if (difference.inDays > 30) {
      final months = (difference.inDays / 30).floor();
      return '$months month${months > 1 ? 's' : ''} ago';
    } else if (difference.inDays > 0) {
      return '${difference.inDays} day${difference.inDays > 1 ? 's' : ''} ago';
    } else if (difference.inHours > 0) {
      return '${difference.inHours} hour${difference.inHours > 1 ? 's' : ''} ago';
    } else if (difference.inMinutes > 0) {
      return '${difference.inMinutes} minute${difference.inMinutes > 1 ? 's' : ''} ago';
    } else {
      return 'Just now';
    }
  }

  // String Manipulation
  static String truncateString(String text, int maxLength, {String suffix = '...'}) {
    if (text.length <= maxLength) return text;
    return '${text.substring(0, maxLength - suffix.length)}$suffix';
  }

  static String capitalizeFirstLetter(String text) {
    if (text.isEmpty) return text;
    return '${text[0].toUpperCase()}${text.substring(1).toLowerCase()}';
  }

  static String capitalizeWords(String text) {
    return text.split(' ').map((word) => capitalizeFirstLetter(word)).join(' ');
  }

  static String removeExtraWhitespace(String text) {
    return text.replaceAll(RegExp(r'\s+'), ' ').trim();
  }

  // Validation Helpers
  static bool isValidEmail(String email) {
    final emailRegex = RegExp(
      r'^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$',
    );
    return emailRegex.hasMatch(email);
  }

  static bool isValidPhone(String phone) {
    final digitsOnly = phone.replaceAll(RegExp(r'[^0-9]'), '');
    return digitsOnly.length >= 10 && digitsOnly.length <= 15;
  }

  static bool isValidUrl(String url) {
    try {
      final uri = Uri.parse(url);
      return uri.hasScheme && (uri.scheme == 'http' || uri.scheme == 'https');
    } catch (e) {
      return false;
    }
  }

  // Color Generation
  static String generateRandomColor() {
    final random = Random();
    final color = Color.fromRGBO(
      random.nextInt(256),
      random.nextInt(256),
      random.nextInt(256),
      1,
    );
    return color.value.toRadixString(16).substring(2);
  }

  // ID Generation
  static String generateUniqueId() {
    final timestamp = DateTime.now().millisecondsSinceEpoch;
    final random = Random().nextInt(9999);
    return '${timestamp}_$random';
  }

  // File Size Formatting
  static String formatFileSize(int bytes) {
    if (bytes < 1024) {
      return '$bytes B';
    } else if (bytes < 1024 * 1024) {
      return '${(bytes / 1024).toStringAsFixed(1)} KB';
    } else if (bytes < 1024 * 1024 * 1024) {
      return '${(bytes / (1024 * 1024)).toStringAsFixed(1)} MB';
    } else {
      return '${(bytes / (1024 * 1024 * 1024)).toStringAsFixed(1)} GB';
    }
  }

  // Distance Calculation
  static double calculateDistance(
    double lat1,
    double lon1,
    double lat2,
    double lon2,
  ) {
    const double earthRadius = 6371; // Earth's radius in kilometers

    final double dLat = (lat2 - lat1) * (pi / 180);
    final double dLon = (lon2 - lon1) * (pi / 180);

    final double a = sin(dLat / 2) * sin(dLat / 2) +
        cos(lat1 * (pi / 180)) *
            cos(lat2 * (pi / 180)) *
            sin(dLon / 2) *
            sin(dLon / 2);

    final double c = 2 * atan2(sqrt(a), sqrt(1 - a));
    return earthRadius * c;
  }

  // Rating Helpers
  static List<bool> getRatingStars(double rating) {
    final List<bool> stars = List.filled(5, false);
    final fullStars = rating.floor();
    final hasHalfStar = (rating - fullStars) >= 0.5;

    for (int i = 0; i < fullStars && i < 5; i++) {
      stars[i] = true;
    }

    if (hasHalfStar && fullStars < 5) {
      stars[fullStars] = true;
    }

    return stars;
  }

  static String formatRating(double rating, {int totalReviews = 0}) {
    return '$rating${totalReviews > 0 ? ' ($totalReviews)' : ''}';
  }

  // Stock Status
  static String getStockStatus(int stockQuantity) {
    if (stockQuantity == 0) {
      return 'Out of Stock';
    } else if (stockQuantity < 10) {
      return 'Only $stockQuantity left';
    } else if (stockQuantity < 50) {
      return 'In Stock';
    } else {
      return 'Available';
    }
  }

  static bool isInStock(int stockQuantity) {
    return stockQuantity > 0;
  }

  // Discount Calculation
  static double calculateDiscountPercentage(double originalPrice, double salePrice) {
    if (originalPrice <= 0 || salePrice <= 0) return 0;
    final discount = ((originalPrice - salePrice) / originalPrice) * 100;
    return discount > 0 ? discount.roundToDouble() : 0;
  }

  static String formatDiscount(double originalPrice, double salePrice) {
    final percentage = calculateDiscountPercentage(originalPrice, salePrice);
    return percentage > 0 ? '${percentage.toInt()}% OFF' : '';
  }

  // Order Status Helpers
  static String formatOrderStatus(String status) {
    switch (status.toLowerCase()) {
      case 'pending':
        return 'Pending';
      case 'confirmed':
        return 'Confirmed';
      case 'processing':
        return 'Processing';
      case 'shipped':
        return 'Shipped';
      case 'delivered':
        return 'Delivered';
      case 'cancelled':
        return 'Cancelled';
      case 'returned':
        return 'Returned';
      default:
        return capitalizeWords(status);
    }
  }

  static String getOrderStatusDescription(String status) {
    switch (status.toLowerCase()) {
      case 'pending':
        return 'Your order is waiting for confirmation';
      case 'confirmed':
        return 'Your order has been confirmed';
      case 'processing':
        return 'Your order is being prepared';
      case 'shipped':
        return 'Your order has been shipped';
      case 'delivered':
        return 'Your order has been delivered';
      case 'cancelled':
        return 'Your order has been cancelled';
      case 'returned':
        return 'Your order has been returned';
      default:
        return 'Order status: ${formatOrderStatus(status)}';
    }
  }

  // Payment Method Helpers
  static String formatPaymentMethod(String paymentMethod) {
    switch (paymentMethod.toLowerCase()) {
      case 'cod':
        return 'Cash on Delivery';
      case 'credit_card':
        return 'Credit Card';
      case 'debit_card':
        return 'Debit Card';
      case 'netbanking':
        return 'Net Banking';
      case 'upi':
        return 'UPI';
      case 'wallet':
        return 'Wallet';
      default:
        return capitalizeWords(paymentMethod);
    }
  }

  // URL Helpers
  static String buildImageUrl(String imagePath, {String? baseUrl}) {
    if (imagePath.startsWith('http')) {
      return imagePath;
    }
    final base = baseUrl ?? AppConstants.imageBaseUrl;
    return '$base$imagePath';
  }

  static Map<String, String> parseQueryString(String queryString) {
    final params = <String, String>{};
    final pairs = queryString.split('&');

    for (final pair in pairs) {
      final keyValue = pair.split('=');
      if (keyValue.length == 2) {
        params[keyValue[0]] = Uri.decodeComponent(keyValue[1]);
      }
    }

    return params;
  }

  // JSON Helpers
  static Map<String, dynamic>? parseJson(String jsonString) {
    try {
      return jsonDecode(jsonString) as Map<String, dynamic>;
    } catch (e) {
      return null;
    }
  }

  static String encodeJson(Map<String, dynamic> data) {
    try {
      return jsonEncode(data);
    } catch (e) {
      return '{}';
    }
  }

  // Logging Helpers
  static void logInfo(String message) {
    if (AppConstants.isDebugMode) {
      print('[INFO] $message');
    }
  }

  static void logError(String message, {dynamic error}) {
    if (AppConstants.isDebugMode) {
      print('[ERROR] $message');
      if (error != null) {
        print('[ERROR] $error');
      }
    }
  }

  static void logWarning(String message) {
    if (AppConstants.isDebugMode) {
      print('[WARNING] $message');
    }
  }

  // Debouncer Utility
  static Debouncer createDebouncer(Duration duration) {
    return Debouncer(duration);
  }

  // Image Validation
  static bool isImageUrl(String url) {
    final imageExtensions = ['.jpg', '.jpeg', '.png', '.gif', '.bmp', '.webp'];
    final uri = Uri.parse(url.toLowerCase());
    return imageExtensions.any((ext) => uri.path.endsWith(ext));
  }

  // Text Statistics
  static int countWords(String text) {
    if (text.trim().isEmpty) return 0;
    return text.trim().split(RegExp(r'\s+')).length;
  }

  static int countCharacters(String text, {bool includeSpaces = true}) {
    if (includeSpaces) {
      return text.length;
    } else {
      return text.replaceAll(RegExp(r'\s'), '').length;
    }
  }

  // Validation Regex Patterns
  static final RegExp emailRegex = RegExp(
    r'^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$',
  );

  static final RegExp phoneRegex = RegExp(
    r'^[+]?[0-9]{10,15}$',
  );

  static final RegExp passwordRegex = RegExp(
    r'^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d@$!%*?&]{8,}$',
  );

  static final RegExp numericRegex = RegExp(r'^[0-9]+$');
  static final RegExp alphaRegex = RegExp(r'^[a-zA-Z]+$');
  static final RegExp alphanumericRegex = RegExp(r'^[a-zA-Z0-9]+$');
}

class Debouncer {
  final Duration duration;
  Timer? _timer;

  Debouncer(this.duration);

  void run(VoidCallback action) {
    _timer?.cancel();
    _timer = Timer(duration, action);
  }

  void cancel() {
    _timer?.cancel();
    _timer = null;
  }
}

// Math Extensions
extension DoubleExtensions on double {
  String toPrice() => Helpers.formatPrice(this);

  bool isBetween(double min, double max) {
    return this >= min && this <= max;
  }
}

extension StringExtensions on String {
  String capitalize() => Helpers.capitalizeFirstLetter(this);
  String capitalizeWords() => Helpers.capitalizeWords(this);
  String truncate(int maxLength) => Helpers.truncateString(this, maxLength);

  bool isValidEmail() => Helpers.isValidEmail(this);
  bool isValidPhone() => Helpers.isValidPhone(this);
  bool isValidUrl() => Helpers.isValidUrl(this);

  String? getValidUrl() {
    if (isEmpty) return null;
    if (startsWith('http')) return this;
    return 'https://$this';
  }
}

extension DateTimeExtensions on DateTime {
  String toFormattedDate({String format = 'yyyy-MM-dd'}) {
    return Helpers.formatDate(this, format: format);
  }

  String toFormattedDateTime({String format = 'yyyy-MM-dd HH:mm'}) {
    return Helpers.formatDateTime(this, format: format);
  }

  String toRelativeTime() {
    return Helpers.formatRelativeTime(this);
  }

  bool isSameDay(DateTime other) {
    return year == other.year && month == other.month && day == other.day;
  }

  bool isToday() {
    final now = DateTime.now();
    return isSameDay(now);
  }

  bool isYesterday() {
    final yesterday = DateTime.now().subtract(const Duration(days: 1));
    return isSameDay(yesterday);
  }

  DateTime startOfDay() {
    return DateTime(year, month, day);
  }

  DateTime endOfDay() {
    return DateTime(year, month, day, 23, 59, 59, 999);
  }
}