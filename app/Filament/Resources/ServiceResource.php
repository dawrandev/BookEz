<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceResource\Pages;
use App\Models\Category;
use App\Models\Service;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;


class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $navigationLabel = 'Услуги';

    protected static ?string $modelLabel = 'Услуга';

    protected static ?string $pluralModelLabel = 'Услуги';

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        return $query->where('user_id', $user->id);
    }



    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Данные услуги')
                    ->schema([
                        Forms\Components\Hidden::make('user_id')
                            ->default(Auth::id()),
                        Forms\Components\TextInput::make('name')
                            ->label('Название услуги')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('duration_minutes')
                            ->label('Продолжительность (минуты)')
                            ->numeric()
                            ->minValue(1)
                            ->suffix('мин'),
                        Forms\Components\TextInput::make('price')
                            ->label('Цена (сум)')
                            ->numeric()
                            ->inputMode('decimal')
                            ->formatStateUsing(fn($state) => $state !== null ? number_format($state, 2, '.', ' ') : null)
                            ->suffix('сум'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        $user = Auth::user();

        $columns = [
            Tables\Columns\TextColumn::make('id')
                ->label('ID')
                ->sortable(),

            Tables\Columns\TextColumn::make('name')
                ->label('Название услуги')
                ->sortable()
                ->searchable(),

            Tables\Columns\TextColumn::make('duration_minutes')
                ->label('Продолжительность')
                ->formatStateUsing(function ($state) {
                    if (!$state) {
                        return 'Не указано';
                    }

                    $hours = floor($state / 60);
                    $minutes = $state % 60;

                    if ($hours > 0) {
                        return "{$hours} ч " . ($minutes > 0 ? "{$minutes} мин" : '');
                    }

                    return "{$minutes} мин";
                })
                ->sortable(),

            Tables\Columns\TextColumn::make('price')
                ->label('Цена')
                ->formatStateUsing(
                    fn($state) => $state !== null
                        ? number_format($state, 0, '.', ' ') . ' сум'
                        : '—'
                )
                ->sortable(),
        ];

        if ($user && $user->hasRole('admin')) {
            array_splice($columns, 1, 0, [
                Tables\Columns\TextColumn::make('user.category.name')
                    ->label('Категория')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Пользователь')
                    ->searchable()
                    ->sortable(),
            ]);
        }

        $filters = [];

        if ($user && $user->hasRole('admin')) {
            $filters[] = Tables\Filters\SelectFilter::make('user_id')
                ->label('Пользователь')
                ->searchable()
                ->preload();

            $filters[] = Tables\Filters\SelectFilter::make('user.category_id')
                ->label('Категория')
                ->relationship('user.category', 'name')
                ->searchable()
                ->preload();
        }

        return $table
            ->columns($columns)
            ->filters($filters)
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ])
            ]);
    }


    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServices::route('/'),
            'create' => Pages\CreateService::route('/create'),
            'edit' => Pages\EditService::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $user = Auth::user();

        if ($user->hasRole('admin')) {
            return static::getModel()::count();
        }

        return static::getModel()::where('user_id', $user->id)->count();
    }
}
