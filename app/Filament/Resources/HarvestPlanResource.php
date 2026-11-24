<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HarvestPlans\Pages\ManageHarvestPlans;
use App\Modules\ProcurementReceiving\Models\Contract;
use App\Modules\ProcurementReceiving\Models\HarvestPlan;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
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
            Select::make('contract_id')->label('Kontrak')
                ->options(Contract::with('partner')->get()->mapWithKeys(fn (Contract $c) => [$c->id => $c->partner->nama.' — '.$c->musim]))
                ->searchable()->required(),
            DatePicker::make('tgl_perkiraan')->label('Perkiraan tanggal panen')->required(),
            TextInput::make('kg_perkiraan')->label('Perkiraan volume (kg)')->numeric()->required()->minValue(1),
            Select::make('status')->options(['RENCANA' => 'Rencana', 'TERPENUHI' => 'Terpenuhi', 'BATAL' => 'Batal'])->default('RENCANA')->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('contract.partner.nama')->label('Petani')->searchable(),
                TextColumn::make('tgl_perkiraan')->date('d M Y')->sortable(),
                TextColumn::make('kg_perkiraan')->label('Perkiraan (kg)'),
                TextColumn::make('status')->badge(),
            ])
            ->recordActions([EditAction::make()])
            ->defaultSort('tgl_perkiraan');
    }

    public static function getPages(): array
    {
        return ['index' => ManageHarvestPlans::route('/')];
    }
}
