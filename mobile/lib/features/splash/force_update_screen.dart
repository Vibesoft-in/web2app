import 'package:flutter/material.dart';

class ForceUpdateScreen extends StatelessWidget {
  final String requiredVersion;
  final String currentVersion;
  final VoidCallback? onUpdate;

  const ForceUpdateScreen({
    super.key,
    required this.requiredVersion,
    required this.currentVersion,
    this.onUpdate,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Scaffold(
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 32.0, vertical: 24.0),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            crossAxisAlignment: CrossAxisAlignment.center,
            children: [
              const Spacer(),
              Container(
                height: 100,
                width: 100,
                decoration: BoxDecoration(
                  color: theme.colorScheme.primary.withOpacity(0.1),
                  shape: BoxShape.circle,
                ),
                child: Icon(
                  Icons.system_update_rounded,
                  size: 50,
                  color: theme.colorScheme.primary,
                ),
              ),
              const SizedBox(height: 24),
              Text(
                'Update Required',
                style: theme.textTheme.headlineSmall?.copyWith(
                  fontWeight: FontWeight.w800,
                ),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 12),
              Text(
                'A new version of the app is available with critical enhancements, checkout security, and new features. Please update to continue shopping.',
                style: theme.textTheme.bodyMedium?.copyWith(
                  color: theme.colorScheme.onSurface.withOpacity(0.7),
                  height: 1.5,
                ),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 20),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                decoration: BoxDecoration(
                  color: theme.colorScheme.surface,
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(color: theme.colorScheme.onSurface.withOpacity(0.1)),
                ),
                child: Text(
                  'Installed: v$currentVersion • Required: v$requiredVersion',
                  style: theme.textTheme.bodySmall?.copyWith(
                    fontWeight: FontWeight.w600,
                    color: theme.colorScheme.onSurface.withOpacity(0.6),
                  ),
                ),
              ),
              const Spacer(),
              ElevatedButton.icon(
                onPressed: onUpdate ?? () {},
                icon: const Icon(Icons.download_rounded),
                label: const Text('Update Now on Google Play'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
