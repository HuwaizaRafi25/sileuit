<?php

namespace App\Modules\ProcurementReceiving\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inspection extends Model
{
    protected $fillable = ['receipt_id', 'kadar_air', 'kadar_hampa', 'butir_patah', 'varietas', 'grade_id', 'keputusan', 'alasan', 'foto_attachment_id'];

    protected function casts(): array
    {
        return ['kadar_air' => 'decimal:1', 'kadar_hampa' => 'decimal:1', 'butir_patah' => 'decimal:1'];
    }

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(Receipt::class);
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    public function foto(): BelongsTo
    {
        return $this->belongsTo(Attachment::class, 'foto_attachment_id');
    }
}
