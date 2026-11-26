<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Grades\Pages\ManageGrades;
use App\Filament\Support\SafeDelete;
use App\Modules\ProcurementReceiving\Models\Grade;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;

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
            Select::make('commodity_id')
                ->label('Komoditas')
                ->relationship('commodity', 'nama')
                ->required()
                ->native(false)
                ->live(),

            TextInput::make('kode')
                ->label('Kode Grade')
                ->placeholder('A / B / C')
                ->required()
                ->maxLength(5)
                ->dehydrateStateUsing(fn (?string $state): string => strtoupper(trim((string) $state)))
                ->unique(
                    table: Grade::class,
                    column: 'kode',
                    ignoreRecord: true,
                    modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule->where('commodity_id', $get('commodity_id')),
                )
                ->validationMessages([
                    'unique' => 'Kode grade ini sudah terdaftar pada komoditas yang sama.',
                ]),

            Section::make('Ambang Batas Mutu')
                ->description('Digunakan sistem saat uji mutu penerimaan untuk menentukan Grade secara otomatis.')
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextInput::make('ambang_mutu.kadar_air_max')
                        ->label('Maks. Kadar Air')
                        ->numeric()
                        ->suffix('%')
                        ->minValue(1)
                        ->maxValue(100)
                        ->required(),

                    TextInput::make('ambang_mutu.butir_hampa_max')
                        ->label('Maks. Butir Hampa (opsional)')
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
                TextColumn::make('commodity.nama')->label('Komoditas')->sortable(),
                TextColumn::make('kode')->label('Grade')->badge(),
                TextColumn::make('ambang_mutu')
                    ->label('Ambang Mutu')
                    ->formatStateUsing(function (mixed $state): string {
                        if (! is_array($state) || $state === []) {
                            return '—';
                        }

                        $parts = [];
                        if (isset($state['kadar_air_max'])) {
                            $parts[] = 'Kadar Air ≤ '.$state['kadar_air_max'].'%';
                        }
                        if (isset($state['butir_hampa_max'])) {
                            $parts[] = 'Hampa ≤ '.$state['butir_hampa_max'].'%';
                        }

                        return $parts !== [] ? implode(' • ', $parts) : json_encode($state);
                    }),
            ])
            ->filters([
                SelectFilter::make('commodity_id')
                    ->label('Komoditas')
                    ->relationship('commodity', 'nama'),
            ])
            ->recordActions([
                EditAction::make(),
                SafeDelete::make(),
            ])
            ->defaultSort('commodity_id');
    }

    public static function getPages(): array
    {
        return ['index' => ManageGrades::route('/')];
    }
}
