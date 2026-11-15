import 'dart:async';
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../models/order.dart';
import '../theme/app_colors.dart';
import 'main_navigation_screen.dart';

class DeliveryTrackingScreen extends StatefulWidget {
  final OrderModel order;

  const DeliveryTrackingScreen({super.key, required this.order});

  @override
  State<DeliveryTrackingScreen> createState() => _DeliveryTrackingScreenState();
}

class _DeliveryTrackingScreenState extends State<DeliveryTrackingScreen> with SingleTickerProviderStateMixin {
  // Total durasi simulasi pengantaran: 2 Menit (120 Detik)
  static const int totalSimulationSeconds = 120;
  int _secondsPassed = 0;
  Timer? _countdownTimer;

  late AnimationController _animController;

  @override
  void initState() {
    super.initState();
    _animController = AnimationController(
      vsync: this,
      duration: const Duration(seconds: 2),
    )..repeat(reverse: true);

    _startTwoMinuteSimulation();
  }

  @override
  void dispose() {
    _countdownTimer?.cancel();
    _animController.dispose();
    super.dispose();
  }

  void _startTwoMinuteSimulation() {
    _countdownTimer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (_secondsPassed >= totalSimulationSeconds) {
        timer.cancel();
      } else {
        setState(() {
          _secondsPassed++;
        });
      }
    });
  }

  // 3 Tahapan:
  // Step 1: Makanan Disiapkan (Detik 0 - 40)
  // Step 2: Makanan Diantar (Detik 41 - 100)
  // Step 3: Makanan Diterima (Detik 101 - 120)
  int get _currentStepIndex {
    if (_secondsPassed <= 40) return 1;
    if (_secondsPassed <= 100) return 2;
    return 3;
  }

  String get _currentStepTitle {
    switch (_currentStepIndex) {
      case 1:
        return 'Makanan Disiapkan';
      case 2:
        return 'Makanan Diantar Driver';
      case 3:
        return 'Makanan Diterima';
      default:
        return 'Makanan Disiapkan';
    }
  }

  String get _currentStepDescription {
    switch (_currentStepIndex) {
      case 1:
        return 'Pesanan telah lunas & stok terpotong! Koki Baknus Dresto sedang memasak hidangan hangat Anda.';
      case 2:
        return 'Makanan selesai dimasak! Driver Pak Budi sedang dalam perjalanan membawa pesanan ke alamat Anda.';
      case 3:
        return 'Driver telah tiba di alamat tujuan! Makanan telah diserahkan dengan selamat. Selamat menikmati!';
      default:
        return '';
    }
  }

  int get _remainingSeconds => (totalSimulationSeconds - _secondsPassed).clamp(0, totalSimulationSeconds);

  String get _formattedCountdown {
    final m = _remainingSeconds ~/ 60;
    final s = _remainingSeconds % 60;
    return '${m.toString().padLeft(2, '0')}:${s.toString().padLeft(2, '0')}';
  }

  double get _progressPercentage => (_secondsPassed / totalSimulationSeconds).clamp(0.0, 1.0);

  // Tombol untuk percepat ke step berikutnya
  void _skipToNextStep() {
    setState(() {
      if (_secondsPassed < 40) {
        _secondsPassed = 41;
      } else if (_secondsPassed < 100) {
        _secondsPassed = 101;
      } else {
        _secondsPassed = totalSimulationSeconds;
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    final currencyFormatter = NumberFormat.currency(locale: 'id_ID', symbol: 'Rp ', decimalDigits: 0);
    final order = widget.order;

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: AppColors.primary,
        foregroundColor: Colors.white,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back),
          tooltip: 'Kembali ke Menu',
          onPressed: () {
            Navigator.pushAndRemoveUntil(
              context,
              MaterialPageRoute(builder: (_) => const MainNavigationScreen()),
              (route) => false,
            );
          },
        ),
        title: const Text('Pengantaran (2 Mnt)', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
        titleSpacing: 0,
        actions: [
          TextButton.icon(
            style: TextButton.styleFrom(
              foregroundColor: AppColors.accent,
              padding: const EdgeInsets.symmetric(horizontal: 8),
            ),
            icon: const Icon(Icons.fast_forward, size: 14),
            label: const Text('Percepat', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 11)),
            onPressed: _secondsPassed >= totalSimulationSeconds ? null : _skipToNextStep,
          )
        ],
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(14),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // BANNER HITUNG MUNDUR (COUNTDOWN 2 MENIT)
              Container(
                width: double.infinity,
                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
                decoration: BoxDecoration(
                  gradient: const LinearGradient(
                    colors: [Color(0xFF0C233C), Color(0xFF1B436E)],
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                  ),
                  borderRadius: BorderRadius.circular(16),
                  boxShadow: [
                    BoxShadow(color: AppColors.primary.withValues(alpha: 0.25), blurRadius: 10, offset: const Offset(0, 4))
                  ],
                ),
                child: Column(
                  children: [
                    Row(
                      crossAxisAlignment: CrossAxisAlignment.center,
                      children: [
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text(
                                'ESTIMASI PENGANTARAN (2 MENIT)',
                                style: TextStyle(color: Colors.white60, fontSize: 9.5, fontWeight: FontWeight.bold),
                                overflow: TextOverflow.ellipsis,
                              ),
                              const SizedBox(height: 3),
                              Text(
                                _remainingSeconds > 0 ? _formattedCountdown : '00:00 (Selesai)',
                                style: const TextStyle(color: Colors.white, fontSize: 24, fontWeight: FontWeight.w900, letterSpacing: 1),
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(width: 8),
                        // Badge Status Step
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                          decoration: BoxDecoration(
                            color: _currentStepIndex == 3 ? AppColors.success : AppColors.accent,
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Text(
                            'Step $_currentStepIndex / 3',
                            style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 11, color: Colors.black),
                          ),
                        ),
                      ],
                    ),

                    const SizedBox(height: 16),

                    // Progress Bar Dinamis
                    ClipRRect(
                      borderRadius: BorderRadius.circular(8),
                      child: LinearProgressIndicator(
                        value: _progressPercentage,
                        minHeight: 8,
                        backgroundColor: Colors.white24,
                        valueColor: AlwaysStoppedAnimation<Color>(
                          _currentStepIndex == 3 ? AppColors.success : AppColors.accent,
                        ),
                      ),
                    ),

                    const SizedBox(height: 14),

                    // Keterangan Status Berjalan
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                      decoration: BoxDecoration(
                        color: Colors.black26,
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Row(
                        children: [
                          Icon(
                            _currentStepIndex == 3 ? Icons.check_circle : Icons.hourglass_top,
                            size: 16,
                            color: _currentStepIndex == 3 ? AppColors.success : AppColors.accent,
                          ),
                          const SizedBox(width: 8),
                          Expanded(
                            child: Text(
                              _currentStepDescription,
                              style: const TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.w500),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),

              const SizedBox(height: 20),

              // WIDGET ANIMASI SEDERHANA SESUAI TAHAP PENGANTARAN
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(20),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(20),
                  border: Border.all(color: AppColors.border),
                  boxShadow: [
                    BoxShadow(color: Colors.black.withValues(alpha: 0.03), blurRadius: 8, offset: const Offset(0, 2))
                  ],
                ),
                child: Column(
                  children: [
                    Text(
                      _currentStepTitle,
                      style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 16, color: AppColors.textDark),
                    ),
                    const SizedBox(height: 14),

                    // AREA ANIMASI
                    SizedBox(
                      height: 100,
                      width: double.infinity,
                      child: Stack(
                        alignment: Alignment.center,
                        children: [
                          // Jalur lintasan jalan
                          Positioned(
                            bottom: 24,
                            left: 20,
                            right: 20,
                            child: Container(
                              height: 4,
                              decoration: BoxDecoration(
                                color: Colors.grey.shade200,
                                borderRadius: BorderRadius.circular(2),
                              ),
                            ),
                          ),

                          // JIKA STEP 1: Makanan Disiapkan (Animasi Wajan/Koki)
                          if (_currentStepIndex == 1)
                            AnimatedBuilder(
                              animation: _animController,
                              builder: (context, child) {
                                return Transform.scale(
                                  scale: 1.0 + (_animController.value * 0.15),
                                  child: Container(
                                    padding: const EdgeInsets.all(16),
                                    decoration: BoxDecoration(
                                      color: AppColors.accent.withValues(alpha: 0.15),
                                      shape: BoxShape.circle,
                                      border: Border.all(color: AppColors.accent, width: 2),
                                    ),
                                    child: const Icon(Icons.soup_kitchen, size: 40, color: AppColors.accentHover),
                                  ),
                                );
                              },
                            ),

                          // JIKA STEP 2: Makanan Diantar (Animasi Motor Driver Berjalan di Lintasan)
                          if (_currentStepIndex == 2)
                            AnimatedAlign(
                              alignment: Alignment(
                                -0.8 + ((_secondsPassed - 40) / 60) * 1.6, // Motor berjalan dari kiri ke kanan
                                0.2,
                              ),
                              duration: const Duration(seconds: 1),
                              child: Container(
                                padding: const EdgeInsets.all(12),
                                decoration: BoxDecoration(
                                  color: AppColors.primary,
                                  shape: BoxShape.circle,
                                  boxShadow: [
                                    BoxShadow(color: AppColors.primary.withValues(alpha: 0.3), blurRadius: 8, offset: const Offset(0, 4))
                                  ],
                                ),
                                child: const Icon(Icons.two_wheeler, size: 28, color: Colors.white),
                              ),
                            ),

                          // JIKA STEP 3: Makanan Diterima (Animasi Centang Sukses)
                          if (_currentStepIndex == 3)
                            AnimatedBuilder(
                              animation: _animController,
                              builder: (context, child) {
                                return Transform.scale(
                                  scale: 1.0 + (_animController.value * 0.08),
                                  child: Container(
                                    padding: const EdgeInsets.all(16),
                                    decoration: BoxDecoration(
                                      color: AppColors.success.withValues(alpha: 0.15),
                                      shape: BoxShape.circle,
                                      border: Border.all(color: AppColors.success, width: 2),
                                    ),
                                    child: const Icon(Icons.task_alt, size: 44, color: AppColors.success),
                                  ),
                                );
                              },
                            ),

                          // Ikon Rumah Tujuan di Ujung Kanan
                          Positioned(
                            bottom: 12,
                            right: 16,
                            child: Icon(
                              Icons.home,
                              size: 28,
                              color: _currentStepIndex == 3 ? AppColors.success : Colors.grey.shade400,
                            ),
                          ),

                          // Ikon Restoran di Ujung Kiri
                          Positioned(
                            bottom: 12,
                            left: 16,
                            child: Icon(
                              Icons.storefront,
                              size: 28,
                              color: _currentStepIndex == 1 ? AppColors.accent : Colors.grey.shade400,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),

              const SizedBox(height: 20),

              // DETAIL 3 TAHAPAN (TIMELINE RESMI)
              Container(
                padding: const EdgeInsets.all(18),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: AppColors.border),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text('Tahapan Pengantaran Makanan', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: AppColors.textDark)),
                    const SizedBox(height: 14),
                    _buildStepRow(
                      stepNumber: 1,
                      title: 'Makanan Disiapkan (0:00 - 0:40)',
                      subtitle: 'Pesanan telah dibayar, stok berkurang otomatis, dan dapur sedang memasak.',
                      isDone: _currentStepIndex > 1,
                      isActive: _currentStepIndex == 1,
                      icon: Icons.soup_kitchen,
                    ),
                    _buildStepRow(
                      stepNumber: 2,
                      title: 'Makanan Diantar (0:41 - 1:40)',
                      subtitle: 'Driver membawa pesanan langsung menuju ke alamat pengantaran.',
                      isDone: _currentStepIndex > 2,
                      isActive: _currentStepIndex == 2,
                      icon: Icons.delivery_dining,
                    ),
                    _buildStepRow(
                      stepNumber: 3,
                      title: 'Makanan Diterima (1:41 - 2:00)',
                      subtitle: 'Makanan telah sampai di tangan pelanggan. Pesanan selesai.',
                      isDone: _currentStepIndex == 3,
                      isActive: false,
                      icon: Icons.check_circle,
                      isLast: true,
                    ),
                  ],
                ),
              ),

              const SizedBox(height: 16),

              // ALAMAT & PATOKAN PENGANTARAN
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: AppColors.border),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text('Tujuan Pengantaran', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: AppColors.textDark)),
                    const SizedBox(height: 10),
                    Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Icon(Icons.location_on, size: 20, color: AppColors.danger),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(order.deliveryAddress ?? 'Alamat Pemesan', style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                              const SizedBox(height: 2),
                              Text('${order.customerName} (${order.customerPhone ?? "No HP"})', style: const TextStyle(fontSize: 11, color: AppColors.textMuted)),
                            ],
                          ),
                        ),
                      ],
                    ),
                    const Divider(height: 16),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text('Total Pembayaran (Lunas)', style: TextStyle(fontSize: 12, color: AppColors.textMuted)),
                        Text(currencyFormatter.format(order.totalAmount), style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: AppColors.primary)),
                      ],
                    ),
                  ],
                ),
              ),

              const SizedBox(height: 20),

              // Tombol Aksi
              if (_currentStepIndex == 3)
                SizedBox(
                  width: double.infinity,
                  height: 48,
                  child: ElevatedButton(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppColors.success,
                      foregroundColor: Colors.white,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    ),
                    onPressed: () {
                      Navigator.pushAndRemoveUntil(
                        context,
                        MaterialPageRoute(builder: (_) => const MainNavigationScreen()),
                        (route) => false,
                      );
                    },
                    child: const Text('Pesanan Selesai - Kembali ke Menu Utama', style: TextStyle(fontWeight: FontWeight.bold)),
                  ),
                )
              else
                SizedBox(
                  width: double.infinity,
                  height: 44,
                  child: OutlinedButton.icon(
                    style: OutlinedButton.styleFrom(
                      foregroundColor: AppColors.primary,
                      side: const BorderSide(color: AppColors.primary),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    ),
                    icon: const Icon(Icons.fast_forward, size: 16),
                    label: const Text('Percepat ke Step Berikutnya', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
                    onPressed: _skipToNextStep,
                  ),
                ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildStepRow({
    required int stepNumber,
    required String title,
    required String subtitle,
    required bool isDone,
    required bool isActive,
    required IconData icon,
    bool isLast = false,
  }) {
    Color color = isDone ? AppColors.success : (isActive ? AppColors.accent : Colors.grey.shade300);

    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Column(
          children: [
            Container(
              width: 30,
              height: 30,
              decoration: BoxDecoration(
                color: color.withValues(alpha: 0.15),
                shape: BoxShape.circle,
                border: Border.all(color: color, width: 2),
              ),
              child: Icon(isDone ? Icons.check : icon, size: 14, color: color),
            ),
            if (!isLast)
              Container(
                width: 2,
                height: 36,
                color: isDone ? AppColors.success : Colors.grey.shade200,
              ),
          ],
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                title,
                style: TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.bold,
                  color: (isDone || isActive) ? AppColors.textDark : Colors.grey.shade400,
                ),
              ),
              const SizedBox(height: 2),
              Text(
                subtitle,
                style: TextStyle(
                  fontSize: 10,
                  color: (isDone || isActive) ? AppColors.textMuted : Colors.grey.shade400,
                ),
              ),
              const SizedBox(height: 12),
            ],
          ),
        ),
      ],
    );
  }
}
