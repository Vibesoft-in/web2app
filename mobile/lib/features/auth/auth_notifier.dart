import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/storage/secure_store.dart';
import '../cart/cart_model.dart';
import '../orders/orders_notifier.dart';
import 'auth_repository.dart';

enum AuthStatus {
  initial,
  loading,
  authenticated,
  unauthenticated,
  error,
}

class AuthState {
  final AuthStatus status;
  final AuthUser? user;
  final String? errorMessage;
  final String? pendingPhone;
  final int? resendAfterSeconds;

  const AuthState({
    this.status = AuthStatus.initial,
    this.user,
    this.errorMessage,
    this.pendingPhone,
    this.resendAfterSeconds,
  });

  bool get isAuthenticated => status == AuthStatus.authenticated && user != null;
  bool get isLoading => status == AuthStatus.loading;

  AuthState copyWith({
    AuthStatus? status,
    AuthUser? user,
    String? errorMessage,
    String? pendingPhone,
    int? resendAfterSeconds,
  }) {
    return AuthState(
      status: status ?? this.status,
      user: user ?? this.user,
      errorMessage: errorMessage,
      pendingPhone: pendingPhone ?? this.pendingPhone,
      resendAfterSeconds: resendAfterSeconds ?? this.resendAfterSeconds,
    );
  }
}

class AuthNotifier extends StateNotifier<AuthState> {
  final AuthRepository _repository;
  final SecureStore _secureStore;
  final Ref _ref;

  AuthNotifier({
    required AuthRepository repository,
    required SecureStore secureStore,
    required Ref ref,
  })  : _repository = repository,
        _secureStore = secureStore,
        _ref = ref,
        super(const AuthState());

  /// Check existing session on startup
  Future<void> checkAuth() async {
    state = state.copyWith(status: AuthStatus.loading);
    try {
      final hasToken = await _secureStore.hasValidToken();
      if (hasToken) {
        final customerId = await _secureStore.getCustomerId();
        state = state.copyWith(
          status: AuthStatus.authenticated,
          user: AuthUser(id: 0, storeCustomerId: customerId),
        );
      } else {
        state = state.copyWith(status: AuthStatus.unauthenticated);
      }
    } catch (e) {
      state = state.copyWith(
        status: AuthStatus.unauthenticated,
        errorMessage: e.toString(),
      );
    }
  }

  /// Request OTP for customer phone
  Future<bool> requestOtp(String phone) async {
    state = state.copyWith(status: AuthStatus.loading, errorMessage: null);
    try {
      final res = await _repository.requestOtp(phone);
      final resendAfter = (res['resend_after'] as num?)?.toInt() ?? 30;

      state = state.copyWith(
        status: AuthStatus.unauthenticated,
        pendingPhone: phone,
        resendAfterSeconds: resendAfter,
      );
      return true;
    } catch (e) {
      state = state.copyWith(
        status: AuthStatus.error,
        errorMessage: e.toString(),
      );
      return false;
    }
  }

  /// Verify OTP code and establish authenticated session
  Future<bool> verifyOtp(String otp, {String? name}) async {
    final phone = state.pendingPhone;
    if (phone == null || phone.isEmpty) {
      state = state.copyWith(
        status: AuthStatus.error,
        errorMessage: 'Phone number missing. Please request a new OTP.',
      );
      return false;
    }

    state = state.copyWith(status: AuthStatus.loading, errorMessage: null);
    try {
      final user = await _repository.verifyOtp(phone, otp, name: name);
      state = state.copyWith(
        status: AuthStatus.authenticated,
        user: user,
        pendingPhone: null,
      );
      return true;
    } catch (e) {
      state = state.copyWith(
        status: AuthStatus.error,
        errorMessage: e.toString(),
      );
      return false;
    }
  }

  /// Google Social Login
  Future<bool> loginWithGoogle(String idToken) async {
    state = state.copyWith(status: AuthStatus.loading, errorMessage: null);
    try {
      final user = await _repository.googleLogin(idToken);
      state = state.copyWith(
        status: AuthStatus.authenticated,
        user: user,
      );
      return true;
    } catch (e) {
      state = state.copyWith(
        status: AuthStatus.error,
        errorMessage: e.toString(),
      );
      return false;
    }
  }

  /// Logout customer: clears tokens, unregisters FCM token, and empties cart and caches
  Future<void> logout({String? fcmToken}) async {
    state = state.copyWith(status: AuthStatus.loading);
    try {
      await _repository.logout(fcmToken: fcmToken);
    } finally {
      // 1. Clear cart and orders state to guarantee zero leftover items
      _ref.read(cartNotifierProvider.notifier).clear();
      _ref.read(ordersNotifierProvider.notifier).clear();

      // 2. Clear tokens from secure store
      await _secureStore.clearSession();

      // 3. Reset state
      state = const AuthState(status: AuthStatus.unauthenticated);
    }
  }

  /// Compliance account deletion: calls backend DELETE /v1/account and wipes all local data
  Future<void> deleteAccount() async {
    state = state.copyWith(status: AuthStatus.loading);
    try {
      await _repository.deleteAccount();
    } finally {
      _ref.read(cartNotifierProvider.notifier).clear();
      _ref.read(ordersNotifierProvider.notifier).clear();
      await _secureStore.clearSession();
      state = const AuthState(status: AuthStatus.unauthenticated);
    }
  }
}

final authRepositoryProvider = Provider<AuthRepository>((ref) {
  return AuthRepository();
});

final secureStoreProvider = Provider<SecureStore>((ref) {
  return const SecureStore();
});

final authNotifierProvider = StateNotifierProvider<AuthNotifier, AuthState>((ref) {
  final repository = ref.watch(authRepositoryProvider);
  final secureStore = ref.watch(secureStoreProvider);
  return AuthNotifier(
    repository: repository,
    secureStore: secureStore,
    ref: ref,
  );
});
