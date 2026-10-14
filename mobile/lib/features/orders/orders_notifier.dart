import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'orders_model.dart';
import 'orders_repository.dart';

enum OrdersStatus {
  initial,
  loading,
  data,
  empty,
  error,
}

class OrdersState {
  final OrdersStatus status;
  final List<CustomerOrder> orders;
  final String? errorMessage;

  const OrdersState({
    this.status = OrdersStatus.initial,
    this.orders = const [],
    this.errorMessage,
  });

  OrdersState copyWith({
    OrdersStatus? status,
    List<CustomerOrder>? orders,
    String? errorMessage,
  }) {
    return OrdersState(
      status: status ?? this.status,
      orders: orders ?? this.orders,
      errorMessage: errorMessage ?? this.errorMessage,
    );
  }
}

class OrdersNotifier extends StateNotifier<OrdersState> {
  final OrdersRepository _repository;

  OrdersNotifier({required OrdersRepository repository})
      : _repository = repository,
        super(const OrdersState());

  Future<void> fetchOrders() async {
    state = state.copyWith(status: OrdersStatus.loading, errorMessage: null);
    try {
      final list = await _repository.getOrders();
      if (list.isEmpty) {
        state = state.copyWith(status: OrdersStatus.empty, orders: []);
      } else {
        state = state.copyWith(status: OrdersStatus.data, orders: list);
      }
    } catch (e) {
      state = state.copyWith(
        status: OrdersStatus.error,
        errorMessage: e.toString(),
      );
    }
  }

  void clear() {
    state = const OrdersState();
  }
}

final ordersRepositoryProvider = Provider<OrdersRepository>((ref) {
  return OrdersRepository();
});

final ordersNotifierProvider =
    StateNotifierProvider<OrdersNotifier, OrdersState>((ref) {
  final repo = ref.watch(ordersRepositoryProvider);
  return OrdersNotifier(repository: repo);
});
