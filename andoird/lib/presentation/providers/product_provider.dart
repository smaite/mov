import 'package:flutter/foundation.dart';
import '../../core/network/api_client.dart';
import '../../core/network/api_endpoints.dart';
import '../../data/models/product.dart' as models;

enum ProductStatus { initial, loading, loaded, error }

class ProductProvider extends ChangeNotifier {
  final ApiClient _apiClient;

  ProductStatus _status = ProductStatus.initial;
  List<models.Product> _products = [];
  List<models.Product> _featuredProducts = [];
  List<models.Category> _categories = [];
  String? _errorMessage;
  
  // Search and filter
  String _searchQuery = '';
  String? _selectedCategoryId;
  List<models.Product> _searchResults = [];
  bool _isSearching = false;

  ProductProvider({required ApiClient apiClient}) : _apiClient = apiClient;

  // Getters
  ProductStatus get status => _status;
  List<models.Product> get products => _products;
  List<models.Product> get featuredProducts => _featuredProducts;
  List<models.Category> get categories => _categories;
  String? get errorMessage => _errorMessage;
  String get searchQuery => _searchQuery;
  String? get selectedCategoryId => _selectedCategoryId;
  List<models.Product> get searchResults => _searchResults;
  bool get isSearching => _isSearching;
  bool get isLoading => _status == ProductStatus.loading;

  // Load home data (featured products and categories)
  Future<void> loadHomeData() async {
    try {
      _setStatus(ProductStatus.loading);
      _clearError();

      final response = await _apiClient.get(ApiEndpoints.home);

      if (response.data != null) {
        // Parse featured products
        if (response.data!['featured_products'] is List) {
          _featuredProducts = (response.data!['featured_products'] as List)
              .map((json) => models.Product.fromJson(json as Map<String, dynamic>))
              .toList();
        }

        // Parse categories
        if (response.data!['categories'] is List) {
          _categories = (response.data!['categories'] as List)
              .map((json) => models.Category.fromJson(json as Map<String, dynamic>))
              .toList();
        }

        _setStatus(ProductStatus.loaded);
      } else {
        _setError('Failed to load home data');
      }
    } catch (e) {
      _setError(e.toString());
    }
  }

  // Load products by category
  Future<void> loadProductsByCategory(String categoryId) async {
    try {
      _setStatus(ProductStatus.loading);
      _clearError();

      final response = await _apiClient.get(
        '${ApiEndpoints.categoryProducts}$categoryId',
      );

      if (response.data != null && response.data!['products'] is List) {
        _products = (response.data!['products'] as List)
            .map((json) => models.Product.fromJson(json as Map<String, dynamic>))
            .toList();

        _selectedCategoryId = categoryId;
        _setStatus(ProductStatus.loaded);
      } else {
        _setError('Failed to load products');
      }
    } catch (e) {
      _setError(e.toString());
    }
  }

  // Load all products
  Future<void> loadAllProducts() async {
    try {
      _setStatus(ProductStatus.loading);
      _clearError();

      final response = await _apiClient.get(ApiEndpoints.allProducts);

      if (response.data != null && response.data!['products'] is List) {
        _products = (response.data!['products'] as List)
            .map((json) => models.Product.fromJson(json as Map<String, dynamic>))
            .toList();

        _selectedCategoryId = null;
        _setStatus(ProductStatus.loaded);
      } else {
        _setError('Failed to load products');
      }
    } catch (e) {
      _setError(e.toString());
    }
  }

  // Search products
  Future<void> searchProducts(String query) async {
    if (query.trim().isEmpty) {
      _searchResults.clear();
      _searchQuery = '';
      _isSearching = false;
      notifyListeners();
      return;
    }

    try {
      _isSearching = true;
      _searchQuery = query;
      notifyListeners();

      final response = await _apiClient.get(
        '${ApiEndpoints.search}${Uri.encodeComponent(query)}',
      );

      if (response.data != null && response.data!['products'] is List) {
        _searchResults = (response.data!['products'] as List)
            .map((json) => models.Product.fromJson(json as Map<String, dynamic>))
            .toList();
      } else {
        _searchResults.clear();
      }

      _isSearching = false;
      notifyListeners();
    } catch (e) {
      _isSearching = false;
      _setError(e.toString());
    }
  }

  // Clear search
  void clearSearch() {
    _searchQuery = '';
    _searchResults.clear();
    _isSearching = false;
    notifyListeners();
  }

  // Get product by ID
  models.Product? getProductById(String productId) {
    try {
      return _products.firstWhere((product) => product.id == productId);
    } catch (e) {
      try {
        return _featuredProducts.firstWhere((product) => product.id == productId);
      } catch (e) {
        try {
          return _searchResults.firstWhere((product) => product.id == productId);
        } catch (e) {
          return null;
        }
      }
    }
  }

  // Load single product details
  Future<models.Product?> loadProductDetails(String productId) async {
    try {
      final response = await _apiClient.get(
        '${ApiEndpoints.productDetails}$productId',
      );

      if (response.data != null && response.data!['product'] != null) {
        return models.Product.fromJson(response.data!['product'] as Map<String, dynamic>);
      }
      return null;
    } catch (e) {
      _setError(e.toString());
      return null;
    }
  }

  // Filter products by price range
  List<models.Product> filterByPriceRange(double minPrice, double maxPrice) {
    return _products.where((product) {
      final price = product.effectivePrice;
      return price >= minPrice && price <= maxPrice;
    }).toList();
  }

  // Sort products
  List<models.Product> sortProducts(List<models.Product> products, String sortBy) {
    final sortedProducts = List<models.Product>.from(products);
    
    switch (sortBy.toLowerCase()) {
      case 'price_low_to_high':
        sortedProducts.sort((a, b) => a.effectivePrice.compareTo(b.effectivePrice));
        break;
      case 'price_high_to_low':
        sortedProducts.sort((a, b) => b.effectivePrice.compareTo(a.effectivePrice));
        break;
      case 'rating':
        sortedProducts.sort((a, b) => b.rating.compareTo(a.rating));
        break;
      case 'newest':
        sortedProducts.sort((a, b) => b.createdAt.compareTo(a.createdAt));
        break;
      case 'popularity':
        sortedProducts.sort((a, b) => b.reviewCount.compareTo(a.reviewCount));
        break;
      case 'name':
        sortedProducts.sort((a, b) => a.name.compareTo(b.name));
        break;
      default:
        break;
    }
    
    return sortedProducts;
  }

  // Get products by category
  List<models.Product> getProductsByCategory(String categoryId) {
    return _products.where((product) => product.category == categoryId).toList();
  }

  // Get category by ID
  models.Category? getCategoryById(String categoryId) {
    try {
      return _categories.firstWhere((category) => category.id == categoryId);
    } catch (e) {
      return null;
    }
  }

  // Refresh data
  Future<void> refresh() async {
    await loadHomeData();
  }

  // Helper methods
  void _setStatus(ProductStatus status) {
    _status = status;
    notifyListeners();
  }

  void _setError(String error) {
    _errorMessage = error;
    _status = ProductStatus.error;
    notifyListeners();
  }

  void _clearError() {
    _errorMessage = null;
  }

  void clearError() {
    _clearError();
    notifyListeners();
  }
}