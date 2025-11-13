import 'dart:convert';
import 'package:shared_preferences/shared_preferences.dart';
import '../../app/constants.dart';

class Preferences {
  static Preferences? _instance;
  static SharedPreferences? _prefs;

  static Future<Preferences> getInstance() async {
    _instance ??= Preferences._internal();
    _prefs ??= await SharedPreferences.getInstance();
    return _instance!;
  }

  Preferences._internal();

  SharedPreferences get _preferences => _prefs!;

  // Save Methods
  Future<void> saveCartCount(int count) async {
    try {
      await _preferences.setInt(AppConstants.cartCountKey, count);
    } catch (e) {
      throw PreferencesException('Failed to save cart count: $e');
    }
  }

  Future<void> saveWishlistCount(int count) async {
    try {
      await _preferences.setInt(AppConstants.wishlistCountKey, count);
    } catch (e) {
      throw PreferencesException('Failed to save wishlist count: $e');
    }
  }

  Future<void> saveThemeMode(String themeMode) async {
    try {
      await _preferences.setString(AppConstants.themeKey, themeMode);
    } catch (e) {
      throw PreferencesException('Failed to save theme mode: $e');
    }
  }

  Future<void> saveLanguage(String languageCode) async {
    try {
      await _preferences.setString(AppConstants.languageKey, languageCode);
    } catch (e) {
      throw PreferencesException('Failed to save language: $e');
    }
  }

  Future<void> saveSearchHistory(List<String> searchHistory) async {
    try {
      final historyJson = jsonEncode(searchHistory);
      await _preferences.setString(AppConstants.searchHistoryKey, historyJson);
    } catch (e) {
      throw PreferencesException('Failed to save search history: $e');
    }
  }

  Future<void> saveViewPreference(String viewPreference) async {
    try {
      await _preferences.setString(AppConstants.viewPreferenceKey, viewPreference);
    } catch (e) {
      throw PreferencesException('Failed to save view preference: $e');
    }
  }

  Future<void> saveFirstLaunch(bool isFirstLaunch) async {
    try {
      await _preferences.setBool('first_launch', isFirstLaunch);
    } catch (e) {
      throw PreferencesException('Failed to save first launch: $e');
    }
  }

  Future<void> saveOnboardingCompleted(bool completed) async {
    try {
      await _preferences.setBool('onboarding_completed', completed);
    } catch (e) {
      throw PreferencesException('Failed to save onboarding status: $e');
    }
  }

  Future<void> saveNotificationEnabled(bool enabled) async {
    try {
      await _preferences.setBool('notifications_enabled', enabled);
    } catch (e) {
      throw PreferencesException('Failed to save notification setting: $e');
    }
  }

  Future<void> saveLocationEnabled(bool enabled) async {
    try {
      await _preferences.setBool('location_enabled', enabled);
    } catch (e) {
      throw PreferencesException('Failed to save location setting: $e');
    }
  }

  Future<void> saveSelectedAddress(String addressId) async {
    try {
      await _preferences.setString('selected_address', addressId);
    } catch (e) {
      throw PreferencesException('Failed to save selected address: $e');
    }
  }

  Future<void> saveFilterPreferences(Map<String, dynamic> filters) async {
    try {
      final filtersJson = jsonEncode(filters);
      await _preferences.setString('filter_preferences', filtersJson);
    } catch (e) {
      throw PreferencesException('Failed to save filter preferences: $e');
    }
  }

  Future<void> saveSortingPreference(String sortBy) async {
    try {
      await _preferences.setString('sorting_preference', sortBy);
    } catch (e) {
      throw PreferencesException('Failed to save sorting preference: $e');
    }
  }

  Future<void> saveAppVersion(String version) async {
    try {
      await _preferences.setString('app_version', version);
    } catch (e) {
      throw PreferencesException('Failed to save app version: $e');
    }
  }

  Future<void> saveLastLoginTime(DateTime time) async {
    try {
      await _preferences.setString('last_login_time', time.toIso8601String());
    } catch (e) {
      throw PreferencesException('Failed to save last login time: $e');
    }
  }

  // Read Methods
  Future<int> getCartCount() async {
    try {
      return _preferences.getInt(AppConstants.cartCountKey) ?? 0;
    } catch (e) {
      throw PreferencesException('Failed to read cart count: $e');
    }
  }

  Future<int> getWishlistCount() async {
    try {
      return _preferences.getInt(AppConstants.wishlistCountKey) ?? 0;
    } catch (e) {
      throw PreferencesException('Failed to read wishlist count: $e');
    }
  }

  Future<String> getThemeMode() async {
    try {
      return _preferences.getString(AppConstants.themeKey) ?? 'system';
    } catch (e) {
      throw PreferencesException('Failed to read theme mode: $e');
    }
  }

  Future<String> getLanguage() async {
    try {
      return _preferences.getString(AppConstants.languageKey) ?? 'en';
    } catch (e) {
      throw PreferencesException('Failed to read language: $e');
    }
  }

  Future<List<String>> getSearchHistory() async {
    try {
      final historyJson = _preferences.getString(AppConstants.searchHistoryKey);
      if (historyJson != null) {
        final List<dynamic> history = jsonDecode(historyJson);
        return history.cast<String>();
      }
      return [];
    } catch (e) {
      throw PreferencesException('Failed to read search history: $e');
    }
  }

  Future<String> getViewPreference() async {
    try {
      return _preferences.getString(AppConstants.viewPreferenceKey) ?? 'grid';
    } catch (e) {
      throw PreferencesException('Failed to read view preference: $e');
    }
  }

