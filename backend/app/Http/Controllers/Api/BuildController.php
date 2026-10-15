<?php

namespace App\Http\Controllers\Api;

use App\Enums\BuildStatus;
use App\Exceptions\BuildException;
use App\Http\Controllers\Controller;
use App\Models\App;
use App\Models\Build;
use App\Services\Build\BuildOrchestrator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BuildController extends Controller
{
    /**
     * List all builds for the app.
     */
    public function index(App $app): JsonResponse
    {
        $builds = $app->builds()->latest()->get();

        return response()->json([
            'data' => $builds,
        ]);
    }

    /**
     * Queue a new Android release build.
     */
    public function store(App $app, BuildOrchestrator $orchestrator): JsonResponse
    {
        try {
            $build = $orchestrator->queueBuild($app);

            return response()->json([
                'message' => 'Android build queued successfully.',
                'data' => $build,
            ], 201);
        } catch (BuildException $e) {
            return response()->json([
                'error' => [
                    'code' => $e->getErrorCode(),
                    'message' => $e->getMessage(),
                ],
            ], $e->getStatusCode());
        }
    }

    /**
     * Show single build details.
     */
    public function show(App $app, Build $build): JsonResponse
    {
        // Enforce strict app ownership
        if ($build->app_id !== $app->id) {
            return response()->json([
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Build not found.',
                ],
            ], 404);
        }

        return response()->json([
            'data' => $build,
        ]);
    }

    /**
     * Get expiring signed download URL for completed build artifact.
     */
    public function downloadUrl(App $app, Build $build): JsonResponse
    {
        if ($build->app_id !== $app->id) {
            return response()->json([
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Build not found.',
                ],
            ], 404);
        }

        if ($build->status !== BuildStatus::READY) {
            return response()->json([
                'error' => [
                    'code' => 'BUILD_NOT_READY',
                    'message' => "Build status is [{$build->status->value}]. Artifact is only available when ready.",
                ],
            ], 422);
        }

        if (empty($build->artifact_path) || ! Storage::disk('local')->exists($build->artifact_path)) {
            return response()->json([
                'error' => [
                    'code' => 'ARTIFACT_NOT_FOUND',
                    'message' => 'Artifact file is missing from private storage.',
                ],
            ], 404);
        }

        // Generate expiring signed URL valid for 15 minutes (900 seconds)
        $expiresAt = now()->addMinutes(15);
        $signedUrl = URL::temporarySignedRoute(
            'builds.download',
            $expiresAt,
            ['ulid' => $build->ulid]
        );

        return response()->json([
            'data' => [
                'download_url' => $signedUrl,
                'expires_at' => $expiresAt->toIso8601String(),
                'version_code' => $build->version_code,
                'version_name' => $build->version_name,
            ],
        ]);
    }

    /**
     * Stream artifact download securely from private storage via signed URL.
     */
    public function download(Request $request, string $ulid): StreamedResponse|JsonResponse
    {
        // Note: The 'signed' middleware verifies signature & timestamp before reaching here.
        /** @var Build|null $build */
        $build = Build::withoutGlobalScopes()->with('app')->where('ulid', $ulid)->first();

        if (! $build || empty($build->artifact_path) || ! Storage::disk('local')->exists($build->artifact_path)) {
            return response()->json([
                'error' => [
                    'code' => 'ARTIFACT_NOT_FOUND',
                    'message' => 'Requested artifact was not found.',
                ],
            ], 404);
        }

        $ext = pathinfo($build->artifact_path, PATHINFO_EXTENSION) ?: 'apk';

        if ($ext === 'apk' && $build->app) {
            app(\App\Services\Build\ApkCustomizer::class)->ensureAppApk($build->app, $build->artifact_path);
        }

        $filename = "{$build->app?->package_name}-v{$build->version_name}-b{$build->version_code}.{$ext}";

        return Storage::disk('local')->download($build->artifact_path, $filename, [
            'Content-Type' => $ext === 'apk' ? 'application/vnd.android.package-archive' : 'application/octet-stream',
        ]);
    }

    /**
     * Retrieve sanitized build execution logs.
     */
    public function logs(App $app, Build $build): JsonResponse
    {
        if ($build->app_id !== $app->id) {
            return response()->json([
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Build not found.',
                ],
            ], 404);
        }

        $logsContent = '';
        if ($build->logs_path && Storage::disk('local')->exists($build->logs_path)) {
            $logsContent = (string) Storage::disk('local')->get($build->logs_path);
        }

        return response()->json([
            'data' => [
                'status' => $build->status->value,
                'logs' => $logsContent,
                'error' => $build->error,
            ],
        ]);
    }
}
