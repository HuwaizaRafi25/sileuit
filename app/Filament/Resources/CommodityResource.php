<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Commodities\Pages\ManageCommodities;
use App\Filament\Support\SafeDelete;
use App\Modules\ProcurementReceiving\Models\Commodity;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CommodityResource extends Resource
{
    protected static ?string $model = Commodity::class;

    protected static ?string $recordTitleAttribute = 'nama';

    public static function getNavigationGroup(): ?string
    {
        return 'Master';
    }

    public static function getModelLabel(): string
    {
        return 'Komoditas';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('PEMILIK', 'ADMIN', 'PENGADAAN') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nama')
                ->label('Nama Komoditas')
                ->required()
                ->maxLength(50)
                ->dehydrateStateUsing(fn (?string $state): string => strtoupper(trim((string) $state)))
                ->unique(ignoreRecord: true)
                ->disabled(fn (?Commodity $record): bool => in_array($record?->nama, ['GABAH', 'BERAS'], true))
                ->dehydrated(true)
                ->helperText('Komoditas inti GABAH dan BERAS dipakai langsung oleh alur penggilingan & penjualan.'),

            TextInput::make('satuan_dasar')
                ->label('Satuan Dasar')
                ->default('kg')
                ->required()
                ->maxLength(5),

            TextInput::make('umur_simpan_hari')
                ->label('Umur Simpan Standar')
                ->numeric()
                ->suffix('hari')
                ->default(180)
                ->required()
                ->minValue(1),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama')->label('Nama Komoditas')->searchable()->sortable(),
                TextColumn::make('satuan_dasar')->label('Satuan Dasar'),
                TextColumn::make('umur_simpan_hari')
                    ->label('Umur Simpan')
                    ->formatStateUsing(fn ($state): string => number_format((int) $state, 0, ',', '.').' hari'),
            ])
            ->recordActions([
                EditAction::make(),
                SafeDelete::make(function (Commodity $record): ?string {
                    if (in_array($record->nama, ['GABAH', 'BERAS'], true)) {
                        return 'Komoditas inti GABAH dan BERAS tidak dapat dihapus.';
                    }

                    if ($record->grades()->exists()) {
                        return 'Komoditas ini masih memiliki Grade terdaftar.';
                    }

                    return null;
                }),
            ])
            ->defaultSort('nama');
    }

    public static function getPages(): array
    {
        return ['index' => ManageCommodities::route('/')];
    }
}