  Future<bool> isFirstLaunch() async {
    try {
      return _preferences.getBool('first_launch') ?? true;
    } catch (e) {
      throw PreferencesException('Failed to read first launch: $e');
    }
  }

  Future<bool> isOnboardingCompleted() async {
    try {
      return _preferences.getBool('onboarding_completed') ?? false;
    } catch (e) {
      throw PreferencesException('Failed to read onboarding status: $e');
    }
  }

  Future<bool> isNotificationEnabled() async {
    try {
      return _preferences.getBool('notifications_enabled') ?? true;
    } catch (e) {
      throw PreferencesException('Failed to read notification setting: $e');
    }
  }

  Future<bool> isLocationEnabled() async {
    try {
      return _preferences.getBool('location_enabled') ?? false;
    } catch (e) {
      throw PreferencesException('Failed to read location setting: $e');
    }
  }

  Future<String?> getSelectedAddress() async {
    try {
      return _preferences.getString('selected_address');
    } catch (e) {
      throw PreferencesException('Failed to read selected address: $e');
    }
  }

  Future<Map<String, dynamic>> getFilterPreferences() async {
    try {
      final filtersJson = _preferences.getString('filter_preferences');
      if (filtersJson != null) {
        return jsonDecode(filtersJson) as Map<String, dynamic>;
      }
      return {};
    } catch (e) {
      throw PreferencesException('Failed to read filter preferences: $e');
    }
  }

  Future<String> getSortingPreference() async {
    try {
      return _preferences.getString('sorting_preference') ?? 'popularity';
    } catch (e) {
      throw PreferencesException('Failed to read sorting preference: $e');
    }
  }

  Future<String> getAppVersion() async {
    try {
      return _preferences.getString('app_version') ?? '1.0.0';
    } catch (e) {
      throw PreferencesException('Failed to read app version: $e');
    }
  }

  Future<DateTime?> getLastLoginTime() async {
    try {
      final timeString = _preferences.getString('last_login_time');
      if (timeString != null) {
        return DateTime.parse(timeString);
      }
      return null;
    } catch (e) {
      throw PreferencesException('Failed to read last login time: $e');
    }
  }

  // Utility Methods
  Future<void> addToSearchHistory(String query) async {
    try {
      final history = await getSearchHistory();
      history.remove(query); // Remove if already exists
      history.insert(0, query); // Add to beginning

      // Keep only last 20 searches
      if (history.length > 20) {
        history.removeRange(20, history.length);
      }

      await saveSearchHistory(history);
    } catch (e) {
      throw PreferencesException('Failed to add to search history: $e');
    }
  }

  Future<void> clearSearchHistory() async {
    try {
      await saveSearchHistory([]);
    } catch (e) {
      throw PreferencesException('Failed to clear search history: $e');
    }
  }

  Future<void> removeSearchQuery(String query) async {
    try {
      final history = await getSearchHistory();
      history.remove(query);
      await saveSearchHistory(history);
    } catch (e) {
      throw PreferencesException('Failed to remove search query: $e');
    }
  }

  Future<void> incrementCartCount() async {
    try {
      final count = await getCartCount();
      await saveCartCount(count + 1);
    } catch (e) {
      throw PreferencesException('Failed to increment cart count: $e');
    }
  }

  Future<void> decrementCartCount() async {
    try {
      final count = await getCartCount();
      if (count > 0) {
        await saveCartCount(count - 1);
      }
    } catch (e) {
      throw PreferencesException('Failed to decrement cart count: $e');
    }
  }

  Future<void> incrementWishlistCount() async {
    try {
      final count = await getWishlistCount();
      await saveWishlistCount(count + 1);
    } catch (e) {
      throw PreferencesException('Failed to increment wishlist count: $e');
    }
  }

  Future<void> decrementWishlistCount() async {
    try {
      final count = await getWishlistCount();
      if (count > 0) {
        await saveWishlistCount(count - 1);
      }
    } catch (e) {
      throw PreferencesException('Failed to decrement wishlist count: $e');
    }
  }

  // Clear Methods
  Future<void> clearCartCount() async {
    try {
      await _preferences.remove(AppConstants.cartCountKey);
    } catch (e) {
      throw PreferencesException('Failed to clear cart count: $e');
    }
  }

  Future<void> clearWishlistCount() async {
    try {
      await _preferences.remove(AppConstants.wishlistCountKey);
    } catch (e) {
      throw PreferencesException('Failed to clear wishlist count: $e');
    }
  }

  Future<void> clearFilterPreferences() async {
    try {
      await _preferences.remove('filter_preferences');
    } catch (e) {
      throw PreferencesException('Failed to clear filter preferences: $e');
    }
  }

  Future<void> clearAll() async {
    try {
      await _preferences.clear();
    } catch (e) {
      throw PreferencesException('Failed to clear all preferences: $e');
    }
  }

  // Debug Methods
  Future<Map<String, dynamic>> getAllPreferences() async {
    try {
      final keys = _preferences.getKeys();
      final Map<String, dynamic> allPrefs = {};

      for (final key in keys) {
        final value = _preferences.get(key);
        allPrefs[key] = value;
      }

      return allPrefs;
    } catch (e) {
      throw PreferencesException('Failed to read all preferences: $e');
    }
  }

  Future<bool> containsKey(String key) async {
    try {
      return _preferences.containsKey(key);
    } catch (e) {
      throw PreferencesException('Failed to check if key exists: $e');
    }
  }
}

class PreferencesException implements Exception {
  final String message;

  PreferencesException(this.message);

  @override
  String toString() => 'PreferencesException: $message';
}