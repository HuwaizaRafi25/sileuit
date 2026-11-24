<?php

namespace App\Modules\ProcurementReceiving\Models;

use App\Modules\IdentityAccess\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class PriceListItem extends Model
{
    protected $fillable = ['commodity_id', 'grade_id', 'harga_per_kg', 'berlaku_dari', 'berlaku_sampai', 'disetujui_oleh', 'disetujui_pada'];

    protected function casts(): array
    {
        return [
            'harga_per_kg' => 'integer',
            'berlaku_dari' => 'date',
            'berlaku_sampai' => 'date',
            'disetujui_pada' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // IR-06: periode harga per grade tidak boleh tumpang tindih (padanan MySQL, DEV-001).
        static::saving(function (self $item) {
            $q = static::query()
                ->where('commodity_id', $item->commodity_id)
                ->where('grade_id', $item->grade_id)
                ->whereDate('berlaku_dari', '<=', $item->berlaku_sampai ?? '9999-12-31')
                ->where(fn (Builder $w) => $w->whereNull('berlaku_sampai')->orWhereDate('berlaku_sampai', '>=', $item->berlaku_dari));
            if ($item->exists) {
                $q->whereKeyNot($item->getKey());
            }
            if ($q->exists()) {
                throw ValidationException::withMessages(['berlaku_dari' => 'Periode harga tumpang tindih dengan harga lain untuk grade ini (IR-06).']);
            }
        });
    }

    public function commodity(): BelongsTo
    {
        return $this->belongsTo(Commodity::class);
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disetujui_oleh');
    }
}
