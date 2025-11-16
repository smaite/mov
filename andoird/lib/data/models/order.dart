import 'product.dart';
import 'user.dart';

class OrderItem {
  final String id;
  final String productId;
  final Product? product;
  final int quantity;
  final double price;
  final double totalPrice;
  final Map<String, dynamic>? selectedVariants;

  OrderItem({
    required this.id,
    required this.productId,
    this.product,
    required this.quantity,
    required this.price,
    required this.totalPrice,
    this.selectedVariants,
  });

  factory OrderItem.fromJson(Map<String, dynamic> json) {
    return OrderItem(
      id: json['id']?.toString() ?? '',
      productId: json['product_id']?.toString() ?? '',
      product: json['product'] != null 
          ? Product.fromJson(json['product'] as Map<String, dynamic>)
          : null,
      quantity: json['quantity']?.toInt() ?? 1,
      price: double.tryParse(json['price']?.toString() ?? '0.0') ?? 0.0,
      totalPrice: double.tryParse(json['total_price']?.toString() ?? '0.0') ?? 0.0,
      selectedVariants: json['selected_variants'] as Map<String, dynamic>?,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'product_id': productId,
      'product': product?.toJson(),
      'quantity': quantity,
      'price': price,
      'total_price': totalPrice,
      'selected_variants': selectedVariants,
    };
  }

  String get formattedPrice => 'Rs. ${price.toStringAsFixed(2)}';
  String get formattedTotalPrice => 'Rs. ${totalPrice.toStringAsFixed(2)}';
  String get productName => product?.name ?? 'Unknown Product';
  String get productImage => product?.image ?? '';

  @override
  String toString() {
    return 'OrderItem{id: $id, productId: $productId, quantity: $quantity, totalPrice: $totalPrice}';
  }
}

enum OrderStatus {
  pending,
  confirmed,
  processing,
  shipped,
  outForDelivery,
  delivered,
  cancelled,
  refunded,
}

extension OrderStatusExtension on OrderStatus {
  String get displayName {
    switch (this) {
      case OrderStatus.pending:
        return 'Pending';
      case OrderStatus.confirmed:
        return 'Confirmed';
      case OrderStatus.processing:
        return 'Processing';
      case OrderStatus.shipped:
        return 'Shipped';
      case OrderStatus.outForDelivery:
        return 'Out for Delivery';
      case OrderStatus.delivered:
        return 'Delivered';
      case OrderStatus.cancelled:
        return 'Cancelled';
      case OrderStatus.refunded:
        return 'Refunded';
    }
  }

  String get value {
    switch (this) {
      case OrderStatus.pending:
        return 'pending';
      case OrderStatus.confirmed:
        return 'confirmed';
      case OrderStatus.processing:
        return 'processing';
      case OrderStatus.shipped:
        return 'shipped';
      case OrderStatus.outForDelivery:
        return 'out_for_delivery';
      case OrderStatus.delivered:
        return 'delivered';
      case OrderStatus.cancelled:
        return 'cancelled';
      case OrderStatus.refunded:
        return 'refunded';
    }
  }

  static OrderStatus fromString(String status) {
    switch (status.toLowerCase()) {
      case 'pending':
        return OrderStatus.pending;
      case 'confirmed':
        return OrderStatus.confirmed;
      case 'processing':
        return OrderStatus.processing;
      case 'shipped':
        return OrderStatus.shipped;
      case 'out_for_delivery':
        return OrderStatus.outForDelivery;
      case 'delivered':
        return OrderStatus.delivered;
      case 'cancelled':
        return OrderStatus.cancelled;
      case 'refunded':
        return OrderStatus.refunded;
      default:
        return OrderStatus.pending;
    }
  }
}

class Order {
  final String id;
  final String userId;
  final List<OrderItem> items;
  final double subtotal;
  final double deliveryFee;
  final double total;
  final OrderStatus status;
  final Address deliveryAddress;
  final String paymentMethod;
  final String? notes;
  final String? trackingNumber;
  final DateTime createdAt;
  final DateTime updatedAt;
  final DateTime? deliveredAt;

  Order({
    required this.id,
    required this.userId,
    required this.items,
    required this.subtotal,
    required this.deliveryFee,
    required this.total,
    required this.status,
    required this.deliveryAddress,
    required this.paymentMethod,
    this.notes,
    this.trackingNumber,
    required this.createdAt,
    required this.updatedAt,
    this.deliveredAt,
  });

