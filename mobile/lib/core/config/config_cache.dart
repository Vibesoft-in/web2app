import 'dart:convert';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import '../env/app_env.dart';
import 'app_config_model.dart';

class ConfigCache {
  String get _cacheKey => '${AppEnv.appKey}_cached_last_good_app_config_v1';
  final FlutterSecureStorage _storage;

  ConfigCache({FlutterSecureStorage? storage})
      : _storage = storage ?? const FlutterSecureStorage();

  AppConfig? _memoryCache;

  AppConfig? get inMemoryConfig => _memoryCache;

  /// Loads last good cached configuration from persistent storage.
  Future<AppConfig?> loadLastGoodConfig() async {
    if (_memoryCache != null) return _memoryCache;

    try {
      final raw = await _storage.read(key: _cacheKey);
      if (raw != null && raw.isNotEmpty) {
        final decoded = jsonDecode(raw) as Map<String, dynamic>;
        final config = AppConfig.fromJson(decoded);
        _memoryCache = config;
        return config;
      }
    } catch (_) {
      // Return null on corrupted cache to trigger network fetch or defaults
    }
    return null;
  }

  /// Persists valid configuration to local disk for offline resilience.
  Future<void> saveConfig(AppConfig config) async {
    _memoryCache = config;
    try {
      final serialized = jsonEncode(config.toJson());
      await _storage.write(key: _cacheKey, value: serialized);
    } catch (_) {
      // In-memory cache still holds the value even if persistent write fails
    }
  }

  /// Clears cached configuration.
  Future<void> clear() async {
    _memoryCache = null;
    try {
      await _storage.delete(key: _cacheKey);
    } catch (_) {}
  }
}
