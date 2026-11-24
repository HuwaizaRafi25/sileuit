<?php

namespace App\Filament\Resources\PriceListItems\Pages;

use App\Filament\Resources\PriceListItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePriceListItems extends ManageRecords
{
    protected static string $resource = PriceListItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Usulkan Harga')
                ->visible(fn () => auth()->user()->can('create-price')),
        ];
    }
}
