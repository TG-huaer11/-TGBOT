<?php
session_start();
if (!isset($_SESSION['admin'])) {
    http_response_code(403);
    exit;
}
require '../config.php';

header('Content-Type: application/json; charset=utf-8');

$task_id = intval($_GET['id'] ?? 0);
$last_id = intval($_GET['last_id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM broadcast_tasks WHERE id = ?");
$stmt->execute([$task_id]);
$task = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$task) {
    echo json_encode(['ok' => false]);
    exit;
}

// 获取新日志
$stmt = $pdo->prepare("SELECT id, chat_id, status, error FROM broadcast_logs WHERE task_id = ? AND id > ? ORDER BY id ASC LIMIT 100");
$stmt->execute([$task_id, $last_id]);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'ok'     => true,
    'sent'   => (int)$task['sent'],
    'failed' => (int)$task['failed'],
    'total'  => (int)$task['total'],
    'status' => $task['status'],
    'logs'   => $logs
], JSON_UNESCAPED_UNICODE);