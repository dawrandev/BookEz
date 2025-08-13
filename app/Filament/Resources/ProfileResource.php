<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProfileResource\Pages;
use App\Filament\Resources\ProfileResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;

class ProfileResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationLabel = 'Профиль';

    protected static ?string $modelLabel = 'Профиль';

    protected static bool $shouldRegisterNavigation = true;

    public static function canViewAny(): bool
    {
        return  Auth::user()->hasRole('specialist');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('id', auth()->id());
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')
                ->label('Имя')
                ->required()
                ->maxLength(255),

            TextInput::make('login')
                ->label('Логин')
                ->required()
                ->unique(User::class, 'login', ignoreRecord: true)
                ->maxLength(255),

            TextInput::make('phone')
                ->label('Телефон')
                ->tel()
                ->nullable()
                ->maxLength(20),

            TextInput::make('password')
                ->label('Новый пароль')
                ->password()
                ->dehydrateStateUsing(fn($state) => !empty($state) ? bcrypt($state) : null)
                ->dehydrated(fn($state) => filled($state))
                ->nullable()
                ->helperText('Оставьте пустым, если не хотите менять пароль'),
        ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListProfiles::route('/'),
            'edit' => Pages\EditProfile::route('/{record}/edit'),
        ];
    }

    public static function getNavigationUrl(): string
    {
        $user = auth()->user();
        if ($user) {
            return static::getUrl('edit', ['record' => $user->id]);
        }

        return '/admin';
    }
}
