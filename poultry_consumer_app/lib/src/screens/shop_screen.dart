import 'package:flutter/material.dart';

import '../models/cart_item.dart';
import '../models/product.dart';
import '../services/api_service.dart';

typedef AddToCartWithQuantity = void Function(Product product, {int quantity});

class ShopScreen extends StatefulWidget {
  const ShopScreen({
    super.key,
    required this.cartItems,
    required this.onAddToCart,
    required this.cartQuantityForProduct,
  });

  final List<CartItem> cartItems;
  final AddToCartWithQuantity onAddToCart;
  final int Function(Product product) cartQuantityForProduct;

  @override
  State<ShopScreen> createState() => _ShopScreenState();
}

class _ShopScreenState extends State<ShopScreen> {
  int? _selectedFarmOwnerId;
  final _searchCtrl = TextEditingController();
  String _query = '';
  late Future<List<Product>> _productsFuture;

  @override
  void initState() {
    super.initState();
    _productsFuture = const ApiService().fetchProducts();
  }

  @override
  void dispose() {
    _searchCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: FutureBuilder<List<Product>>(
        future: _productsFuture,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          }

          if (!snapshot.hasData || snapshot.data!.isEmpty) {
            return const Center(
              child: Padding(
                padding: EdgeInsets.symmetric(horizontal: 24),
                child: Text(
                  'No products available yet. The shop will appear once farm owners add stock.',
                  textAlign: TextAlign.center,
                ),
              ),
            );
          }

          final allProducts = snapshot.data!;
          final products = _selectedFarmOwnerId == null
              ? allProducts
              : allProducts
                    .where((p) => p.farmOwnerId == _selectedFarmOwnerId)
                    .toList();

          final farms = <int, String>{
            for (final product in allProducts) product.farmOwnerId: product.farmName,
          };

          return CustomScrollView(
            slivers: [
              SliverAppBar.large(
                pinned: true,
                title: const Text('Marketplace'),
                flexibleSpace: const FlexibleSpaceBar(
                  background: DecoratedBox(
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        colors: [Color(0xFF1F2937), Color(0xFF0B1220)],
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                      ),
                    ),
                  ),
                ),
              ),
              SliverToBoxAdapter(
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(16, 8, 16, 4),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Shop by farm',
                        style: Theme.of(context)
                            .textTheme
                            .titleSmall
                            ?.copyWith(fontWeight: FontWeight.w800),
                      ),
                      const SizedBox(height: 8),
                      TextField(
                        controller: _searchCtrl,
                        decoration: InputDecoration(
                          hintText: 'Search products, categories, keywords',
                          suffixIcon: IconButton(
                            onPressed: _applySearch,
                            icon: const Icon(Icons.search),
                          ),
                        ),
                        onSubmitted: (_) => _applySearch(),
                      ),
                      const SizedBox(height: 10),
                      Wrap(
                        spacing: 8,
                        runSpacing: 8,
                        children: [
                          ChoiceChip(
                            label: const Text('All Farms'),
                            selected: _selectedFarmOwnerId == null,
                            selectedColor: const Color(0xFFEA580C),
                            labelStyle: TextStyle(
                              color: _selectedFarmOwnerId == null ? Colors.white : const Color(0xFFE2E8F0),
                              fontWeight: FontWeight.w700,
                            ),
                            backgroundColor: const Color(0xFF111827),
                            side: const BorderSide(color: Color(0xFF374151)),
                            onSelected: (_) => setState(() => _selectedFarmOwnerId = null),
                          ),
                          for (final entry in farms.entries)
                            ChoiceChip(
                              label: Text(entry.value),
                              selected: _selectedFarmOwnerId == entry.key,
                              selectedColor: const Color(0xFFEA580C),
                              labelStyle: TextStyle(
                                color: _selectedFarmOwnerId == entry.key ? Colors.white : const Color(0xFFE2E8F0),
                                fontWeight: FontWeight.w700,
                              ),
                              backgroundColor: const Color(0xFF111827),
                              side: const BorderSide(color: Color(0xFF374151)),
                              onSelected: (_) =>
                                  setState(() => _selectedFarmOwnerId = entry.key),
                            ),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
              SliverPadding(
                padding: const EdgeInsets.fromLTRB(16, 8, 16, 24),
                sliver: SliverLayoutBuilder(
                  builder: (context, constraints) {
                    final gridCount = _gridCountForWidth(constraints.crossAxisExtent);
                    final childAspectRatio = _childAspectRatioForWidth(
                      constraints.crossAxisExtent,
                      gridCount,
                    );

                    return SliverGrid(
                      delegate: SliverChildBuilderDelegate(
                        (context, index) {
                          final product = products[index];
                          final cartQuantity = widget.cartQuantityForProduct(product);

                          return _buildProductCard(product, cartQuantity);
                        },
                        childCount: products.length,
                      ),
                      gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
                        crossAxisCount: gridCount,
                        mainAxisSpacing: 12,
                        crossAxisSpacing: 12,
                        childAspectRatio: childAspectRatio,
                      ),
                    );
                  },
                ),
              ),
            ],
          );
        },
      ),
    );
  }

  void _applySearch() {
    final nextQuery = _searchCtrl.text.trim();
    if (nextQuery == _query) {
      return;
    }

    setState(() {
      _query = nextQuery;
      _productsFuture = const ApiService().fetchProducts(query: _query);
    });
  }

  Widget _buildImageFallback() {
    return Container(
      color: const Color(0xFF1F2937),
      alignment: Alignment.center,
      child: const Icon(Icons.inventory_2_outlined, color: Color(0xFF94A3B8), size: 34),
    );
  }

  Widget _buildProductCard(Product product, int cartQuantity) {
    final remainingStock = product.stock - cartQuantity;

    return Card(
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: remainingStock > 0 ? () => _handleAddToCart(product, cartQuantity) : null,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Expanded(
              flex: 9,
              child: SizedBox(
                width: double.infinity,
                child: product.imageUrl.isNotEmpty
                    ? Image.network(
                        product.imageUrl,
                        fit: BoxFit.cover,
                        webHtmlElementStrategy: WebHtmlElementStrategy.prefer,
                        errorBuilder: (context, error, stackTrace) => _buildImageFallback(),
                      )
                    : _buildImageFallback(),
              ),
            ),
            Expanded(
              flex: 8,
              child: Padding(
                padding: const EdgeInsets.fromLTRB(10, 10, 10, 8),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      product.name,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: Theme.of(context).textTheme.titleSmall?.copyWith(
                            fontWeight: FontWeight.w800,
                            height: 1.15,
                          ),
                    ),
                    const SizedBox(height: 4),
                    GestureDetector(
                      onTap: () => setState(() => _selectedFarmOwnerId = product.farmOwnerId),
                      child: Text(
                        product.farmOwnerName ?? product.farmName,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: Theme.of(context).textTheme.bodySmall?.copyWith(
                              color: const Color(0xFFF59E0B),
                              fontWeight: FontWeight.w700,
                            ),
                      ),
                    ),
                    const SizedBox(height: 6),
                    Row(
                      children: [
                        Flexible(child: _buildStarRating(product.shopRating)),
                        const SizedBox(width: 6),
                        Expanded(
                          child: Text(
                            'Stock ${product.stock}',
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            textAlign: TextAlign.right,
                            style: Theme.of(context).textTheme.labelSmall?.copyWith(
                                  color: const Color(0xFF94A3B8),
                                  fontWeight: FontWeight.w700,
                                ),
                          ),
                        ),
                      ],
                    ),
                    const Spacer(),
                    Text(
                      'PHP ${product.price.toStringAsFixed(2)}',
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: Theme.of(context).textTheme.titleSmall?.copyWith(
                            color: const Color(0xFFFB923C),
                            fontWeight: FontWeight.w900,
                          ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      product.hasCustomOrderChoices
                          ? 'Choices: ${product.normalizedOrderChoices.join(', ')} ${product.unit}'
                          : (product.isBulkOrderEnabled
                              ? 'Bulk: ${product.effectiveOrderStep} ${product.unit} per step'
                              : (product.effectiveMinimumOrder > 1
                                  ? 'Min order: ${product.effectiveMinimumOrder} ${product.unit}'
                                  : 'Unit: ${product.unit}')),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: Theme.of(context).textTheme.labelSmall?.copyWith(
                            color: const Color(0xFF94A3B8),
                            fontWeight: FontWeight.w700,
                          ),
                    ),
                    const SizedBox(height: 6),
                    SizedBox(
                      width: double.infinity,
                      child: FilledButton.tonalIcon(
                        style: FilledButton.styleFrom(
                          backgroundColor: cartQuantity > 0
                              ? const Color(0xFF334155)
                              : const Color(0xFFEA580C),
                          foregroundColor: Colors.white,
                          padding: const EdgeInsets.symmetric(vertical: 10),
                          textStyle: Theme.of(context).textTheme.labelLarge?.copyWith(
                                fontWeight: FontWeight.w800,
                              ),
                        ),
                        onPressed: remainingStock <= 0
                            ? null
                            : () => _handleAddToCart(product, cartQuantity),
                        icon: Icon(
                          cartQuantity > 0 ? Icons.add_circle : Icons.add_shopping_cart,
                          size: 18,
                        ),
                        label: Text(
                          product.hasCustomOrderChoices
                              ? (cartQuantity > 0 ? 'Qty $cartQuantity (Choices)' : 'Choose Qty')
                              : (product.isBulkOrderEnabled
                                  ? (cartQuantity > 0 ? 'Qty $cartQuantity (Bulk)' : 'Choose Bulk Qty')
                                  : (cartQuantity > 0 ? 'Qty $cartQuantity' : 'Add')),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _handleAddToCart(Product product, int cartQuantity) async {
    if (!product.hasCustomOrderChoices && !product.isBulkOrderEnabled) {
      widget.onAddToCart(product, quantity: product.effectiveMinimumOrder);
      return;
    }

    final availableChoices = product.hasCustomOrderChoices
        ? product.normalizedOrderChoices.where((choice) => choice <= product.stock).toList()
        : product.suggestedBulkQuantitiesForStock(product.stock - cartQuantity);

    if (availableChoices.isEmpty) {
      if (!mounted) {
        return;
      }
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            product.hasCustomOrderChoices
                ? 'No quantity choices available for current stock.'
                : 'Not enough stock for bulk step of ${product.effectiveOrderStep} ${product.unit}.',
          ),
        ),
      );
      return;
    }

    final selectedChoice = await showModalBottomSheet<int>(
      context: context,
      backgroundColor: const Color(0xFF0F172A),
      builder: (context) {
        return SafeArea(
          child: Padding(
            padding: const EdgeInsets.fromLTRB(16, 16, 16, 20),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Choose quantity',
                  style: Theme.of(context).textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.w800,
                      ),
                ),
                const SizedBox(height: 4),
                Text(
                  product.name,
                  style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                        color: const Color(0xFFCBD5E1),
                      ),
                ),
                const SizedBox(height: 14),
                Text(
                  product.hasCustomOrderChoices
                      ? 'Choose one of the farm owner quantity options.'
                      : 'Bulk mode: multiples of ${product.effectiveOrderStep} ${product.unit}',
                  style: Theme.of(context).textTheme.bodySmall?.copyWith(
                        color: const Color(0xFF94A3B8),
                      ),
                ),
                const SizedBox(height: 10),
                for (final choice in availableChoices)
                  Padding(
                    padding: const EdgeInsets.only(bottom: 8),
                    child: SizedBox(
                      width: double.infinity,
                      child: FilledButton.tonal(
                        onPressed: () => Navigator.of(context).pop(choice),
                        child: Text(
                          '$choice ${product.unit} • PHP ${(product.price * choice).toStringAsFixed(2)}',
                        ),
                      ),
                    ),
                  ),
              ],
            ),
          ),
        );
      },
    );

    if (selectedChoice == null) {
      return;
    }

    widget.onAddToCart(product, quantity: selectedChoice);
  }

  int _gridCountForWidth(double width) {
    if (width >= 1200) {
      return 5;
    }

    if (width >= 900) {
      return 4;
    }

    if (width >= 680) {
      return 3;
    }

    return 2;
  }

  double _childAspectRatioForWidth(double width, int gridCount) {
    final tileWidth = (width - ((gridCount - 1) * 12)) / gridCount;

    if (tileWidth >= 240) {
      return 0.76;
    }

    if (tileWidth >= 180) {
      return 0.72;
    }

    return 0.68;
  }

  Widget _buildStarRating(double rating) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: List.generate(5, (index) {
        final filled = rating >= (index + 1);
        return Icon(
          filled ? Icons.star_rounded : Icons.star_border_rounded,
          size: 16,
          color: const Color(0xFFF59E0B),
        );
      }),
    );
  }
}
