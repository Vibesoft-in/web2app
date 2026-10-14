import 'package:flutter/material.dart';
import '../config/app_config_model.dart';

class ThemeEngine {
  ThemeEngine._();

  /// Default fallback palette colors if tokens are missing or invalid
  static const Color defaultPrimary = Color(0xFF6366F1);
  static const Color defaultSecondary = Color(0xFF1E293B);
  static const Color defaultBackground = Color(0xFFFFFFFF);
  static const Color defaultSurface = Color(0xFFF8FAFC);
  static const Color defaultText = Color(0xFF0F172A);

  /// Safe Hex Color parser with robust error handling and fallbacks.
  /// Handles #RRGGBB, #AARRGGBB, and RRGGBB formats.
  static Color parseHexColor(String? hexString, Color fallback) {
    if (hexString == null) return fallback;

    var cleaned = hexString.replaceAll('#', '').trim();
    if (cleaned.length == 6) {
      cleaned = 'FF$cleaned'; // Add full alpha
    }

    if (cleaned.length != 8) return fallback;

    // Validate that all characters are hexadecimal
    final isHex = RegExp(r'^[0-9A-Fa-f]{8}$').hasMatch(cleaned);
    if (!isHex) return fallback;

    try {
      final value = int.parse(cleaned, radix: 16);
      return Color(value);
    } catch (_) {
      return fallback;
    }
  }

  /// Converts white-label theme configuration into a high-grade Flutter ThemeData.
  static ThemeData buildTheme(ThemeConfig config) {
    final colors = config.colors;
    final primary = parseHexColor(colors.primary, defaultPrimary);
    final secondary = parseHexColor(colors.secondary, defaultSecondary);
    final background = parseHexColor(colors.background, defaultBackground);
    final surface = parseHexColor(colors.surface, defaultSurface);
    final text = parseHexColor(colors.text, defaultText);

    final radius = config.radius.clamp(0, 32).toDouble();
    final fontFamily = _resolveFontFamily(config.font);

    final colorScheme = ColorScheme(
      brightness: Brightness.light,
      primary: primary,
      onPrimary: Colors.white,
      secondary: secondary,
      onSecondary: Colors.white,
      error: const Color(0xFFEF4444),
      onError: Colors.white,
      surface: surface,
      onSurface: text,
    );

    return ThemeData(
      useMaterial3: true,
      colorScheme: colorScheme,
      scaffoldBackgroundColor: background,
      fontFamily: fontFamily,
      appBarTheme: AppBarTheme(
        backgroundColor: background,
        foregroundColor: text,
        elevation: 0,
        centerTitle: false,
        titleTextStyle: TextStyle(
          color: text,
          fontSize: 18,
          fontWeight: FontWeight.w700,
          fontFamily: fontFamily,
        ),
      ),
      cardTheme: CardTheme(
        color: surface,
        elevation: 0,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(radius),
          side: BorderSide(color: text.withOpacity(0.08)),
        ),
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: primary,
          foregroundColor: Colors.white,
          elevation: 0,
          minimumSize: const Size.fromHeight(48),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(radius),
          ),
          textStyle: TextStyle(
            fontWeight: FontWeight.w600,
            fontSize: 15,
            fontFamily: fontFamily,
          ),
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          foregroundColor: primary,
          side: BorderSide(color: primary),
          minimumSize: const Size.fromHeight(48),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(radius),
          ),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: surface,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(radius),
          borderSide: BorderSide(color: text.withOpacity(0.12)),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(radius),
          borderSide: BorderSide(color: text.withOpacity(0.12)),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(radius),
          borderSide: BorderSide(color: primary, width: 2),
        ),
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      ),
    );
  }

  static String? _resolveFontFamily(String? requestedFont) {
    if (requestedFont == null || requestedFont.isEmpty) return null;
    // Standard supported clean mobile fonts
    switch (requestedFont.toLowerCase()) {
      case 'poppins':
      case 'inter':
      case 'outfit':
      case 'roboto':
        return requestedFont;
      default:
        return null; // System font fallback
    }
  }
}
