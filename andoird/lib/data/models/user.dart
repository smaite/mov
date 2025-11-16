class User {
  final String id;
  final String username;
  final String email;
  final String? firstName;
  final String? lastName;
  final String userType;
  final String? profileImage;
  final String? phone;
  final String? address;
  final DateTime? createdAt;
  final DateTime? updatedAt;

  User({
    required this.id,
    required this.username,
    required this.email,
    this.firstName,
    this.lastName,
    required this.userType,
    this.profileImage,
    this.phone,
    this.address,
    this.createdAt,
    this.updatedAt,
  });

  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      id: json['id']?.toString() ?? '',
      username: json['username']?.toString() ?? '',
      email: json['email']?.toString() ?? '',
      firstName: json['first_name']?.toString(),
      lastName: json['last_name']?.toString(),
      userType: json['user_type']?.toString() ?? 'customer',
      profileImage: json['profile_image']?.toString(),
      phone: json['phone']?.toString(),
      address: json['address']?.toString(),
      createdAt: json['created_at'] != null 
          ? DateTime.tryParse(json['created_at'].toString()) 
          : null,
      updatedAt: json['updated_at'] != null 
          ? DateTime.tryParse(json['updated_at'].toString()) 
          : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'username': username,
      'email': email,
      'first_name': firstName,
      'last_name': lastName,
      'user_type': userType,
      'profile_image': profileImage,
      'phone': phone,
      'address': address,
      'created_at': createdAt?.toIso8601String(),
      'updated_at': updatedAt?.toIso8601String(),
    };
  }

  // Getters
  String get fullName {
    if (firstName != null && lastName != null) {
      return '$firstName $lastName';
    } else if (firstName != null) {
      return firstName!;
    } else if (lastName != null) {
      return lastName!;
    }
    return username;
  }

  String get displayName {
    return fullName.isNotEmpty ? fullName : username;
  }

  bool get isCustomer => userType.toLowerCase() == 'customer';
  bool get isVendor => userType.toLowerCase() == 'vendor';
  bool get isAdmin => userType.toLowerCase() == 'admin';

  User copyWith({
    String? id,
    String? username,
    String? email,
    String? firstName,
    String? lastName,
    String? userType,
    String? profileImage,
    String? phone,
    String? address,
    DateTime? createdAt,
    DateTime? updatedAt,
  }) {
    return User(
      id: id ?? this.id,
      username: username ?? this.username,
      email: email ?? this.email,
      firstName: firstName ?? this.firstName,
      lastName: lastName ?? this.lastName,
      userType: userType ?? this.userType,
      profileImage: profileImage ?? this.profileImage,
      phone: phone ?? this.phone,
      address: address ?? this.address,
      createdAt: createdAt ?? this.createdAt,
      updatedAt: updatedAt ?? this.updatedAt,
    );
  }

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is User && runtimeType == other.runtimeType && id == other.id;

  @override
  int get hashCode => id.hashCode;

  @override
  String toString() {
    return 'User{id: $id, username: $username, email: $email, userType: $userType}';
  }
}

class UserProfile {
  final User user;
  final List<Address> addresses;
  final List<PaymentMethod> paymentMethods;
  final List<Order> recentOrders;
  final int wishlistCount;
  final int cartCount;

  UserProfile({
    required this.user,
    required this.addresses,
    required this.paymentMethods,
    required this.recentOrders,
    required this.wishlistCount,
    required this.cartCount,
  });

  factory UserProfile.fromJson(Map<String, dynamic> json) {
    return UserProfile(
      user: User.fromJson(json['user'] ?? {}),
      addresses: (json['addresses'] as List<dynamic>?)
          ?.map((item) => Address.fromJson(item as Map<String, dynamic>))
          .toList() ?? [],
      paymentMethods: (json['payment_methods'] as List<dynamic>?)
          ?.map((item) => PaymentMethod.fromJson(item as Map<String, dynamic>))
          .toList() ?? [],
      recentOrders: (json['recent_orders'] as List<dynamic>?)
          ?.map((item) => Order.fromJson(item as Map<String, dynamic>))
          .toList() ?? [],
      wishlistCount: json['wishlist_count']?.toInt() ?? 0,
      cartCount: json['cart_count']?.toInt() ?? 0,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'user': user.toJson(),
      'addresses': addresses.map((item) => item.toJson()).toList(),
      'payment_methods': paymentMethods.map((item) => item.toJson()).toList(),
      'recent_orders': recentOrders.map((item) => item.toJson()).toList(),
      'wishlist_count': wishlistCount,
      'cart_count': cartCount,
    };
  }
}

