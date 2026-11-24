<?php

namespace App\Filament\Resources\HarvestPlans\Pages;

use App\Filament\Resources\HarvestPlanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageHarvestPlans extends ManageRecords
{
    protected static string $resource = HarvestPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Tambah Rencana Panen')];
    }
}
