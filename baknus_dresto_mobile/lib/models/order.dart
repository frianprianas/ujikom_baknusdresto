import 'product.dart';

class CartItem {
  final ProductModel product;
  int quantity;
  String? notes;

  CartItem({
    required this.product,
    this.quantity = 1,
    this.notes,
  });

  double get subtotal => product.price * quantity;
}

class OrderModel {
  final int id;
  final String orderNumber;
  final String orderType; // dine-in, delivery, takeaway
  final String customerName;
  final String? customerPhone;
  final String? deliveryAddress;
  final int? tableId;
  final String? tableNumber;
  bool isPaid;
  String status; // open, completed, held, cancelled
  String? deliveryStatus; // pending, preparing, on_the_way, delivered
  final double subtotal;
  final double taxAmount;
  final double totalAmount;
  final String? notes;
  final DateTime createdAt;

  OrderModel({
    required this.id,
    required this.orderNumber,
    required this.orderType,
    required this.customerName,
    this.customerPhone,
    this.deliveryAddress,
    this.tableId,
    this.tableNumber,
    required this.isPaid,
    required this.status,
    this.deliveryStatus,
    required this.subtotal,
    required this.taxAmount,
    required this.totalAmount,
    this.notes,
    required this.createdAt,
  });

  factory OrderModel.fromJson(Map<String, dynamic> json) {
    return OrderModel(
      id: json['id'] as int,
      orderNumber: json['order_number'] as String,
      orderType: (json['order_type'] as String?) ?? 'dine-in',
      customerName: (json['customer_name'] as String?) ?? 'Pelanggan',
      customerPhone: json['customer_phone'] as String?,
      deliveryAddress: json['delivery_address'] as String?,
      tableId: json['table_id'] as int?,
      tableNumber: json['table'] != null ? json['table']['table_number'] : null,
      isPaid: json['is_paid'] == true || json['is_paid'] == 1,
      status: (json['status'] as String?) ?? 'open',
      deliveryStatus: json['delivery_status'] as String?,
      subtotal: double.tryParse(json['subtotal'].toString()) ?? 0.0,
      taxAmount: double.tryParse(json['tax_amount'].toString()) ?? 0.0,
      totalAmount: double.tryParse(json['total_amount'].toString()) ?? 0.0,
      notes: json['notes'] as String?,
      createdAt: json['created_at'] != null
          ? DateTime.tryParse(json['created_at'].toString()) ?? DateTime.now()
          : DateTime.now(),
    );
  }
}
