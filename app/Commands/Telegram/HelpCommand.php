<?php

namespace App\Commands\Telegram;

use Telegram\Bot\Commands\Command;

class HelpCommand extends Command
{
    protected string $name = 'help';

    protected string $description = 'Help';

    public function handle()
    {
        $helpText = "/start - Bottı iske túsiriw\n/help - Járdem";
        $this->replyWithMessage(['text' => $helpText]);
    }
}
