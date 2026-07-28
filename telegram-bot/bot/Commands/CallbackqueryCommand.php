<?php
namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Request;

class CallbackqueryCommand extends SystemCommand {
    public function execute() {
        $callbackQuery = $this->getCallbackQuery();
        $data = $callbackQuery->getData();   // 如 "approve_123"
        $userId = $callbackQuery->getFrom()->getId();

        if (strpos($data, 'approve_') === 0) {
            // 处理逻辑...
            Request::answerCallbackQuery([
                'callback_query_id' => $callbackQuery->getId(),
                'text' => '已批准！',
                'show_alert' => true
            ]);
        }
    }
}