import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../models/order.dart';
import '../models/table.dart';
import '../services/api_service.dart';
import '../theme/app_colors.dart';
import 'payment_screen.dart';
import 'table_picker_screen.dart';

class CartScreen extends StatefulWidget {
  final List<CartItem> cart;
  final String initialOrderType;
  final RestaurantTableModel? initialTable;
  final bool isTabScreen;
  final VoidCallback onCartUpdated;

  const CartScreen({
    super.key,
    required this.cart,
    this.initialOrderType = 'dine-in',
    this.initialTable,
    this.isTabScreen = false,
    required this.onCartUpdated,
  });

  @override
  State<CartScreen> createState() => _CartScreenState();
}

class _CartScreenState extends State<CartScreen> {
  final ApiService _api = ApiService();
  late String _orderType; // dine-in atau delivery
  RestaurantTableModel? _selectedTable;

  final TextEditingController _nameController = TextEditingController();
  final TextEditingController _phoneController = TextEditingController();
  final TextEditingController _addressController = TextEditingController();
  final TextEditingController _notesController = TextEditingController();

  bool _isSubmitting = false;

  @override
  void initState() {
    super.initState();
    _orderType = widget.initialOrderType;
    _selectedTable = widget.initialTable;

    final u = _api.currentUser;
    _nameController.text = u != null ? u.name : 'Pelanggan Baknus';
    _phoneController.text = u != null ? u.phone : '081298765432';
    _addressController.text = u != null && u.address != null
        ? '${u.address} (Patokan: ${u.addressBenchmark ?? "-"})'
        : 'Jl. Merdeka No. 12, Jakarta';
  }

  double get _subtotal => widget.cart.fold(0, (sum, item) => sum + item.subtotal);
  double get _taxAmount => _subtotal * 0.10;
  double get _totalAmount => _subtotal + _taxAmount;

