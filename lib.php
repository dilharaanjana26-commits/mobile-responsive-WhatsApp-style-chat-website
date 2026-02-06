<?php
require_once __DIR__ . '/config.php';

function json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

function sanitize_text(string $value): string
{
    return trim(filter_var($value, FILTER_SANITIZE_FULL_SPECIAL_CHARS));
}

function current_user(): ?array
{
    if (!empty($_SESSION['user_id'])) {
        global $pdo;
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch() ?: null;
    }
    return null;
}

function admin_required(): void
{
    if (empty($_SESSION['admin_id'])) {
        header('Location: /admin/login.php');
        exit;
    }
}

function rate_limit(string $key, int $limit, int $seconds): void
{
    $now = time();
    if (!isset($_SESSION['rate_limits'])) {
        $_SESSION['rate_limits'] = [];
    }

    $bucket = $_SESSION['rate_limits'][$key] ?? ['count' => 0, 'reset' => $now + $seconds];

    if ($now > $bucket['reset']) {
        $bucket = ['count' => 0, 'reset' => $now + $seconds];
    }

    $bucket['count']++;
    $_SESSION['rate_limits'][$key] = $bucket;

    if ($bucket['count'] > $limit) {
        json_response(['error' => 'Too many requests. Please slow down.'], 429);
    }
}

function save_upload(string $field): ?array
{
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $file = $_FILES[$field];
    $allowed = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'video/mp4',
        'audio/mpeg',
        'audio/webm',
        'application/pdf',
        'application/zip'
    ];

    if (!in_array($file['type'], $allowed, true)) {
        json_response(['error' => 'Unsupported file type.'], 422);
    }

    if ($file['size'] > 20 * 1024 * 1024) {
        json_response(['error' => 'File is too large (max 20MB).'], 422);
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $name = uniqid('upload_', true) . '.' . $ext;
    $path = __DIR__ . '/uploads/' . $name;

    if (!move_uploaded_file($file['tmp_name'], $path)) {
        json_response(['error' => 'Upload failed.'], 500);
    }

    return [
        'path' => '/uploads/' . $name,
        'type' => $file['type']
    ];
}
