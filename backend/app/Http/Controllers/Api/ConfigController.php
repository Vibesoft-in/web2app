<?php

namespace App\Http\Controllers\Api;

use App\Enums\SetupStep;
use App\Http\Controllers\Controller;
use App\Models\App;
use App\Models\AppConfig;
use App\Services\Config\ConfigValidator;
use App\Services\Setup\AppSetupStateMachine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConfigController extends Controller
{
    public function __construct(
        protected ConfigValidator $validator,
        protected AppSetupStateMachine $stateMachine,
    ) {}

    /**
     * Show current draft and published configurations for the app.
     */
    public function show(App $app): JsonResponse
    {
        $draft = $app->configs()->where('state', 'draft')->latest('version')->first();
        $published = $app->configs()->where('state', 'published')->latest('version')->first();

        return response()->json([
            'data' => [
                'draft' => $draft,
                'published' => $published,
            ],
        ]);
    }

    /**
     * Save or update draft configuration after strict validation.
     */
    public function saveDraft(Request $request, App $app): JsonResponse
    {
        /** @var array<string, mixed> $payload */
        $payload = $request->input('config', $request->all());
        $baseVersion = $request->input('base_version');

        // Validate via ConfigValidator (rejects non-hex colors, non-https URLs, oversized values)
        $validatedConfig = $this->validator->validate($payload);

        $result = DB::transaction(function () use ($app, $validatedConfig, $baseVersion) {
            /** @var AppConfig|null $existingDraft */
            $existingDraft = $app->configs()
                ->where('state', 'draft')
                ->lockForUpdate()
                ->first();

            if ($existingDraft) {
                // Optimistic concurrency check: if client provided base_version and it differs from server version
                if ($baseVersion !== null && (int) $existingDraft->version !== (int) $baseVersion) {
                    return [
                        'conflict' => true,
                        'server_version' => $existingDraft->version,
                        'current_config' => $existingDraft->config,
                    ];
                }

                $existingDraft->config = $validatedConfig;
                $existingDraft->version = (int) $existingDraft->version + 1;
                $existingDraft->save();

                return ['draft' => $existingDraft];
            }

            $lastVersion = (int) ($app->configs()->max('version') ?? 0);
            $newVersion = $lastVersion + 1;

            $draft = AppConfig::create([
                'tenant_id' => $app->tenant_id,
                'app_id' => $app->id,
                'version' => $newVersion,
                'state' => 'draft',
                'config' => $validatedConfig,
            ]);

            return ['draft' => $draft];
        });

        if (isset($result['conflict']) && $result['conflict']) {
            return response()->json([
                'error' => [
                    'code' => 'VERSION_CONFLICT',
                    'message' => 'The configuration was modified in another session. Please refresh and try again.',
                    'server_version' => $result['server_version'],
                    'current_config' => $result['current_config'],
                ],
            ], 409);
        }

        // Ensure app advances to theme step if currently in earlier step
        $this->stateMachine->advanceTo($app, SetupStep::THEME);

        return response()->json([
            'message' => 'Draft configuration saved successfully.',
            'data' => $result['draft'],
        ]);
    }

    /**
     * Publish the configuration and advance app state.
     */
    public function publish(Request $request, App $app): JsonResponse
    {
        /** @var array<string, mixed> $payload */
        $payload = $request->input('config');

        $published = DB::transaction(function () use ($app, $payload) {
            $configToPublish = null;

            if (! empty($payload) && is_array($payload)) {
                $configToPublish = $this->validator->validate($payload);
            }

            $draft = $app->configs()->where('state', 'draft')->first();

            if (! $draft && ! $configToPublish) {
                abort(422, 'No draft configuration found to publish.');
            }

            if ($draft) {
                if ($configToPublish) {
                    $draft->config = $configToPublish;
                }
                $draft->state = 'published';
                $draft->published_at = now();
                $draft->save();

                return $draft;
            }

            $lastVersion = (int) ($app->configs()->max('version') ?? 0);
            $newVersion = $lastVersion + 1;

            /** @var array<string, mixed> $configToPublish */
            return AppConfig::create([
                'tenant_id' => $app->tenant_id,
                'app_id' => $app->id,
                'version' => $newVersion,
                'state' => 'published',
                'config' => $configToPublish,
                'published_at' => now(),
            ]);
        });

        $app->status = 'configured';
        $app->save();

        // Advance to preview step
        $this->stateMachine->advanceTo($app, SetupStep::PREVIEW);

        return response()->json([
            'message' => 'Configuration published successfully.',
            'data' => $published,
        ]);
    }
}
