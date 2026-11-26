<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HetConfigs\Pages\ManageHetConfigs;
use App\Filament\Support\SafeDelete;
use App\Modules\PointOfSale\Models\HetConfig;
use Closure;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class HetConfigResource extends Resource
{
    protected static ?string $model = HetConfig::class;

    public static function getNavigationGroup(): ?string
    {
        return 'Harga & Kontrak';
    }

    public static function getModelLabel(): string
    {
        return 'Konfigurasi HET';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('PEMILIK', 'ADMIN', 'PENGADAAN') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('commodity_id')
                ->label('Komoditas')
                ->relationship('commodity', 'nama')
                ->required()
                ->native(false),

            TextInput::make('harga_per_kg')
                ->label('HET Maksimal per kg')
                ->numeric()
                ->prefix('Rp')
                ->required()
                ->minValue(1),

            Select::make('mode')
                ->label('Kebijakan Saat Melebihi HET')
                ->options([
                    'BLOKIR' => 'Blokir — Tolak penjualan melebihi HET',
                    'PERINGATAN' => 'Peringatan — Izinkan penjualan dengan catatan',
                ])
                ->default('BLOKIR')
                ->required()
                ->native(false),

            Toggle::make('aktif')
                ->label('Aktif')
                ->default(true)
                ->helperText('Hanya boleh ada 1 konfigurasi HET aktif per komoditas.')
                ->rules([
                    fn (Get $get, ?HetConfig $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get, $record): void {
                        if (! $value || ! $get('commodity_id')) {
                            return;
                        }

                        $exists = HetConfig::query()
                            ->where('commodity_id', (int) $get('commodity_id'))
                            ->where('aktif', true)
                            ->when($record, fn ($q) => $q->whereKeyNot($record->id))
                            ->exists();

                        if ($exists) {
                            $fail('Komoditas ini sudah memiliki konfigurasi HET yang aktif. Nonaktifkan yang lama terlebih dahulu.');
                        }
                    },
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('commodity.nama')->label('Komoditas')->sortable(),
                TextColumn::make('harga_per_kg')
                    ->label('HET/kg')
                    ->formatStateUsing(fn ($state): string => 'Rp'.number_format((int) $state, 0, ',', '.')),
                TextColumn::make('mode')
                    ->label('Kebijakan')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'BLOKIR' ? 'danger' : 'warning'),
                IconColumn::make('aktif')->label('Aktif')->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
                SafeDelete::make(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageHetConfigs::route('/')];
    }
}
