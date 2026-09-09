<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClientPortalDeviceTokenRequest;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class DeviceController extends Controller
{
    public function store(ClientPortalDeviceTokenRequest $request): JsonResponse
    {
        /** @var Client $client */
        $client = $request->user();
        $token = (string) $request->validated()['expo_push_token'];

        if ($client->expo_push_token !== $token) {
            $client->forceFill([
                'expo_push_token' => $token,
                'expo_push_token_updated_at' => Carbon::now(),
            ])->save();
        }

        return response()->json([
            'message' => __('client_portal.device.token_saved'),
        ]);
    }
}
