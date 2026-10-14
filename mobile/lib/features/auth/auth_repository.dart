import 'package:dio/dio.dart';
import '../../core/network/dio_client.dart';
import '../../core/network/error_mapper.dart';
import '../../core/storage/secure_store.dart';

class AuthUser {
  final int id;
  final String? phone;
  final String? email;
  final String? name;
  final String? storeCustomerId;

  const AuthUser({
    required this.id,
    this.phone,
    this.email,
    this.name,
    this.storeCustomerId,
  });

  factory AuthUser.fromJson(Map<String, dynamic> json) {
    return AuthUser(
      id: (json['id'] as num?)?.toInt() ?? 0,
      phone: json['phone'] as String?,
      email: json['email'] as String?,
      name: json['name'] as String?,
      storeCustomerId: json['store_customer_id']?.toString(),
    );
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        if (phone != null) 'phone': phone,
        if (email != null) 'email': email,
        if (name != null) 'name': name,
        if (storeCustomerId != null) 'store_customer_id': storeCustomerId,
      };
}

class AuthRepository {
  final DioClient _dioClient;
  final SecureStore _secureStore;

  AuthRepository({
    DioClient? dioClient,
    SecureStore? secureStore,
  })  : _dioClient = dioClient ?? DioClient(),
        _secureStore = secureStore ?? const SecureStore();

  Future<Map<String, dynamic>> requestOtp(String phone) async {
    try {
      final response = await _dioClient.dio.post(
        '/v1/auth/otp/request',
        data: {'phone': phone},
      );
      return response.data as Map<String, dynamic>;
    } on DioException catch (e) {
      throw ErrorMapper.fromDioError(e);
    }
  }

  Future<AuthUser> verifyOtp(String phone, String otp, {String? name}) async {
    try {
      final response = await _dioClient.dio.post(
        '/v1/auth/otp/verify',
        data: {
          'phone': phone,
          'otp': otp,
          if (name != null) 'name': name,
        },
      );

      final data = response.data['data'] as Map<String, dynamic>;
      final token = data['token'] as String;
      final user = AuthUser.fromJson(data['user'] as Map<String, dynamic>);

      await _secureStore.saveTokens(
        accessToken: token,
        customerId: user.storeCustomerId,
      );

      return user;
    } on DioException catch (e) {
      throw ErrorMapper.fromDioError(e);
    }
  }

  Future<AuthUser> googleLogin(String idToken) async {
    try {
      final response = await _dioClient.dio.post(
        '/v1/auth/social/google',
        data: {'id_token': idToken},
      );

      final data = response.data['data'] as Map<String, dynamic>;
      final token = data['token'] as String;
      final user = AuthUser.fromJson(data['user'] as Map<String, dynamic>);

      await _secureStore.saveTokens(
        accessToken: token,
        customerId: user.storeCustomerId,
      );

      return user;
    } on DioException catch (e) {
      throw ErrorMapper.fromDioError(e);
    }
  }

  Future<void> logout({String? fcmToken}) async {
    try {
      // 1. Call backend logout
      await _dioClient.dio.post('/v1/auth/logout').catchError((_) => Response(requestOptions: RequestOptions()));

      // 2. Unregister FCM token if provided
      if (fcmToken != null && fcmToken.isNotEmpty) {
        await _dioClient.dio.delete(
          '/v1/devices',
          data: {'fcm_token': fcmToken},
        ).catchError((_) => Response(requestOptions: RequestOptions()));
      }
    } finally {
      // 3. Purge local tokens
      await _secureStore.clearSession();
    }
  }

  Future<void> deleteAccount() async {
    try {
      await _dioClient.dio.delete('/v1/account');
    } on DioException catch (e) {
      throw ErrorMapper.fromDioError(e);
    } finally {
      await _secureStore.clearSession();
    }
  }

  Future<bool> isAuthenticated() => _secureStore.hasValidToken();
}
