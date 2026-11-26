<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Contracts\Pages\ManageContracts;
use App\Filament\Support\SafeDelete;
use App\Infrastructure\Services\AuditLogger;
use App\Modules\ProcurementReceiving\Models\Contract;
use App\Modules\ProcurementReceiving\Models\Partner;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ContractResource extends Resource
{
    protected static ?string $model = Contract::class;

    public static function getNavigationGroup(): ?string
    {
        return 'Harga & Kontrak';
    }

    public static function getModelLabel(): string
    {
        return 'Kontrak Musim';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('PEMILIK', 'ADMIN', 'PENGADAAN') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('partner_id')
                ->label('Petani Mitra')
                ->options(fn (?Contract $record): array => Partner::query()
                    ->where('tipe', 'FARMER')
                    ->where(function ($q) use ($record): void {
                        $q->where('status', 'AKTIF');
                        if ($record?->partner_id) {
                            $q->orWhere('id', $record->partner_id);
                        }
                    })
                    ->orderBy('nama')
                    ->pluck('nama', 'id')
                    ->all())
                ->searchable()
                ->preload()
                ->required()
                ->disabled(fn (?Contract $record): bool => $record !== null && $record->status !== 'DRAFT')
                ->dehydrated(true),

            Select::make('standard_package_id')
                ->label('Paket Standar Budidaya')
                ->relationship('package', 'nama')
                ->required()
                ->native(false)
                ->disabled(fn (?Contract $record): bool => $record !== null && $record->status !== 'DRAFT')
                ->dehydrated(true),

            TextInput::make('musim')
                ->label('Musim Tanam')
                ->placeholder('2026/2027')
                ->required()
                ->maxLength(20)
                ->rules([
                    fn (Get $get, ?Contract $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get, $record): void {
                        if ($get('status') !== 'AKTIF' || ! $get('partner_id') || ! $value) {
                            return;
                        }

                        $ada = Contract::query()
                            ->where('partner_id', $get('partner_id'))
                            ->where('musim', $value)
                            ->where('status', 'AKTIF')
                            ->when($record, fn ($q) => $q->whereKeyNot($record->id))
                            ->exists();

                        if ($ada) {
                            $fail('Petani ini sudah memiliki kontrak aktif pada musim tersebut.');
                        }
                    },
                ]),

            DatePicker::make('mulai')
                ->label('Tanggal Mulai')
                ->required(),

            DatePicker::make('selesai')
                ->label('Tanggal Selesai')
                ->required()
                ->afterOrEqual('mulai'),

            TextInput::make('estimasi_kg')
                ->label('Estimasi Volume Panen')
                ->numeric()
                ->suffix('kg')
                ->required()
                ->minValue(1),

            Select::make('status')
                ->label('Status Kontrak')
                ->options(function (?Contract $record): array {
                    if (auth()->user()?->can('approve-contract')) {
                        return ['DRAFT' => 'Draf', 'AKTIF' => 'Aktif', 'SELESAI' => 'Selesai', 'BATAL' => 'Batal'];
                    }

                    if ($record && $record->status === 'AKTIF') {
                        return ['AKTIF' => 'Aktif', 'SELESAI' => 'Selesai', 'BATAL' => 'Batal'];
                    }

                    return ['DRAFT' => 'Draf (Menunggu Persetujuan Pemilik)', 'BATAL' => 'Batal'];
                })
                ->default('DRAFT')
                ->required()
                ->native(false)
                ->helperText('Aktivasi kontrak dari Draf hanya dapat disetujui oleh Pemilik.')
                ->disabled(fn (string $operation): bool => $operation === 'create' && ! auth()->user()?->can('approve-contract'))
                ->dehydrated(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('partner.nama')->label('Petani')->searchable()->sortable(),
                TextColumn::make('musim')->label('Musim')->sortable(),
                TextColumn::make('package.nama')->label('Paket Standar'),
                TextColumn::make('mulai')->label('Mulai')->date('d M Y'),
                TextColumn::make('selesai')->label('Selesai')->date('d M Y'),
                TextColumn::make('estimasi_kg')
                    ->label('Estimasi')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 0, ',', '.').' kg'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'AKTIF' => 'success',
                        'DRAFT' => 'warning',
                        'SELESAI' => 'info',
                        'BATAL' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(['DRAFT' => 'Draf', 'AKTIF' => 'Aktif', 'SELESAI' => 'Selesai', 'BATAL' => 'Batal']),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('aktifkan')
                    ->label('Setujui & Aktifkan')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Contract $record): bool => $record->status === 'DRAFT' && auth()->user()->can('approve-contract'))
                    ->action(function (Contract $record): void {
                        $record->update(['status' => 'AKTIF']);
                        AuditLogger::log('KONTRAK_DISETUJUI', 'contracts', $record->id, ['status' => 'DRAFT'], ['status' => 'AKTIF']);
                    }),
                SafeDelete::make(function (Contract $record): ?string {
                    if ($record->status !== 'DRAFT') {
                        return 'Hanya kontrak berstatus Draf yang dapat dihapus.';
                    }

                    if ($record->harvestPlans()->exists()) {
                        return 'Kontrak sudah memiliki rencana panen.';
                    }

                    return null;
                }),
            ])
            ->defaultSort('mulai', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ManageContracts::route('/')];
    }
}
