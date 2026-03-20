import 'package:flutter_test/flutter_test.dart';

import 'package:poultry_consumer_app/src/app.dart';

void main() {
  testWidgets('consumer app renders login form first', (WidgetTester tester) async {
    await tester.pumpWidget(const ConsumerShopApp());
    await tester.pump(const Duration(milliseconds: 800));

    expect(find.text('Consumer Login'), findsOneWidget);
    expect(find.text('Log In'), findsOneWidget);
  });
}
