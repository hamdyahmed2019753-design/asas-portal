<?php

namespace App\Http\Controllers;

use App\Services\HealthService;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    public function __invoke(HealthService $health): JsonResponse
    {
        $result = $health->check();

        return response()->json([
            'status' => $result['healthy'] ? 'ok' : 'unavailable',
            'timestamp' => now()->toIso8601String(),
            'checks' => $result['checks'],
        ], $result['healthy'] ? 200 : 503);
    }
}
