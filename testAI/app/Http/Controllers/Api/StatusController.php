<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class StatusController extends Controller
{
   #[OA\Get(
    path: '/api/health',
    summary: 'Проверка статуса сервиса',
    tags: ['Status'],
    responses: [
        new OA\Response(
            response: 200,
            description: 'OK'
        )
    ]
)]
    public function health(): JsonResponse
    {
        $dbOk = true;
        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $dbOk = false;
        }

        return response()->json([
            'status'    => $dbOk ? 'ok' : 'degraded',
            'timestamp' => now()->toIso8601String(),
            'services'  => [
                'database' => $dbOk,
                'ai' => filled(config('services.gigachat.auth_key')),
            ],
        ]);
    }

   #[OA\Get(
    path: '/api/metrics',
    summary: 'Статистика обращений',
    tags: ['Status'],
    responses: [
        new OA\Response(
            response: 200,
            description: 'OK'
        )
    ]
)]
   public function metrics(): JsonResponse
{
    $path = storage_path('app/metrics.json');

    $defaults = ['total' => 0, 'by_sentiment' => [], 'by_category' => [], 'ai_failures' => 0];
    $decoded  = file_exists($path) ? json_decode((string) file_get_contents($path), true) : null;

    return response()->json(is_array($decoded) ? array_merge($defaults, $decoded) : $defaults);
}
}
