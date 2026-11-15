import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../models/user.dart';
import '../services/api_service.dart';
import '../theme/app_colors.dart';

class ProfileScreen extends StatefulWidget {
  final VoidCallback? onUserLoggedIn;

  const ProfileScreen({super.key, this.onUserLoggedIn});

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  final ApiService _api = ApiService();
  bool _isLoginMode = false;
  bool _isLoading = false;

  // Controllers Registrasi
  final _regNameCtrl = TextEditingController(text: 'Rian Hidayat');
  final _regPhoneCtrl = TextEditingController(text: '081234567891');
  final _regAddressCtrl = TextEditingController(text: 'Jl. Melati Indah Blok C No. 15');
  final _regBenchmarkCtrl = TextEditingController(text: 'Pagar hitam, samping warung Madura');

  // Controllers Login
  final _loginPhoneCtrl = TextEditingController();
  final _loginPassCtrl = TextEditingController();

  // Controllers OTP & Set Password Modal
  final _otpCtrl = TextEditingController();
  final _newPassCtrl = TextEditingController();
  final _confirmPassCtrl = TextEditingController();
  String _currentOtpSimulation = '';
  String _pendingPhone = '';

  @override
  void initState() {
    super.initState();
    _checkSession();
  }

  Future<void> _checkSession() async {
    final user = await _api.loadSavedUser();
    if (user != null) {
      setState(() {});
    }
  }

  // 1. Request OTP Registrasi
  Future<void> _handleRequestOtp() async {
    final name = _regNameCtrl.text.trim();
    final phone = _regPhoneCtrl.text.trim();
    final address = _regAddressCtrl.text.trim();
    final benchmark = _regBenchmarkCtrl.text.trim();

    if (name.isEmpty || phone.isEmpty || address.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Nama, No. Telepon, dan Alamat wajib diisi.'), backgroundColor: AppColors.danger),
      );
      return;
    }

    setState(() => _isLoading = true);

    final res = await _api.requestRegisterOtp(
      name: name,
      phone: phone,
      address: address,
      addressBenchmark: benchmark,
    );

    setState(() => _isLoading = false);

