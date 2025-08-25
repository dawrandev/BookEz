<?php

namespace App\Filament\Resources\CompletedBookingResource\Pages;

use App\Filament\Resources\CompletedBookingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCompletedBooking extends EditRecord
{
    protected static string $resource = CompletedBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
