import 'dart:convert';
import 'dart:io';
import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import '../models/category.dart';
import '../models/product.dart';
import '../models/table.dart';
import '../models/order.dart';
import '../models/user.dart';

class ApiService {
  static final ApiService _instance = ApiService._internal();
  factory ApiService() => _instance;
  ApiService._internal();

  static String get defaultBaseUrl {
    // Alamat VPS Cloud Baknus Dresto
    return 'http://202.10.40.177/api/mobile';
  }

  String baseUrl = defaultBaseUrl;
  UserModel? currentUser;

  void setBaseUrl(String newUrl) {
    baseUrl = newUrl;
  }

  // ==========================================
  // AUTENTIKASI & REGISTRASI OTP PELANGGAN
  // ==========================================

  // 1. Request Registrasi & Kirim OTP Simulasi
  Future<Map<String, dynamic>> requestRegisterOtp({
    required String name,
    required String phone,
    required String address,
    String? addressBenchmark,
  }) async {
    final payload = {
      'name': name,
      'phone': phone,
      'address': address,
      'address_benchmark': addressBenchmark,
    };

    try {
      final res = await http.post(
        Uri.parse('$baseUrl/auth/register-otp'),
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
        body: jsonEncode(payload),
      ).timeout(const Duration(seconds: 5));

      final json = jsonDecode(res.body);
      if (res.statusCode == 200 && json['success'] == true) {
        return {
          'success': true,
          'message': json['message'],
          'phone': json['data']['phone'],
          'otp_simulation': json['data']['otp_simulation'],
        };
      } else {
        return {
          'success': false,
          'message': json['message'] ?? 'Gagal mendaftar',
        };
      }
    } catch (e) {
      debugPrint('requestRegisterOtp offline: $e');
      // Simulasi offline jika backend belum hidup
      return {
        'success': true,
        'message': 'Simulasi OTP terkirim ke $phone',
        'phone': phone,
        'otp_simulation': '1234',
      };
    }
  }

  // 2. Verifikasi OTP & Simpan Password
  Future<Map<String, dynamic>> verifyOtpAndSetPassword({
    required String phone,
    required String otpCode,
    required String password,
  }) async {
    final payload = {
      'phone': phone,
      'otp_code': otpCode,
      'password': password,
    };

    try {
      final res = await http.post(
        Uri.parse('$baseUrl/auth/verify-otp-password'),
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
        body: jsonEncode(payload),
      ).timeout(const Duration(seconds: 5));

      final json = jsonDecode(res.body);
      if (res.statusCode == 200 && json['success'] == true) {
        final user = UserModel.fromJson(json['data']['user']);
        currentUser = user;
        await _saveUserToPrefs(user);
        return {
          'success': true,
          'message': json['message'],
          'user': user,
        };
      } else {
        return {
          'success': false,
          'message': json['message'] ?? 'Verifikasi OTP gagal',
        };
      }
    } catch (e) {
      debugPrint('verifyOtpAndSetPassword offline: $e');
      if (otpCode == '1234' || otpCode.length == 4) {
        final user = UserModel(
          id: 99,
          name: 'Pelanggan Baknus',
          phone: phone,
          address: 'Jl. Merdeka No. 12',
          addressBenchmark: 'Dekat Masjid Al-Ikhlas',
        );
        currentUser = user;
        await _saveUserToPrefs(user);
        return {
          'success': true,
          'message': 'Verifikasi berhasil (Mode Simulasi)',
          'user': user,
        };
      }
      return {'success': false, 'message': 'Kode OTP salah'};
    }
  }

  // 3. Login dengan No HP & Password
  Future<Map<String, dynamic>> login({
    required String phone,
    required String password,
  }) async {
    final payload = {
      'phone': phone,
      'password': password,
    };

    try {
      final res = await http.post(
        Uri.parse('$baseUrl/auth/login'),
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
        body: jsonEncode(payload),
      ).timeout(const Duration(seconds: 5));

      final json = jsonDecode(res.body);
      if (res.statusCode == 200 && json['success'] == true) {
        final user = UserModel.fromJson(json['data']['user']);
        currentUser = user;
        await _saveUserToPrefs(user);
        return {'success': true, 'user': user, 'message': json['message']};
      } else {
        return {'success': false, 'message': json['message'] ?? 'Login gagal'};
      }
    } catch (e) {
      debugPrint('login offline: $e');
      final user = UserModel(
        id: 99,
        name: 'Pelanggan Baknus',
        phone: phone,
        address: 'Jl. Merdeka No. 12',
        addressBenchmark: 'Dekat Masjid Al-Ikhlas',
      );
      currentUser = user;
      await _saveUserToPrefs(user);
      return {'success': true, 'user': user, 'message': 'Login berhasil (Simulasi)'};
    }
  }

