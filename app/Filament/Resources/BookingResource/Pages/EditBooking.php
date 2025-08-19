<?php

namespace App\Filament\Resources\BookingResource\Pages;

use App\Filament\Resources\BookingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Notifications\TelegramNotificationService;
use Illuminate\Support\Facades\App;

class EditBooking extends EditRecord
{
    protected static string $resource = BookingResource::class;

    protected function afterSave(): void
    {
        $booking = $this->record;

        if ($booking->wasChanged('status')) {
            $notificationService = App::make(TelegramNotificationService::class);
            $notificationService->sendStatusUpdate($booking);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ViewAction::make(),
        ];
    }
}
