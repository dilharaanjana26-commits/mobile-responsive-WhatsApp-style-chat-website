<?php
require_once __DIR__ . '/lib.php';

$action = $_GET['action'] ?? '';

if ($action === 'init') {
    $input = json_decode(file_get_contents('php://input'), true);
    $fingerprint = $input['fingerprint'] ?? '';

    if (!$fingerprint) {
        json_response(['error' => 'Fingerprint missing.'], 422);
    }

    $stmt = $pdo->prepare('SELECT * FROM users WHERE fingerprint = ?');
    $stmt->execute([$fingerprint]);
    $user = $stmt->fetch();

    if (!$user) {
        $stmt = $pdo->prepare('INSERT INTO users (name, fingerprint) VALUES (?, ?)');
        $stmt->execute(['Guest User', $fingerprint]);
        $userId = (int)$pdo->lastInsertId();
    } else {
        $userId = (int)$user['id'];
    }

    $_SESSION['user_id'] = $userId;
    setcookie('device_fp', $fingerprint, time() + 60 * 60 * 24 * 30, '/', '', false, true);

    json_response(['success' => true, 'user_id' => $userId]);
}

if ($action === 'profile') {
    verify_csrf();
    $user = current_user();
    if (!$user) {
        json_response(['error' => 'Unauthorized'], 401);
    }

    $name = sanitize_text($_POST['name'] ?? '');
    if ($name === '') {
        json_response(['error' => 'Name is required.'], 422);
    }

    $avatar = save_upload('avatar');
    $avatarPath = $avatar['path'] ?? $user['avatar'];

    $stmt = $pdo->prepare('UPDATE users SET name = ?, avatar = ? WHERE id = ?');
    $stmt->execute([$name, $avatarPath, $user['id']]);

    json_response(['success' => true]);
}

if ($action === 'send') {
    verify_csrf();
    rate_limit('send_message', 10, 10);
    $user = current_user();
    if (!$user) {
        json_response(['error' => 'Unauthorized'], 401);
    }

    if ((int)$user['is_blocked'] === 1) {
        json_response(['error' => 'You are blocked.'], 403);
    }

    $message = sanitize_text($_POST['message'] ?? '');
    $upload = save_upload('media');
    $type = 'text';
    $mediaUrl = null;

    if ($upload) {
        $mediaUrl = $upload['path'];
        if (str_starts_with($upload['type'], 'image/')) {
            $type = 'image';
        } elseif (str_starts_with($upload['type'], 'video/')) {
            $type = 'video';
        } elseif (str_starts_with($upload['type'], 'audio/')) {
            $type = 'audio';
        } else {
            $type = 'document';
        }
    }

    if ($message === '' && !$upload) {
        json_response(['error' => 'Message or media required.'], 422);
    }

    $adminId = 1;
    $stmt = $pdo->prepare('INSERT INTO messages (sender_id, receiver_id, sender_role, receiver_role, message, type, media_url, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$user['id'], $adminId, 'user', 'admin', $message, $type, $mediaUrl, 'sent']);

    $settings = $pdo->query('SELECT * FROM admin_settings ORDER BY created_at DESC LIMIT 1')->fetch();
    $admin = $pdo->query('SELECT status FROM admin_users WHERE id = 1')->fetch();
    if ($settings && !empty($settings['auto_reply'])) {
        $now = new DateTime();
        $withinHours = true;
        if (!empty($settings['working_hours']) && str_contains($settings['working_hours'], '-')) {
            [$start, $end] = explode('-', $settings['working_hours']);
            $startTime = DateTime::createFromFormat('H:i', trim($start));
            $endTime = DateTime::createFromFormat('H:i', trim($end));
            if ($startTime && $endTime) {
                $todayStart = (clone $now)->setTime((int)$startTime->format('H'), (int)$startTime->format('i'));
                $todayEnd = (clone $now)->setTime((int)$endTime->format('H'), (int)$endTime->format('i'));
                $withinHours = $now >= $todayStart && $now <= $todayEnd;
            }
        }

        if (($admin['status'] ?? 'offline') !== 'online' || !$withinHours) {
            $stmt = $pdo->prepare('INSERT INTO messages (sender_id, receiver_id, sender_role, receiver_role, message, type, status) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$adminId, $user['id'], 'admin', 'user', $settings['auto_reply'], 'text', 'sent']);
        }
    }

    json_response(['success' => true]);
}

if ($action === 'poll') {
    $user = current_user();
    if (!$user) {
        json_response(['messages' => []]);
    }

    $stmt = $pdo->prepare('SELECT * FROM messages WHERE (sender_id = ? AND receiver_role = "admin") OR (receiver_id = ? AND sender_role = "admin") ORDER BY created_at ASC');
    $stmt->execute([$user['id'], $user['id']]);
    $rows = $stmt->fetchAll();

    $messages = [];
    $latestAdminId = $_SESSION['latest_admin_msg'] ?? 0;
    $playSound = false;
    foreach ($rows as $row) {
        if ($row['sender_role'] === 'admin' && $row['id'] > $latestAdminId) {
            $playSound = true;
            $latestAdminId = $row['id'];
        }
        $messages[] = [
            'id' => $row['id'],
            'sender' => $row['sender_role'] === 'user' ? 'user' : 'admin',
            'text' => $row['message'],
            'type' => $row['type'],
            'media' => $row['media_url'],
            'status' => $row['status'],
            'time' => date('H:i', strtotime($row['created_at']))
        ];
    }

    $stmt = $pdo->prepare('UPDATE messages SET status = "seen", is_seen = 1 WHERE receiver_id = ? AND receiver_role = "user"');
    $stmt->execute([$user['id']]);

    $_SESSION['latest_admin_msg'] = $latestAdminId;
    json_response(['messages' => $messages, 'playSound' => $playSound]);
}

if ($action === 'typing') {
    $user = current_user();
    if (!$user) {
        json_response(['error' => 'Unauthorized'], 401);
    }

    $stmt = $pdo->prepare('UPDATE users SET last_typing = NOW() WHERE id = ?');
    $stmt->execute([$user['id']]);

    json_response(['success' => true]);
}

json_response(['error' => 'Invalid action.'], 400);
