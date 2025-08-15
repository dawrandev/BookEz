<?php

namespace App\Handlers\Telegram;

use App\Models\Client;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\Laravel\Facades\Telegram;

class MessageHandler
{
    protected $telegram;
    protected $message;
    protected $chatId;
    protected $text;
    public function __construct(Telegram $telegram)
    {
        $this->telegram = $telegram;
        $update = $telegram->getWebhookUpdate();
        $this->message = $update->getMessage();
        $this->chatId = $update->getMessage()->getChat()->getId();
        $this->text = trim($update->getMessage()->getText());
    }
    public function handle($message)
    {
        try {
            $step = Cache::get("register_step_$this->chatId");

            if ($this->message->getContact()) {
                $phone = $this->message->getContact()->getPhoneNumber();
                $fullName = Cache::get("register_full_name_$this->chatId");

                Client::create([
                    'telegram_chat_id' => $this->chatId,
                    'telegram_id' => $this->message->getFrom()->getId(),
                    'username' => $this->message->getForm->getUsername(),
                    'full_name' => $fullName,
                    'phone' => $phone
                ]);

                $this->telegram->sendMessage([
                    'chat_id' => $this->chatId,
                    'text' => 'Siz tabıslı dizimnen óttińiz'
                ]);

                Cache::forget("register_step_$this->chatId");
                Cache::forget("register_full_name_$this->chatId");
                return;
            }

            $text = trim($message->getText());
            $step = Cache::get("register_step_$this->chatId");

            if ($step === 'ask_full_name') {
                Cache::put("register_full_name_$this->chatId", $this->text, 300);
                Cache::put("register_step_$this->chatId", 'ask_phone', 300);

                $this->telegram->sendMessage([
                    'chat_id' => $this->chatId,
                    'text' => '📞Telefon nomerińizdi kiritiń',
                    'reply_markup' => Keyboard::make()
                        ->setResizeKeyboard(true)
                        ->setOneTimeKeyboard(true)
                        ->keyboard([
                            [
                                Keyboard::button([
                                    'text' => '📱 Telefon nomer jiberiw',
                                    'request_contact' => true
                                ])
                            ]
                        ])
                ]);
            }


            if (str_starts_with($this->text, '/')) {
                return;
            }

            Telegram::sendMessage([
                'chat_id' => $this->chatId,
                'text'    => "Siz text jiberdińiz: {$text}"
            ]);
        } catch (Exception $e) {
            Log::error($e->getMessage());

            $this->telegram->sendMessage([
                'chat_id' => $this->chatId,
                'text' => 'Qátelik júz berdi. Iltimas qaytadan urınıp kóriń'
            ]);
        }
    }
}
