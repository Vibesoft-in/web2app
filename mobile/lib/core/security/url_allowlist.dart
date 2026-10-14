class UrlAllowlist {
  final List<String> allowedHosts;

  const UrlAllowlist({this.allowedHosts = const []});

  /// Evaluates whether [url] is safe to load within the in-app Webview.
  /// Strict security rule: HTTPS only and host must be present in whitelist.
  bool isAllowed(String? url) {
    if (url == null || url.isEmpty) return false;

    try {
      final uri = Uri.parse(url);
      if (uri.scheme.toLowerCase() != 'https') return false;

      final host = uri.host.toLowerCase();
      if (host.isEmpty) return false;

      // Check exact match or subdomain match
      return allowedHosts.any((allowed) {
        final cleanAllowed = allowed.toLowerCase().trim();
        return host == cleanAllowed || host.endsWith('.$cleanAllowed');
      });
    } catch (_) {
      return false;
    }
  }
}
