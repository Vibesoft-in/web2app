import 'package:flutter_test/flutter_test.dart';
import 'package:webintoapp_engine/features/cart/cart_model.dart';
import 'package:webintoapp_engine/features/catalog/product_model.dart';

void main() {
  group('CartNotifier Tests', () {
    late CartNotifier notifier;
    const testProduct1 = Product(
      id: 'p1',
      title: 'Oxford Shirt',
      price: 1500.0,
      salePrice: 1200.0,
    );
    const testProduct2 = Product(
      id: 'p2',
      title: 'Leather Belt',
      price: 800.0,
    );

    setUp(() {
      notifier = CartNotifier();
    });

    test('initial state is empty', () {
      expect(notifier.state.isEmpty, isTrue);
      expect(notifier.state.totalCount, equals(0));
      expect(notifier.state.totalAmount, equals(0.0));
    });

    test('adding items calculates total count and amount using sale price', () {
      notifier.addItem(testProduct1, quantity: 2);

      expect(notifier.state.items.length, equals(1));
      expect(notifier.state.totalCount, equals(2));
      // 2 * 1200 (sale price) = 2400
      expect(notifier.state.totalAmount, equals(2400.0));

      notifier.addItem(testProduct2, quantity: 1);
      expect(notifier.state.items.length, equals(2));
      expect(notifier.state.totalCount, equals(3));
      // 2400 + 800 = 3200
      expect(notifier.state.totalAmount, equals(3200.0));
    });

    test('adding same item merges quantity', () {
      notifier.addItem(testProduct1, quantity: 1);
      notifier.addItem(testProduct1, quantity: 2);

      expect(notifier.state.items.length, equals(1));
      expect(notifier.state.totalCount, equals(3));
      expect(notifier.state.totalAmount, equals(3600.0));
    });

    test('updating quantity to 0 removes item', () {
      notifier.addItem(testProduct1, quantity: 2);
      final key = notifier.state.items.first.key;

      notifier.updateQuantity(key, 0);
      expect(notifier.state.isEmpty, isTrue);
    });

    test('clear removes all items', () {
      notifier.addItem(testProduct1);
      notifier.addItem(testProduct2);
      expect(notifier.state.items.length, equals(2));

      notifier.clear();
      expect(notifier.state.isEmpty, isTrue);
    });
  });
}
