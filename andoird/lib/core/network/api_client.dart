import 'dart:convert';
import 'dart:io';
import 'package:dio/dio.dart';
import 'package:cookie_jar/cookie_jar.dart';
import '../../app/constants.dart';

class ApiClient {
  static final ApiClient _instance = ApiClient._internal();
  factory ApiClient() => _instance;
  ApiClient._internal();

  late Dio _dio;
  late CookieJar _cookieJar;

  Dio get dio => _dio;

  void initialize() {
    _cookieJar = CookieJar();
    _dio = Dio(BaseOptions(
      baseUrl: AppConstants.apiBaseUrl,
      connectTimeout: Duration(milliseconds: AppConstants.connectTimeout),
      receiveTimeout: Duration(milliseconds: AppConstants.networkTimeout),
      sendTimeout: Duration(milliseconds: AppConstants.networkTimeout),
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'Accept': 'application/json',
        'User-Agent': '${AppConstants.appName}/${AppConstants.appVersion}',
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      validateStatus: (status) {
        return status != null && status < 500;
      },
    ));

    // Add cookie manager for session handling
    // TODO: Implement proper cookie management
    // _dio.interceptors.add(CookieManager(_cookieJar));

    // Add logging interceptor for debug mode
    if (AppConstants.isDebugMode) {
      _dio.interceptors.add(LogInterceptor(
        requestBody: true,
        responseBody: true,
        requestHeader: true,
        responseHeader: true,
        error: true,
        logPrint: (object) {
          print('[API] $object');
        },
      ));
    }

    // Add request interceptor for CSRF token
    _dio.interceptors.add(InterceptorsWrapper(
      onRequest: (options, handler) {
        // Add CSRF token if available
        if (options.method == 'POST' || options.method == 'PUT' || options.method == 'DELETE') {
          // TODO: Implement CSRF token management
          // options.data['csrf_token'] = _getCsrfToken();
        }
        handler.next(options);
      },
      onResponse: (response, handler) {
        // Extract CSRF token from response headers if available
        if (response.headers['set-cookie'] != null) {
          // TODO: Parse and store CSRF token
        }
        handler.next(response);
      },
      onError: (error, handler) {
        // Handle common errors
        final response = error.response;
        if (response != null) {
          switch (response.statusCode) {
            case 401:
              // Session expired - redirect to login
              _handleSessionExpired();
              break;
            case 403:
              // Forbidden - insufficient permissions
              _handleForbidden();
              break;
            case 404:
              // Not found
              _handleNotFound();
              break;
            case 500:
              // Server error
              _handleServerError();
              break;
            case 503:
              // Service unavailable
              _handleServiceUnavailable();
              break;
          }
        } else {
          // Network error
          _handleNetworkError(error);
        }
        handler.next(error);
      },
    ));
  }

  // HTTP Methods
  Future<Response<Map<String, dynamic>>> get(
    String path, {
    Map<String, dynamic>? queryParameters,
    Options? options,
  }) async {
    try {
      final response = await _dio.get<Map<String, dynamic>>(
        path,
        queryParameters: queryParameters,
        options: options,
      );
      return _handleResponse(response);
    } catch (e) {
      throw _handleError(e);
    }
  }

  Future<Response<Map<String, dynamic>>> post(
    String path, {
    dynamic data,
    Map<String, dynamic>? queryParameters,
    Options? options,
  }) async {
    try {
      final response = await _dio.post<Map<String, dynamic>>(
        path,
        data: data,
        queryParameters: queryParameters,
        options: options,
      );
      return _handleResponse(response);
    } catch (e) {
      throw _handleError(e);
    }
  }

  Future<Response<Map<String, dynamic>>> put(
    String path, {
    dynamic data,
    Map<String, dynamic>? queryParameters,
    Options? options,
  }) async {
    try {
      final response = await _dio.put<Map<String, dynamic>>(
        path,
        data: data,
        queryParameters: queryParameters,
        options: options,
      );
      return _handleResponse(response);
    } catch (e) {
      throw _handleError(e);
    }
  }

  Future<Response<Map<String, dynamic>>> delete(
    String path, {
    dynamic data,
    Map<String, dynamic>? queryParameters,
    Options? options,
  }) async {
    try {
      final response = await _dio.delete<Map<String, dynamic>>(
        path,
        data: data,
        queryParameters: queryParameters,
        options: options,
      );
      return _handleResponse(response);
    } catch (e) {
      throw _handleError(e);
    }
  }

