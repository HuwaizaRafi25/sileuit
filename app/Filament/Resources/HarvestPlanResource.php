<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HarvestPlans\Pages\ManageHarvestPlans;
use App\Filament\Support\SafeDelete;
use App\Modules\ProcurementReceiving\Models\Contract;
use App\Modules\ProcurementReceiving\Models\HarvestPlan;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class HarvestPlanResource extends Resource
{
    protected static ?string $model = HarvestPlan::class;

    public static function getNavigationGroup(): ?string
    {
        return 'Harga & Kontrak';
    }

    public static function getModelLabel(): string
    {
        return 'Rencana Panen';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('PEMILIK', 'ADMIN', 'PENGADAAN') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('contract_id')
                ->label('Kontrak Aktif Petani')
                ->helperText('Hanya kontrak berstatus Aktif yang siap dijadwalkan panennya.')
                ->options(fn (?HarvestPlan $record): array => Contract::with('partner')
                    ->where(function ($q) use ($record): void {
                        $q->where('status', 'AKTIF');
                        if ($record?->contract_id) {
                            $q->orWhere('id', $record->contract_id);
                        }
                    })
                    ->orderByDesc('id')
                    ->get()
                    ->mapWithKeys(fn (Contract $c): array => [
                        $c->id => "{$c->partner->nama} — Musim {$c->musim} ({$c->status})",
                    ])
                    ->all())
                ->searchable()
                ->preload()
                ->required(),

            DatePicker::make('tgl_perkiraan')
                ->label('Perkiraan Tanggal Panen')
                ->required(),

            TextInput::make('kg_perkiraan')
                ->label('Perkiraan Volume')
                ->numeric()
                ->suffix('kg')
                ->required()
                ->minValue(1),

            Select::make('status')
                ->label('Status Rencana')
                ->options(['RENCANA' => 'Rencana', 'TERPENUHI' => 'Terpenuhi', 'BATAL' => 'Batal'])
                ->default('RENCANA')
                ->required()
                ->native(false)
                ->disabled(fn (string $operation, ?HarvestPlan $record): bool => $operation === 'create' || $record?->status === 'TERPENUHI')
                ->dehydrated(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('contract.partner.nama')->label('Petani')->searchable()->sortable(),
                TextColumn::make('contract.musim')->label('Musim'),
                TextColumn::make('tgl_perkiraan')->label('Perkiraan Panen')->date('d M Y')->sortable(),
                TextColumn::make('kg_perkiraan')
                    ->label('Perkiraan Volume')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 0, ',', '.').' kg'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'RENCANA' => 'info',
                        'TERPENUHI' => 'success',
                        'BATAL' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(['RENCANA' => 'Rencana', 'TERPENUHI' => 'Terpenuhi', 'BATAL' => 'Batal']),
            ])
            ->recordActions([
                EditAction::make(),
                SafeDelete::make(fn (HarvestPlan $record): ?string => $record->status === 'TERPENUHI'
                    ? 'Rencana panen yang sudah terpenuhi tidak dapat dihapus.'
                    : null),
            ])
            ->defaultSort('tgl_perkiraan');
    }

    public static function getPages(): array
    {
        return ['index' => ManageHarvestPlans::route('/')];
    }
}
