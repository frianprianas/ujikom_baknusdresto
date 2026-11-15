class RestaurantTableModel {
  final int id;
  final String tableNumber;
  final int capacity;
  String status; // available, occupied, reserved
  final String floorSection;

  RestaurantTableModel({
    required this.id,
    required this.tableNumber,
    required this.capacity,
    required this.status,
    required this.floorSection,
  });

  bool get isAvailable => status == 'available';
  bool get isReserved => status == 'reserved';
  bool get isOccupied => status == 'occupied';

  factory RestaurantTableModel.fromJson(Map<String, dynamic> json) {
    return RestaurantTableModel(
      id: json['id'] as int,
      tableNumber: json['table_number'] as String,
      capacity: (json['capacity'] as num?)?.toInt() ?? 4,
      status: (json['status'] as String?) ?? 'available',
      floorSection: (json['floor_section'] as String?) ?? 'Main Hall',
    );
  }
}
