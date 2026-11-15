import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../models/order.dart';
import '../models/table.dart';
import '../services/api_service.dart';
import '../theme/app_colors.dart';
import 'delivery_tracking_screen.dart';
import 'order_success_screen.dart';

class PaymentScreen extends StatefulWidget {
  final OrderModel order;
  final RestaurantTableModel? table;

  const PaymentScreen({super.key, required this.order, this.table});

  @override
  State<PaymentScreen> createState() => _PaymentScreenState();
}

class _PaymentScreenState extends State<PaymentScreen> {
  final ApiService _api = ApiService();
  String _selectedMethod = 'qris';
  bool _isProcessing = false;

  final List<Map<String, dynamic>> _paymentMethods = [
    {'id': 'qris', 'name': 'QRIS (Semua E-Wallet & Bank)', 'icon': Icons.qr_code_scanner, 'category': 'Instant'},
    {'id': 'gopay', 'name': 'GoPay', 'icon': Icons.account_balance_wallet, 'category': 'E-Wallet'},
    {'id': 'ovo', 'name': 'OVO', 'icon': Icons.account_balance_wallet, 'category': 'E-Wallet'},
    {'id': 'dana', 'name': 'DANA', 'icon': Icons.account_balance_wallet, 'category': 'E-Wallet'},
    {'id': 'shopeepay', 'name': 'ShopeePay', 'icon': Icons.account_balance_wallet, 'category': 'E-Wallet'},
    {'id': 'va_bca', 'name': 'BCA Virtual Account', 'icon': Icons.account_balance, 'category': 'Virtual Account'},
    {'id': 'va_mandiri', 'name': 'Mandiri Virtual Account', 'icon': Icons.account_balance, 'category': 'Virtual Account'},
    {'id': 'va_bri', 'name': 'BRI Virtual Account', 'icon': Icons.account_balance, 'category': 'Virtual Account'},
  ];

