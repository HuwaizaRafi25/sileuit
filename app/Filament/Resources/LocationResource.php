<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Locations\Pages\ManageLocations;
use App\Filament\Support\SafeDelete;
use App\Modules\IdentityAccess\Models\Location;
use App\Modules\IdentityAccess\Models\User;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LocationResource extends Resource
{
    protected static ?string $model = Location::class;

    protected static ?string $recordTitleAttribute = 'nama';

    public static function getNavigationGroup(): ?string
    {
        return 'Master';
    }

    public static function getModelLabel(): string
    {
        return 'Lokasi';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('PEMILIK', 'ADMIN') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('kode')
                ->label('Kode Lokasi')
                ->placeholder('G1 / C1')
                ->required()
                ->maxLength(10)
                ->dehydrateStateUsing(fn (?string $state): string => strtoupper(trim((string) $state)))
                ->unique(ignoreRecord: true),

            TextInput::make('nama')
                ->label('Nama Lokasi')
                ->placeholder('Gudang 1 / Cabang 1')
                ->required()
                ->maxLength(80),

            Select::make('tipe')
                ->label('Tipe Lokasi')
                ->options([
                    'GUDANG' => 'Gudang (Penerimaan, Pengeringan & Stok Induk)',
                    'CABANG' => 'Cabang (Penjualan Kasir / POS)',
                ])
                ->required()
                ->native(false),

            TextInput::make('alamat')
                ->label('Alamat Lengkap')
                ->maxLength(255),

            Toggle::make('aktif')
                ->label('Aktif')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kode')->label('Kode')->searchable()->sortable(),
                TextColumn::make('nama')->label('Nama Lokasi')->searchable(),
                TextColumn::make('tipe')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'GUDANG' ? 'primary' : 'info'),
                TextColumn::make('alamat')->label('Alamat')->placeholder('—')->limit(40),
                IconColumn::make('aktif')->label('Aktif')->boolean(),
            ])
            ->filters([
                SelectFilter::make('tipe')
                    ->options(['GUDANG' => 'Gudang', 'CABANG' => 'Cabang']),
            ])
            ->recordActions([
                EditAction::make(),
                SafeDelete::make(function (Location $record): ?string {
                    $hasUsers = User::query()
                        ->where('active_location_id', $record->id)
                        ->orWhereHas('locationScopes', fn ($q) => $q->where('locations.id', $record->id))
                        ->exists();

                    if ($hasUsers) {
                        return 'Lokasi ini masih menjadi lokasi tugas pengguna. Nonaktifkan lokasi ini daripada menghapusnya.';
                    }

                    return null;
                }),
            ])
            ->defaultSort('kode');
    }

    public static function getPages(): array
    {
        return ['index' => ManageLocations::route('/')];
    }
}
