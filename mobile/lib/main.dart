import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'core/config/config_provider.dart';
import 'core/routing/router.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(
    const ProviderScope(
      child: WebIntoAppWhiteLabelEngine(),
    ),
  );
}

class WebIntoAppWhiteLabelEngine extends ConsumerWidget {
  const WebIntoAppWhiteLabelEngine({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final router = ref.watch(appRouterProvider);
    final themeData = ref.watch(currentThemeDataProvider);
    final config = ref.watch(appConfigNotifierProvider).config;

    return MaterialApp.router(
      title: config.app.name,
      debugShowCheckedModeBanner: false,
      theme: themeData,
      routerConfig: router,
    );
  }
}
