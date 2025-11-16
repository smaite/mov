import 'product.dart';

class CartItem {
  final String id;
  final String productId;
  final Product? product;
  final int quantity;
  final double price;
  final Map<String, dynamic>? selectedVariants;
  final DateTime addedAt;

  CartItem({
    required this.id,
    required this.productId,
    this.product,
    required this.quantity,
    required this.price,
    this.selectedVariants,
    required this.addedAt,
  });

  factory CartItem.fromJson(Map<String, dynamic> json) {
    return CartItem(
      id: json['id']?.toString() ?? '',
      productId: json['product_id']?.toString() ?? '',
      product: json['product'] != null 
          ? Product.fromJson(json['product'] as Map<String, dynamic>)
          : null,
      quantity: json['quantity']?.toInt() ?? 1,
      price: double.tryParse(json['price']?.toString() ?? '0.0') ?? 0.0,
      selectedVariants: json['selected_variants'] as Map<String, dynamic>?,
      addedAt: json['added_at'] != null
          ? DateTime.tryParse(json['added_at'].toString()) ?? DateTime.now()
          : DateTime.now(),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'product_id': productId,
      'product': product?.toJson(),
      'quantity': quantity,
      'price': price,
      'selected_variants': selectedVariants,
      'added_at': addedAt.toIso8601String(),
    };
  }

  // Getters
  double get totalPrice => price * quantity;

  String get formattedTotalPrice => 'Rs. ${totalPrice.toStringAsFixed(2)}';

  String get formattedUnitPrice => 'Rs. ${price.toStringAsFixed(2)}';

  bool get isAvailable => product?.isInStock ?? false;

  String get productName => product?.name ?? 'Unknown Product';

  String get productImage => product?.image ?? '';

  CartItem copyWith({
    String? id,
    String? productId,
    Product? product,
    int? quantity,
    double? price,
    Map<String, dynamic>? selectedVariants,
    DateTime? addedAt,
  }) {
    return CartItem(
      id: id ?? this.id,
      productId: productId ?? this.productId,
      product: product ?? this.product,
      quantity: quantity ?? this.quantity,
      price: price ?? this.price,
      selectedVariants: selectedVariants ?? this.selectedVariants,
      addedAt: addedAt ?? this.addedAt,
    );
  }

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CartItem && runtimeType == other.runtimeType && id == other.id;

  @override
  int get hashCode => id.hashCode;

  @override
  String toString() {
    return 'CartItem{id: $id, productId: $productId, quantity: $quantity, price: $price}';
  }
}

class Cart {
  final String id;
  final String userId;
  final List<CartItem> items;
  final DateTime createdAt;
  final DateTime updatedAt;

  Cart({
    required this.id,
    required this.userId,
    required this.items,
    required this.createdAt,
    required this.updatedAt,
  });

  factory Cart.fromJson(Map<String, dynamic> json) {
    return Cart(
      id: json['id']?.toString() ?? '',
      userId: json['user_id']?.toString() ?? '',
      items: (json['items'] as List<dynamic>?)
          ?.map((item) => CartItem.fromJson(item as Map<String, dynamic>))
          .toList() ?? [],
      createdAt: json['created_at'] != null
          ? DateTime.tryParse(json['created_at'].toString()) ?? DateTime.now()
          : DateTime.now(),
      updatedAt: json['updated_at'] != null
          ? DateTime.tryParse(json['updated_at'].toString()) ?? DateTime.now()
          : DateTime.now(),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'user_id': userId,
      'items': items.map((item) => item.toJson()).toList(),
      'created_at': createdAt.toIso8601String(),
      'updated_at': updatedAt.toIso8601String(),
    };
  }

  // Getters
  int get itemCount => items.length;

  int get totalQuantity => items.fold(0, (sum, item) => sum + item.quantity);

  double get subtotal => items.fold(0.0, (sum, item) => sum + item.totalPrice);

  double get deliveryFee => subtotal > 1000 ? 0.0 : 100.0; // Free delivery above Rs. 1000

