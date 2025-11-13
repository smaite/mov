import '../../app/constants.dart';

class Validators {
  // Email Validation
  static String? validateEmail(String? value) {
    if (value == null || value.isEmpty) {
      return 'Email is required';
    }

    final emailRegex = RegExp(
      r'^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$',
    );

    if (!emailRegex.hasMatch(value)) {
      return 'Please enter a valid email address';
    }

    return null;
  }

  // Password Validation
  static String? validatePassword(String? value) {
    if (value == null || value.isEmpty) {
      return 'Password is required';
    }

    if (value.length < AppConstants.minPasswordLength) {
      return 'Password must be at least ${AppConstants.minPasswordLength} characters';
    }

    if (value.length > AppConstants.maxPasswordLength) {
      return 'Password must not exceed ${AppConstants.maxPasswordLength} characters';
    }

    // Check for at least one uppercase letter
    if (!value.contains(RegExp(r'[A-Z]'))) {
      return 'Password must contain at least one uppercase letter';
    }

    // Check for at least one lowercase letter
    if (!value.contains(RegExp(r'[a-z]'))) {
      return 'Password must contain at least one lowercase letter';
    }

    // Check for at least one number
    if (!value.contains(RegExp(r'[0-9]'))) {
      return 'Password must contain at least one number';
    }

    return null;
  }

  // Confirm Password Validation
  static String? validateConfirmPassword(String? value, String? password) {
    if (value == null || value.isEmpty) {
      return 'Please confirm your password';
    }

    if (value != password) {
      return 'Passwords do not match';
    }

    return null;
  }

  // Name Validation
  static String? validateName(String? value, {String fieldName = 'Name'}) {
    if (value == null || value.isEmpty) {
      return '$fieldName is required';
    }

    if (value.length > AppConstants.maxNameLength) {
      return '$fieldName must not exceed ${AppConstants.maxNameLength} characters';
    }

    // Check for only letters and spaces
    if (!value.contains(RegExp(r'^[a-zA-Z\s]+$'))) {
      return '$fieldName can only contain letters and spaces';
    }

    return null;
  }

  // Phone Validation
  static String? validatePhone(String? value) {
    if (value == null || value.isEmpty) {
      return 'Phone number is required';
    }

    // Remove any non-digit characters
    final digitsOnly = value.replaceAll(RegExp(r'[^0-9]'), '');

    if (digitsOnly.length < 10) {
      return 'Phone number must be at least 10 digits';
    }

    if (digitsOnly.length > 15) {
      return 'Phone number must not exceed 15 digits';
    }

    return null;
  }

  // Address Validation
  static String? validateAddress(String? value) {
    if (value == null || value.isEmpty) {
      return 'Address is required';
    }

    if (value.length > AppConstants.maxAddressLength) {
      return 'Address must not exceed ${AppConstants.maxAddressLength} characters';
    }

    return null;
  }

  // City Validation
  static String? validateCity(String? value) {
    if (value == null || value.isEmpty) {
      return 'City is required';
    }

    if (value.length > 50) {
      return 'City name must not exceed 50 characters';
    }

    if (!value.contains(RegExp(r'^[a-zA-Z\s]+$'))) {
      return 'City name can only contain letters and spaces';
    }

    return null;
  }

  // Postal Code Validation
  static String? validatePostalCode(String? value) {
    if (value == null || value.isEmpty) {
      return 'Postal code is required';
    }

    if (!value.contains(RegExp(r'^[0-9]{5,6}$'))) {
      return 'Please enter a valid postal code';
    }

    return null;
  }

  // Quantity Validation
  static String? validateQuantity(String? value, {int maxQuantity = 99}) {
    if (value == null || value.isEmpty) {
      return 'Quantity is required';
    }

    final quantity = int.tryParse(value);
    if (quantity == null) {
      return 'Please enter a valid quantity';
    }

    if (quantity < 1) {
      return 'Quantity must be at least 1';
    }

    if (quantity > maxQuantity) {
      return 'Quantity must not exceed $maxQuantity';
    }

    return null;
  }

  // Price Validation
  static String? validatePrice(String? value) {
    if (value == null || value.isEmpty) {
      return 'Price is required';
    }

    final price = double.tryParse(value);
    if (price == null) {
      return 'Please enter a valid price';
    }

    if (price < 0) {
      return 'Price cannot be negative';
    }

    if (price > 999999.99) {
      return 'Price is too high';
    }

    return null;
  }

