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
        if (! is_dir($outDir)) {
            mkdir($outDir, 0755, true);
        }

        $aabPath = $outDir.DIRECTORY_SEPARATOR.'app-release.aab';
        // Mock standard zip / bundle binary header
        file_put_contents($aabPath, "PK\x03\x04MockAndroidAppBundleForBuild#{$buildNumber}Name={$buildName}");

        $output = "[Flutter Mock] Building Android App Bundle...".PHP_EOL.
            "Running Gradle task 'bundleRelease'...".PHP_EOL.
            "Built build/app/outputs/bundle/release/app-release.aab (18.4MB).";

        return [
            'success' => true,
            'output' => $output,
            'artifact_path' => $aabPath,
            'error' => null,
        ];
    }
}
