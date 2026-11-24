<?php

namespace App\Modules\AuditNotification\Models;

use App\Modules\IdentityAccess\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

class AuditLog extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['nilai_lama' => 'array', 'nilai_baru' => 'array', 'waktu' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new RuntimeException('audit_logs append-only (IR-07).'));
        static::deleting(fn () => throw new RuntimeException('audit_logs append-only (IR-07).'));
    }
}
