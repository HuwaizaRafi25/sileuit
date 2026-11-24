<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Commodities\Pages\ManageCommodities;
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
            TextInput::make('nama')->required()->maxLength(50)->unique(ignoreRecord: true),
            TextInput::make('satuan_dasar')->default('kg')->required()->maxLength(5),
            TextInput::make('umur_simpan_hari')->label('Umur simpan (hari)')->numeric()->default(180)->required()->minValue(1),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama')->searchable()->sortable(),
                TextColumn::make('satuan_dasar'),
                TextColumn::make('umur_simpan_hari')->label('Umur simpan (hari)'),
            ])
            ->recordActions([EditAction::make()])
            ->defaultSort('nama');
    }

    public static function getPages(): array
    {
        return ['index' => ManageCommodities::route('/')];
    }
}
