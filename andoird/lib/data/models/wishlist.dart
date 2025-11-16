import 'product.dart';

class WishlistItem {
  final String id;
  final String userId;
  final String productId;
  final Product? product;
  final DateTime addedAt;

  WishlistItem({
    required this.id,
    required this.userId,
    required this.productId,
    this.product,
    required this.addedAt,
  });

  factory WishlistItem.fromJson(Map<String, dynamic> json) {
    return WishlistItem(
      id: json['id']?.toString() ?? '',
      userId: json['user_id']?.toString() ?? '',
      productId: json['product_id']?.toString() ?? '',
      product: json['product'] != null 
          ? Product.fromJson(json['product'] as Map<String, dynamic>)
          : null,
      addedAt: json['added_at'] != null
          ? DateTime.tryParse(json['added_at'].toString()) ?? DateTime.now()
          : DateTime.now(),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'user_id': userId,
      'product_id': productId,
      'product': product?.toJson(),
      'added_at': addedAt.toIso8601String(),
    };
  }

  // Getters
  String get productName => product?.name ?? 'Unknown Product';
  String get productImage => product?.image ?? '';
  double get productPrice => product?.effectivePrice ?? 0.0;
  String get formattedPrice => 'Rs. ${productPrice.toStringAsFixed(2)}';
  bool get isAvailable => product?.isInStock ?? false;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WishlistItem && runtimeType == other.runtimeType && id == other.id;

  @override
  int get hashCode => id.hashCode;

  @override
  String toString() {
    return 'WishlistItem{id: $id, productId: $productId, addedAt: $addedAt}';
  }
}

class Wishlist {
  final String id;
  final String userId;
  final List<WishlistItem> items;
  final DateTime createdAt;
  final DateTime updatedAt;

  Wishlist({
    required this.id,
    required this.userId,
    required this.items,
    required this.createdAt,
    required this.updatedAt,
  });

  factory Wishlist.fromJson(Map<String, dynamic> json) {
    return Wishlist(
      id: json['id']?.toString() ?? '',
      userId: json['user_id']?.toString() ?? '',
      items: (json['items'] as List<dynamic>?)
          ?.map((item) => WishlistItem.fromJson(item as Map<String, dynamic>))
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
  bool get isEmpty => items.isEmpty;
  bool get isNotEmpty => items.isNotEmpty;

  List<WishlistItem> get availableItems => 
      items.where((item) => item.isAvailable).toList();

  List<WishlistItem> get unavailableItems => 
      items.where((item) => !item.isAvailable).toList();

  // Methods
  WishlistItem? findItem(String productId) {
    try {
      return items.firstWhere((item) => item.productId == productId);
    } catch (e) {
      return null;
    }
  }

  bool containsProduct(String productId) {
    return items.any((item) => item.productId == productId);
  }

  Wishlist addItem(WishlistItem newItem) {
    // Check if item already exists
    if (containsProduct(newItem.productId)) {
      return this; // Don't add duplicate
    }

    final updatedItems = [...items, newItem];
    return copyWith(
      items: updatedItems,
      updatedAt: DateTime.now(),
    );
  }

  Wishlist removeItem(String productId) {
    final updatedItems = items.where((item) => item.productId != productId).toList();
    
    return copyWith(
      items: updatedItems,
      updatedAt: DateTime.now(),
    );
  }

  Wishlist toggleItem(WishlistItem item) {
    if (containsProduct(item.productId)) {
      return removeItem(item.productId);
    } else {
      return addItem(item);
    }
  }

  Wishlist clear() {
    return copyWith(
      items: [],
      updatedAt: DateTime.now(),
    );
  }

  Wishlist copyWith({
    String? id,
    String? userId,
    List<WishlistItem>? items,
    DateTime? createdAt,
    DateTime? updatedAt,
  }) {
    return Wishlist(
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
      other is Wishlist && runtimeType == other.runtimeType && id == other.id;

  @override
  int get hashCode => id.hashCode;

  @override
  String toString() {
    return 'Wishlist{id: $id, itemCount: $itemCount}';
  }
}