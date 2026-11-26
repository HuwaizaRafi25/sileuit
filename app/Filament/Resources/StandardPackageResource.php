<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StandardPackages\Pages\ManageStandardPackages;
use App\Filament\Support\SafeDelete;
use App\Modules\ProcurementReceiving\Models\Contract;
use App\Modules\ProcurementReceiving\Models\StandardPackage;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
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
            TextInput::make('nama')
                ->label('Nama Paket Budidaya')
                ->required()
                ->maxLength(80),

            TextInput::make('jenis_pupuk')
                ->label('Anjuran Pemupukan')
                ->maxLength(80),

            TextInput::make('jendela_tanam')
                ->label('Jendela Tanam')
                ->placeholder('Nov–Mar')
                ->maxLength(80),

            Section::make('Target Parameter Mutu Panen')
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextInput::make('parameter_mutu.kadar_air_max')
                        ->label('Target Maks. Kadar Air')
                        ->numeric()
                        ->suffix('%')
                        ->minValue(1)
                        ->maxValue(100)
                        ->nullable(),

                    TextInput::make('parameter_mutu.hampa_max')
                        ->label('Target Maks. Butir Hampa')
                        ->numeric()
                        ->suffix('%')
                        ->minValue(0)
                        ->maxValue(100)
                        ->nullable(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama')->label('Nama Paket')->searchable()->sortable(),
                TextColumn::make('jenis_pupuk')->label('Jenis Pupuk')->placeholder('—'),
                TextColumn::make('jendela_tanam')->label('Jendela Tanam')->placeholder('—'),
                TextColumn::make('parameter_mutu')
                    ->label('Target Mutu')
                    ->formatStateUsing(function (mixed $state): string {
                        if (! is_array($state) || $state === []) {
                            return '—';
                        }

                        $parts = [];
                        if (isset($state['kadar_air_max'])) {
                            $parts[] = 'KA ≤ '.$state['kadar_air_max'].'%';
                        }
                        if (isset($state['hampa_max'])) {
                            $parts[] = 'Hampa ≤ '.$state['hampa_max'].'%';
                        }

                        return $parts !== [] ? implode(' • ', $parts) : json_encode($state);
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                SafeDelete::make(fn (StandardPackage $record): ?string => Contract::where('standard_package_id', $record->id)->exists()
                    ? 'Paket standar ini sedang dipakai pada kontrak petani.'
                    : null),
            ])
            ->defaultSort('nama');
    }

    public static function getPages(): array
    {
        return ['index' => ManageStandardPackages::route('/')];
    }
}
