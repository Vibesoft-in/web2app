class OrderLineItem {
  final String id;
  final String productId;
  final String name;
  final int quantity;
  final String total;

  const OrderLineItem({
    required this.id,
    required this.productId,
    required this.name,
    required this.quantity,
    required this.total,
  });

  factory OrderLineItem.fromJson(Map<String, dynamic> json) {
    return OrderLineItem(
      id: json['id']?.toString() ?? '',
      productId: json['product_id']?.toString() ?? '',
      name: json['name'] as String? ?? '',
      quantity: (json['quantity'] as num?)?.toInt() ?? 1,
      total: json['total']?.toString() ?? '0.00',
    );
  }
}

class CustomerOrder {
  final String id;
  final String number;
  final String status;
  final String total;
  final String currency;
  final String? createdAt;
  final List<OrderLineItem> items;

  const CustomerOrder({
    required this.id,
    required this.number,
    required this.status,
    required this.total,
    required this.currency,
    this.createdAt,
    this.items = const [],
  });

  factory CustomerOrder.fromJson(Map<String, dynamic> json) {
    final rawItems = json['items'] as List<dynamic>? ?? [];
    return CustomerOrder(
      id: json['id']?.toString() ?? '',
      number: json['number']?.toString() ?? '',
      status: json['status'] as String? ?? 'processing',
      total: json['total']?.toString() ?? '0.00',
      currency: json['currency'] as String? ?? 'INR',
      createdAt: json['created_at'] as String?,
      items: rawItems
          .map((i) => OrderLineItem.fromJson(i as Map<String, dynamic>))
          .toList(),
    );
  }
}
