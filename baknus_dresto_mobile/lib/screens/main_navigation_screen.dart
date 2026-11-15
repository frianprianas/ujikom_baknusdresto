import 'package:flutter/material.dart';
import '../models/order.dart';
import '../models/table.dart';
import '../theme/app_colors.dart';
import 'cart_screen.dart';
import 'home_screen.dart';
import 'profile_screen.dart';
import 'table_picker_screen.dart';

class MainNavigationScreen extends StatefulWidget {
  const MainNavigationScreen({super.key});

  @override
  State<MainNavigationScreen> createState() => _MainNavigationScreenState();
}

class _MainNavigationScreenState extends State<MainNavigationScreen> {
  int _currentIndex = 0;
  final List<CartItem> _sharedCart = [];
  RestaurantTableModel? _selectedTable;
  String _orderType = 'dine-in';

  @override
  Widget build(BuildContext context) {
    final cartItemCount = _sharedCart.fold(0, (sum, i) => sum + i.quantity);

    final screens = [
      // TAB 0: BERANDA / MENU
      HomeScreen(
        sharedCart: _sharedCart,
        selectedTable: _selectedTable,
        orderType: _orderType,
        onOrderTypeChanged: (type) => setState(() => _orderType = type),
        onTableSelected: (tbl) => setState(() => _selectedTable = tbl),
        onCartUpdated: () => setState(() {}),
        onNavigateToCart: () => setState(() => _currentIndex = 2),
      ),

      // TAB 1: DENAH MEJA RESTORAN
      TablePickerScreen(
        selectedTable: _selectedTable,
        isTabScreen: true,
        onTablePicked: (table) {
          setState(() {
            _selectedTable = table;
            _orderType = 'dine-in';
            _currentIndex = 0; // kembali ke menu setelah pilih meja
          });
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text('${table.tableNumber} dipilih untuk Self-Order Dine-In.'),
              backgroundColor: AppColors.primary,
              duration: const Duration(seconds: 2),
            ),
          );
        },
      ),

      // TAB 2: KERANJANG
      CartScreen(
        cart: _sharedCart,
        initialOrderType: _orderType,
        initialTable: _selectedTable,
        isTabScreen: true,
        onCartUpdated: () => setState(() {}),
      ),

      // TAB 3: PROFIL & REGISTRASI OTP
      ProfileScreen(
        onUserLoggedIn: () => setState(() {}),
      ),
    ];

    return Scaffold(
      body: IndexedStack(
        index: _currentIndex,
        children: screens,
      ),
      bottomNavigationBar: Container(
        decoration: BoxDecoration(
          color: Colors.white,
          border: const Border(top: BorderSide(color: AppColors.border, width: 0.8)),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.05),
              blurRadius: 10,
              offset: const Offset(0, -2),
            ),
          ],
        ),
        child: BottomNavigationBar(
          currentIndex: _currentIndex,
          onTap: (index) => setState(() => _currentIndex = index),
          backgroundColor: Colors.white,
          type: BottomNavigationBarType.fixed,
          selectedItemColor: AppColors.primary,
          unselectedItemColor: AppColors.textMuted,
          selectedFontSize: 11,
          unselectedFontSize: 11,
          selectedLabelStyle: const TextStyle(fontWeight: FontWeight.bold),
          elevation: 0,
          items: [
            const BottomNavigationBarItem(
              icon: Icon(Icons.restaurant_menu),
              activeIcon: Icon(Icons.restaurant_menu, color: AppColors.primary),
              label: 'Menu',
            ),
            const BottomNavigationBarItem(
              icon: Icon(Icons.table_restaurant_outlined),
              activeIcon: Icon(Icons.table_restaurant, color: AppColors.primary),
              label: 'Meja Resto',
            ),
            BottomNavigationBarItem(
              icon: Badge(
                isLabelVisible: cartItemCount > 0,
                label: Text('$cartItemCount', style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold)),
                backgroundColor: AppColors.accent,
                textColor: Colors.black,
                child: const Icon(Icons.shopping_bag_outlined),
              ),
              activeIcon: Badge(
                isLabelVisible: cartItemCount > 0,
                label: Text('$cartItemCount', style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold)),
                backgroundColor: AppColors.accent,
                textColor: Colors.black,
                child: const Icon(Icons.shopping_bag, color: AppColors.primary),
              ),
              label: 'Keranjang',
            ),
            const BottomNavigationBarItem(
              icon: Icon(Icons.person_outline),
              activeIcon: Icon(Icons.person, color: AppColors.primary),
              label: 'Akun Saya',
            ),
          ],
        ),
      ),
    );
  }
}
