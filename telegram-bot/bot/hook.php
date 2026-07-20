<?php
require '../config.php';

$update = json_decode(file_get_contents('php://input'), true);
if (!$update || !isset($update['message'])) exit;

$chat_id = $update['message']['chat']['id'];
$text = trim($update['message']['text'] ?? '');

// 自动记录用户
$from = $update['message']['from'] ?? null;
if ($from) {
    $pdo->prepare("INSERT INTO users (id, username, first_name, last_name, joined_at, last_active) 
                  VALUES (?, ?, ?, ?, NOW(), NOW()) 
                  ON DUPLICATE KEY UPDATE username=VALUES(username), first_name=VALUES(first_name), last_active=NOW()")
       ->execute([$from['id'], $from['username']??'', $from['first_name']??'', $from['last_name']??'']);
}

// 生成底部菜单 (Reply Keyboard)
$buttons = $pdo->query("SELECT button_name FROM menu_buttons WHERE is_active=1 ORDER BY sort_order ASC")->fetchAll();
$keyboard = ['keyboard' => [], 'resize_keyboard' => true];
$row = [];
foreach ($buttons as $b) {
    $row[] = ['text' => $b['button_name']];
    if (count($row) >= 2) {
        $keyboard['keyboard'][] = $row;
        $row = [];
    }
}
if (!empty($row)) $keyboard['keyboard'][] = $row;

// 处理按钮点击
$handled = false;
$all_buttons = $pdo->query("SELECT * FROM menu_buttons WHERE is_active=1")->fetchAll();
foreach ($all_buttons as $btn) {
    if ($text === $btn['button_name']) {
        
        $reply_text = $btn['reply_text'] ?: '功能开发中...';
        $inline = $btn['inline_buttons'];   // Inline 按钮

        if ($btn['reply_type'] === 'photo' && !empty($btn['photo_url'])) {
            // 发送图片 + 文字 + Inline按钮
            sendPhoto($chat_id, $btn['photo_url'], $reply_text, $inline);
        } else {
            // 发送纯文本 + Inline按钮
            sendMessage($chat_id, $reply_text, $inline);
        }
        $handled = true;
        break;
    }
}

if (!$handled) {
    if (in_array($text, ['/start', '开始', '/menu'])) {
        sendMessage($chat_id, "欢迎使用花儿机器人！\n请选择下方功能：", null);
    } else {
        sendMessage($chat_id, "请使用下方菜单操作~", null);
    }
}

// 发送文字消息 + Inline 按钮
function sendMessage($chat_id, $text, $inline_json) {
    $data = [
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => 'HTML'
    ];
    if ($inline_json) $data['reply_markup'] = $inline_json;
    file_get_contents("https://api.telegram.org/bot".BOT_TOKEN."/sendMessage?" . http_build_query($data));
}

// 发送图片 + 文字 + Inline 按钮
function sendPhoto($chat_id, $photo_url, $caption, $inline_json) {
    $data = [
        'chat_id' => $chat_id,
        'photo' => $photo_url,
        'caption' => $caption,
        'parse_mode' => 'HTML'
    ];
    if ($inline_json) $data['reply_markup'] = $inline_json;
    file_get_contents("https://api.telegram.org/bot".BOT_TOKEN."/sendPhoto?" . http_build_query($data));
}
?>