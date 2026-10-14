class AppConfig {
  final int schemaVersion;
  final int configVersion;
  final AppMetadata app;
  final ThemeConfig theme;
  final FeatureConfig features;
  final List<HomeSection> home;
  final LinksConfig links;
  final List<String> webviewAllowedHosts;

  const AppConfig({
    required this.schemaVersion,
    required this.configVersion,
    required this.app,
    required this.theme,
    required this.features,
    required this.home,
    required this.links,
    required this.webviewAllowedHosts,
  });

  factory AppConfig.defaults() {
    return const AppConfig(
      schemaVersion: 1,
      configVersion: 1,
      app: AppMetadata(
        name: 'Store App',
        currency: 'INR',
        minVersion: '1.0.0',
        maintenance: false,
      ),
      theme: ThemeConfig(
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
      ),
      features: FeatureConfig(
        wishlist: true,
        otpLogin: true,
        googleLogin: true,
        notifications: true,
        reviews: false,
      ),
      home: [
        BannerHomeSection(items: []),
        CategoriesHomeSection(),
        ProductRailHomeSection(title: 'Featured Products', source: 'featured'),
      ],
      links: LinksConfig(),
      webviewAllowedHosts: [],
    );
  }

  factory AppConfig.fromJson(Map<String, dynamic> json) {
    final rawHome = json['home'];
    final List<HomeSection> parsedSections = [];

    if (rawHome is List) {
      for (final item in rawHome) {
        if (item is Map<String, dynamic>) {
          final section = HomeSection.fromJson(item);
          // Only include recognized and safe section types
          if (section is! UnknownHomeSection) {
            parsedSections.add(section);
          }
        }
      }
    }

    return AppConfig(
      schemaVersion: json['schema_version'] as int? ?? 1,
      configVersion: json['config_version'] as int? ?? 1,
      app: json['app'] is Map<String, dynamic>
          ? AppMetadata.fromJson(json['app'] as Map<String, dynamic>)
          : AppConfig.defaults().app,
      theme: json['theme'] is Map<String, dynamic>
          ? ThemeConfig.fromJson(json['theme'] as Map<String, dynamic>)
          : AppConfig.defaults().theme,
      features: json['features'] is Map<String, dynamic>
          ? FeatureConfig.fromJson(json['features'] as Map<String, dynamic>)
          : AppConfig.defaults().features,
      home: parsedSections.isNotEmpty ? parsedSections : AppConfig.defaults().home,
      links: json['links'] is Map<String, dynamic>
          ? LinksConfig.fromJson(json['links'] as Map<String, dynamic>)
          : const LinksConfig(),
      webviewAllowedHosts: (json['webview_allowed_hosts'] as List<dynamic>?)
              ?.map((e) => e.toString())
              .toList() ??
          const [],
    );
  }

  Map<String, dynamic> toJson() => {
        'schema_version': schemaVersion,
        'config_version': configVersion,
        'app': app.toJson(),
        'theme': theme.toJson(),
        'features': features.toJson(),
        'home': home.map((h) => h.toJson()).toList(),
        'links': links.toJson(),
        'webview_allowed_hosts': webviewAllowedHosts,
      };
}

class AppMetadata {
  final String name;
  final String currency;
  final String minVersion;
  final bool maintenance;

  const AppMetadata({
    required this.name,
    required this.currency,
    required this.minVersion,
    required this.maintenance,
  });

  factory AppMetadata.fromJson(Map<String, dynamic> json) {
    return AppMetadata(
      name: json['name'] as String? ?? 'Store App',
      currency: json['currency'] as String? ?? 'INR',
      minVersion: json['min_version'] as String? ?? '1.0.0',
      maintenance: json['maintenance'] as bool? ?? false,
    );
  }

  Map<String, dynamic> toJson() => {
        'name': name,
        'currency': currency,
        'min_version': minVersion,
        'maintenance': maintenance,
      };
}

class ThemeConfig {
  final String layout;
  final ThemeColors colors;
  final int radius;
  final String font;
  final String nav;

  const ThemeConfig({
    required this.layout,
    required this.colors,
    required this.radius,
    required this.font,
    required this.nav,
  });

  factory ThemeConfig.fromJson(Map<String, dynamic> json) {
    return ThemeConfig(
      layout: json['layout'] as String? ?? 'theme_a',
      colors: json['colors'] is Map<String, dynamic>
          ? ThemeColors.fromJson(json['colors'] as Map<String, dynamic>)
          : const ThemeColors(),
      radius: (json['radius'] as num?)?.toInt() ?? 12,
      font: json['font'] as String? ?? 'Inter',
      nav: json['nav'] as String? ?? 'bottom',
    );
  }

  Map<String, dynamic> toJson() => {
        'layout': layout,
        'colors': colors.toJson(),
        'radius': radius,
        'font': font,
        'nav': nav,
      };
}

class ThemeColors {
  final String primary;
  final String secondary;
  final String background;
  final String surface;
  final String text;

