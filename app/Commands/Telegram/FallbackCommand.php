<?php

namespace App\Commands\Telegram;

use Telegram\Bot\Commands\Command;

class FallbackCommand extends Command
{
    protected string $name = 'fallback';

    protected string $description = 'Fallback command';

    public function handle()
    {
        $this->replyWithMessage([
            'text' => 'Belgisiz buyrıq'
        ]);
    }
}
