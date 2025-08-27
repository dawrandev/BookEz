<?php

namespace App\Filament\Resources\SocialNetworksResource\Pages;

use App\Filament\Resources\SocialNetworksResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSocialNetworks extends ListRecords
{
    protected static string $resource = SocialNetworksResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
