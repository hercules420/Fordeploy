import 'package:flutter/material.dart';
import 'dart:async';

import 'models/consumer_session.dart';
import 'services/api_service.dart';
import 'services/session_storage_service.dart';
import 'screens/consumer_login_screen.dart';
import 'screens/home_shell.dart';

class ConsumerShopApp extends StatelessWidget {
  const ConsumerShopApp({super.key});

  @override
  Widget build(BuildContext context) {
    const brand = Color(0xFFEA580C);

    return MaterialApp(
      title: 'Poultry Consumer Shop',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        useMaterial3: true,
        colorScheme: ColorScheme.fromSeed(
          seedColor: brand,
          brightness: Brightness.dark,
        ),
        fontFamily: 'Trebuchet MS',
        scaffoldBackgroundColor: const Color(0xFF0B1220),
        appBarTheme: const AppBarTheme(
          backgroundColor: Color(0xFF0B1220),
          foregroundColor: Color(0xFFF8FAFC),
          surfaceTintColor: Colors.transparent,
        ),
        cardTheme: CardThemeData(
          color: const Color(0xFF111827),
          elevation: 0,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(20),
            side: const BorderSide(color: Color(0xFF374151)),
          ),
        ),
        navigationBarTheme: const NavigationBarThemeData(
          height: 74,
          backgroundColor: Color(0xFF111827),
          indicatorColor: Color(0xFFEA580C),
          labelTextStyle: WidgetStatePropertyAll(
            TextStyle(fontWeight: FontWeight.w700),
          ),
        ),
        inputDecorationTheme: InputDecorationTheme(
          filled: true,
          fillColor: const Color(0xFF0F172A),
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(14),
            borderSide: const BorderSide(color: Color(0xFF374151)),
          ),
          enabledBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(14),
            borderSide: const BorderSide(color: Color(0xFF374151)),
          ),
          focusedBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(14),
            borderSide: const BorderSide(color: Color(0xFFEA580C), width: 1.5),
          ),
        ),
      ),
      home: const ConsumerLoginGateway(),
    );
  }
}

class ConsumerLoginGateway extends StatefulWidget {
  const ConsumerLoginGateway({super.key});

  @override
  State<ConsumerLoginGateway> createState() => _ConsumerLoginGatewayState();
}

class _ConsumerLoginGatewayState extends State<ConsumerLoginGateway> {
  final _sessionStorage = const SessionStorageService();
  final _api = const ApiService();

  ConsumerSession? _session;
  bool _restoringSession = true;
  StreamSubscription<DateTime>? _unauthorizedSubscription;

  @override
  void initState() {
    super.initState();
    _unauthorizedSubscription = ApiService.unauthorizedEvents.listen((_) {
      if (!mounted || _session == null) {
        return;
      }

      _logout();
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Session expired. Please log in again.')),
      );
    });
    _restoreSession();
  }

  @override
  void dispose() {
    _unauthorizedSubscription?.cancel();
    super.dispose();
  }

  Future<void> _restoreSession() async {
    final stored = await _sessionStorage
        .readSession()
        .timeout(const Duration(milliseconds: 600), onTimeout: () => null);
    if (stored == null) {
      if (mounted) {
        setState(() => _restoringSession = false);
      }
      return;
    }

    try {
      final profile = await _api.fetchProfile(stored.token);
      final restored = stored.copyWith(
        name: (profile['name'] ?? stored.name).toString(),
        phone: (profile['phone'] ?? stored.phone)?.toString(),
        location: (profile['location'] ?? stored.location)?.toString(),
      );

      await _sessionStorage.saveSession(restored);

      if (!mounted) {
        return;
      }

      setState(() {
        _session = restored;
        _restoringSession = false;
      });
    } catch (_) {
      await _sessionStorage.clearSession();

      if (!mounted) {
        return;
      }

      setState(() => _restoringSession = false);
    }
  }

  Future<void> _handleLoginSuccess(ConsumerSession session) async {
    await _sessionStorage.saveSession(session);
    if (!mounted) {
      return;
    }

    setState(() => _session = session);
  }

  Future<void> _logout() async {
    final token = _session?.token;
    if (token != null && token.isNotEmpty) {
      try {
        await _api.logout(token);
      } catch (_) {
        // Clear local session regardless of remote logout result.
      }
    }

    await _sessionStorage.clearSession();

    if (!mounted) {
      return;
    }

    setState(() => _session = null);
  }

  @override
  Widget build(BuildContext context) {
    if (_restoringSession) {
      return const Scaffold(
        body: Center(child: CircularProgressIndicator()),
      );
    }

    if (_session != null) {
      return HomeShell(
        session: _session!,
        onLogoutRequested: _logout,
      );
    }

    return ConsumerLoginScreen(
      onLoginSuccess: _handleLoginSuccess,
    );
  }
}
