<?php

namespace App\Modules\BillingAccountsReceivable\Http\Controllers;

use App\Infrastructure\Services\AuditLogger;
use App\Modules\BillingAccountsReceivable\Models\Payment;
use App\Modules\IdentityAccess\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentWebhookController
{
    /** H.2: verifikasi HMAC-SHA512 + idempotensi (TC-13). */
    public function handle(Request $request): JsonResponse
    {
        $secret = (string) config('services.webhook.secret');
        $raw = $request->getContent();
        $expected = hash_hmac('sha512', $raw, $secret);
        $signature = (string) $request->header('X-Signature');

        if ($signature === '' || ! hash_equals($expected, $signature)) {
            return response()->json(['message' => 'Signature tidak valid.'], 401);
        }

        $payload = json_decode($raw, true) ?: [];
        $eventId = $payload['event_id'] ?? null;
        if (! $eventId) {
            return response()->json(['message' => 'event_id wajib ada.'], 422);
        }
        if (($payload['status'] ?? '') !== 'paid') {
            return response()->json(['message' => 'Status bukan paid, diabaikan.'], 200);
        }

        $sudah = Payment::where('idempotency_key', $eventId)->first();
        if ($sudah) {
            return response()->json(['message' => 'Pembayaran sudah tercatat.', 'payment_id' => $sudah->id], 200);
        }

        $payment = Payment::create([
            'arah' => 'MASUK', 'partner_id' => null, 'metode' => 'GATEWAY',
            'jumlah' => (int) ($payload['amount'] ?? 0), 'referensi' => $payload['order_ref'] ?? null,
            'idempotency_key' => $eventId, 'dicatat_oleh' => User::where('email', 'keu-1@sileuit.id')->value('id'),
        ]);

        AuditLogger::log('WEBHOOK_GATEWAY', 'payments', $payment->id, null, ['event_id' => $eventId]);

        return response()->json(['message' => 'Pembayaran dicatat.', 'payment_id' => $payment->id], 200);
    }
}
