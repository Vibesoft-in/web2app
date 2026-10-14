import 'package:dio/dio.dart';

class NetworkException implements Exception {
  final String code;
  final String message;
  final int? statusCode;

  const NetworkException({
    required this.code,
    required this.message,
    this.statusCode,
  });

  @override
  String toString() => 'NetworkException($code): $message';
}

class ErrorMapper {
  ErrorMapper._();

  static NetworkException fromDioError(DioException error) {
    if (error.type == DioExceptionType.connectionTimeout ||
        error.type == DioExceptionType.sendTimeout ||
        error.type == DioExceptionType.receiveTimeout) {
      return const NetworkException(
        code: 'TIMEOUT',
        message: 'Connection timed out. Please check your internet connection.',
      );
    }

    if (error.type == DioExceptionType.connectionError) {
      return const NetworkException(
        code: 'NO_INTERNET',
        message: 'Unable to connect to store servers. Please verify your connection.',
      );
    }

    final response = error.response;
    if (response != null) {
      final statusCode = response.statusCode;
      final data = response.data;

      if (data is Map<String, dynamic>) {
        final errorObj = data['error'];
        if (errorObj is Map<String, dynamic>) {
          final code = errorObj['code'] as String? ?? 'SERVER_ERROR';
          final message = errorObj['message'] as String? ?? _friendlyMessage(code, statusCode);
          return NetworkException(
            code: code,
            message: message,
            statusCode: statusCode,
          );
        }

        final msg = data['message'] as String?;
        if (msg != null) {
          return NetworkException(
            code: 'HTTP_$statusCode',
            message: msg,
            statusCode: statusCode,
          );
        }
      }

      return NetworkException(
        code: 'HTTP_$statusCode',
        message: _defaultStatusMessage(statusCode),
        statusCode: statusCode,
      );
    }

    return NetworkException(
      code: 'UNKNOWN',
      message: error.message ?? 'An unexpected network error occurred.',
    );
  }

  static String _friendlyMessage(String code, int? status) {
    switch (code) {
      case 'URL_UNREACHABLE':
        return 'Store server is currently unreachable. Please verify domain status.';
      case 'NOT_HTTPS':
        return 'Secure HTTPS connection required.';
      case 'REST_BLOCKED':
        return 'Store REST API appears blocked or disabled.';
      case 'INVALID_KEYS':
        return 'Store API credentials are invalid or expired.';
      case 'STEP_LOCKED':
        return 'This onboarding step is currently locked.';
      case 'UNAUTHENTICATED':
        return 'Your session has expired. Please log in again.';
      case 'MAINTENANCE_MODE':
        return 'The store is currently undergoing scheduled maintenance.';
      default:
        return _defaultStatusMessage(status);
    }
  }

  static String _defaultStatusMessage(int? statusCode) {
    switch (statusCode) {
      case 400:
        return 'Invalid request format.';
      case 401:
        return 'Session expired. Please sign in again.';
      case 403:
        return 'Access denied.';
      case 404:
        return 'The requested resource was not found.';
      case 422:
        return 'Please review the submitted form fields.';
      case 429:
        return 'Too many requests. Please wait a moment.';
      case 500:
      case 502:
      case 503:
        return 'Store server error. Our engineering team is on it.';
      default:
        return 'Something went wrong. Please try again.';
    }
  }
}
