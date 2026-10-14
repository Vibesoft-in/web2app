import 'package:flutter_test/flutter_test.dart';
import 'package:webintoapp_engine/core/utils/version_utils.dart';

void main() {
  group('VersionUtils Tests', () {
    test('correctly detects when update is required', () {
      expect(VersionUtils.isUpdateRequired('1.0.0', '1.0.1'), isTrue);
      expect(VersionUtils.isUpdateRequired('1.0.0', '1.1.0'), isTrue);
      expect(VersionUtils.isUpdateRequired('1.9.9', '2.0.0'), isTrue);
      expect(VersionUtils.isUpdateRequired('1.0.0+1', '1.0.2'), isTrue);
    });

    test('correctly detects when app is up to date or ahead', () {
      expect(VersionUtils.isUpdateRequired('1.0.1', '1.0.0'), isFalse);
      expect(VersionUtils.isUpdateRequired('2.0.0', '1.9.9'), isFalse);
      expect(VersionUtils.isUpdateRequired('1.2.0', '1.2.0'), isFalse);
    });
  });
}