    if (res['success'] == true) {
      _currentOtpSimulation = res['otp_simulation'] ?? '1234';
      _pendingPhone = res['phone'] ?? phone;
      _otpCtrl.text = _currentOtpSimulation; // Otomatis terisi agar mempermudah testing simulasi

      if (!mounted) return;
      _showOtpAndPasswordModal();
    } else {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(res['message'] ?? 'Gagal meminta OTP'), backgroundColor: AppColors.danger),
      );
    }
  }

  // Modal Verifikasi OTP & Pembuatan Password
  void _showOtpAndPasswordModal() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => StatefulBuilder(
        builder: (context, setModalState) {
          return Container(
            padding: EdgeInsets.fromLTRB(20, 20, 20, MediaQuery.of(context).viewInsets.bottom + 20),
            decoration: const BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
            ),
            child: SingleChildScrollView(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Text('Verifikasi OTP & Buat Password', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                      IconButton(icon: const Icon(Icons.close), onPressed: () => Navigator.pop(ctx)),
                    ],
                  ),
                  const SizedBox(height: 8),

                  // KOTAK NOTIFIKASI SIMULASI SMS OTP
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: AppColors.accent.withValues(alpha: 0.15),
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: AppColors.accent),
                    ),
                    child: Row(
                      children: [
                        const Icon(Icons.sms, color: AppColors.accentHover, size: 24),
                        const SizedBox(width: 10),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text('SMS Masuk (Simulasi):', style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Colors.brown)),
                              Text(
                                'Kode OTP Anda: $_currentOtpSimulation (Berlaku 10 menit)',
                                style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: AppColors.textDark),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),

                  const SizedBox(height: 16),
                  const Text('Masukkan 4 Digit Kode OTP', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 6),
                  TextField(
                    controller: _otpCtrl,
                    keyboardType: TextInputType.number,
                    maxLength: 4,
                    textAlign: TextAlign.center,
                    style: const TextStyle(fontSize: 22, fontWeight: FontWeight.w900, letterSpacing: 8),
                    decoration: InputDecoration(
                      filled: true,
                      fillColor: AppColors.background,
                      counterText: '',
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
                      contentPadding: const EdgeInsets.symmetric(vertical: 10),
                    ),
                  ),

                  const SizedBox(height: 14),
                  const Text('Buat Password Akun Aplikasi', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 6),
                  TextField(
                    controller: _newPassCtrl,
                    obscureText: true,
                    decoration: InputDecoration(
                      prefixIcon: const Icon(Icons.lock_outline, size: 18),
                      hintText: 'Minimal 6 karakter',
                      filled: true,
                      fillColor: AppColors.background,
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
                      contentPadding: const EdgeInsets.all(12),
                    ),
                  ),

                  const SizedBox(height: 12),
                  const Text('Ulangi Password', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 6),
                  TextField(
                    controller: _confirmPassCtrl,
                    obscureText: true,
                    decoration: InputDecoration(
                      prefixIcon: const Icon(Icons.lock_outline, size: 18),
                      hintText: 'Ketik ulang password',
                      filled: true,
                      fillColor: AppColors.background,
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
                      contentPadding: const EdgeInsets.all(12),
                    ),
                  ),

                  const SizedBox(height: 20),

                  SizedBox(
                    width: double.infinity,
                    height: 48,
                    child: ElevatedButton(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppColors.primary,
                        foregroundColor: Colors.white,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                      onPressed: () async {
                        final otp = _otpCtrl.text.trim();
                        final pass = _newPassCtrl.text.trim();
                        final confirm = _confirmPassCtrl.text.trim();

                        if (otp.length != 4) {
                          ScaffoldMessenger.of(context).showSnackBar(
                            const SnackBar(content: Text('Kode OTP harus 4 digit.')),
                          );
                          return;
                        }

                        if (pass.length < 6) {
                          ScaffoldMessenger.of(context).showSnackBar(
                            const SnackBar(content: Text('Password minimal 6 karakter.')),
                          );
                          return;
                        }

                        if (pass != confirm) {
                          ScaffoldMessenger.of(context).showSnackBar(
                            const SnackBar(content: Text('Konfirmasi password tidak cocok.')),
                          );
                          return;
                        }

                        final messenger = ScaffoldMessenger.of(context);
                        Navigator.pop(ctx);
                        setState(() => _isLoading = true);

                        final res = await _api.verifyOtpAndSetPassword(
                          phone: _pendingPhone,
                          otpCode: otp,
                          password: pass,
                        );

                        setState(() => _isLoading = false);

                        if (res['success'] == true) {
                          messenger.showSnackBar(
                            const SnackBar(
                              content: Text('Pendaftaran Berhasil! Akun dan password telah aktif.'),
                              backgroundColor: AppColors.success,
                            ),
                          );
                          widget.onUserLoggedIn?.call();
                        } else {
                          messenger.showSnackBar(
                            SnackBar(content: Text(res['message'] ?? 'Gagal membuat akun'), backgroundColor: AppColors.danger),
                          );
                        }
                      },
                      child: const Text('Verifikasi & Selesaikan Pendaftaran', style: TextStyle(fontWeight: FontWeight.bold)),
                    ),
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  // 2. Handle Login
  Future<void> _handleLogin() async {
    final phone = _loginPhoneCtrl.text.trim();
    final pass = _loginPassCtrl.text.trim();

    if (phone.isEmpty || pass.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('No. HP dan Password wajib diisi.')),
      );
      return;
    }

    setState(() => _isLoading = true);
    final res = await _api.login(phone: phone, password: pass);
    setState(() => _isLoading = false);

    if (res['success'] == true) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(res['message'] ?? 'Login berhasil!'), backgroundColor: AppColors.success),
      );
      widget.onUserLoggedIn?.call();
    } else {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(res['message'] ?? 'Login gagal.'), backgroundColor: AppColors.danger),
      );
    }
  }

  // 3. Quick Login Simulasi
  Future<void> _quickLoginAs({
    required String name,
    required String phone,
    required String address,
    required String benchmark,
  }) async {
    setState(() => _isLoading = true);
    final user = UserModel(
      id: 99,
      name: name,
      phone: phone,
      address: address,
      addressBenchmark: benchmark,
      role: 'customer',
    );
    _api.currentUser = user;
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString('user_session', jsonEncode(user.toJson()));
    } catch (_) {}

    setState(() => _isLoading = false);

    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Masuk sebagai $name (Simulasi Cepat)'),
          backgroundColor: AppColors.success,
        ),
      );
      widget.onUserLoggedIn?.call();
    }
  }

  @override
  Widget build(BuildContext context) {
    final user = _api.currentUser;

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: AppColors.primary,
        foregroundColor: Colors.white,
        title: const Text('Akun & Profil Pelanggan', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
      ),
      body: SafeArea(
        child: user != null ? _buildLoggedInView(user) : _buildAuthForm(),
      ),
    );
  }

  // TAMPILAN JIKA SUDAH LOGIN
  Widget _buildLoggedInView(UserModel user) {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(20),
      child: Column(
        children: [
          // Profil Card
          Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(20),
              border: Border.all(color: AppColors.border),
            ),
            child: Column(
              children: [
                Container(
                  width: 70,
                  height: 70,
                  decoration: BoxDecoration(
                    color: AppColors.primary.withValues(alpha: 0.1),
                    shape: BoxShape.circle,
                  ),
                  child: const Icon(Icons.person, color: AppColors.primary, size: 40),
                ),
                const SizedBox(height: 12),
                Text(user.name, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 18, color: AppColors.textDark)),
                const SizedBox(height: 4),
                Text(user.phone, style: const TextStyle(fontSize: 13, color: AppColors.textMuted)),
                const SizedBox(height: 10),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(
                    color: AppColors.success.withValues(alpha: 0.12),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: const Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(Icons.verified, size: 14, color: AppColors.success),
                      SizedBox(width: 4),
                      Text('Akun Terverifikasi (OTP)', style: TextStyle(color: AppColors.success, fontSize: 11, fontWeight: FontWeight.bold)),
                    ],
                  ),
                ),
              ],
            ),
          ),

          const SizedBox(height: 16),

          // Alamat & Patokan Pengantaran
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(18),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(20),
              border: Border.all(color: AppColors.border),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Row(
                  children: [
                    Icon(Icons.location_on, color: AppColors.danger, size: 20),
                    SizedBox(width: 8),
                    Text('Alamat Utama Pengantaran', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: AppColors.textDark)),
                  ],
                ),
                const SizedBox(height: 12),
                Text(
                  user.address ?? 'Belum ada alamat pengantaran.',
                  style: const TextStyle(fontSize: 13, color: AppColors.textDark, height: 1.3),
                ),
                const SizedBox(height: 8),
                const Divider(height: 1),
                const SizedBox(height: 8),
                Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Icon(Icons.signpost_outlined, size: 16, color: AppColors.accentHover),
                    const SizedBox(width: 6),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('Patokan Jalan:', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: AppColors.textMuted)),
                          Text(
                            user.addressBenchmark ?? 'Belum mengisi patokan.',
                            style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: AppColors.textDark),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),

          const SizedBox(height: 24),

          // Tombol Logout dengan Modal Konfirmasi
          SizedBox(
            width: double.infinity,
            height: 48,
            child: OutlinedButton.icon(
              style: OutlinedButton.styleFrom(
                foregroundColor: AppColors.danger,
                side: const BorderSide(color: AppColors.danger),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              ),
              icon: const Icon(Icons.logout, size: 18),
              label: const Text('Keluar dari Akun (Logout)', style: TextStyle(fontWeight: FontWeight.bold)),
              onPressed: () async {
                final confirm = await showDialog<bool>(
                  context: context,
                  builder: (ctx) => AlertDialog(
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                    title: const Text('Konfirmasi Logout', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                    content: const Text('Apakah Anda yakin ingin keluar dari akun aplikasi mobile ini?', style: TextStyle(fontSize: 13)),
                    actions: [
                      TextButton(
                        onPressed: () => Navigator.pop(ctx, false),
                        child: const Text('Batal', style: TextStyle(color: Colors.grey)),
                      ),
                      ElevatedButton(
                        style: ElevatedButton.styleFrom(
                          backgroundColor: AppColors.danger,
                          foregroundColor: Colors.white,
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                        ),
                        onPressed: () => Navigator.pop(ctx, true),
                        child: const Text('Ya, Logout'),
                      ),
                    ],
                  ),
                );

                if (confirm == true) {
                  await _api.logout();
                  setState(() {});
                  if (mounted) {
                    ScaffoldMessenger.of(context).showSnackBar(
                      const SnackBar(
                        content: Text('Anda telah berhasil keluar dari akun.'),
                        backgroundColor: AppColors.primary,
                      ),
                    );
                  }
                }
              },
            ),
          ),
        ],
      ),
    );
  }

  // FORM REGISTRASI / LOGIN
  Widget _buildAuthForm() {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Logo Identitas Resto Resmi
          Center(
            child: Container(
              margin: const EdgeInsets.only(bottom: 20, top: 4),
              child: Column(
                children: [
                  ClipRRect(
                    borderRadius: BorderRadius.circular(20),
                    child: Image.asset(
                      'assets/images/logo.png',
                      width: 76,
                      height: 76,
                      fit: BoxFit.cover,
                    ),
                  ),
                  const SizedBox(height: 8),
                  const Text('Baknus Dresto', style: TextStyle(fontWeight: FontWeight.w900, fontSize: 18, color: AppColors.textDark)),
                  const Text('Restaurant POS & Self-Order', style: TextStyle(fontSize: 11, color: AppColors.textMuted)),
                ],
              ),
            ),
          ),
          // Segmented Toggle Registrasi vs Login
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
                    onTap: () => setState(() => _isLoginMode = false),
                    borderRadius: BorderRadius.circular(10),
                    child: Container(
                      padding: const EdgeInsets.symmetric(vertical: 10),
                      decoration: BoxDecoration(
                        color: !_isLoginMode ? AppColors.primary : Colors.transparent,
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Center(
                        child: Text(
                          'Daftar Pelanggan Baru',
                          style: TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.bold,
                            color: !_isLoginMode ? Colors.white : AppColors.textDark,
                          ),
                        ),
                      ),
                    ),
                  ),
                ),
                Expanded(
                  child: InkWell(
                    onTap: () => setState(() => _isLoginMode = true),
                    borderRadius: BorderRadius.circular(10),
                    child: Container(
                      padding: const EdgeInsets.symmetric(vertical: 10),
                      decoration: BoxDecoration(
                        color: _isLoginMode ? AppColors.primary : Colors.transparent,
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Center(
                        child: Text(
                          'Masuk (Login)',
                          style: TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.bold,
                            color: _isLoginMode ? Colors.white : AppColors.textDark,
                          ),
                        ),
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),

          const SizedBox(height: 20),

          if (!_isLoginMode) ...[
            // FORM DAFTAR
            const Text('Form Registrasi Pelanggan', style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: AppColors.textDark)),
            const SizedBox(height: 4),
            const Text('Cukup isi nama dan no telepon, verifikasi OTP, lalu buat password.', style: TextStyle(fontSize: 11, color: AppColors.textMuted)),
            const SizedBox(height: 16),

            _buildInputField('Nama Lengkap', _regNameCtrl, Icons.person, 'Contoh: Rian Hidayat'),
            const SizedBox(height: 12),
            _buildInputField('Nomor WhatsApp / HP', _regPhoneCtrl, Icons.phone, 'Contoh: 081234567890', isPhone: true),
            const SizedBox(height: 12),
            _buildInputField('Alamat Lengkap Pengantaran', _regAddressCtrl, Icons.home, 'Nama jalan, perumahan, no. rumah', maxLines: 2),
            const SizedBox(height: 12),
            _buildInputField('Patokan Jalan (Opsional)', _regBenchmarkCtrl, Icons.signpost, 'Contoh: Pagar hitam depan warung Madura'),

            const SizedBox(height: 24),

            SizedBox(
              width: double.infinity,
              height: 48,
              child: ElevatedButton.icon(
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppColors.primary,
                  foregroundColor: Colors.white,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  elevation: 0,
                ),
                icon: _isLoading
                    ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                    : const Icon(Icons.send, size: 18),
                label: const Text('Kirim Kode OTP (Simulasi)', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                onPressed: _isLoading ? null : _handleRequestOtp,
              ),
            ),
          ] else ...[
            // SIMULASI AKUN CEPAT
            Container(
              padding: const EdgeInsets.all(12),
              margin: const EdgeInsets.only(bottom: 16),
              decoration: BoxDecoration(
                color: AppColors.primary.withOpacity(0.05),
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: AppColors.primary.withOpacity(0.15)),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Row(
                    children: [
                      Icon(Icons.bolt, color: AppColors.accent, size: 16),
                      SizedBox(width: 6),
                      Text('Simulasi Akun Cepat (1-Klik)', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 11, color: AppColors.primary)),
                    ],
                  ),
                  const SizedBox(height: 8),
                  InkWell(
                    onTap: () => _quickLoginAs(
                      name: 'Rian Hidayat',
                      phone: '081234567891',
                      address: 'Jl. Melati Indah Blok C No. 15',
                      benchmark: 'Pagar hitam samping warung',
                    ),
                    borderRadius: BorderRadius.circular(10),
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(10),
                        border: Border.all(color: AppColors.border),
                      ),
                      child: const Row(
                        children: [
                          Icon(Icons.account_circle, color: AppColors.primary, size: 24),
                          SizedBox(width: 8),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text('Rian Hidayat', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
                                Text('081234567891 • Pelanggan Tetap', style: TextStyle(fontSize: 10, color: AppColors.textMuted)),
                              ],
                            ),
                          ),
                          Icon(Icons.arrow_forward_ios, size: 12, color: AppColors.accent),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(height: 6),
                  InkWell(
                    onTap: () => _quickLoginAs(
                      name: 'Siti Rahma',
                      phone: '081298765432',
                      address: 'Jl. Anggrek No. 8, Jakarta Barat',
                      benchmark: 'Depan gapura hijau',
                    ),
                    borderRadius: BorderRadius.circular(10),
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(10),
                        border: Border.all(color: AppColors.border),
                      ),
                      child: const Row(
                        children: [
                          Icon(Icons.account_circle, color: AppColors.success, size: 24),
                          SizedBox(width: 8),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text('Siti Rahma', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
                                Text('081298765432 • Pelanggan Online', style: TextStyle(fontSize: 10, color: AppColors.textMuted)),
                              ],
                            ),
                          ),
                          Icon(Icons.arrow_forward_ios, size: 12, color: AppColors.accent),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
            ),

            // FORM LOGIN MANUAL
            const Text('Atau Masuk dengan Nomor Telepon', style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: AppColors.textDark)),
            const SizedBox(height: 4),
            const Text('Gunakan nomor telepon dan password yang telah Anda buat.', style: TextStyle(fontSize: 11, color: AppColors.textMuted)),
            const SizedBox(height: 12),

            _buildInputField('Nomor Telepon', _loginPhoneCtrl, Icons.phone, '081234567890', isPhone: true),
            const SizedBox(height: 12),
            _buildInputField('Password', _loginPassCtrl, Icons.lock, 'Password akun', isPassword: true),

            const SizedBox(height: 24),

            SizedBox(
              width: double.infinity,
              height: 48,
              child: ElevatedButton.icon(
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppColors.primary,
                  foregroundColor: Colors.white,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  elevation: 0,
                ),
                icon: _isLoading
                    ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                    : const Icon(Icons.login, size: 18),
                label: const Text('Masuk ke Aplikasi', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                onPressed: _isLoading ? null : _handleLogin,
              ),
            ),
          ],
        ],
      ),
    );
  }

  Widget _buildInputField(
    String label,
    TextEditingController controller,
    IconData icon,
    String hint, {
    bool isPhone = false,
    bool isPassword = false,
    int maxLines = 1,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: AppColors.textDark)),
        const SizedBox(height: 6),
        TextField(
          controller: controller,
          keyboardType: isPhone ? TextInputType.phone : TextInputType.text,
          obscureText: isPassword,
          maxLines: maxLines,
          decoration: InputDecoration(
            prefixIcon: Icon(icon, size: 18, color: AppColors.textMuted),
            hintText: hint,
            hintStyle: const TextStyle(fontSize: 12, color: Colors.grey),
            filled: true,
            fillColor: Colors.white,
            border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: AppColors.border)),
            enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: AppColors.border)),
            contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
          ),
          style: const TextStyle(fontSize: 12),
        ),
      ],
    );
  }
}
