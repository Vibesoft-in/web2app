import 'package:flutter_test/flutter_test.dart';
import 'package:webintoapp_engine/core/security/url_allowlist.dart';

void main() {
  group('UrlAllowlist Tests', () {
    const allowlist = UrlAllowlist(
      allowedHosts: ['mystore.com', 'api.razorpay.com'],
    );

    test('allows secure HTTPS matching hosts or subdomains', () {
      expect(allowlist.isAllowed('https://mystore.com/checkout'), isTrue);
      expect(allowlist.isAllowed('https://shop.mystore.com/pay'), isTrue);
      expect(allowlist.isAllowed('https://api.razorpay.com/v1/checkout'), isTrue);
    });

    test('blocks insecure plain HTTP and unlisted domains', () {
      // Insecure HTTP blocked strictly
      expect(allowlist.isAllowed('http://mystore.com/checkout'), isFalse);

      // Unwhitelisted domain blocked
      expect(allowlist.isAllowed('https://evilphishing.com/mystore.com'), isFalse);
      expect(allowlist.isAllowed('https://google.com'), isFalse);
      expect(allowlist.isAllowed(null), isFalse);
      expect(allowlist.isAllowed(''), isFalse);
    });
  });
}
