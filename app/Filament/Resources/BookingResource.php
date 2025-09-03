<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BookingResource\Pages;
use App\Models\Booking;
use App\Models\Client;
use App\Models\Service;
use App\Models\Schedule;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Бронирования';

    protected static ?string $modelLabel = 'Бронирование';

    protected static ?string $pluralModelLabel = 'Бронирования';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', auth()->id())
            ->with(['user', 'client', 'service', 'schedule']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Информация о бронировании')
                ->schema([
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\Select::make('user_id')
                                ->label('Специалист')
                                ->options(User::whereHas('services')->pluck('name', 'id'))
                                ->required()
                                ->reactive()
                                ->searchable()
                                ->preload()
                                ->default(auth()->id())
                                ->disabled(),

                            Forms\Components\Select::make('client_id')
                                ->label('Клиент')
                                ->options(Client::pluck('full_name', 'id'))
                                ->required()
                                ->searchable()
                                ->createOptionForm([
                                    Forms\Components\TextInput::make('name')
                                        ->label('Имя')
                                        ->required(),
                                    Forms\Components\TextInput::make('phone')
                                        ->label('Телефон')
                                        ->tel(),
                                    Forms\Components\TextInput::make('email')
                                        ->label('Email')
                                        ->email(),
                                ])
                                ->createOptionUsing(function (array $data) {
                                    return Client::create($data)->id;
                                }),

                            Forms\Components\Select::make('service_id')
                                ->label('Услуга')
                                ->options(function (callable $get) {
                                    return Service::where('user_id', auth()->id())
                                        ->where('status', 'active')
                                        ->pluck('name', 'id');
                                })
                                ->required()
                                ->reactive()
                                ->searchable(),

                            Forms\Components\Select::make('schedule_id')
                                ->label('График работы')
                                ->options(function (callable $get) {
                                    return Schedule::where('user_id', auth()->id())
                                        ->where('is_day_off', false)
                                        ->where('work_date', '>=', now()->toDateString())
                                        ->get()
                                        ->mapWithKeys(function ($schedule) {
                                            return [
                                                $schedule->id => $schedule->work_date->format('d.m.Y') .
                                                    ' (' . $schedule->start_time . '-' . $schedule->end_time . ')'
                                            ];
                                        });
                                })
                                ->required()
                                ->reactive(),
                        ]),

                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\TimePicker::make('start_time')
                                ->label('Время начала')
                                ->required()
                                ->reactive(),

                            Forms\Components\TimePicker::make('end_time')
                                ->label('Время окончания')
                                ->required()
                                ->afterStateHydrated(function ($state, $set, callable $get) {
                                    if (!$state && $get('start_time') && $get('service_id')) {
                                        $service = Service::find($get('service_id'));
                                        if ($service) {
                                            $endTime = Carbon::parse($get('start_time'))
                                                ->addMinutes($service->duration_minutes);
                                            $set('end_time', $endTime->format('H:i'));
                                        }
                                    }
                                }),
                        ]),

                    Forms\Components\Select::make('status')
                        ->label('Статус')
                        ->options([
                            'pending' => 'В ожидании',
                            'confirmed' => 'Подтверждено',
                            'canceled' => 'Отменено',
                            'completed' => 'Завершено',
                        ])
                        ->default('pending')
                        ->required(),

                    Forms\Components\Textarea::make('notes')
                        ->label('Заметки')
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(2),
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
                    ->limit(30)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 30 ? $state : null;
                    }),

                Tables\Columns\TextColumn::make('schedule.work_date')
                    ->label('Дата')
                    ->date('d.m.Y')
                    ->sortable()
                    ->badge()
                    ->color(fn($record) => $record->schedule->work_date->isToday() ? 'success' : ($record->schedule->work_date->isPast() ? 'danger' : 'warning')),

                Tables\Columns\TextColumn::make('time_range')
                    ->label('Время')
                    ->getStateUsing(function ($record) {
                        $start = Carbon::parse($record->start_time)->format('H:i');
                        $end = Carbon::parse($record->end_time)->format('H:i');
                        return $start . ' - ' . $end;
                    })
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('service.duration_minutes')
                    ->label('Длительность')
                    ->getStateUsing(function ($record) {
                        $minutes = $record->service->duration_minutes;
                        $hours = intdiv($minutes, 60);
                        $mins = $minutes % 60;
                        return $hours > 0 ? "{$hours}ч {$mins}м" : "{$mins}м";
                    })
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('service.price')
                    ->label('Цена')
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'confirmed',
                        'danger' => 'canceled',
                        'secondary' => 'completed',
                    ])
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            'pending' => 'В ожидании',
                            'confirmed' => 'Подтверждено',
                            'canceled' => 'Отменено',
                            'completed' => 'Завершено',
                            default => $state,
                        };
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создано')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'pending' => 'В ожидании',
                        'confirmed' => 'Подтверждено',
                        'canceled' => 'Отменено',
                        'completed' => 'Завершено',
                    ]),

                Filter::make('work_date')
                    ->label('Дата работы')
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

                Filter::make('today')
                    ->label('Сегодня')
                    ->query(fn(Builder $query): Builder => $query->whereHas(
                        'schedule',
                        fn($q) => $q->where('work_date', now()->toDateString())
                    ))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Просмотр'),
                Tables\Actions\EditAction::make()
                    ->label('Изменить'),
                Tables\Actions\Action::make('confirm')
                    ->label('Подтвердить')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(function (Booking $record) {
                        $record->status = 'confirmed';
                        $record->save(); // Eloquent orqali save, observerni trigger qiladi
                    })
                    ->visible(fn(Booking $record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->after(function () {
                        return redirect()->route('filament.admin.resources.bookings.index');
                    }),
                // BookingResource.php da complete action'ni quyidagicha o'zgartiring:

                Tables\Actions\Action::make('complete')
                    ->label('Завершить')
                    ->icon('heroicon-o-check-badge')
                    ->color('primary')
                    ->action(function (Booking $record) {
                        $record->status = 'completed';
                        $record->completed_at = now(); // Bu qatorni qo'shing
                        $record->save();
                    })
                    ->visible(fn(Booking $record) => $record->status === 'confirmed')
                    ->requiresConfirmation()
                    ->modalHeading('Завершить бронирование')
                    ->modalDescription('Вы уверены, что хотите завершить это бронирование?')
                    ->modalSubmitActionLabel('Да, завершить')
                    ->modalCancelActionLabel('Отмена')
                    ->after(function () {
                        return redirect()->route('filament.admin.resources.bookings.index');
                    }),
                Tables\Actions\Action::make('cancel')
                    ->label('Отменить')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->action(function (Booking $record) {
                        $record->status = 'canceled';
                        $record->save(); // Eloquent orqali save, observerni trigger qiladi
                    })
                    ->visible(fn(Booking $record) => in_array($record->status, ['pending', 'confirmed']))
                    ->requiresConfirmation()
                    ->after(function () {
                        return redirect()->route('filament.admin.resources.bookings.index');
                    }),
                Tables\Actions\DeleteAction::make()
                    ->label('Удалить'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('confirm')
                        ->label('Подтвердить выбранные')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->status = 'confirmed';
                                $record->save(); // Har bir recordni alohida save qilish
                            });
                        })
                        ->requiresConfirmation(),
                    Tables\Actions\BulkAction::make('cancel')
                        ->label('Отменить выбранные')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->status = 'canceled';
                                $record->save(); // Har bir recordni alohida save qilish
                            });
                        })
                        ->requiresConfirmation(),
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Удалить выбранные'),
                ])
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s')
            ->striped()
            ->emptyStateHeading('Нет бронирований')
            ->emptyStateDescription('Бронирования появятся здесь после их создания')
            ->emptyStateIcon('heroicon-o-clipboard-document-check');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBookings::route('/'),
            'create' => Pages\CreateBooking::route('/create'),
            'view' => Pages\ViewBooking::route('/{record}'),
            'edit' => Pages\EditBooking::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('user_id', auth()->id())
            ->where('status', 'pending')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::where('user_id', auth()->id())
            ->where('status', 'pending')->count() > 0 ? 'warning' : null;
    }
}
