import 'dart:convert';

import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import '../models/consumer_session.dart';

class SessionStorageService {
  static const _sessionKey = 'consumer_session_v1';

  const SessionStorageService();

  FlutterSecureStorage get _storage => const FlutterSecureStorage();

  Future<void> saveSession(ConsumerSession session) async {
    try {
      await _storage.write(
        key: _sessionKey,
        value: jsonEncode(session.toJson()),
      );
    } catch (_) {
      // Ignore storage errors on unsupported targets/tests.
    }
  }

  Future<ConsumerSession?> readSession() async {
    try {
      final raw = await _storage.read(key: _sessionKey);
      if (raw == null || raw.isEmpty) {
        return null;
      }

      final json = jsonDecode(raw);
      if (json is! Map<String, dynamic>) {
        return null;
      }

      return ConsumerSession.fromJson(json);
    } catch (_) {
      return null;
    }
  }

  Future<void> clearSession() async {
    try {
      await _storage.delete(key: _sessionKey);
    } catch (_) {
      // Ignore storage errors on unsupported targets/tests.
    }
  }
}