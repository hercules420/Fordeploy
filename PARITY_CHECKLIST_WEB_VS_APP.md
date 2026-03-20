# Marketplace Parity Checklist (Web vs Mobile App)

Date: 2026-03-11
Scope: Consumer marketplace features only (`products`, `cart/checkout`, `orders`, `profile`, `notifications/complaints`, `ratings`)
Method: Route + controller + view/screen + service behavior comparison

## Feature Matrix
| Feature | Web (Laravel Blade) | Mobile App (Flutter) | Parity | Remaining Difference | Close Action |
|---|---|---|---|---|---|
| Consumer login access control | Session login with role + email verification (`Auth` flow) | API login endpoint returns user payload | Partial | Mobile requests rely on `consumer_id` per call, no token/session auth | Add token auth (Sanctum/JWT), require authenticated API guard, remove raw `consumer_id` trust |
| Product browse list | `products.index` with active/in-stock/approved farm filtering, farm chips, search bar, pagination | `ShopScreen` fetches `/api/mobile/products`, supports search and farm filtering in UI | Partial | App has no pagination; web has pagination and "View" detail action | Add API pagination and Flutter paging; add optional product detail screen parity |
| Add to cart | Web stores cart in session, supports quantity increments (`cart_add`) | App local in-memory cart list | Partial | App cart loses state on app restart and has no per-item quantity control | Add local persistence (storage) and quantity +/- controls |
| Checkout form fields | Delivery type (delivery/pickup), address, city, province, postal code | App only asks `deliveryAddress` | Partial | Missing delivery type + city/province/postal fields in app | Extend `CartScreen` form and `ApiService.placeOrder` payload |
| Place order validation | Revalidates stock + farm-owner consistency + quantity from cart | Revalidates stock/farm in backend | Partial | App always posts quantity=1 for every cart item | Track and post real quantities from app cart model |
| Shipping/tax behavior | Shipping depends on `delivery_type`; tax applied in backend | Backend currently always sets shipping=100 in mobile endpoint | Partial | Mobile endpoint does not honor delivery type logic parity with web | Update mobile order endpoint to accept and apply `delivery_type` and conditional shipping |
| Orders list | Paginated order list, status/payment, links to detail (`orders.show`) | `OrdersScreen` lists orders with basic fields | Partial | No order detail screen in app; no pagination | Add order detail endpoint payload (or reuse existing) and `OrderDetailScreen`; add paging |
| Order detail | Detailed item table, totals, metadata (`orders.show`) | Not implemented | Missing | Mobile cannot inspect order line items and totals like web detail | Add detail UI + API support in app flow |
| Profile view/edit | `marketplace.profile.edit` with name/phone/location | `AccountScreen` fetch + update same fields | Full | Minor UX differences only | Keep validations/messages aligned if fields evolve |
| Notifications inbox | Paginated inbox, marks unread as read when opened | `NotificationsScreen` fetches list and refreshes | Partial | App has no pagination or read-state badge/UI | Add pagination, unread/read UI state, and optional mark-all/read-one controls |
| Complaint submission | Select order from dropdown, subject + message | Requires manual order ID entry, subject + message | Partial | App UX is weaker and error-prone vs web order selector | Add order picker sourced from `fetchOrders()` and remove manual ID entry |
| Ratings list | Delivered orders, current rating + feedback, submit/update rating | Delivered orders list with star submit | Partial | App does not expose feedback text input/update UX like web | Add optional feedback field and update action per delivery |
| Rating authorization | Validates ownership and delivered status | Same server-side checks reused | Full | N/A | Keep as-is |
| Marketplace navigation | Shop, profile, orders, notifications, ratings via navbar | Tabs: Shop, Cart, Orders, Inbox, Ratings, Account | Full | Naming/label differences only | Optional label alignment |
| Consumer app promotion/deeplink page | Web includes "Open App Options" flow | N/A in app | Not Applicable | Web-only affordance | No action needed |

## Priority Gap List

### P0 (Security / Data Trust)
1. Token-based authentication for mobile API
- Current risk: `consumer_id` can be tampered if endpoint is hit directly.
- Backend impact: `routes/api.php`, `app/Http/Controllers/Api/MobileMarketplaceController.php`.
- App impact: `poultry_consumer_app/lib/src/services/api_service.dart` (attach bearer token instead of query/body `consumer_id`).

### P1 (Behavioral Parity)
1. Cart quantities and checkout fields parity
- Files: `poultry_consumer_app/lib/src/screens/cart_screen.dart`, `poultry_consumer_app/lib/src/models/product.dart` (or separate cart line model), `poultry_consumer_app/lib/src/services/api_service.dart`, `app/Http/Controllers/Api/MobileMarketplaceController.php`.
2. Orders detail parity
- Files: `poultry_consumer_app/lib/src/screens/orders_screen.dart` + new detail screen, mobile orders API response shape.
3. Complaint order picker parity
- Files: `poultry_consumer_app/lib/src/screens/notifications_screen.dart`.
4. Ratings feedback parity
- Files: `poultry_consumer_app/lib/src/screens/ratings_screen.dart`, `poultry_consumer_app/lib/src/services/api_service.dart`.

### P2 (UX / Scalability)
1. Pagination parity for products/orders/notifications/ratings
- Files: API list endpoints + Flutter `FutureBuilder` lists.
2. Read/unread notification treatment parity
- Files: notifications endpoint + inbox UI badges/filters.

## Definition of Parity Done
A feature is marked "Full" only when all are true:
1. Same business rules and validation outcomes as web.
2. Same required user inputs and equivalent UX path.
3. Same authorization guarantees.
4. Same core data visibility (list + detail where applicable).
5. Same error-state handling quality.

## Recommended Execution Order
1. Mobile auth hardening (token auth).
2. Cart quantity + checkout field parity.
3. Orders detail + complaint order picker.
4. Ratings feedback + list pagination/read-state polish.

## Quick Status Summary
- Full parity now: `Profile edit`, `Rating authorization`, `Core navigation`.
- Partial parity: `Login auth model`, `Products`, `Cart/Checkout`, `Orders`, `Notifications`, `Complaints UX`, `Ratings UX`.
- Missing parity: `Order detail screen`.
