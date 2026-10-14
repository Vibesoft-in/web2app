import '../core/config/app_config_model.dart';

class LayoutPresets {
  LayoutPresets._();

  /// Theme A: Modern Minimalist
  static const ThemeConfig themeA = ThemeConfig(
    layout: 'theme_a',
    colors: ThemeColors(
      primary: '#6366F1',
      secondary: '#1E293B',
      background: '#FFFFFF',
      surface: '#F8FAFC',
      text: '#0F172A',
    ),
    radius: 12,
    font: 'Inter',
    nav: 'bottom',
  );

  /// Theme B: Vibrant Lifestyle
  static const ThemeConfig themeB = ThemeConfig(
    layout: 'theme_b',
    colors: ThemeColors(
      primary: '#EC4899',
      secondary: '#3B82F6',
      background: '#FFFFFF',
      surface: '#FFF1F2',
      text: '#1F2937',
    ),
    radius: 16,
    font: 'Poppins',
    nav: 'bottom',
  );

  /// Theme C: Dark Luxury
  static const ThemeConfig themeC = ThemeConfig(
    layout: 'theme_c',
    colors: ThemeColors(
      primary: '#F59E0B',
      secondary: '#64748B',
      background: '#0F172A',
      surface: '#1E293B',
      text: '#F8FAFC',
    ),
    radius: 8,
    font: 'Outfit',
    nav: 'bottom',
  );

  static ThemeConfig getPreset(String layoutKey) {
    switch (layoutKey.toLowerCase()) {
      case 'theme_b':
        return themeB;
      case 'theme_c':
        return themeC;
      case 'theme_a':
      default:
        return themeA;
    }
  }
}
