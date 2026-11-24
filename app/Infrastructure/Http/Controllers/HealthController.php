<?php

namespace App\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class HealthController
{
    public function __invoke(): JsonResponse
    {
        $checks = [];
        $healthy = true;

        $start = microtime(true);
        try {
            DB::select('SELECT 1');
            $checks['database'] = [
                'status' => 'up',
                'latency_ms' => round((microtime(true) - $start) * 1000, 1),
            ];
        } catch (\Throwable) {
            $healthy = false;
            $checks['database'] = ['status' => 'down', 'error' => 'connection_failed'];
        }

        $start = microtime(true);
        try {
            Redis::connection()->ping();
            $checks['redis_cache'] = [
                'status' => 'up',
                'latency_ms' => round((microtime(true) - $start) * 1000, 1),
            ];
        } catch (\Throwable) {
            $healthy = false;
            $checks['redis_cache'] = ['status' => 'down', 'error' => 'connection_failed'];
        }

        $writable = is_writable(storage_path('framework')) && is_writable(storage_path('logs'));
        $checks['storage_writable'] = ['status' => $writable ? 'up' : 'down'];
        $healthy = $healthy && $writable;

        $free = disk_free_space(base_path());
        $total = disk_total_space(base_path());
        $freePercent = $total > 0 ? round($free / $total * 100, 1) : 0.0;
        $diskOk = $freePercent >= 20;
        $checks['disk_space'] = ['status' => $diskOk ? 'ok' : 'low', 'free_percent' => $freePercent];
        $healthy = $healthy && $diskOk;

        return response()->json([
            'status' => $healthy ? 'healthy' : 'degraded',
            'timestamp' => now()->utc()->format('Y-m-d\TH:i:s\Z'),
            'checks' => $checks,
        ], $healthy ? 200 : 503);
    }
}
