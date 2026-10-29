<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\StoreSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class SettingsController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $settings = Cache::remember('settings.global', 3600, fn () => StoreSettings::resolve());
        } catch (\Throwable $e) {
            $settings = StoreSettings::resolve();
        }

        return response()->json($settings);
    }
}
