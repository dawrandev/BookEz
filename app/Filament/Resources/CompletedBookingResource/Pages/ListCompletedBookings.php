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
            Actions\Action::make('export')
                ->label('Экспорт')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(function (): StreamedResponse {
                    $bookings = \App\Models\Booking::where('user_id', auth()->id())
                        ->where('status', 'completed')
                        ->with(['client', 'service', 'schedule'])
                        ->latest('completed_at')
                        ->get();

                    $headers = [
                        'Content-Type'        => 'text/csv',
                        'Content-Disposition' => 'attachment; filename="completed_bookings.csv"',
                    ];

                    return response()->streamDownload(function () use ($bookings) {
                        $out = fopen('php://output', 'w');
                        fputcsv($out, ['ID', 'Client', 'Service', 'Date', 'Time', 'Price', 'Status']);
                        foreach ($bookings as $b) {
                            fputcsv($out, [
                                $b->id,
                                optional($b->client)->full_name,
                                optional($b->service)->name,
                                optional($b->schedule?->work_date)->format('d.m.Y'),
                                sprintf('%s - %s', substr($b->start_time, 0, 5), substr($b->end_time, 0, 5)),
                                $b->service?->price,
                                $b->status,
                            ]);
                        }
                        fclose($out);
                    }, 'completed_bookings.csv', $headers);
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // 
        ];
    }
}
