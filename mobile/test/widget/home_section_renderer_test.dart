import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:webintoapp_engine/core/config/app_config_model.dart';
import 'package:webintoapp_engine/features/catalog/product_model.dart';
import 'package:webintoapp_engine/features/home/home_section_renderer.dart';

void main() {
  group('Widget Tests: HomeSectionRenderer', () {
    const testProducts = [
      Product(
        id: '1',
        title: 'Classic Denim Jacket',
        price: 2999.0,
      ),
      Product(
        id: '2',
        title: 'Leather Boots',
        price: 4999.0,
      ),
    ];

    testWidgets('renders categories and product rails, safely skipping unknown sections',
        (tester) async {
      Product? tappedProduct;

      final sections = [
        const CategoriesHomeSection(),
        const ProductRailHomeSection(
          title: 'Trending Today',
          source: 'featured',
        ),
        const UnknownHomeSection(type: 'future_ar_preview'),
      ];

      await tester.pumpWidget(
        MaterialApp(
          home: Scaffold(
            body: HomeSectionRenderer(
              sections: sections,
              products: testProducts,
              onProductTap: (p) => tappedProduct = p,
            ),
          ),
        ),
      );

      // Verify Categories section rendered
      expect(find.text('Categories'), findsOneWidget);
      expect(find.text('Apparel'), findsOneWidget);

      // Verify Product Rail rendered with title
      expect(find.text('Trending Today'), findsOneWidget);
      expect(find.text('Classic Denim Jacket'), findsOneWidget);

      // Verify tap on product in rail invokes callback
      await tester.tap(find.text('Classic Denim Jacket'));
      await tester.pump();

      expect(tappedProduct, isNotNull);
      expect(tappedProduct?.id, equals('1'));
    });
  });
}
