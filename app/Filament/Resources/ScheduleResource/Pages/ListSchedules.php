<?php

namespace App\Filament\Resources\ScheduleResource\Pages;

use App\Filament\Resources\ScheduleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ListSchedules extends ListRecords
{
    protected static string $resource = ScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $user = Auth::user();

        return [
            'prev_week' => Tab::make()
                ->label('Прошлая неделя')
                ->icon('heroicon-m-chevron-left')
                ->modifyQueryUsing(fn(Builder $query) => $this->filterByWeek($query, -1))
                ->badge($this->getWeekBadge(-1))
                ->badgeColor('gray'),

            'this_week' => Tab::make()
                ->label('Эта неделя')
                ->icon('heroicon-m-calendar')
                ->modifyQueryUsing(fn(Builder $query) => $this->filterByWeek($query, 0))
                ->badge($this->getWeekBadge(0))
                ->badgeColor('primary'),

            'next_week' => Tab::make()
                ->label('Следующая неделя')
                ->icon('heroicon-m-chevron-right')
                ->modifyQueryUsing(fn(Builder $query) => $this->filterByWeek($query, 1))
                ->badge($this->getWeekBadge(1))
                ->badgeColor('success'),

            'next_2_week' => Tab::make()
                ->label('+2 недели')
                ->icon('heroicon-m-chevron-double-right')
                ->modifyQueryUsing(fn(Builder $query) => $this->filterByWeek($query, 2))
                ->badge($this->getWeekBadge(2))
                ->badgeColor('warning'),

            'all' => Tab::make()
                ->label('Все записи')
                ->icon('heroicon-m-queue-list')
                ->badge($this->getAllBadge())
                ->badgeColor('gray'),
        ];
    }

    protected function filterByWeek(Builder $query, int $weekOffset): Builder
    {
        $user = Auth::user();

        // Hafta boshlanishi va tugashi (dushanba - yakshanba)
        $startOfWeek = Carbon::now()->startOfWeek()->addWeeks($weekOffset);
        $endOfWeek = $startOfWeek->copy()->endOfWeek();

        $filteredQuery = $query->whereBetween('work_date', [$startOfWeek, $endOfWeek]);

        // Admin bo'lmasa, faqat o'z ma'lumotlarini ko'radi
        if ($user && !$user->hasRole('admin')) {
            $filteredQuery->where('user_id', Auth::id());
        }

        return $filteredQuery->orderBy('work_date', 'asc');
    }

    protected function getWeekBadge(int $weekOffset): int
    {
        $user = Auth::user();

        $startOfWeek = Carbon::now()->startOfWeek()->addWeeks($weekOffset);
        $endOfWeek = $startOfWeek->copy()->endOfWeek();

        $query = ScheduleResource::getModel()::whereBetween('work_date', [$startOfWeek, $endOfWeek]);

        if ($user && !$user->hasRole('admin')) {
            $query->where('user_id', Auth::id());
        }

        return $query->count();
    }

    protected function getAllBadge(): int
    {
        $user = Auth::user();

        $query = ScheduleResource::getModel()::query();

        if ($user && !$user->hasRole('admin')) {
            $query->where('user_id', Auth::id());
        }

        return $query->count();
    }

    public function getDefaultActiveTab(): string
    {
        return 'this_week';
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    // Yangi yaratilgandan keyin tegishli tab'ga yo'naltirish
    protected function getRedirectUrl(): ?string
    {
        // Agar yangi record yaratilgan bo'lsa, uning haftasiga yo'naltirish
        if ($this->record && isset($this->record->work_date)) {
            $recordDate = Carbon::parse($this->record->work_date);
            $currentWeekStart = Carbon::now()->startOfWeek();

            $weekDiff = $recordDate->startOfWeek()->diffInWeeks($currentWeekStart, false);

            if ($weekDiff == -1) {
                return static::getUrl(['activeTab' => 'prev_week']);
            } elseif ($weekDiff == 0) {
                return static::getUrl(['activeTab' => 'this_week']);
            } elseif ($weekDiff == 1) {
                return static::getUrl(['activeTab' => 'next_week']);
            } elseif ($weekDiff == 2) {
                return static::getUrl(['activeTab' => 'next_2_week']);
            }
        }

        return static::getUrl(['activeTab' => 'this_week']);
    }
}
