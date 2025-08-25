<?php

namespace App\Commands\Telegram;

use App\Models\Client;
use App\Services\Telegram\BookingViewService;

class MyBookingsCommand extends MenuCommand
{
    protected string $name = 'my_bookings';

    protected string $description = 'Mening bronlarım';

    protected string $usage = '/my_bookings';

    public function __construct(protected BookingViewService $bookingViewService)
    {
        // 
    }

    public function handle()
    {
        $chatId = $this->getUpdate()->getMessage()->getChat()->getId();
        $client = Client::where('telegram_chat_id', $chatId)->first();

        $this->bookingViewService->showMyBookings($chatId);
    }
}
