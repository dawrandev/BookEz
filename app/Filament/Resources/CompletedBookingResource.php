<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompletedBookingResource\Pages;
use App\Models\Booking;
use App\Models\Client;
use App\Models\Service;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class CompletedBookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static ?string $navigationIcon = 'heroicon-o-check-circle';

    protected static ?string $navigationLabel = 'История услуг';

    protected static ?string $modelLabel = 'Завершенная услуга';

    protected static ?string $pluralModelLabel = 'История услуг';

    protected static ?int $navigationSort = 2;


    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', auth()->id())
            ->where('status', 'completed')
            ->with(['user', 'client', 'service', 'schedule'])
            ->latest('completed_at');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Информация об услуге')
                ->schema([
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\TextInput::make('client.full_name')
                                ->label('Клиент')
                                ->disabled(),

                            Forms\Components\TextInput::make('service.name')
                                ->label('Услуга')
                                ->disabled(),

                            Forms\Components\TextInput::make('schedule.work_date')
                                ->label('Дата')
                                ->disabled(),

                            Forms\Components\TextInput::make('time_range')
                                ->label('Время')
                                ->formatStateUsing(function ($record) {
                                    return $record->start_time . ' - ' . $record->end_time;
                                })
                                ->disabled(),

                            Forms\Components\TextInput::make('service.price')
                                ->label('Цена')
                                ->disabled(),

                            Forms\Components\TextInput::make('completed_at')
                                ->label('Завершено')
                                ->formatStateUsing(fn($state) => $state?->format('d.m.Y H:i'))
                                ->disabled(),
                        ]),

                    Forms\Components\Grid::make(1)
                        ->schema([
                            Forms\Components\ViewField::make('rating_display')
                                ->label('Рейтинг')
                                ->view('filament.components.rating-display')
                                ->visible(fn($record) => $record->rating),

                            Forms\Components\Textarea::make('feedback')
                                ->label('Отзыв клиента')
                                ->disabled()
                                ->visible(fn($record) => $record->feedback),

                            Forms\Components\Textarea::make('notes')
                                ->label('Заметки')
                                ->disabled()
                                ->visible(fn($record) => $record->notes),
                        ]),
                ])
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('index')
                    ->label('№')
                    ->rowIndex(),

                Tables\Columns\TextColumn::make('client.full_name')
                    ->label('Клиент')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('service.name')
                    ->label('Услуга')
                    ->searchable()
                    ->limit(25)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 25 ? $state : null;
                    }),

                Tables\Columns\TextColumn::make('schedule.work_date')
                    ->label('Дата услуги')
                    ->date('d.m.Y')
                    ->sortable()
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('time_range')
                    ->label('Время')
                    ->getStateUsing(function ($record) {
                        return Carbon::parse($record->start_time)->format('H:i') . ' - ' .
                            Carbon::parse($record->end_time)->format('H:i');
                    })
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('service.price')
                    ->label('Доход')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),

                Tables\Columns\TextColumn::make('rating')
                    ->label('Рейтинг')
                    ->formatStateUsing(function ($state) {
                        if (!$state) return '—';
                        return str_repeat('⭐', $state) . " ({$state}/5)";
                    })
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Завершено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->since()
                    ->tooltip(function ($record) {
                        return $record->completed_at?->format('d.m.Y в H:i');
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Забронировано')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('work_date')
                    ->label('Дата услуги')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('С'),
                        Forms\Components\DatePicker::make('until')
                            ->label('До'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn(Builder $query, $date): Builder => $query->whereHas(
                                    'schedule',
                                    fn($q) => $q->where('work_date', '>=', $date)
                                ),
                            )
                            ->when(
                                $data['until'],
                                fn(Builder $query, $date): Builder => $query->whereHas(
                                    'schedule',
                                    fn($q) => $q->where('work_date', '<=', $date)
                                ),
                            );
                    }),

                Filter::make('this_month')
                    ->label('Этот месяц')
                    ->query(
                        fn(Builder $query): Builder =>
                        $query->whereMonth('completed_at', Carbon::now()->month)
                            ->whereYear('completed_at', Carbon::now()->year)
                    )
                    ->toggle(),

                Filter::make('has_rating')
                    ->label('С рейтингом')
                    ->query(fn(Builder $query): Builder => $query->whereNotNull('rating'))
                    ->toggle(),

                SelectFilter::make('service_id')
                    ->label('Услуга')
                    ->options(function () {
                        return Service::where('user_id', auth()->id())
                            ->whereHas('bookings', fn($q) => $q->where('status', 'completed'))
                            ->pluck('name', 'id');
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Просмотр'),
            ])
            ->defaultSort('completed_at', 'desc')
            ->striped()
            ->emptyStateHeading('Нет завершенных услуг')
            ->emptyStateDescription('Завершенные услуги появятся здесь после их выполнения')
            ->emptyStateIcon('heroicon-o-check-circle');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompletedBookings::route('/'),
            'view' => Pages\ViewCompletedBooking::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $today = static::getModel()::where('user_id', auth()->id())
            ->where('status', 'completed')
            ->whereDate('completed_at', today())
            ->count();

        return $today > 0 ? (string) $today : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}
