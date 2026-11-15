import 'package:flutter/material.dart';
import 'package:mobile_scanner/mobile_scanner.dart';
import '../models/table.dart';
import '../services/api_service.dart';
import '../theme/app_colors.dart';

class QrScannerScreen extends StatefulWidget {
  const QrScannerScreen({super.key});

  @override
  State<QrScannerScreen> createState() => _QrScannerScreenState();
}

class _QrScannerScreenState extends State<QrScannerScreen>
    with SingleTickerProviderStateMixin {
  final ApiService _api = ApiService();
  late MobileScannerController _scannerController;
  late AnimationController _animCtrl;
  late Animation<double> _laserAnim;

  List<RestaurantTableModel> _tables = [];
  bool _isLoading = true;
  bool _isProcessingScan = false;
  bool _flashOn = false;

  @override
  void initState() {
    super.initState();
    _scannerController = MobileScannerController(
      detectionSpeed: DetectionSpeed.normal,
      facing: CameraFacing.back,
      torchEnabled: false,
    );

    _animCtrl = AnimationController(
      vsync: this,
      duration: const Duration(seconds: 2),
    )..repeat(reverse: true);

    _laserAnim = Tween<double>(begin: 0.0, end: 1.0).animate(
      CurvedAnimation(parent: _animCtrl, curve: Curves.easeInOut),
    );

    _loadTables();
  }

  @override
  void dispose() {
    _animCtrl.dispose();
    _scannerController.dispose();
    super.dispose();
  }

  Future<void> _loadTables() async {
    final tables = await _api.getTables();
    if (mounted) {
      setState(() {
        _tables = tables;
        _isLoading = false;
      });
    }
  }

  void _onBarcodeDetected(BarcodeCapture capture) {
    if (_isProcessingScan) return;

    final List<Barcode> barcodes = capture.barcodes;
    for (final barcode in barcodes) {
      final raw = barcode.rawValue;
      if (raw != null && raw.trim().isNotEmpty) {
        setState(() => _isProcessingScan = true);
        _handleRawData(raw);
        break;
      }
    }
  }

  void _handleRawData(String raw) {
    if (_tables.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Sedang menyinkronkan data meja dari server...'),
          backgroundColor: AppColors.warning,
        ),
      );
      setState(() => _isProcessingScan = false);
      return;
    }

    final cleaned = raw.trim();
    RestaurantTableModel? matchedTable;

    // 1. Cek jika raw data berupa URL: http://.../order/meja/1
    final urlMatch = RegExp(r'/order/meja/(\d+)').firstMatch(cleaned);
    if (urlMatch != null) {
      final id = int.tryParse(urlMatch.group(1) ?? '');
      if (id != null) {
        matchedTable = _tables.where((t) => t.id == id).firstOrNull;
      }
    }

    // 2. Cek jika raw data berupa ID numerik langsung
    if (matchedTable == null) {
      final idNum = int.tryParse(cleaned);
      if (idNum != null) {
        matchedTable = _tables.where((t) => t.id == idNum).firstOrNull;
      }
    }

    // 3. Cek jika raw data berupa nama nomor meja (misal "Meja 01", "01", "Meja 1")
    if (matchedTable == null) {
      final lower = cleaned.toLowerCase();
      matchedTable = _tables.where((t) {
        final tNum = t.tableNumber.toLowerCase();
        return tNum == lower ||
            tNum.replaceAll('meja', '').trim() == lower.replaceAll('meja', '').trim();
      }).firstOrNull;
    }

    if (matchedTable != null) {
      _showScanSuccess(matchedTable);
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Kode QR "$cleaned" tidak terdaftar sebagai meja Baknus Dresto.'),
          backgroundColor: AppColors.danger,
        ),
      );
      // Tunggu sebentar sebelum scan berikutnya diperbolehkan
      Future.delayed(const Duration(seconds: 2), () {
        if (mounted) setState(() => _isProcessingScan = false);
      });
    }
  }

  void _showScanSuccess(RestaurantTableModel table) {
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (_) => Container(
        padding: const EdgeInsets.all(24),
        decoration: const BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.vertical(top: Radius.circular(28)),
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            // Handle Bar
            Container(
              width: 40,
              height: 4,
              decoration: BoxDecoration(
                color: Colors.grey.shade300,
                borderRadius: BorderRadius.circular(2),
              ),
            ),
            const SizedBox(height: 20),

            // Success Icon
            Container(
              width: 64,
              height: 64,
              decoration: BoxDecoration(
                color: AppColors.success.withOpacity(0.12),
                shape: BoxShape.circle,
                border: Border.all(color: AppColors.success.withOpacity(0.3)),
              ),
              child: const Icon(Icons.check_circle, color: AppColors.success, size: 36),
            ),
            const SizedBox(height: 14),

            const Text(
              'QR Meja Terverifikasi!',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.w900, color: AppColors.textDark),
            ),
            const SizedBox(height: 4),
            Text(
              'Kamera asli berhasil membaca barcode meja fisik.',
              style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
            ),
            const SizedBox(height: 18),

            // Card Table Info
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: AppColors.background,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: AppColors.border),
              ),
              child: Row(
                children: [
                  Container(
                    width: 50,
                    height: 50,
                    decoration: BoxDecoration(
                      color: AppColors.accent.withOpacity(0.2),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: const Icon(Icons.table_restaurant, color: AppColors.accent, size: 28),
                  ),
                  const SizedBox(width: 14),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          table.tableNumber,
                          style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 16, color: AppColors.textDark),
                        ),
                        const SizedBox(height: 2),
                        Text(
                          '${table.floorSection} • Kapasitas ${table.capacity} Kursi',
                          style: const TextStyle(fontSize: 12, color: AppColors.textMuted),
                        ),
                      ],
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                    decoration: BoxDecoration(
                      color: table.isAvailable
                          ? AppColors.success.withOpacity(0.15)
                          : AppColors.danger.withOpacity(0.15),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Text(
                      table.status.toUpperCase(),
                      style: TextStyle(
                        fontSize: 10,
                        fontWeight: FontWeight.w900,
                        color: table.isAvailable ? AppColors.success : AppColors.danger,
                      ),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 22),

            // Confirm Button
            SizedBox(
              width: double.infinity,
              height: 50,
              child: ElevatedButton(
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppColors.accent,
                  foregroundColor: Colors.black,
                  elevation: 0,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                ),
                onPressed: () {
                  Navigator.pop(context); // Tutup bottom sheet
                  Navigator.pop(context, table); // Kembalikan table ke HomeScreen
                },
                child: const Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Text('Gunakan Meja Ini & Mulai Pesan', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                    SizedBox(width: 8),
                    Icon(Icons.arrow_forward, size: 16),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    ).then((_) {
      // Izinkan scan kembali jika bottom sheet ditutup
      if (mounted) {
        setState(() => _isProcessingScan = false);
      }
    });
  }

  void _openManualInputDialog() {
    final textCtrl = TextEditingController();
    showDialog(
      context: context,
      builder: (_) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18)),
        title: const Row(
          children: [
            Icon(Icons.qr_code_scanner, color: AppColors.accent),
            SizedBox(width: 8),
            Text('Input Link / Kode Meja', style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold)),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Masukkan nomor meja (misal "01") atau paste link QR dari web POS:',
              style: TextStyle(fontSize: 11, color: AppColors.textMuted),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: textCtrl,
              autofocus: true,
              decoration: InputDecoration(
                hintText: 'Contoh: 1 atau http://.../order/meja/1',
                hintStyle: const TextStyle(fontSize: 12),
                filled: true,
                fillColor: AppColors.background,
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Batal'),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: AppColors.accent,
              foregroundColor: Colors.black,
            ),
            onPressed: () {
              Navigator.pop(context);
              if (textCtrl.text.trim().isNotEmpty) {
                _handleRawData(textCtrl.text.trim());
              }
            },
            child: const Text('Proses'),
          ),
        ],
      ),
    );
  }

  void _openQuickTablePicker() {
    showModalBottomSheet(
      context: context,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
      builder: (_) => Container(
        padding: const EdgeInsets.all(20),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Simulasi Scan QR Meja (Ujikom)',
              style: TextStyle(fontSize: 16, fontWeight: FontWeight.w900, color: AppColors.textDark),
            ),
            const SizedBox(height: 4),
            const Text(
              'Pilih meja restoran yang ingin Anda simulasikan hasil scannya:',
              style: TextStyle(fontSize: 12, color: AppColors.textMuted),
            ),
            const SizedBox(height: 16),
            if (_tables.isEmpty)
              const Center(child: Padding(padding: EdgeInsets.all(20), child: CircularProgressIndicator()))
            else
              GridView.builder(
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                  crossAxisCount: 3,
                  crossAxisSpacing: 10,
                  mainAxisSpacing: 10,
                  childAspectRatio: 1.4,
                ),
                itemCount: _tables.length,
                itemBuilder: (ctx, i) {
                  final t = _tables[i];
                  return InkWell(
                    onTap: () {
                      Navigator.pop(context);
                      _showScanSuccess(t);
                    },
                    borderRadius: BorderRadius.circular(12),
                    child: Container(
                      decoration: BoxDecoration(
                        color: t.isAvailable ? AppColors.accent.withOpacity(0.12) : Colors.grey.shade100,
                        border: Border.all(
                          color: t.isAvailable ? AppColors.accent : Colors.grey.shade300,
                          width: 1.5,
                        ),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Text(
                            t.tableNumber,
                            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: AppColors.textDark),
                          ),
                          Text(
                            '${t.capacity} Kursi',
                            style: const TextStyle(fontSize: 10, color: AppColors.textMuted),
                          ),
                        ],
                      ),
                    ),
                  );
                },
              ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.black,
      body: Stack(
        children: [
          // 1. LIVE HARDWARE CAMERA SCANNER
          Positioned.fill(
            child: MobileScanner(
              controller: _scannerController,
              onDetect: _onBarcodeDetected,
              errorBuilder: (context, error, child) {
                return Center(
                  child: Padding(
                    padding: const EdgeInsets.all(24.0),
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        const Icon(Icons.videocam_off, color: Colors.white54, size: 64),
                        const SizedBox(height: 16),
                        const Text(
                          'Kamera Tidak Terdeteksi',
                          style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 16),
                        ),
                        const SizedBox(height: 6),
                        Text(
                          'Jika di emulator/PC tanpa webcam, gunakan tombol Simulasi Scan di bawah.',
                          style: TextStyle(color: Colors.grey.shade400, fontSize: 12),
                          textAlign: TextAlign.center,
                        ),
                      ],
                    ),
                  ),
                );
              },
            ),
          ),

          // 2. SCANNER CAMERA VIEWFINDER OVERLAY
          Center(
            child: Container(
              width: 260,
              height: 260,
              decoration: BoxDecoration(
                border: Border.all(color: AppColors.accent.withOpacity(0.8), width: 2),
                borderRadius: BorderRadius.circular(24),
              ),
              child: Stack(
                children: [
                  // Corner brackets
                  Positioned(
                    top: 0,
                    left: 0,
                    child: Container(
                      width: 28,
                      height: 28,
                      decoration: const BoxDecoration(
                        border: Border(
                          top: BorderSide(color: AppColors.accent, width: 4),
                          left: BorderSide(color: AppColors.accent, width: 4),
                        ),
                        borderRadius: BorderRadius.only(topLeft: Radius.circular(20)),
                      ),
                    ),
                  ),
                  Positioned(
                    top: 0,
                    right: 0,
                    child: Container(
                      width: 28,
                      height: 28,
                      decoration: const BoxDecoration(
                        border: Border(
                          top: BorderSide(color: AppColors.accent, width: 4),
                          right: BorderSide(color: AppColors.accent, width: 4),
                        ),
                        borderRadius: BorderRadius.only(topRight: Radius.circular(20)),
                      ),
                    ),
                  ),
                  Positioned(
                    bottom: 0,
                    left: 0,
                    child: Container(
                      width: 28,
                      height: 28,
                      decoration: const BoxDecoration(
                        border: Border(
                          bottom: BorderSide(color: AppColors.accent, width: 4),
                          left: BorderSide(color: AppColors.accent, width: 4),
                        ),
                        borderRadius: BorderRadius.only(bottomLeft: Radius.circular(20)),
                      ),
                    ),
                  ),
                  Positioned(
                    bottom: 0,
                    right: 0,
                    child: Container(
                      width: 28,
                      height: 28,
                      decoration: const BoxDecoration(
                        border: Border(
                          bottom: BorderSide(color: AppColors.accent, width: 4),
                          right: BorderSide(color: AppColors.accent, width: 4),
                        ),
                        borderRadius: BorderRadius.only(bottomRight: Radius.circular(20)),
                      ),
                    ),
                  ),

                  // Animated Scanning Laser Line
                  AnimatedBuilder(
                    animation: _laserAnim,
                    builder: (ctx, child) {
                      return Positioned(
                        top: _laserAnim.value * 230 + 10,
                        left: 10,
                        right: 10,
                        child: Container(
                          height: 3,
                          decoration: BoxDecoration(
                            gradient: const LinearGradient(
                              colors: [Colors.transparent, AppColors.accent, Colors.white, AppColors.accent, Colors.transparent],
                            ),
                            boxShadow: [
                              BoxShadow(
                                color: AppColors.accent.withOpacity(0.8),
                                blurRadius: 10,
                                spreadRadius: 2,
                              ),
                            ],
                          ),
                        ),
                      );
                    },
                  ),
                ],
              ),
            ),
          ),

          // 3. TOP APP BAR OVERLAY
          Positioned(
            top: 0,
            left: 0,
            right: 0,
            child: SafeArea(
              child: Padding(
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    IconButton(
                      icon: const Icon(Icons.arrow_back, color: Colors.white),
                      onPressed: () => Navigator.pop(context),
                    ),
                    const Column(
                      children: [
                        Text(
                          'Scan QR Code Meja',
                          style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 16),
                        ),
                        Text(
                          'Arahkan kamera ke QR Meja',
                          style: TextStyle(color: AppColors.accent, fontSize: 11),
                        ),
                      ],
                    ),
                    Row(
                      children: [
                        // Flashlight Toggle
                        IconButton(
                          icon: Icon(
                            _flashOn ? Icons.flash_on : Icons.flash_off,
                            color: _flashOn ? AppColors.accent : Colors.white70,
                          ),
                          onPressed: () async {
                            await _scannerController.toggleTorch();
                            setState(() => _flashOn = !_flashOn);
                          },
                        ),
                        // Switch Camera Front/Back
                        IconButton(
                          icon: const Icon(Icons.flip_camera_ios, color: Colors.white70),
                          onPressed: () => _scannerController.switchCamera(),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
          ),

          // 4. BOTTOM INSTRUCTIONS & ACTION BUTTONS
          Positioned(
            bottom: 0,
            left: 0,
            right: 0,
            child: SafeArea(
              child: Container(
                padding: const EdgeInsets.fromLTRB(24, 16, 24, 24),
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    begin: Alignment.bottomCenter,
                    end: Alignment.topCenter,
                    colors: [Colors.black.withOpacity(0.95), Colors.transparent],
                  ),
                ),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const Text(
                      'Posisikan QR Code meja di dalam kotak bidik kamera',
                      style: TextStyle(color: Colors.white, fontSize: 13, fontWeight: FontWeight.bold),
                      textAlign: TextAlign.center,
                    ),
                    const SizedBox(height: 6),
                    Text(
                      'Kamera akan otomatis mendeteksi barcode secara instan.',
                      style: TextStyle(color: Colors.grey.shade400, fontSize: 11),
                      textAlign: TextAlign.center,
                    ),
                    const SizedBox(height: 18),

                    // Quick Action Buttons
                    Row(
                      children: [
                        // Button Simulasi Meja (Bila sedang diuji tanpa kamera fisik)
                        Expanded(
                          child: ElevatedButton.icon(
                            style: ElevatedButton.styleFrom(
                              backgroundColor: AppColors.accent,
                              foregroundColor: Colors.black,
                              padding: const EdgeInsets.symmetric(vertical: 12),
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                            ),
                            icon: const Icon(Icons.touch_app, size: 18),
                            label: const Text('Simulasi Scan', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
                            onPressed: _openQuickTablePicker,
                          ),
                        ),
                        const SizedBox(width: 10),

                        // Input Manual / Paste Link
                        Expanded(
                          child: OutlinedButton.icon(
                            style: OutlinedButton.styleFrom(
                              foregroundColor: Colors.white,
                              side: const BorderSide(color: Colors.white38),
                              padding: const EdgeInsets.symmetric(vertical: 12),
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                            ),
                            icon: const Icon(Icons.edit, size: 18),
                            label: const Text('Input Kode', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
                            onPressed: _openManualInputDialog,
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
