<?php
require '../config.php';

$update = json_decode(file_get_contents('php://input'), true);
if (!$update || !isset($update['message'])) exit;

$chat_id = $update['message']['chat']['id'];
$text = trim($update['message']['text'] ?? '');

// 记录用户
$from = $update['message']['from'] ?? null;
if ($from) {
    $pdo->prepare("INSERT INTO users (id, username, first_name, last_name, joined_at, last_active) 
                  VALUES (?, ?, ?, ?, NOW(), NOW()) 
                  ON DUPLICATE KEY UPDATE username=VALUES(username), first_name=VALUES(first_name), last_active=NOW()")
       ->execute([$from['id'], $from['username']??'', $from['first_name']??'', $from['last_name']??'']);
}

// 处理按钮（模糊匹配）
$handled = false;
$stmt = $pdo->prepare("SELECT * FROM menu_buttons WHERE is_active = 1 ORDER BY sort_order ASC");
$stmt->execute();
$buttons = $stmt->fetchAll();

foreach ($buttons as $btn) {
    $btn_name = trim($btn['button_name']);
    $btn_cmd = trim($btn['command']);
    
    similar_text($text, $btn_name, $percent_name);
    similar_text($text, $btn_cmd, $percent_cmd);

    if ($text === $btn_name || $text === $btn_cmd || $percent_name > 80 || $percent_cmd > 80) {
        $reply_text = $btn['reply_text'] ?: '功能开发中...';
        $photo_url  = $btn['photo_url'] ?? '';

        if (!empty($photo_url)) {
            sendPhoto($chat_id, $photo_url, $reply_text);
        } else {
            sendMessage($chat_id, $reply_text);
        }
        $handled = true;
        break;
    }
}

// /start 刷新菜单
if ($text === '/start' || $text === '开始' || $text === '/menu') {
    sendKeyboard($chat_id);
} elseif (!$handled) {
    sendMessage($chat_id, "请使用下方菜单操作~");
}

function sendMessage($chat_id, $text) {
    $data = ['chat_id' => $chat_id, 'text' => $text, 'parse_mode' => 'HTML'];
    file_get_contents("https://api.telegram.org/bot".BOT_TOKEN."/sendMessage?" . http_build_query($data));
}

function sendPhoto($chat_id, $photo_url, $caption) {
    $data = [
        'chat_id' => $chat_id,
        'photo' => $photo_url,
        'caption' => $caption,
        'parse_mode' => 'HTML'
    ];
    file_get_contents("https://api.telegram.org/bot".BOT_TOKEN."/sendPhoto?" . http_build_query($data));
}

function sendKeyboard($chat_id) {
    $stmt = $pdo->prepare("SELECT button_name FROM menu_buttons WHERE is_active = 1 ORDER BY sort_order ASC");
    $stmt->execute();
    $buttons = $stmt->fetchAll();

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

    $data = [
        'chat_id' => $chat_id,
        'text' => "欢迎使用！请选择下方功能：",
        'reply_markup' => json_encode($keyboard)
    ];
    file_get_contents("https://api.telegram.org/bot".BOT_TOKEN."/sendMessage?" . http_build_query($data));
}
?>