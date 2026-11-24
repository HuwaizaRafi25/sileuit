<?php

namespace App\Filament\Resources\UomConversions\Pages;

use App\Filament\Resources\UomConversionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageUomConversions extends ManageRecords
{
    protected static string $resource = UomConversionResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Tambah Konversi')];
    }
}
