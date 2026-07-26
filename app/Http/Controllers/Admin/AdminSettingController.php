<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\StoreSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSettingController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(StoreSettings::resolve());
    }

    public function update(Request $request): JsonResponse
    {
        $updates = $request->validate([
            'store_name' => 'sometimes|string',
            'store_email' => 'sometimes|string|email',
            'store_phone' => 'sometimes|string',
            'currency' => 'sometimes|string',
            'tax_rate' => 'sometimes|numeric',
            'shipping_fee' => 'sometimes|numeric',
            'free_shipping_threshold' => 'sometimes|numeric',
            'loyalty_points_threshold' => 'sometimes|numeric|min:0',
            'site_name' => 'sometimes|string',
            'site_description' => 'sometimes|string',
            'support_email' => 'sometimes|string|email',
            'shipping_cost' => 'sometimes|numeric',
        ]);

        $aliases = [
            'site_name' => 'store_name',
            'support_email' => 'store_email',
            'shipping_cost' => 'shipping_fee',
        ];

        foreach ($updates as $key => $value) {
            $canonicalKey = $aliases[$key] ?? $key;
            Setting::updateOrCreate(['key' => $canonicalKey], ['value' => $value]);
        }

        return response()->json(StoreSettings::resolve());
    }
}
