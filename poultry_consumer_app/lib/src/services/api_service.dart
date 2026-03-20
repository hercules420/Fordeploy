import 'dart:convert';
import 'dart:async';

import 'package:http/http.dart' as http;

import '../config/app_config.dart';
import '../models/cart_item.dart';
import '../models/consumer_session.dart';
import '../models/product.dart';

class ApiService {
  const ApiService();

  static final StreamController<DateTime> _unauthorizedController = StreamController<DateTime>.broadcast();
  static DateTime? _lastUnauthorizedEventAt;

  static Stream<DateTime> get unauthorizedEvents => _unauthorizedController.stream;

  Map<String, String> _jsonHeaders([String? token]) {
    return {
      'Content-Type': 'application/json',
      if (token != null && token.isNotEmpty) 'Authorization': 'Bearer $token',
    };
  }

  void _emitUnauthorizedEvent() {
    final now = DateTime.now();
    if (_lastUnauthorizedEventAt != null && now.difference(_lastUnauthorizedEventAt!).inSeconds < 2) {
      return;
    }

    _lastUnauthorizedEventAt = now;
    _unauthorizedController.add(now);
  }

  Map<String, dynamic> _decodeResponseBody(String body) {
    if (body.trim().isEmpty) {
      return const {};
    }

    try {
      final decoded = jsonDecode(body);
      if (decoded is Map<String, dynamic>) {
        return decoded;
      }
    } catch (_) {
      // Fall through to default map.
    }

    return {'message': body};
  }

  String _extractErrorMessage(Map<String, dynamic> body, String fallback) {
    final message = body['message']?.toString().trim();
    if (message != null && message.isNotEmpty) {
      return message;
    }

    final errors = body['errors'];
    if (errors is Map<String, dynamic>) {
      for (final value in errors.values) {
        if (value is List && value.isNotEmpty) {
          final first = value.first.toString().trim();
          if (first.isNotEmpty) {
            return first;
          }
        }

        final text = value.toString().trim();
        if (text.isNotEmpty) {
          return text;
        }
      }
    }

    return fallback;
  }

  Future<http.Response> _sendWithRetry(Future<http.Response> Function() requestBuilder) async {
    var attempt = 0;
    while (true) {
      try {
        final response = await requestBuilder().timeout(AppConfig.requestTimeout);

        if (response.statusCode == 401) {
          _emitUnauthorizedEvent();
        }

        if (response.statusCode >= 500 && attempt < AppConfig.networkRetryCount) {
          attempt++;
          await Future<void>.delayed(const Duration(milliseconds: 500));
          continue;
        }

        return response;
      } on TimeoutException {
        if (attempt < AppConfig.networkRetryCount) {
          attempt++;
          await Future<void>.delayed(const Duration(milliseconds: 500));
          continue;
        }
        throw Exception('Request timed out. Please check your connection and try again.');
      } catch (_) {
        if (attempt < AppConfig.networkRetryCount) {
          attempt++;
          await Future<void>.delayed(const Duration(milliseconds: 500));
          continue;
        }
        throw Exception('Network request failed. Please try again.');
      }
    }
  }

  Future<ConsumerSession> login({
    required String email,
    required String password,
  }) async {
    final uri = Uri.parse('${AppConfig.apiBaseUrl}/api/mobile/auth/login');
    final response = await _sendWithRetry(() => http.post(
      uri,
      headers: _jsonHeaders(),
      body: jsonEncode({'email': email, 'password': password}),
    ));

    final body = _decodeResponseBody(response.body);
    if (response.statusCode >= 200 && response.statusCode < 300) {
      return ConsumerSession.fromJson(
        (body['data'] ?? <String, dynamic>{}) as Map<String, dynamic>,
      );
    }

    throw Exception(_extractErrorMessage(body, 'Login failed'));
  }

