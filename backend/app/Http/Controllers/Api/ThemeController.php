<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Theme;
use Illuminate\Http\JsonResponse;

class ThemeController extends Controller
{
    /**
     * List all active design themes.
     */
    public function index(): JsonResponse
    {
        $themes = Theme::where('is_active', true)->get();

        return response()->json([
            'data' => $themes,
        ]);
    }

    /**
     * Show details of a single theme by key.
     */
    public function show(string $key): JsonResponse
    {
        $theme = Theme::where('key', $key)->where('is_active', true)->firstOrFail();

        return response()->json([
            'data' => $theme,
        ]);
    }
}
