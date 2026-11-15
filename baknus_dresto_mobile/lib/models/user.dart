class UserModel {
  final int id;
  final String name;
  final String phone;
  final String? address;
  final String? addressBenchmark; // Patokan jalan
  final String role;

  UserModel({
    required this.id,
    required this.name,
    required this.phone,
    this.address,
    this.addressBenchmark,
    this.role = 'customer',
  });

  factory UserModel.fromJson(Map<String, dynamic> json) {
    return UserModel(
      id: json['id'] as int? ?? 0,
      name: (json['name'] as String?) ?? 'Pelanggan',
      phone: (json['phone'] as String?) ?? '',
      address: json['address'] as String?,
      addressBenchmark: json['address_benchmark'] as String?,
      role: (json['role'] as String?) ?? 'customer',
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'phone': phone,
      'address': address,
      'address_benchmark': addressBenchmark,
      'role': role,
    };
  }
}
