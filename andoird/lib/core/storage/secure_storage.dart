import 'dart:convert';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import '../../app/constants.dart';

class SecureStorage {
  static final SecureStorage _instance = SecureStorage._internal();
  factory SecureStorage() => _instance;
  SecureStorage._internal();

  final FlutterSecureStorage _storage = const FlutterSecureStorage(
    aOptions: AndroidOptions(
      encryptedSharedPreferences: true,
    ),
    iOptions: IOSOptions(
      accessibility: KeychainAccessibility.first_unlock_this_device,
    ),
  );

  // Save Methods
  Future<void> saveToken(String token) async {
    try {
      await _storage.write(key: AppConstants.tokenKey, value: token);
    } catch (e) {
      throw StorageException('Failed to save token: $e');
    }
  }

  Future<void> saveUser(Map<String, dynamic> userData) async {
    try {
      final userJson = jsonEncode(userData);
      await _storage.write(key: AppConstants.userKey, value: userJson);
    } catch (e) {
      throw StorageException('Failed to save user data: $e');
    }
  }

  Future<void> saveRefreshToken(String refreshToken) async {
    try {
      await _storage.write(key: 'refresh_token', value: refreshToken);
    } catch (e) {
      throw StorageException('Failed to save refresh token: $e');
    }
  }

  Future<void> saveUserCredentials(String email, String password) async {
    try {
      final credentials = jsonEncode({
        'email': email,
        'password': password,
      });
      await _storage.write(key: 'user_credentials', value: credentials);
    } catch (e) {
      throw StorageException('Failed to save user credentials: $e');
    }
  }

  Future<void> saveBiometricEnabled(bool enabled) async {
    try {
      await _storage.write(key: 'biometric_enabled', value: enabled.toString());
    } catch (e) {
      throw StorageException('Failed to save biometric setting: $e');
    }
  }

  Future<void> saveSecurityPin(String pin) async {
    try {
      await _storage.write(key: 'security_pin', value: pin);
    } catch (e) {
      throw StorageException('Failed to save security PIN: $e');
    }
  }

  // Read Methods
  Future<String?> getToken() async {
    try {
      return await _storage.read(key: AppConstants.tokenKey);
    } catch (e) {
      throw StorageException('Failed to read token: $e');
    }
  }

  Future<Map<String, dynamic>?> getUser() async {
    try {
      final userJson = await _storage.read(key: AppConstants.userKey);
      if (userJson != null) {
        return jsonDecode(userJson) as Map<String, dynamic>;
      }
      return null;
    } catch (e) {
      throw StorageException('Failed to read user data: $e');
    }
  }

  Future<String?> getRefreshToken() async {
    try {
      return await _storage.read(key: 'refresh_token');
    } catch (e) {
      throw StorageException('Failed to read refresh token: $e');
    }
  }

  Future<Map<String, String>?> getUserCredentials() async {
    try {
      final credentialsJson = await _storage.read(key: 'user_credentials');
      if (credentialsJson != null) {
        final credentials = jsonDecode(credentialsJson) as Map<String, dynamic>;
        return {
          'email': credentials['email'] as String,
          'password': credentials['password'] as String,
        };
      }
      return null;
    } catch (e) {
      throw StorageException('Failed to read user credentials: $e');
    }
  }

  Future<bool> isBiometricEnabled() async {
    try {
      final enabled = await _storage.read(key: 'biometric_enabled');
      return enabled == 'true';
    } catch (e) {
      throw StorageException('Failed to read biometric setting: $e');
    }
  }

  Future<String?> getSecurityPin() async {
    try {
      return await _storage.read(key: 'security_pin');
    } catch (e) {
      throw StorageException('Failed to read security PIN: $e');
    }
  }

  // Check Methods
  Future<bool> hasToken() async {
    try {
      final token = await getToken();
      return token != null && token.isNotEmpty;
    } catch (e) {
      return false;
    }
  }

  Future<bool> hasUser() async {
    try {
      final user = await getUser();
      return user != null && user.isNotEmpty;
    } catch (e) {
      return false;
    }
  }

  Future<bool> hasRefreshToken() async {
    try {
      final refreshToken = await getRefreshToken();
      return refreshToken != null && refreshToken.isNotEmpty;
    } catch (e) {
      return false;
    }
  }

  Future<bool> hasUserCredentials() async {
    try {
      final credentials = await getUserCredentials();
      return credentials != null &&
             credentials['email']!.isNotEmpty &&
             credentials['password']!.isNotEmpty;
    } catch (e) {
      return false;
    }
  }

  Future<bool> hasSecurityPin() async {
    try {
      final pin = await getSecurityPin();
      return pin != null && pin.isNotEmpty;
    } catch (e) {
      return false;
    }
  }

  // Delete Methods
  Future<void> deleteToken() async {
    try {
      await _storage.delete(key: AppConstants.tokenKey);
    } catch (e) {
      throw StorageException('Failed to delete token: $e');
    }
  }

  Future<void> deleteUser() async {
    try {
      await _storage.delete(key: AppConstants.userKey);
    } catch (e) {
      throw StorageException('Failed to delete user data: $e');
    }
  }

  Future<void> deleteRefreshToken() async {
    try {
      await _storage.delete(key: 'refresh_token');
    } catch (e) {
      throw StorageException('Failed to delete refresh token: $e');
    }
  }

  Future<void> deleteUserCredentials() async {
    try {
      await _storage.delete(key: 'user_credentials');
    } catch (e) {
      throw StorageException('Failed to delete user credentials: $e');
    }
  }

  Future<void> deleteSecurityPin() async {
    try {
      await _storage.delete(key: 'security_pin');
    } catch (e) {
      throw StorageException('Failed to delete security PIN: $e');
    }
  }

  // Clear All Data
  Future<void> clearAll() async {
    try {
      await _storage.deleteAll();
    } catch (e) {
      throw StorageException('Failed to clear all secure data: $e');
    }
  }

  // Clear Auth Data
  Future<void> clearAuthData() async {
    try {
      await Future.wait([
        deleteToken(),
        deleteUser(),
        deleteRefreshToken(),
      ]);
    } catch (e) {
      throw StorageException('Failed to clear auth data: $e');
    }
  }

  // Utility Methods
  Future<bool> isDataSecure() async {
    try {
      // Test if secure storage is working
      const testKey = 'test_secure';
      const testValue = 'test_value';

      await _storage.write(key: testKey, value: testValue);
      final readValue = await _storage.read(key: testKey);
      await _storage.delete(key: testKey);

      return readValue == testValue;
    } catch (e) {
      return false;
    }
  }

  Future<void> migrateFromPreferences(Map<String, String> preferencesData) async {
    try {
      for (final entry in preferencesData.entries) {
        if (entry.key == AppConstants.tokenKey ||
            entry.key == AppConstants.userKey ||
            entry.key == 'refresh_token') {
          await _storage.write(key: entry.key, value: entry.value);
        }
      }
    } catch (e) {
      throw StorageException('Failed to migrate data: $e');
    }
  }

  // Get all stored keys (for debugging purposes)
  Future<Set<String>> getAllKeys() async {
    try {
      return await _storage.readAll() as Set<String>;
    } catch (e) {
      throw StorageException('Failed to read all keys: $e');
    }
  }

  // Check if storage is available
  Future<bool> isAvailable() async {
    try {
      const testKey = 'availability_test';
      await _storage.write(key: testKey, value: 'test');
      await _storage.delete(key: testKey);
      return true;
    } catch (e) {
      return false;
    }
  }
}

class StorageException implements Exception {
  final String message;

  StorageException(this.message);

  @override
  String toString() => 'StorageException: $message';
}