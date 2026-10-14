import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import '../env/app_env.dart';

class SecureStore {
  String get _tokenKey => '${AppEnv.appKey}_auth_customer_token';
  String get _refreshTokenKey => '${AppEnv.appKey}_auth_customer_refresh_token';
  String get _customerIdKey => '${AppEnv.appKey}_auth_customer_id';

  final FlutterSecureStorage _storage;

  const SecureStore({FlutterSecureStorage? storage})
      : _storage = storage ?? const FlutterSecureStorage();

  Future<void> saveTokens({
    required String accessToken,
    String? refreshToken,
    String? customerId,
  }) async {
    await _storage.write(key: _tokenKey, value: accessToken);
    if (refreshToken != null) {
      await _storage.write(key: _refreshTokenKey, value: refreshToken);
    }
    if (customerId != null) {
      await _storage.write(key: _customerIdKey, value: customerId);
    }
  }

  Future<String?> getAccessToken() async {
    return _storage.read(key: _tokenKey);
  }

  Future<String?> getRefreshToken() async {
    return _storage.read(key: _refreshTokenKey);
  }

  Future<String?> getCustomerId() async {
    return _storage.read(key: _customerIdKey);
  }

  Future<bool> hasValidToken() async {
    final token = await getAccessToken();
    return token != null && token.isNotEmpty;
  }

  /// Wipes all sensitive session tokens upon logout.
  Future<void> clearSession() async {
    await _storage.delete(key: _tokenKey);
    await _storage.delete(key: _refreshTokenKey);
    await _storage.delete(key: _customerIdKey);
  }
}
