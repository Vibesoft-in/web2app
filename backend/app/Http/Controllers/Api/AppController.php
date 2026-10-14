<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\App;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppController extends Controller
{
    /**
     * List apps belonging strictly to the authenticated tenant.
     */
    public function index(): JsonResponse
    {
        $apps = App::all();

        return response()->json([
            'data' => $apps,
        ]);
    }

    /**
     * Show a single app (returns 404 if app belongs to another tenant).
     */
    public function show(App $app): JsonResponse
    {
        return response()->json([
            'data' => $app,
        ]);
    }

    /**
     * Store a new app scoped to the authenticated tenant.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'platform' => ['required', 'in:woo,shopify'],
            'package_name' => ['required', 'string', 'max:255', 'unique:apps,package_name'],
        ]);

        $app = App::create($validated);

        return response()->json([
            'data' => $app,
        ], 201);
    }
}
