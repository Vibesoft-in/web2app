import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:webintoapp_engine/core/widgets/empty_view.dart';
import 'package:webintoapp_engine/core/widgets/error_view.dart';

void main() {
  group('Widget Tests: Empty and Error Views', () {
    testWidgets('EmptyView displays title, message, and handles action button tap',
        (tester) async {
      bool actionTapped = false;

      await tester.pumpWidget(
        MaterialApp(
          home: Scaffold(
            body: EmptyView(
              title: 'No Items Found',
              message: 'Try exploring other categories.',
              actionLabel: 'Explore Catalog',
              onAction: () => actionTapped = true,
            ),
          ),
        ),
      );

      expect(find.text('No Items Found'), findsOneWidget);
      expect(find.text('Try exploring other categories.'), findsOneWidget);
      expect(find.text('Explore Catalog'), findsOneWidget);

      await tester.tap(find.text('Explore Catalog'));
      await tester.pump();

      expect(actionTapped, isTrue);
    });

    testWidgets('ErrorView displays error message and invokes retry callback',
        (tester) async {
      bool retryClicked = false;

      await tester.pumpWidget(
        MaterialApp(
          home: Scaffold(
            body: ErrorView(
              title: 'Network Timeout',
              message: 'Failed to connect to store backend.',
              onRetry: () => retryClicked = true,
            ),
          ),
        ),
      );

      expect(find.text('Network Timeout'), findsOneWidget);
      expect(find.text('Failed to connect to store backend.'), findsOneWidget);
      expect(find.text('Try Again'), findsOneWidget);

      await tester.tap(find.text('Try Again'));
      await tester.pump();

      expect(retryClicked, isTrue);
    });
  });
}