  Future<Response<Map<String, dynamic>>> upload(
    String path, {
    required String filePath,
    Map<String, dynamic>? data,
    Map<String, dynamic>? queryParameters,
    ProgressCallback? onSendProgress,
  }) async {
    try {
      final fileName = filePath.split('/').last;
      final file = await MultipartFile.fromFile(filePath, filename: fileName);

      final formData = FormData.fromMap({
        'file': file,
        ...?data,
      });

      final response = await _dio.post<Map<String, dynamic>>(
        path,
        data: formData,
        queryParameters: queryParameters,
        onSendProgress: onSendProgress,
      );
      return _handleResponse(response);
    } catch (e) {
      throw _handleError(e);
    }
  }

  // Response Handler
  Response<Map<String, dynamic>> _handleResponse(Response response) {
    if (response.data is Map<String, dynamic>) {
      return response as Response<Map<String, dynamic>>;
    } else if (response.data is String) {
      try {
        final data = jsonDecode(response.data as String) as Map<String, dynamic>;
        return Response<Map<String, dynamic>>(
          data: data,
          statusCode: response.statusCode,
          statusMessage: response.statusMessage,
          headers: response.headers,
          requestOptions: response.requestOptions,
        );
      } catch (e) {
        throw ApiException('Invalid response format');
      }
    } else {
      throw ApiException('Unexpected response format');
    }
  }

  // Error Handler
  ApiException _handleError(dynamic error) {
    if (error is DioException) {
      switch (error.type) {
        case DioExceptionType.connectionTimeout:
        case DioExceptionType.sendTimeout:
        case DioExceptionType.receiveTimeout:
          return ApiException('Request timeout. Please try again.');
        case DioExceptionType.badResponse:
          final response = error.response;
          if (response?.data is Map<String, dynamic>) {
            final data = response!.data as Map<String, dynamic>;
            final message = data['message'] ?? data['error'] ?? 'Unknown error occurred';
            return ApiException(message, statusCode: response.statusCode);
          }
          return ApiException('Server error: ${response?.statusCode}');
        case DioExceptionType.cancel:
          return ApiException('Request was cancelled');
        case DioExceptionType.connectionError:
          return ApiException(AppConstants.networkErrorMessage);
        case DioExceptionType.badCertificate:
          return ApiException('Invalid SSL certificate');
        case DioExceptionType.unknown:
          if (error.error is SocketException) {
            return ApiException(AppConstants.networkErrorMessage);
          }
          return ApiException(AppConstants.generalErrorMessage);
      }
    }
    return ApiException(AppConstants.generalErrorMessage);
  }

  // Error Handlers
  void _handleSessionExpired() {
    // TODO: Implement session expiry handling
    // Clear stored credentials
    // Navigate to login screen
  }

  void _handleForbidden() {
    // TODO: Implement forbidden access handling
  }

  void _handleNotFound() {
    // TODO: Implement not found handling
  }

  void _handleServerError() {
    // TODO: Implement server error handling
  }

  void _handleServiceUnavailable() {
    // TODO: Implement service unavailable handling
  }

  void _handleNetworkError(DioException error) {
    // TODO: Implement network error handling
  }

  // Cookie Management
  Future<void> clearCookies() async {
    await _cookieJar.deleteAll();
  }

  Future<List<Cookie>> getCookies(Uri uri) async {
    return await _cookieJar.loadForRequest(uri);
  }

  Future<void> saveCookies(List<Cookie> cookies) async {
    // TODO: Implement cookie saving if needed
  }
}

// Custom Exception Classes
class ApiException implements Exception {
  final String message;
  final int? statusCode;

  ApiException(this.message, {this.statusCode});

  @override
  String toString() => 'ApiException: $message';
}

class NetworkException implements Exception {
  final String message;

  NetworkException(this.message);

  @override
  String toString() => 'NetworkException: $message';
}

class ServerException implements Exception {
  final String message;
  final int? statusCode;

  ServerException(this.message, {this.statusCode});

  @override
  String toString() => 'ServerException: $message';
}

class TimeoutException implements Exception {
  final String message;

  TimeoutException(this.message);

  @override
  String toString() => 'TimeoutException: $message';
}