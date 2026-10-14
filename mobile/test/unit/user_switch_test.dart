import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:webintoapp_engine/core/storage/secure_store.dart';
import 'package:webintoapp_engine/features/auth/auth_notifier.dart';
import 'package:webintoapp_engine/features/auth/auth_repository.dart';
import 'package:webintoapp_engine/features/cart/cart_model.dart';
import 'package:webintoapp_engine/features/catalog/product_model.dart';
import 'package:webintoapp_engine/features/orders/orders_model.dart';
import 'package:webintoapp_engine/features/orders/orders_notifier.dart';

class MockAuthRepository extends AuthRepository {
  bool logoutCalled = false;
  bool deleteAccountCalled = false;
  String? lastUnregisteredFcmToken;

  @override
  Future<Map<String, dynamic>> requestOtp(String phone) async {
    return {'message': 'OTP sent', 'resend_after': 30};
  }

  @override
  Future<AuthUser> verifyOtp(String phone, String otp, {String? name}) async {
    return AuthUser(
      id: 1,
      phone: phone,
      name: name ?? 'User A',
      storeCustomerId: 'cust_101',
    );
  }

  @override
  Future<AuthUser> googleLogin(String idToken) async {
    return const AuthUser(
      id: 2,
      email: 'user_b@example.com',
      name: 'User B',
      storeCustomerId: 'cust_102',
    );
  }

  @override
  Future<void> logout({String? fcmToken}) async {
    logoutCalled = true;
    lastUnregisteredFcmToken = fcmToken;
  }

  @override
  Future<void> deleteAccount() async {
    deleteAccountCalled = true;
  }
}

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  group('User Switch & Clean Session Tests', () {
    late SecureStore secureStore;
    late MockAuthRepository mockAuthRepo;
    late ProviderContainer container;

    setUp(() {
      FlutterSecureStorage.setMockInitialValues({});
      secureStore = const SecureStore();
      mockAuthRepo = MockAuthRepository();

      container = ProviderContainer(
        overrides: [
          authRepositoryProvider.overrideWithValue(mockAuthRepo),
          secureStoreProvider.overrideWithValue(secureStore),
        ],
      );
    });

    tearDown(() {
      container.dispose();
    });

    test('User A logs in, adds cart items, logs out -> clears tokens, cart, and caches', () async {
      final authNotifier = container.read(authNotifierProvider.notifier);
      final cartNotifier = container.read(cartNotifierProvider.notifier);

      // 1. User A logs in
      await authNotifier.requestOtp('+919876543210');
      final loginSuccess = await authNotifier.verifyOtp('123456', name: 'Alice');
      expect(loginSuccess, isTrue);

      final authStateA = container.read(authNotifierProvider);
      expect(authStateA.isAuthenticated, isTrue);
      expect(authStateA.user?.name, equals('Alice'));

      // Save tokens for User A
      await secureStore.saveTokens(
        accessToken: 'token_user_a_secret',
        customerId: 'cust_101',
      );

      // 2. User A adds items to cart
      cartNotifier.addItem(
        const Product(id: 'p1', title: 'Silk Dress', price: 2500.0),
        quantity: 2,
      );
      cartNotifier.addItem(
        const Product(id: 'p2', title: 'Handbag', price: 1500.0),
        quantity: 1,
      );

      expect(container.read(cartNotifierProvider).totalCount, equals(3));
      expect(container.read(cartNotifierProvider).totalAmount, equals(6500.0));
      expect(await secureStore.getAccessToken(), equals('token_user_a_secret'));

      // Populate orders state for User A
      container.read(ordersNotifierProvider.notifier).state = const OrdersState(
        status: OrdersStatus.data,
        orders: [
          CustomerOrder(id: '501', number: '501', status: 'processing', total: '2500.0', currency: 'INR', createdAt: '2026-10-13', items: []),
        ],
      );
      expect(container.read(ordersNotifierProvider).orders, hasLength(1));

      // 3. User A logs out with FCM token unregistration
      await authNotifier.logout(fcmToken: 'fcm_user_a_token');

      expect(mockAuthRepo.logoutCalled, isTrue);
      expect(mockAuthRepo.lastUnregisteredFcmToken, equals('fcm_user_a_token'));

      // Session state is reset to unauthenticated
      expect(container.read(authNotifierProvider).isAuthenticated, isFalse);
      expect(container.read(authNotifierProvider).status, equals(AuthStatus.unauthenticated));

      // CRITICAL ASSERTION: Tokens are completely wiped from SecureStore
      expect(await secureStore.getAccessToken(), isNull);
      expect(await secureStore.getCustomerId(), isNull);
      expect(await secureStore.hasValidToken(), isFalse);

      // CRITICAL ASSERTION: Cart is completely emptied on logout
      expect(container.read(cartNotifierProvider).isEmpty, isTrue);
      expect(container.read(cartNotifierProvider).totalCount, equals(0));
      expect(container.read(cartNotifierProvider).totalAmount, equals(0.0));

      // CRITICAL ASSERTION: Orders are completely cleared on logout
      expect(container.read(ordersNotifierProvider).orders, isEmpty);
      expect(container.read(ordersNotifierProvider).status, equals(OrdersStatus.initial));

      // 4. User B logs in on the SAME device
      final loginSuccessB = await authNotifier.loginWithGoogle('google_token_bob');
      expect(loginSuccessB, isTrue);

      final authStateB = container.read(authNotifierProvider);
      expect(authStateB.isAuthenticated, isTrue);
      expect(authStateB.user?.email, equals('user_b@example.com'));

      await secureStore.saveTokens(
        accessToken: 'token_user_b_secret',
        customerId: 'cust_102',
      );

      // CRITICAL ASSERTION: User B sees zero leftover data from User A
      final cartStateForUserB = container.read(cartNotifierProvider);
      expect(cartStateForUserB.isEmpty, isTrue);
      expect(cartStateForUserB.items, isEmpty);
      expect(container.read(ordersNotifierProvider).orders, isEmpty);
      expect(await secureStore.getAccessToken(), equals('token_user_b_secret'));
      expect(await secureStore.getCustomerId(), equals('cust_102'));
    });

    test('Account deletion wipes cart, tokens, and resets session completely', () async {
      final authNotifier = container.read(authNotifierProvider.notifier);
      final cartNotifier = container.read(cartNotifierProvider.notifier);

      // Setup logged-in state with cart item
      await authNotifier.loginWithGoogle('token_123');
      await secureStore.saveTokens(accessToken: 'token_to_delete', customerId: 'cust_999');
      cartNotifier.addItem(const Product(id: 'p3', title: 'Watch', price: 5000.0));

      expect(container.read(cartNotifierProvider).isEmpty, isFalse);
      expect(await secureStore.hasValidToken(), isTrue);

      // Trigger delete account
      await authNotifier.deleteAccount();

      expect(mockAuthRepo.deleteAccountCalled, isTrue);
      expect(container.read(authNotifierProvider).isAuthenticated, isFalse);
      expect(container.read(cartNotifierProvider).isEmpty, isTrue);
      expect(await secureStore.getAccessToken(), isNull);
    });
  });
}
