import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../models/order.dart';
import '../theme/app_colors.dart';
import 'main_navigation_screen.dart';

class OrderSuccessScreen extends StatelessWidget {
  final OrderModel order;
  final String? tableStatus;

  const OrderSuccessScreen({super.key, required this.order, this.tableStatus});

  @override
  Widget build(BuildContext context) {
    final currencyFormatter = NumberFormat.currency(locale: 'id_ID', symbol: 'Rp ', decimalDigits: 0);

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: AppColors.primary,
        foregroundColor: Colors.white,
        automaticallyImplyLeading: false,
        title: Row(
          children: [
            ClipRRect(
              borderRadius: BorderRadius.circular(6),
              child: Image.asset('assets/images/logo.png', width: 28, height: 28),
            ),
            const SizedBox(width: 10),
            const Text('Status Pemesanan', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
          ],
        ),
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(20),
          child: Column(
            children: [
              const SizedBox(height: 10),
              // Success Icon Animation
              Container(
                width: 80,
                height: 80,
                decoration: BoxDecoration(
                  color: AppColors.success.withOpacity(0.12),
                  shape: BoxShape.circle,
                ),
                child: const Icon(Icons.check_circle, color: AppColors.success, size: 54),
              ),
              const SizedBox(height: 16),
              const Text(
                'Pembayaran Berhasil!',
                style: TextStyle(fontSize: 22, fontWeight: FontWeight.w900, color: AppColors.textDark),
              ),
              const SizedBox(height: 6),
              Text(
                order.orderType == 'dine-in'
                    ? 'Pesanan Mandiri Diterima & Meja Telah Di-Booking'
                    : 'Pesanan Online Diterima & Sedang Diproses',
                textAlign: TextAlign.center,
                style: const TextStyle(fontSize: 13, color: AppColors.textMuted),
              ),
              const SizedBox(height: 24),

              // CARD HIGHLIGHT STATUS MEJA & STOK
              if (order.orderType == 'dine-in')
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(16),
                  margin: const EdgeInsets.only(bottom: 16),
                  decoration: BoxDecoration(
                    gradient: const LinearGradient(
                      colors: [Color(0xFF0C233C), Color(0xFF1E3A5F)],
                      begin: Alignment.topLeft,
                      end: Alignment.bottomRight,
                    ),
                    borderRadius: BorderRadius.circular(16),
                    boxShadow: [
                      BoxShadow(color: AppColors.primary.withOpacity(0.2), blurRadius: 10, offset: const Offset(0, 4))
                    ],
                  ),
                  child: Column(
                    children: [
                      Row(
                        children: [
                          const Icon(Icons.table_restaurant, color: AppColors.accent, size: 18),
                          const SizedBox(width: 6),
                          const Expanded(
                            child: Text(
                              'STATUS MEJA RESTORAN',
                              style: TextStyle(color: Colors.white70, fontSize: 10, fontWeight: FontWeight.bold),
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                          const SizedBox(width: 6),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                            decoration: BoxDecoration(
                              color: AppColors.accent,
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: const Text('BOOKED', style: TextStyle(color: Colors.black, fontWeight: FontWeight.w900, fontSize: 10)),
                          ),
                        ],
                      ),
                      const SizedBox(height: 12),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text(order.tableNumber ?? 'Meja Restoran', style: const TextStyle(color: Colors.white, fontSize: 24, fontWeight: FontWeight.w900)),
                          const Text('Tanpa Perlu ke Kasir', style: TextStyle(color: Colors.white70, fontSize: 12)),
                        ],
                      ),
                      const SizedBox(height: 8),
                      const Divider(color: Colors.white24),
                      const SizedBox(height: 4),
                      const Row(
                        children: [
                          Icon(Icons.inventory_2_outlined, color: AppColors.success, size: 14),
                          SizedBox(width: 6),
                          Expanded(
                            child: Text(
                              'Stok menu makanan telah otomatis berkurang dari sistem inventory restoran.',
                              style: TextStyle(color: Colors.white, fontSize: 11),
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),

              // Detail Tagihan
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: AppColors.border),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text('Rincian Transaksi', style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: AppColors.textDark)),
                    const SizedBox(height: 12),
                    _buildRow('No. Pesanan', order.orderNumber),
                    _buildRow('Nama Pelanggan', order.customerName),
                    _buildRow('Tipe Pesanan', order.orderType.toUpperCase()),
                    if (order.tableNumber != null) _buildRow('Nomor Meja', order.tableNumber!),
                    if (order.deliveryAddress != null) _buildRow('Alamat Antar', order.deliveryAddress!),
                    const Divider(height: 20),
                    _buildRow('Subtotal', currencyFormatter.format(order.subtotal)),
                    _buildRow('Pajak Restoran (10%)', currencyFormatter.format(order.taxAmount)),
                    const SizedBox(height: 4),
                    _buildRow(
                      'Total Pembayaran',
                      currencyFormatter.format(order.totalAmount),
                      isBold: true,
                      color: AppColors.primary,
                      fontSize: 16,
                    ),
                    const SizedBox(height: 8),
                    Container(
                      padding: const EdgeInsets.all(10),
                      decoration: BoxDecoration(
                        color: AppColors.success.withOpacity(0.08),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: const Row(
                        children: [
                          Icon(Icons.soup_kitchen, color: AppColors.success, size: 18),
                          SizedBox(width: 8),
                          Expanded(
                            child: Text(
                              'Pesanan Anda telah diteruskan ke layar dapur (KDS) dan koki sedang menyiapkan hidangan.',
                              style: TextStyle(fontSize: 11, color: AppColors.success, fontWeight: FontWeight.w600),
                            ),
                          ),
                        ],
                      ),
                    )
                  ],
                ),
              ),

              const SizedBox(height: 24),

              // Action Button
              SizedBox(
                width: double.infinity,
                height: 48,
                child: ElevatedButton.icon(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppColors.primary,
                    foregroundColor: Colors.white,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  icon: const Icon(Icons.home),
                  label: const Text('Kembali ke Menu Utama', style: TextStyle(fontWeight: FontWeight.bold)),
                  onPressed: () {
                    Navigator.pushAndRemoveUntil(
                      context,
                      MaterialPageRoute(builder: (_) => const MainNavigationScreen()),
                      (route) => false,
                    );
                  },
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildRow(String label, String value, {bool isBold = false, Color? color, double fontSize = 12}) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: const TextStyle(fontSize: 12, color: AppColors.textMuted)),
          Flexible(
            child: Text(
              value,
              textAlign: TextAlign.end,
              style: TextStyle(
                fontSize: fontSize,
                fontWeight: isBold ? FontWeight.bold : FontWeight.w600,
                color: color ?? AppColors.textDark,
              ),
            ),
          ),
        ],
      ),
    );
  }
}
