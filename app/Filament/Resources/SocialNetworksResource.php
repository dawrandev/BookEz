<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SocialNetworksResource\Pages;
use App\Models\SocialNetworks;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SocialNetworksResource extends Resource
{
    protected static ?string $model = SocialNetworks::class;

    protected static ?string $navigationIcon = 'heroicon-o-share';

    protected static ?string $navigationLabel = 'Социальные сети';
    protected static ?string $modelLabel = 'Социальная сеть';
    protected static ?string $pluralModelLabel = 'Социальные сети';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('user_id')
                ->label('Пользователь')
                ->relationship('user', 'name')
                ->searchable()
                ->preload()
                ->required(),

            Forms\Components\TextInput::make('platform')
                ->label('Платформа')
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('url')
                ->label('Ссылка')
                ->url()
                ->required()
                ->maxLength(255),

            Forms\Components\Select::make('status')
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
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Пользователь')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('platform')
                    ->label('Платформа')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('url')
                    ->label('Ссылка')
                    ->url(fn($record) => $record->url)
                    ->openUrlInNewTab()
                    ->wrap(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn($state) => [
                        'active' => 'Активный',
                        'inactive' => 'Неактивный',
                    ][$state] ?? $state)
                    ->color(fn($state) => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        default => 'secondary',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Дата создания')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Дата обновления')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Фильтр по статусу')
                    ->options([
                        'active' => 'Активный',
                        'inactive' => 'Неактивный',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Редактировать'),
                Tables\Actions\DeleteAction::make()->label('Удалить'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Удалить выбранное'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Relation managers kerak bo'lsa shu yerga qo'shasiz
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSocialNetworks::route('/'),
            'create' => Pages\CreateSocialNetworks::route('/create'),
            'edit'   => Pages\EditSocialNetworks::route('/{record}/edit'),
        ];
    }
}
