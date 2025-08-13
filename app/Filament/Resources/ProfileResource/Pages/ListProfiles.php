<?php

namespace App\Filament\Resources\ProfileResource\Pages;

use App\Filament\Resources\ProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProfiles extends ListRecords
{
    protected static string $resource = ProfileResource::class;

    public function mount(): void
    {
        $this->redirect(ProfileResource::getUrl('edit', ['record' => auth()->id()]));
    }
}
