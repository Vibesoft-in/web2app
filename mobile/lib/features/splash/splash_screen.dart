import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../core/config/config_provider.dart';
import '../../core/env/app_env.dart';
import '../../core/utils/version_utils.dart';
import '../../core/widgets/error_view.dart';
import 'force_update_screen.dart';
import 'maintenance_screen.dart';

class SplashScreen extends ConsumerWidget {
  const SplashScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final configState = ref.watch(appConfigNotifierProvider);
    final theme = Theme.of(context);

    if (configState.isLoading) {
      return Scaffold(
        backgroundColor: theme.scaffoldBackgroundColor,
        body: Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Container(
                height: 84,
                width: 84,
                decoration: BoxDecoration(
                  color: theme.colorScheme.primary,
                  borderRadius: BorderRadius.circular(24),
                  boxShadow: [
                    BoxShadow(
                      color: theme.colorScheme.primary.withOpacity(0.35),
                      blurRadius: 24,
                      offset: const Offset(0, 8),
                    ),
                  ],
                ),
                child: const Icon(
                  Icons.shopping_bag_rounded,
                  size: 44,
                  color: Colors.white,
                ),
              ),
              const SizedBox(height: 28),
              Text(
                configState.config.app.name,
                style: theme.textTheme.headlineSmall?.copyWith(
                  fontWeight: FontWeight.w800,
                  letterSpacing: -0.5,
                ),
              ),
              const SizedBox(height: 32),
              SizedBox(
                height: 24,
                width: 24,
                child: CircularProgressIndicator(
                  strokeWidth: 2.5,
                  valueColor: AlwaysStoppedAnimation<Color>(theme.colorScheme.primary),
                ),
              ),
            ],
          ),
        ),
      );
    }

    // 1. Check Maintenance Gate
    if (configState.config.app.maintenance) {
      return MaintenanceScreen(
        onRetry: () => ref.read(appConfigNotifierProvider.notifier).loadConfig(),
      );
    }

    // 2. Check Force-Update Gate
    final minVersion = configState.config.app.minVersion;
    if (VersionUtils.isUpdateRequired(AppEnv.appVersion, minVersion)) {
      return ForceUpdateScreen(
        requiredVersion: minVersion,
        currentVersion: AppEnv.appVersion,
        onUpdate: () {},
      );
    }

    // 3. Error with no cache fallback
    if (configState.errorMessage != null &&
        configState.config.configVersion == 1 &&
        configState.config.app.name == 'Store App') {
      return Scaffold(
        body: ErrorView(
          title: 'Store Connection Failed',
          message: configState.errorMessage!,
          onRetry: () => ref.read(appConfigNotifierProvider.notifier).loadConfig(),
        ),
      );
    }

    // 4. Config Ready: Auto-navigate to home
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (context.mounted) {
        context.go('/home');
      }
    });

    return const Scaffold(
      body: Center(child: CircularProgressIndicator()),
    );
  }
}
