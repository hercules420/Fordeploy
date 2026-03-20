import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter/scheduler.dart';

import '../config/app_config.dart';
import '../models/consumer_session.dart';
import '../services/api_service.dart';

class NotificationsScreen extends StatefulWidget {
  const NotificationsScreen({
    super.key,
    required this.session,
  });

  final ConsumerSession session;

  @override
  State<NotificationsScreen> createState() => _NotificationsScreenState();
}

class _NotificationsScreenState extends State<NotificationsScreen> with WidgetsBindingObserver {
  final _api = const ApiService();
  late Future<List<Map<String, dynamic>>> _future;
  Timer? _pollTimer;

  final _orderIdCtrl = TextEditingController();
  final _subjectCtrl = TextEditingController();
  final _messageCtrl = TextEditingController();
  bool _sending = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _future = _api.fetchNotifications(widget.session.token);
    _startPolling();
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _pollTimer?.cancel();
    _orderIdCtrl.dispose();
    _subjectCtrl.dispose();
    _messageCtrl.dispose();
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      _refresh();
    }
  }

  void _startPolling() {
    _pollTimer?.cancel();
    _pollTimer = Timer.periodic(AppConfig.notificationsPollingInterval, (_) {
      if (!mounted || SchedulerBinding.instance.lifecycleState != AppLifecycleState.resumed) {
        return;
      }

      _refresh();
    });
  }

  void _refresh() {
    setState(() {
      _future = _api.fetchNotifications(widget.session.token);
    });
  }

  Future<void> _sendComplaint() async {
    final orderId = int.tryParse(_orderIdCtrl.text.trim());
    if (orderId == null || _subjectCtrl.text.trim().isEmpty || _messageCtrl.text.trim().isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Order ID, subject, and message are required.')),
      );
      return;
    }

    setState(() => _sending = true);
    try {
      await _api.submitComplaint(
        token: widget.session.token,
        orderId: orderId,
        subject: _subjectCtrl.text.trim(),
        message: _messageCtrl.text.trim(),
      );

      if (!mounted) {
        return;
      }

      _subjectCtrl.clear();
      _messageCtrl.clear();
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Complaint sent successfully.')),
      );
      _refresh();
    } catch (error) {
      if (!mounted) {
        return;
      }
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(error.toString().replaceFirst('Exception: ', ''))),
      );
    } finally {
      if (mounted) {
        setState(() => _sending = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: Column(
        children: [
          ListTile(
            title: const Text('Notifications & Complaints'),
            subtitle: const Text('Read updates and report marketplace issues'),
            trailing: IconButton(onPressed: _refresh, icon: const Icon(Icons.refresh)),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 4, 16, 8),
            child: Card(
              child: Padding(
                padding: const EdgeInsets.all(12),
                child: Column(
                  children: [
                    TextField(
                      controller: _orderIdCtrl,
                      keyboardType: TextInputType.number,
                      decoration: const InputDecoration(labelText: 'Order ID'),
                    ),
                    const SizedBox(height: 8),
                    TextField(
                      controller: _subjectCtrl,
                      decoration: const InputDecoration(labelText: 'Complaint Subject'),
                    ),
                    const SizedBox(height: 8),
                    TextField(
                      controller: _messageCtrl,
                      maxLines: 2,
                      decoration: const InputDecoration(labelText: 'Complaint Message'),
                    ),
                    const SizedBox(height: 10),
                    SizedBox(
                      width: double.infinity,
                      child: FilledButton(
                        onPressed: _sending ? null : _sendComplaint,
                        child: Text(_sending ? 'Sending...' : 'Send Complaint'),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
          Expanded(
            child: FutureBuilder<List<Map<String, dynamic>>>(
              future: _future,
              builder: (context, snapshot) {
                if (snapshot.connectionState == ConnectionState.waiting) {
                  return const Center(child: CircularProgressIndicator());
                }

                final items = snapshot.data ?? const [];
                if (items.isEmpty) {
                  return const Center(child: Text('No notifications yet.'));
                }

                return ListView.separated(
                  padding: const EdgeInsets.fromLTRB(16, 4, 16, 20),
                  itemCount: items.length,
                  separatorBuilder: (_, index) => const SizedBox(height: 10),
                  itemBuilder: (context, index) {
                    final item = items[index];
                    return Card(
                      child: ListTile(
                        title: Text(item['title']?.toString() ?? 'Notification'),
                        subtitle: Text(item['message']?.toString() ?? ''),
                      ),
                    );
                  },
                );
              },
            ),
          ),
        ],
      ),
    );
  }
}