  // 1. Aksi "Pesan Saja" (Stok TIDAK berkurang, Meja BELUM dibooking)
  Future<void> _submitOrderOnly() async {
    if (!_validateForm()) return;

    setState(() => _isSubmitting = true);

    final res = await _api.createOrder(
      orderType: _orderType,
      tableId: _orderType == 'dine-in' ? _selectedTable?.id : null,
      customerName: _nameController.text.trim(),
      customerPhone: _phoneController.text.trim(),
      deliveryAddress: _orderType == 'delivery' ? _addressController.text.trim() : null,
      notes: _notesController.text.trim(),
      items: widget.cart,
    );

    setState(() => _isSubmitting = false);

    if (res['success'] == true) {
      final order = res['order'] as OrderModel;
      widget.cart.clear();
      widget.onCartUpdated();

      if (!mounted) return;

      showDialog(
        context: context,
        barrierDismissible: false,
        builder: (_) => AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
          title: const Row(
            children: [
              Icon(Icons.info_outline, color: AppColors.accent),
              SizedBox(width: 8),
              Text('Pesanan Dibuat (Belum Bayar)', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
            ],
          ),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('Nomor Pesanan: ${order.orderNumber}', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
              const SizedBox(height: 8),
              const Text(
                'Sesuai ketentuan, karena Anda baru melakukan "Pesan Saja":\n\n'
                '• Stok menu makanan BELUM berkurang.\n'
                '• Meja BELUM berstatus Booking.\n\n'
                'Stok akan berkurang dan meja akan berubah jadi BOOKING setelah Anda melakukan pembayaran mandiri di aplikasi.',
                style: TextStyle(fontSize: 12, color: AppColors.textDark, height: 1.3),
              ),
            ],
          ),
          actions: [
            ElevatedButton(
              style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.primary,
                foregroundColor: Colors.white,
              ),
              onPressed: () {
                Navigator.pop(context); // close dialog
                Navigator.pop(context); // close cart
              },
              child: const Text('Mengerti'),
            )
          ],
        ),
      );
    } else {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(res['message'] ?? 'Gagal membuat pesanan'), backgroundColor: AppColors.danger),
      );
    }
  }

  // 2. Aksi "Bayar Sekarang Tanpa Kasir" (Self-Pay)
  // Menuju ke PaymentScreen -> Saat lunas: STOK BERKURANG & MEJA JADI BOOKING
  Future<void> _submitAndPayNow() async {
    if (!_validateForm()) return;

    setState(() => _isSubmitting = true);

    final res = await _api.createOrder(
      orderType: _orderType,
      tableId: _orderType == 'dine-in' ? _selectedTable?.id : null,
      customerName: _nameController.text.trim(),
      customerPhone: _phoneController.text.trim(),
      deliveryAddress: _orderType == 'delivery' ? _addressController.text.trim() : null,
      notes: _notesController.text.trim(),
      items: widget.cart,
    );

    setState(() => _isSubmitting = false);

    if (res['success'] == true) {
      final order = res['order'] as OrderModel;
      final selectedTable = _selectedTable;

      widget.cart.clear();
      widget.onCartUpdated();

      if (!mounted) return;

      Navigator.push(
        context,
        MaterialPageRoute(
          builder: (_) => PaymentScreen(
            order: order,
            table: selectedTable,
          ),
        ),
      );
    } else {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(res['message'] ?? 'Gagal membuat pesanan'), backgroundColor: AppColors.danger),
      );
    }
  }

  bool _validateForm() {
    if (widget.cart.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Keranjang belanja masih kosong!'), backgroundColor: AppColors.danger),
      );
      return false;
    }

    if (_nameController.text.trim().isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Nama pemesan wajib diisi.'), backgroundColor: AppColors.danger),
      );
      return false;
    }

    if (_orderType == 'dine-in' && _selectedTable == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Silakan pilih Meja Restoran terlebih dahulu untuk Dine-In!'),
          backgroundColor: AppColors.danger,
        ),
      );
      return false;
    }

    if (_orderType == 'delivery' && _addressController.text.trim().isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Alamat pengantaran wajib diisi untuk delivery!'), backgroundColor: AppColors.danger),
      );
      return false;
    }

    return true;
  }

  @override
  Widget build(BuildContext context) {
    final currencyFormatter = NumberFormat.currency(locale: 'id_ID', symbol: 'Rp ', decimalDigits: 0);

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: AppColors.primary,
        foregroundColor: Colors.white,
        automaticallyImplyLeading: !widget.isTabScreen,
        title: const Text('Checkout & Pemesanan', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
      ),
      body: SafeArea(
        child: Column(
          children: [
            Expanded(
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // SEGMENTED TOGGLE: DINE-IN VS DELIVERY
                    Container(
                      padding: const EdgeInsets.all(4),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(14),
                        border: Border.all(color: AppColors.border),
                      ),
                      child: Row(
                        children: [
                          Expanded(
                            child: InkWell(
                              onTap: () => setState(() => _orderType = 'dine-in'),
                              borderRadius: BorderRadius.circular(10),
                              child: Container(
                                padding: const EdgeInsets.symmetric(vertical: 10),
                                decoration: BoxDecoration(
                                  color: _orderType == 'dine-in' ? AppColors.primary : Colors.transparent,
                                  borderRadius: BorderRadius.circular(10),
                                ),
                                child: Row(
                                  mainAxisAlignment: MainAxisAlignment.center,
                                  children: [
                                    Icon(Icons.restaurant, size: 16, color: _orderType == 'dine-in' ? AppColors.accent : AppColors.textMuted),
                                    const SizedBox(width: 8),
                                    Text(
                                      'Dine-In (Di Meja)',
                                      style: TextStyle(
                                        fontSize: 12,
                                        fontWeight: FontWeight.bold,
                                        color: _orderType == 'dine-in' ? Colors.white : AppColors.textDark,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ),
                          ),
                          Expanded(
                            child: InkWell(
                              onTap: () => setState(() => _orderType = 'delivery'),
                              borderRadius: BorderRadius.circular(10),
                              child: Container(
                                padding: const EdgeInsets.symmetric(vertical: 10),
                                decoration: BoxDecoration(
                                  color: _orderType == 'delivery' ? AppColors.primary : Colors.transparent,
                                  borderRadius: BorderRadius.circular(10),
                                ),
                                child: Row(
                                  mainAxisAlignment: MainAxisAlignment.center,
                                  children: [
                                    Icon(Icons.delivery_dining, size: 18, color: _orderType == 'delivery' ? AppColors.accent : AppColors.textMuted),
                                    const SizedBox(width: 8),
                                    Text(
                                      'Pesan Antar Online',
                                      style: TextStyle(
                                        fontSize: 12,
                                        fontWeight: FontWeight.bold,
                                        color: _orderType == 'delivery' ? Colors.white : AppColors.textDark,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),

                    const SizedBox(height: 16),

                    // KARTU PILIH MEJA (JIKA DINE-IN)
                    if (_orderType == 'dine-in')
                      Container(
                        padding: const EdgeInsets.all(14),
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(14),
                          border: Border.all(color: _selectedTable != null ? AppColors.primary : Colors.amber.shade400, width: 1.5),
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              children: [
                                Expanded(
                                  child: Row(
                                    children: [
                                      Container(
                                        padding: const EdgeInsets.all(8),
                                        decoration: BoxDecoration(
                                          color: AppColors.primary.withOpacity(0.1),
                                          shape: BoxShape.circle,
                                        ),
                                        child: const Icon(Icons.table_restaurant, color: AppColors.primary, size: 20),
                                      ),
                                      const SizedBox(width: 10),
                                      Expanded(
                                        child: Column(
                                          crossAxisAlignment: CrossAxisAlignment.start,
                                          children: [
                                            Text(
                                              _selectedTable != null ? _selectedTable!.tableNumber : 'Belum Memilih Meja',
                                              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: AppColors.textDark),
                                              overflow: TextOverflow.ellipsis,
                                            ),
                                            Text(
                                              _selectedTable != null
                                                  ? '${_selectedTable!.floorSection} • ${_selectedTable!.capacity} Kursi'
                                                  : 'Pilih meja untuk self-order tanpa kasir',
                                              style: const TextStyle(fontSize: 11, color: AppColors.textMuted),
                                              overflow: TextOverflow.ellipsis,
                                            ),
                                          ],
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                                const SizedBox(width: 8),
                                ElevatedButton(
                                  style: ElevatedButton.styleFrom(
                                    backgroundColor: _selectedTable != null ? Colors.grey.shade100 : AppColors.accent,
                                    foregroundColor: _selectedTable != null ? AppColors.textDark : Colors.black,
                                    elevation: 0,
                                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                                  ),
                                  onPressed: () async {
                                    final res = await Navigator.push<RestaurantTableModel>(
                                      context,
                                      MaterialPageRoute(
                                        builder: (_) => TablePickerScreen(selectedTable: _selectedTable),
                                      ),
                                    );
                                    if (res != null) {
                                      setState(() => _selectedTable = res);
                                    }
                                  },
                                  child: Text(_selectedTable != null ? 'Ganti Meja' : 'Pilih Meja', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 11)),
                                ),
                              ],
                            ),
                            if (_selectedTable != null) ...[
                              const SizedBox(height: 8),
                              const Divider(height: 1),
                              const SizedBox(height: 8),
                              const Row(
                                children: [
                                  Icon(Icons.check_circle, color: AppColors.success, size: 14),
                                  SizedBox(width: 6),
                                  Expanded(
                                    child: Text(
                                      'Meja akan otomatis berubah jadi status BOOKING begitu Anda membayar.',
                                      style: TextStyle(fontSize: 10, color: AppColors.success, fontWeight: FontWeight.bold),
                                    ),
                                  ),
                                ],
                              ),
                            ]
                          ],
                        ),
                      ),

                    // FORM INPUT DELIVERY (JIKA DELIVERY)
                    if (_orderType == 'delivery')
                      Container(
                        padding: const EdgeInsets.all(14),
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(14),
                          border: Border.all(color: AppColors.border),
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text('Alamat Pengantaran Makanan', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: AppColors.textDark)),
                            const SizedBox(height: 10),
                            TextField(
                              controller: _addressController,
                              maxLines: 2,
                              decoration: InputDecoration(
                                hintText: 'Masukkan nama jalan, nomor rumah, RT/RW, dan patokan..',
                                hintStyle: const TextStyle(fontSize: 12, color: Colors.grey),
                                filled: true,
                                fillColor: AppColors.background,
                                border: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: BorderSide.none),
                                contentPadding: const EdgeInsets.all(10),
                              ),
                              style: const TextStyle(fontSize: 12),
                            ),
                            const SizedBox(height: 10),
                            TextField(
                              controller: _phoneController,
                              keyboardType: TextInputType.phone,
                              decoration: InputDecoration(
                                prefixIcon: const Icon(Icons.phone, size: 16, color: AppColors.textMuted),
                                hintText: 'No. WhatsApp Aktif',
                                hintStyle: const TextStyle(fontSize: 12, color: Colors.grey),
                                filled: true,
                                fillColor: AppColors.background,
                                border: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: BorderSide.none),
                                contentPadding: const EdgeInsets.all(10),
                              ),
                              style: const TextStyle(fontSize: 12),
                            ),
                          ],
                        ),
                      ),

                    const SizedBox(height: 16),

                    // NAMA PELANGGAN
                    Container(
                      padding: const EdgeInsets.all(14),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(14),
                        border: Border.all(color: AppColors.border),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('Nama Pemesan', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: AppColors.textDark)),
                          const SizedBox(height: 8),
                          TextField(
                            controller: _nameController,
                            decoration: InputDecoration(
                              prefixIcon: const Icon(Icons.person, size: 18, color: AppColors.textMuted),
                              hintText: 'Nama lengkap Anda',
                              hintStyle: const TextStyle(fontSize: 12, color: Colors.grey),
                              filled: true,
                              fillColor: AppColors.background,
                              border: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: BorderSide.none),
                              contentPadding: const EdgeInsets.all(10),
                            ),
                            style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold),
                          ),
                        ],
                      ),
                    ),

                    const SizedBox(height: 16),

                    // DAFTAR MENU DALAM KERANJANG
                    const Text('Daftar Menu yang Dipesan', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: AppColors.textDark)),
                    const SizedBox(height: 8),

                    if (widget.cart.isEmpty)
                      Container(
                        padding: const EdgeInsets.all(30),
                        alignment: Alignment.center,
                        child: const Text('Keranjang masih kosong', style: TextStyle(color: AppColors.textMuted)),
                      )
                    else
                      ...widget.cart.map((item) {
                        return Container(
                          margin: const EdgeInsets.only(bottom: 8),
                          padding: const EdgeInsets.all(12),
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(14),
                            border: Border.all(color: AppColors.border),
                          ),
                          child: Row(
                            children: [
                              Container(
                                width: 44,
                                height: 44,
                                decoration: BoxDecoration(
                                  color: AppColors.primary.withOpacity(0.08),
                                  borderRadius: BorderRadius.circular(10),
                                ),
                                child: const Icon(Icons.fastfood, color: AppColors.primary, size: 22),
                              ),
                              const SizedBox(width: 12),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(item.product.name, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: AppColors.textDark)),
                                    const SizedBox(height: 2),
                                    Text(
                                      currencyFormatter.format(item.product.price),
                                      style: const TextStyle(fontSize: 11, color: AppColors.textMuted),
                                    ),
                                  ],
                                ),
                              ),
                              // Counter QTY
                              Row(
                                children: [
                                  InkWell(
                                    onTap: () {
                                      setState(() {
                                        if (item.quantity > 1) {
                                          item.quantity--;
                                        } else {
                                          widget.cart.remove(item);
                                        }
                                      });
                                      widget.onCartUpdated();
                                    },
                                    child: Container(
                                      padding: const EdgeInsets.all(4),
                                      decoration: BoxDecoration(
                                        color: Colors.grey.shade100,
                                        borderRadius: BorderRadius.circular(6),
                                      ),
                                      child: const Icon(Icons.remove, size: 14, color: Colors.black87),
                                    ),
                                  ),
                                  Padding(
                                    padding: const EdgeInsets.symmetric(horizontal: 10),
                                    child: Text(
                                      '${item.quantity}',
                                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
                                    ),
                                  ),
                                  InkWell(
                                    onTap: () {
                                      if (item.quantity < item.product.stock) {
                                        setState(() => item.quantity++);
                                        widget.onCartUpdated();
                                      } else {
                                        ScaffoldMessenger.of(context).showSnackBar(
                                          SnackBar(
                                            content: Text('Maksimal stok ${item.product.name} tercapai.'),
                                            duration: const Duration(seconds: 1),
                                          ),
                                        );
                                      }
                                    },
                                    child: Container(
                                      padding: const EdgeInsets.all(4),
                                      decoration: BoxDecoration(
                                        color: AppColors.accent,
                                        borderRadius: BorderRadius.circular(6),
                                      ),
                                      child: const Icon(Icons.plus_one, size: 14, color: Colors.black),
                                    ),
                                  ),
                                ],
                              ),
                            ],
                          ),
                        );
                      }),

                    const SizedBox(height: 16),

                    // RINCIAN BIAYA
                    Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(14),
                        border: Border.all(color: AppColors.border),
                      ),
                      child: Column(
                        children: [
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              const Text('Subtotal Menu', style: TextStyle(fontSize: 12, color: AppColors.textMuted)),
                              Text(currencyFormatter.format(_subtotal), style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600)),
                            ],
                          ),
                          const SizedBox(height: 6),
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              const Text('Pajak Restoran PB1 (10%)', style: TextStyle(fontSize: 12, color: AppColors.textMuted)),
                              Text(currencyFormatter.format(_taxAmount), style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600)),
                            ],
                          ),
                          const Divider(height: 18),
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              const Text('Total Pembayaran', style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: AppColors.textDark)),
                              Text(
                                currencyFormatter.format(_totalAmount),
                                style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w900, color: AppColors.primary),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),

            // DUA TOMBOL AKSI: "PESAN SAJA" VS "BAYAR SEKARANG"
            Container(
              padding: const EdgeInsets.all(16),
              decoration: const BoxDecoration(
                color: Colors.white,
                border: Border(top: BorderSide(color: AppColors.border)),
              ),
              child: SafeArea(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    // TOMBOL UTAMA: BAYAR SEKARANG (SELF-PAY)
                    SizedBox(
                      width: double.infinity,
                      height: 48,
                      child: ElevatedButton.icon(
                        style: ElevatedButton.styleFrom(
                          backgroundColor: AppColors.success,
                          foregroundColor: Colors.white,
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                          elevation: 0,
                        ),
                        icon: _isSubmitting
                            ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                            : const Icon(Icons.flash_on, size: 20),
                        label: Text(
                          _isSubmitting
                              ? 'Memproses..'
                              : 'Bayar Sekarang Tanpa ke Kasir (${currencyFormatter.format(_totalAmount)})',
                          style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 13),
                        ),
                        onPressed: _isSubmitting ? null : _submitAndPayNow,
                      ),
                    ),
                    const SizedBox(height: 8),
                    // TOMBOL KEDUA: PESAN SAJA (BELUM BAYAR)
                    SizedBox(
                      width: double.infinity,
                      height: 40,
                      child: OutlinedButton(
                        style: OutlinedButton.styleFrom(
                          foregroundColor: AppColors.textDark,
                          side: const BorderSide(color: AppColors.border),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        ),
                        onPressed: _isSubmitting ? null : _submitOrderOnly,
                        child: const Text(
                          'Pesan Saja (Bayar Nanti / Stok Belum Berkurang)',
                          style: TextStyle(fontSize: 11, fontWeight: FontWeight.w600),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
