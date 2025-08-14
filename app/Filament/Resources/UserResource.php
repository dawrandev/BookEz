<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\Category;
use App\Models\User;
use Dom\Text;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Специалист';

    protected static ?string $pluralModelLabel = 'Специалисты';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->role('specialist');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('category_id')
                    ->label('Категория')
                    ->relationship('category', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        TextInput::make('name')
                            ->label('Название')
                            ->placeholder('Например: Услуги')
                            ->prefixIcon('heroicon-o-tag')
                            ->maxLength(50)
                            ->required()
                            ->columnSpanFull(),

                        TextInput::make('icon')
                            ->label('Иконка')
                            ->placeholder('Например: heroicon-o-tag')
                            ->maxLength(50)
                            ->columnSpanFull()
                    ]),
                TextInput::make('name')
                    ->label('Имя')
                    ->required(),
                TextInput::make('login')
                    ->label('Логин')
                    ->required(),
                TextInput::make('phone')
                    ->label('Телефон')
                    ->required(),
                TextInput::make('password')
                    ->label('Пароль')
                    ->password()
                    ->maxLength(255)
                    ->required(fn($livewire) => $livewire instanceof Pages\CreateUser) // create sahifada required
                    ->dehydrated(fn($state) => filled($state)) // bo‘sh bo‘lsa update qilmaydi
                    ->afterStateHydrated(fn($component, $state, $record) => $component->state(null))
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('index')->label('№')->rowIndex(),
                TextColumn::make('category.name')->label('Категория')->sortable()->searchable(),
                TextColumn::make('name')->label('Имя')->sortable()->searchable(),
                TextColumn::make('login')->label('Логин')->sortable()->searchable(),
                TextColumn::make('phone')->label('Телефон')->sortable()->searchable(),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Категория')
                    ->options(
                        Category::pluck('name', 'id')->toArray()
                    )
                    ->searchable()
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
