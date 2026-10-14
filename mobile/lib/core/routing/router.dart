import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../features/auth/login_screen.dart';
import '../../features/cart/cart_screen.dart';
import '../../features/catalog/catalog_screen.dart';
import '../../features/checkout/checkout_webview_screen.dart';
import '../../features/home/home_screen.dart';
import '../../features/orders/orders_screen.dart';
import '../../features/product/product_detail_screen.dart';
import '../../features/profile/profile_screen.dart';
import '../../features/splash/maintenance_screen.dart';
import '../../features/splash/splash_screen.dart';

final appRouterProvider = Provider<GoRouter>((ref) {
  return GoRouter(
    initialLocation: '/',
    routes: [
      GoRoute(
        path: '/',
        builder: (context, state) => const SplashScreen(),
      ),
      GoRoute(
        path: '/home',
        builder: (context, state) => const HomeScreen(),
      ),
      GoRoute(
        path: '/catalog',
        builder: (context, state) {
          final category = state.uri.queryParameters['category'];
          return CatalogScreen(initialCategory: category);
        },
      ),
      GoRoute(
        path: '/product/:id',
        builder: (context, state) {
          final id = state.pathParameters['id'] ?? '1';
          return ProductDetailScreen(productId: id);
        },
      ),
      GoRoute(
        path: '/cart',
        builder: (context, state) => const CartScreen(),
      ),
      GoRoute(
        path: '/login',
        builder: (context, state) => const LoginScreen(),
      ),
      GoRoute(
        path: '/profile',
        builder: (context, state) => const ProfileScreen(),
      ),
      GoRoute(
        path: '/orders',
        builder: (context, state) => const OrdersScreen(),
      ),
      GoRoute(
        path: '/checkout-webview',
        builder: (context, state) {
          final extra = state.extra as Map<String, dynamic>? ?? {};
          final checkoutUrl = extra['checkout_url'] as String? ?? '';
          final allowedHosts = (extra['allowed_hosts'] as List<dynamic>?)
                  ?.map((e) => e.toString())
                  .toList() ??
              [];
          return CheckoutWebviewScreen(
            checkoutUrl: checkoutUrl,
            allowedHosts: allowedHosts,
          );
        },
      ),
      GoRoute(
        path: '/maintenance',
        builder: (context, state) => const MaintenanceScreen(),
      ),
    ],
  );
});
