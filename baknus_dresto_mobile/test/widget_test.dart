import 'package:flutter_test/flutter_test.dart';
import 'package:baknus_dresto_mobile/main.dart';

void main() {
  testWidgets('Baknus Dresto mobile app smoke test', (WidgetTester tester) async {
    await tester.pumpWidget(const BaknusDrestoMobileApp());
    expect(find.text('Baknus Dresto'), findsOneWidget);
  });
}
