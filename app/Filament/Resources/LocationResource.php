<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Locations\Pages\ManageLocations;
use App\Modules\IdentityAccess\Models\Location;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LocationResource extends Resource
{
    protected static ?string $model = Location::class;

    protected static ?string $recordTitleAttribute = 'nama';

    public static function getNavigationGroup(): ?string
    {
        return 'Master';
    }

    public static function getModelLabel(): string
    {
        return 'Lokasi';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('PEMILIK', 'ADMIN') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('kode')->required()->maxLength(10)->unique(ignoreRecord: true),
            TextInput::make('nama')->required()->maxLength(80),
            Select::make('tipe')->options(['GUDANG' => 'Gudang', 'CABANG' => 'Cabang'])->required(),
            TextInput::make('alamat')->maxLength(255),
            Toggle::make('aktif')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kode')->searchable()->sortable(),
                TextColumn::make('nama')->searchable(),
                TextColumn::make('tipe')->badge(),
                IconColumn::make('aktif')->boolean(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->defaultSort('kode');
    }

    public static function getPages(): array
    {
        return ['index' => ManageLocations::route('/')];
    }
}
