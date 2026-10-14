class AppEnv {
  AppEnv._();

  /// Injected via --dart-define=APP_KEY=...
  /// White-label tenant public application identifier.
  static const String appKey = String.fromEnvironment(
    'APP_KEY',
    defaultValue: 'app_pub_demo12345',
  );

  /// Injected via --dart-define=API_BASE_URL=...
  /// Base endpoint for WebIntoApp multi-tenant engine API.
  static const String apiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'https://api.webintoapp.com',
  );

  /// Current semantic version of the installed client app.
  static const String appVersion = String.fromEnvironment(
    'APP_VERSION',
    defaultValue: '1.0.0',
  );

  /// Whether valid application keys have been provided at build or run time.
  static bool get isConfigured => appKey.isNotEmpty && apiBaseUrl.isNotEmpty;
}
