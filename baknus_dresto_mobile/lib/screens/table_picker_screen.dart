import 'package:flutter/material.dart';
import '../models/table.dart';
import '../services/api_service.dart';
import '../theme/app_colors.dart';
import 'qr_scanner_screen.dart';

class TablePickerScreen extends StatefulWidget {
  final RestaurantTableModel? selectedTable;
  final bool isTabScreen;
  final ValueChanged<RestaurantTableModel>? onTablePicked;

  const TablePickerScreen({
    super.key,
    this.selectedTable,
    this.isTabScreen = false,
    this.onTablePicked,
  });

  @override
  State<TablePickerScreen> createState() => _TablePickerScreenState();
}

class _TablePickerScreenState extends State<TablePickerScreen> {
  final ApiService _api = ApiService();
  List<RestaurantTableModel> _tables = [];
  bool _isLoading = true;
  RestaurantTableModel? _currentSelection;

  @override
  void initState() {
    super.initState();
    _currentSelection = widget.selectedTable;
    _loadTables();
  }

  Future<void> _loadTables() async {
    setState(() => _isLoading = true);
    final tables = await _api.getTables();
    setState(() {
      _tables = tables;
      _isLoading = false;
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: AppColors.primary,
        foregroundColor: Colors.white,
        automaticallyImplyLeading: !widget.isTabScreen,
        title: const Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Pilih Meja Restoran', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
            Text('Fitur Self-Order Tanpa Kasir', style: TextStyle(fontSize: 11, color: Colors.white70)),
          ],
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.qr_code_scanner),
            tooltip: 'Scan Barcode Meja',
            onPressed: () async {
              final table = await Navigator.push<RestaurantTableModel>(
                context,
                MaterialPageRoute(builder: (_) => const QrScannerScreen()),
              );
              if (table != null && mounted) {
                if (widget.isTabScreen) {
                  widget.onTablePicked?.call(table);
                  setState(() => _currentSelection = table);
                } else {
                  Navigator.pop(context, table);
                }
              }
            },
          ),
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _loadTables,
            tooltip: 'Segarkan Meja',
          ),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: AppColors.accent))
          : Column(
              children: [
                // Banner Informasi Fitur
                Container(
                  padding: const EdgeInsets.all(14),
                  margin: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: AppColors.border),
                    boxShadow: [
                      BoxShadow(color: Colors.black.withOpacity(0.03), blurRadius: 8, offset: const Offset(0, 2))
                    ],
                  ),
                  child: Column(
                    children: [
                      Row(
                        children: [
                          Container(
                            padding: const EdgeInsets.all(8),
                            decoration: BoxDecoration(
                              color: AppColors.accent.withOpacity(0.15),
                              shape: BoxShape.circle,
                            ),
                            child: const Icon(Icons.restaurant, color: AppColors.accent, size: 20),
                          ),
                          const SizedBox(width: 12),
                          const Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text('Pesan & Bayar di Meja', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: AppColors.textDark)),
                                SizedBox(height: 2),
                                Text('Pilih meja yang tersedia. Saat pembayaran selesai, meja ini otomatis berubah menjadi status BOOKING.', style: TextStyle(fontSize: 11, color: AppColors.textMuted)),
                              ],
                            ),
                          )
                        ],
                      ),
                      const SizedBox(height: 12),
                      const Divider(height: 1),
                      const SizedBox(height: 10),
                      // Legend status meja
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceAround,
                        children: [
                          _buildLegend(AppColors.success, 'Tersedia'),
                          _buildLegend(AppColors.accent, 'Booking'),
                          _buildLegend(AppColors.danger, 'Terisi'),
                        ],
                      ),
                      const SizedBox(height: 12),
                      // Tombol Scan Barcode Meja
                      SizedBox(
                        width: double.infinity,
                        height: 38,
                        child: ElevatedButton.icon(
                          style: ElevatedButton.styleFrom(
                            backgroundColor: AppColors.accent,
                            foregroundColor: Colors.black,
                            elevation: 0,
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                          ),
                          icon: const Icon(Icons.qr_code_scanner, size: 16),
                          label: const Text('Scan QR Code di Atas Meja', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 11)),
                          onPressed: () async {
                            final table = await Navigator.push<RestaurantTableModel>(
                              context,
                              MaterialPageRoute(builder: (_) => const QrScannerScreen()),
                            );
                            if (table != null && mounted) {
                              if (widget.isTabScreen) {
                                widget.onTablePicked?.call(table);
                                setState(() => _currentSelection = table);
                              } else {
                                Navigator.pop(context, table);
                              }
                            }
                          },
                        ),
                      ),
                    ],
                  ),
                ),

                // Grid Meja Restoran
                Expanded(
                  child: RefreshIndicator(
                    onRefresh: _loadTables,
                    child: GridView.builder(
                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                        crossAxisCount: 2,
                        childAspectRatio: 1.15,
                        crossAxisSpacing: 12,
                        mainAxisSpacing: 12,
                      ),
                      itemCount: _tables.length,
                      itemBuilder: (context, index) {
                        final table = _tables[index];
                        final isSelected = _currentSelection?.id == table.id;

                        Color statusColor;
                        String statusLabel;
                        IconData statusIcon;

                        if (table.isAvailable) {
                          statusColor = AppColors.success;
                          statusLabel = 'Tersedia';
                          statusIcon = Icons.check_circle_outline;
                        } else if (table.isReserved) {
                          statusColor = AppColors.accent;
                          statusLabel = 'Booking (Booked)';
                          statusIcon = Icons.bookmark_added;
                        } else {
                          statusColor = AppColors.danger;
                          statusLabel = 'Terisi Penuh';
                          statusIcon = Icons.cancel_outlined;
                        }

                        return InkWell(
                          onTap: table.isAvailable
                              ? () {
                                  setState(() {
                                    _currentSelection = table;
                                  });
                                }
                              : () {
                                  ScaffoldMessenger.of(context).showSnackBar(
                                    SnackBar(
                                      content: Text('Maaf, ${table.tableNumber} saat ini berstatus $statusLabel.'),
                                      backgroundColor: AppColors.danger,
                                    ),
                                  );
                                },
                          borderRadius: BorderRadius.circular(16),
                          child: AnimatedContainer(
                            duration: const Duration(milliseconds: 200),
                            decoration: BoxDecoration(
                              color: isSelected ? AppColors.primary.withOpacity(0.04) : Colors.white,
                              borderRadius: BorderRadius.circular(16),
                              border: Border.all(
                                color: isSelected
                                    ? AppColors.primary
                                    : (table.isAvailable ? AppColors.border : Colors.grey.shade300),
                                width: isSelected ? 2.5 : 1.0,
                              ),
                              boxShadow: [
                                BoxShadow(
                                  color: Colors.black.withOpacity(0.02),
                                  blurRadius: 6,
                                  offset: const Offset(0, 2),
                                )
                              ],
                            ),
                            padding: const EdgeInsets.all(12),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Row(
                                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                  children: [
                                    Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                      decoration: BoxDecoration(
                                        color: statusColor.withOpacity(0.12),
                                        borderRadius: BorderRadius.circular(8),
                                      ),
                                      child: Row(
                                        mainAxisSize: MainAxisSize.min,
                                        children: [
                                          Icon(statusIcon, size: 12, color: statusColor),
                                          const SizedBox(width: 4),
                                          Text(
                                            statusLabel,
                                            style: TextStyle(
                                              fontSize: 10,
                                              fontWeight: FontWeight.bold,
                                              color: statusColor,
                                            ),
                                          ),
                                        ],
                                      ),
                                    ),
                                    if (isSelected)
                                      const Icon(Icons.check_circle, color: AppColors.primary, size: 18),
                                  ],
                                ),
                                Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      table.tableNumber,
                                      style: TextStyle(
                                        fontSize: 16,
                                        fontWeight: FontWeight.w900,
                                        color: table.isAvailable ? AppColors.textDark : Colors.grey,
                                      ),
                                    ),
                                    const SizedBox(height: 2),
                                    Text(
                                      '${table.floorSection} • ${table.capacity} Kursi',
                                      style: const TextStyle(fontSize: 11, color: AppColors.textMuted),
                                    ),
                                  ],
                                ),
                              ],
                            ),
                          ),
                        );
                      },
                    ),
                  ),
                ),

                // Bottom Action Bar
                Container(
                  padding: const EdgeInsets.all(16),
                  decoration: const BoxDecoration(
                    color: Colors.white,
                    border: Border(top: BorderSide(color: AppColors.border)),
                  ),
                  child: SafeArea(
                    child: SizedBox(
                      width: double.infinity,
                      height: 48,
                      child: ElevatedButton(
                        style: ElevatedButton.styleFrom(
                          backgroundColor: _currentSelection != null ? AppColors.primary : Colors.grey.shade400,
                          foregroundColor: Colors.white,
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                          elevation: 0,
                        ),
                        onPressed: _currentSelection != null
                            ? () {
                                if (widget.onTablePicked != null) {
                                  widget.onTablePicked!(_currentSelection!);
                                }
                                if (!widget.isTabScreen) {
                                  Navigator.pop(context, _currentSelection);
                                }
                              }
                            : null,
                        child: Text(
                          _currentSelection != null
                              ? 'Pilih ${_currentSelection!.tableNumber}'
                              : 'Silakan Pilih Meja',
                          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
                        ),
                      ),
                    ),
                  ),
                ),
              ],
            ),
    );
  }

  Widget _buildLegend(Color color, String label) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Container(
          width: 10,
          height: 10,
          decoration: BoxDecoration(color: color, shape: BoxShape.circle),
        ),
        const SizedBox(width: 6),
        Text(label, style: const TextStyle(fontSize: 11, color: AppColors.textMuted, fontWeight: FontWeight.w500)),
      ],
    );
  }
}
