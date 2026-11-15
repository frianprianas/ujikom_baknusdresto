import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../models/category.dart';
import '../models/order.dart';
import '../models/product.dart';
import '../models/table.dart';
import '../services/api_service.dart';
import '../theme/app_colors.dart';
import 'cart_screen.dart';
import 'table_picker_screen.dart';

class HomeScreen extends StatefulWidget {
  final List<CartItem>? sharedCart;
  final RestaurantTableModel? selectedTable;
  final String orderType;
  final ValueChanged<String>? onOrderTypeChanged;
  final ValueChanged<RestaurantTableModel>? onTableSelected;
  final VoidCallback? onCartUpdated;
  final VoidCallback? onNavigateToCart;

  const HomeScreen({
    super.key,
    this.sharedCart,
    this.selectedTable,
    this.orderType = 'dine-in',
    this.onOrderTypeChanged,
    this.onTableSelected,
    this.onCartUpdated,
    this.onNavigateToCart,
  });

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  final ApiService _api = ApiService();
  final currencyFormatter = NumberFormat.currency(locale: 'id_ID', symbol: 'Rp ', decimalDigits: 0);

  List<CategoryModel> _categories = [];
  List<ProductModel> _products = [];
  final List<CartItem> _localCart = [];

  List<CartItem> get _cart => widget.sharedCart ?? _localCart;
  RestaurantTableModel? get _selectedTable => widget.selectedTable ?? _localTable;
  RestaurantTableModel? _localTable;

  String get _currentOrderType => widget.orderType;

