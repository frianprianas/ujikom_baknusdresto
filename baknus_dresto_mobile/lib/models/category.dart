class CategoryModel {
  final int id;
  final String name;
  final String? icon;
  final int productsCount;

  CategoryModel({
    required this.id,
    required this.name,
    this.icon,
    this.productsCount = 0,
  });

  factory CategoryModel.fromJson(Map<String, dynamic> json) {
    return CategoryModel(
      id: json['id'] as int,
      name: json['name'] as String,
      icon: json['icon'] as String?,
      productsCount: json['products_count'] != null
          ? (json['products_count'] as num).toInt()
          : 0,
    );
  }
}
