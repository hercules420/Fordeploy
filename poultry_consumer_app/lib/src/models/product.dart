import '../config/app_config.dart';

class Product {
  const Product({
    required this.id,
    required this.farmOwnerId,
    required this.name,
    required this.price,
    required this.stock,
    required this.farmName,
    required this.imageUrl,
    required this.shopRating,
    required this.shopType,
    required this.category,
    required this.unit,
    required this.minimumOrder,
    required this.isBulkOrderEnabled,
    required this.orderQuantityStep,
    required this.orderQuantityOptions,
    this.farmOwnerName,
    this.description,
  });

  final int id;
  final int farmOwnerId;
  final String name;
  final double price;
  final int stock;
  final String farmName;
  final String imageUrl;
  final double shopRating;
  final String shopType;
  final String category;
  final String unit;
  final int minimumOrder;
  final bool isBulkOrderEnabled;
  final int orderQuantityStep;
  final List<int> orderQuantityOptions;
  final String? farmOwnerName;
  final String? description;

  int get effectiveOrderStep => isBulkOrderEnabled ? (orderQuantityStep < 1 ? 1 : orderQuantityStep) : 1;

  int get effectiveMinimumOrder => minimumOrder < 1 ? 1 : minimumOrder;

  bool get hasCustomOrderChoices => orderQuantityOptions.isNotEmpty;

  List<int> get normalizedOrderChoices {
    final sorted = [...orderQuantityOptions]
      ..sort((a, b) => a.compareTo(b));

    return sorted.where((value) => value > 0).toSet().toList()..sort((a, b) => a.compareTo(b));
  }

  List<int> suggestedBulkQuantitiesForStock(int remainingStock) {
    if (!isBulkOrderEnabled || remainingStock < 1) {
      return const [];
    }

    final step = effectiveOrderStep;
    final options = <int>[];
    for (var multiplier = 1; multiplier <= 6; multiplier++) {
      final quantity = step * multiplier;
      if (quantity > remainingStock) {
        break;
      }
      options.add(quantity);
    }
    return options;
  }

  factory Product.fromJson(Map<String, dynamic> json) {
    int parseInt(dynamic value) => int.tryParse(value.toString()) ?? 0;

    final rawImage = (json['image_url'] ?? '').toString().trim();
    final normalizedImage = _normalizeImageUrl(rawImage);
    final parsedMinimumOrder = parseInt(json['minimum_order']);
    final parsedOrderStep = parseInt(json['order_quantity_step']);
    final parsedOptionsRaw = (json['order_quantity_options'] as List<dynamic>? ?? const []);
    final parsedOptions = parsedOptionsRaw
        .map((entry) => parseInt(entry))
        .where((entry) => entry > 0)
        .toSet()
        .toList()
      ..sort();

    return Product(
      id: parseInt(json['id']),
      farmOwnerId: parseInt(json['farm_owner_id']),
      name: (json['name'] ?? 'Unnamed Product') as String,
      price: double.tryParse(json['price'].toString()) ?? 0,
      stock: parseInt(json['stock']),
      farmName: (json['farm_name'] ?? 'Unknown Farm') as String,
      imageUrl: normalizedImage,
      shopRating: double.tryParse(json['shop_rating'].toString()) ?? 0,
      shopType: (json['shop_type'] ?? 'Poultry Shop') as String,
      category: (json['category'] ?? '') as String,
      unit: (json['unit'] ?? 'unit').toString(),
      minimumOrder: parsedMinimumOrder < 1 ? 1 : parsedMinimumOrder,
      isBulkOrderEnabled: json['is_bulk_order_enabled'] == true || json['is_bulk_order_enabled'].toString() == '1',
      orderQuantityStep: parsedOrderStep <= 0 ? 1 : parsedOrderStep,
      orderQuantityOptions: parsedOptions,
      farmOwnerName: json['farm_owner_name'] as String?,
      description: json['description'] as String?,
    );
  }

  static String _normalizeImageUrl(String raw) {
    if (raw.isEmpty) {
      return '';
    }

    final value = raw.trim();
    if (value.startsWith('http://') || value.startsWith('https://')) {
      return value;
    }

    final base = AppConfig.apiBaseUrl.replaceAll(RegExp(r'/+$'), '');
    final path = value.startsWith('/') ? value : '/$value';
    return '$base$path';
  }
}
