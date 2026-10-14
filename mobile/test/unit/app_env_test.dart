import 'package:flutter_test/flutter_test.dart';
import 'package:webintoapp_engine/core/env/app_env.dart';

void main() {
  group('AppEnv Tests', () {
    test('provides build-time injected configuration without hardcoded secrets', () {
      expect(AppEnv.appKey, isNotEmpty);
      expect(AppEnv.apiBaseUrl, isNotEmpty);
      expect(AppEnv.appVersion, isNotEmpty);

      // Verify no secrets or private keys leaked in AppEnv
      expect(AppEnv.appKey.startsWith('cs_'), isFalse);
      expect(AppEnv.appKey.startsWith('sk_'), isFalse);
      expect(AppEnv.isConfigured, isTrue);
    });
  });
}
