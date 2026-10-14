import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:webview_flutter/webview_flutter.dart';
import '../../core/security/url_allowlist.dart';
import '../cart/cart_model.dart';
import '../orders/orders_repository.dart';

class CheckoutWebviewScreen extends ConsumerStatefulWidget {
  final String checkoutUrl;
  final List<String> allowedHosts;

  const CheckoutWebviewScreen({
    super.key,
    required this.checkoutUrl,
    required this.allowedHosts,
  });

  @override
  ConsumerState<CheckoutWebviewScreen> createState() =>
      _CheckoutWebviewScreenState();
}

class _CheckoutWebviewScreenState
    extends ConsumerState<CheckoutWebviewScreen> {
  late final WebViewController _controller;
  late final UrlAllowlist _allowlist;
  bool _isLoading = true;
  bool _isConfirmingOrder = false;
  String? _errorMessage;

  @override
  void initState() {
    super.initState();
    _allowlist = UrlAllowlist(allowedHosts: widget.allowedHosts);

    _controller = WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..setNavigationDelegate(
        NavigationDelegate(
          onNavigationRequest: (NavigationRequest request) {
            // Enforce strict security: only whitelisted hosts and HTTPS allowed
            if (!_allowlist.isAllowed(request.url)) {
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(
                  content: Text(
                    'Navigation blocked: Target domain is not permitted.',
                  ),
                  backgroundColor: Colors.red,
                ),
              );
              return NavigationDecision.prevent;
            }

            // Check if user reached order completion/thank-you page
            _checkOrderCompletion(request.url);

            return NavigationDecision.navigate;
          },
          onPageStarted: (String url) {
            if (mounted) setState(() => _isLoading = true);
            _checkOrderCompletion(url);
          },
          onPageFinished: (String url) {
            if (mounted) setState(() => _isLoading = false);
            _checkOrderCompletion(url);
          },
          onWebResourceError: (WebResourceError error) {
            if (mounted) {
              setState(() {
                _isLoading = false;
                _errorMessage = error.description;
              });
            }
          },
        ),
      );

    // Initial load
    if (_allowlist.isAllowed(widget.checkoutUrl)) {
      _controller.loadRequest(Uri.parse(widget.checkoutUrl));
    } else {
      _errorMessage = 'The initial checkout URL host is not in the allowed list.';
      _isLoading = false;
    }
  }

  /// Inspect current URL for WooCommerce order-received pattern
  void _checkOrderCompletion(String url) {
    if (_isConfirmingOrder) return;

    try {
      final uri = Uri.parse(url);
      final path = uri.path;

      // Match WooCommerce /order-received/{id} or query param
      final orderReceivedMatch = RegExp(r'order-received/(\d+)').firstMatch(path);
      String? orderId = orderReceivedMatch?.group(1);

      if (orderId == null && uri.queryParameters.containsKey('order_id')) {
        orderId = uri.queryParameters['order_id'];
      }

      if (orderId != null && orderId.isNotEmpty) {
        _confirmOrderSuccess(orderId);
      }
    } catch (_) {
      // Ignore URL parsing errors
    }
  }

  /// Revalidate & confirm the order directly from backend before clearing cart
  Future<void> _confirmOrderSuccess(String orderId) async {
    setState(() => _isConfirmingOrder = true);

    try {
      final ordersRepo = ref.read(ordersRepositoryProvider);
      final confirmedOrder = await ordersRepo.getOrder(orderId);

      // Wipe cart now that server confirmed order
      ref.read(cartNotifierProvider.notifier).clear();

      if (!mounted) return;

      // Show confirmation dialog
      await showDialog(
        context: context,
        barrierDismissible: false,
        builder: (ctx) => AlertDialog(
          title: const Row(
            children: [
              Icon(Icons.check_circle, color: Colors.green, size: 28),
              SizedBox(width: 8),
              Text('Order Confirmed!'),
            ],
          ),
          content: Text(
            'Order #${confirmedOrder.number} has been placed successfully.\nTotal: ₹${confirmedOrder.total}',
          ),
          actions: [
            ElevatedButton(
              onPressed: () {
                Navigator.of(ctx).pop();
                context.go('/orders');
              },
              child: const Text('View My Orders'),
            ),
          ],
        ),
      );
    } catch (e) {
      if (mounted) {
        setState(() => _isConfirmingOrder = false);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Payment processed, verifying order: $e'),
            action: SnackBarAction(
              label: 'View Orders',
              onPressed: () {
                ref.read(cartNotifierProvider.notifier).clear();
                context.go('/orders');
              },
            ),
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Secure Checkout'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            tooltip: 'Reload',
            onPressed: () => _controller.reload(),
          ),
        ],
      ),
      body: Stack(
        children: [
          if (_errorMessage != null)
            Center(
              child: Padding(
                padding: const EdgeInsets.all(24.0),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const Icon(Icons.error_outline, size: 48, color: Colors.red),
                    const SizedBox(height: 16),
                    Text(
                      _errorMessage!,
                      textAlign: TextAlign.center,
                      style: const TextStyle(fontWeight: FontWeight.w600),
                    ),
                    const SizedBox(height: 20),
                    ElevatedButton(
                      onPressed: () => context.pop(),
                      child: const Text('Return to Cart'),
                    ),
                  ],
                ),
              ),
            )
          else
            WebViewWidget(controller: _controller),

          if (_isLoading || _isConfirmingOrder)
            Container(
              color: Colors.white.withOpacity(0.8),
              child: Center(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const CircularProgressIndicator(),
                    const SizedBox(height: 16),
                    Text(
                      _isConfirmingOrder
                          ? 'Verifying order confirmation with server...'
                          : 'Loading checkout...',
                      style: const TextStyle(fontWeight: FontWeight.w600),
                    ),
                  ],
                ),
              ),
            ),
        ],
      ),
    );
  }
}
