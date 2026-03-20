import 'package:flutter/foundation.dart';

class AppConfig {
  const AppConfig._();

  static const String environment = String.fromEnvironment(
    'APP_ENV',
    defaultValue: 'dev',
  );

  static const String _apiBaseUrlOverride = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: '',
  );

  static bool get isProduction {
    final env = environment.toLowerCase();
    return env == 'production' || env == 'prod';
  }

  static String get apiBaseUrl {
    final override = _apiBaseUrlOverride.trim();
    if (override.isNotEmpty) {
      return override;
    }

    switch (environment.toLowerCase()) {
      case 'production':
      case 'prod':
        return 'https://api.poultry-system.com';
      case 'staging':
        return 'https://staging-api.poultry-system.com';
    }

    if (kIsWeb || defaultTargetPlatform != TargetPlatform.android) {
      return 'http://127.0.0.1:8000';
    }

    return 'http://10.0.2.2:8000';
  }

  static Duration get ordersPollingInterval => const Duration(seconds: 20);

  static Duration get notificationsPollingInterval => const Duration(seconds: 25);

  static Duration get requestTimeout => const Duration(seconds: 15);

  static int get networkRetryCount => isProduction ? 1 : 0;
}
