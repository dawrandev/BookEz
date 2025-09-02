<?php

namespace App\Notifications;

use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;

class TelegramNotificationService
{
    public function sendBookingCreated(Booking $booking): void
    {
        try {
            $client = $booking->client;
            if (!$client || !$client->telegram_chat_id) {
                Log::warning('No telegram_chat_id for booking created notification', ['booking_id' => $booking->id]);
                return;
            }

            $workDate = Carbon::parse($booking->schedule->work_date)->format('d.m.Y');
            $startTime = Carbon::parse($booking->start_time)->format('H:i');
            $endTime = Carbon::parse($booking->end_time)->format('H:i');

            $message = "🎉 <b>Jańa bron jaratıldı!</b>\n\n";
            $message .= "📋 Bron ID: #{$booking->id}\n";
            $message .= "👨‍⚕️ Specialist: {$booking->user->name}\n";
            $message .= "🔧 Xizmet: {$booking->service->name}\n";
            $message .= "🗓 Sáne: {$workDate}\n";
            $message .= "⏰ Waqıt: {$startTime} - {$endTime}\n";
            $message .= "💰 Qıymet: {$booking->service->price} SUM\n\n";
            $message .= "📱 Status: <b>Kútilmekte</b>\n";
            $message .= "Qániyge tastıyıqlaǵanında sizge xabar beremiz!";

            Telegram::sendMessage([
                'chat_id' => $client->telegram_chat_id,
                'text' => $message,
                'parse_mode' => 'HTML'
            ]);

            Log::info('Booking created notification sent to client', [
                'booking_id' => $booking->id,
                'client_id' => $client->id
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send booking created notification', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function sendNewBookingToSpecialist(Booking $booking, string $bookingLink): void
    {
        try {
            $specialist = $booking->user;
            if (!$specialist || !$specialist->telegram_chat_id) {
                Log::warning('No telegram_chat_id for specialist new booking notification', [
                    'booking_id' => $booking->id,
                    'user_id' => $booking->user_id
                ]);
                return;
            }

            $client = $booking->client;
            $workDate = Carbon::parse($booking->schedule->work_date)->format('d.m.Y');
            $startTime = Carbon::parse($booking->start_time)->format('H:i');
            $endTime = Carbon::parse($booking->end_time)->format('H:i');

            $message = "🔔 <b>Sizge jańa bron qosıldı!</b>\n\n";
            $message .= "📋 Bron ID: #{$booking->id}\n";
            $message .= "👤 Klient: {$client->full_name}\n";
            $message .= "🔧 Xizmet: {$booking->service->name}\n";
            $message .= "🗓 Sáne: {$workDate}\n";
            $message .= "⏰ Waqıt: {$startTime} - {$endTime}\n";
            $message .= "💰 Qıymet: {$booking->service->price} SUM\n\n";
            $message .= "📱 Status: <b>Kútilmekte</b>\n";
            $message .= "Brondı tastıyıqlań yáki biykarlań";

            $keyboard = [
                [
                    ['text' => '📋 Brondı kóriw', 'url' => $bookingLink]
                ]
            ];

            Telegram::sendMessage([
                'chat_id' => $specialist->telegram_chat_id,
                'text' => $message,
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
            ]);

            Log::info('New booking notification sent to specialist', [
                'booking_id' => $booking->id,
                'specialist_id' => $specialist->id
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send new booking notification to specialist', [
                'booking_id' => $booking->id,
                'user_id' => $booking->user_id,
                'error' => $e->getMessage()
            ]);
        }
    }

    // Bitta method bilan bron yaratish - faqat clientga xabar
    public function sendBookingCreatedNotification(Booking $booking): void
    {
        $this->sendBookingCreated($booking);
    }

    // Bitta method bilan specialistga xabar
    public function sendBookingCreatedToSpecialist(Booking $booking, string $bookingLink): void
    {
        $this->sendNewBookingToSpecialist($booking, $bookingLink);
    }

    public function sendStatusUpdate(Booking $booking): void
    {
        try {
            $client = $booking->client;
            if (!$client || !$client->telegram_chat_id) {
                Log::warning('No telegram_chat_id for status update notification', ['booking_id' => $booking->id]);
                return;
            }

            $message = $this->generateStatusMessage($booking);
            $keyboard = $this->getStatusKeyboard($booking);

            Telegram::sendMessage([
                'chat_id' => $client->telegram_chat_id,
                'text' => $message,
                'parse_mode' => 'HTML',
                'reply_markup' => $keyboard ? json_encode(['inline_keyboard' => $keyboard]) : null
            ]);

            Log::info('Status update notification sent', [
                'booking_id' => $booking->id,
                'status' => $booking->status
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send status update notification', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function generateStatusMessage(Booking $booking): string
    {
        $workDate = Carbon::parse($booking->schedule->work_date)->format('d.m.Y');
        $startTime = Carbon::parse($booking->start_time)->format('H:i');
        $endTime = Carbon::parse($booking->end_time)->format('H:i');

        $message = "📢 <b>Bron statusı ózgerdi!</b>\n\n";
        $message .= "📋 Bron ID: #{$booking->id}\n";
        $message .= "👨‍⚕️ Specialist: {$booking->user->name}\n";
        $message .= "🔧 Xizmet: {$booking->service->name}\n";
        $message .= "🗓 Sáne: {$workDate}\n";
        $message .= "⏰ Waqıt: {$startTime} - {$endTime}\n\n";

        switch ($booking->status) {
            case 'confirmed':
                $message .= "✅ <b>Status: Tastıyıqlandı</b>\n";
                $message .= "Siz belgilengen waqıtta keliwińiz kerek!";
                break;

            case 'canceled':
                $message .= "❌ <b>Status: Bıykarlandı</b>\n";
                $message .= "Basqa waqıt ushın qaytadan bron qılıń.";
                break;

            case 'completed':
                $message .= "🎉 <b>Status: Juwmaqlandı</b>\n";
                $message .= "✅ Xizmet tabıslı juwmaqlandı\n";
                $message .= "⭐ Bizdiń xizmetimizdi bahalań!";
                break;

            default:
                $message .= "📱 Status: {$booking->getStatusTextAttribute()}";
        }

        return $message;
    }

    private function getStatusKeyboard(Booking $booking): ?array
    {
        switch ($booking->status) {
            case 'completed':
                return [
                    [
                        ['text' => '⭐ Bahalaw', 'callback_data' => "rating_{$booking->id}"],
                    ],
                    [
                        ['text' => '📖 Barlıq bronlar', 'callback_data' => "my_bookings_{$booking->client_id}"],
                        ['text' => '🏠 Bas menyu', 'callback_data' => 'main_menu']
                    ]
                ];

            case 'confirmed':
                return [
                    [
                        ['text' => '❌ Bıykarlaw', 'callback_data' => "cancel_booking_{$booking->id}"],
                    ],
                    [
                        ['text' => '📖 Barlıq bronlar', 'callback_data' => "my_bookings_{$booking->client_id}"],
                        ['text' => '🏠 Bas menyu', 'callback_data' => 'main_menu']
                    ]
                ];

            case 'canceled':
                return [
                    [
                        ['text' => '🔄 Jańadan bron qılıw', 'callback_data' => "specialists"],
                        ['text' => '🏠 Bas menyu', 'callback_data' => 'main_menu']
                    ]
                ];

            default:
                return [
                    [
                        ['text' => '📖 Barlıq bronlar', 'callback_data' => "my_bookings_{$booking->client_id}"],
                        ['text' => '🏠 Bas menyu', 'callback_data' => 'main_menu']
                    ]
                ];
        }
    }

    public function sendRatingRequest(int $chatId, int $bookingId): void
    {
        try {
            $keyboard = [];

            for ($i = 1; $i <= 5; $i++) {
                $stars = str_repeat('⭐', $i);
                $keyboard[] = [
                    ['text' => $stars, 'callback_data' => "rate_{$bookingId}_{$i}"]
                ];
            }

            $keyboard[] = [
                ['text' => '❌ Bahalamaw', 'callback_data' => "skip_rating_{$bookingId}"]
            ];

            $message = "⭐ <b>Xizmetimizdi bahalań!</b>\n\n";

            Telegram::sendMessage([
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
            ]);

            Log::info('Rating request sent', ['chat_id' => $chatId, 'booking_id' => $bookingId]);
        } catch (\Exception $e) {
            Log::error('Failed to send rating request', [
                'chat_id' => $chatId,
                'booking_id' => $bookingId,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function sendFeedbackRequest(int $chatId, int $bookingId, int $rating): void
    {
        try {
            $stars = str_repeat('⭐', $rating);

            $keyboard = [
                [
                    ['text' => '❌ Pikir bildirmew', 'callback_data' => "skip_feedback_{$bookingId}"]
                ]
            ];

            $message = "💬 <b>Raxmet! {$stars}</b>\n\n";
            $message .= "Eger qosımsha pikir bildirmoqshı bolsań, \n";
            $message .= "iltimas jozıp jibériń:";

            Telegram::sendMessage([
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
            ]);

            Log::info('Feedback request sent', [
                'chat_id' => $chatId,
                'booking_id' => $bookingId,
                'rating' => $rating
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send feedback request', [
                'chat_id' => $chatId,
                'booking_id' => $bookingId,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function sendFeedbackThanks(int $chatId): void
    {
        try {
            $keyboard = [
                [
                    ['text' => '🏠 Bas menyu', 'callback_data' => 'main_menu'],
                    ['text' => '🔄 Jańadan bron', 'callback_data' => 'specialists']
                ]
            ];

            $message = "🙏 <b>Rahmet!</b>\n\n";
            $message .= "Siziń pikirińiz biz ushın óte qádirlí.\n";
            $message .= "Biziń xizmetimizdi rawajlandırıwǵa járdem bergenińiz ushın rahmet!";

            Telegram::sendMessage([
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send feedback thanks', [
                'chat_id' => $chatId,
                'error' => $e->getMessage()
            ]);
        }
    }
}
