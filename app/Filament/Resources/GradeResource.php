<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Grades\Pages\ManageGrades;
use App\Modules\ProcurementReceiving\Models\Grade;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GradeResource extends Resource
{
    protected static ?string $model = Grade::class;

    public static function getNavigationGroup(): ?string
    {
        return 'Master';
    }

    public static function getModelLabel(): string
    {
        return 'Grade';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('PEMILIK', 'ADMIN', 'PENGADAAN') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('commodity_id')->label('Komoditas')->relationship('commodity', 'nama')->required(),
            TextInput::make('kode')->required()->maxLength(5),
            KeyValue::make('ambang_mutu')->label('Ambang mutu (contoh: kadar_air_max = 14)')
                ->keyLabel('Parameter')->valueLabel('Nilai')->nullable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('commodity.nama')->label('Komoditas')->sortable(),
                TextColumn::make('kode')->badge(),
                TextColumn::make('ambang_mutu')->formatStateUsing(fn ($state) => json_encode($state)),
            ])
            ->recordActions([EditAction::make()])
            ->defaultSort('commodity_id');
    }

    public static function getPages(): array
    {
        return ['index' => ManageGrades::route('/')];
    }
}
