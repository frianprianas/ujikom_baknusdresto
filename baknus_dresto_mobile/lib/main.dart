import 'package:flutter/material.dart';
import 'screens/main_navigation_screen.dart';
import 'theme/app_colors.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(const BaknusDrestoMobileApp());
}

class BaknusDrestoMobileApp extends StatelessWidget {
  const BaknusDrestoMobileApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Baknus Dresto Mobile',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        useMaterial3: true,
        scaffoldBackgroundColor: AppColors.background,
        colorScheme: ColorScheme.fromSeed(
          seedColor: AppColors.primary,
          primary: AppColors.primary,
          secondary: AppColors.accent,
        ),
        fontFamily: 'sans-serif',
      ),
      home: const MainNavigationScreen(),
    );
  }
}
