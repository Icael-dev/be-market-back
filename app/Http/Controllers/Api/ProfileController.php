<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Address;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json($request->user()->load('profile'));
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->only(['name', 'email', 'full_name', 'phone', 'avatar_url']);

        $validator = Validator::make($data, [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', 'unique:users,email,' . $request->user()->id],
            'full_name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'avatar_url' => ['sometimes', 'nullable', 'url'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        if (isset($data['full_name'])) {
            $user->name = $data['full_name'];
        }
        if (isset($data['name'])) {
            $user->name = $data['name'];
        }
        if (isset($data['email'])) {
            $user->email = $data['email'];
        }
        $user->save();

        $profile = $user->profile;
        if ($profile) {
            $profile->fill($data);
            $profile->save();
        }

        return response()->json($user->load('profile'));
    }

    public function addresses(Request $request): JsonResponse
    {
        return response()->json($request->user()->addresses()->get());
    }

    public function storeAddress(Request $request): JsonResponse
    {
        $data = $request->only(['label', 'full_name', 'phone', 'address_line1', 'address_line2', 'city', 'region', 'country', 'postal_code', 'is_default']);

        $validator = Validator::make($data, [
            'label' => ['sometimes', 'nullable', 'string', 'max:255'],
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'address_line1' => ['required', 'string', 'max:255'],
            'address_line2' => ['sometimes', 'nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'region' => ['sometimes', 'nullable', 'string', 'max:255'],
            'country' => ['required', 'string', 'max:255'],
            'postal_code' => ['sometimes', 'nullable', 'string', 'max:50'],
            'is_default' => ['sometimes', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if (!empty($data['is_default'])) {
            $request->user()->addresses()->update(['is_default' => false]);
        }

        $address = $request->user()->addresses()->create($data);

        return response()->json($address, 201);
    }

    public function updateAddress(Request $request, string $id): JsonResponse
    {
        $address = $request->user()->addresses()->where('id', $id)->first();
        if (!$address) {
            return response()->json(['message' => 'Address not found.'], 404);
        }

        $data = $request->only(['label', 'full_name', 'phone', 'address_line1', 'address_line2', 'city', 'region', 'country', 'postal_code', 'is_default']);

        $validator = Validator::make($data, [
            'label' => ['sometimes', 'nullable', 'string', 'max:255'],
            'full_name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:30'],
            'address_line1' => ['sometimes', 'string', 'max:255'],
            'address_line2' => ['sometimes', 'nullable', 'string', 'max:255'],
            'city' => ['sometimes', 'string', 'max:255'],
            'region' => ['sometimes', 'nullable', 'string', 'max:255'],
            'country' => ['sometimes', 'string', 'max:255'],
            'postal_code' => ['sometimes', 'nullable', 'string', 'max:50'],
            'is_default' => ['sometimes', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if (!empty($data['is_default'])) {
            $request->user()->addresses()->update(['is_default' => false]);
        }

        $address->fill($data);
        $address->save();

        return response()->json($address);
    }

    public function destroyAddress(Request $request, string $id): JsonResponse
    {
        $address = $request->user()->addresses()->where('id', $id)->first();
        if (!$address) {
            return response()->json(['message' => 'Address not found.'], 404);
        }

        $address->delete();

        return response()->json(['message' => 'Address deleted.']);
    }
}
