<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Filament\Support\SafeDelete;
use App\Infrastructure\Services\AuditLogger;
use App\Modules\IdentityAccess\Models\Location;
use App\Modules\IdentityAccess\Models\Role;
use App\Modules\IdentityAccess\Models\User;
use App\Modules\ProcurementReceiving\Models\Partner;
use Closure;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $recordTitleAttribute = 'name';

    /** @var array<int, string> */
    public const GLOBAL_ROLES = ['PEMILIK', 'ADMIN', 'PENGADAAN', 'KEUANGAN'];

    /** @var array<int, string> */
    public const WAREHOUSE_ROLES = ['PETUGAS_GUDANG', 'KEPALA_GUDANG', 'KURIR'];

    /** @var array<int, string> */
    public const BRANCH_ROLES = ['KASIR'];

    /** @var array<int, string> */
    public const PARTNER_ROLES = ['PETANI', 'MITRA_B2B'];

    public static function getNavigationGroup(): ?string
    {
        return 'Master';
    }

    public static function getModelLabel(): string
    {
        return 'Pengguna';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('PEMILIK', 'ADMIN') ?? false;
    }

    public static function isLocationBoundRole(?string $roleKode): bool
    {
        return in_array($roleKode, [...self::WAREHOUSE_ROLES, ...self::BRANCH_ROLES], true);
    }

    public static function requiredLocationTypeForRole(?string $roleKode): ?string
    {
        if (in_array($roleKode, self::BRANCH_ROLES, true)) {
            return 'CABANG';
        }

        if (in_array($roleKode, self::WAREHOUSE_ROLES, true)) {
            return 'GUDANG';
        }

        return null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('1. Peran Pengguna')
                ->description('Setiap akun memiliki satu peran utama yang menentukan hak akses, lokasi tugas, dan metode masuk.')
                ->columnSpanFull()
                ->schema([
                    Select::make('role_kode')
                        ->label('Peran')
                        ->options(fn (): array => self::roleOptions())
                        ->required()
                        ->live()
                        ->rules([
                            fn (?User $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($record): void {
                                if (! $record || ! $record->hasRole('PEMILIK') || $value === 'PEMILIK') {
                                    return;
                                }

                                $otherActivePemilik = User::query()
                                    ->where('status', 'AKTIF')
                                    ->whereKeyNot($record->id)
                                    ->whereHas('roles', fn ($q) => $q->where('kode', 'PEMILIK'))
                                    ->exists();

                                if (! $otherActivePemilik) {
                                    $fail('Minimal harus ada 1 akun Pemilik aktif di dalam sistem.');
                                }
                            },
                        ])
                        ->afterStateUpdated(function (?string $state, Set $set): void {
                            if (! self::isLocationBoundRole($state)) {
                                $set('assigned_location_id', null);
                            }

                            if (! in_array($state, self::PARTNER_ROLES, true)) {
                                $set('partner_id', null);
                            }

                            if ($state === 'PETANI') {
                                $set('email', null);
                                $set('password', null);
                            } else {
                                $set('pin_hash', null);
                            }
                        }),

                    Callout::make('Panduan Peran')
                        ->info()
                        ->visible(fn (Get $get): bool => filled($get('role_kode')))
                        ->description(fn (Get $get): string => match ($get('role_kode')) {
                            'PEMILIK', 'ADMIN', 'PENGADAAN', 'KEUANGAN' => 'Akses Lintas Lokasi (Global): dapat mengakses seluruh gudang dan cabang melalui panel Admin maupun aplikasi operasional tanpa pembatasan lokasi.',
                            'PETUGAS_GUDANG', 'KEPALA_GUDANG', 'KURIR' => 'Penugasan Gudang: wajib ditugaskan ke tepat 1 lokasi bertipe Gudang. Seluruh transaksi timbangan, stok, dan pengantaran mengikuti gudang tersebut.',
                            'KASIR' => 'Penugasan Cabang: wajib ditugaskan ke tepat 1 lokasi bertipe Cabang untuk operasional kasir POS dan penerimaan stok cabang.',
                            'PETANI' => 'Portal Petani Mitra: petani masuk ke /portal menggunakan Nomor HP terdaftar + PIN 6-digit (tidak memerlukan email dan kata sandi).',
                            'MITRA_B2B' => 'Portal Mitra Retail B2B: akun khusus mitra retail untuk pemesanan stok, pantau tagihan/piutang, dan pengajuan retur.',
                            default => '',
                        }),
                ]),

            Section::make('2. Penugasan Lokasi Kerja')
                ->description('Akun lapangan hanya terikat pada satu lokasi tugas agar cakupan stok dan transaksi tidak bercampur.')
                ->columnSpanFull()
                ->visible(fn (Get $get): bool => self::isLocationBoundRole($get('role_kode')))
                ->schema([
                    Select::make('assigned_location_id')
                        ->label(fn (Get $get): string => $get('role_kode') === 'KASIR' ? 'Cabang Tugas' : 'Gudang Tugas')
                        ->helperText(fn (Get $get): string => $get('role_kode') === 'KASIR'
                            ? 'Hanya menampilkan lokasi bertipe Cabang yang aktif.'
                            : 'Hanya menampilkan lokasi bertipe Gudang yang aktif.')
                        ->options(function (Get $get, ?User $record): array {
                            $tipe = self::requiredLocationTypeForRole($get('role_kode'));
                            if (! $tipe) {
                                return [];
                            }

                            return Location::query()
                                ->where('tipe', $tipe)
                                ->where(function ($q) use ($record): void {
                                    $q->where('aktif', true);
                                    if ($record?->active_location_id) {
                                        $q->orWhere('id', $record->active_location_id);
                                    }
                                })
                                ->orderBy('kode')
                                ->get()
                                ->mapWithKeys(fn (Location $loc): array => [
                                    $loc->id => "{$loc->kode} — {$loc->nama}".($loc->aktif ? '' : ' (Nonaktif)'),
                                ])
                                ->all();
                        })
                        ->required(fn (Get $get): bool => self::isLocationBoundRole($get('role_kode')))
                        ->native(false)
                        ->rules([
                            fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                                $requiredType = self::requiredLocationTypeForRole($get('role_kode'));
                                if (! $requiredType || ! $value) {
                                    return;
                                }

                                $location = Location::find($value);
                                if (! $location || $location->tipe !== $requiredType) {
                                    $fail("Lokasi tugas untuk peran ini harus bertipe {$requiredType}.");
                                }
                            },
                        ]),
                ]),

            Section::make('2. Keterkaitan Data Mitra')
                ->description('Hubungkan akun dengan entitas Mitra agar riwayat panen atau tagihan langsung sinkron.')
                ->columnSpanFull()
                ->visible(fn (Get $get): bool => in_array($get('role_kode'), self::PARTNER_ROLES, true))
                ->schema([
                    Select::make('partner_id')
                        ->key(fn (Get $get): string => 'partner-select-'.($get('role_kode') ?? 'none'))
                        ->label(fn (Get $get): string => $get('role_kode') === 'PETANI' ? 'Data Petani Mitra' : 'Data Mitra Retail B2B')
                        ->helperText('Hanya menampilkan mitra aktif yang belum punya akun. Jika mitra baru belum terdaftar, klik tombol + untuk menambahkan langsung.')
                        ->noOptionsMessage('Semua data mitra aktif saat ini sudah memiliki akun pengguna. Klik tombol + di kanan untuk mendaftarkan data mitra baru.')
                        ->options(function (Get $get, ?User $record): array {
                            $roleKode = $get('role_kode');
                            if (! in_array($roleKode, self::PARTNER_ROLES, true)) {
                                return [];
                            }

                            $partnerTypes = $roleKode === 'PETANI' ? ['FARMER'] : ['RETAIL', 'CUSTOMER'];
                            $usedPartnerIds = User::query()
                                ->whereNotNull('partner_id')
                                ->when($record, fn ($q) => $q->whereKeyNot($record->id))
                                ->pluck('partner_id');

                            return Partner::query()
                                ->whereIn('tipe', $partnerTypes)
                                ->whereNotIn('id', $usedPartnerIds)
                                ->where(function ($q) use ($record): void {
                                    $q->where('status', 'AKTIF');
                                    if ($record?->partner_id) {
                                        $q->orWhere('id', $record->partner_id);
                                    }
                                })
                                ->orderBy('nama')
                                ->get()
                                ->mapWithKeys(fn (Partner $partner): array => [
                                    $partner->id => "{$partner->nama} ({$partner->hp})",
                                ])
                                ->all();
                        })
                        ->searchable()
                        ->preload()
                        ->live()
                        ->required(fn (Get $get): bool => in_array($get('role_kode'), self::PARTNER_ROLES, true))
                        ->unique(table: User::class, column: 'partner_id', ignoreRecord: true, modifyRuleUsing: null)
                        ->validationMessages([
                            'unique' => 'Data mitra ini sudah terhubung dengan akun pengguna lain.',
                        ])
                        ->createOptionForm(function (Get $get): array {
                            $isFarmer = $get('role_kode') === 'PETANI';

                            return [
                                TextInput::make('nama')
                                    ->label($isFarmer ? 'Nama Petani' : 'Nama Mitra Retail')
                                    ->required()
                                    ->maxLength(100),
                                TextInput::make('hp')
                                    ->label('Nomor HP (+62...)')
                                    ->tel()
                                    ->required()
                                    ->maxLength(20)
                                    ->placeholder('081234567890')
                                    ->rules([
                                        fn (): Closure => function (string $attribute, mixed $value, Closure $fail) use ($isFarmer): void {
                                            $normalized = User::normalizeHp(is_string($value) ? $value : null);
                                            if (! $normalized) {
                                                return;
                                            }
                                            $exists = Partner::query()
                                                ->where('tipe', $isFarmer ? 'FARMER' : 'RETAIL')
                                                ->where('hp_hash', hash('sha256', $normalized))
                                                ->exists();
                                            if ($exists) {
                                                $fail('Nomor HP ini sudah terdaftar di data mitra.');
                                            }
                                        },
                                    ]),
                                TextInput::make('kelompok')
                                    ->label('Kelompok Tani')
                                    ->maxLength(80)
                                    ->visible($isFarmer),
                                TextInput::make('batas_kredit')
                                    ->label('Batas Kredit (Rp)')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->default(0)
                                    ->minValue(0)
                                    ->visible(! $isFarmer),
                                Select::make('termin_hari')
                                    ->label('Termin Kredit (Hari)')
                                    ->options([7 => '7 hari', 14 => '14 hari', 30 => '30 hari'])
                                    ->default(14)
                                    ->required(! $isFarmer)
                                    ->visible(! $isFarmer),
                            ];
                        })
                        ->createOptionUsing(function (array $data, Get $get, Set $set): int {
                            $isFarmer = $get('role_kode') === 'PETANI';

                            $partner = Partner::create([
                                'tipe' => $isFarmer ? 'FARMER' : 'RETAIL',
                                'nama' => $data['nama'],
                                'hp' => $data['hp'],
                                'kelompok' => $isFarmer ? ($data['kelompok'] ?? null) : null,
                                'batas_kredit' => $isFarmer ? 0 : (int) ($data['batas_kredit'] ?? 0),
                                'termin_hari' => $isFarmer ? null : (int) ($data['termin_hari'] ?? 14),
                                'status' => 'AKTIF',
                            ]);

                            if (! filled($get('name'))) {
                                $set('name', $partner->nama);
                            }

                            if (! filled($get('hp'))) {
                                $set('hp', $partner->hp);
                            }

                            return $partner->getKey();
                        })
                        ->afterStateUpdated(function (mixed $state, Set $set, Get $get): void {
                            if (! $state) {
                                return;
                            }

                            $partner = Partner::find((int) $state);
                            if (! $partner) {
                                return;
                            }

                            if (! filled($get('name'))) {
                                $set('name', $partner->nama);
                            }

                            if (! filled($get('hp'))) {
                                $set('hp', $partner->hp);
                            }
                        }),
                ]),

            Section::make('3. Identitas & Kredensial Masuk')
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Nama Lengkap')
                        ->required()
                        ->maxLength(100),

                    Select::make('status')
                        ->label('Status Akun')
                        ->options(['AKTIF' => 'Aktif', 'NONAKTIF' => 'Nonaktif'])
                        ->default('AKTIF')
                        ->required()
                        ->native(false)
                        ->rules([
                            fn (?User $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($record): void {
                                if (! $record || $value !== 'NONAKTIF') {
                                    return;
                                }

                                if ($record->id === auth()->id()) {
                                    $fail('Anda tidak dapat menonaktifkan akun yang sedang dipakai sendiri.');

                                    return;
                                }

                                if ($record->hasRole('PEMILIK')) {
                                    $otherActivePemilik = User::query()
                                        ->where('status', 'AKTIF')
                                        ->whereKeyNot($record->id)
                                        ->whereHas('roles', fn ($q) => $q->where('kode', 'PEMILIK'))
                                        ->exists();

                                    if (! $otherActivePemilik) {
                                        $fail('Minimal harus ada 1 akun Pemilik aktif di dalam sistem.');
                                    }
                                }
                            },
                        ]),

                    TextInput::make('email')
                        ->label('Alamat Email')
                        ->email()
                        ->maxLength(255)
                        ->placeholder('contoh@sileuit.id')
                        ->visible(fn (Get $get): bool => $get('role_kode') !== 'PETANI')
                        ->required(fn (Get $get): bool => filled($get('role_kode')) && $get('role_kode') !== 'PETANI')
                        ->dehydrated(fn (Get $get): bool => $get('role_kode') !== 'PETANI')
                        ->unique(ignoreRecord: true),

                    TextInput::make('hp')
                        ->label(fn (Get $get): string => $get('role_kode') === 'PETANI' ? 'Nomor HP Masuk Portal' : 'Nomor HP (Opsional)')
                        ->tel()
                        ->maxLength(20)
                        ->placeholder('081234567890 atau +6281234567890')
                        ->helperText('Otomatis dinormalisasi ke format +62 saat disimpan.')
                        ->required(fn (Get $get): bool => $get('role_kode') === 'PETANI')
                        ->rules([
                            fn (?User $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($record): void {
                                $normalized = User::normalizeHp(is_string($value) ? $value : null);
                                if (! $normalized) {
                                    return;
                                }

                                $exists = User::query()
                                    ->where('hp_hash', hash('sha256', $normalized))
                                    ->when($record, fn ($q) => $q->whereKeyNot($record->id))
                                    ->exists();

                                if ($exists) {
                                    $fail('Nomor HP ini sudah digunakan oleh akun pengguna lain.');
                                }
                            },
                        ]),

                    TextInput::make('password')
                        ->label(fn (string $operation): string => $operation === 'create' ? 'Kata Sandi' : 'Kata Sandi Baru (opsional)')
                        ->password()
                        ->revealable()
                        ->minLength(8)
                        ->helperText(fn (string $operation): string => $operation === 'create'
                            ? 'Minimal 8 karakter.'
                            : 'Kosongkan jika tidak ingin mengubah kata sandi.')
                        ->visible(fn (Get $get): bool => $get('role_kode') !== 'PETANI')
                        ->required(fn (string $operation, Get $get): bool => $operation === 'create' && $get('role_kode') !== 'PETANI')
                        ->dehydrated(fn (?string $state, Get $get): bool => $get('role_kode') !== 'PETANI' && filled($state))
                        ->columnSpanFull(),

                    TextInput::make('pin_hash')
                        ->label(fn (string $operation): string => $operation === 'create' ? 'PIN 6-Digit Portal Petani' : 'PIN 6-Digit Baru (opsional)')
                        ->password()
                        ->revealable()
                        ->numeric()
                        ->length(6)
                        ->helperText(fn (string $operation): string => $operation === 'create'
                            ? 'Wajib 6 angka untuk masuk ke Portal Petani (/login/petani).'
                            : 'Kosongkan jika tidak ingin mengubah PIN petani.')
                        ->visible(fn (Get $get): bool => $get('role_kode') === 'PETANI')
                        ->required(fn (string $operation, Get $get): bool => $operation === 'create' && $get('role_kode') === 'PETANI')
                        ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Hash::make($state) : null)
                        ->dehydrated(fn (?string $state, Get $get): bool => $get('role_kode') === 'PETANI' && filled($state))
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('roles.nama')
                    ->label('Peran')
                    ->badge()
                    ->color('primary'),
                TextColumn::make('penugasan')
                    ->label('Lokasi / Mitra')
                    ->state(function (User $record): string {
                        if ($record->isGlobal()) {
                            return 'Lintas Lokasi (Global)';
                        }

                        if ($record->activeLocation) {
                            return "{$record->activeLocation->kode} — {$record->activeLocation->nama}";
                        }

                        if ($record->partner) {
                            return "Mitra: {$record->partner->nama}";
                        }

                        return 'Belum ditentukan';
                    })
                    ->badge()
                    ->color(fn (User $record): string => $record->isGlobal() ? 'info' : 'gray'),
                TextColumn::make('email')
                    ->label('Email')
                    ->placeholder('— (Login HP & PIN)')
                    ->searchable(),
                TextColumn::make('hp')
                    ->label('No. HP')
                    ->placeholder('—'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'AKTIF' => 'success',
                        'NONAKTIF' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('roles')
                    ->label('Peran')
                    ->relationship('roles', 'nama'),
                SelectFilter::make('status')
                    ->options(['AKTIF' => 'Aktif', 'NONAKTIF' => 'Nonaktif']),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateRecordDataUsing(fn (array $data, User $record): array => self::hydrateFormData($record, $data))
                    ->using(fn (User $record, array $data): User => self::saveUserRecord($record, $data)),
                SafeDelete::make(
                    guardReason: function (User $record): ?string {
                        if ($record->id === auth()->id()) {
                            return 'Anda tidak dapat menghapus akun yang sedang dipakai sendiri.';
                        }

                        if ($record->hasRole('PEMILIK')) {
                            $otherActivePemilik = User::query()
                                ->where('status', 'AKTIF')
                                ->whereKeyNot($record->id)
                                ->whereHas('roles', fn ($q) => $q->where('kode', 'PEMILIK'))
                                ->exists();

                            if (! $otherActivePemilik) {
                                return 'Minimal harus ada 1 akun Pemilik aktif di dalam sistem.';
                            }
                        }

                        return null;
                    },
                    afterDelete: function (User $record): void {
                        AuditLogger::log('PENGGUNA_DIHAPUS', 'users', $record->id, ['name' => $record->name], null);
                    },
                ),
            ])
            ->defaultSort('name');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function hydrateFormData(User $record, array $data): array
    {
        $data['role_kode'] = $record->roles()->value('kode');
        $data['assigned_location_id'] = $record->active_location_id
            ?? $record->locationScopes()->value('locations.id');

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function saveUserRecord(?User $record, array $data): User
    {
        $roleKode = (string) ($data['role_kode'] ?? '');
        $assignedLocationId = filled($data['assigned_location_id'] ?? null) ? (int) $data['assigned_location_id'] : null;

        /** @var Role $role */
        $role = Role::where('kode', $roleKode)->firstOrFail();

        $attributes = Arr::except($data, ['role_kode', 'assigned_location_id']);

        if ($roleKode === 'PETANI') {
            $attributes['email'] = null;
            $attributes['password'] = null;
            $attributes['active_location_id'] = null;
            $scopeIds = [];
        } elseif ($roleKode === 'MITRA_B2B') {
            $attributes['pin_hash'] = null;
            $attributes['active_location_id'] = null;
            $scopeIds = [];
        } elseif (in_array($roleKode, self::GLOBAL_ROLES, true)) {
            $attributes['pin_hash'] = null;
            $attributes['active_location_id'] = null;
            $attributes['partner_id'] = null;
            $scopeIds = [];
        } else {
            $attributes['pin_hash'] = null;
            $attributes['partner_id'] = null;
            $attributes['active_location_id'] = $assignedLocationId;
            $scopeIds = $assignedLocationId ? [$assignedLocationId] : [];
        }

        return DB::transaction(function () use ($record, $attributes, $role, $scopeIds, $roleKode, $assignedLocationId): User {
            $isNew = $record === null;

            if ($isNew) {
                $user = User::create($attributes);
            } else {
                $record->update($attributes);
                $user = $record->fresh();
            }

            $user->roles()->sync([$role->id]);
            $user->locationScopes()->sync($scopeIds);

            AuditLogger::log(
                $isNew ? 'PENGGUNA_DIBUAT' : 'PENGGUNA_DIPERBARUI',
                'users',
                $user->id,
                null,
                [
                    'name' => $user->name,
                    'peran' => $roleKode,
                    'lokasi_id' => $assignedLocationId,
                    'partner_id' => $user->partner_id,
                    'status' => $user->status,
                ]
            );

            return $user;
        });
    }

    /**
     * @return array<string, string>
     */
    private static function roleOptions(): array
    {
        $suffixes = [
            'PEMILIK' => 'Akses Global',
            'ADMIN' => 'Akses Global',
            'PENGADAAN' => 'Akses Global',
            'KEUANGAN' => 'Akses Global',
            'PETUGAS_GUDANG' => 'Lokasi Gudang',
            'KEPALA_GUDANG' => 'Lokasi Gudang',
            'KURIR' => 'Lokasi Gudang',
            'KASIR' => 'Lokasi Cabang',
            'PETANI' => 'Portal HP & PIN',
            'MITRA_B2B' => 'Akun Mitra Retail',
        ];

        return Role::query()
            ->orderBy('id')
            ->get()
            ->mapWithKeys(function (Role $role) use ($suffixes): array {
                $suffix = $suffixes[$role->kode] ?? 'Operasional';

                return [$role->kode => "{$role->nama} — {$suffix}"];
            })
            ->all();
    }

    public static function getPages(): array
    {
        return ['index' => ManageUsers::route('/')];
    }
}
