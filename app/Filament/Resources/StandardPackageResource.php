<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StandardPackages\Pages\ManageStandardPackages;
use App\Modules\ProcurementReceiving\Models\StandardPackage;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StandardPackageResource extends Resource
{
    protected static ?string $model = StandardPackage::class;

    protected static ?string $recordTitleAttribute = 'nama';

    public static function getNavigationGroup(): ?string
    {
        return 'Master';
    }

    public static function getModelLabel(): string
    {
        return 'Paket Standar';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('PEMILIK', 'ADMIN', 'PENGADAAN') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nama')->required()->maxLength(80),
            TextInput::make('jenis_pupuk')->maxLength(80),
            TextInput::make('jendela_tanam')->label('Jendela tanam')->maxLength(80),
            KeyValue::make('parameter_mutu')->keyLabel('Parameter')->valueLabel('Nilai')->nullable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama')->searchable()->sortable(),
                TextColumn::make('jenis_pupuk'),
                TextColumn::make('jendela_tanam'),
            ])
            ->recordActions([EditAction::make()])
            ->defaultSort('nama');
    }

    public static function getPages(): array
    {
        return ['index' => ManageStandardPackages::route('/')];
    }
}
