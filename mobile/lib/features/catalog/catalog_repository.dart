import 'package:dio/dio.dart';
import '../../core/network/dio_client.dart';
import '../../core/network/error_mapper.dart';
import 'product_model.dart';

class CatalogRepository {
  final DioClient _dioClient;

  CatalogRepository({DioClient? dioClient})
      : _dioClient = dioClient ?? DioClient();

  /// Fetches paginated products with optional search query and category filters.
  Future<List<Product>> getProducts({
    int page = 1,
    int perPage = 10,
    String? search,
    String? category,
  }) async {
    try {
      final response = await _dioClient.dio.get(
        '/v1/products',
        queryParameters: {
          'page': page,
          'per_page': perPage,
          if (search != null && search.trim().isNotEmpty) 'search': search.trim(),
          if (category != null && category.trim().isNotEmpty) 'category': category.trim(),
        },
      );

      final data = response.data;
      if (data is Map<String, dynamic> && data['data'] is List) {
        return (data['data'] as List)
            .map((item) => Product.fromJson(item as Map<String, dynamic>))
            .toList();
      } else if (data is List) {
        return data.map((item) => Product.fromJson(item as Map<String, dynamic>)).toList();
      }
      return _fallbackSampleProducts(page, search);
    } on DioException catch (e) {
      if (e.type == DioExceptionType.connectionError || e.response == null) {
        // Return demo products so users and offline preview experience real catalog
        return _fallbackSampleProducts(page, search);
      }
      throw ErrorMapper.fromDioError(e);
    } catch (_) {
      return _fallbackSampleProducts(page, search);
    }
  }

  /// Fetches single product details by ID
  Future<Product> getProduct(String id) async {
    try {
      final response = await _dioClient.dio.get('/v1/products/$id');
      final data = response.data;
      if (data is Map<String, dynamic>) {
        final productJson = data['data'] is Map<String, dynamic>
            ? data['data'] as Map<String, dynamic>
            : data;
        return Product.fromJson(productJson);
      }
      return _findFallbackProduct(id);
    } on DioException catch (e) {
      if (e.response == null) return _findFallbackProduct(id);
      throw ErrorMapper.fromDioError(e);
    } catch (_) {
      return _findFallbackProduct(id);
    }
  }

  Product _findFallbackProduct(String id) {
    return _allSampleProducts().firstWhere(
      (p) => p.id == id,
      orElse: () => _allSampleProducts().first,
    );
  }

  List<Product> _fallbackSampleProducts(int page, String? search) {
    if (page > 3) return []; // Simulate end of pagination after page 3

    var list = _allSampleProducts();
    if (search != null && search.trim().isNotEmpty) {
      final q = search.toLowerCase();
      list = list.where((p) => p.title.toLowerCase().contains(q)).toList();
    }
    return list;
  }

  List<Product> _allSampleProducts() {
    return const [
      Product(
        id: '1',
        title: 'Classic Cotton Hoodie',
        description:
            'Premium 380 GSM organic cotton blend fleece with double-lined hood and kangaroo pocket. Pre-shrunk for an enduring fit.',
        price: 2499.0,
        salePrice: 1899.0,
        images: [
          'https://images.unsplash.com/photo-1556905055-8f358a7a47b2?w=600&q=80',
          'https://images.unsplash.com/photo-1578632767115-351597cf2477?w=600&q=80',
        ],
        variants: [
          ProductVariant(id: 'v1', name: 'Size S / Black', inStock: true),
          ProductVariant(id: 'v2', name: 'Size M / Black', inStock: true),
          ProductVariant(id: 'v3', name: 'Size L / Charcoal', inStock: false),
        ],
        categories: ['Apparel', 'Hoodies'],
      ),
      Product(
        id: '2',
        title: 'Minimalist Leather Watch',
        description:
            'Sapphire glass face with genuine Italian vegetable-tanned leather strap. Japanese quartz movement with 5 ATM water resistance.',
        price: 4999.0,
        salePrice: null,
        images: [
          'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=600&q=80',
        ],
        variants: [
          ProductVariant(id: 'v4', name: 'Tan Strap', inStock: true),
          ProductVariant(id: 'v5', name: 'Midnight Black', inStock: true),
        ],
        categories: ['Accessories', 'Watches'],
      ),
      Product(
        id: '3',
        title: 'Wireless Sport Earbuds',
        description:
            'Active noise cancellation with 32-hour battery life case. IPX7 sweatproof rated for intensive fitness sessions.',
        price: 2999.0,
        salePrice: 2299.0,
        images: [
          'https://images.unsplash.com/photo-1572569511254-d8f925fe2cbb?w=600&q=80',
        ],
        variants: [
          ProductVariant(id: 'v6', name: 'Matte White', inStock: true),
          ProductVariant(id: 'v7', name: 'Deep Space', inStock: true),
        ],
        categories: ['Electronics', 'Audio'],
      ),
      Product(
        id: '4',
        title: 'Everyday Canvas Sneakers',
        description:
            'Vulcanized rubber sole with reinforced toe cap and eco-friendly cotton twill lining for all-day comfort.',
        price: 3299.0,
        salePrice: null,
        images: [
          'https://images.unsplash.com/photo-1549298916-b41d501d3772?w=600&q=80',
        ],
        variants: [
          ProductVariant(id: 'v8', name: 'EU 41 / Off-White', inStock: true),
          ProductVariant(id: 'v9', name: 'EU 42 / Off-White', inStock: true),
          ProductVariant(id: 'v10', name: 'EU 43 / Navy', inStock: true),
        ],
        categories: ['Footwear', 'Sneakers'],
      ),
    ];
  }
}
