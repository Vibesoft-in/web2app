import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../theme/theme_engine.dart';
import 'app_config_model.dart';
import 'config_repository.dart';

final configRepositoryProvider = Provider<ConfigRepository>((ref) {
  return ConfigRepository();
});

class ConfigState {
  final AppConfig config;
  final bool isLoading;
  final String? errorMessage;

  const ConfigState({
    required this.config,
    this.isLoading = false,
    this.errorMessage,
  });

  ConfigState copyWith({
    AppConfig? config,
    bool? isLoading,
    String? errorMessage,
  }) {
    return ConfigState(
      config: config ?? this.config,
      isLoading: isLoading ?? this.isLoading,
      errorMessage: errorMessage,
    );
  }
}

class ConfigNotifier extends StateNotifier<ConfigState> {
  final ConfigRepository _repository;

  ConfigNotifier(this._repository)
      : super(ConfigState(config: AppConfig.defaults(), isLoading: true)) {
    loadConfig();
  }

  Future<void> loadConfig() async {
    state = state.copyWith(isLoading: true, errorMessage: null);
    try {
      final config = await _repository.fetchAppConfig();
      state = ConfigState(config: config, isLoading: false);
    } catch (e) {
      // Fallback to cache if available
      final cached = await _repository.getCachedConfig();
      if (cached != null) {
        state = ConfigState(config: cached, isLoading: false);
      } else {
        state = state.copyWith(
          isLoading: false,
          errorMessage: e.toString(),
        );
      }
    }
  }

  void updateConfig(AppConfig newConfig) {
    state = state.copyWith(config: newConfig);
  }
}

final appConfigNotifierProvider =
    StateNotifierProvider<ConfigNotifier, ConfigState>((ref) {
  final repo = ref.watch(configRepositoryProvider);
  return ConfigNotifier(repo);
});

final currentThemeDataProvider = Provider<ThemeData>((ref) {
  final config = ref.watch(appConfigNotifierProvider).config;
  return ThemeEngine.buildTheme(config.theme);
});
