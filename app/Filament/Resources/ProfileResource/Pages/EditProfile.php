<?php

namespace App\Filament\Resources\ProfileResource\Pages;

use App\Filament\Resources\ProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class EditProfile extends EditRecord
{
    protected static string $resource = ProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Delete action o'chirilgan
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Password maydonini olish
        $password = request()->input('data.password');

        // Agar yangi parol kiritilgan bo'lsa
        if (!empty($password)) {
            // Yangi parolni hash qilish va dataga qo'shish
            $data['password'] = Hash::make($password);
        }

        // Recordni yangilash
        $record->update($data);

        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
