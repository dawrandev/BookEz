<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestBookings extends BaseWidget
{
    protected static ?string $heading = 'So\'nggi Bookinglar';
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Booking::where('user_id', auth()->id())
                    ->with(['client', 'service', 'schedule'])
                    ->latest()
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('client.full_name')
                    ->label('Mijoz')
                    ->searchable()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('service.name')
                    ->label('Xizmat')
                    ->limit(30),

                Tables\Columns\TextColumn::make('schedule.work_date')
                    ->label('Sana')
                    ->date('M j')
                    ->badge()
                    ->color(
                        fn($record) =>
                        $record->schedule->work_date->isToday() ? 'success' : ($record->schedule->work_date->isPast() ? 'gray' : 'warning')
                    ),

                Tables\Columns\TextColumn::make('time_range')
                    ->label('Vaqt')
                    ->getStateUsing(function ($record) {
                        return Carbon::parse($record->start_time)->format('H:i') . '-' .
                            Carbon::parse($record->end_time)->format('H:i');
                    })
                    ->badge()
                    ->color('gray'),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'confirmed',
                        'danger' => 'canceled',
                        'primary' => 'completed',
                    ])
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            'pending' => 'Kutilmoqda',
                            'confirmed' => 'Tasdiqlangan',
                            'canceled' => 'Bekor qilingan',
                            'completed' => 'Tugallangan',
                            default => $state,
                        };
                    }),

                Tables\Columns\TextColumn::make('service.price')
                    ->label('Narx')
                    ->formatStateUsing(fn($state) => number_format($state, 0))
                    ->weight('bold')
                    ->color(fn($record) => $record->status === 'completed' ? 'success' : 'gray'),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->url(fn(Booking $record): string => route('filament.admin.resources.bookings.view', $record))
                    ->icon('heroicon-m-eye'),
            ]);
    }
}
