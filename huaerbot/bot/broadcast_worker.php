<?php
// 命令行专用脚本，不要用浏览器访问
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from command line.\n");
}

require __DIR__ . '/../config.php';

function sendTelegram($method, $data) {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/" . $method;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    $result = curl_exec($ch);
    curl_close($ch);
    return json_decode($result, true);
}

echo "[" . date('Y-m-d H:i:s') . "] Broadcast Worker started\n";

while (true) {
    try {
        // 取一个待处理任务
        $stmt = $pdo->query("SELECT * FROM broadcast_tasks WHERE status = 'pending' ORDER BY id ASC LIMIT 1");
        $task = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$task) {
            sleep(5);
            continue;
        }

        // 标记为运行中
        $pdo->prepare("UPDATE broadcast_tasks SET status = 'running' WHERE id = ?")->execute([$task['id']]);

        $users = json_decode($task['selected_ids'], true);
        $keyboard = $task['keyboard'] ? json_decode($task['keyboard'], true) : null;
        $sent = 0;
        $failed = 0;

        echo "[" . date('Y-m-d H:i:s') . "] Start task #{$task['id']}, total " . count($users) . " users\n";

        foreach ($users as $chat_id) {
            $data = [
                'chat_id'    => $chat_id,
                'parse_mode' => 'HTML'
            ];
            if ($keyboard) {
                $data['reply_markup'] = json_encode(['inline_keyboard' => $keyboard]);
            }

            $result = null;
            if (!empty($task['photo_url'])) {
                $data['photo'] = $task['photo_url'];
                $data['caption'] = $task['message'];
                $result = sendTelegram('sendPhoto', $data);
            } else {
                $data['text'] = $task['message'];
                $result = sendTelegram('sendMessage', $data);
            }

            if (isset($result['ok']) && $result['ok']) {
                $sent++;
                $status = 'success';
                $error = null;
            } else {
                $failed++;
                $status = 'failed';
                $error = $result['description'] ?? 'unknown error';
            }

            // 写日志
            $pdo->prepare("INSERT INTO broadcast_logs (task_id, chat_id, status, error) VALUES (?, ?, ?, ?)")
                ->execute([$task['id'], $chat_id, $status, $error]);

            // 更新进度
            $pdo->prepare("UPDATE broadcast_tasks SET sent = ?, failed = ? WHERE id = ?")
                ->execute([$sent + $failed, $failed, $task['id']]);

            // 控制速度，避免 Telegram 限流
            usleep(120000); // 0.12 秒
        }

        // 完成
        $pdo->prepare("UPDATE broadcast_tasks SET status = 'finished', sent = ?, failed = ? WHERE id = ?")
            ->execute([$sent + $failed, $failed, $task['id']]);

        echo "[" . date('Y-m-d H:i:s') . "] Task #{$task['id']} finished. Success: $sent, Failed: $failed\n";

    } catch (Exception $e) {
        echo "[" . date('Y-m-d H:i:s') . "] Error: " . $e->getMessage() . "\n";
        sleep(10);
    }
}