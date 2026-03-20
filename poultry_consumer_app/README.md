# Poultry Consumer App

Flutter mobile app for the Poultry marketplace consumer experience.

## Payment Gateway

This app keeps **PayMongo** as the online payment flow:
- `gcash` via PayMongo checkout
- `paymaya` via PayMongo checkout
- `cod` is still supported

No payment gateway change is required.

## Environment Configuration

The app uses `--dart-define` values:

- `APP_ENV=dev|staging|production`
- `API_BASE_URL=https://your-backend-domain` (recommended for staging/prod)

Examples:

```bash
# Local debug (Android emulator -> backend on host machine)
flutter run --dart-define=APP_ENV=dev

# Staging backend
flutter run --dart-define=APP_ENV=staging --dart-define=API_BASE_URL=https://staging-api.yourdomain.com

# Production backend
flutter run --release --dart-define=APP_ENV=production --dart-define=API_BASE_URL=https://api.yourdomain.com
```

## Build For Deployment

```bash
# Android App Bundle (Play Store)
flutter build appbundle --release \
	--dart-define=APP_ENV=production \
	--dart-define=API_BASE_URL=https://api.yourdomain.com

# Android APK (direct install)
flutter build apk --release \
	--dart-define=APP_ENV=production \
	--dart-define=API_BASE_URL=https://api.yourdomain.com
```

## Production Hardening Included

- Request timeout control
- Automatic retry for transient failures (production)
- Better API error parsing and user messages
- Global session-expiry handling on `401` (forces re-login safely)

## Pre-GoLive Checklist

- Backend API URL is HTTPS and reachable from mobile networks
- PayMongo callback/webhook URLs point to production backend
- Mobile app can place order and open PayMongo checkout
- Retry payment flow works on failed/expired payment link
- Token expiry triggers auto logout and re-login prompt
