import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../catalog/product_model.dart';

class CartItem {
  final Product product;
  final ProductVariant? selectedVariant;
  final int quantity;

  const CartItem({
    required this.product,
    this.selectedVariant,
    this.quantity = 1,
  });

  double get unitPrice => selectedVariant?.price ?? product.currentPrice;
  double get totalPrice => unitPrice * quantity;

  String get key => '${product.id}_${selectedVariant?.id ?? "default"}';

  CartItem copyWith({int? quantity}) {
    return CartItem(
      product: product,
      selectedVariant: selectedVariant,
      quantity: quantity ?? this.quantity,
    );
  }
}

class CartState {
  final List<CartItem> items;

  const CartState({this.items = const []});

  int get totalCount => items.fold(0, (sum, i) => sum + i.quantity);
  double get totalAmount => items.fold(0.0, (sum, i) => sum + i.totalPrice);
  bool get isEmpty => items.isEmpty;
}

class CartNotifier extends StateNotifier<CartState> {
  CartNotifier() : super(const CartState());

  void addItem(Product product, {ProductVariant? variant, int quantity = 1}) {
    final targetKey = '${product.id}_${variant?.id ?? "default"}';
    final existingIndex = state.items.indexWhere((i) => i.key == targetKey);

    if (existingIndex >= 0) {
      final currentItem = state.items[existingIndex];
      final updatedList = List<CartItem>.from(state.items);
      updatedList[existingIndex] = currentItem.copyWith(
        quantity: currentItem.quantity + quantity,
      );
      state = CartState(items: updatedList);
    } else {
      final newItem = CartItem(
        product: product,
        selectedVariant: variant,
        quantity: quantity,
      );
      state = CartState(items: [...state.items, newItem]);
    }
  }

  void updateQuantity(String key, int newQuantity) {
    if (newQuantity <= 0) {
      removeItem(key);
      return;
    }

    final updated = state.items.map((item) {
      if (item.key == key) {
        return item.copyWith(quantity: newQuantity);
      }
      return item;
    }).toList();

    state = CartState(items: updated);
  }

  void removeItem(String key) {
    state = CartState(items: state.items.where((i) => i.key != key).toList());
  }

  void clear() {
    state = const CartState(items: []);
  }
}

final cartNotifierProvider =
    StateNotifierProvider<CartNotifier, CartState>((ref) {
  return CartNotifier();
});
