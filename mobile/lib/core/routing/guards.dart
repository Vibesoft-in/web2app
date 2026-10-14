import '../storage/secure_store.dart';

class RouteGuards {
  final SecureStore secureStore;

  const RouteGuards({this.secureStore = const SecureStore()});

  /// Guard to check if current route requires authenticated customer session
  Future<bool> requireAuth(String path) async {
    final protectedRoutes = ['/profile', '/orders', '/checkout'];
    final isProtected = protectedRoutes.any((r) => path.startsWith(r));

    if (!isProtected) return true;

    return await secureStore.hasValidToken();
  }
}