  Future<void> logout(String token) async {
    final uri = Uri.parse('${AppConfig.apiBaseUrl}/api/mobile/auth/logout');

    final response = await _sendWithRetry(() => http.post(uri, headers: _jsonHeaders(token)));
    if (response.statusCode >= 200 && response.statusCode < 300) {
      return;
    }

    final body = _decodeResponseBody(response.body);
    throw Exception(_extractErrorMessage(body, 'Logout failed'));
  }

  Future<List<Product>> fetchProducts({String query = ''}) async {
    final uri = Uri.parse('${AppConfig.apiBaseUrl}/api/mobile/products').replace(
      queryParameters: query.trim().isEmpty ? null : {'q': query.trim()},
    );

    try {
      final response = await _sendWithRetry(() => http.get(uri));

      if (response.statusCode == 200) {
        final body = _decodeResponseBody(response.body);
        final items = (body['data'] ?? []) as List<dynamic>;
        return items
            .whereType<Map<String, dynamic>>()
            .map(Product.fromJson)
            .where((product) => product.stock > 0)
            .toList();
      }
    } catch (_) {
      // Keep homepage empty until backend provides actual stocked products.
    }

    return const [];
  }

  Future<List<Map<String, dynamic>>> fetchOrders(String token) async {
    final uri = Uri.parse('${AppConfig.apiBaseUrl}/api/mobile/orders');

    final response = await _sendWithRetry(() => http.get(uri, headers: _jsonHeaders(token)));
    final body = _decodeResponseBody(response.body);

    if (response.statusCode >= 200 && response.statusCode < 300) {
      return ((body['data'] ?? []) as List<dynamic>)
          .whereType<Map<String, dynamic>>()
          .toList();
    }

    throw Exception(_extractErrorMessage(body, 'Failed to load orders'));
  }

  Future<Map<String, dynamic>> placeOrder({
    required String token,
    required List<CartItem> items,
    required String deliveryAddress,
    required String deliveryCity,
    required String deliveryProvince,
    required String deliveryPostalCode,
    required String paymentMethod,
  }) async {
    final uri = Uri.parse('${AppConfig.apiBaseUrl}/api/mobile/orders');
    final payload = {
      'delivery_address': deliveryAddress,
      'delivery_city': deliveryCity,
      'delivery_province': deliveryProvince,
      'delivery_postal_code': deliveryPostalCode,
      'payment_method': paymentMethod,
      'items': items
          .map((item) => {
                'product_id': item.product.id,
                'quantity': item.quantity,
              })
          .toList(),
    };

    final response = await _sendWithRetry(() => http.post(
      uri,
      headers: _jsonHeaders(token),
      body: jsonEncode(payload),
    ));

    final body = _decodeResponseBody(response.body);

    if (response.statusCode < 200 || response.statusCode >= 300) {
      throw Exception(_extractErrorMessage(body, 'Failed to place order'));
    }

    return ((body['data'] ?? <String, dynamic>{}) as Map<String, dynamic>);
  }

  Future<void> cancelOrder({
    required String token,
    required int orderId,
  }) async {
    final uri = Uri.parse('${AppConfig.apiBaseUrl}/api/mobile/orders/$orderId/cancel');

    final response = await _sendWithRetry(() => http.post(uri, headers: _jsonHeaders(token)));
    final body = _decodeResponseBody(response.body);

    if (response.statusCode < 200 || response.statusCode >= 300) {
      throw Exception(_extractErrorMessage(body, 'Failed to cancel order'));
    }
  }

  Future<Map<String, dynamic>> retryOrderPayment({
    required String token,
    required int orderId,
  }) async {
    final uri = Uri.parse('${AppConfig.apiBaseUrl}/api/mobile/orders/$orderId/retry-payment');

    final response = await _sendWithRetry(() => http.post(uri, headers: _jsonHeaders(token)));
    final body = _decodeResponseBody(response.body);

    if (response.statusCode < 200 || response.statusCode >= 300) {
      throw Exception(_extractErrorMessage(body, 'Failed to retry payment'));
    }

    return ((body['data'] ?? <String, dynamic>{}) as Map<String, dynamic>);
  }

