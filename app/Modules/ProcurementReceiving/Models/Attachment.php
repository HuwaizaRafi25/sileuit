<?php

namespace App\Modules\ProcurementReceiving\Models;

use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    protected $fillable = ['path', 'disk', 'mime', 'ukuran_byte', 'diunggah_oleh'];
}
