import 'package:dio/dio.dart';
import '../network/dio_client.dart';
import '../network/error_mapper.dart';
import 'app_config_model.dart';
import 'config_cache.dart';

class ConfigRepository {
  final DioClient _dioClient;
  final ConfigCache _cache;

  ConfigRepository({
    DioClient? dioClient,
    ConfigCache? cache,
  })  : _dioClient = dioClient ?? DioClient(),
        _cache = cache ?? ConfigCache();

  /// Fetches the latest white-label runtime configuration from the server.
  /// If offline or server unreachable, transparently falls back to cached config.
  Future<AppConfig> fetchAppConfig() async {
    try {
      final response = await _dioClient.dio.get('/v1/app-config');
      final data = response.data;
      if (data is Map<String, dynamic>) {
        final configJson = data['data'] is Map<String, dynamic>
            ? data['data'] as Map<String, dynamic>
            : data;
        final config = AppConfig.fromJson(configJson);
        await _cache.saveConfig(config);
        return config;
      }
      throw const NetworkException(
        code: 'INVALID_CONFIG_FORMAT',
        message: 'Invalid configuration format received from server.',
      );
    } on DioException catch (e) {
      // Offline fallback: try to load last good cached config
      final cached = await _cache.loadLastGoodConfig();
      if (cached != null) {
        return cached;
      }
      throw ErrorMapper.fromDioError(e);
    } catch (e) {
      final cached = await _cache.loadLastGoodConfig();
      if (cached != null) {
        return cached;
      }
      if (e is NetworkException) rethrow;
      throw NetworkException(
        code: 'CONFIG_FETCH_FAILED',
        message: 'Could not load configuration: $e',
      );
    }
  }

  /// Synchronously or asynchronously access the last cached configuration
  Future<AppConfig?> getCachedConfig() => _cache.loadLastGoodConfig();
}
