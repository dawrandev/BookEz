<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ScheduleResource\Pages;
use App\Models\Schedule;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class ScheduleResource extends Resource
{
    protected static ?string $model = Schedule::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'График';
    protected static ?string $pluralModelLabel = 'Графики';
    protected static ?string $modelLabel = 'График';

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if ($user && $user->hasRole('admin')) {
            return $query->orderBy('work_date', 'asc');
        }

        return $query->where('user_id', Auth::id())->orderBy('work_date', 'asc');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Настройки дня')
                ->columns(2)
                ->schema([
                    Forms\Components\Hidden::make('user_id')->default(Auth::id()),

                    Forms\Components\DatePicker::make('work_date')
                        ->label('Дата')
                        ->required()
                        ->helperText('На одну дату можно создать только один график.')
                        ->rules([
                            'required',
                            'date',
                            function ($get) {
                                $userId = Auth::id();
                                return Rule::unique('schedules', 'work_date')
                                    ->where('user_id', $userId)
                                    ->ignore(request()->route('record'));
                            }
                        ])
                        ->validationMessages([
                            'unique' => 'На эту дату уже существует график.'
                        ]),

                    Forms\Components\Toggle::make('is_day_off')
                        ->label('Выходной день')
                        ->helperText('Если отмечено, рабочее время и перерывы не требуются.')
                        ->reactive(),
                ]),

            Forms\Components\Section::make('Рабочее время')
                ->columns(2)
                ->schema([
                    Forms\Components\TimePicker::make('start_time')
                        ->label('Начало работы')
                        ->withoutSeconds()
                        ->required(fn($get) => !$get('is_day_off'))
                        ->disabled(fn($get) => (bool)$get('is_day_off')),

                    Forms\Components\TimePicker::make('end_time')
                        ->label('Окончание работы')
                        ->withoutSeconds()
                        ->required(fn($get) => !$get('is_day_off'))
                        ->disabled(fn($get) => (bool)$get('is_day_off'))
                        ->rule('after:start_time')
                        ->helperText('Должно быть позже, чем время начала.'),
                ]),

            Forms\Components\Section::make('Перерывы')
                ->hidden(fn($get) => (bool)$get('is_day_off'))
                ->schema([
                    Forms\Components\Repeater::make('breaks')
                        ->relationship('breaks')
                        ->label('Перерывы (необязательно)')
                        ->addActionLabel('Добавить перерыв')
                        ->grid(1)
                        ->reorderable(false)
                        ->collapsed(false)
                        ->schema([
                            Forms\Components\TimePicker::make('start_time')
                                ->label('Начало перерыва')
                                ->required()
                                ->withoutSeconds(),

                            Forms\Components\TimePicker::make('end_time')
                                ->label('Конец перерыва')
                                ->required()
                                ->rule('after:start_time')
                                ->withoutSeconds(),

                            Forms\Components\TextInput::make('reason')
                                ->label('Причина')
                                ->placeholder('Обед, личное, ...')
                                ->maxLength(100)
                                ->columnSpanFull(),
                        ])
                        ->helperText('Перерывы должны попадать в рабочий интервал и не пересекаться.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $user = Auth::user();
        $today = Carbon::today();

        $columns = [
            Tables\Columns\TextColumn::make('index')->label('№')->rowIndex(),

            Tables\Columns\TextColumn::make('work_date')
                ->label('День недели')
                ->formatStateUsing(function ($state) {
                    $date = Carbon::parse($state);
                    $dayNames = [
                        'Monday' => 'Понедельник',
                        'Tuesday' => 'Вторник',
                        'Wednesday' => 'Среда',
                        'Thursday' => 'Четверг',
                        'Friday' => 'Пятница',
                        'Saturday' => 'Суббота',
                        'Sunday' => 'Воскресенье'
                    ];
                    return $dayNames[$date->format('l')] . ' (' . $date->format('d.m') . ')';
                })
                ->extraAttributes(function ($record) use ($today) {
                    $workDate = Carbon::parse($record->work_date);
                    if ($workDate->lt($today)) {
                        return ['style' => 'color: #6b7280; font-style: italic;'];
                    } elseif ($workDate->isToday()) {
                        return ['style' => 'font-weight: 600;'];
                    }
                    return [];
                })
                ->sortable()
                ->searchable(),

            Tables\Columns\TextColumn::make('start_time')
                ->label('Начало')
                ->formatStateUsing(function ($state, $record) {
                    if ($record->is_day_off) {
                        return '—';
                    }
                    $time = $state ? \Carbon\Carbon::parse($state)->format('H:i') : '—';
                    return $time;
                })
                ->extraAttributes(function ($record) use ($today) {
                    $workDate = Carbon::parse($record->work_date);
                    if ($workDate->lt($today)) {
                        return ['style' => 'color: #6b7280; font-style: italic;'];
                    } elseif ($workDate->isToday()) {
                        return ['style' => 'font-weight: 600;'];
                    }
                    return [];
                })
                ->toggleable(isToggledHiddenByDefault: false),

            Tables\Columns\TextColumn::make('end_time')
                ->label('Конец')
                ->formatStateUsing(function ($state, $record) {
                    if ($record->is_day_off) {
                        return '—';
                    }
                    $time = $state ? \Carbon\Carbon::parse($state)->format('H:i') : '—';
                    return $time;
                })
                ->extraAttributes(function ($record) use ($today) {
                    $workDate = Carbon::parse($record->work_date);
                    if ($workDate->lt($today)) {
                        return ['style' => 'color: #6b7280; font-style: italic;'];
                    } elseif ($workDate->isToday()) {
                        return ['style' => 'font-weight: 600;'];
                    }
                    return [];
                })
                ->toggleable(isToggledHiddenByDefault: false),

            Tables\Columns\IconColumn::make('is_day_off')
                ->label('Статус')
                ->boolean()
                ->trueIcon('heroicon-o-x-circle')
                ->falseIcon('heroicon-o-check-circle')
                ->trueColor('danger')
                ->falseColor('success')
                ->sortable()
                ->extraAttributes(function ($record) use ($today) {
                    $workDate = Carbon::parse($record->work_date);
                    if ($workDate->lt($today)) {
                        return ['style' => 'opacity: 0.5;'];
                    } elseif ($workDate->isToday()) {
                        return ['style' => ''];
                    }
                    return [];
                }),

            Tables\Columns\TextColumn::make('breaks_count')
                ->counts('breaks')
                ->label('Перерывов')
                ->sortable()
                ->extraAttributes(function ($record) use ($today) {
                    $workDate = Carbon::parse($record->work_date);
                    if ($workDate->lt($today)) {
                        return ['style' => 'color: #6b7280; font-style: italic;'];
                    } elseif ($workDate->isToday()) {
                        return ['style' => ' font-weight: 600;'];
                    }
                    return [];
                }),
        ];

        if ($user && $user->hasRole('admin')) {
            array_splice($columns, 1, 0, [
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Специалист')
                    ->searchable()
                    ->sortable()
                    ->extraAttributes(function ($record) use ($today) {
                        $workDate = Carbon::parse($record->work_date);
                        if ($workDate->lt($today)) {
                            return ['style' => 'color: #6b7280; font-style: italic;'];
                        } elseif ($workDate->isToday()) {
                            return ['style' => ' font-weight: 600;'];
                        }
                        return [];
                    }),
            ]);
        }

        return $table
            ->columns($columns)
            ->paginated(false) // pagination o'chiramiz chunki tabs ishlatamiz
            ->defaultSort('work_date', 'asc')
            ->filters([
                Tables\Filters\Filter::make('only_day_off')
                    ->label('Только выходные')
                    ->query(fn(Builder $query) => $query->where('is_day_off', true)),

                Tables\Filters\Filter::make('only_working')
                    ->label('Только рабочие')
                    ->query(fn(Builder $query) => $query->where('is_day_off', false)),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(function ($record) use ($today) {
                        return Carbon::parse($record->work_date)->gte($today);
                    }),
                Tables\Actions\DeleteAction::make()
                    ->visible(function ($record) use ($today) {
                        return Carbon::parse($record->work_date)->gte($today);
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSchedules::route('/'),
            'create' => Pages\CreateSchedule::route('/create'),
            'edit'   => Pages\EditSchedule::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $user = Auth::user();
        if ($user && $user->hasRole('admin')) {
            return (string) static::getModel()::count();
        }
        return (string) static::getModel()::where('user_id', Auth::id())->count();
    }

    // Haftalik ma'lumotlarni olish uchun helper method
    public static function getWeeklySchedules($weekOffset = 0)
    {
        $user = Auth::user();

        // Joriy hafta boshlanishi (dushanba)
        $startOfWeek = Carbon::now()->startOfWeek()->addWeeks($weekOffset);
        $endOfWeek = $startOfWeek->copy()->endOfWeek();

        $query = static::getModel()::whereBetween('work_date', [$startOfWeek, $endOfWeek])
            ->orderBy('work_date', 'asc');

        if ($user && !$user->hasRole('admin')) {
            $query->where('user_id', Auth::id());
        }

        return $query->get();
    }

    // Hafta nomini olish uchun helper method
    public static function getWeekLabel($weekOffset = 0)
    {
        $startOfWeek = Carbon::now()->startOfWeek()->addWeeks($weekOffset);
        $endOfWeek = $startOfWeek->copy()->endOfWeek();

        if ($weekOffset == 0) {
            return 'Эта неделя (' . $startOfWeek->format('d.m') . ' - ' . $endOfWeek->format('d.m') . ')';
        } elseif ($weekOffset == -1) {
            return 'Прошлая неделя (' . $startOfWeek->format('d.m') . ' - ' . $endOfWeek->format('d.m') . ')';
        } elseif ($weekOffset == 1) {
            return 'Следующая неделя (' . $startOfWeek->format('d.m') . ' - ' . $endOfWeek->format('d.m') . ')';
        } else {
            return 'Неделя (' . $startOfWeek->format('d.m') . ' - ' . $endOfWeek->format('d.m') . ')';
        }
    }
}