  Future<Map<String, dynamic>> fetchProfile(String token) async {
    final uri = Uri.parse('${AppConfig.apiBaseUrl}/api/mobile/profile');

    final response = await _sendWithRetry(() => http.get(uri, headers: _jsonHeaders(token)));
    final body = _decodeResponseBody(response.body);

    if (response.statusCode >= 200 && response.statusCode < 300) {
      return ((body['data'] ?? <String, dynamic>{}) as Map<String, dynamic>);
    }

    throw Exception(_extractErrorMessage(body, 'Failed to load profile'));
  }

  Future<Map<String, dynamic>> updateProfile({
    required String token,
    required String name,
    required String phone,
    required String location,
  }) async {
    final uri = Uri.parse('${AppConfig.apiBaseUrl}/api/mobile/profile');
    final response = await _sendWithRetry(() => http.patch(
      uri,
      headers: _jsonHeaders(token),
      body: jsonEncode({
        'name': name,
        'phone': phone,
        'location': location,
      }),
    ));

    final body = _decodeResponseBody(response.body);
    if (response.statusCode >= 200 && response.statusCode < 300) {
      return ((body['data'] ?? <String, dynamic>{}) as Map<String, dynamic>);
    }

    throw Exception(_extractErrorMessage(body, 'Failed to update profile'));
  }

  Future<List<Map<String, dynamic>>> fetchNotifications(String token) async {
    final uri = Uri.parse('${AppConfig.apiBaseUrl}/api/mobile/notifications');

    final response = await _sendWithRetry(() => http.get(uri, headers: _jsonHeaders(token)));
    final body = _decodeResponseBody(response.body);

    if (response.statusCode >= 200 && response.statusCode < 300) {
      return ((body['data'] ?? []) as List<dynamic>)
          .whereType<Map<String, dynamic>>()
          .toList();
    }

    throw Exception(_extractErrorMessage(body, 'Failed to load notifications'));
  }

  Future<void> submitComplaint({
    required String token,
    required int orderId,
    required String subject,
    required String message,
  }) async {
    final uri = Uri.parse('${AppConfig.apiBaseUrl}/api/mobile/complaints');

    final response = await _sendWithRetry(() => http.post(
      uri,
      headers: _jsonHeaders(token),
      body: jsonEncode({
        'order_id': orderId,
        'subject': subject,
        'message': message,
      }),
    ));

    if (response.statusCode < 200 || response.statusCode >= 300) {
      final body = _decodeResponseBody(response.body);
      throw Exception(_extractErrorMessage(body, 'Failed to submit complaint'));
    }
  }

  Future<List<Map<String, dynamic>>> fetchRatings(String token) async {
    final uri = Uri.parse('${AppConfig.apiBaseUrl}/api/mobile/ratings');

    final response = await _sendWithRetry(() => http.get(uri, headers: _jsonHeaders(token)));
    final body = _decodeResponseBody(response.body);

    if (response.statusCode >= 200 && response.statusCode < 300) {
      return ((body['data'] ?? []) as List<dynamic>)
          .whereType<Map<String, dynamic>>()
          .toList();
    }

    throw Exception(_extractErrorMessage(body, 'Failed to load ratings'));
  }

  Future<void> submitRating({
    required String token,
    required int deliveryId,
    required int rating,
    String feedback = '',
  }) async {
    final uri = Uri.parse('${AppConfig.apiBaseUrl}/api/mobile/ratings/$deliveryId');

    final response = await _sendWithRetry(() => http.post(
      uri,
      headers: _jsonHeaders(token),
      body: jsonEncode({
        'rating': rating,
        'feedback': feedback,
      }),
    ));

    if (response.statusCode < 200 || response.statusCode >= 300) {
      final body = _decodeResponseBody(response.body);
      throw Exception(_extractErrorMessage(body, 'Failed to submit rating'));
    }
  }
}
