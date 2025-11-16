import 'package:shared_preferences/shared_preferences.dart';

/// Shared Preferences Wrapper for App-wide Settings
class Preferences {
  static SharedPreferences? _prefs;

  // Keys
  static const String _keyThemeMode = 'theme_mode';
  static const String _keyLanguage = 'language';
  static const String _keyFirstLaunch = 'first_launch';
  static const String _keyViewMode = 'view_mode'; // grid or list
  static const String _keySearchHistory = 'search_history';
  static const String _keyNotificationsEnabled = 'notifications_enabled';

  /// Initialize SharedPreferences
  static Future<void> getInstance() async {
    _prefs = await SharedPreferences.getInstance();
  }

  /// Ensure preferences are initialized
  static SharedPreferences get instance {
    if (_prefs == null) {
      throw Exception('Preferences not initialized. Call getInstance() first.');
    }
    return _prefs!;
  }

  // Theme Mode
  static String get themeMode => instance.getString(_keyThemeMode) ?? 'system';
  static Future<bool> setThemeMode(String mode) => instance.setString(_keyThemeMode, mode);

  // Language
  static String get language => instance.getString(_keyLanguage) ?? 'en';
  static Future<bool> setLanguage(String lang) => instance.setString(_keyLanguage, lang);

  // First Launch
  static bool get isFirstLaunch => instance.getBool(_keyFirstLaunch) ?? true;
  static Future<bool> setFirstLaunch(bool value) => instance.setBool(_keyFirstLaunch, value);

  // View Mode (grid/list)
  static String get viewMode => instance.getString(_keyViewMode) ?? 'grid';
  static Future<bool> setViewMode(String mode) => instance.setString(_keyViewMode, mode);

  // Search History
  static List<String> get searchHistory => instance.getStringList(_keySearchHistory) ?? [];
  static Future<bool> addSearchTerm(String term) async {
    final history = searchHistory;
    if (!history.contains(term)) {
      history.insert(0, term);
      // Keep only last 10 searches
      if (history.length > 10) {
        history.removeLast();
      }
      return instance.setStringList(_keySearchHistory, history);
    }
    return true;
  }
  static Future<bool> clearSearchHistory() => instance.remove(_keySearchHistory);

  // Notifications
  static bool get notificationsEnabled => instance.getBool(_keyNotificationsEnabled) ?? true;
  static Future<bool> setNotificationsEnabled(bool enabled) => 
      instance.setBool(_keyNotificationsEnabled, enabled);

  // Clear all preferences
  static Future<bool> clearAll() => instance.clear();
}