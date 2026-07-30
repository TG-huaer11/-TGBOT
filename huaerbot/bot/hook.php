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

// 处理按钮回复
$handled = false;
$stmt = $pdo->prepare("SELECT * FROM menu_buttons WHERE is_active = 1 ORDER BY sort_order ASC");
$stmt->execute();
$buttons = $stmt->fetchAll();

foreach ($buttons as $btn) {
    $btn_name = trim($btn['button_name']);
    $btn_cmd  = trim($btn['command']);
    
    similar_text($text, $btn_name, $p1);
    similar_text($text, $btn_cmd, $p2);

    if ($text === $btn_name || $text === $btn_cmd || max($p1, $p2) > 75) {
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

// 无论如何都尝试刷新键盘（加强版）
sendKeyboard($chat_id);

// 如果没有处理任何按钮，给默认提示
if (!$handled && !in_array($text, ['/start','开始','/menu'])) {
    sendMessage($chat_id, "请使用下方菜单操作~");
}

// ====================== 函数 ======================
function sendMessage($chat_id, $text) {
    $data = ['chat_id' => $chat_id, 'text' => $text, 'parse_mode' => 'HTML'];
    file_get_contents("https://api.telegram.org/bot".BOT_TOKEN."/sendMessage?" . http_build_query($data));
}

function sendPhoto($chat_id, $photo_url, $caption) {
    $data = ['chat_id' => $chat_id, 'photo' => $photo_url, 'caption' => $caption, 'parse_mode' => 'HTML'];
    file_get_contents("https://api.telegram.org/bot".BOT_TOKEN."/sendPhoto?" . http_build_query($data));
}

function sendKeyboard($chat_id) {
    global $pdo;
    
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
        'text' => "请选择下方功能：",
        'reply_markup' => json_encode($keyboard)
    ];
    file_get_contents("https://api.telegram.org/bot".BOT_TOKEN."/sendMessage?" . http_build_query($data));
}
?>