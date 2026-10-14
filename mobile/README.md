# WebIntoApp Mobile (Flutter White-Label Engine)

White-label native mobile application engine for WebIntoApp multi-tenant SaaS, adhering strictly to [`docs/03_mobile_app_architecture.md`](../docs/03_mobile_app_architecture.md) and [`docs/04_security_roadmap_keypoints.md`](../docs/04_security_roadmap_keypoints.md).

---

## 1. Architecture Overview

- **State Management**: Flutter Riverpod (`flutter_riverpod`)
- **Routing**: Declarative deep-link router via `go_router`
- **Networking**: `Dio` with multi-tenant headers (`X-App-Key`, `X-App-Version`), session interceptors, and `ErrorMapper`
- **Tokens & Storage**: Hardware-backed Keystore/Keychain via `flutter_secure_storage`
- **Dynamic Theming**: `ThemeEngine` dynamically constructing Material 3 `ThemeData` from runtime configuration tokens
- **Forward-Compatibility**: Home sections ignore unknown/future types gracefully without crashing

---

## 2. Environment & Dart-Define (Zero Hardcoded Secrets)

The app accepts runtime and build-time configurations strictly via `--dart-define`. **No API secrets or store keys are ever bundled inside the binary**.

```bash
flutter run \
  --dart-define=APP_KEY=app_pub_01JTESTAPP1234567890 \
  --dart-define=API_BASE_URL=https://api.webintoapp.com \
  --dart-define=APP_VERSION=1.0.0
```

---

## 3. Directory Layout

```text
mobile/lib/
 ├── main.dart
 ├── core/
 │    ├── env/          # AppEnv reading APP_KEY & API_BASE_URL from --dart-define
 │    ├── network/      # DioClient, ErrorMapper, NetworkException
 │    ├── config/       # AppConfig model, ConfigRepository, ConfigCache, ConfigProvider
 │    ├── theme/        # ThemeEngine (hex parser, token-to-ThemeData mapper)
 │    ├── routing/      # GoRouter configuration, RouteGuards
 │    ├── storage/      # SecureStore wrapping flutter_secure_storage
 │    ├── security/     # UrlAllowlist for secure in-app Webview
 │    ├── utils/        # VersionUtils (semantic version comparison)
 │    └── widgets/      # LoadingView, ErrorView, EmptyView
 ├── features/
 │    ├── splash/       # SplashScreen, MaintenanceScreen, ForceUpdateScreen
 │    ├── home/         # HomeScreen, HomeSectionRenderer (banners, categories, rails)
 │    ├── catalog/      # CatalogScreen (pagination, 400ms debounce search), Product model
 │    ├── product/      # ProductDetailScreen (gallery, variants, quantity stepper)
 │    └── cart/         # CartScreen, CartNotifier, CartItem
 └── layouts/           # ThemeA, ThemeB, ThemeC presets
```

---

## 4. Running Tests

```bash
# Run all unit tests
flutter test test/unit/

# Run all widget tests
flutter test test/widget/

# Run complete test suite
flutter test
```
