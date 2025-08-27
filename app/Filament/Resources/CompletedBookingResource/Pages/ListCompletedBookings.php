<?php

namespace App\Filament\Resources\CompletedBookingResource\Pages;

use App\Filament\Resources\CompletedBookingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Colors\Color;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListCompletedBookings extends ListRecords
{
    protected static string $resource = CompletedBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // 
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // 
        ];
    }
}
