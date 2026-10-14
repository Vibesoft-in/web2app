import 'dart:async';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'catalog_repository.dart';
import 'product_model.dart';

class CatalogState {
  final List<Product> products;
  final bool isLoading;
  final bool isLoadingMore;
  final bool hasMore;
  final int page;
  final String searchQuery;
  final String? errorMessage;

  const CatalogState({
    this.products = const [],
    this.isLoading = false,
    this.isLoadingMore = false,
    this.hasMore = true,
    this.page = 1,
    this.searchQuery = '',
    this.errorMessage,
  });

  bool get isEmpty => !isLoading && products.isEmpty && errorMessage == null;

  CatalogState copyWith({
    List<Product>? products,
    bool? isLoading,
    bool? isLoadingMore,
    bool? hasMore,
    int? page,
    String? searchQuery,
    String? errorMessage,
  }) {
    return CatalogState(
      products: products ?? this.products,
      isLoading: isLoading ?? this.isLoading,
      isLoadingMore: isLoadingMore ?? this.isLoadingMore,
      hasMore: hasMore ?? this.hasMore,
      page: page ?? this.page,
      searchQuery: searchQuery ?? this.searchQuery,
      errorMessage: errorMessage,
    );
  }
}

class CatalogNotifier extends StateNotifier<CatalogState> {
  final CatalogRepository _repository;
  Timer? _debounceTimer;

  CatalogNotifier(this._repository) : super(const CatalogState(isLoading: true)) {
    loadInitial();
  }

  Future<void> loadInitial({String? search}) async {
    state = state.copyWith(
      isLoading: true,
      errorMessage: null,
      page: 1,
      hasMore: true,
      searchQuery: search ?? state.searchQuery,
    );

    try {
      final items = await _repository.getProducts(
        page: 1,
        search: state.searchQuery,
      );
      state = state.copyWith(
        products: items,
        isLoading: false,
        hasMore: items.length >= 4,
        page: 1,
      );
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        errorMessage: e.toString(),
      );
    }
  }

  Future<void> loadMore() async {
    if (state.isLoading || state.isLoadingMore || !state.hasMore) return;

    final nextPage = state.page + 1;
    state = state.copyWith(isLoadingMore: true);

    try {
      final moreItems = await _repository.getProducts(
        page: nextPage,
        search: state.searchQuery,
      );

      if (moreItems.isEmpty) {
        state = state.copyWith(isLoadingMore: false, hasMore: false);
      } else {
        state = state.copyWith(
          products: [...state.products, ...moreItems],
          isLoadingMore: false,
          page: nextPage,
          hasMore: moreItems.length >= 4,
        );
      }
    } catch (_) {
      state = state.copyWith(isLoadingMore: false);
    }
  }

  /// 400ms Debounce search input
  void onSearchChanged(String query) {
    if (_debounceTimer?.isActive ?? false) _debounceTimer?.cancel();

    _debounceTimer = Timer(const Duration(milliseconds: 400), () {
      loadInitial(search: query);
    });
  }

  @override
  void dispose() {
    _debounceTimer?.cancel();
    super.dispose();
  }
}

final catalogRepositoryProvider = Provider<CatalogRepository>((ref) {
  return CatalogRepository();
});

final catalogNotifierProvider =
    StateNotifierProvider<CatalogNotifier, CatalogState>((ref) {
  final repo = ref.watch(catalogRepositoryProvider);
  return CatalogNotifier(repo);
});
