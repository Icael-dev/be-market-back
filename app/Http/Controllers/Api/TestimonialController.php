<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class TestimonialController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $testimonials = Cache::remember('testimonials.active', 3600, function () {
                return Testimonial::where('is_active', true)
                    ->orderBy('sort_order', 'asc')
                    ->orderBy('created_at', 'desc')
                    ->get();
            });
        } catch (\Throwable $e) {
            $testimonials = Testimonial::where('is_active', true)
                ->orderBy('sort_order', 'asc')
                ->orderBy('created_at', 'desc')
                ->get();
        }

        if (! $testimonials instanceof Collection) {
            $testimonials = Testimonial::where('is_active', true)
                ->orderBy('sort_order', 'asc')
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return response()->json($testimonials);
    }
}