  double get total => subtotal + deliveryFee;

  String get formattedSubtotal => 'Rs. ${subtotal.toStringAsFixed(2)}';

  String get formattedDeliveryFee => deliveryFee == 0 
      ? 'Free' 
      : 'Rs. ${deliveryFee.toStringAsFixed(2)}';

  String get formattedTotal => 'Rs. ${total.toStringAsFixed(2)}';

  bool get isEmpty => items.isEmpty;

  bool get isNotEmpty => items.isNotEmpty;

  bool get hasUnavailableItems => items.any((item) => !item.isAvailable);

  List<CartItem> get availableItems => items.where((item) => item.isAvailable).toList();

  List<CartItem> get unavailableItems => items.where((item) => !item.isAvailable).toList();

  // Methods
  CartItem? findItem(String productId) {
    try {
      return items.firstWhere((item) => item.productId == productId);
    } catch (e) {
      return null;
    }
  }

  bool containsProduct(String productId) {
    return items.any((item) => item.productId == productId);
  }

  int getProductQuantity(String productId) {
    final item = findItem(productId);
    return item?.quantity ?? 0;
  }

  Cart addItem(CartItem newItem) {
    final existingItemIndex = items.indexWhere(
      (item) => item.productId == newItem.productId,
    );

    List<CartItem> updatedItems;
    if (existingItemIndex >= 0) {
      // Update existing item quantity
      updatedItems = List.from(items);
      updatedItems[existingItemIndex] = items[existingItemIndex].copyWith(
        quantity: items[existingItemIndex].quantity + newItem.quantity,
      );
    } else {
      // Add new item
      updatedItems = [...items, newItem];
    }

    return copyWith(
      items: updatedItems,
      updatedAt: DateTime.now(),
    );
  }

  Cart updateItemQuantity(String productId, int quantity) {
    if (quantity <= 0) {
      return removeItem(productId);
    }

    final updatedItems = items.map((item) {
      if (item.productId == productId) {
        return item.copyWith(quantity: quantity);
      }
      return item;
    }).toList();

    return copyWith(
      items: updatedItems,
      updatedAt: DateTime.now(),
    );
  }

  Cart removeItem(String productId) {
    final updatedItems = items.where((item) => item.productId != productId).toList();
    
    return copyWith(
      items: updatedItems,
      updatedAt: DateTime.now(),
    );
  }

  Cart clear() {
    return copyWith(
      items: [],
      updatedAt: DateTime.now(),
    );
  }

  Cart copyWith({
    String? id,
    String? userId,
    List<CartItem>? items,
    DateTime? createdAt,
    DateTime? updatedAt,
  }) {
    return Cart(
      id: id ?? this.id,
      userId: userId ?? this.userId,
      items: items ?? this.items,
      createdAt: createdAt ?? this.createdAt,
      updatedAt: updatedAt ?? this.updatedAt,
    );
  }

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is Cart && runtimeType == other.runtimeType && id == other.id;

  @override
  int get hashCode => id.hashCode;

  @override
  String toString() {
    return 'Cart{id: $id, itemCount: $itemCount, total: $total}';
  }
}

class CartSummary {
  final int itemCount;
  final double subtotal;
  final double deliveryFee;
  final double total;

  CartSummary({
    required this.itemCount,
    required this.subtotal,
    required this.deliveryFee,
    required this.total,
  });

  factory CartSummary.fromCart(Cart cart) {
    return CartSummary(
      itemCount: cart.totalQuantity,
      subtotal: cart.subtotal,
      deliveryFee: cart.deliveryFee,
      total: cart.total,
    );
  }

  String get formattedSubtotal => 'Rs. ${subtotal.toStringAsFixed(2)}';
  String get formattedDeliveryFee => deliveryFee == 0 ? 'Free' : 'Rs. ${deliveryFee.toStringAsFixed(2)}';
  String get formattedTotal => 'Rs. ${total.toStringAsFixed(2)}';
}