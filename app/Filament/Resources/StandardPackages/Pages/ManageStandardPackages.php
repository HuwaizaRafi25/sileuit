<?php

namespace App\Filament\Resources\StandardPackages\Pages;

use App\Filament\Resources\StandardPackageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageStandardPackages extends ManageRecords
{
    protected static string $resource = StandardPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Tambah Paket')];
    }
}
