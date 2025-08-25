<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Welcome Message --}}
        <div class="bg-gradient-to-r from-primary-500 to-primary-600 rounded-lg p-6 text-white">
            <h2 class="text-2xl font-bold mb-2">
                Добро пожаловать, {{ auth()->user()->name }}! 👋
            </h2>
            <p class="text-primary-100">
                Сегодня {{ now()->format('d.m.Y') }} - Ваша статистика панели управления
            </p>
        </div>

        {{-- Stats Cards --}}
        @livewire(\App\Filament\Widgets\StatsOverviewWidget::class)

        {{-- Charts Row --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            @livewire(\App\Filament\Widgets\RevenueChart::class)
            @livewire(\App\Filament\Widgets\ServicesChart::class)
        </div>

        {{-- Latest Bookings --}}
        @livewire(\App\Filament\Widgets\LatestBookings::class)

        {{-- Quick Actions --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="{{ route('filament.admin.resources.bookings.create') }}"
                class="flex items-center p-4 bg-white rounded-lg shadow hover:shadow-md transition-shadow border border-gray-200">
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-900">Новая бронь</h3>
                    <p class="text-sm text-gray-500">Создать новую бронь</p>
                </div>
            </a>

            <a href="{{ route('filament.admin.resources.services.index') }}"
                class="flex items-center p-4 bg-white rounded-lg shadow hover:shadow-md transition-shadow border border-gray-200">
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 7.172V5L8 4z" />
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-900">Услуги</h3>
                    <p class="text-sm text-gray-500">Управление услугами</p>
                </div>
            </a>

            <a href="{{ route('filament.admin.resources.completed-bookings.index') }}"
                class="flex items-center p-4 bg-white rounded-lg shadow hover:shadow-md transition-shadow border border-gray-200">
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-900">История</h3>
                    <p class="text-sm text-gray-500">Завершённые услуги</p>
                </div>
            </a>
        </div>
    </div>
</x-filament-panels::page>