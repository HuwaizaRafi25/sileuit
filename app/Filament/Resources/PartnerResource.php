<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Partners\Pages\ManagePartners;
use App\Filament\Support\SafeDelete;
use App\Modules\IdentityAccess\Models\User;
use App\Modules\ProcurementReceiving\Models\Partner;
use Closure;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PartnerResource extends Resource
{
    protected static ?string $model = Partner::class;

    protected static ?string $recordTitleAttribute = 'nama';

    public static function getNavigationGroup(): ?string
    {
        return 'Master';
    }

    public static function getModelLabel(): string
    {
        return 'Mitra';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('PEMILIK', 'ADMIN', 'PENGADAAN') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identitas Mitra')
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    Select::make('tipe')
                        ->label('Jenis Mitra')
                        ->options([
                            'FARMER' => 'Petani Mitra (Pemasok Gabah)',
                            'RETAIL' => 'Mitra Retail B2B (Kredit/Grosir)',
                            'CUSTOMER' => 'Pelanggan Umum',
                        ])
                        ->required()
                        ->native(false)
                        ->live()
                        ->afterStateUpdated(function (?string $state, Set $set): void {
                            if ($state === 'FARMER') {
                                $set('batas_kredit', 0);
                                $set('termin_hari', null);
                            } else {
                                $set('kelompok', null);
                            }
                        }),

                    Select::make('status')
                        ->label('Status')
                        ->options(['AKTIF' => 'Aktif', 'NONAKTIF' => 'Nonaktif'])
                        ->default('AKTIF')
                        ->required()
                        ->native(false),

                    TextInput::make('nama')
                        ->label('Nama Lengkap / Usaha')
                        ->required()
                        ->maxLength(100),

                    TextInput::make('hp')
                        ->label('Nomor HP (+62...)')
                        ->tel()
                        ->required()
                        ->maxLength(20)
                        ->placeholder('081234567890')
                        ->helperText('Otomatis dinormalisasi ke format +62 saat disimpan.')
                        ->rules([
                            fn (Get $get, ?Partner $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get, $record): void {
                                $normalized = User::normalizeHp(is_string($value) ? $value : null);
                                $tipe = $get('tipe');
                                if (! $normalized || ! $tipe) {
                                    return;
                                }

                                $exists = Partner::query()
                                    ->where('tipe', $tipe)
                                    ->where('hp_hash', hash('sha256', $normalized))
                                    ->when($record, fn ($q) => $q->whereKeyNot($record->id))
                                    ->exists();

                                if ($exists) {
                                    $fail('Nomor HP ini sudah terdaftar untuk jenis mitra tersebut.');
                                }
                            },
                        ]),

                    TextInput::make('no_rekening')
                        ->label(fn (Get $get): string => $get('tipe') === 'FARMER'
                            ? 'No. Rekening Pencairan Dana (opsional)'
                            : 'No. Rekening Pembayaran (opsional)')
                        ->maxLength(40),

                    TextInput::make('kelompok')
                        ->label('Kelompok Tani')
                        ->maxLength(80)
                        ->visible(fn (Get $get): bool => $get('tipe') === 'FARMER')
                        ->dehydrated(fn (Get $get): bool => $get('tipe') === 'FARMER'),

                    Textarea::make('alamat')
                        ->label('Alamat Lengkap')
                        ->rows(2)
                        ->columnSpanFull(),
                ]),

            Section::make('Ketentuan Kredit B2B')
                ->description('Khusus Mitra Retail / Pelanggan yang melakukan pembelian tempo.')
                ->columnSpanFull()
                ->visible(fn (Get $get): bool => in_array($get('tipe'), ['RETAIL', 'CUSTOMER'], true))
                ->columns(2)
                ->schema([
                    TextInput::make('batas_kredit')
                        ->label('Batas Kredit')
                        ->numeric()
                        ->prefix('Rp')
                        ->default(0)
                        ->minValue(0)
                        ->dehydrateStateUsing(fn (mixed $state, Get $get): int => $get('tipe') === 'FARMER' ? 0 : (int) ($state ?? 0)),

                    Select::make('termin_hari')
                        ->label('Termin Pembayaran (Hari)')
                        ->options([7 => '7 hari', 14 => '14 hari', 30 => '30 hari'])
                        ->nullable()
                        ->native(false)
                        ->required(fn (Get $get): bool => $get('tipe') === 'RETAIL')
                        ->dehydrateStateUsing(fn (mixed $state, Get $get): ?int => $get('tipe') === 'FARMER'
                            ? null
                            : (filled($state) ? (int) $state : null)),
                ]),

            Section::make('Lahan Pertanian')
                ->description('Daftar petak lahan sawah milik petani mitra.')
                ->columnSpanFull()
                ->visible(fn (Get $get): bool => $get('tipe') === 'FARMER')
                ->schema([
                    Repeater::make('farmPlots')
                        ->label('Lahan Sawah')
                        ->relationship()
                        ->schema([
                            TextInput::make('nama_desa')->label('Nama Desa / Blok')->required()->maxLength(80),
                            TextInput::make('luas_ha')->label('Luas Lahan')->numeric()->minValue(0.01)->suffix('ha')->nullable(),
                        ])
                        ->defaultItems(0)
                        ->columns(2),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama')->label('Nama')->searchable()->sortable(),
                TextColumn::make('tipe')
                    ->label('Jenis')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'FARMER' => 'Petani',
                        'RETAIL' => 'Retail B2B',
                        'CUSTOMER' => 'Pelanggan',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'FARMER' => 'success',
                        'RETAIL' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('hp')->label('No. HP'),
                TextColumn::make('kelompok')->label('Kelompok Tani')->placeholder('—'),
                TextColumn::make('batas_kredit')
                    ->label('Batas Kredit')
                    ->formatStateUsing(fn ($state, Partner $record): string => $record->tipe === 'FARMER'
                        ? '—'
                        : 'Rp'.number_format((int) $state, 0, ',', '.')),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'AKTIF' ? 'success' : 'danger'),
            ])
            ->filters([
                SelectFilter::make('tipe')
                    ->label('Jenis Mitra')
                    ->options(['FARMER' => 'Petani', 'RETAIL' => 'Retail B2B', 'CUSTOMER' => 'Pelanggan']),
                SelectFilter::make('status')
                    ->options(['AKTIF' => 'Aktif', 'NONAKTIF' => 'Nonaktif']),
            ])
            ->recordActions([
                EditAction::make(),
                SafeDelete::make(function (Partner $record): ?string {
                    if ($record->contracts()->exists()) {
                        return 'Mitra ini sudah memiliki kontrak musim. Ubah status menjadi Nonaktif jika sudah tidak bermitra.';
                    }

                    if ($record->users()->exists()) {
                        return 'Mitra ini masih terhubung ke akun pengguna. Putuskan atau nonaktifkan akun terkait terlebih dahulu.';
                    }

                    return null;
                }),
            ])
            ->defaultSort('nama');
    }

    public static function getPages(): array
    {
        return ['index' => ManagePartners::route('/')];
    }
}