  // Card Number Validation
  static String? validateCardNumber(String? value) {
    if (value == null || value.isEmpty) {
      return 'Card number is required';
    }

    // Remove spaces and dashes
    final cleanNumber = value.replaceAll(RegExp(r'[\s-]'), '');

    if (!cleanNumber.contains(RegExp(r'^[0-9]{13,19}$'))) {
      return 'Please enter a valid card number';
    }

    // Luhn algorithm for basic validation
    int sum = 0;
    bool isEven = false;

    for (int i = cleanNumber.length - 1; i >= 0; i--) {
      int digit = int.parse(cleanNumber[i]);

      if (isEven) {
        digit *= 2;
        if (digit > 9) {
          digit -= 9;
        }
      }

      sum += digit;
      isEven = !isEven;
    }

    if (sum % 10 != 0) {
      return 'Please enter a valid card number';
    }

    return null;
  }

  // CVV Validation
  static String? validateCVV(String? value) {
    if (value == null || value.isEmpty) {
      return 'CVV is required';
    }

    if (!value.contains(RegExp(r'^[0-9]{3,4}$'))) {
      return 'Please enter a valid CVV';
    }

    return null;
  }

  // Expiry Date Validation
  static String? validateExpiryDate(String? value) {
    if (value == null || value.isEmpty) {
      return 'Expiry date is required';
    }

    // Format should be MM/YY
    if (!value.contains(RegExp(r'^(0[1-9]|1[0-2])\/[0-9]{2}$'))) {
      return 'Please enter a valid expiry date (MM/YY)';
    }

    final parts = value.split('/');
    final month = int.parse(parts[0]);
    final year = 2000 + int.parse(parts[1]); // Convert YY to 20YY

    final now = DateTime.now();
    final expiryDate = DateTime(year, month);

    // Check if expired
    if (expiryDate.isBefore(DateTime(now.year, now.month))) {
      return 'Card has expired';
    }

    return null;
  }

  // Required Field Validation
  static String? validateRequired(String? value, {String fieldName = 'Field'}) {
    if (value == null || value.trim().isEmpty) {
      return '$fieldName is required';
    }
    return null;
  }

  // Length Validation
  static String? validateLength(
    String? value, {
    int minLength = 0,
    int maxLength = 1000,
    String fieldName = 'Field',
  }) {
    if (value == null) return null;

    if (value.length < minLength) {
      return '$fieldName must be at least $minLength characters';
    }

    if (value.length > maxLength) {
      return '$fieldName must not exceed $maxLength characters';
    }

    return null;
  }

  // URL Validation
  static String? validateUrl(String? value) {
    if (value == null || value.isEmpty) {
      return 'URL is required';
    }

    final urlRegex = RegExp(
      r'^https?:\/\/(www\.)?[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b([-a-zA-Z0-9()@:%_\+.~#?&//=]*)$',
    );

    if (!urlRegex.hasMatch(value)) {
      return 'Please enter a valid URL';
    }

    return null;
  }

  // Search Query Validation
  static String? validateSearchQuery(String? value) {
    if (value == null || value.trim().isEmpty) {
      return 'Please enter a search term';
    }

    if (value.length > AppConstants.maxSearchLength) {
      return 'Search term must not exceed ${AppConstants.maxSearchLength} characters';
    }

    return null;
  }

  // Rating Validation
  static String? validateRating(double? rating) {
    if (rating == null) {
      return 'Rating is required';
    }

    if (rating < 1.0) {
      return 'Rating must be at least 1 star';
    }

    if (rating > 5.0) {
      return 'Rating must not exceed 5 stars';
    }

    return null;
  }

  // Category Validation
  static String? validateCategory(String? value) {
    if (value == null || value.isEmpty) {
      return 'Please select a category';
    }
    return null;
  }

  // Brand Validation
  static String? validateBrand(String? value) {
    if (value == null || value.isEmpty) {
      return 'Please select a brand';
    }
    return null;
  }

  // Security PIN Validation
  static String? validatePin(String? value) {
    if (value == null || value.isEmpty) {
      return 'PIN is required';
    }

    if (!value.contains(RegExp(r'^[0-9]{4}$'))) {
      return 'PIN must be exactly 4 digits';
    }

    return null;
  }

  // OTP Validation
  static String? validateOtp(String? value) {
    if (value == null || value.isEmpty) {
      return 'OTP is required';
    }

    if (!value.contains(RegExp(r'^[0-9]{6}$'))) {
      return 'OTP must be exactly 6 digits';
    }

    return null;
  }
}