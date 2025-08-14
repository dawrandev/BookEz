<?php

namespace App\Filament\Resources\ScheduleResource\Pages;

use App\Filament\Resources\ScheduleResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;


class CreateSchedule extends CreateRecord
{
    protected static string $resource = ScheduleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (!empty($data['is_day_off'])) {
            $data['start_time'] = null;
            $data['end_time'] = null;
        } else {
            if (isset($data['start_time'], $data['end_time']) && $data['start_time'] >= $data['end_time']) {
                throw ValidationException::withMessages([
                    'end_time' => 'Окончание работы должно быть позже начала.',
                ]);
            }
        }
        return $data;
    }

    protected function beforeCreate(): void
    {
        $data = $this->data;

        // breaks validatsiyasi: ish vaqtiga sig‘ishi va kesishmasligi
        if (empty($data['is_day_off']) && !empty($data['breaks'])) {
            $start = $data['start_time'];
            $end   = $data['end_time'];

            foreach ($data['breaks'] as $i => $b) {
                if ($b['start_time'] < $start || $b['end_time'] > $end) {
                    throw ValidationException::withMessages([
                        "breaks.{$i}.start_time" => 'Перерыв должен быть внутри рабочего интервала.',
                    ]);
                }
                if ($b['start_time'] >= $b['end_time']) {
                    throw ValidationException::withMessages([
                        "breaks.{$i}.end_time" => 'Конец перерыва должен быть позже начала.',
                    ]);
                }
            }

            // kesishmasligi
            $intervals = collect($data['breaks'])
                ->map(fn($b) => [$b['start_time'], $b['end_time']])
                ->sortBy(fn($p) => $p[0])
                ->values();

            for ($i = 1; $i < $intervals->count(); $i++) {
                if ($intervals[$i][0] < $intervals[$i - 1][1]) {
                    throw ValidationException::withMessages([
                        "breaks.$i.start_time" => 'Перерывы не должны пересекаться.',
                    ]);
                }
            }
        }
    }
}
