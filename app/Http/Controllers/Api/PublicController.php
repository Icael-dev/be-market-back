<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\BlogPost;
use App\Models\FAQ;
use App\Models\LegalPage;
use App\Models\FlashSale;
use App\Models\NewsletterSubscriber;
use App\Models\Setting;
use App\Models\StockNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PublicController extends Controller
{
    public function banners(): JsonResponse
    {
        return response()->json(Banner::where('is_active', true)->orderBy('sort_order')->get());
    }

    public function blog(): JsonResponse
    {
        $query = BlogPost::where('is_published', true);

        if ($request->query('published')) {
            $query->where('is_published', true);
        }

        return response()->json($query->orderBy('published_at', 'desc')->get());
    }

    public function blogDetail(string $slug): JsonResponse
    {
        $post = BlogPost::where('slug', $slug)->where('is_published', true)->first();
        if (!$post) {
            return response()->json(['message' => 'Blog post not found.'], 404);
        }
        return response()->json($post);
    }

    public function faqs(): JsonResponse
    {
        return response()->json(FAQ::where('is_active', true)->orderBy('sort_order')->get());
    }

    public function legalPages(): JsonResponse
    {
        return response()->json(LegalPage::all());
    }

    public function legalPage(string $slug): JsonResponse
    {
        $page = LegalPage::where('slug', $slug)->first();
        if (!$page) {
            return response()->json(['message' => 'Legal page not found.'], 404);
        }
        return response()->json($page);
    }

    public function flashSales(): JsonResponse
    {
        $query = FlashSale::with('product');

        if ($request->boolean('active')) {
            $now = now();
            $query->where('is_active', true)
                ->where('starts_at', '<=', $now)
                ->where('ends_at', '>=', $now);
        }

        return response()->json($query->get());
    }

    public function newsletter(Request $request): JsonResponse
    {
        $data = $request->only(['email']);
        $validator = Validator::make($data, [
            'email' => ['required', 'email'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        NewsletterSubscriber::updateOrCreate(
            ['email' => $data['email']],
            ['is_active' => true, 'subscribed_at' => now()]
        );

        return response()->json(['message' => 'Subscribed successfully.']);
    }

    public function stockNotifications(Request $request): JsonResponse
    {
        $data = $request->only(['product_id', 'email']);
        $validator = Validator::make($data, [
            'product_id' => ['required', 'uuid', 'exists:products,id'],
            'email' => ['required', 'email'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        StockNotification::create([
            'product_id' => $data['product_id'],
            'email' => $data['email'],
            'is_notified' => false,
        ]);

        return response()->json(['message' => 'Stock notification registered.']);
    }
}