  // 4. Update Alamat & Patokan
  Future<bool> updateAddress({
    required String phone,
    required String address,
    String? addressBenchmark,
  }) async {
    try {
      final res = await http.post(
        Uri.parse('$baseUrl/auth/update-address'),
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
        body: jsonEncode({
          'phone': phone,
          'address': address,
          'address_benchmark': addressBenchmark,
        }),
      );
      if (res.statusCode == 200) {
        if (currentUser != null) {
          currentUser = UserModel(
            id: currentUser!.id,
            name: currentUser!.name,
            phone: currentUser!.phone,
            address: address,
            addressBenchmark: addressBenchmark,
            role: currentUser!.role,
          );
          await _saveUserToPrefs(currentUser!);
        }
        return true;
      }
    } catch (e) {
      debugPrint('updateAddress error: $e');
    }
    return false;
  }

  Future<void> _saveUserToPrefs(UserModel user) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString('user_session', jsonEncode(user.toJson()));
    } catch (e) {
      debugPrint('saveUserPrefs: $e');
    }
  }

  Future<UserModel?> loadSavedUser() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final str = prefs.getString('user_session');
      if (str != null) {
        currentUser = UserModel.fromJson(jsonDecode(str));
        return currentUser;
      }
    } catch (e) {
      debugPrint('loadSavedUser: $e');
    }
    return null;
  }

  Future<void> logout() async {
    currentUser = null;
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.remove('user_session');
    } catch (e) {
      debugPrint('logout error: $e');
    }
  }

  // ==========================================
  // KATALOG & MEJA
  // ==========================================

  Future<List<CategoryModel>> getCategories() async {
    try {
      final res = await http.get(Uri.parse('$baseUrl/categories')).timeout(const Duration(seconds: 4));
      if (res.statusCode == 200) {
        final json = jsonDecode(res.body);
        if (json['success'] == true) {
          final list = json['data'] as List;
          return list.map((item) => CategoryModel.fromJson(item)).toList();
        }
      }
    } catch (e) {
      debugPrint('ApiService.getCategories fallback: $e');
    }

    return [
      CategoryModel(id: 1, name: 'Makanan Utama', icon: 'fa-utensils', productsCount: 5),
      CategoryModel(id: 2, name: 'Camilan & Starters', icon: 'fa-cookie-bite', productsCount: 4),
      CategoryModel(id: 3, name: 'Minuman Dingin/Hangat', icon: 'fa-mug-hot', productsCount: 4),
      CategoryModel(id: 4, name: 'Dessert & Penutup', icon: 'fa-ice-cream', productsCount: 2),
    ];
  }

  Future<List<ProductModel>> getProducts({int? categoryId, String? search}) async {
    try {
      final queryParams = <String, String>{};
      if (categoryId != null) queryParams['category_id'] = categoryId.toString();
      if (search != null && search.isNotEmpty) queryParams['search'] = search;

      final uri = Uri.parse('$baseUrl/products').replace(queryParameters: queryParams);
      final res = await http.get(uri).timeout(const Duration(seconds: 4));

      if (res.statusCode == 200) {
        final json = jsonDecode(res.body);
        if (json['success'] == true) {
          final list = json['data'] as List;
          return list.map((item) => ProductModel.fromJson(item)).toList();
        }
      }
    } catch (e) {
      debugPrint('ApiService.getProducts fallback: $e');
    }

    return [
      ProductModel(id: 1, name: 'Nasi Goreng Spesial Baknus', price: 28000, stock: 45, categoryId: 1, categoryName: 'Makanan Utama', iconType: 'utensils'),
      ProductModel(id: 2, name: 'Ayam Bakar Madu Dresto', price: 32000, stock: 30, categoryId: 1, categoryName: 'Makanan Utama', iconType: 'utensils'),
      ProductModel(id: 3, name: 'Mie Goreng Seafood Spesial', price: 27000, stock: 25, categoryId: 1, categoryName: 'Makanan Utama', iconType: 'bowl-rice'),
      ProductModel(id: 4, name: 'Sop Iga Sapi Kuah Gurih', price: 42000, stock: 18, categoryId: 1, categoryName: 'Makanan Utama', iconType: 'bowl-rice'),
      ProductModel(id: 5, name: 'Chicken Wings BBQ Crispy', price: 24000, stock: 35, categoryId: 2, categoryName: 'Camilan & Starters', iconType: 'utensils'),
      ProductModel(id: 6, name: 'Kentang Goreng Keju (French Fries)', price: 18000, stock: 40, categoryId: 2, categoryName: 'Camilan & Starters', iconType: 'bowl-rice'),
      ProductModel(id: 7, name: 'Es Teh Manis Melati', price: 6000, stock: 100, categoryId: 3, categoryName: 'Minuman Dingin/Hangat', iconType: 'mug-hot'),
      ProductModel(id: 8, name: 'Kopi Susu Gula Aren Baknus', price: 18000, stock: 50, categoryId: 3, categoryName: 'Minuman Dingin/Hangat', iconType: 'mug-hot'),
      ProductModel(id: 9, name: 'Jus Alpukat Kocok Cokelat', price: 20000, stock: 20, categoryId: 3, categoryName: 'Minuman Dingin/Hangat', iconType: 'mug-hot'),
    ];
  }

  Future<List<RestaurantTableModel>> getTables() async {
    try {
      final res = await http.get(Uri.parse('$baseUrl/tables')).timeout(const Duration(seconds: 4));
      if (res.statusCode == 200) {
        final json = jsonDecode(res.body);
        if (json['success'] == true) {
          final list = json['data'] as List;
          return list.map((item) => RestaurantTableModel.fromJson(item)).toList();
        }
      }
    } catch (e) {
      debugPrint('ApiService.getTables fallback: $e');
    }

    return [
      RestaurantTableModel(id: 1, tableNumber: 'Meja 01', capacity: 2, status: 'available', floorSection: 'Main Hall'),
      RestaurantTableModel(id: 2, tableNumber: 'Meja 02', capacity: 4, status: 'available', floorSection: 'Main Hall'),
      RestaurantTableModel(id: 3, tableNumber: 'Meja 03', capacity: 4, status: 'occupied', floorSection: 'Main Hall'),
      RestaurantTableModel(id: 4, tableNumber: 'Meja 04', capacity: 6, status: 'available', floorSection: 'Main Hall'),
      RestaurantTableModel(id: 5, tableNumber: 'Meja VIP 01', capacity: 8, status: 'available', floorSection: 'VIP Room'),
      RestaurantTableModel(id: 6, tableNumber: 'Meja Outdoor 01', capacity: 4, status: 'reserved', floorSection: 'Outdoor Garden'),
      RestaurantTableModel(id: 7, tableNumber: 'Meja Outdoor 02', capacity: 4, status: 'available', floorSection: 'Outdoor Garden'),
    ];
  }

  // ==========================================
  // PEMESANAN & PEMBAYARAN MANDIRI
  // ==========================================

  Future<Map<String, dynamic>> createOrder({
    required String orderType,
    int? tableId,
    required String customerName,
    String? customerPhone,
    String? deliveryAddress,
    String? notes,
    required List<CartItem> items,
  }) async {
    final payload = {
      'order_type': orderType,
      'table_id': tableId,
      'customer_name': customerName,
      'customer_phone': customerPhone,
      'delivery_address': deliveryAddress,
      'notes': notes,
      'items': items.map((i) => {
        'product_id': i.product.id,
        'quantity': i.quantity,
        'notes': i.notes,
      }).toList(),
    };

    try {
      final res = await http.post(
        Uri.parse('$baseUrl/orders'),
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
        body: jsonEncode(payload),
      ).timeout(const Duration(seconds: 6));

      final json = jsonDecode(res.body);
      if (res.statusCode == 201 || json['success'] == true) {
        return {
          'success': true,
          'message': json['message'] ?? 'Pesanan berhasil dibuat',
          'order': OrderModel.fromJson(json['data']),
        };
      } else {
        return {
          'success': false,
          'message': json['message'] ?? 'Gagal membuat pesanan',
        };
      }
    } catch (e) {
      debugPrint('ApiService.createOrder offline simulation: $e');
      double subtotal = items.fold(0, (sum, i) => sum + i.subtotal);
      double tax = subtotal * 0.10;
      double total = subtotal + tax;
      final mockOrder = OrderModel(
        id: DateTime.now().millisecondsSinceEpoch % 100000,
        orderNumber: 'MOB-${DateTime.now().year}${DateTime.now().month.toString().padLeft(2, '0')}${DateTime.now().day.toString().padLeft(2, '0')}-MOCK',
        orderType: orderType,
        customerName: customerName,
        customerPhone: customerPhone,
        deliveryAddress: deliveryAddress,
        tableId: tableId,
        tableNumber: tableId != null ? 'Meja $tableId' : null,
        isPaid: false,
        status: 'open',
        deliveryStatus: orderType == 'delivery' ? 'pending' : null,
        subtotal: subtotal,
        taxAmount: tax,
        totalAmount: total,
        notes: notes,
        createdAt: DateTime.now(),
      );

      return {
        'success': true,
        'message': 'Pesanan berhasil dibuat (Simulasi)',
        'order': mockOrder,
      };
    }
  }

  Future<Map<String, dynamic>> payOrder({
    required int orderId,
    required String paymentMethod,
    required double amountPaid,
  }) async {
    final payload = {
      'payment_method': paymentMethod,
      'amount_paid': amountPaid,
    };

    try {
      final res = await http.post(
        Uri.parse('$baseUrl/orders/$orderId/pay'),
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
        body: jsonEncode(payload),
      ).timeout(const Duration(seconds: 6));

      final json = jsonDecode(res.body);
      if (res.statusCode == 200 || json['success'] == true) {
        return {
          'success': true,
          'message': json['message'] ?? 'Pembayaran berhasil dikonfirmasi',
          'order': OrderModel.fromJson(json['data']['order']),
          'table_status': json['data']['table_status'],
        };
      } else {
        return {
          'success': false,
          'message': json['message'] ?? 'Gagal memproses pembayaran',
        };
      }
    } catch (e) {
      debugPrint('ApiService.payOrder offline simulation: $e');
      return {
        'success': true,
        'message': 'Pembayaran berhasil (Simulasi)! Meja telah di-booking & stok terpotong.',
        'table_status': 'reserved',
      };
    }
  }

  Future<OrderModel?> getOrder(int id) async {
    try {
      final res = await http.get(Uri.parse('$baseUrl/orders/$id')).timeout(const Duration(seconds: 4));
      if (res.statusCode == 200) {
        final json = jsonDecode(res.body);
        if (json['success'] == true) {
          return OrderModel.fromJson(json['data']);
        }
      }
    } catch (e) {
      debugPrint('ApiService.getOrder error: $e');
    }
    return null;
  }

  Future<Map<String, dynamic>> advanceDeliverySimulation(int orderId) async {
    try {
      final res = await http.post(
        Uri.parse('$baseUrl/orders/$orderId/simulate-delivery'),
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
      ).timeout(const Duration(seconds: 5));

      final json = jsonDecode(res.body);
      if (res.statusCode == 200 && json['success'] == true) {
        return {
          'success': true,
          'delivery_status': json['data']['delivery_status'],
          'status_label': json['data']['status_label'],
          'description': json['data']['description'],
          'estimated_minutes': json['data']['estimated_minutes'],
        };
      }
    } catch (e) {
      debugPrint('ApiService.advanceDeliverySimulation offline: $e');
    }

    return {
      'success': true,
      'delivery_status': 'on_the_way',
      'status_label': 'Sedang Diantar Driver',
      'description': 'Driver Baknus Express sedang menuju ke lokasi pengantaran Anda.',
      'estimated_minutes': 8,
    };
  }
}
