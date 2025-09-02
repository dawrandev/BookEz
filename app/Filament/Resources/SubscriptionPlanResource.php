<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubscriptionPlanResource\Pages;
use App\Models\SubscriptionPlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Collection;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SubscriptionPlanResource extends Resource
{
    protected static ?string $model = SubscriptionPlan::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'Тарифные планы';
    protected static ?string $pluralLabel = 'Тарифные планы';
    protected static ?string $modelLabel = 'Тарифный план';

    protected static ?string $navigationGroup = 'Управление подписками';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Название плана')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('price')
                            ->label('Цена (UZS)')
                            ->numeric()
                            ->required()
                            ->step(1000)
                            ->placeholder('200000'),
                    ]),

                Forms\Components\TagsInput::make('features')
                    ->label('Особенности плана')
                    ->placeholder('Добавьте особенность и нажмите Enter')
                    ->columnSpanFull(),

                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Активен')
                            ->default(true),

                        Forms\Components\Toggle::make('is_default')
                            ->label('По умолчанию')
                            ->helperText('Только один план может быть по умолчанию'),

                        Forms\Components\TextInput::make('sort_order')
                            ->label('Порядок сортировки')
                            ->numeric()
                            ->default(0),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('price')
                    ->label('Цена')
                    ->formatStateUsing(fn(int $state): string => number_format($state) . ' UZS')
                    ->sortable(),

                Tables\Columns\TextColumn::make('users_count')
                    ->label('Пользователей')
                    ->counts('users')
                    ->sortable(),

                Tables\Columns\TextColumn::make('features_list')
                    ->label('Особенности')
                    ->limit(50)
                    ->tooltip(function (SubscriptionPlan $record): ?string {
                        return $record->features_list;
                    }),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активен')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_default')
                    ->label('По умолчанию')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Порядок')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Статус')
                    ->placeholder('Все планы')
                    ->trueLabel('Активные')
                    ->falseLabel('Неактивные'),

                Tables\Filters\TernaryFilter::make('is_default')
                    ->label('По умолчанию'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),

                Tables\Actions\Action::make('set_default')
                    ->label('Сделать основным')
                    ->icon('heroicon-o-star')
                    ->color('warning')
                    ->action(function (SubscriptionPlan $record): void {
                        // Barcha planlarni default emas qilish
                        SubscriptionPlan::query()->update(['is_default' => false]);
                        // Tanlangan planni default qilish
                        $record->update(['is_default' => true]);
                    })
                    ->visible(fn(SubscriptionPlan $record): bool => !$record->is_default && $record->is_active)
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),

                Tables\Actions\BulkAction::make('activate')
                    ->label('Активировать')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->action(
                        fn(Collection $records) =>
                        $records->each->update(['is_active' => true])
                    ),

                Tables\Actions\BulkAction::make('deactivate')
                    ->label('Деактивировать')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->action(
                        fn(Collection $records) =>
                        $records->each->update(['is_active' => false])
                    ),
            ])
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscriptionPlans::route('/'),
            'create' => Pages\CreateSubscriptionPlan::route('/create'),
            'edit' => Pages\EditSubscriptionPlan::route('/{record}/edit'),
        ];
    }
}