  const ThemeColors({
    this.primary = '#6366F1',
    this.secondary = '#1E293B',
    this.background = '#FFFFFF',
    this.surface = '#F8FAFC',
    this.text = '#0F172A',
  });

  factory ThemeColors.fromJson(Map<String, dynamic> json) {
    return ThemeColors(
      primary: json['primary'] as String? ?? '#6366F1',
      secondary: json['secondary'] as String? ?? '#1E293B',
      background: json['background'] as String? ?? '#FFFFFF',
      surface: json['surface'] as String? ?? '#F8FAFC',
      text: json['text'] as String? ?? '#0F172A',
    );
  }

  Map<String, dynamic> toJson() => {
        'primary': primary,
        'secondary': secondary,
        'background': background,
        'surface': surface,
        'text': text,
      };
}

class FeatureConfig {
  final bool wishlist;
  final bool otpLogin;
  final bool googleLogin;
  final bool notifications;
  final bool reviews;

  const FeatureConfig({
    this.wishlist = true,
    this.otpLogin = true,
    this.googleLogin = true,
    this.notifications = true,
    this.reviews = false,
  });

  factory FeatureConfig.fromJson(Map<String, dynamic> json) {
    return FeatureConfig(
      wishlist: json['wishlist'] as bool? ?? true,
      otpLogin: json['otp_login'] as bool? ?? true,
      googleLogin: json['google_login'] as bool? ?? true,
      notifications: json['notifications'] as bool? ?? true,
      reviews: json['reviews'] as bool? ?? false,
    );
  }

  Map<String, dynamic> toJson() => {
        'wishlist': wishlist,
        'otp_login': otpLogin,
        'google_login': googleLogin,
        'notifications': notifications,
        'reviews': reviews,
      };
}

abstract class HomeSection {
  final String type;
  const HomeSection({required this.type});

  factory HomeSection.fromJson(Map<String, dynamic> json) {
    final type = json['type'] as String? ?? '';
    switch (type) {
      case 'banner':
        return BannerHomeSection.fromJson(json);
      case 'categories':
        return CategoriesHomeSection.fromJson(json);
      case 'product_rail':
        return ProductRailHomeSection.fromJson(json);
      default:
        // Safely ignore unknown future section types without crashing
        return UnknownHomeSection(type: type);
    }
  }

  Map<String, dynamic> toJson();
}

class BannerItem {
  final String image;
  final String? link;

  const BannerItem({required this.image, this.link});

  factory BannerItem.fromJson(Map<String, dynamic> json) {
    return BannerItem(
      image: json['image'] as String? ?? '',
      link: json['link'] as String?,
    );
  }

  Map<String, dynamic> toJson() => {
        'image': image,
        if (link != null) 'link': link,
      };
}

class BannerHomeSection extends HomeSection {
  final List<BannerItem> items;

  const BannerHomeSection({this.items = const []}) : super(type: 'banner');

  factory BannerHomeSection.fromJson(Map<String, dynamic> json) {
    final rawItems = json['items'];
    final List<BannerItem> itemsList = [];
    if (rawItems is List) {
      for (final i in rawItems) {
        if (i is Map<String, dynamic>) {
          itemsList.add(BannerItem.fromJson(i));
        }
      }
    }
    return BannerHomeSection(items: itemsList);
  }

  @override
  Map<String, dynamic> toJson() => {
        'type': type,
        'items': items.map((e) => e.toJson()).toList(),
      };
}

class CategoriesHomeSection extends HomeSection {
  const CategoriesHomeSection() : super(type: 'categories');

  factory CategoriesHomeSection.fromJson(Map<String, dynamic> _) {
    return const CategoriesHomeSection();
  }

  @override
  Map<String, dynamic> toJson() => {'type': type};
}

class ProductRailHomeSection extends HomeSection {
  final String title;
  final String source;

  const ProductRailHomeSection({
    this.title = 'Featured Products',
    this.source = 'recent',
  }) : super(type: 'product_rail');

  factory ProductRailHomeSection.fromJson(Map<String, dynamic> json) {
    return ProductRailHomeSection(
      title: json['title'] as String? ?? 'Featured Products',
      source: json['source'] as String? ?? 'recent',
    );
  }

  @override
  Map<String, dynamic> toJson() => {
        'type': type,
        'title': title,
        'source': source,
      };
}

class UnknownHomeSection extends HomeSection {
  const UnknownHomeSection({required super.type});

  @override
  Map<String, dynamic> toJson() => {'type': type};
}

class LinksConfig {
  final String? privacy;
  final String? terms;
  final String? supportPhone;

  const LinksConfig({this.privacy, this.terms, this.supportPhone});

  factory LinksConfig.fromJson(Map<String, dynamic> json) {
    return LinksConfig(
      privacy: json['privacy'] as String?,
      terms: json['terms'] as String?,
      supportPhone: json['support_phone'] as String?,
    );
  }

  Map<String, dynamic> toJson() => {
        if (privacy != null) 'privacy': privacy,
        if (terms != null) 'terms': terms,
        if (supportPhone != null) 'support_phone': supportPhone,
      };
}
