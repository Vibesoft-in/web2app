import 'package:flutter_test/flutter_test.dart';
import 'package:webintoapp_engine/core/config/app_config_model.dart';

void main() {
  group('AppConfig Model Tests', () {
    test('parses official spec JSON and safely ignores unknown home section types', () {
      final sampleJson = {
        'schema_version': 1,
        'config_version': 14,
        'app': {
          'name': 'Glow Beauty',
          'currency': 'INR',
          'min_version': '1.0.0',
          'maintenance': false,
        },
        'theme': {
          'layout': 'theme_a',
          'colors': {
            'primary': '#E91E63',
            'secondary': '#222222',
            'background': '#FFFFFF',
            'surface': '#FAFAFA',
            'text': '#111111',
          },
          'radius': 12,
          'font': 'Poppins',
          'nav': 'bottom',
        },
        'features': {
          'wishlist': true,
          'otp_login': true,
          'google_login': true,
          'notifications': true,
          'reviews': false,
        },
        'home': [
          {
            'type': 'banner',
            'items': [
              {'image': 'https://example.com/banner.jpg', 'link': 'category:12'}
            ]
          },
          {'type': 'categories'},
          {
            'type': 'product_rail',
            'title': 'New Arrivals',
            'source': 'recent',
          },
          // UNKNOWN future section types must NOT crash the app
          {'type': 'metaverse_vr_room', 'asset_id': 9999},
          {'type': 'live_shopping_stream', 'stream_url': 'rtmp://...'},
        ],
        'links': {
          'privacy': 'https://store.com/privacy',
          'terms': 'https://store.com/terms',
          'support_phone': '+919876543210',
        },
        'webview_allowed_hosts': ['store.example.com', 'payment.razorpay.com'],
      };

      final config = AppConfig.fromJson(sampleJson);

      expect(config.app.name, equals('Glow Beauty'));
      expect(config.app.currency, equals('INR'));
      expect(config.app.maintenance, isFalse);
      expect(config.theme.layout, equals('theme_a'));
      expect(config.theme.colors.primary, equals('#E91E63'));
      expect(config.features.wishlist, isTrue);
      expect(config.features.reviews, isFalse);

      // Only recognized sections are kept (3 out of 5)
      expect(config.home.length, equals(3));
      expect(config.home[0], isA<BannerHomeSection>());
      expect(config.home[1], isA<CategoriesHomeSection>());
      expect(config.home[2], isA<ProductRailHomeSection>());

      final rail = config.home[2] as ProductRailHomeSection;
      expect(rail.title, equals('New Arrivals'));

      expect(config.webviewAllowedHosts, contains('store.example.com'));
      expect(config.webviewAllowedHosts, contains('payment.razorpay.com'));
    });

    test('defaults are safe and robust when JSON is empty or missing fields', () {
      final config = AppConfig.fromJson({});
      expect(config.app.name, equals('Store App'));
      expect(config.theme.colors.primary, equals('#6366F1'));
      expect(config.home, isNotEmpty);
    });
  });
}
