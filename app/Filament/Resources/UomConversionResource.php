<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UomConversions\Pages\ManageUomConversions;
use App\Filament\Support\SafeDelete;
use App\Modules\ProcurementReceiving\Models\UomConversion;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UomConversionResource extends Resource
{
    protected static ?string $model = UomConversion::class;

    protected static ?string $recordTitleAttribute = 'nama';

    public static function getNavigationGroup(): ?string
    {
        return 'Master';
    }

    public static function getModelLabel(): string
    {
        return 'Konversi Satuan';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('PEMILIK', 'ADMIN', 'PENGADAAN') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nama')
                ->label('Kode / Nama Satuan')
                ->placeholder('KARUNG_50')
                ->required()
                ->maxLength(30)
                ->dehydrateStateUsing(fn (?string $state): string => strtoupper(trim((string) $state)))
                ->unique(ignoreRecord: true),

            TextInput::make('faktor_ke_kg')
                ->label('Setara Kilogram')
                ->helperText('Contoh: isi 50 jika 1 satuan = 50 kg.')
                ->numeric()
                ->suffix('kg')
                ->required()
                ->minValue(0.001),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama')->label('Satuan')->searchable()->sortable(),
                TextColumn::make('faktor_ke_kg')
                    ->label('kg per Satuan')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 3, ',', '.').' kg'),
            ])
            ->recordActions([
                EditAction::make(),
                SafeDelete::make(),
            ])
            ->defaultSort('nama');
    }

    public static function getPages(): array
    {
        return ['index' => ManageUomConversions::route('/')];
    }
}
