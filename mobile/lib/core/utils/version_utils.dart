class VersionUtils {
  VersionUtils._();

  /// Compares [current] version with [required] minVersion.
  /// Returns true if [current] is strictly less than [required].
  static bool isUpdateRequired(String current, String required) {
    final currentParts = _parseParts(current);
    final requiredParts = _parseParts(required);

    for (int i = 0; i < 3; i++) {
      final curr = currentParts[i];
      final req = requiredParts[i];
      if (curr < req) return true;
      if (curr > req) return false;
    }

    return false;
  }

  static List<int> _parseParts(String version) {
    final clean = version.split('+').first.replaceAll(RegExp(r'[^0-9.]'), '');
    final segments = clean.split('.');
    final major = segments.isNotEmpty ? int.tryParse(segments[0]) ?? 0 : 0;
    final minor = segments.length > 1 ? int.tryParse(segments[1]) ?? 0 : 0;
    final patch = segments.length > 2 ? int.tryParse(segments[2]) ?? 0 : 0;
    return [major, minor, patch];
  }
}
