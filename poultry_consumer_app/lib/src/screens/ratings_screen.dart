import 'package:flutter/material.dart';

import '../models/consumer_session.dart';
import '../services/api_service.dart';

class RatingsScreen extends StatefulWidget {
  const RatingsScreen({
    super.key,
    required this.session,
  });

  final ConsumerSession session;

  @override
  State<RatingsScreen> createState() => _RatingsScreenState();
}

class _RatingsScreenState extends State<RatingsScreen> {
  final _api = const ApiService();
  late Future<List<Map<String, dynamic>>> _future;

  @override
  void initState() {
    super.initState();
    _future = _api.fetchRatings(widget.session.token);
  }

  void _refresh() {
    setState(() {
      _future = _api.fetchRatings(widget.session.token);
    });
  }

  Future<void> _submitRating(int deliveryId, int rating) async {
    try {
      await _api.submitRating(
        token: widget.session.token,
        deliveryId: deliveryId,
        rating: rating,
      );
      if (!mounted) {
        return;
      }
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Rating submitted.')),
      );
      _refresh();
    } catch (error) {
      if (!mounted) {
        return;
      }
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(error.toString().replaceFirst('Exception: ', ''))),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: Column(
        children: [
          ListTile(
            title: const Text('Order Ratings'),
            subtitle: const Text('Rate delivered orders and service quality'),
            trailing: IconButton(onPressed: _refresh, icon: const Icon(Icons.refresh)),
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
                  return const Center(child: Text('No delivered orders available for rating.'));
                }

                return ListView.separated(
                  padding: const EdgeInsets.fromLTRB(16, 4, 16, 20),
                  itemCount: items.length,
                  separatorBuilder: (_, index) => const SizedBox(height: 10),
                  itemBuilder: (context, index) {
                    final item = items[index];
                    final currentRating = (item['rating'] as num?)?.toInt() ?? 0;
                    final deliveryId = (item['id'] as num?)?.toInt() ?? 0;

                    return Card(
                      child: Padding(
                        padding: const EdgeInsets.all(12),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Order ${item['order_number'] ?? ''}',
                              style: Theme.of(context)
                                  .textTheme
                                  .titleSmall
                                  ?.copyWith(fontWeight: FontWeight.w700),
                            ),
                            const SizedBox(height: 4),
                            Text('Farm: ${item['farm_name'] ?? 'Farm'}'),
                            const SizedBox(height: 8),
                            Wrap(
                              spacing: 6,
                              children: List.generate(5, (starIndex) {
                                final score = starIndex + 1;
                                final active = score <= currentRating;
                                return IconButton.filledTonal(
                                  onPressed: deliveryId > 0 ? () => _submitRating(deliveryId, score) : null,
                                  icon: Icon(
                                    Icons.star,
                                    color: active ? const Color(0xFFF59E0B) : const Color(0xFF94A3B8),
                                  ),
                                );
                              }),
                            ),
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
