import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:webintoapp_engine/core/network/dio_client.dart';
import 'package:webintoapp_engine/core/storage/secure_store.dart';
import 'package:webintoapp_engine/features/auth/auth_repository.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  group('AuthRepository Unit Tests', () {
    late SecureStore secureStore;
    late Dio dio;
    late AuthRepository repo;

    setUp(() {
      FlutterSecureStorage.setMockInitialValues({});
      secureStore = const SecureStore();
      dio = Dio(BaseOptions(baseUrl: 'https://api.example.com'));
      final dioClient = DioClient(customDio: dio, storage: secureStore);
      repo = AuthRepository(dioClient: dioClient, secureStore: secureStore);
    });

    test('AuthUser serialization and deserialization', () {
      final user = AuthUser.fromJson({
        'id': 42,
        'phone': '+919999999999',
        'email': 'customer@example.com',
        'name': 'Customer Test',
        'store_customer_id': '108',
      });

      expect(user.id, equals(42));
      expect(user.phone, equals('+919999999999'));
      expect(user.email, equals('customer@example.com'));
      expect(user.name, equals('Customer Test'));
      expect(user.storeCustomerId, equals('108'));

      final json = user.toJson();
      expect(json['id'], equals(42));
      expect(json['phone'], equals('+919999999999'));
      expect(json['store_customer_id'], equals('108'));
    });

    test('logout wipes secure storage session', () async {
      await secureStore.saveTokens(
        accessToken: 'valid_access_token',
        customerId: '123',
      );
      expect(await secureStore.hasValidToken(), isTrue);

      // Perform logout (network call will catch error because URL is fake, but finally block wipes storage)
      await repo.logout(fcmToken: 'test_fcm_token');

      expect(await secureStore.hasValidToken(), isFalse);
      expect(await secureStore.getAccessToken(), isNull);
      expect(await secureStore.getCustomerId(), isNull);
    });
  });
}
