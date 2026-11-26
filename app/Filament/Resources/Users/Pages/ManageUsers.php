<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\UserResource;
use App\Modules\IdentityAccess\Models\User;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageUsers extends ManageRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Pengguna')
                ->using(fn (array $data): User => UserResource::saveUserRecord(null, $data)),
        ];
    }
}
