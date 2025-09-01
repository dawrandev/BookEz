<?php

namespace App\Filament\Resources\SocialNetworksResource\Pages;

use App\Filament\Resources\SocialNetworksResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateSocialNetworks extends CreateRecord
{
    protected static string $resource = SocialNetworksResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (Auth::user()->role === 'specialist') {
            $data['user_id'] = Auth::id();
        }

        if (Auth::user()->role === 'admin' && (!isset($data['user_id']) || empty($data['user_id']))) {
            throw new \Exception('Пользователь должен быть выбран администратором');
        }

        return $data;
    }

    protected function canCreate(): bool
    {
        return in_array(Auth::user()->role, ['admin', 'specialist']);
    }
}