  int? _selectedCategoryId;
  String _searchQuery = '';
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadInitialData();
  }

  Future<void> _loadInitialData() async {
    setState(() => _isLoading = true);
    final cats = await _api.getCategories();
    final prods = await _api.getProducts();

    setState(() {
      _categories = cats;
      _products = prods;
      _isLoading = false;
    });
  }

  Future<void> _filterProducts() async {
    final prods = await _api.getProducts(
      categoryId: _selectedCategoryId,
      search: _searchQuery,
    );
    setState(() {
      _products = prods;
    });
  }

  int get _cartTotalItems => _cart.fold(0, (sum, i) => sum + i.quantity);
  double get _cartTotalPrice => _cart.fold(0, (sum, i) => sum + i.subtotal);

  void _addToCart(ProductModel product) {
    if (product.stock <= 0) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Maaf, stok ${product.name} sudah habis!'),
          backgroundColor: AppColors.danger,
        ),
      );
      return;
    }

    final existingIndex = _cart.indexWhere((i) => i.product.id == product.id);

    setState(() {
      if (existingIndex >= 0) {
        if (_cart[existingIndex].quantity + 1 > product.stock) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text('Pesanan ${product.name} melebihi sisa stok (${product.stock} porsi).'),
              backgroundColor: AppColors.warning,
            ),
          );
          return;
        }
        _cart[existingIndex].quantity++;
      } else {
        _cart.add(CartItem(product: product, quantity: 1));
      }
    });

    widget.onCartUpdated?.call();

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('${product.name} ditambahkan ke keranjang.'),
        duration: const Duration(milliseconds: 700),
        backgroundColor: AppColors.primary,
      ),
    );
  }

  void _openServerConfigDialog() {
    final textCtrl = TextEditingController(text: _api.baseUrl);
    showDialog(
      context: context,
      builder: (_) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: const Text('Pengaturan API Backend', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Ubah alamat API Laravel Baknus Dresto sesuai koneksi Anda:\n'
              '• Cloud Server VPS: http://202.10.40.177/api/mobile\n'
              '• Emulator Lokal: http://10.0.2.2:8000/api/mobile\n'
              '• Local Web/PC: http://127.0.0.1:8000/api/mobile',
              style: TextStyle(fontSize: 11, color: AppColors.textMuted),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: textCtrl,
              decoration: InputDecoration(
                filled: true,
                fillColor: AppColors.background,
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: BorderSide.none),
                contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              ),
              style: const TextStyle(fontSize: 12),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Batal'),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: AppColors.primary, foregroundColor: Colors.white),
            onPressed: () {
              _api.setBaseUrl(textCtrl.text.trim());
              Navigator.pop(context);
              _loadInitialData();
            },
            child: const Text('Simpan'),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: AppColors.primary,
        elevation: 0,
        foregroundColor: Colors.white,
        title: Row(
          children: [
            ClipRRect(
              borderRadius: BorderRadius.circular(10),
              child: Image.asset(
                'assets/images/logo.png',
                width: 36,
                height: 36,
                fit: BoxFit.cover,
                errorBuilder: (context, error, stackTrace) => Container(
                  padding: const EdgeInsets.all(6),
                  decoration: BoxDecoration(
                    color: AppColors.accent,
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: const Icon(Icons.restaurant, color: Colors.black, size: 18),
                ),
              ),
            ),
            const SizedBox(width: 10),
            const Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Baknus Dresto', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w900, color: Colors.white)),
                Text('Self-Order & Online Delivery', style: TextStyle(fontSize: 10, color: AppColors.accent, fontWeight: FontWeight.bold)),
              ],
            ),
          ],
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.settings, size: 20),
            tooltip: 'Pengaturan Server API',
            onPressed: _openServerConfigDialog,
          ),
        ],
      ),
      body: SafeArea(
        child: Column(
          children: [
            // TOP BANNER MODE: DINE-IN SELF ORDER VS DELIVERY
            Container(
              padding: const EdgeInsets.fromLTRB(16, 12, 16, 14),
              color: AppColors.primary,
              child: Column(
                children: [
                  // Toggle Mode
                  Container(
                    padding: const EdgeInsets.all(3),
                    decoration: BoxDecoration(
                      color: Colors.white.withOpacity(0.12),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Row(
                      children: [
                        Expanded(
                          child: InkWell(
                            onTap: () => widget.onOrderTypeChanged?.call('dine-in'),
                            borderRadius: BorderRadius.circular(10),
                            child: Container(
                              padding: const EdgeInsets.symmetric(vertical: 8),
                              decoration: BoxDecoration(
                                color: _currentOrderType == 'dine-in' ? AppColors.accent : Colors.transparent,
                                borderRadius: BorderRadius.circular(10),
                              ),
                              child: Center(
                                child: Text(
                                  'Makan di Tempat (Dine-In)',
                                  style: TextStyle(
                                    fontSize: 11,
                                    fontWeight: FontWeight.bold,
                                    color: _currentOrderType == 'dine-in' ? Colors.black : Colors.white70,
                                  ),
                                ),
                              ),
                            ),
                          ),
                        ),
                        Expanded(
                          child: InkWell(
                            onTap: () => widget.onOrderTypeChanged?.call('delivery'),
                            borderRadius: BorderRadius.circular(10),
                            child: Container(
                              padding: const EdgeInsets.symmetric(vertical: 8),
                              decoration: BoxDecoration(
                                color: _currentOrderType == 'delivery' ? AppColors.accent : Colors.transparent,
                                borderRadius: BorderRadius.circular(10),
                              ),
                              child: Center(
                                child: Text(
                                  'Pesan Antar (Delivery)',
                                  style: TextStyle(
                                    fontSize: 11,
                                    fontWeight: FontWeight.bold,
                                    color: _currentOrderType == 'delivery' ? Colors.black : Colors.white70,
                                  ),
                                ),
                              ),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),

                  const SizedBox(height: 10),

                  // Quick Action Info
                  if (_currentOrderType == 'dine-in')
                    InkWell(
                      onTap: () async {
                        final table = await Navigator.push<RestaurantTableModel>(
                          context,
                          MaterialPageRoute(
                            builder: (_) => TablePickerScreen(selectedTable: _selectedTable),
                          ),
                        );
                        if (table != null) {
                          widget.onTableSelected?.call(table);
                          setState(() => _localTable = table);
                        }
                      },
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                        decoration: BoxDecoration(
                          color: Colors.white.withOpacity(0.08),
                          borderRadius: BorderRadius.circular(10),
                          border: Border.all(color: Colors.white24),
                        ),
                        child: Row(
                          children: [
                            const Icon(Icons.table_restaurant, color: AppColors.accent, size: 16),
                            const SizedBox(width: 8),
                            Expanded(
                              child: Text(
                                _selectedTable != null
                                    ? 'Meja: ${_selectedTable!.tableNumber} (${_selectedTable!.capacity} Kursi)'
                                    : 'Belum Memilih Meja (Pilih Meja)',
                                style: const TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold),
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                            const SizedBox(width: 8),
                            const Text('Ganti →', style: TextStyle(color: AppColors.accent, fontSize: 11, fontWeight: FontWeight.bold)),
                          ],
                        ),
                      ),
                    )
                  else
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                      decoration: BoxDecoration(
                        color: Colors.white.withOpacity(0.08),
                        borderRadius: BorderRadius.circular(10),
                        border: Border.all(color: Colors.white24),
                      ),
                      child: const Row(
                        children: [
                          Icon(Icons.motorcycle, color: AppColors.accent, size: 16),
                          SizedBox(width: 8),
                          Expanded(
                            child: Text(
                              'Pesan Online: Pembayaran otomatis memotong stok & mengaktifkan simulasi pengantaran.',
                              style: TextStyle(color: Colors.white, fontSize: 10),
                            ),
                          ),
                        ],
                      ),
                    ),
                ],
              ),
            ),

            // SEARCH BAR & CATEGORY BAR
            Container(
              padding: const EdgeInsets.fromLTRB(16, 12, 16, 4),
              color: Colors.white,
              child: Column(
                children: [
                  // Search Box
                  Container(
                    height: 40,
                    decoration: BoxDecoration(
                      color: AppColors.background,
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: TextField(
                      onChanged: (val) {
                        _searchQuery = val;
                        _filterProducts();
                      },
                      decoration: const InputDecoration(
                        prefixIcon: Icon(Icons.search, size: 18, color: AppColors.textMuted),
                        hintText: 'Cari menu makanan atau minuman...',
                        hintStyle: TextStyle(fontSize: 12, color: Colors.grey),
                        border: InputBorder.none,
                        contentPadding: EdgeInsets.symmetric(vertical: 10),
                      ),
                      style: const TextStyle(fontSize: 12),
                    ),
                  ),

                  const SizedBox(height: 10),

                  // Horizontal Category Filter
                  SizedBox(
                    height: 34,
                    child: ListView(
                      scrollDirection: Axis.horizontal,
                      children: [
                        _buildCategoryChip(null, 'Semua Menu'),
                        ..._categories.map((c) => _buildCategoryChip(c.id, c.name)),
                      ],
                    ),
                  ),
                ],
              ),
            ),

            const Divider(height: 1, color: AppColors.border),

            // PRODUCT GRID
            Expanded(
              child: _isLoading
                  ? const Center(child: CircularProgressIndicator(color: AppColors.accent))
                  : _products.isEmpty
                      ? const Center(child: Text('Menu tidak ditemukan', style: TextStyle(color: AppColors.textMuted)))
                      : RefreshIndicator(
                          onRefresh: _loadInitialData,
                          child: GridView.builder(
                            padding: const EdgeInsets.fromLTRB(16, 14, 16, 80),
                            gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                              crossAxisCount: 2,
                              childAspectRatio: 0.65,
                              crossAxisSpacing: 12,
                              mainAxisSpacing: 12,
                            ),
                            itemCount: _products.length,
                            itemBuilder: (context, index) {
                              final product = _products[index];
                              final isOutOfStock = product.stock <= 0;

                              return Container(
                                decoration: BoxDecoration(
                                  color: Colors.white,
                                  borderRadius: BorderRadius.circular(16),
                                  border: Border.all(color: AppColors.border),
                                  boxShadow: [
                                    BoxShadow(color: Colors.black.withOpacity(0.02), blurRadius: 6, offset: const Offset(0, 2))
                                  ],
                                ),
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    // Food Graphic / Icon Banner
                                    Container(
                                      height: 84,
                                      width: double.infinity,
                                      decoration: BoxDecoration(
                                        gradient: LinearGradient(
                                          colors: [
                                            const Color(0xFFB47B2B).withOpacity(0.85),
                                            const Color(0xFF20556F).withOpacity(0.9),
                                          ],
                                          begin: Alignment.topLeft,
                                          end: Alignment.bottomRight,
                                        ),
                                        borderRadius: const BorderRadius.vertical(top: Radius.circular(15)),
                                      ),
                                      child: Stack(
                                        children: [
                                          Center(
                                            child: Icon(
                                              product.categoryId == 3 ? Icons.local_drink : Icons.restaurant,
                                              size: 34,
                                              color: Colors.white.withOpacity(0.9),
                                            ),
                                          ),
                                          // Stock Badge
                                          Positioned(
                                            top: 6,
                                            right: 6,
                                            child: Container(
                                              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                              decoration: BoxDecoration(
                                                color: isOutOfStock ? AppColors.danger : Colors.black87,
                                                borderRadius: BorderRadius.circular(6),
                                              ),
                                              child: Text(
                                                isOutOfStock ? 'HABIS' : 'Sisa ${product.stock}',
                                                style: const TextStyle(color: Colors.white, fontSize: 9, fontWeight: FontWeight.bold),
                                              ),
                                            ),
                                          ),
                                        ],
                                      ),
                                    ),

                                    // Content: Category -> Name -> Price grouped closely without big gaps
                                    Padding(
                                      padding: const EdgeInsets.fromLTRB(10, 8, 10, 4),
                                      child: Column(
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        mainAxisSize: MainAxisSize.min,
                                        children: [
                                          Text(
                                            product.categoryName ?? 'Menu Resto',
                                            maxLines: 1,
                                            overflow: TextOverflow.ellipsis,
                                            style: const TextStyle(fontSize: 10, color: AppColors.textMuted, fontWeight: FontWeight.bold),
                                          ),
                                          const SizedBox(height: 2),
                                          Text(
                                            product.name,
                                            maxLines: 2,
                                            overflow: TextOverflow.ellipsis,
                                            style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: AppColors.textDark, height: 1.2),
                                          ),
                                          const SizedBox(height: 4),
                                          Text(
                                            currencyFormatter.format(product.price),
                                            style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w900, color: AppColors.primary),
                                          ),
                                        ],
                                      ),
                                    ),

                                    const Spacer(),

                                    // Add to Cart Button
                                    Padding(
                                      padding: const EdgeInsets.fromLTRB(10, 0, 10, 8),
                                      child: SizedBox(
                                        width: double.infinity,
                                        height: 30,
                                        child: ElevatedButton(
                                          style: ElevatedButton.styleFrom(
                                            backgroundColor: isOutOfStock ? Colors.grey.shade300 : AppColors.accent,
                                            foregroundColor: Colors.black,
                                            elevation: 0,
                                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                                            padding: EdgeInsets.zero,
                                          ),
                                          onPressed: isOutOfStock ? null : () => _addToCart(product),
                                          child: Text(
                                            isOutOfStock ? 'Habis' : '+ Tambah',
                                            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 11),
                                          ),
                                        ),
                                      ),
                                    ),
                                  ],
                                ),
                              );
                            },
                          ),
                        ),
            ),
          ],
        ),
      ),

      // BOTTOM FLOATING CART BAR
      bottomNavigationBar: _cart.isNotEmpty
          ? Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: Colors.white,
                boxShadow: [
                  BoxShadow(color: Colors.black.withOpacity(0.08), blurRadius: 10, offset: const Offset(0, -2))
                ],
              ),
              child: SafeArea(
                child: Row(
                  children: [
                    Expanded(
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text('$_cartTotalItems Item Terpilih', style: const TextStyle(fontSize: 11, color: AppColors.textMuted)),
                          Text(
                            currencyFormatter.format(_cartTotalPrice),
                            style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w900, color: AppColors.primary),
                          ),
                        ],
                      ),
                    ),
                    ElevatedButton.icon(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppColors.primary,
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 12),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                      icon: const Icon(Icons.shopping_bag_outlined, size: 18),
                      label: const Text('Checkout & Bayar', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                      onPressed: () {
                        if (widget.onNavigateToCart != null) {
                          widget.onNavigateToCart!();
                        } else {
                          Navigator.push(
                            context,
                            MaterialPageRoute(
                              builder: (_) => CartScreen(
                                cart: _cart,
                                initialOrderType: _currentOrderType,
                                initialTable: _selectedTable,
                                onCartUpdated: () => setState(() {}),
                              ),
                            ),
                          );
                        }
                      },
                    ),
                  ],
                ),
              ),
            )
          : null,
    );
  }

  Widget _buildCategoryChip(int? id, String label) {
    final isSelected = _selectedCategoryId == id;
    return Padding(
      padding: const EdgeInsets.only(right: 6),
      child: ChoiceChip(
        label: Text(label),
        selected: isSelected,
        labelStyle: TextStyle(
          fontSize: 11,
          fontWeight: isSelected ? FontWeight.bold : FontWeight.w500,
          color: isSelected ? Colors.black : AppColors.textDark,
        ),
        selectedColor: AppColors.accent,
        backgroundColor: AppColors.background,
        side: BorderSide(color: isSelected ? AppColors.accent : Colors.transparent),
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
        onSelected: (val) {
          setState(() {
            _selectedCategoryId = val ? id : null;
          });
          _filterProducts();
        },
      ),
    );
  }
}
