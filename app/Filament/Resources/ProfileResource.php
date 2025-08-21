<?php

namespace App\Filament\Resources;

use Afsakar\LeafletMapPicker\LeafletMapPicker;
use App\Filament\Resources\ProfileResource\Pages;
use App\Models\User;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationLabel = 'Профиль';

    protected static ?string $modelLabel = 'Профиль';

    protected static ?string $pluralModelLabel = 'Профили';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->description('Ваши личные данные и настройки профиля')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\FileUpload::make('photo')
                                    ->label('Фото профиля')
                                    ->image()
                                    ->directory('profile-photos')
                                    ->disk('public')
                                    ->visibility('public')
                                    ->imageEditor()
                                    ->circleCropper()
                                    ->maxSize(2048)
                                    ->acceptedFileTypes(['image/jpeg', 'image/png'])
                                    ->helperText('Загрузите фото размером до 2MB (JPG, PNG)')
                                    ->columnSpan(1),

                                Forms\Components\Grid::make()
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Полное имя')
                                            ->required()
                                            ->maxLength(255)
                                            ->prefixIcon('heroicon-o-user'),

                                        Forms\Components\TextInput::make('login')
                                            ->label('Логин')
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->maxLength(255)
                                            ->prefixIcon('heroicon-o-at-symbol'),

                                        Forms\Components\TextInput::make('phone')
                                            ->label('Номер телефона')
                                            ->tel()
                                            ->maxLength(255)
                                            ->prefixIcon('heroicon-o-phone')
                                            ->placeholder('+998 XX XXX XX XX'),
                                    ])
                                    ->columnSpan(1),
                            ])
                            ->columns(2),

                        Forms\Components\Textarea::make('description')
                            ->label('Описание')
                            ->placeholder('Расскажите о себе...')
                            ->rows(4)
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Информация системы')
                    ->description('Информация, управляемая администратором')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Forms\Components\Section::make('Локация')
                            ->description('Ваше текущее местоположение')
                            ->icon('heroicon-o-map')
                            ->schema([
                                LeafletMapPicker::make('location')
                                    ->label('Location')
                                    ->height('400px')
                                    ->defaultLocation([42.4531, 59.6103])
                                    ->defaultZoom(13)
                                    ->draggable()
                                    ->clickable(),
                            ]),
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\Placeholder::make('status_display')
                                    ->label('Статус аккаунта')
                                    ->content(
                                        fn(?User $record): string =>
                                        $record ? match ($record->status) {
                                            'active' => '🟢 Активный',
                                            'inactive' => '🔴 Неактивный',
                                            default => '⚪ Неизвестно'
                                        } : '⚪ Неизвестно'
                                    ),

                                Forms\Components\Placeholder::make('category_display')
                                    ->label('Категория')
                                    ->content(
                                        fn(?User $record): string =>
                                        $record?->category?->name ?? 'Не указана'
                                    ),
                            ])
                            ->columns(2),
                    ]),

                Forms\Components\Section::make('Безопасность')
                    ->description('Изменение пароля')
                    ->icon('heroicon-o-lock-closed')
                    ->schema([
                        Forms\Components\TextInput::make('password')
                            ->label('Новый пароль')
                            ->password()
                            ->dehydrated(false)
                            ->helperText('Оставьте пустым, чтобы сохранить текущий пароль')
                            ->minLength(8)
                            ->prefixIcon('heroicon-o-key')
                            ->placeholder('Введите новый пароль...'),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->where('id', Auth::id()))
            ->columns([
                Tables\Columns\ImageColumn::make('photo')
                    ->label('Фото')
                    ->circular()
                    ->size(60)
                    ->defaultImageUrl(fn(): string => 'https://ui-avatars.com/api/?name=' . urlencode(Auth::user()->name ?? 'User') . '&color=7F9CF5&background=EBF4FF'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Имя')
                    ->weight('bold')
                    ->searchable()
                    ->icon('heroicon-o-user'),

                Tables\Columns\TextColumn::make('login')
                    ->label('Логин')
                    ->searchable()
                    ->icon('heroicon-o-at-symbol')
                    ->copyable()
                    ->copyMessage('Логин скопирован!')
                    ->copyMessageDuration(1500),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Телефон')
                    ->searchable()
                    ->icon('heroicon-o-phone')
                    ->copyable()
                    ->copyMessage('Телефон скопирован!')
                    ->copyMessageDuration(1500),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Категория')
                    ->badge()
                    ->color('primary')
                    ->icon('heroicon-o-tag'),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'success' => 'active',
                        'danger' => 'inactive',
                    ])
                    ->icons([
                        'success' => 'heroicon-o-check-circle',
                        'danger' => 'heroicon-o-x-circle',
                    ])
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'active' => 'Активный',
                        'inactive' => 'Неактивный',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->icon('heroicon-o-calendar')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Редактировать')
                    ->icon('heroicon-o-pencil')
                    ->color('primary'),
            ])
            ->bulkActions([
                // Bulk actions удалены, так как видно только свой профиль
            ])
            ->emptyStateHeading('Профиль не найден')
            ->emptyStateDescription('Не удалось загрузить информацию о профиле')
            ->emptyStateIcon('heroicon-o-user-circle');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('id', Auth::id());
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
            'index' => Pages\ListProfiles::route('/'),
            'edit' => Pages\EditProfile::route('/{record}/edit'),
        ];
    }

    // Можно просматривать только свой профиль
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        return null;
    }
}
