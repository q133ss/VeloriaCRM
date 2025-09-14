<?php

namespace App\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Exceptions\HttpResponseException;

abstract class BaseService
{
    protected function errorResponse(string $code, string $message, array $fields = [], int $status = 400): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => $code,
                'message' => $message,
                'fields' => $fields,
            ],
        ], $status);
    }

    protected function throwError(string $code, string $message, array $fields = [], int $status = 400): void
    {
        throw new HttpResponseException($this->errorResponse($code, $message, $fields, $status));
    }
}
