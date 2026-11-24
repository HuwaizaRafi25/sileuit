<?php

namespace App\Filament\Resources\Commodities\Pages;

use App\Filament\Resources\CommodityResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCommodities extends ManageRecords
{
    protected static string $resource = CommodityResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Tambah Komoditas')];
    }
}
