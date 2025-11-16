import 'package:flutter/foundation.dart';
import '../../core/network/api_client.dart';
import '../../core/network/api_endpoints.dart';
import '../../data/models/cart.dart';
import '../../data/models/product.dart' as models;

enum CartStatus { initial, loading, loaded, error }

class CartProvider extends ChangeNotifier {
  final ApiClient _apiClient;

  CartStatus _status = CartStatus.initial;
  Cart? _cart;
  String? _errorMessage;
  int _cartCount = 0;

  CartProvider({required ApiClient apiClient}) : _apiClient = apiClient {
    _loadCartCount();
  }

  // Getters
  CartStatus get status => _status;
  Cart? get cart => _cart;
  String? get errorMessage => _errorMessage;
  int get cartCount => _cartCount;
  bool get isLoading => _status == CartStatus.loading;
  bool get hasItems => _cart?.isNotEmpty ?? false;
  double get subtotal => _cart?.subtotal ?? 0.0;
  double get deliveryFee => _cart?.deliveryFee ?? 0.0;
  double get total => _cart?.total ?? 0.0;
  List<CartItem> get items => _cart?.items ?? [];

  // Load cart count
  Future<void> _loadCartCount() async {
    try {
      final response = await _apiClient.post(ApiEndpoints.cartCount);
      if (response.data?['success'] == true) {
        _cartCount = response.data?['count'] ?? 0;
        notifyListeners();
      }
    } catch (e) {
      // Silently fail for cart count
    }
  }

  // Load full cart details
  Future<void> loadCart() async {
    try {
      _setStatus(CartStatus.loading);
      _clearError();

      final response = await _apiClient.get('/pages/cart/index.php');

      if (response.data != null) {
        _cart = Cart.fromJson(response.data!);
        _cartCount = _cart?.totalQuantity ?? 0;
        _setStatus(CartStatus.loaded);
      } else {
        _setError('Failed to load cart');
      }
    } catch (e) {
      _setError(e.toString());
    }
  }

  // Add item to cart
  Future<bool> addToCart({
    required String productId,
    int quantity = 1,
    Map<String, dynamic>? selectedVariants,
  }) async {
    try {
      _clearError();

      final response = await _apiClient.post(
        ApiEndpoints.cart,
        data: {
          'product_id': productId,
          'quantity': quantity,
          if (selectedVariants != null) 'selected_variants': selectedVariants,
        },
      );

      if (response.data?['success'] == true) {
        _cartCount = response.data?['cart_count'] ?? _cartCount + quantity;
        
        // Update local cart if loaded
        if (_cart != null) {
          await loadCart(); // Reload to get updated cart
        }
        
        notifyListeners();
        return true;
      } else {
        _setError(response.data?['message'] ?? 'Failed to add item to cart');
        return false;
      }
    } catch (e) {
      _setError(e.toString());
      return false;
    }
  }

  // Update cart item quantity
  Future<bool> updateQuantity({
    required String cartItemId,
    required int quantity,
  }) async {
    try {
      _clearError();

      final response = await _apiClient.post(
        ApiEndpoints.cartUpdate,
        data: {
          'cart_id': cartItemId,
          'quantity': quantity,
        },
      );

      if (response.data?['success'] == true) {
        await loadCart(); // Reload cart to get updated data
        return true;
      } else {
        _setError(response.data?['message'] ?? 'Failed to update quantity');
        return false;
      }
    } catch (e) {
      _setError(e.toString());
      return false;
    }
  }

  // Remove item from cart
  Future<bool> removeFromCart(String cartItemId) async {
    try {
      _clearError();

      final response = await _apiClient.post(
        ApiEndpoints.cartRemove,
        data: {
          'cart_id': cartItemId,
        },
      );

      if (response.data?['success'] == true) {
        await loadCart(); // Reload cart to get updated data
        return true;
      } else {
        _setError(response.data?['message'] ?? 'Failed to remove item');
        return false;
      }
    } catch (e) {
      _setError(e.toString());
      return false;
    }
  }

  // Clear entire cart
  Future<bool> clearCart() async {
    try {
      _clearError();

      final response = await _apiClient.post('/ajax/cart.php?action=clear');

      if (response.data?['success'] == true) {
        _cart = null;
        _cartCount = 0;
        _setStatus(CartStatus.loaded);
        return true;
      } else {
        _setError(response.data?['message'] ?? 'Failed to clear cart');
        return false;
      }
    } catch (e) {
      _setError(e.toString());
      return false;
    }
  }

  // Check if product is in cart
  bool isProductInCart(String productId) {
    return _cart?.containsProduct(productId) ?? false;
  }

  // Get quantity of product in cart
  int getProductQuantity(String productId) {
    return _cart?.getProductQuantity(productId) ?? 0;
  }

  // Get cart item by product ID
  CartItem? getCartItemByProduct(String productId) {
    return _cart?.findItem(productId);
  }

  // Local cart operations (optimistic updates)
  void _addItemLocally(CartItem item) {
    if (_cart != null) {
      _cart = _cart!.addItem(item);
      _cartCount = _cart!.totalQuantity;
      notifyListeners();
    }
  }

  void _updateQuantityLocally(String productId, int quantity) {
    if (_cart != null) {
      _cart = _cart!.updateItemQuantity(productId, quantity);
      _cartCount = _cart!.totalQuantity;
      notifyListeners();
    }
  }

  void _removeItemLocally(String productId) {
    if (_cart != null) {
      _cart = _cart!.removeItem(productId);
      _cartCount = _cart!.totalQuantity;
      notifyListeners();
    }
  }

  // Quick add to cart with optimistic update
  Future<bool> quickAddToCart(models.Product product, {int quantity = 1}) async {
    // Optimistic update
    final cartItem = CartItem(
      id: 'temp_${DateTime.now().millisecondsSinceEpoch}',
      productId: product.id,
      product: product,
      quantity: quantity,
      price: product.effectivePrice,
      addedAt: DateTime.now(),
    );

    _addItemLocally(cartItem);

    // Try to sync with server
    final success = await addToCart(productId: product.id, quantity: quantity);
    
    if (!success) {
      // Revert optimistic update
      _removeItemLocally(product.id);
    }

    return success;
  }

  // Increment product quantity
  Future<bool> incrementQuantity(String productId) async {
    final currentQuantity = getProductQuantity(productId);
    if (currentQuantity > 0) {
      final cartItem = getCartItemByProduct(productId);
      if (cartItem != null) {
        return await updateQuantity(
          cartItemId: cartItem.id,
          quantity: currentQuantity + 1,
        );
      }
    }
    return false;
  }

  // Decrement product quantity
  Future<bool> decrementQuantity(String productId) async {
    final currentQuantity = getProductQuantity(productId);
    if (currentQuantity > 1) {
      final cartItem = getCartItemByProduct(productId);
      if (cartItem != null) {
        return await updateQuantity(
          cartItemId: cartItem.id,
          quantity: currentQuantity - 1,
        );
      }
    } else if (currentQuantity == 1) {
      final cartItem = getCartItemByProduct(productId);
      if (cartItem != null) {
        return await removeFromCart(cartItem.id);
      }
    }
    return false;
  }

  // Refresh cart data
  Future<void> refresh() async {
    await loadCart();
  }

  // Helper methods
  void _setStatus(CartStatus status) {
    _status = status;
    notifyListeners();
  }

  void _setError(String error) {
    _errorMessage = error;
    _status = CartStatus.error;
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