<?php

namespace App\Modules\IdentityAccess\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $fillable = ['kode', 'nama', 'tipe', 'alamat', 'aktif'];

    protected function casts(): array
    {
        return ['aktif' => 'boolean'];
    }
}
