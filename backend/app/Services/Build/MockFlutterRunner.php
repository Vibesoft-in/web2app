<?php

namespace App\Services\Build;

class MockFlutterRunner implements FlutterRunnerInterface
{
    protected bool $shouldFail = false;

    protected ?string $failureMessage = null;

    public function setFailure(bool $fail, ?string $message = 'Simulated Flutter build failure.'): self
    {
        $this->shouldFail = $fail;
        $this->failureMessage = $message;

        return $this;
    }

    public function runBuild(
        string $workspaceDir,
        array $dartDefines,
        int $buildNumber,
        string $buildName,
        int $timeoutSeconds = 1800
    ): array {
        if ($this->shouldFail) {
            return [
                'success' => false,
                'output' => '[Flutter Mock] Build process started.'.PHP_EOL.'[Error] Gradle build failed with exit code 1.'.PHP_EOL.($this->failureMessage ?? ''),
                'artifact_path' => null,
                'error' => $this->failureMessage ?? 'Gradle build failed.',
            ];
        }

        // Create mock output directory and artifact
        $outDir = $workspaceDir.DIRECTORY_SEPARATOR.'build'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'outputs'.DIRECTORY_SEPARATOR.'bundle'.DIRECTORY_SEPARATOR.'release';
        $apkDir = $workspaceDir.DIRECTORY_SEPARATOR.'build'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'outputs'.DIRECTORY_SEPARATOR.'flutter-apk';
        if (! is_dir($outDir)) {
            mkdir($outDir, 0755, true);
        }
        if (! is_dir($apkDir)) {
            mkdir($apkDir, 0755, true);
        }

        $aabPath = $outDir.DIRECTORY_SEPARATOR.'app-release.aab';
        $apkPath = $apkDir.DIRECTORY_SEPARATOR.'app-release.apk';

        $templatePath = resource_path('templates/app-release.apk');
        if (file_exists($templatePath)) {
            copy($templatePath, $apkPath);
            copy($templatePath, $aabPath);
            $sizeMb = round(filesize($apkPath) / 1048576, 1);
        } else {
            // Mock standard zip / bundle binary headers
            file_put_contents($aabPath, "PK\x03\x04MockAndroidAppBundleForBuild#{$buildNumber}Name={$buildName}");
            file_put_contents($apkPath, "PK\x03\x04MockAndroidApkForBuild#{$buildNumber}Name={$buildName}");
            $sizeMb = 16.2;
        }

        $output = '[Flutter Engine] Building Android App Bundle & Standalone APK...'.PHP_EOL.
            "Running Gradle task 'assembleRelease' & 'bundleRelease'...".PHP_EOL.
            "Built build/app/outputs/flutter-apk/app-release.apk ({$sizeMb}MB)".PHP_EOL.
            "Built build/app/outputs/bundle/release/app-release.aab ({$sizeMb}MB).";

        return [
            'success' => true,
            'output' => $output,
            'artifact_path' => $apkPath,
            'error' => null,
        ];
    }
}
