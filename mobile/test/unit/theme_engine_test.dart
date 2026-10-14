import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:webintoapp_engine/core/config/app_config_model.dart';
import 'package:webintoapp_engine/core/theme/theme_engine.dart';

void main() {
  group('ThemeEngine Tests', () {
    test('parses valid hex colors correctly', () {
      const fallback = Colors.blue;

      // 6-character hex
      final red = ThemeEngine.parseHexColor('#FF0000', fallback);
      expect(red.value, equals(0xFFFF0000));

      // Without leading #
      final green = ThemeEngine.parseHexColor('00FF00', fallback);
      expect(green.value, equals(0xFF00FF00));

      // 8-character hex (with explicit alpha)
      final semiWhite = ThemeEngine.parseHexColor('#80FFFFFF', fallback);
      expect(semiWhite.value, equals(0x80FFFFFF));
    });

    test('reverts gracefully to fallback on malformed or non-hex inputs', () {
      const fallback = Colors.amber;

      expect(ThemeEngine.parseHexColor('invalid', fallback), equals(fallback));
      expect(ThemeEngine.parseHexColor('#123', fallback), equals(fallback));
      expect(ThemeEngine.parseHexColor('#GG0000', fallback), equals(fallback));
      expect(ThemeEngine.parseHexColor(null, fallback), equals(fallback));
      expect(ThemeEngine.parseHexColor('', fallback), equals(fallback));
    });

    test('builds comprehensive ThemeData from tokens', () {
      const themeConfig = ThemeConfig(
        layout: 'theme_b',
        colors: ThemeColors(
          primary: '#EC4899',
          secondary: '#3B82F6',
          background: '#0F172A',
          surface: '#1E293B',
          text: '#F8FAFC',
        ),
        radius: 16,
        font: 'Poppins',
        nav: 'bottom',
      );

      final theme = ThemeEngine.buildTheme(themeConfig);

      expect(theme.colorScheme.primary.value, equals(0xFFEC4899));
      expect(theme.scaffoldBackgroundColor.value, equals(0xFF0F172A));
      expect(theme.fontFamily, equals('Poppins'));

      // Check card shape radius
      final cardShape = theme.cardTheme.shape as RoundedRectangleBorder;
      expect(cardShape.borderRadius, equals(BorderRadius.circular(16)));
    });
  });
}
