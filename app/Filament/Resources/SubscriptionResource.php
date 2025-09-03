<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubscriptionResource\Pages;
use App\Models\Subscription;
use App\Models\User;
use App\Models\SubscriptionPlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Database\Eloquent\Collection;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'Подписки';
    protected static ?string $pluralLabel = 'Подписки';
    protected static ?string $modelLabel = 'Подписка';
    protected static ?string $navigationGroup = 'Управление подписками';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->label('Пользователь')
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                        if ($state) {
                            $user = User::find($state);
                            $plan = $get('subscription_plan_id') ? SubscriptionPlan::find($get('subscription_plan_id')) : null;
                            $monthlyPrice = $plan?->price ?? $user?->getMonthlyPrice() ?? 200000;
                            $set('monthly_price_display', number_format($monthlyPrice) . ' UZS');

                            $amount = $get('amount');
                            if ($amount && $monthlyPrice) {
                                $monthsCount = floor($amount / $monthlyPrice);
                                $set('months_count', $monthsCount);

                                $startDate = $get('start_date') ?: now()->toDateString();
                                $endDate = Carbon::parse($startDate)->addMonths($monthsCount)->toDateString();
                                $set('end_date', $endDate);
                            }
                        }
                    })
                    ->required(),

                Forms\Components\Select::make('subscription_plan_id')
                    ->relationship('subscriptionPlan', 'name')
                    ->label('Тарифный план')
                    ->live()
                    ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                        if ($state) {
                            $plan = SubscriptionPlan::find($state);
                            $monthlyPrice = $plan?->price ?? 200000;
                            $set('monthly_price_display', number_format($monthlyPrice) . ' UZS');

                            $amount = $get('amount');
                            if ($amount) {
                                $monthsCount = floor($amount / $monthlyPrice);
                                $set('months_count', $monthsCount);

                                $startDate = $get('start_date') ?: now()->toDateString();
                                $endDate = Carbon::parse($startDate)->addMonths($monthsCount)->toDateString();
                                $set('end_date', $endDate);
                            }
                        }
                    })
                    ->required(),

                Forms\Components\Placeholder::make('monthly_price_display')
                    ->label('Тариф пользователя')
                    ->content('Выберите пользователя и план'),

                Forms\Components\TextInput::make('amount')
                    ->label('Сумма (UZS)')
                    ->numeric()
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                        if ($state) {
                            $planId = $get('subscription_plan_id');
                            $userId = $get('user_id');

                            if ($planId) {
                                $plan = SubscriptionPlan::find($planId);
                                $monthlyPrice = $plan?->price ?? 200000;
                            } elseif ($userId) {
                                $user = User::find($userId);
                                $monthlyPrice = $user?->getMonthlyPrice() ?? 200000;
                            } else {
                                $monthlyPrice = 200000;
                            }

                            $amount = (int) $state;
                            $monthsCount = floor($amount / $monthlyPrice);
                            $set('months_count', $monthsCount);

                            $startDate = $get('start_date') ?: now()->toDateString();
                            $endDate = Carbon::parse($startDate)->addMonths($monthsCount)->toDateString();
                            $set('end_date', $endDate);
                        }
                    })
                    ->helperText('Количество месяцев будет рассчитано автоматически'),

                Forms\Components\TextInput::make('months_count')
                    ->label('Количество месяцев')
                    ->numeric()
                    ->readOnly()
                    ->dehydrated(true),

                Forms\Components\DatePicker::make('start_date')
                    ->label('Дата начала')
                    ->default(now())
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                        $amount = $get('amount');
                        $planId = $get('subscription_plan_id');
                        $userId = $get('user_id');

                        if ($amount && $state) {
                            if ($planId) {
                                $plan = SubscriptionPlan::find($planId);
                                $monthlyPrice = $plan?->price ?? 200000;
                            } elseif ($userId) {
                                $user = User::find($userId);
                                $monthlyPrice = $user?->getMonthlyPrice() ?? 200000;
                            } else {
                                $monthlyPrice = 200000;
                            }

                            $monthsCount = floor($amount / $monthlyPrice);
                            $endDate = Carbon::parse($state)->addMonths($monthsCount)->toDateString();
                            $set('end_date', $endDate);
                        }
                    })
                    ->required(),

                Forms\Components\DatePicker::make('end_date')
                    ->label('Дата окончания')
                    ->disabled()
                    ->dehydrated(true),

                Forms\Components\Select::make('status')
                    ->label('Статус')
                    ->options([
                        Subscription::STATUS_ACTIVE => 'Активный',
                        Subscription::STATUS_EXPIRED => 'Истекший',
                        Subscription::STATUS_PENDING => 'Ожидание',
                    ])
                    ->default(Subscription::STATUS_ACTIVE)
                    ->required(),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('index')
                    ->label('№')
                    ->rowIndex(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Пользователь')
                    ->sortable()
                    ->searchable(),

                // User status ko'rsatish
                Tables\Columns\BadgeColumn::make('user.status')
                    ->label('Статус пользователя')
                    ->colors([
                        'success' => 'active',
                        'danger' => 'inactive',
                        'gray' => fn($state) => is_null($state),
                    ])
                    ->formatStateUsing(fn(?string $state): string => match ($state) {
                        'active' => 'Активный',
                        'inactive' => 'Неактивный',
                        default => 'Неизвестно',
                    }),

                Tables\Columns\TextColumn::make('subscriptionPlan.name')
                    ->label('Тариф')
                    ->badge()
                    ->color('primary')
                    ->placeholder('План не выбран')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Сумма')
                    ->formatStateUsing(fn(int $state): string => number_format($state) . ' UZS')
                    ->sortable(),

                Tables\Columns\TextColumn::make('months_count')
                    ->label('Месяцев')
                    ->sortable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Начало')
                    ->date('d.m.Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('Конец')
                    ->date('d.m.Y')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Статус подписки')
                    ->colors([
                        'success' => Subscription::STATUS_ACTIVE,
                        'danger' => Subscription::STATUS_EXPIRED,
                        'warning' => Subscription::STATUS_PENDING,
                    ])
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        Subscription::STATUS_ACTIVE => 'Активный',
                        Subscription::STATUS_EXPIRED => 'Истекший',
                        Subscription::STATUS_PENDING => 'Ожидание',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('remaining_days')
                    ->label('Осталось дней')
                    ->getStateUsing(function (Subscription $record): string {
                        $days = $record->remaining_days;
                        if ($days <= 0) {
                            return 'Истек';
                        }
                        return $days . ' дн.';
                    })
                    ->color(function (Subscription $record): string {
                        $days = $record->remaining_days;
                        if ($days <= 0) return 'danger';
                        if ($days <= 7) return 'warning';
                        return 'success';
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создано')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус подписки')
                    ->options([
                        Subscription::STATUS_ACTIVE => 'Активные',
                        Subscription::STATUS_EXPIRED => 'Истекшие',
                        Subscription::STATUS_PENDING => 'Ожидающие',
                    ]),

                Tables\Filters\SelectFilter::make('user_status')
                    ->label('Статус пользователя')
                    ->relationship('user', 'status')
                    ->options([
                        'active' => 'Активные пользователи',
                        'inactive' => 'Неактивные пользователи',
                    ]),

                Tables\Filters\SelectFilter::make('subscription_plan_id')
                    ->label('Тарифный план')
                    ->relationship('subscriptionPlan', 'name'),

                Tables\Filters\Filter::make('expiring_soon')
                    ->label('Скоро истекают (7 дней)')
                    ->query(
                        fn(Builder $query): Builder =>
                        $query->where('status', Subscription::STATUS_ACTIVE)
                            ->where('end_date', '>=', now())
                            ->where('end_date', '<=', now()->addDays(7))
                    ),

                Tables\Filters\Filter::make('expiring_today')
                    ->label('Истекают сегодня')
                    ->query(
                        fn(Builder $query): Builder =>
                        $query->where('status', Subscription::STATUS_ACTIVE)
                            ->whereDate('end_date', now())
                    ),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Редактировать'),

                Tables\Actions\DeleteAction::make()
                    ->label('Удалить'),

                Tables\Actions\Action::make('extend_subscription')
                    ->label('Продлить')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->form([
                        Forms\Components\TextInput::make('additional_amount')
                            ->label('Дополнительная сумма (UZS)')
                            ->numeric()
                            ->required()
                            ->helperText('Будет добавлено к текущей подписке'),
                    ])
                    ->action(function (array $data, Subscription $record): void {
                        try {
                            $record->extend($data['additional_amount']);

                            Notification::make()
                                ->title('Подписка продлена')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Ошибка')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(
                        fn(Subscription $record): bool =>
                        $record->status === Subscription::STATUS_ACTIVE
                    ),

                Tables\Actions\Action::make('expire_subscription')
                    ->label('Завершить')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->action(function (Subscription $record): void {
                        $record->expire();

                        Notification::make()
                            ->title('Подписка завершена')
                            ->success()
                            ->send();
                    })
                    ->visible(
                        fn(Subscription $record): bool =>
                        $record->status === Subscription::STATUS_ACTIVE
                    )
                    ->requiresConfirmation(),

                Tables\Actions\Action::make('activate_subscription')
                    ->label('Активировать')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(function (Subscription $record): void {
                        if ($record->activate()) {
                            Notification::make()
                                ->title('Подписка активирована')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Ошибка')
                                ->body('Невозможно активировать истекшую подписку')
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(
                        fn(Subscription $record): bool =>
                        $record->status !== Subscription::STATUS_ACTIVE &&
                            $record->end_date >= now()
                    )
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->label('Удалить выбранные'),

                Tables\Actions\BulkAction::make('mark_expired')
                    ->label('Отметить как истекшие')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->action(function (Collection $records) {
                        $count = 0;
                        $records->each(function ($record) use (&$count) {
                            $record->expire();
                            $count++;
                        });

                        Notification::make()
                            ->title("Завершено {$count} подписок")
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('expire_all_old')
                    ->label('Завершить все истекшие')
                    ->icon('heroicon-o-clock')
                    ->color('warning')
                    ->action(function () {
                        $count = Subscription::expireOldSubscriptions();

                        Notification::make()
                            ->title("Завершено {$count} истекших подписок")
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),

                Tables\Actions\Action::make('update_user_statuses')
                    ->label('Обновить статусы пользователей')
                    ->icon('heroicon-o-users')
                    ->color('primary')
                    ->action(function () {
                        $count = Subscription::updateAllUserStatuses();

                        Notification::make()
                            ->title("Обновлено {$count} статусов пользователей")
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscriptions::route('/'),
            'create' => Pages\CreateSubscription::route('/create'),
            'edit' => Pages\EditSubscription::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user', 'subscriptionPlan']);
    }

    public static function getNavigationBadge(): ?string
    {
        $expiringSoon = static::getModel()::expiringSoon()->count();
        return $expiringSoon > 0 ? (string) $expiringSoon : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getNavigationBadge() ? 'warning' : null;
    }
}
