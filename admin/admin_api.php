<?php
require_once __DIR__ . '/../lib.php';
admin_required();

$action = $_GET['action'] ?? '';

if ($action === 'users') {
    $stmt = $pdo->query('SELECT u.*, (SELECT message FROM messages WHERE (sender_id = u.id AND sender_role = "user") OR (receiver_id = u.id AND receiver_role = "user") ORDER BY created_at DESC LIMIT 1) AS last_message FROM users u ORDER BY is_pinned DESC, created_at DESC');
    $users = [];
    foreach ($stmt->fetchAll() as $row) {
        $users[] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'status' => $row['is_blocked'] ? 'Blocked' : 'Active',
            'last_message' => $row['last_message'] ?? 'No messages yet',
        ];
    }
    json_response(['users' => $users]);
}

if ($action === 'messages') {
    $userId = (int)($_GET['user_id'] ?? 0);
    $stmt = $pdo->prepare('SELECT * FROM messages WHERE (sender_id = ? AND sender_role = "user") OR (receiver_id = ? AND receiver_role = "user") ORDER BY created_at ASC');
    $stmt->execute([$userId, $userId]);
    $rows = $stmt->fetchAll();

    $messages = [];
    foreach ($rows as $row) {
        $messages[] = [
            'id' => $row['id'],
            'sender' => $row['sender_role'] === 'admin' ? 'admin' : 'user',
            'text' => $row['message'],
            'type' => $row['type'],
            'media' => $row['media_url'],
            'status' => $row['status'],
            'time' => date('H:i', strtotime($row['created_at']))
        ];
    }

    $stmt = $pdo->prepare('UPDATE messages SET status = "seen", is_seen = 1 WHERE receiver_id = ? AND receiver_role = "admin"');
    $stmt->execute([$userId]);

    json_response(['messages' => $messages]);
}

