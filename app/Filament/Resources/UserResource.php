<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Modules\IdentityAccess\Models\Location;
use App\Modules\IdentityAccess\Models\User;
use App\Modules\ProcurementReceiving\Models\Partner;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): ?string
    {
        return 'Master';
    }

    public static function getModelLabel(): string
    {
        return 'Pengguna';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('PEMILIK', 'ADMIN') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nama')->required()->maxLength(100),
            TextInput::make('email')->email()->unique(ignoreRecord: true)->nullable(),
            TextInput::make('hp')->label('Nomor HP (+62...)')->maxLength(20)->nullable(),
            TextInput::make('password')->label('Kata sandi')->password()->minLength(8)
                ->required(fn (string $operation): bool => $operation === 'create')
                ->dehydrated(fn (?string $state): bool => filled($state)),
            TextInput::make('pin_hash')->label('PIN 6 digit (khusus petani)')->password()->numeric()->minLength(6)->maxLength(6)
                ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Hash::make($state) : null)
                ->dehydrated(fn (?string $state): bool => filled($state)),
            Select::make('status')->options(['AKTIF' => 'Aktif', 'NONAKTIF' => 'Nonaktif'])->default('AKTIF')->required(),
            CheckboxList::make('roles')->label('Peran')->relationship('roles', 'nama')->columns(2),
            CheckboxList::make('locationScopes')->label('Cakupan lokasi')->relationship('locationScopes', 'nama')->columns(2),
            Select::make('active_location_id')->label('Lokasi aktif')->options(Location::pluck('nama', 'id'))->nullable(),
            Select::make('partner_id')->label('Terhubung ke mitra')->options(Partner::pluck('nama', 'id'))->searchable()->nullable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('roles.nama')->badge(),
                TextColumn::make('locationScopes.kode')->badge(),
                TextColumn::make('status')->badge(),
            ])
            ->recordActions([EditAction::make()])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return ['index' => ManageUsers::route('/')];
    }
}
