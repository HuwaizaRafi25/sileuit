<?php

namespace App\Filament\Resources\Grades\Pages;

use App\Filament\Resources\GradeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageGrades extends ManageRecords
{
    protected static string $resource = GradeResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Tambah Grade')];
    }
}
