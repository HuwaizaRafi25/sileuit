<?php

it('mengembalikan 200 dan struktur healthz sesuai Bagian J.4.1', function () {
    $response = $this->getJson('/healthz');

    $response->assertOk()
        ->assertJsonStructure([
            'status',
            'timestamp',
            'checks' => [
                'database' => ['status', 'latency_ms'],
                'redis_cache' => ['status', 'latency_ms'],
                'storage_writable' => ['status'],
                'disk_space' => ['status', 'free_percent'],
            ],
        ])
        ->assertJsonPath('status', 'healthy')
        ->assertJsonPath('checks.database.status', 'up')
        ->assertJsonPath('checks.redis_cache.status', 'up');
});

it('menolak metode selain GET pada healthz', function () {
    $this->postJson('/healthz')->assertMethodNotAllowed();
});
