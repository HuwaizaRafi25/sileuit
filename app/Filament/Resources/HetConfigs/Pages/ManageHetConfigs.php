<?php

namespace App\Filament\Resources\HetConfigs\Pages;

use App\Filament\Resources\HetConfigResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageHetConfigs extends ManageRecords
{
    protected static string $resource = HetConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Tambah HET'),
        ];
    }
}
