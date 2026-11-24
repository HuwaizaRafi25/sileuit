<?php

namespace App\Infrastructure\Services;

use App\Modules\AuditNotification\Models\AuditLog;

class AuditLogger
{
    private const SENSITIF = ['password', 'pin', 'pin_hash', 'no_rekening', 'hp'];

    public static function log(string $aksi, ?string $entitas = null, ?int $entitasId = null, ?array $lama = null, ?array $baru = null): void
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'aksi' => $aksi,
            'entitas' => $entitas,
            'entitas_id' => $entitasId,
            'nilai_lama' => self::redact($lama),
            'nilai_baru' => self::redact($baru),
            'ip' => request()->ip(),
            'user_agent' => substr((string) request()->userAgent(), 0, 255),
            'waktu' => now(),
        ]);
    }

    private static function redact(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        return collect($values)
            ->map(fn ($v, $k) => in_array(strtolower((string) $k), self::SENSITIF, true) ? '[REDACTED]' : $v)
            ->all();
    }
}
