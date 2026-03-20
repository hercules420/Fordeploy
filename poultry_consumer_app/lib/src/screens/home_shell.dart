import 'package:flutter/material.dart';

import '../models/cart_item.dart';
import '../models/consumer_session.dart';
import '../models/product.dart';
import 'account_screen.dart';
import 'cart_screen.dart';
import 'notifications_screen.dart';
import 'orders_screen.dart';
import 'ratings_screen.dart';
import 'shop_screen.dart';

class HomeShell extends StatefulWidget {
  const HomeShell({
    super.key,
    required this.session,
    required this.onLogoutRequested,
  });

  final ConsumerSession session;
  final VoidCallback onLogoutRequested;

  @override
  State<HomeShell> createState() => _HomeShellState();
}

class _HomeShellState extends State<HomeShell> {
  int _index = 0;
  final List<CartItem> _cartItems = [];

  void _addToCart(Product product, {int quantity = 1}) {
    final safeQuantity = quantity < 1 ? 1 : quantity;

    setState(() {
      final index = _cartItems.indexWhere((item) => item.product.id == product.id);
      if (index >= 0) {
        final current = _cartItems[index];

        if (product.hasCustomOrderChoices) {
          final nextChoice = safeQuantity > product.stock ? product.stock : safeQuantity;
          _cartItems[index] = current.copyWith(quantity: nextChoice);
          return;
        }

        final next = current.quantity + safeQuantity;
        if (current.quantity < product.stock) {
          _cartItems[index] = current.copyWith(
            quantity: next > product.stock ? product.stock : next,
          );
        }
        return;
      }

      final startingQty = safeQuantity > product.stock ? product.stock : safeQuantity;
      if (startingQty > 0) {
        _cartItems.add(CartItem(product: product, quantity: startingQty));
      }
    });
  }

  void _decrementCartItem(Product product, {int quantity = 1}) {
    final safeQuantity = quantity < 1 ? 1 : quantity;

    setState(() {
      final index = _cartItems.indexWhere((item) => item.product.id == product.id);
      if (index < 0) {
        return;
      }

      final current = _cartItems[index];

      if (product.hasCustomOrderChoices) {
        if (safeQuantity <= 0) {
          _cartItems.removeAt(index);
          return;
        }

        _cartItems[index] = current.copyWith(quantity: safeQuantity);
        return;
      }

      if (current.quantity <= safeQuantity) {
        _cartItems.removeAt(index);
        return;
      }

      _cartItems[index] = current.copyWith(quantity: current.quantity - safeQuantity);
    });
  }

  void _removeCartItem(Product product) {
    setState(() {
      _cartItems.removeWhere((item) => item.product.id == product.id);
    });
  }

  int _quantityForProduct(Product product) {
    final index = _cartItems.indexWhere((item) => item.product.id == product.id);
    if (index < 0) {
      return 0;
    }

    return _cartItems[index].quantity;
  }

  void _clearCart() {
    setState(_cartItems.clear);
  }

  int get _cartUnitCount => _cartItems.fold<int>(0, (sum, item) => sum + item.quantity);

  @override
  Widget build(BuildContext context) {
    final pages = [
      ShopScreen(
        cartItems: _cartItems,
        onAddToCart: _addToCart,
        cartQuantityForProduct: _quantityForProduct,
      ),
      CartScreen(
        items: _cartItems,
        session: widget.session,
        onAddItem: _addToCart,
        onDecrementItem: _decrementCartItem,
        onRemoveItem: _removeCartItem,
        onClearCart: _clearCart,
        onOrderPlaced: _clearCart,
      ),
      OrdersScreen(session: widget.session),
      NotificationsScreen(session: widget.session),
      RatingsScreen(session: widget.session),
      AccountScreen(
        session: widget.session,
        onLogoutRequested: widget.onLogoutRequested,
      ),
    ];

    return Scaffold(
      body: pages[_index],
      bottomNavigationBar: NavigationBar(
        selectedIndex: _index,
        onDestinationSelected: (value) => setState(() => _index = value),
        destinations: [
          const NavigationDestination(
            icon: Icon(Icons.storefront_outlined),
            selectedIcon: Icon(Icons.storefront),
            label: 'Shop',
          ),
          NavigationDestination(
            icon: Badge(
              isLabelVisible: _cartItems.isNotEmpty,
              label: Text(_cartUnitCount.toString()),
              child: const Icon(Icons.shopping_cart_outlined),
            ),
            selectedIcon: Badge(
              isLabelVisible: _cartItems.isNotEmpty,
              label: Text(_cartUnitCount.toString()),
              child: const Icon(Icons.shopping_cart),
            ),
            label: 'Cart',
          ),
          const NavigationDestination(
            icon: Icon(Icons.receipt_long_outlined),
            selectedIcon: Icon(Icons.receipt_long),
            label: 'Orders',
          ),
          const NavigationDestination(
            icon: Icon(Icons.notifications_outlined),
            selectedIcon: Icon(Icons.notifications),
            label: 'Inbox',
          ),
          const NavigationDestination(
            icon: Icon(Icons.star_outline),
            selectedIcon: Icon(Icons.star),
            label: 'Ratings',
          ),
          const NavigationDestination(
            icon: Icon(Icons.person_outline),
            selectedIcon: Icon(Icons.person),
            label: 'Account',
          ),
        ],
      ),
    );
  }
}