if ($action === 'send') {
    verify_csrf();
    rate_limit('admin_send', 20, 10);
    $userId = (int)($_POST['user_id'] ?? 0);
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

    $stmt = $pdo->prepare('INSERT INTO messages (sender_id, receiver_id, sender_role, receiver_role, message, type, media_url, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$_SESSION['admin_id'], $userId, 'admin', 'user', $message, $type, $mediaUrl, 'sent']);

    json_response(['success' => true]);
}

if ($action === 'quick_replies') {
    $stmt = $pdo->prepare('SELECT * FROM quick_replies WHERE admin_id = ? ORDER BY created_at DESC');
    $stmt->execute([$_SESSION['admin_id']]);
    json_response(['replies' => $stmt->fetchAll()]);
}

if ($action === 'quick_reply_add') {
    $input = json_decode(file_get_contents('php://input'), true);
    $title = sanitize_text($input['title'] ?? '');
    $body = sanitize_text($input['body'] ?? '');
    if ($title === '' || $body === '') {
        json_response(['error' => 'Title and body required.'], 422);
    }
    $stmt = $pdo->prepare('INSERT INTO quick_replies (admin_id, title, body) VALUES (?, ?, ?)');
    $stmt->execute([$_SESSION['admin_id'], $title, $body]);
    json_response(['success' => true]);
}

if ($action === 'profile') {
    $stmt = $pdo->prepare('SELECT name, status, avatar FROM admin_users WHERE id = ?');
    $stmt->execute([$_SESSION['admin_id']]);
    $profile = $stmt->fetch();
    json_response(['profile' => $profile]);
}

if ($action === 'profile_update') {
    verify_csrf();
    $name = sanitize_text($_POST['name'] ?? '');
    $status = sanitize_text($_POST['status'] ?? 'online');
    if ($name === '') {
        json_response(['error' => 'Name required.'], 422);
    }

    $upload = save_upload('avatar');
    $avatarPath = $upload['path'] ?? null;

    if ($avatarPath) {
        $stmt = $pdo->prepare('UPDATE admin_users SET name = ?, status = ?, avatar = ? WHERE id = ?');
        $stmt->execute([$name, $status, $avatarPath, $_SESSION['admin_id']]);
    } else {
        $stmt = $pdo->prepare('UPDATE admin_users SET name = ?, status = ? WHERE id = ?');
        $stmt->execute([$name, $status, $_SESSION['admin_id']]);
    }

    json_response(['success' => true]);
}

if ($action === 'auto_reply') {
    verify_csrf();
    $autoReply = sanitize_text($_POST['auto_reply'] ?? '');
    $workingHours = sanitize_text($_POST['working_hours'] ?? '');

    $stmt = $pdo->query('SELECT id FROM admin_settings LIMIT 1');
    $existing = $stmt->fetch();

    if ($existing) {
        $stmt = $pdo->prepare('UPDATE admin_settings SET auto_reply = ?, working_hours = ? WHERE id = ?');
        $stmt->execute([$autoReply, $workingHours, $existing['id']]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO admin_settings (auto_reply, working_hours) VALUES (?, ?)');
        $stmt->execute([$autoReply, $workingHours]);
    }

    json_response(['success' => true]);
}

if ($action === 'analytics') {
    $activeUsers = $pdo->query('SELECT COUNT(DISTINCT sender_id) AS count FROM messages WHERE sender_role = "user" AND created_at >= NOW() - INTERVAL 1 DAY')->fetchColumn();
    $messagesToday = $pdo->query('SELECT COUNT(*) FROM messages WHERE DATE(created_at) = CURDATE()')->fetchColumn();

    $stmt = $pdo->query('SELECT sender_id, created_at FROM messages WHERE sender_role = "user" ORDER BY created_at DESC LIMIT 50');
    $userMessages = $stmt->fetchAll();
    $avgReplyMinutes = 0;
    $samples = 0;
    foreach ($userMessages as $msg) {
        $stmt = $pdo->prepare('SELECT created_at FROM messages WHERE sender_role = "admin" AND receiver_id = ? AND created_at >= ? ORDER BY created_at ASC LIMIT 1');
        $stmt->execute([$msg['sender_id'], $msg['created_at']]);
        $reply = $stmt->fetch();
        if ($reply) {
            $diff = strtotime($reply['created_at']) - strtotime($msg['created_at']);
            $avgReplyMinutes += max($diff, 0);
            $samples++;
        }
    }
    $avgReply = $samples ? round(($avgReplyMinutes / $samples) / 60, 1) : 0;

    json_response([
        'analytics' => [
            'Active users (24h)' => (int)$activeUsers,
            'Messages today' => (int)$messagesToday,
            'Avg reply time (min)' => $avgReply,
        ]
    ]);
}

if ($action === 'export') {
    $type = $_GET['type'] ?? 'csv';
    $stmt = $pdo->query('SELECT * FROM messages ORDER BY created_at DESC');
    $messages = $stmt->fetchAll();

    if ($type === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="chat_export.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['id', 'sender_id', 'receiver_id', 'sender_role', 'receiver_role', 'message', 'type', 'media_url', 'status', 'created_at']);
        foreach ($messages as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
        exit;
    }

    // Placeholder PDF export. Replace with a proper PDF library in production.
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="chat_export.pdf"');
    echo "%PDF-1.4\n% Generated by WhatsApp-style Chat\n";
    echo "1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF";
    exit;
}

if ($action === 'user_action') {
    $input = json_decode(file_get_contents('php://input'), true);
    $userId = (int)($input['user_id'] ?? 0);
    $field = $input['field'] ?? '';
    $value = (int)($input['value'] ?? 0);

    $allowed = ['is_blocked', 'is_muted', 'is_pinned', 'is_archived'];
    if (!in_array($field, $allowed, true)) {
        json_response(['error' => 'Invalid action'], 422);
    }

    $stmt = $pdo->prepare("UPDATE users SET {$field} = ? WHERE id = ?");
    $stmt->execute([$value, $userId]);
    json_response(['success' => true]);
}

json_response(['error' => 'Invalid action'], 400);
