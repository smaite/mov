import '../../core/network/api_client.dart';
import '../../core/network/api_endpoints.dart';
import '../../core/storage/secure_storage.dart';
import '../models/user.dart';

class AuthRepository {
  final ApiClient _apiClient;
  final SecureStorage _secureStorage;

  AuthRepository({
    required ApiClient apiClient,
    required SecureStorage secureStorage,
  }) : _apiClient = apiClient, _secureStorage = secureStorage;

  // Login with email and password
  Future<User> login({
    required String email,
    required String password,
    bool rememberMe = false,
  }) async {
    try {
      final response = await _apiClient.post(
        ApiEndpoints.login,
        data: {
          'email': email,
          'password': password,
          'remember': rememberMe,
        },
      );

      if (response.data?['success'] == true) {
        final user = User.fromJson(response.data!['user'] as Map<String, dynamic>);
        
        // Save user data
        await _secureStorage.saveUser(user.toJson());
        
        // Save credentials if remember me is enabled
        if (rememberMe) {
          await _secureStorage.saveUserCredentials(email, password);
        }

        return user;
      } else {
        throw Exception(response.data?['message'] ?? 'Login failed');
      }
    } catch (e) {
      throw Exception('Login failed: $e');
    }
  }

  // Register new user
  Future<User> register({
    required String username,
    required String email,
    required String password,
    required String firstName,
    required String lastName,
  }) async {
    try {
      final response = await _apiClient.post(
        ApiEndpoints.register,
        data: {
          'username': username,
          'email': email,
          'password': password,
          'first_name': firstName,
          'last_name': lastName,
        },
      );

      if (response.data?['success'] == true) {
        final user = User.fromJson(response.data!['user'] as Map<String, dynamic>);
        
        // Save user data
        await _secureStorage.saveUser(user.toJson());
        
        return user;
      } else {
        throw Exception(response.data?['message'] ?? 'Registration failed');
      }
    } catch (e) {
      throw Exception('Registration failed: $e');
    }
  }

  // Get current user from storage
  Future<User?> getCurrentUser() async {
    try {
      final userData = await _secureStorage.getUser();
      if (userData != null) {
        return User.fromJson(userData);
      }
      return null;
    } catch (e) {
      return null;
    }
  }

  // Check if user is logged in
  Future<bool> isLoggedIn() async {
    try {
      final user = await getCurrentUser();
      return user != null;
    } catch (e) {
      return false;
    }
  }

  // Get saved credentials
  Future<Map<String, String>?> getSavedCredentials() async {
    try {
      return await _secureStorage.getUserCredentials();
    } catch (e) {
      return null;
    }
  }

  // Auto login with saved credentials
  Future<User?> autoLogin() async {
    try {
      final credentials = await getSavedCredentials();
      if (credentials != null) {
        return await login(
          email: credentials['email']!,
          password: credentials['password']!,
          rememberMe: true,
        );
      }
      return null;
    } catch (e) {
      return null;
    }
  }

  // Logout user
  Future<void> logout() async {
    try {
      // Clear API cookies
      await _apiClient.clearCookies();
      
      // Clear user data
      await _secureStorage.clearAuthData();
    } catch (e) {
      // Even if API logout fails, clear local data
      await _secureStorage.clearAuthData();
    }
  }

  // Update user profile
  Future<User> updateProfile({
    required String userId,
    String? firstName,
    String? lastName,
    String? phone,
    String? address,
  }) async {
    try {
      final response = await _apiClient.post(
        ApiEndpoints.userProfile,
        data: {
          'user_id': userId,
          if (firstName != null) 'first_name': firstName,
          if (lastName != null) 'last_name': lastName,
          if (phone != null) 'phone': phone,
          if (address != null) 'address': address,
        },
      );

      if (response.data?['success'] == true) {
        final user = User.fromJson(response.data!['user'] as Map<String, dynamic>);
        
        // Update stored user data
        await _secureStorage.saveUser(user.toJson());
        
        return user;
      } else {
        throw Exception(response.data?['message'] ?? 'Profile update failed');
      }
    } catch (e) {
      throw Exception('Profile update failed: $e');
    }
  }

  // Change password
  Future<void> changePassword({
    required String currentPassword,
    required String newPassword,
  }) async {
    try {
      final response = await _apiClient.post(
        '/pages/auth/change-password.php',
        data: {
          'current_password': currentPassword,
          'new_password': newPassword,
        },
      );

      if (response.data?['success'] != true) {
        throw Exception(response.data?['message'] ?? 'Password change failed');
      }
    } catch (e) {
      throw Exception('Password change failed: $e');
    }
  }

  // Reset password
  Future<void> resetPassword({required String email}) async {
    try {
      final response = await _apiClient.post(
        '/pages/auth/reset-password.php',
        data: {'email': email},
      );

      if (response.data?['success'] != true) {
        throw Exception(response.data?['message'] ?? 'Password reset failed');
      }
    } catch (e) {
      throw Exception('Password reset failed: $e');
    }
  }

  // Social login (Google)
  Future<User> loginWithGoogle({required String googleToken}) async {
    try {
      final response = await _apiClient.post(
        '/pages/auth/google-login.php',
        data: {
          'google_token': googleToken,
        },
      );

      if (response.data?['success'] == true) {
        final user = User.fromJson(response.data!['user'] as Map<String, dynamic>);
        
        // Save user data
        await _secureStorage.saveUser(user.toJson());
        
        return user;
      } else {
        throw Exception(response.data?['message'] ?? 'Google login failed');
      }
    } catch (e) {
      throw Exception('Google login failed: $e');
    }
  }

  // Social login (Facebook)
  Future<User> loginWithFacebook({required String facebookToken}) async {
    try {
      final response = await _apiClient.post(
        '/pages/auth/facebook-login.php',
        data: {
          'facebook_token': facebookToken,
        },
      );

      if (response.data?['success'] == true) {
        final user = User.fromJson(response.data!['user'] as Map<String, dynamic>);
        
        // Save user data
        await _secureStorage.saveUser(user.toJson());
        
        return user;
      } else {
        throw Exception(response.data?['message'] ?? 'Facebook login failed');
      }
    } catch (e) {
      throw Exception('Facebook login failed: $e');
    }
  }
}