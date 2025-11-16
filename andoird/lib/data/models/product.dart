class Product {
  final String id;
  final String name;
  final String description;
  final double price;
  final double? salePrice;
  final String image;
  final List<String> images;
  final String category;
  final String? brand;
  final int stockQuantity;
  final double rating;
  final int reviewCount;
  final String vendorId;
  final String vendorName;
  final Map<String, dynamic>? attributes;
  final bool isActive;
  final bool isFeatured;
  final DateTime createdAt;
  final DateTime updatedAt;

  Product({
    required this.id,
    required this.name,
    required this.description,
    required this.price,
    this.salePrice,
    required this.image,
    required this.images,
    required this.category,
    this.brand,
    required this.stockQuantity,
    required this.rating,
    required this.reviewCount,
    required this.vendorId,
    required this.vendorName,
    this.attributes,
    required this.isActive,
    required this.isFeatured,
    required this.createdAt,
    required this.updatedAt,
  });

  factory Product.fromJson(Map<String, dynamic> json) {
    return Product(
      id: json['id']?.toString() ?? '',
      name: json['name']?.toString() ?? '',
      description: json['description']?.toString() ?? '',
      price: double.tryParse(json['price']?.toString() ?? '0.0') ?? 0.0,
      salePrice: json['sale_price'] != null 
          ? double.tryParse(json['sale_price'].toString()) 
          : null,
      image: json['image']?.toString() ?? '',
      images: json['images'] is List 
          ? (json['images'] as List).map((e) => e.toString()).toList()
          : [json['image']?.toString() ?? ''],
      category: json['category']?.toString() ?? '',
      brand: json['brand']?.toString(),
      stockQuantity: json['stock_quantity']?.toInt() ?? 0,
      rating: double.tryParse(json['rating']?.toString() ?? '0.0') ?? 0.0,
      reviewCount: json['review_count']?.toInt() ?? 0,
      vendorId: json['vendor_id']?.toString() ?? '',
      vendorName: json['vendor_name']?.toString() ?? '',
      attributes: json['attributes'] as Map<String, dynamic>?,
      isActive: json['is_active']?.toString() == '1' || json['is_active'] == true,
      isFeatured: json['is_featured']?.toString() == '1' || json['is_featured'] == true,
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
      'name': name,
      'description': description,
      'price': price,
      'sale_price': salePrice,
      'image': image,
      'images': images,
      'category': category,
      'brand': brand,
      'stock_quantity': stockQuantity,
      'rating': rating,
      'review_count': reviewCount,
      'vendor_id': vendorId,
      'vendor_name': vendorName,
      'attributes': attributes,
      'is_active': isActive,
      'is_featured': isFeatured,
      'created_at': createdAt.toIso8601String(),
      'updated_at': updatedAt.toIso8601String(),
    };
  }

  // Getters
  String get displayPrice {
    if (salePrice != null && salePrice! < price) {
      return 'Rs. ${salePrice!.toStringAsFixed(2)}';
    }
    return 'Rs. ${price.toStringAsFixed(2)}';
  }

  String? get originalPrice {
    if (salePrice != null && salePrice! < price) {
      return 'Rs. ${price.toStringAsFixed(2)}';
    }
    return null;
  }

  double get effectivePrice => salePrice ?? price;

  bool get isOnSale => salePrice != null && salePrice! < price;

  bool get isInStock => stockQuantity > 0;

  bool get isOutOfStock => stockQuantity <= 0;

  String get stockStatus {
    if (stockQuantity > 10) return 'In Stock';
    if (stockQuantity > 0) return 'Low Stock';
    return 'Out of Stock';
  }

  double get discountPercentage {
    if (!isOnSale) return 0.0;
    return ((price - salePrice!) / price) * 100;
  }

  String get formattedRating => rating.toStringAsFixed(1);

  Product copyWith({
    String? id,
    String? name,
    String? description,
    double? price,
    double? salePrice,
    String? image,
    List<String>? images,
    String? category,
    String? brand,
    int? stockQuantity,
    double? rating,
    int? reviewCount,
    String? vendorId,
    String? vendorName,
    Map<String, dynamic>? attributes,
    bool? isActive,
    bool? isFeatured,
    DateTime? createdAt,
    DateTime? updatedAt,
  }) {
    return Product(
      id: id ?? this.id,
      name: name ?? this.name,
      description: description ?? this.description,
      price: price ?? this.price,
      salePrice: salePrice ?? this.salePrice,
      image: image ?? this.image,
      images: images ?? this.images,
      category: category ?? this.category,
      brand: brand ?? this.brand,
      stockQuantity: stockQuantity ?? this.stockQuantity,
      rating: rating ?? this.rating,
      reviewCount: reviewCount ?? this.reviewCount,
      vendorId: vendorId ?? this.vendorId,
      vendorName: vendorName ?? this.vendorName,
      attributes: attributes ?? this.attributes,
      isActive: isActive ?? this.isActive,
      isFeatured: isFeatured ?? this.isFeatured,
      createdAt: createdAt ?? this.createdAt,
      updatedAt: updatedAt ?? this.updatedAt,
    );
  }

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is Product && runtimeType == other.runtimeType && id == other.id;

  @override
  int get hashCode => id.hashCode;

  @override
  String toString() {
    return 'Product{id: $id, name: $name, price: $price, category: $category}';
  }
}

class Category {
  final String id;
  final String name;
  final String? description;
  final String? icon;
  final String? image;
  final String? parentId;
  final bool isActive;
  final int sortOrder;
  final DateTime createdAt;

  Category({
    required this.id,
    required this.name,
    this.description,
    this.icon,
    this.image,
    this.parentId,
    required this.isActive,
    required this.sortOrder,
    required this.createdAt,
  });

  factory Category.fromJson(Map<String, dynamic> json) {
    return Category(
      id: json['id']?.toString() ?? '',
      name: json['name']?.toString() ?? '',
      description: json['description']?.toString(),
      icon: json['icon']?.toString(),
      image: json['image']?.toString(),
      parentId: json['parent_id']?.toString(),
      isActive: json['is_active']?.toString() == '1' || json['is_active'] == true,
      sortOrder: json['sort_order']?.toInt() ?? 0,
      createdAt: json['created_at'] != null
          ? DateTime.tryParse(json['created_at'].toString()) ?? DateTime.now()
          : DateTime.now(),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'description': description,
      'icon': icon,
      'image': image,
      'parent_id': parentId,
      'is_active': isActive,
      'sort_order': sortOrder,
      'created_at': createdAt.toIso8601String(),
    };
  }

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is Category && runtimeType == other.runtimeType && id == other.id;

  @override
  int get hashCode => id.hashCode;

  @override
  String toString() => 'Category{id: $id, name: $name}';
}