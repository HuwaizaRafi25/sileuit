<?php

namespace App\Modules\IdentityAccess\Models;

use App\Modules\ProcurementReceiving\Models\Partner;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'hp', 'password', 'pin_hash', 'status', 'active_location_id', 'partner_id'];

    protected $hidden = ['password', 'pin_hash', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'hp' => 'encrypted',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $user): void {
            if ($user->isDirty('hp')) {
                if (filled($user->hp)) {
                    $normalized = self::normalizeHp($user->hp);
                    $user->hp = $normalized;
                    $user->hp_hash = $normalized ? hash('sha256', $normalized) : null;
                } else {
                    $user->hp = null;
                    $user->hp_hash = null;
                }
            }
        });
    }

    public static function normalizeHp(?string $hp): ?string
    {
        if (! filled($hp)) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $hp) ?? '';
        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62'.$digits;
        }

        return '+'.$digits;
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function locationScopes(): BelongsToMany
    {
        return $this->belongsToMany(Location::class, 'user_location_scopes');
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function activeLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'active_location_id');
    }

    public function hasRole(string ...$kode): bool
    {
        return $this->roles->whereIn('kode', $kode)->isNotEmpty();
    }

    public function isGlobal(): bool
    {
        return $this->hasRole('PEMILIK', 'ADMIN', 'PENGADAAN', 'KEUANGAN');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->status === 'AKTIF' && $this->isGlobal();
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }
}
