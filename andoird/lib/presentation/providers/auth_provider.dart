import 'package:flutter/foundation.dart';
import '../../core/network/api_client.dart';
import '../../core/storage/secure_storage.dart';
import '../../data/models/user.dart';
import '../../data/repositories/auth_repository.dart';

enum AuthStatus { initial, loading, authenticated, unauthenticated, error }

class AuthProvider extends ChangeNotifier {
  final AuthRepository _authRepository;
  
  AuthStatus _status = AuthStatus.initial;
  User? _user;
  String? _errorMessage;

  AuthProvider({
    required ApiClient apiClient,
    required SecureStorage secureStorage,
  }) : _authRepository = AuthRepository(
          apiClient: apiClient,
          secureStorage: secureStorage,
        ) {
    _checkAuthStatus();
  }

  // Getters
  AuthStatus get status => _status;
  User? get user => _user;
  String? get errorMessage => _errorMessage;
  bool get isAuthenticated => _status == AuthStatus.authenticated && _user != null;
  bool get isLoading => _status == AuthStatus.loading;

  // Check if user is already logged in
  Future<void> _checkAuthStatus() async {
    try {
      _setStatus(AuthStatus.loading);
      
      final user = await _authRepository.getCurrentUser();
      if (user != null) {
        _user = user;
        _setStatus(AuthStatus.authenticated);
      } else {
        // Try auto login with saved credentials
        final autoLoginUser = await _authRepository.autoLogin();
        if (autoLoginUser != null) {
          _user = autoLoginUser;
          _setStatus(AuthStatus.authenticated);
        } else {
          _setStatus(AuthStatus.unauthenticated);
        }
      }
    } catch (e) {
      _setStatus(AuthStatus.unauthenticated);
    }
  }

  // Login
  Future<bool> login({
    required String email,
    required String password,
    bool rememberMe = false,
  }) async {
    try {
      _setStatus(AuthStatus.loading);
      _clearError();

      final user = await _authRepository.login(
        email: email,
        password: password,
        rememberMe: rememberMe,
      );

      _user = user;
      _setStatus(AuthStatus.authenticated);
      return true;
    } catch (e) {
      _setError(e.toString());
      _setStatus(AuthStatus.unauthenticated);
      return false;
    }
  }

  // Register
  Future<bool> register({
    required String username,
    required String email,
    required String password,
    required String firstName,
    required String lastName,
  }) async {
    try {
      _setStatus(AuthStatus.loading);
      _clearError();

      final user = await _authRepository.register(
        username: username,
        email: email,
        password: password,
        firstName: firstName,
        lastName: lastName,
      );

      _user = user;
      _setStatus(AuthStatus.authenticated);
      return true;
    } catch (e) {
      _setError(e.toString());
      _setStatus(AuthStatus.unauthenticated);
      return false;
    }
  }

  // Google Login
  Future<bool> loginWithGoogle(String googleToken) async {
    try {
      _setStatus(AuthStatus.loading);
      _clearError();

      final user = await _authRepository.loginWithGoogle(googleToken: googleToken);

      _user = user;
      _setStatus(AuthStatus.authenticated);
      return true;
    } catch (e) {
      _setError(e.toString());
      _setStatus(AuthStatus.unauthenticated);
      return false;
    }
  }

  // Facebook Login
  Future<bool> loginWithFacebook(String facebookToken) async {
    try {
      _setStatus(AuthStatus.loading);
      _clearError();

      final user = await _authRepository.loginWithFacebook(facebookToken: facebookToken);

      _user = user;
      _setStatus(AuthStatus.authenticated);
      return true;
    } catch (e) {
      _setError(e.toString());
      _setStatus(AuthStatus.unauthenticated);
      return false;
    }
  }

  // Logout
  Future<void> logout() async {
    try {
      _setStatus(AuthStatus.loading);
      await _authRepository.logout();
      _user = null;
      _setStatus(AuthStatus.unauthenticated);
    } catch (e) {
      // Even if logout fails, clear local state
      _user = null;
      _setStatus(AuthStatus.unauthenticated);
    }
  }

  // Update Profile
  Future<bool> updateProfile({
    String? firstName,
    String? lastName,
    String? phone,
    String? address,
  }) async {
    if (_user == null) return false;

    try {
      _clearError();

      final updatedUser = await _authRepository.updateProfile(
        userId: _user!.id,
        firstName: firstName,
        lastName: lastName,
        phone: phone,
        address: address,
      );

      _user = updatedUser;
      notifyListeners();
      return true;
    } catch (e) {
      _setError(e.toString());
      return false;
    }
  }

  // Change Password
  Future<bool> changePassword({
    required String currentPassword,
    required String newPassword,
  }) async {
    try {
      _clearError();

      await _authRepository.changePassword(
        currentPassword: currentPassword,
        newPassword: newPassword,
      );

      return true;
    } catch (e) {
      _setError(e.toString());
      return false;
    }
  }

  // Reset Password
  Future<bool> resetPassword({required String email}) async {
    try {
      _clearError();

      await _authRepository.resetPassword(email: email);
      return true;
    } catch (e) {
      _setError(e.toString());
      return false;
    }
  }

  // Helper methods
  void _setStatus(AuthStatus status) {
    _status = status;
    notifyListeners();
  }

  void _setError(String error) {
    _errorMessage = error;
    notifyListeners();
  }

  void _clearError() {
    _errorMessage = null;
    if (_status == AuthStatus.error) {
      notifyListeners();
    }
  }

  void clearError() {
    _clearError();
  }
}