import 'package:cached_network_image/cached_network_image.dart';
import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../core/network/dio_client.dart';
import '../../core/widgets/empty_view.dart';
import '../auth/auth_notifier.dart';
import 'cart_model.dart';

class CartScreen extends ConsumerStatefulWidget {
  const CartScreen({super.key});

  @override
  ConsumerState<CartScreen> createState() => _CartScreenState();
}

class _CartScreenState extends ConsumerState<CartScreen> {
  bool _isCheckingOut = false;

  Future<void> _handleCheckout(CartState cartState) async {
    final authState = ref.read(authNotifierProvider);

    if (!authState.isAuthenticated) {
      final shouldLogin = await showDialog<bool>(
        context: context,
        builder: (ctx) => AlertDialog(
          title: const Text('Sign In Required'),
          content: const Text(
            'Please sign in to proceed with secure checkout and order tracking.',
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(ctx, false),
              child: const Text('Cancel'),
            ),
            ElevatedButton(
              onPressed: () => Navigator.pop(ctx, true),
              child: const Text('Sign In'),
            ),
          ],
        ),
      );

      if (shouldLogin == true && mounted) {
        context.push('/login');
      }
      return;
    }

    setState(() => _isCheckingOut = true);

    try {
      final dioClient = DioClient();
      final itemsPayload = cartState.items.map((item) {
        return {
          'product_id': item.product.id,
          'quantity': item.quantity,
          if (item.selectedVariant != null)
            'variant_id': item.selectedVariant!.id,
        };
      }).toList();

      final response = await dioClient.dio.post(
        '/v1/cart/checkout-url',
        data: {'items': itemsPayload},
      );

      final data = response.data['data'] as Map<String, dynamic>;
      final checkoutUrl = data['checkout_url'] as String;
      final allowedHosts = (data['allowed_hosts'] as List<dynamic>?)
              ?.map((e) => e.toString())
              .toList() ??
          [];

      if (!mounted) return;

      context.push('/checkout-webview', extra: {
        'checkout_url': checkoutUrl,
        'allowed_hosts': allowedHosts,
      });
    } on DioException catch (e) {
      if (!mounted) return;
      final errorMsg = e.response?.data['error']?['message'] ??
          'Failed to initialize checkout. Please check item availability.';
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(errorMsg.toString()),
          backgroundColor: Colors.red,
        ),
      );
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Checkout error: $e'),
          backgroundColor: Colors.red,
        ),
      );
    } finally {
      if (mounted) setState(() => _isCheckingOut = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final cartState = ref.watch(cartNotifierProvider);
    final cartNotifier = ref.read(cartNotifierProvider.notifier);
    final theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Shopping Cart'),
        actions: [
          if (!cartState.isEmpty)
            IconButton(
              icon: const Icon(Icons.delete_sweep_outlined),
              tooltip: 'Clear Cart',
              onPressed: () => cartNotifier.clear(),
            ),
        ],
      ),
      body: cartState.isEmpty
          ? EmptyView(
              title: 'Your Cart is Empty',
              message: 'Looks like you have not added any products yet.',
              icon: Icons.shopping_bag_outlined,
              actionLabel: 'Explore Catalog',
              onAction: () => context.go('/catalog'),
            )
          : ListView.separated(
              padding: const EdgeInsets.only(
                  left: 16, right: 16, top: 16, bottom: 120),
              itemCount: cartState.items.length,
              separatorBuilder: (_, __) => const SizedBox(height: 12),
              itemBuilder: (context, idx) {
                final item = cartState.items[idx];
                return Card(
                  child: Padding(
                    padding: const EdgeInsets.all(12.0),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.center,
                      children: [
                        ClipRRect(
                          borderRadius: BorderRadius.circular(8),
                          child: Container(
                            height: 70,
                            width: 70,
                            color: Colors.black12,
                            child: CachedNetworkImage(
                              imageUrl: item.product.images.isNotEmpty
                                  ? item.product.images.first
                                  : '',
                              fit: BoxFit.cover,
                              errorWidget: (_, __, ___) =>
                                  const Icon(Icons.broken_image),
                            ),
                          ),
                        ),
                        const SizedBox(width: 14),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                item.product.title,
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: theme.textTheme.titleSmall?.copyWith(
                                  fontWeight: FontWeight.w700,
                                ),
                              ),
                              if (item.selectedVariant != null) ...[
                                const SizedBox(height: 2),
                                Text(
                                  item.selectedVariant!.name,
                                  style: theme.textTheme.bodySmall?.copyWith(
                                    color: theme.colorScheme.onSurface
                                        .withOpacity(0.6),
                                  ),
                                ),
                              ],
                              const SizedBox(height: 6),
                              Text(
                                '₹${item.totalPrice.toStringAsFixed(0)}',
                                style: theme.textTheme.titleSmall?.copyWith(
                                  fontWeight: FontWeight.w800,
                                  color: theme.colorScheme.primary,
                                ),
                              ),
                            ],
                          ),
                        ),
                        // Quantity controls
                        Row(
                          children: [
                            IconButton(
                              icon: const Icon(Icons.remove, size: 16),
                              onPressed: () => cartNotifier.updateQuantity(
                                  item.key, item.quantity - 1),
                            ),
                            Text(
                              '${item.quantity}',
                              style:
                                  const TextStyle(fontWeight: FontWeight.bold),
                            ),
                            IconButton(
                              icon: const Icon(Icons.add, size: 16),
                              onPressed: () => cartNotifier.updateQuantity(
                                  item.key, item.quantity + 1),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                );
              },
            ),
      bottomSheet: cartState.isEmpty
          ? null
          : Container(
              padding: const EdgeInsets.all(20.0),
              decoration: BoxDecoration(
                color: theme.scaffoldBackgroundColor,
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withOpacity(0.05),
                    offset: const Offset(0, -4),
                    blurRadius: 16,
                  ),
                ],
              ),
              child: SafeArea(
                child: Row(
                  children: [
                    Column(
                      mainAxisSize: MainAxisSize.min,
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Total (${cartState.totalCount} items)',
                          style: theme.textTheme.bodySmall?.copyWith(
                            color:
                                theme.colorScheme.onSurface.withOpacity(0.6),
                          ),
                        ),
                        Text(
                          '₹${cartState.totalAmount.toStringAsFixed(0)}',
                          style: theme.textTheme.titleLarge?.copyWith(
                            fontWeight: FontWeight.w900,
                            color: theme.colorScheme.primary,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(width: 20),
                    Expanded(
                      child: ElevatedButton(
                        onPressed: _isCheckingOut
                            ? null
                            : () => _handleCheckout(cartState),
                        child: _isCheckingOut
                            ? const SizedBox(
                                height: 20,
                                width: 20,
                                child:
                                    CircularProgressIndicator(strokeWidth: 2),
                              )
                            : const Text('Checkout'),
                      ),
                    ),
                  ],
                ),
              ),
            ),
    );
  }
}
