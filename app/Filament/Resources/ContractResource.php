<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Contracts\Pages\ManageContracts;
use App\Infrastructure\Services\AuditLogger;
use App\Modules\ProcurementReceiving\Models\Contract;
use App\Modules\ProcurementReceiving\Models\Partner;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
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
            Select::make('partner_id')->label('Petani')
                ->options(Partner::where('tipe', 'FARMER')->pluck('nama', 'id'))->searchable()->required(),
            Select::make('standard_package_id')->label('Paket standar')->relationship('package', 'nama')->required(),
            TextInput::make('musim')->label('Musim (mis. 2026/2027)')->required()->maxLength(20),
            DatePicker::make('mulai')->required(),
            DatePicker::make('selesai')->required()->afterOrEqual('mulai'),
            TextInput::make('estimasi_kg')->label('Estimasi volume (kg)')->numeric()->required()->minValue(1),
            Select::make('status')->options(['DRAFT' => 'Draf', 'AKTIF' => 'Aktif', 'SELESAI' => 'Selesai', 'BATAL' => 'Batal'])->default('DRAFT')->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('partner.nama')->label('Petani')->searchable(),
                TextColumn::make('musim')->sortable(),
                TextColumn::make('package.nama')->label('Paket'),
                TextColumn::make('estimasi_kg')->label('Estimasi (kg)'),
                TextColumn::make('status')->badge(),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('aktifkan')->label('Setujui & Aktifkan')->color('success')->requiresConfirmation()
                    ->visible(fn (Contract $record) => $record->status === 'DRAFT' && auth()->user()->can('approve-contract'))
                    ->action(function (Contract $record) {
                        $record->update(['status' => 'AKTIF']);
                        AuditLogger::log('KONTRAK_DISETUJUI', 'contracts', $record->id, ['status' => 'DRAFT'], ['status' => 'AKTIF']);
                    }),
            ])
            ->defaultSort('mulai', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ManageContracts::route('/')];
    }
}
