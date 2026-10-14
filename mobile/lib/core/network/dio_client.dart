import 'package:dio/dio.dart';
import '../env/app_env.dart';
import '../storage/secure_store.dart';

class DioClient {
  final Dio dio;
  final SecureStore secureStore;
  final void Function()? onUnauthenticated;

  DioClient({
    Dio? customDio,
    SecureStore? storage,
    this.onUnauthenticated,
  })  : secureStore = storage ?? const SecureStore(),
        dio = customDio ??
            Dio(
              BaseOptions(
                baseUrl: AppEnv.apiBaseUrl,
                connectTimeout: const Duration(seconds: 10),
                receiveTimeout: const Duration(seconds: 15),
                sendTimeout: const Duration(seconds: 10),
                headers: {
                  'Content-Type': 'application/json',
                  'Accept': 'application/json',
                  'X-App-Key': AppEnv.appKey,
                  'X-App-Version': AppEnv.appVersion,
                },
              ),
            ) {
    dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) async {
          // Attach customer token if authenticated
          final token = await secureStore.getAccessToken();
          if (token != null && token.isNotEmpty) {
            options.headers['Authorization'] = 'Bearer $token';
          }
          return handler.next(options);
        },
        onError: (DioException error, handler) async {
          if (error.response?.statusCode == 401) {
            await secureStore.clearSession();
            onUnauthenticated?.call();
          }
          return handler.next(error);
        },
      ),
    );
  }
}
