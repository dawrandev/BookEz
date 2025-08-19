<?php

namespace App\Filament\Resources\BookingResource\Pages;

use App\Filament\Resources\BookingResource;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewBooking extends ViewRecord
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('confirm')
                ->label('Подтвердить')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->action(fn() => $this->record->update(['status' => 'confirmed']))
                ->visible(fn() => $this->record->status === 'pending')
                ->requiresConfirmation(),
            Actions\Action::make('cancel')
                ->label('Отменить')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->action(fn() => $this->record->update(['status' => 'canceled']))
                ->visible(fn() => in_array($this->record->status, ['pending', 'confirmed']))
                ->requiresConfirmation(),
            Actions\DeleteAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Информация о бронировании')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('client.full_name')
                                    ->label('Клиент')
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                    ->weight('bold'),

                                Infolists\Components\TextEntry::make('user.name')
                                    ->label('Специалист')
                                    ->badge()
                                    ->color('primary'),
                            ]),

                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('service.name')
                                    ->label('Услуга')
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large),

                                Infolists\Components\TextEntry::make('service.price')
                                    ->label('Стоимость')
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                    ->weight('bold')
                                    ->color('success')
                                    ->getStateUsing(function ($record) {
                                        return number_format($record->service->price, 0, '.', ' ');
                                    }),

                            ]),

                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('schedule.work_date')
                                    ->label('Дата')
                                    ->date('d.m.Y (l)')
                                    ->badge()
                                    ->color('warning'),

                                Infolists\Components\TextEntry::make('time_range')
                                    ->label('Время')
                                    ->getStateUsing(function ($record) {
                                        $start = Carbon::parse($record->start_time)->format('H:i');
                                        $end = Carbon::parse($record->end_time)->format('H:i');
                                        return $start . ' - ' . $end;
                                    })
                                    ->badge()
                                    ->color('info'),

                                Infolists\Components\TextEntry::make('service.duration_minutes')
                                    ->label('Длительность')
                                    ->getStateUsing(function ($record) {
                                        $minutes = $record->service->duration_minutes;
                                        $hours = intdiv($minutes, 60);
                                        $mins = $minutes % 60;
                                        return $hours > 0 ? "{$hours} ч {$mins} мин" : "{$mins} мин";
                                    })
                                    ->badge(),
                            ]),

                        Infolists\Components\TextEntry::make('status')
                            ->label('Статус')
                            ->badge()
                            ->color(fn($record) => match ($record->status) {
                                'pending' => 'warning',
                                'confirmed' => 'success',
                                'canceled' => 'danger',
                                'completed' => 'gray',
                            })
                            ->formatStateUsing(fn($state) => match ($state) {
                                'pending' => 'В ожидании',
                                'confirmed' => 'Подтверждено',
                                'canceled' => 'Отменено',
                                'completed' => 'Завершено',
                                default => $state,
                            }),
                    ]),

                Infolists\Components\Section::make('Контактная информация')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('client.phone')
                                    ->label('Телефон')
                                    ->placeholder('Не указан')
                                    ->url(fn($record) => $record->client->phone ? 'tel:' . $record->client->phone : null),

                                Infolists\Components\TextEntry::make('client.username')
                                    ->label('Имя пользователя')
                                    ->placeholder('Не указан')
                                    ->url(fn($record) => $record->client->username ? 'email:' . $record->client->username : null),
                            ]),
                    ])
                    ->collapsible()

            ]);
    }
}
