<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Partners\Pages\ManagePartners;
use App\Modules\ProcurementReceiving\Models\Partner;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PartnerResource extends Resource
{
    protected static ?string $model = Partner::class;

    protected static ?string $recordTitleAttribute = 'nama';

    public static function getNavigationGroup(): ?string
    {
        return 'Master';
    }

    public static function getModelLabel(): string
    {
        return 'Mitra';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('PEMILIK', 'ADMIN', 'PENGADAAN') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('tipe')->options(['FARMER' => 'Petani', 'RETAIL' => 'Retail B2B', 'CUSTOMER' => 'Pelanggan'])->required(),
            TextInput::make('nama')->required()->maxLength(100),
            TextInput::make('hp')->label('Nomor HP (+62...)')->required()->maxLength(20),
            TextInput::make('kelompok')->label('Kelompok tani')->maxLength(80),
            TextInput::make('batas_kredit')->label('Batas kredit (Rp)')->numeric()->default(0)->minValue(0),
            Select::make('termin_hari')->label('Termin (hari)')->options([7 => '7', 14 => '14', 30 => '30'])->nullable(),
            TextInput::make('no_rekening')->label('No. rekening (opsional)')->maxLength(40),
            Select::make('status')->options(['AKTIF' => 'Aktif', 'NONAKTIF' => 'Nonaktif'])->default('AKTIF')->required(),
            Textarea::make('alamat')->rows(2),
            Repeater::make('farmPlots')->label('Lahan (khusus petani)')->relationship()
                ->schema([
                    TextInput::make('nama_desa')->required()->maxLength(80),
                    TextInput::make('luas_ha')->label('Luas (ha)')->numeric()->nullable(),
                ])->defaultItems(0)->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama')->searchable()->sortable(),
                TextColumn::make('tipe')->badge(),
                TextColumn::make('hp'),
                TextColumn::make('kelompok'),
                TextColumn::make('batas_kredit')->label('Batas kredit')
                    ->formatStateUsing(fn ($state) => 'Rp'.number_format((int) $state, 0, ',', '.')),
                TextColumn::make('status')->badge(),
            ])
            ->recordActions([EditAction::make()])
            ->defaultSort('nama');
    }

    public static function getPages(): array
    {
        return ['index' => ManagePartners::route('/')];
    }
}
