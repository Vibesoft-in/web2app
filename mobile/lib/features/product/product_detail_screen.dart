import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../core/widgets/error_view.dart';
import '../../core/widgets/loading_view.dart';
import '../cart/cart_model.dart';
import '../catalog/catalog_notifier.dart';
import '../catalog/product_model.dart';

final productDetailProvider =
    FutureProvider.family<Product, String>((ref, id) async {
  final repo = ref.watch(catalogRepositoryProvider);
  return repo.getProduct(id);
});

class ProductDetailScreen extends ConsumerStatefulWidget {
  final String productId;

  const ProductDetailScreen({super.key, required this.productId});

  @override
  ConsumerState<ProductDetailScreen> createState() => _ProductDetailScreenState();
}

class _ProductDetailScreenState extends ConsumerState<ProductDetailScreen> {
  int _selectedImageIndex = 0;
  ProductVariant? _selectedVariant;
  int _quantity = 1;

  @override
  Widget build(BuildContext context) {
    final productAsync = ref.watch(productDetailProvider(widget.productId));
    final theme = Theme.of(context);

    return productAsync.when(
      loading: () => const Scaffold(
        body: LoadingView(message: 'Loading product details...'),
      ),
      error: (err, _) => Scaffold(
        appBar: AppBar(),
        body: ErrorView(
          message: err.toString(),
          onRetry: () => ref.refresh(productDetailProvider(widget.productId)),
        ),
      ),
      data: (product) {
        final activeVariant = _selectedVariant ??
            (product.variants.isNotEmpty ? product.variants.first : null);
        final unitPrice = activeVariant?.price ?? product.currentPrice;

        return Scaffold(
          appBar: AppBar(
            title: Text(
              product.title,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
            actions: [
              IconButton(
                icon: const Icon(Icons.share_outlined),
                onPressed: () {},
              ),
              IconButton(
                icon: const Icon(Icons.shopping_bag_outlined),
                onPressed: () => context.push('/cart'),
              ),
            ],
          ),
          body: SingleChildScrollView(
            physics: const BouncingScrollPhysics(),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // 1. Image Gallery with Carousel
                SizedBox(
                  height: 340,
                  child: Stack(
                    children: [
                      PageView.builder(
                        itemCount: product.images.length,
                        onPageChanged: (i) => setState(() => _selectedImageIndex = i),
                        itemBuilder: (context, idx) {
                          return CachedNetworkImage(
                            imageUrl: product.images[idx],
                            fit: BoxFit.cover,
                            placeholder: (_, __) => Container(color: Colors.black12),
                            errorWidget: (_, __, ___) => const Icon(Icons.broken_image),
                          );
                        },
                      ),
                      if (product.images.length > 1)
                        Positioned(
                          bottom: 12,
                          left: 0,
                          right: 0,
                          child: Row(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: List.generate(
                              product.images.length,
                              (idx) => Container(
                                margin: const EdgeInsets.symmetric(horizontal: 3),
                                height: 6,
                                width: _selectedImageIndex == idx ? 18 : 6,
                                decoration: BoxDecoration(
                                  color: _selectedImageIndex == idx
                                      ? theme.colorScheme.primary
                                      : Colors.white.withOpacity(0.6),
                                  borderRadius: BorderRadius.circular(3),
                                ),
                              ),
                            ),
                          ),
                        ),
                    ],
                  ),
                ),

                Padding(
                  padding: const EdgeInsets.all(20.0),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      // Price & Badge
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Row(
                            crossAxisAlignment: CrossAxisAlignment.baseline,
                            textBaseline: TextBaseline.alphabetic,
                            children: [
                              Text(
                                '₹${unitPrice.toStringAsFixed(0)}',
                                style: theme.textTheme.headlineSmall?.copyWith(
                                  fontWeight: FontWeight.w900,
                                  color: theme.colorScheme.primary,
                                ),
                              ),
                              if (product.isOnSale) ...[
                                const SizedBox(width: 8),
                                Text(
                                  '₹${product.price.toStringAsFixed(0)}',
                                  style: theme.textTheme.bodyMedium?.copyWith(
                                    decoration: TextDecoration.lineThrough,
                                    color: theme.colorScheme.onSurface.withOpacity(0.5),
                                  ),
                                ),
                              ],
                            ],
                          ),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                            decoration: BoxDecoration(
                              color: product.inStock
                                  ? Colors.emerald.withOpacity(0.12)
                                  : Colors.red.withOpacity(0.12),
                              borderRadius: BorderRadius.circular(6),
                            ),
                            child: Text(
                              product.inStock ? 'In Stock' : 'Out of Stock',
                              style: TextStyle(
                                color: product.inStock ? Colors.emerald : Colors.red,
                                fontWeight: FontWeight.w700,
                                fontSize: 11,
                              ),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 12),

                      // Title
                      Text(
                        product.title,
                        style: theme.textTheme.titleLarge?.copyWith(
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                      const SizedBox(height: 16),

                      // 2. Variants Selector (if any)
                      if (product.variants.isNotEmpty) ...[
                        Text(
                          'Select Variant',
                          style: theme.textTheme.titleSmall?.copyWith(
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                        const SizedBox(height: 8),
                        Wrap(
                          spacing: 8,
                          runSpacing: 8,
                          children: product.variants.map((v) {
                            final isSelected = activeVariant?.id == v.id;
                            return ChoiceChip(
                              label: Text(v.name),
                              selected: isSelected,
                              onSelected: (selected) {
                                if (selected) setState(() => _selectedVariant = v);
                              },
                            );
                          }).toList(),
                        ),
                        const SizedBox(height: 20),
                      ],

                      // 3. Quantity Selector
                      Row(
                        children: [
                          Text(
                            'Quantity',
                            style: theme.textTheme.titleSmall?.copyWith(
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                          const Spacer(),
                          Container(
                            decoration: BoxDecoration(
                              color: theme.colorScheme.surface,
                              borderRadius: BorderRadius.circular(8),
                              border: Border.all(
                                color: theme.colorScheme.onSurface.withOpacity(0.12),
                              ),
                            ),
                            child: Row(
                              children: [
                                IconButton(
                                  icon: const Icon(Icons.remove, size: 16),
                                  onPressed: _quantity > 1
                                      ? () => setState(() => _quantity--)
                                      : null,
                                ),
                                Padding(
                                  padding: const EdgeInsets.symmetric(horizontal: 8.0),
                                  child: Text(
                                    '$_quantity',
                                    style: const TextStyle(fontWeight: FontWeight.bold),
                                  ),
                                ),
                                IconButton(
                                  icon: const Icon(Icons.add, size: 16),
                                  onPressed: () => setState(() => _quantity++),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 24),

                      // 4. Description
                      Text(
                        'Product Overview',
                        style: theme.textTheme.titleSmall?.copyWith(
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                      const SizedBox(height: 8),
                      Text(
                        product.description,
                        style: theme.textTheme.bodyMedium?.copyWith(
                          height: 1.6,
                          color: theme.colorScheme.onSurface.withOpacity(0.75),
                        ),
                      ),
                      const SizedBox(height: 80),
                    ],
                  ),
                ),
              ],
            ),
          ),
          bottomSheet: Container(
            padding: const EdgeInsets.all(16.0),
            decoration: BoxDecoration(
              color: theme.scaffoldBackgroundColor,
              border: Border(
                top: BorderSide(
                  color: theme.colorScheme.onSurface.withOpacity(0.08),
                ),
              ),
            ),
            child: ElevatedButton.icon(
              onPressed: product.inStock
                  ? () {
                      ref.read(cartNotifierProvider.notifier).addItem(
                            product,
                            variant: activeVariant,
                            quantity: _quantity,
                          );

                      ScaffoldMessenger.of(context).showSnackBar(
                        SnackBar(
                          content: Text('Added ${_quantity}x "${product.title}" to cart!'),
                          action: SnackBarAction(
                            label: 'View Cart',
                            onPressed: () => context.push('/cart'),
                          ),
                          behavior: SnackBarBehavior.floating,
                        ),
                      );
                    }
                  : null,
              icon: const Icon(Icons.add_shopping_cart_rounded),
              label: Text(product.inStock ? 'Add to Cart' : 'Currently Unavailable'),
            ),
          ),
        );
      },
    );
  }
}
