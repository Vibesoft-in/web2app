<?php

namespace App\Services\Build;

use Symfony\Component\Process\Process;

class SystemFlutterRunner implements FlutterRunnerInterface
{
    /**
     * Run real flutter build appbundle process.
     */
    public function runBuild(
        string $workspaceDir,
        array $dartDefines,
        int $buildNumber,
        string $buildName,
        int $timeoutSeconds = 1800
    ): array {
        $flutterBin = env('FLUTTER_BIN', 'flutter');

        $command = [
            $flutterBin,
            'build',
            'appbundle',
            '--release',
            "--build-number={$buildNumber}",
            "--build-name={$buildName}",
        ];

        foreach ($dartDefines as $key => $val) {
            $command[] = "--dart-define={$key}={$val}";
        }

        $process = new Process($command, $workspaceDir);
        $process->setTimeout($timeoutSeconds);

        try {
            $process->run();

            $output = $process->getOutput().PHP_EOL.$process->getErrorOutput();
            $aabPath = $workspaceDir.DIRECTORY_SEPARATOR.'build'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'outputs'.DIRECTORY_SEPARATOR.'bundle'.DIRECTORY_SEPARATOR.'release'.DIRECTORY_SEPARATOR.'app-release.aab';

            if ($process->isSuccessful() && file_exists($aabPath)) {
                return [
                    'success' => true,
                    'output' => $output,
                    'artifact_path' => $aabPath,
                    'error' => null,
                ];
            }

            return [
                'success' => false,
                'output' => $output,
                'artifact_path' => null,
                'error' => 'Flutter process exited with error code '.$process->getExitCode(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'output' => $process->getOutput(),
                'artifact_path' => null,
                'error' => $e->getMessage(),
            ];
        }
    }
}
