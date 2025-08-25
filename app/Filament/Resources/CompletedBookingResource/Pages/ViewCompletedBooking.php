<?php

namespace App\Filament\Resources\CompletedBookingResource\Pages;

use App\Filament\Resources\CompletedBookingResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCompletedBooking extends ViewRecord
{
    protected static string $resource = CompletedBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('download_receipt')
                ->label('Скачать чек')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(function () {
                    // PDF chek yaratish logikasi
                    return response()->download(
                        $this->generateReceipt($this->record)
                    );
                })
                ->visible(fn() => $this->record->service->price > 0),
        ];
    }

    private function generateReceipt($booking): string
    {
        // PDF yaratish logikasi (keyinroq implement qilamiz)
        return storage_path("app/receipts/booking_{$booking->id}.pdf");
    }
}
