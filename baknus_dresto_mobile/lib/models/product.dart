class ProductModel {
  final int id;
  final String name;
  final String? code;
  final int? categoryId;
  final double price;
  int stock;
  final bool isAvailable;
  final String? iconType;
  final String? categoryName;

  ProductModel({
    required this.id,
    required this.name,
    this.code,
    this.categoryId,
    required this.price,
    required this.stock,
    this.isAvailable = true,
    this.iconType,
    this.categoryName,
  });

  factory ProductModel.fromJson(Map<String, dynamic> json) {
    return ProductModel(
      id: json['id'] as int,
      name: json['name'] as String,
      code: json['code'] as String?,
      categoryId: json['category_id'] as int?,
      price: double.tryParse(json['price'].toString()) ?? 0.0,
      stock: (json['stock'] as num?)?.toInt() ?? 0,
      isAvailable: json['is_available'] == true || json['is_available'] == 1,
      iconType: json['icon_type'] as String?,
      categoryName: json['category'] != null ? json['category']['name'] : null,
    );
  }
}
