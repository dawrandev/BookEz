<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SocialNetworksResource\Pages\CreateSocialNetworks;
use App\Filament\Resources\SocialNetworksResource\Pages\EditSocialNetworks;
use App\Filament\Resources\SocialNetworksResource\Pages\ListSocialNetworks;
use App\Models\SocialNetworks;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class SocialNetworksResource extends Resource
{
    protected static ?string $model = SocialNetworks::class;

    protected static ?string $navigationIcon = 'heroicon-o-link';

    protected static ?string $navigationLabel = 'Социальные сети';

    protected static ?string $modelLabel = 'Социальная сеть';

    protected static ?string $pluralModelLabel = 'Социальные сети';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\Hidden::make('user_id')
                            ->default(fn() => Auth::id())
                            ->when(
                                !Auth::user()?->hasRole('admin'),
                                fn(Forms\Components\Hidden $component) => $component
                            ),

                        Forms\Components\Select::make('user_id')
                            ->label('Пользователь')
                            ->relationship('user', 'name')
                            ->required()
                            ->searchable()
                            ->default(fn() => Auth::id())
                            ->visible(fn() => Auth::user()?->hasRole('admin')),

                        Forms\Components\Select::make('platform')
                            ->label('Платформа')
                            ->options([
                                'facebook' => 'Facebook',
                                'instagram' => 'Instagram',
                                'twitter' => 'Twitter',
                                'linkedin' => 'LinkedIn',
                                'youtube' => 'YouTube',
                                'tiktok' => 'TikTok',
                                'telegram' => 'Telegram',
                                'github' => 'GitHub',
                                'website' => 'Веб-сайт',
                                'other' => 'Другое',
                            ])
                            ->required()
                            ->native(false),

                        Forms\Components\TextInput::make('url')
                            ->label('Ссылка')
                            ->url()
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options([
                                'active' => 'Активный',
                                'inactive' => 'Неактивный',
                            ])
                            ->default('active')
                            ->required()
                            ->native(false),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('index')->label('№')->rowIndex(),
                Tables\Columns\ImageColumn::make('photo')->label('Фото')->circular()->size(60)->visible(fn() => Auth::user()?->hasRole('admin')),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Пользователь')
                    ->sortable()
                    ->searchable()
                    ->visible(fn() => Auth::user()?->hasRole('admin')),

                Tables\Columns\TextColumn::make('platform')
                    ->label('Платформа')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'facebook' => 'info',
                        'instagram' => 'warning',
                        'twitter' => 'primary',
                        'linkedin' => 'success',
                        'youtube' => 'danger',
                        'tiktok' => 'gray',
                        'telegram' => 'info',
                        'github' => 'gray',
                        'website' => 'success',
                        default => 'secondary',
                    }),

                Tables\Columns\TextColumn::make('url')
                    ->label('Ссылка')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    })
                    ->openUrlInNewTab(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'success' => 'active',
                        'danger' => 'inactive',
                    ]),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Дата создания')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Дата обновления')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('platform')
                    ->label('Платформа')
                    ->options([
                        'facebook' => 'Facebook',
                        'instagram' => 'Instagram',
                        'twitter' => 'Twitter',
                        'linkedin' => 'LinkedIn',
                        'youtube' => 'YouTube',
                        'tiktok' => 'TikTok',
                        'telegram' => 'Telegram',
                        'github' => 'GitHub',
                        'website' => 'Веб-сайт',
                        'other' => 'Другое',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'active' => 'Активный',
                        'inactive' => 'Неактивный',
                    ]),

                Tables\Filters\Filter::make('user')
                    ->form([
                        Forms\Components\Select::make('user_id')
                            ->label('Пользователь')
                            ->relationship('user', 'name')
                            ->searchable(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['user_id'],
                                fn(Builder $query, $userId): Builder => $query->where('user_id', $userId),
                            );
                    })
                    ->visible(fn() => Auth::user()?->hasRole('admin')),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Редактировать'),
                Tables\Actions\DeleteAction::make()->label('Удалить'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Удалить выбранное'),

                    Tables\Actions\BulkAction::make('activate')
                        ->label('Активировать')
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->action(fn($records) => $records->each->update(['status' => 'active']))
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Деактивировать')
                        ->icon('heroicon-m-x-circle')
                        ->color('danger')
                        ->action(fn($records) => $records->each->update(['status' => 'inactive']))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    protected static function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['user_id'])) {
            $data['user_id'] = Auth::id();
        }

        return $data;
    }

    protected static function mutateFormDataBeforeSave(array $data): array
    {
        if (empty($data['user_id'])) {
            $data['user_id'] = Auth::id();
        }

        return $data;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (!Auth::user()?->hasRole('admin')) {
            $query->where('user_id', Auth::id());
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSocialNetworks::route('/'),
            'create' => CreateSocialNetworks::route('/create'),
            'edit' => EditSocialNetworks::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [];
    }
}
