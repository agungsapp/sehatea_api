<?php

namespace App\Http\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class ApiResponse
{
    /**
     * Return a standard success response.
     *
     * @param  array<string, mixed>|null  $data
     */
    public static function success(string $message, mixed $data = null, int $status = Response::HTTP_OK): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    /**
     * Return a standard failure response.
     */
    public static function error(string $message, int $status = Response::HTTP_BAD_REQUEST, mixed $data = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    /**
     * Return a standardized validation error response.
     */
    public static function validation(ValidationException $e): JsonResponse
    {
        return self::error($e->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY, $e->errors());
    }
}
