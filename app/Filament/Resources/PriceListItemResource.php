<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PriceListItems\Pages\ManagePriceListItems;
use App\Filament\Support\SafeDelete;
use App\Infrastructure\Services\AuditLogger;
use App\Modules\ProcurementReceiving\Models\Grade;
use App\Modules\ProcurementReceiving\Models\PriceListItem;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;

class PriceListItemResource extends Resource
{
    protected static ?string $model = PriceListItem::class;

    public static function getNavigationGroup(): ?string
    {
        return 'Harga & Kontrak';
    }

    public static function getModelLabel(): string
    {
        return 'Harga Beli';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('PEMILIK', 'ADMIN', 'PENGADAAN', 'KEUANGAN') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('commodity_id')
                ->label('Komoditas')
                ->relationship('commodity', 'nama')
                ->required()
                ->native(false)
                ->live()
                ->afterStateUpdated(fn (Set $set) => $set('grade_id', null)),

            Select::make('grade_id')
                ->label('Grade Komoditas')
                ->helperText('Hanya menampilkan grade yang sesuai dengan komoditas terpilih.')
                ->options(function (Get $get): array {
                    $commodityId = $get('commodity_id');
                    if (! filled($commodityId)) {
                        return [];
                    }

                    return Grade::query()
                        ->where('commodity_id', (int) $commodityId)
                        ->orderBy('kode')
                        ->get()
                        ->mapWithKeys(function (Grade $record): array {
                            $ka = isset($record->ambang_mutu['kadar_air_max'])
                                ? " (KA ≤ {$record->ambang_mutu['kadar_air_max']}%)"
                                : '';

                            return [$record->id => "Grade {$record->kode}{$ka}"];
                        })
                        ->all();
                })
                ->disabled(fn (Get $get): bool => ! filled($get('commodity_id')))
                ->searchable()
                ->preload()
                ->required()
                ->rules([
                    fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                        $commodityId = $get('commodity_id');
                        if (! $commodityId || ! $value) {
                            return;
                        }

                        $grade = Grade::find((int) $value);
                        if (! $grade || $grade->commodity_id !== (int) $commodityId) {
                            $fail('Grade yang dipilih bukan milik komoditas tersebut.');
                        }
                    },
                ])
                ->createOptionForm([
                    Select::make('commodity_id')
                        ->label('Komoditas')
                        ->relationship('commodity', 'nama')
                        ->required()
                        ->native(false),
                    TextInput::make('kode')
                        ->label('Kode Grade')
                        ->placeholder('A / B / C')
                        ->required()
                        ->maxLength(5)
                        ->dehydrateStateUsing(fn (?string $state): string => strtoupper(trim((string) $state)))
                        ->unique(
                            table: Grade::class,
                            column: 'kode',
                            modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule->where('commodity_id', $get('commodity_id')),
                        ),
                    TextInput::make('ambang_mutu.kadar_air_max')
                        ->label('Maks. Kadar Air (%)')
                        ->numeric()
                        ->suffix('%')
                        ->minValue(1)
                        ->maxValue(100)
                        ->required(),
                ])
                ->createOptionUsing(fn (array $data): int => Grade::create($data)->getKey()),

            TextInput::make('harga_per_kg')
                ->label('Harga Beli per kg')
                ->numeric()
                ->prefix('Rp')
                ->required()
                ->minValue(1),

            DatePicker::make('berlaku_dari')
                ->label('Berlaku Dari')
                ->default(today())
                ->required()
                ->rules([
                    fn (Get $get, ?PriceListItem $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get, $record): void {
                        $commodityId = $get('commodity_id');
                        $gradeId = $get('grade_id');
                        if (! $commodityId || ! $gradeId || ! $value) {
                            return;
                        }

                        $sampai = $get('berlaku_sampai') ?: '9999-12-31';
                        $overlap = PriceListItem::query()
                            ->where('commodity_id', (int) $commodityId)
                            ->where('grade_id', (int) $gradeId)
                            ->whereDate('berlaku_dari', '<=', $sampai)
                            ->where(fn (Builder $w) => $w->whereNull('berlaku_sampai')->orWhereDate('berlaku_sampai', '>=', $value))
                            ->when($record, fn ($q) => $q->whereKeyNot($record->id))
                            ->exists();

                        if ($overlap) {
                            $fail('Periode harga tumpang tindih dengan harga lain untuk komoditas dan grade ini.');
                        }
                    },
                ]),

            DatePicker::make('berlaku_sampai')
                ->label('Berlaku Sampai (opsional)')
                ->nullable()
                ->afterOrEqual('berlaku_dari'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('commodity.nama')->label('Komoditas')->sortable(),
                TextColumn::make('grade.kode')->label('Grade')->badge(),
                TextColumn::make('harga_per_kg')
                    ->label('Harga/kg')
                    ->formatStateUsing(fn ($state): string => 'Rp'.number_format((int) $state, 0, ',', '.')),
                TextColumn::make('berlaku_dari')->label('Berlaku Dari')->date('d M Y')->sortable(),
                TextColumn::make('berlaku_sampai')->label('Berlaku Sampai')->date('d M Y')->placeholder('Tanpa akhir'),
                TextColumn::make('approver.name')
                    ->label('Status Persetujuan')
                    ->placeholder('Menunggu Pemilik')
                    ->badge()
                    ->color(fn (?string $state): string => filled($state) ? 'success' : 'warning'),
            ])
            ->filters([
                SelectFilter::make('commodity_id')
                    ->label('Komoditas')
                    ->relationship('commodity', 'nama'),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn (PriceListItem $record): bool => $record->disetujui_oleh === null && auth()->user()->can('create-price')),
                Action::make('setujui')
                    ->label('Setujui')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (PriceListItem $record): bool => $record->disetujui_oleh === null && auth()->user()->can('approve-price'))
                    ->action(function (PriceListItem $record): void {
                        $record->update(['disetujui_oleh' => auth()->id(), 'disetujui_pada' => now()]);
                        AuditLogger::log('HARGA_DISETUJUI', 'price_list_items', $record->id, null, ['harga_per_kg' => $record->harga_per_kg]);
                    }),
                SafeDelete::make(
                    afterDelete: function (PriceListItem $record): void {
                        AuditLogger::log('HARGA_DIHAPUS', 'price_list_items', $record->id, ['harga_per_kg' => $record->harga_per_kg], null);
                    },
                )->visible(fn (): bool => auth()->user()->can('create-price')),
            ])
            ->defaultSort('berlaku_dari', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ManagePriceListItems::route('/')];
    }
}