class Address {
  final String id;
  final String type;
  final String street;
  final String city;
  final String state;
  final String postalCode;
  final String country;
  final bool isDefault;
  final DateTime? createdAt;

  Address({
    required this.id,
    required this.type,
    required this.street,
    required this.city,
    required this.state,
    required this.postalCode,
    required this.country,
    required this.isDefault,
    required this.createdAt,
  });

  factory Address.fromJson(Map<String, dynamic> json) {
    return Address(
      id: json['id']?.toString() ?? '',
      type: json['type']?.toString() ?? 'home',
      street: json['street']?.toString() ?? '',
      city: json['city']?.toString() ?? '',
      state: json['state']?.toString() ?? '',
      postalCode: json['postal_code']?.toString() ?? '',
      country: json['country']?.toString() ?? '',
      isDefault: json['is_default']?.toString() == '1' || json['is_default'] == true,
      createdAt: json['created_at'] != null 
          ? DateTime.tryParse(json['created_at'].toString()) 
          : DateTime.now(),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'type': type,
      'street': street,
      'city': city,
      'state': state,
      'postal_code': postalCode,
      'country': country,
      'is_default': isDefault,
      'created_at': createdAt?.toIso8601String(),
    };
  }

  String get fullAddress {
    return '$street, $city, $state $postalCode, $country';
  }

  Address copyWith({
    String? id,
    String? type,
    String? street,
    String? city,
    String? state,
    String? postalCode,
    String? country,
    bool? isDefault,
    DateTime? createdAt,
  }) {
    return Address(
      id: id ?? this.id,
      type: type ?? this.type,
      street: street ?? this.street,
      city: city ?? this.city,
      state: state ?? this.state,
      postalCode: postalCode ?? this.postalCode,
      country: country ?? this.country,
      isDefault: isDefault ?? this.isDefault,
      createdAt: createdAt ?? this.createdAt,
    );
  }
}

class PaymentMethod {
  final String id;
  final String type;
  final String last4;
  final String brand;
  final bool isDefault;
  final DateTime? createdAt;

  PaymentMethod({
    required this.id,
    required this.type,
    required this.last4,
    required this.brand,
    required this.isDefault,
    required this.createdAt,
  });

  factory PaymentMethod.fromJson(Map<String, dynamic> json) {
    return PaymentMethod(
      id: json['id']?.toString() ?? '',
      type: json['type']?.toString() ?? 'card',
      last4: json['last4']?.toString() ?? '',
      brand: json['brand']?.toString() ?? '',
      isDefault: json['is_default']?.toString() == '1' || json['is_default'] == true,
      createdAt: json['created_at'] != null
          ? DateTime.tryParse(json['created_at'].toString())
          : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'type': type,
      'last4': last4,
      'brand': brand,
      'is_default': isDefault,
      'created_at': createdAt?.toIso8601String(),
    };
  }
}

// Placeholder Order class - will be implemented in order.dart
class Order {
  final String id;
  final double total;
  final String status;
  final DateTime createdAt;
  final List<OrderItem> items;

  Order({
    required this.id,
    required this.total,
    required this.status,
    required this.createdAt,
    required this.items,
  });

  factory Order.fromJson(Map<String, dynamic> json) {
    return Order(
      id: json['id']?.toString() ?? '',
      total: double.tryParse(json['total']?.toString() ?? '0.0') ?? 0.0,
      status: json['status']?.toString() ?? 'pending',
      createdAt: json['created_at'] != null
          ? DateTime.tryParse(json['created_at'].toString()) ?? DateTime.now()
          : DateTime.now(),
      items: (json['items'] as List<dynamic>?)
          ?.map((item) => OrderItem.fromJson(item as Map<String, dynamic>))
          .toList() ?? [],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'total': total,
      'status': status,
      'created_at': createdAt.toIso8601String(),
      'items': items.map((item) => item.toJson()).toList(),
    };
  }
}

class OrderItem {
  final String productName;
  final int quantity;
  final double price;

  OrderItem({
    required this.productName,
    required this.quantity,
    required this.price,
  });

  factory OrderItem.fromJson(Map<String, dynamic> json) {
    return OrderItem(
      productName: json['product_name']?.toString() ?? '',
      quantity: json['quantity']?.toInt() ?? 1,
      price: double.tryParse(json['price']?.toString() ?? '0.0') ?? 0.0,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'product_name': productName,
      'quantity': quantity,
      'price': price,
    };
  }
}