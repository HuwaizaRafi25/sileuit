<?php

use App\Modules\BillingAccountsReceivable\Models\Payment;

beforeEach(fn () => $this->seed());

it('TC-13: webhook ganda dengan signature valid diproses sekali', function () {
    config(['services.webhook.secret' => 'test-secret']);
    $payload = json_encode(['event_id' => 'evt-001', 'order_ref' => 'ORD-1', 'amount' => 50000, 'status' => 'paid']);
    $sig = hash_hmac('sha512', $payload, 'test-secret');

    $this->call('POST', '/api/v1/payments/webhook', [], [], [], ['HTTP_X-Signature' => $sig, 'CONTENT_TYPE' => 'application/json'], $payload)->assertOk();
    $this->call('POST', '/api/v1/payments/webhook', [], [], [], ['HTTP_X-Signature' => $sig, 'CONTENT_TYPE' => 'application/json'], $payload)->assertOk();

    expect(Payment::where('idempotency_key', 'evt-001')->count())->toBe(1);
});

it('webhook dengan signature tidak valid ditolak 401', function () {
    config(['services.webhook.secret' => 'test-secret']);
    $payload = json_encode(['event_id' => 'evt-002', 'amount' => 1000, 'status' => 'paid']);

    $this->call('POST', '/api/v1/payments/webhook', [], [], [], ['HTTP_X-Signature' => 'palsu', 'CONTENT_TYPE' => 'application/json'], $payload)->assertUnauthorized();
    expect(Payment::count())->toBe(0);
});
