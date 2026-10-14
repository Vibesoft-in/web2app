class ProductVariant {
  final String id;
  final String name;
  final String? sku;
  final double? price;
  final bool inStock;

  const ProductVariant({
    required this.id,
    required this.name,
    this.sku,
    this.price,
    this.inStock = true,
  });

  factory ProductVariant.fromJson(Map<String, dynamic> json) {
    return ProductVariant(
      id: json['id']?.toString() ?? '',
      name: json['name'] as String? ?? 'Default',
      sku: json['sku'] as String?,
      price: (json['price'] as num?)?.toDouble(),
      inStock: json['in_stock'] as bool? ?? true,
    );
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'name': name,
        if (sku != null) 'sku': sku,
        if (price != null) 'price': price,
        'in_stock': inStock,
      };
}

class Product {
  final String id;
  final String title;
  final String description;
  final double price;
  final double? salePrice;
  final List<String> images;
  final bool inStock;
  final double rating;
  final List<ProductVariant> variants;
  final List<String> categories;

  const Product({
    required this.id,
    required this.title,
    this.description = '',
    required this.price,
    this.salePrice,
    this.images = const [],
    this.inStock = true,
    this.rating = 4.8,
    this.variants = const [],
    this.categories = const [],
  });

  bool get isOnSale => salePrice != null && salePrice! < price;
  double get currentPrice => salePrice ?? price;

  factory Product.fromJson(Map<String, dynamic> json) {
    final rawImages = json['images'];
    final List<String> parsedImages = [];
    if (rawImages is List) {
      for (final img in rawImages) {
        if (img is String && img.isNotEmpty) {
          parsedImages.add(img);
        } else if (img is Map<String, dynamic> && img['src'] != null) {
          parsedImages.add(img['src'].toString());
        }
      }
    }

    final rawVariants = json['variants'];
    final List<ProductVariant> parsedVariants = [];
    if (rawVariants is List) {
      for (final v in rawVariants) {
        if (v is Map<String, dynamic>) {
          parsedVariants.add(ProductVariant.fromJson(v));
        }
      }
    }

    return Product(
      id: json['id']?.toString() ?? '',
      title: json['title'] as String? ?? json['name'] as String? ?? 'Product',
      description: json['description'] as String? ?? '',
      price: (json['price'] as num?)?.toDouble() ??
          double.tryParse(json['regular_price']?.toString() ?? '') ??
          0.0,
      salePrice: (json['sale_price'] as num?)?.toDouble() ??
          double.tryParse(json['sale_price']?.toString() ?? ''),
      images: parsedImages.isNotEmpty
          ? parsedImages
          : const ['https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=500&q=80'],
      inStock: json['in_stock'] as bool? ?? (json['stock_status'] == 'instock'),
      rating: (json['rating'] as num?)?.toDouble() ?? 4.8,
      variants: parsedVariants,
      categories: (json['categories'] as List<dynamic>?)
              ?.map((e) => e is Map ? e['name']?.toString() ?? '' : e.toString())
              .toList() ??
          const [],
    );
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'title': title,
        'description': description,
        'price': price,
        if (salePrice != null) 'sale_price': salePrice,
        'images': images,
        'in_stock': inStock,
        'rating': rating,
        'variants': variants.map((v) => v.toJson()).toList(),
        'categories': categories,
      };
}
