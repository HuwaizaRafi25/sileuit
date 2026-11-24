<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PriceListItems\Pages\ManagePriceListItems;
use App\Infrastructure\Services\AuditLogger;
use App\Modules\ProcurementReceiving\Models\PriceListItem;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

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
            Select::make('commodity_id')->label('Komoditas')->relationship('commodity', 'nama')->required(),
            Select::make('grade_id')->label('Grade')->relationship('grade', 'kode')->required(),
            TextInput::make('harga_per_kg')->label('Harga per kg (Rp)')->numeric()->required()->minValue(1),
            DatePicker::make('berlaku_dari')->required(),
            DatePicker::make('berlaku_sampai')->nullable()->afterOrEqual('berlaku_dari'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('commodity.nama')->label('Komoditas')->sortable(),
                TextColumn::make('grade.kode')->label('Grade')->badge(),
                TextColumn::make('harga_per_kg')->label('Harga/kg')
                    ->formatStateUsing(fn ($state) => 'Rp'.number_format((int) $state, 0, ',', '.')),
                TextColumn::make('berlaku_dari')->date('d M Y')->sortable(),
                TextColumn::make('berlaku_sampai')->date('d M Y')->placeholder('tanpa akhir'),
                TextColumn::make('approver.name')->label('Disetujui oleh')->placeholder('Menunggu persetujuan'),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn (PriceListItem $record) => $record->disetujui_oleh === null && auth()->user()->hasRole('PENGADAAN')),
                Action::make('setujui')
                    ->label('Setujui')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (PriceListItem $record) => $record->disetujui_oleh === null && auth()->user()->can('approve-price'))
                    ->action(function (PriceListItem $record) {
                        $record->update(['disetujui_oleh' => auth()->id(), 'disetujui_pada' => now()]);
                        AuditLogger::log('HARGA_DISETUJUI', 'price_list_items', $record->id, null, ['harga_per_kg' => $record->harga_per_kg]);
                    }),
            ])
            ->defaultSort('berlaku_dari', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ManagePriceListItems::route('/')];
    }
}
