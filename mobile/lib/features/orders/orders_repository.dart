import 'package:dio/dio.dart';
import '../../core/network/dio_client.dart';
import '../../core/network/error_mapper.dart';
import 'orders_model.dart';

class OrdersRepository {
  final DioClient _dioClient;

  OrdersRepository({DioClient? dioClient})
      : _dioClient = dioClient ?? DioClient();

  /// Fetch orders for authenticated customer
  Future<List<CustomerOrder>> getOrders({int page = 1, int perPage = 20}) async {
    try {
      final response = await _dioClient.dio.get(
        '/v1/orders',
        queryParameters: {'page': page, 'per_page': perPage},
      );

      final list = (response.data['data'] as List<dynamic>?) ?? [];
      return list
          .map((item) => CustomerOrder.fromJson(item as Map<String, dynamic>))
          .toList();
    } on DioException catch (e) {
      throw ErrorMapper.fromDioError(e);
    }
  }

  /// Fetch single order details
  Future<CustomerOrder> getOrder(String id) async {
    try {
      final response = await _dioClient.dio.get('/v1/orders/$id');
      final data = response.data['data'] as Map<String, dynamic>;
      return CustomerOrder.fromJson(data);
    } on DioException catch (e) {
      throw ErrorMapper.fromDioError(e);
    }
  }
}
