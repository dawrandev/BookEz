<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientResource\Pages;
use App\Filament\Resources\ClientResource\Pages\ListClients;
use App\Filament\Resources\ClientResource\RelationManagers;
use App\Models\Client;
use Dom\Text;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Клиенты';

    protected static ?string $pluralLabel = 'Клиенты';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = \Filament\Facades\Filament::auth()->user();

        if ($user && $user->hasRole('admin')) {
            return $query;
        } elseif ($user && $user->hasRole('specialist')) {
            return $query->whereHas('bookings', function (Builder $subQuery) {
                $subQuery->where('user_id', \Filament\Facades\Filament::auth()->id())
                    ->where('status', 'completed');
            });
        }

        // Hech qanday role bo'lmasa - bo'sh natija
        return $query->whereRaw('1 = 0');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // 
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('index')
                    ->label('№')
                    ->rowIndex(),

                TextColumn::make('username')
                    ->label('Имя пользователя')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('full_name')
                    ->label('Полное имя')
                    ->sortable()
                    ->searchable()
                    ->weight('medium'),

                TextColumn::make('phone')
                    ->label('Телефон')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('completed_bookings_count')
                    ->label('Услуг завершено')
                    ->counts('bookings')
                    ->getStateUsing(function ($record) {
                        if (auth()->user()->role === 'admin') {
                            return $record->bookings()->where('status', 'completed')->count();
                        }
                        // Oddiy user faqat o'z bookinglarini hisoblaydi
                        return $record->bookings()
                            ->where('status', 'completed')
                            ->where('user_id', auth()->id())
                            ->count();
                    })
                    ->badge()
                    ->color('success'),

                // Oxirgi completed booking sanasi
                TextColumn::make('last_completed_booking')
                    ->label('Последняя услуга')
                    ->getStateUsing(function ($record) {
                        $query = $record->bookings()
                            ->where('status', 'completed');

                        if (auth()->user()->role !== 'admin') {
                            $query->where('user_id', auth()->id());
                        }

                        $lastBooking = $query->latest('completed_at')->first();

                        return $lastBooking?->completed_at?->format('d.m.Y');
                    })
                    ->badge()
                    ->color('gray'),

                TextColumn::make('created_at')
                    ->label('Регистрация')
                    ->dateTime('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('recent_clients')
                    ->label('Недавние клиенты')
                    ->query(function (Builder $query): Builder {
                        $subQuery = function (Builder $bookingQuery) {
                            $bookingQuery->where('status', 'completed')
                                ->where('completed_at', '>=', now()->subDays(30));

                            if (auth()->user()->role !== 'admin') {
                                $bookingQuery->where('user_id', auth()->id());
                            }
                        };

                        return $query->whereHas('bookings', $subQuery);
                    })
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Просмотр')
                    ->modalHeading('Информация о клиенте')
                    ->modalWidth('sm')
                    ->form([  // 
                        Forms\Components\TextInput::make('username')
                            ->label('Имя пользователя')
                            ->disabled(),

                        Forms\Components\TextInput::make('full_name')
                            ->label('Полное имя')
                            ->disabled(),

                        Forms\Components\TextInput::make('phone')
                            ->label('Телефон')
                            ->disabled(),

                        Forms\Components\TextInput::make('telegram_id')
                            ->label('Телеграм ID')
                            ->disabled(),

                        Forms\Components\TextInput::make('telegram_chat_id')
                            ->label('Телеграм Чат ID')
                            ->disabled(),
                    ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // 
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn() => auth()->user()->hasRole('admin')),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Нет клиентов')
            ->emptyStateDescription('Клиенты с завершенными услугами появятся здесь')
            ->emptyStateIcon('heroicon-o-user-group');
    }

    public static function getRelations(): array
    {
        return [
            // 
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClients::route('/'),
        ];
    }

    // Navigation badge - clientlar sonini ko'rsatish
    public static function getNavigationBadge(): ?string
    {
        $query = static::getEloquentQuery();
        $count = $query->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }
}