  factory Order.fromJson(Map<String, dynamic> json) {
    return Order(
      id: json['id']?.toString() ?? '',
      userId: json['user_id']?.toString() ?? '',
      items: (json['items'] as List<dynamic>?)
          ?.map((item) => OrderItem.fromJson(item as Map<String, dynamic>))
          .toList() ?? [],
      subtotal: double.tryParse(json['subtotal']?.toString() ?? '0.0') ?? 0.0,
      deliveryFee: double.tryParse(json['delivery_fee']?.toString() ?? '0.0') ?? 0.0,
      total: double.tryParse(json['total']?.toString() ?? '0.0') ?? 0.0,
      status: OrderStatusExtension.fromString(json['status']?.toString() ?? 'pending'),
      deliveryAddress: Address.fromJson(json['delivery_address'] as Map<String, dynamic>),
      paymentMethod: json['payment_method']?.toString() ?? 'cod',
      notes: json['notes']?.toString(),
      trackingNumber: json['tracking_number']?.toString(),
      createdAt: json['created_at'] != null
          ? DateTime.tryParse(json['created_at'].toString()) ?? DateTime.now()
          : DateTime.now(),
      updatedAt: json['updated_at'] != null
          ? DateTime.tryParse(json['updated_at'].toString()) ?? DateTime.now()
          : DateTime.now(),
      deliveredAt: json['delivered_at'] != null
          ? DateTime.tryParse(json['delivered_at'].toString())
          : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'user_id': userId,
      'items': items.map((item) => item.toJson()).toList(),
      'subtotal': subtotal,
      'delivery_fee': deliveryFee,
      'total': total,
      'status': status.value,
      'delivery_address': deliveryAddress.toJson(),
      'payment_method': paymentMethod,
      'notes': notes,
      'tracking_number': trackingNumber,
      'created_at': createdAt.toIso8601String(),
      'updated_at': updatedAt.toIso8601String(),
      'delivered_at': deliveredAt?.toIso8601String(),
    };
  }

  // Getters
  int get itemCount => items.length;
  int get totalQuantity => items.fold(0, (sum, item) => sum + item.quantity);
  String get formattedSubtotal => 'Rs. ${subtotal.toStringAsFixed(2)}';
  String get formattedDeliveryFee => deliveryFee == 0 ? 'Free' : 'Rs. ${deliveryFee.toStringAsFixed(2)}';
  String get formattedTotal => 'Rs. ${total.toStringAsFixed(2)}';
  String get statusDisplayName => status.displayName;
  String get formattedCreatedAt => '${createdAt.day}/${createdAt.month}/${createdAt.year}';
  
  bool get canBeCancelled => status == OrderStatus.pending || status == OrderStatus.confirmed;
  bool get isDelivered => status == OrderStatus.delivered;
  bool get isCancelled => status == OrderStatus.cancelled;
  bool get isActive => !isCancelled && !isDelivered;

  Order copyWith({
    String? id,
    String? userId,
    List<OrderItem>? items,
    double? subtotal,
    double? deliveryFee,
    double? total,
    OrderStatus? status,
    Address? deliveryAddress,
    String? paymentMethod,
    String? notes,
    String? trackingNumber,
    DateTime? createdAt,
    DateTime? updatedAt,
    DateTime? deliveredAt,
  }) {
    return Order(
      id: id ?? this.id,
      userId: userId ?? this.userId,
      items: items ?? this.items,
      subtotal: subtotal ?? this.subtotal,
      deliveryFee: deliveryFee ?? this.deliveryFee,
      total: total ?? this.total,
      status: status ?? this.status,
      deliveryAddress: deliveryAddress ?? this.deliveryAddress,
      paymentMethod: paymentMethod ?? this.paymentMethod,
      notes: notes ?? this.notes,
      trackingNumber: trackingNumber ?? this.trackingNumber,
      createdAt: createdAt ?? this.createdAt,
      updatedAt: updatedAt ?? this.updatedAt,
      deliveredAt: deliveredAt ?? this.deliveredAt,
    );
  }

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is Order && runtimeType == other.runtimeType && id == other.id;

  @override
  int get hashCode => id.hashCode;

  @override
  String toString() {
    return 'Order{id: $id, status: ${status.displayName}, total: $total, itemCount: $itemCount}';
  }
}

class OrderSummary {
  final String id;
  final double total;
  final OrderStatus status;
  final int itemCount;
  final DateTime createdAt;

  OrderSummary({
    required this.id,
    required this.total,
    required this.status,
    required this.itemCount,
    required this.createdAt,
  });

  factory OrderSummary.fromOrder(Order order) {
    return OrderSummary(
      id: order.id,
      total: order.total,
      status: order.status,
      itemCount: order.itemCount,
      createdAt: order.createdAt,
    );
  }

  String get formattedTotal => 'Rs. ${total.toStringAsFixed(2)}';
  String get statusDisplayName => status.displayName;
  String get formattedCreatedAt => '${createdAt.day}/${createdAt.month}/${createdAt.year}';
}