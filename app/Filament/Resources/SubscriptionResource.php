<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubscriptionResource\Pages;
use App\Models\Subscription;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Collection;
use Illuminate\Database\Eloquent\Builder;

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
                            $user = \App\Models\User::find($state);
                            $monthlyPrice = $user?->getMonthlyPrice() ?? 200000;
                            $set('monthly_price_display', number_format($monthlyPrice) . ' UZS');

                            $amount = $get('amount');
                            if ($amount) {
                                $monthsCount = floor($amount / $monthlyPrice);
                                $set('months_count', $monthsCount);

                                $startDate = $get('start_date') ?: now()->toDateString();
                                $endDate = \Carbon\Carbon::parse($startDate)->addMonths($monthsCount)->toDateString();
                                $set('end_date', $endDate);
                            }
                        }
                    })
                    ->required(),

                Forms\Components\Select::make('subscription_plan_id')
                    ->relationship(
                        'subscriptionPlan',
                        'name',
                    )
                    ->label('Тарифный план')
                    ->live()
                    ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                        if ($state) {
                            $plan = \App\Models\SubscriptionPlan::find($state);
                            $monthlyPrice = $plan?->price ?? 200000;
                            $set('monthly_price_display', number_format($monthlyPrice) . ' UZS');

                            $amount = $get('amount');
                            if ($amount) {
                                $monthsCount = floor($amount / $monthlyPrice);
                                $set('months_count', $monthsCount);

                                $startDate = $get('start_date') ?: now()->toDateString();
                                $endDate = \Carbon\Carbon::parse($startDate)->addMonths($monthsCount)->toDateString();
                                $set('end_date', $endDate);
                            }
                        }
                    })
                    ->required(),
                Forms\Components\Placeholder::make('monthly_price_display')
                    ->label('Тариф пользователя')
                    ->content('Выберите пользователя'),

                Forms\Components\TextInput::make('amount')
                    ->label('Сумма (UZS)')
                    ->numeric()
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                        if ($state) {
                            $planId = $get('subscription_plan_id');
                            if ($planId) {
                                $plan = \App\Models\SubscriptionPlan::find($planId);
                                $monthlyPrice = $plan?->price ?? 200000;
                                $amount = (int) $state;
                                $monthsCount = floor($amount / $monthlyPrice);
                                $set('months_count', $monthsCount);

                                $startDate = $get('start_date') ?: now()->toDateString();
                                $endDate = \Carbon\Carbon::parse($startDate)->addMonths($monthsCount)->toDateString();
                                $set('end_date', $endDate);
                            }
                        }
                    })
                    ->helperText('Количество месяцев будет рассчитано автоматически'),

                Forms\Components\TextInput::make('months_count')
                    ->label('Количество месяцев')
                    ->numeric()
                    ->disabled()
                    ->dehydrated(false),

                Forms\Components\DatePicker::make('start_date')
                    ->label('Дата начала')
                    ->default(now())
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                        $amount = $get('amount');
                        $userId = $get('user_id');
                        if ($amount && $state && $userId) {
                            $user = \App\Models\User::find($userId);
                            $monthlyPrice = $user?->getMonthlyPrice() ?? 200000;
                            $monthsCount = floor($amount / $monthlyPrice);
                            $endDate = \Carbon\Carbon::parse($state)->addMonths($monthsCount)->toDateString();
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
                        'active' => 'Активный',
                        'expired' => 'Истекший',
                        'pending' => 'Ожидание',
                    ])
                    ->default('active')
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
                    ->label('Статус')
                    ->colors([
                        'success' => 'active',
                        'danger' => 'expired',
                        'warning' => 'pending',
                    ])
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'active' => 'Активный',
                        'expired' => 'Истекший',
                        'pending' => 'Ожидание',
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
                    ->color(
                        fn(Subscription $record): string =>
                        $record->remaining_days <= 7 && $record->remaining_days > 0 ? 'warning' : ($record->remaining_days <= 0 ? 'danger' : 'success')
                    ),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создано')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'active' => 'Активные',
                        'expired' => 'Истекшие',
                        'pending' => 'Ожидающие',
                    ]),

                Tables\Filters\Filter::make('expiring_soon')
                    ->label('Скоро истекают')
                    ->query(
                        fn(Builder $query): Builder =>
                        $query->where('status', 'active')
                            ->where('end_date', '>=', now())
                            ->where('end_date', '<=', now()->addDays(7))
                    ),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Редактировать'),
                Tables\Actions\DeleteAction::make()->label('Удалить'),

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
                        $user = $record->user;
                        $monthlyPrice = $user->getMonthlyPrice();
                        $additionalMonths = floor($data['additional_amount'] / $monthlyPrice);

                        $record->update([
                            'amount' => $record->amount + $data['additional_amount'],
                            'months_count' => $record->months_count + $additionalMonths,
                            'end_date' => \Carbon\Carbon::parse($record->end_date)->addMonths($additionalMonths),
                            'status' => 'active',
                        ]);
                    })
                    ->visible(fn(Subscription $record): bool => $record->status === 'active'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()->label('Удалить выбранные'),

                Tables\Actions\BulkAction::make('mark_expired')
                    ->label('Отметить как истекшие')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->action(
                        fn(Collection $records) =>
                        $records->each->update(['status' => 'expired'])
                    )
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
        return parent::getEloquentQuery()->with('user');
    }
}
