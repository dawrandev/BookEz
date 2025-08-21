<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\Category;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\KeyValue;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
                    ->preload(),

                FileUpload::make('photo')
                    ->label('Фото')
                    ->image()
                    ->directory('users/photos')
                    ->imageEditor()
                    ->columnSpanFull(),

                TextInput::make('name')
                    ->label('Имя')
                    ->required(),

                TextInput::make('login')
                    ->label('Логин')
                    ->required(),

                TextInput::make('phone')
                    ->label('Телефон'),

                TextInput::make('password')
                    ->label('Пароль')
                    ->password()
                    ->maxLength(255)
                    ->required(fn($livewire) => $livewire instanceof Pages\CreateUser)
                    ->dehydrated(fn($state) => filled($state))
                    ->afterStateHydrated(fn($component, $state, $record) => $component->state(null)),

                Textarea::make('description')
                    ->label('Описание')
                    ->placeholder('Краткое описание специалиста...')
                    ->columnSpanFull(),

                KeyValue::make('location')
                    ->label('Локация')
                    ->addButtonLabel('Добавить')
                    ->columnSpanFull(),

                Select::make('status')
                    ->label('Статус')
                    ->options([
                        'active' => 'Активный',
                        'inactive' => 'Неактивный',
                    ])
                    ->default('active')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('index')->label('№')->rowIndex(),
                ImageColumn::make('photo')->label('Фото')->circular()->size(60),
                TextColumn::make('category.name')->label('Категория')->sortable()->searchable(),
                TextColumn::make('name')->label('Имя')->sortable()->searchable(),
                TextColumn::make('login')->label('Логин')->sortable()->searchable(),
                TextColumn::make('phone')->label('Телефон')->sortable()->searchable(),
                TextColumn::make('description')->label('Описание')->limit(30),
                TextColumn::make('status')->label('Статус')
                    ->badge()
                    ->colors([
                        'success' => 'active',
                        'danger' => 'inactive',
                    ]),
                TextColumn::make('created_at')->label('Создан')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Категория')
                    ->options(Category::pluck('name', 'id')->toArray())
                    ->searchable(),

                SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'active' => 'Активный',
                        'inactive' => 'Неактивный',
                    ]),
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
        return [];
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
