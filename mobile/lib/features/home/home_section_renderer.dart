import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../core/config/app_config_model.dart';
import '../catalog/product_model.dart';

class HomeSectionRenderer extends StatelessWidget {
  final List<HomeSection> sections;
  final List<Product> products;
  final void Function(Product product)? onProductTap;

  const HomeSectionRenderer({
    super.key,
    required this.sections,
    this.products = const [],
    this.onProductTap,
  });

  @override
  Widget build(BuildContext context) {
    return ListView.separated(
      physics: const BouncingScrollPhysics(),
      padding: const EdgeInsets.only(bottom: 32),
      itemCount: sections.length,
      separatorBuilder: (_, __) => const SizedBox(height: 24),
      itemBuilder: (context, index) {
        final section = sections[index];
        return _buildSection(context, section);
      },
    );
  }

  Widget _buildSection(BuildContext context, HomeSection section) {
    if (section is BannerHomeSection) {
      return _buildBannerSection(context, section);
    } else if (section is CategoriesHomeSection) {
      return _buildCategoriesSection(context);
    } else if (section is ProductRailHomeSection) {
      return _buildProductRailSection(context, section);
    }

    // Safely ignore unknown future section types without crashing
    return const SizedBox.shrink();
  }

  Widget _buildBannerSection(BuildContext context, BannerHomeSection section) {
    if (section.items.isEmpty) {
      // Default branded hero banner
      final theme = Theme.of(context);
      return Container(
        height: 160,
        margin: const EdgeInsets.symmetric(horizontal: 16),
        padding: const EdgeInsets.all(20),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(16),
          gradient: LinearGradient(
            colors: [
              theme.colorScheme.primary,
              theme.colorScheme.primary.withOpacity(0.75),
            ],
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
          ),
          boxShadow: [
            BoxShadow(
              color: theme.colorScheme.primary.withOpacity(0.25),
              blurRadius: 16,
              offset: const Offset(0, 6),
            ),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisAlignment: MainAxisAlignment.end,
          children: [
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
              decoration: BoxDecoration(
                color: Colors.white.withOpacity(0.2),
                borderRadius: BorderRadius.circular(6),
              ),
              child: const Text(
                'NEW ARRIVALS',
                style: TextStyle(
                  color: Colors.white,
                  fontWeight: FontWeight.w800,
                  fontSize: 10,
                  letterSpacing: 1.0,
                ),
              ),
            ),
            const SizedBox(height: 8),
            const Text(
              'Summer Collection Live',
              style: TextStyle(
                color: Colors.white,
                fontWeight: FontWeight.w800,
                fontSize: 20,
              ),
            ),
            const SizedBox(height: 2),
            Text(
              'Up to 40% OFF this week only',
              style: TextStyle(
                color: Colors.white.withOpacity(0.9),
                fontSize: 12,
              ),
            ),
          ],
        ),
      );
    }

    return SizedBox(
      height: 170,
      child: PageView.builder(
        itemCount: section.items.length,
        itemBuilder: (context, idx) {
          final item = section.items[idx];
          return Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: ClipRRect(
              borderRadius: BorderRadius.circular(16),
              child: CachedNetworkImage(
                imageUrl: item.image,
                fit: BoxFit.cover,
                placeholder: (_, __) => Container(color: Colors.black12),
                errorWidget: (_, __, ___) => Container(
                  color: Colors.black12,
                  child: const Icon(Icons.broken_image, color: Colors.black26),
                ),
              ),
            ),
          );
        },
      ),
    );
  }

  Widget _buildCategoriesSection(BuildContext context) {
    final categories = const [
      {'name': 'All', 'icon': Icons.grid_view_rounded},
      {'name': 'Apparel', 'icon': Icons.checkroom_rounded},
      {'name': 'Shoes', 'icon': Icons.roller_skating_rounded},
      {'name': 'Watches', 'icon': Icons.watch_rounded},
      {'name': 'Audio', 'icon': Icons.headphones_rounded},
    ];

    final theme = Theme.of(context);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 16.0),
          child: Text(
            'Categories',
            style: theme.textTheme.titleMedium?.copyWith(
              fontWeight: FontWeight.w800,
            ),
          ),
        ),
        const SizedBox(height: 12),
        SizedBox(
          height: 88,
          child: ListView.separated(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            scrollDirection: Axis.horizontal,
            itemCount: categories.length,
            separatorBuilder: (_, __) => const SizedBox(width: 14),
            itemBuilder: (context, idx) {
              final cat = categories[idx];
              final isFirst = idx == 0;
              return GestureDetector(
                onTap: () => context.push('/catalog?category=${cat['name']}'),
                child: Column(
                  children: [
                    Container(
                      height: 56,
                      width: 56,
                      decoration: BoxDecoration(
                        color: isFirst
                            ? theme.colorScheme.primary
                            : theme.colorScheme.surface,
                        shape: BoxShape.circle,
                        border: Border.all(
                          color: isFirst
                              ? theme.colorScheme.primary
                              : theme.colorScheme.onSurface.withOpacity(0.08),
                        ),
                      ),
                      child: Icon(
                        cat['icon'] as IconData,
                        color: isFirst ? Colors.white : theme.colorScheme.onSurface,
                        size: 26,
                      ),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      cat['name'] as String,
                      style: theme.textTheme.bodySmall?.copyWith(
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ],
                ),
              );
            },
          ),
        ),
      ],
    );
  }

  Widget _buildProductRailSection(
    BuildContext context,
    ProductRailHomeSection section,
  ) {
    final theme = Theme.of(context);
    final displayList = products.isNotEmpty ? products : const <Product>[];

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 16.0),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.between,
            children: [
              Text(
                section.title,
                style: theme.textTheme.titleMedium?.copyWith(
                  fontWeight: FontWeight.w800,
                ),
              ),
              TextButton(
                onPressed: () => context.push('/catalog'),
                child: const Text('View all'),
              ),
            ],
          ),
        ),
        const SizedBox(height: 8),
        SizedBox(
          height: 240,
          child: ListView.separated(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            scrollDirection: Axis.horizontal,
            itemCount: displayList.length,
            separatorBuilder: (_, __) => const SizedBox(width: 14),
            itemBuilder: (context, idx) {
              final product = displayList[idx];
              return GestureDetector(
                onTap: () {
                  if (onProductTap != null) {
                    onProductTap!(product);
                  } else {
                    context.push('/product/${product.id}');
                  }
                },
                child: Container(
                  width: 160,
                  decoration: BoxDecoration(
                    color: theme.colorScheme.surface,
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(
                      color: theme.colorScheme.onSurface.withOpacity(0.08),
                    ),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      ClipRRect(
                        borderRadius: const BorderRadius.vertical(top: Radius.circular(12)),
                        child: Container(
                          height: 140,
                          width: double.infinity,
                          color: Colors.black12,
                          child: CachedNetworkImage(
                            imageUrl: product.images.first,
                            fit: BoxFit.cover,
                            placeholder: (_, __) => Container(color: Colors.black12),
                            errorWidget: (_, __, ___) => const Icon(Icons.broken_image),
                          ),
                        ),
                      ),
                      Padding(
                        padding: const EdgeInsets.all(10.0),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              product.title,
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: theme.textTheme.bodySmall?.copyWith(
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              '₹${product.currentPrice.toStringAsFixed(0)}',
                              style: theme.textTheme.bodyMedium?.copyWith(
                                fontWeight: FontWeight.w800,
                                color: theme.colorScheme.primary,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              );
            },
          ),
        ),
      ],
    );
  }
}
