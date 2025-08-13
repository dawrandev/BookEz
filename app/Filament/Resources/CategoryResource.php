<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Filament\Resources\CategoryResource\RelationManagers;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'Категории';



    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Основная информация')
                    ->description('Введите название категории')
                    ->schema([
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
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('index')
                    ->label('№')
                    ->rowIndex(),
                TextColumn::make('name')
                    ->label('Название')
                    ->sortable()
                    ->searchable()
                    ->icon(fn($record) => $record->icon)
                    ->weight('bold')
                    ->tooltip(fn($record) => 'Категория: ' . $record->name),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make()
                    ->label('Изменить')
                    ->icon('heroicon-o-pencil')
                    ->color('info'),
                DeleteAction::make()
                    ->label('Удалить')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
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
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
