<?php

namespace App\Services\Build;

interface FlutterRunnerInterface
{
    /**
     * Run flutter build appbundle in the specified workspace directory.
     *
     * @param  string  $workspaceDir  Isolated build workspace directory
     * @param  array<string, string>  $dartDefines  Safe key-value environment pairs
     * @param  int  $buildNumber  Version code
     * @param  string  $buildName  Version name (e.g. 1.0.0)
     * @param  int  $timeoutSeconds  Process timeout in seconds
     * @return array{success: bool, output: string, artifact_path: ?string, error: ?string}
     */
    public function runBuild(
        string $workspaceDir,
        array $dartDefines,
        int $buildNumber,
        string $buildName,
        int $timeoutSeconds = 1800
    ): array;
}
