import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter/scheduler.dart';
import 'package:url_launcher/url_launcher.dart';

import '../config/app_config.dart';
import '../models/consumer_session.dart';
import '../services/api_service.dart';

class OrdersScreen extends StatefulWidget {
  const OrdersScreen({
    super.key,
    required this.session,
  });

  final ConsumerSession session;

  @override
  State<OrdersScreen> createState() => _OrdersScreenState();
}

class _OrdersScreenState extends State<OrdersScreen> with WidgetsBindingObserver {
  final _api = const ApiService();
  late Future<List<Map<String, dynamic>>> _future;
  int? _cancellingOrderId;
  int? _retryingOrderId;
  Timer? _pollTimer;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _future = _api.fetchOrders(widget.session.token);
    _startPolling();
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _pollTimer?.cancel();
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
    _pollTimer = Timer.periodic(AppConfig.ordersPollingInterval, (_) {
      if (!mounted || SchedulerBinding.instance.lifecycleState != AppLifecycleState.resumed) {
        return;
      }

      _refresh();
    });
  }

  void _refresh() {
    setState(() {
      _future = _api.fetchOrders(widget.session.token);
    });
  }

  Future<void> _retryPayment(Map<String, dynamic> order) async {
    final orderId = int.tryParse(order['id'].toString());
    if (orderId == null) {
      return;
    }

    setState(() => _retryingOrderId = orderId);
    try {
      final result = await _api.retryOrderPayment(
        token: widget.session.token,
        orderId: orderId,
      );

      final payment = result['payment'];
      final checkoutUrl = payment is Map<String, dynamic>
          ? payment['checkout_url']?.toString()
          : null;

      if (checkoutUrl == null || checkoutUrl.isEmpty) {
        throw Exception('Payment link was not generated.');
      }

      final uri = Uri.tryParse(checkoutUrl);
      final launched = uri != null && await launchUrl(uri, mode: LaunchMode.externalApplication);

      if (!mounted) {
        return;
      }

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            launched
                ? 'Checkout reopened. Complete payment to update this order.'
                : 'Checkout generated, but could not open it automatically.',
          ),
        ),
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
        setState(() => _retryingOrderId = null);
      }
    }
  }

  Future<void> _cancelOrder(Map<String, dynamic> order) async {
    final orderId = int.tryParse(order['id'].toString());
    if (orderId == null) {
      return;
    }

    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) {
        return AlertDialog(
          title: const Text('Cancel Order?'),
          content: Text(
            'This will cancel ${order['order_number'] ?? 'your order'} and restore the reserved stock.',
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(context).pop(false),
              child: const Text('Keep Order'),
            ),
            FilledButton(
              onPressed: () => Navigator.of(context).pop(true),
              child: const Text('Cancel Order'),
            ),
          ],
        );
      },
    );

    if (confirmed != true || !mounted) {
      return;
    }

    setState(() => _cancellingOrderId = orderId);
    try {
      await _api.cancelOrder(token: widget.session.token, orderId: orderId);

      if (!mounted) {
        return;
      }

      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Order cancelled successfully.')),
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
        setState(() => _cancellingOrderId = null);
      }
    }
  }

  ({Color background, Color foreground}) _statusColors(String statusCode) {
    switch (statusCode) {
      case 'pending':
        return (background: const Color(0xFF3F2A07), foreground: const Color(0xFFFBBF24));
      case 'confirmed':
        return (background: const Color(0xFF1E3A8A), foreground: const Color(0xFFBFDBFE));
      case 'processing':
      case 'preparing':
        return (background: const Color(0xFF3B2F0E), foreground: const Color(0xFFFDE68A));
      case 'ready_for_pickup':
      case 'packed':
        return (background: const Color(0xFF312E81), foreground: const Color(0xFFC7D2FE));
      case 'assigned':
        return (background: const Color(0xFF3730A3), foreground: const Color(0xFFA5B4FC));
      case 'out_for_delivery':
      case 'shipped':
        return (background: const Color(0xFF0C4A6E), foreground: const Color(0xFFBAE6FD));
      case 'delivered':
        return (background: const Color(0xFF14532D), foreground: const Color(0xFFBBF7D0));
      case 'completed':
        return (background: const Color(0xFF065F46), foreground: const Color(0xFFA7F3D0));
      case 'cancelled':
      case 'refunded':
        return (background: const Color(0xFF7F1D1D), foreground: const Color(0xFFFECACA));
      default:
        return (background: const Color(0xFF1F2937), foreground: const Color(0xFFE5E7EB));
    }
  }

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: Column(
        children: [
          ListTile(
            title: const Text('My Orders'),
            subtitle: const Text('Track your purchases and current status'),
            trailing: IconButton(
              onPressed: _refresh,
              icon: const Icon(Icons.refresh),
            ),
          ),
          Expanded(
            child: FutureBuilder<List<Map<String, dynamic>>>(
              future: _future,
              builder: (context, snapshot) {
                if (snapshot.connectionState == ConnectionState.waiting) {
                  return const Center(child: CircularProgressIndicator());
                }

                if (snapshot.hasError) {
                  return Center(
                    child: Padding(
                      padding: const EdgeInsets.all(20),
                      child: Text(
                        snapshot.error.toString().replaceFirst('Exception: ', ''),
                        textAlign: TextAlign.center,
                      ),
                    ),
                  );
                }

                final orders = snapshot.data ?? const [];
                if (orders.isEmpty) {
                  return const Center(child: Text('No orders yet.'));
                }

                return ListView.separated(
                  padding: const EdgeInsets.fromLTRB(16, 4, 16, 20),
                  itemCount: orders.length,
                  separatorBuilder: (_, index) => const SizedBox(height: 10),
                  itemBuilder: (context, index) {
                    final order = orders[index];
                    final amount = (order['total_amount'] ?? 0).toString();
                    final statusCode = (order['consumer_status'] ?? order['status'] ?? '-')
                      .toString();
                    final statusLabel = (order['consumer_status_label'] ?? order['status'] ?? '-')
                      .toString();
                    final colors = _statusColors(statusCode);
                    final canCancel = order['can_cancel'] == true;
                    final canRetryPayment = order['can_retry_payment'] == true;
                    final paymentMethod = order['payment_method']?.toString() ?? 'N/A';
                    final items = (order['items'] as List<dynamic>? ?? const [])
                        .whereType<Map<String, dynamic>>()
                        .toList();

                    return Card(
                      child: Padding(
                        padding: const EdgeInsets.all(14),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              order['order_number']?.toString() ?? 'Order',
                              style: Theme.of(context)
                                  .textTheme
                                  .titleMedium
                                  ?.copyWith(fontWeight: FontWeight.w800),
                            ),
                            const SizedBox(height: 5),
                            Text('Farm: ${order['farm_name'] ?? 'Farm'}'),
                            const SizedBox(height: 2),
                            Row(
                              children: [
                                const Text('Status: '),
                                Container(
                                  padding: const EdgeInsets.symmetric(
                                    horizontal: 8,
                                    vertical: 3,
                                  ),
                                  decoration: BoxDecoration(
                                    color: colors.background,
                                    borderRadius: BorderRadius.circular(999),
                                    border: Border.all(
                                      color: colors.foreground.withValues(alpha: 0.35),
                                    ),
                                  ),
                                  child: Text(
                                    statusLabel,
                                    style: Theme.of(context).textTheme.labelSmall?.copyWith(
                                      color: colors.foreground,
                                      fontWeight: FontWeight.w700,
                                    ),
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 2),
                            Text('Payment: ${order['payment_status'] ?? '-'}'),
                            const SizedBox(height: 2),
                            Text('Method: $paymentMethod'),
                            const SizedBox(height: 2),
                            Text('Total: PHP $amount'),
                            if (items.isNotEmpty) ...[
                              const SizedBox(height: 10),
                              for (final item in items)
                                Padding(
                                  padding: const EdgeInsets.only(bottom: 4),
                                  child: Text(
                                    '${item['quantity']}x ${item['product_name']} • PHP ${item['total_price']}',
                                    style: Theme.of(context).textTheme.bodySmall,
                                  ),
                                ),
                            ],
                            if (canCancel) ...[
                              const SizedBox(height: 12),
                              Align(
                                alignment: Alignment.centerRight,
                                child: OutlinedButton.icon(
                                  onPressed: _cancellingOrderId == order['id']
                                      ? null
                                      : () => _cancelOrder(order),
                                  icon: const Icon(Icons.cancel_outlined),
                                  label: Text(
                                    _cancellingOrderId == order['id']
                                        ? 'Cancelling...'
                                        : 'Cancel Order',
                                  ),
                                ),
                              ),
                            ],
                            if (canRetryPayment) ...[
                              const SizedBox(height: 8),
                              Align(
                                alignment: Alignment.centerRight,
                                child: FilledButton.tonalIcon(
                                  onPressed: _retryingOrderId == order['id']
                                      ? null
                                      : () => _retryPayment(order),
                                  icon: const Icon(Icons.payments_outlined),
                                  label: Text(
                                    _retryingOrderId == order['id']
                                        ? 'Opening Checkout...'
                                        : 'Retry Payment',
                                  ),
                                ),
                              ),
                            ],
                          ],
                        ),
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
