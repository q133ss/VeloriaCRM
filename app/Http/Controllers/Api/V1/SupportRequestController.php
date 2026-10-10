<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SupportRequestStoreRequest;
use App\Models\SupportRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class SupportRequestController extends Controller
{
    public function store(SupportRequestStoreRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($data['contact_type'] === SupportRequest::TYPE_TELEGRAM) {
            $data['contact'] = '@' . ltrim($data['contact'], '@');
        }

        SupportRequest::create([
            'name' => $data['name'] ?? null,
            'contact_type' => $data['contact_type'],
            'contact' => $data['contact'],
            'message' => $data['message'] ?? null,
            'ip' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
        ]);

        return response()->json(['message' => __('help.guest.success')], 201);
    }
}