  Future<void> _processPayment() async {
    setState(() => _isProcessing = true);

    final res = await _api.payOrder(
      orderId: widget.order.id,
      paymentMethod: _selectedMethod,
      amountPaid: widget.order.totalAmount,
    );

    setState(() => _isProcessing = false);

    if (res['success'] == true) {
      final updatedOrder = res['order'] as OrderModel? ?? widget.order;
      updatedOrder.isPaid = true;

      if (!mounted) return;

      if (widget.order.orderType == 'delivery') {
        Navigator.pushReplacement(
          context,
          MaterialPageRoute(
            builder: (_) => DeliveryTrackingScreen(order: updatedOrder),
          ),
        );
      } else {
        Navigator.pushReplacement(
          context,
          MaterialPageRoute(
            builder: (_) => OrderSuccessScreen(
              order: updatedOrder,
              tableStatus: res['table_status'] ?? 'reserved',
            ),
          ),
        );
      }
    } else {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(res['message'] ?? 'Gagal memproses pembayaran'),
          backgroundColor: AppColors.danger,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final currencyFormatter = NumberFormat.currency(locale: 'id_ID', symbol: 'Rp ', decimalDigits: 0);

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: AppColors.primary,
        foregroundColor: Colors.white,
        title: const Text('Pembayaran Mandiri di Aplikasi', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Banner Total Tagihan
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(20),
                decoration: BoxDecoration(
                  color: AppColors.primary,
                  borderRadius: BorderRadius.circular(16),
                  boxShadow: [
                    BoxShadow(color: AppColors.primary.withOpacity(0.2), blurRadius: 8, offset: const Offset(0, 3))
                  ],
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text('TOTAL YANG HARUS DIBAYAR', style: TextStyle(color: Colors.white70, fontSize: 11, fontWeight: FontWeight.bold)),
                    const SizedBox(height: 6),
                    Text(
                      currencyFormatter.format(widget.order.totalAmount),
                      style: const TextStyle(color: Colors.white, fontSize: 28, fontWeight: FontWeight.w900),
                    ),
                    const SizedBox(height: 6),
                    Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                          decoration: BoxDecoration(
                            color: AppColors.accent,
                            borderRadius: BorderRadius.circular(6),
                          ),
                          child: Text(
                            widget.order.orderType == 'dine-in'
                                ? 'DINE-IN: ${widget.order.tableNumber ?? "Meja Resto"}'
                                : 'DELIVERY ONLINE',
                            style: const TextStyle(color: Colors.black, fontSize: 10, fontWeight: FontWeight.w900),
                          ),
                        ),
                        const SizedBox(width: 8),
                        Text(
                          widget.order.orderNumber,
                          style: const TextStyle(color: Colors.white70, fontSize: 11),
                        ),
                      ],
                    ),
                  ],
                ),
              ),

              const SizedBox(height: 16),

              // PEMBERITAHUAN ATURAN SISTEM
              Container(
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(
                  color: Colors.amber.shade50,
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(color: Colors.amber.shade200),
                ),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Icon(Icons.info_outline, color: Colors.amber, size: 20),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('Aturan Pembayaran & Stok:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: Color(0xFF92400E))),
                          const SizedBox(height: 3),
                          Text(
                            widget.order.orderType == 'dine-in'
                                ? 'Saat Anda menyelesaikan pembayaran ini, Meja ${widget.order.tableNumber ?? ""} otomatis berubah menjadi status BOOKING di sistem dan stok menu makanan berkurang tanpa harus ke kasir.'
                                : 'Setelah dibayar, stok menu makanan akan langsung berkurang dan pesanan diteruskan ke dapur untuk pengantaran.',
                            style: const TextStyle(fontSize: 11, color: Color(0xFF78350F), height: 1.3),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),

              const SizedBox(height: 20),

              // PILIHAN METODE PEMBAYARAN
              const Text('Pilih Metode Pembayaran', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: AppColors.textDark)),
              const SizedBox(height: 12),

              // QRIS Card Display jika dipilih QRIS
              if (_selectedMethod == 'qris')
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(16),
                  margin: const EdgeInsets.only(bottom: 16),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: AppColors.accent, width: 1.5),
                  ),
                  child: Column(
                    children: [
                      const Text('Scan QRIS Baknus Dresto', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: AppColors.textDark)),
                      const SizedBox(height: 4),
                      const Text('BCA • Mandiri • BRI • GoPay • OVO • Dana • ShopeePay', style: TextStyle(fontSize: 10, color: AppColors.textMuted)),
                      const SizedBox(height: 14),
                      Container(
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: AppColors.border),
                        ),
                        child: Column(
                          children: [
                            Image.network(
                              'https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=BAKNUS-DRESTO-ORDER-${widget.order.orderNumber}',
                              width: 170,
                              height: 170,
                              errorBuilder: (context, error, stackTrace) => Container(
                                width: 170,
                                height: 170,
                                color: Colors.grey.shade100,
                                child: const Column(
                                  mainAxisAlignment: MainAxisAlignment.center,
                                  children: [
                                    Icon(Icons.qr_code_2, size: 80, color: AppColors.primary),
                                    Text('QRIS SIMULATOR', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 11)),
                                  ],
                                ),
                              ),
                            ),
                            const SizedBox(height: 8),
                            const Text('NMID: ID1020038475932', style: TextStyle(fontSize: 10, color: AppColors.textMuted, fontWeight: FontWeight.bold)),
                          ],
                        ),
                      ),
                      const SizedBox(height: 10),
                      const Text('Klik tombol bayar di bawah untuk simulasi penyelesaian pembayaran instan.', style: TextStyle(fontSize: 11, color: AppColors.textMuted), textAlign: TextAlign.center),
                    ],
                  ),
                ),

              // List of methods
              ..._paymentMethods.map((pm) {
                final isSelected = _selectedMethod == pm['id'];
                return Container(
                  margin: const EdgeInsets.only(bottom: 8),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(
                      color: isSelected ? AppColors.accent : AppColors.border,
                      width: isSelected ? 1.5 : 1.0,
                    ),
                  ),
                  child: ListTile(
                    contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 2),
                    leading: Container(
                      padding: const EdgeInsets.all(8),
                      decoration: BoxDecoration(
                        color: isSelected ? AppColors.accent.withOpacity(0.15) : Colors.grey.shade100,
                        shape: BoxShape.circle,
                      ),
                      child: Icon(pm['icon'] as IconData, color: isSelected ? AppColors.accentHover : AppColors.primary, size: 20),
                    ),
                    title: Text(pm['name'], style: TextStyle(fontWeight: isSelected ? FontWeight.bold : FontWeight.w500, fontSize: 13)),
                    subtitle: Text(pm['category'], style: const TextStyle(fontSize: 10, color: AppColors.textMuted)),
                    trailing: Radio<String>(
                      value: pm['id'],
                      groupValue: _selectedMethod,
                      activeColor: AppColors.accentHover,
                      onChanged: (val) {
                        setState(() => _selectedMethod = val!);
                      },
                    ),
                    onTap: () {
                      setState(() => _selectedMethod = pm['id']);
                    },
                  ),
                );
              }),

              const SizedBox(height: 20),

              // Tombol Konfirmasi Bayar
              SizedBox(
                width: double.infinity,
                height: 52,
                child: ElevatedButton.icon(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppColors.success,
                    foregroundColor: Colors.white,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                    elevation: 0,
                  ),
                  icon: _isProcessing
                      ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                      : const Icon(Icons.check_circle_outline, size: 22),
                  label: Text(
                    _isProcessing ? 'Memproses Pembayaran..' : 'Konfirmasi Bayar (${currencyFormatter.format(widget.order.totalAmount)})',
                    style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 14),
                  ),
                  onPressed: _isProcessing ? null : _processPayment,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
