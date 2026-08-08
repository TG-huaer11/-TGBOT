<?php
require '../config.php';

// ==================== 配置机器人作者 ====================
// ==================== 从数据库读取转发接收人 ====================
$owner_chat_id = 0;
try {
    $stmt = $pdo->prepare("SELECT value FROM settings WHERE name = 'owner_chat_id' LIMIT 1");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && is_numeric($row['value'])) {
        $owner_chat_id = (int)$row['value'];
    }
} catch (Exception $e) {
    // 表不存在或出错时使用默认值，防止报错
    $owner_chat_id = 8295042957;
}
define('OWNER_CHAT_ID', $owner_chat_id);

$update = json_decode(file_get_contents('php://input'), true);
if (!$update || !isset($update['message'])) exit;

$message = $update['message'];
$chat_id = $message['chat']['id'];
$text    = trim($message['text'] ?? '');
$message_id = $message['message_id'] ?? null;
$from = $message['from'] ?? null;

// ====================== 记录用户 ======================
if ($from) {
    $pdo->prepare("INSERT INTO users (id, username, first_name, last_name, joined_at, last_active) 
                  VALUES (?, ?, ?, ?, NOW(), NOW()) 
                  ON DUPLICATE KEY UPDATE username=VALUES(username), first_name=VALUES(first_name), last_active=NOW()")
       ->execute([$from['id'], $from['username']??'', $from['first_name']??'', $from['last_name']??'']);
}

// ====================== TRC20 USDT 自动查询 ======================
if (isTrc20Address($text)) {
    $result = queryTrc20USDTDetail($text);
    
    $data = [
        'chat_id' => $chat_id,
        'text' => $result,
        'parse_mode' => 'HTML',
        'reply_to_message_id' => $message_id
    ];
    file_get_contents("https://api.telegram.org/bot".BOT_TOKEN."/sendMessage?" . http_build_query($data));
    exit;   // 查询后直接退出，不再走菜单
}

// ====================== 仅作者可使用的双向回复 ======================
if ($chat_id == OWNER_CHAT_ID && isset($message['reply_to_message'])) {
    $reply_to = $message['reply_to_message'];
    $original_text = $reply_to['text'] ?? '';
    
    if (preg_match('/🆔 <b>ID：<\/b> <code>(\d+)<\/code>/', $original_text, $matches)) {
        $target_user_id = (int)$matches[1];
        
        if ($target_user_id > 0 && $target_user_id != OWNER_CHAT_ID && !empty($text)) {
            
            $send_data = ['chat_id' => $target_user_id, 'parse_mode' => 'HTML'];

            if (isset($message['photo'])) {
                $photo = end($message['photo'])['file_id'];
                $send_data['photo'] = $photo;
                $send_data['caption'] = $text;
                $method = 'sendPhoto';
            } else {
                $send_data['text'] = $text;
                $method = 'sendMessage';
            }

            file_get_contents("https://api.telegram.org/bot" . BOT_TOKEN . "/{$method}?" . http_build_query($send_data));

            // 确认回复成功
            file_get_contents("https://api.telegram.org/bot" . BOT_TOKEN . "/sendMessage?" . http_build_query([
                'chat_id' => OWNER_CHAT_ID,
                'text' => "✅ 已成功回复给用户 <code>{$target_user_id}</code>",
                'parse_mode' => 'HTML',
                'reply_to_message_id' => $message_id
            ]));
        }
    }
}

// ====================== 转发用户消息给作者 ======================
if ($from && $chat_id != OWNER_CHAT_ID) {
    $user_info = "👤 <b>用户：</b> {$from['first_name']} " . 
                 (!empty($from['last_name']) ? $from['last_name'] : '') . "\n";
    $user_info .= "🆔 <b>ID：</b> <code>{$from['id']}</code>\n";
    $user_info .= "🔗 <b>用户名：</b> @" . ($from['username'] ?? '无') . "\n\n";
    
    $forward_text = $user_info . "💬 <b>消息内容：</b>\n" . 
                   ($text ?: '[非文本消息或媒体]');

    file_get_contents("https://api.telegram.org/bot" . BOT_TOKEN . "/sendMessage?" . http_build_query([
        'chat_id' => OWNER_CHAT_ID,
        'text' => $forward_text,
        'parse_mode' => 'HTML'
    ]));

    // 转发媒体消息
    if (isset($message['photo']) || isset($message['document']) || isset($message['voice']) || 
        isset($message['video']) || isset($message['sticker']) || isset($message['audio'])) {
        
        file_get_contents("https://api.telegram.org/bot" . BOT_TOKEN . "/forwardMessage?" . http_build_query([
            'chat_id' => OWNER_CHAT_ID,
            'from_chat_id' => $chat_id,
            'message_id' => $message_id
        ]));
    }
}

// ====================== 菜单按钮处理 ======================
$handled = false;
if (!empty($text)) {
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
}

if (!$handled) {
    sendKeyboard($chat_id);
}

// ====================== 辅助函数 ======================
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

// ====================== TRC20 USDT 查询功能 ======================
function isTrc20Address($addr) {
    $addr = trim($addr);
    return (strlen($addr) === 34 && strtoupper($addr[0]) === 'T');
}

function queryTrc20USDTDetail($address) {
    $address = trim($address);
    
    // TronScan API 查询余额
    $url = "https://apilist.tronscanapi.com/api/account?address=" . urlencode($address);
    $json = @file_get_contents($url);
    $data = json_decode($json, true);

    $trxBalance = isset($data['balance']) ? $data['balance'] / 1000000 : 0;

    $usdt = 0;
    if (isset($data['trc20token_balances'])) {
        foreach ($data['trc20token_balances'] as $token) {
            if (strtoupper($token['tokenAbbr'] ?? '') === 'USDT') {
                $usdt = ($token['balance'] ?? 0) / 1000000;
                break;
            }
        }
    } elseif (isset($data['tokens'])) {
        foreach ($data['tokens'] as $token) {
            if (strtoupper($token['tokenAbbr'] ?? '') === 'USDT') {
                $usdt = ($token['balance'] ?? 0) / pow(10, $token['tokenDecimal'] ?? 6);
                break;
            }
        }
    }

    // 查询带宽和能量
    $resourceUrl = "https://api.trongrid.io/wallet/getaccountresource";
    $postData = json_encode(['address' => $address, 'visible' => true]);
    $resource = curlPost($resourceUrl, $postData);

    $freeNetLimit = $resource['freeNetLimit'] ?? 0;
    $freeNetUsed  = $resource['freeNetUsed'] ?? 0;
    $netLimit     = $resource['NetLimit'] ?? 0;
    $netUsed      = $resource['NetUsed'] ?? 0;
    $totalBandwidth = $freeNetLimit + $netLimit;
    $usedBandwidth  = $freeNetUsed + $netUsed;
    $remainingBandwidth = max(0, $totalBandwidth - $usedBandwidth);

    $energyLimit = $resource['EnergyLimit'] ?? 0;
    $energyUsed  = $resource['EnergyUsed'] ?? 0;
    $remainingEnergy = max(0, $energyLimit - $energyUsed);

    $msg = "<b>TRC-20 · 地址查询</b>\n\n";
    $msg .= "地址：<code>{$address}</code>\n\n";
    $msg .= "TRX   余额：<b>" . number_format($trxBalance, 4) . " TRX</b>\n";
    $msg .= "USDT 余额：<b>" . number_format($usdt, 4) . " USDT</b>\n\n";
    $msg .= "带宽：<b>" . number_format($remainingBandwidth) . "</b> / " . number_format($totalBandwidth) . "\n";
    $msg .= "能量：<b>" . number_format($remainingEnergy) . "</b> / " . number_format($energyLimit) . "\n";

    return $msg;
}

function curlPost($url, $postData) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    curl_close($ch);
    return json_decode($result, true) ?: [];
}
?>