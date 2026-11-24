<?php

namespace App\Modules\IdentityAccess\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    protected $fillable = ['kode', 'nama'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
