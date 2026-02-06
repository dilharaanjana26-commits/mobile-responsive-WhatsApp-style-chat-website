<?php
require_once __DIR__ . '/../lib.php';
admin_required();

$stmt = $pdo->prepare('SELECT * FROM admin_users WHERE id = ?');
$stmt->execute([$_SESSION['admin_id']]);
$admin = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body class="bg-gray-100 dark:bg-slate-900 text-gray-900 dark:text-gray-100 min-h-screen">
    <div class="max-w-6xl mx-auto p-4">
        <header class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4">
            <div>
                <h1 class="text-2xl font-semibold">Admin Dashboard</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Welcome back, <?= htmlspecialchars($admin['name']) ?></p>
            </div>
            <div class="flex gap-2">
                <a href="/admin/logout.php" class="px-3 py-2 rounded-lg bg-gray-200 dark:bg-slate-700">Logout</a>
                <button id="toggle-theme" class="px-3 py-2 rounded-lg bg-green-500 text-white">Toggle Mode</button>
            </div>
        </header>

        <div class="grid lg:grid-cols-[280px_1fr] gap-4">
            <aside class="bg-white dark:bg-slate-800 rounded-xl p-4 shadow">
                <h2 class="text-lg font-semibold mb-3">User Chats</h2>
                <div id="user-list" class="space-y-2"></div>
                <div class="mt-6">
                    <h3 class="text-sm font-semibold uppercase text-gray-500">Quick Replies</h3>
                    <div id="quick-replies" class="mt-2 space-y-2"></div>
                    <button id="add-reply" class="mt-3 w-full px-3 py-2 rounded-lg bg-green-500 text-white">Add Quick Reply</button>
                </div>
            </aside>

            <section class="bg-white dark:bg-slate-800 rounded-xl shadow flex flex-col">
                <div class="border-b border-gray-200 dark:border-slate-700 p-4 flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold" id="active-user">Select a chat</h2>
                        <p class="text-sm text-gray-500" id="active-user-meta">No user selected</p>
                    </div>
                    <div class="flex gap-2">
                        <button id="pin-chat" class="px-3 py-2 rounded-lg bg-gray-100 dark:bg-slate-700">Pin</button>
                        <button id="archive-chat" class="px-3 py-2 rounded-lg bg-gray-100 dark:bg-slate-700">Archive</button>
                        <button id="block-chat" class="px-3 py-2 rounded-lg bg-red-500 text-white">Block</button>
                    </div>
                </div>

                <div id="admin-messages" class="flex-1 overflow-y-auto p-4 space-y-4 bg-gray-50 dark:bg-slate-900"></div>

                <div class="border-t border-gray-200 dark:border-slate-700 p-4">
                    <form id="admin-message-form" class="flex items-center gap-2 relative">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="file" id="admin-media" class="hidden" />
                        <button type="button" id="admin-upload" class="p-2 rounded-full bg-gray-100 dark:bg-slate-700">📎</button>
                        <button type="button" id="admin-emoji-btn" class="p-2 rounded-full bg-gray-100 dark:bg-slate-700">😊</button>
                        <button type="button" id="admin-record-btn" class="p-2 rounded-full bg-gray-100 dark:bg-slate-700">🎙️</button>
                        <input id="admin-input" name="message" class="flex-1 px-4 py-2 rounded-full border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800" placeholder="Reply to user" autocomplete="off" />
                        <button type="submit" class="px-4 py-2 rounded-full bg-green-500 text-white">Send</button>
                        <div id="admin-emoji-panel" class="hidden absolute bottom-16 left-4 bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl p-2 shadow-lg">
                            <div class="grid grid-cols-6 gap-2 text-xl">
                                <button type="button" class="emoji">😀</button>
                                <button type="button" class="emoji">😂</button>
                                <button type="button" class="emoji">😍</button>
                                <button type="button" class="emoji">👍</button>
                                <button type="button" class="emoji">🙏</button>
                                <button type="button" class="emoji">🎉</button>
                            </div>
                        </div>
                    </form>
                </div>
            </section>
        </div>

        <div class="grid md:grid-cols-2 gap-4 mt-4">
            <section class="bg-white dark:bg-slate-800 rounded-xl p-4 shadow">
                <h3 class="text-lg font-semibold mb-2">Auto Replies</h3>
                <form id="auto-reply-form" class="space-y-2">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <textarea name="auto_reply" class="w-full px-3 py-2 rounded border border-gray-300 dark:border-slate-600" rows="3" placeholder="Welcome / offline / busy reply"></textarea>
                    <input name="working_hours" class="w-full px-3 py-2 rounded border border-gray-300 dark:border-slate-600" placeholder="Working hours (e.g. 09:00-18:00)" />
                    <button class="px-3 py-2 rounded bg-green-500 text-white">Save</button>
                </form>
            </section>
            <section class="bg-white dark:bg-slate-800 rounded-xl p-4 shadow">
                <h3 class="text-lg font-semibold mb-2">Admin Profile</h3>
                <form id="admin-profile-form" class="space-y-2">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input name="name" class="w-full px-3 py-2 rounded border border-gray-300 dark:border-slate-600" placeholder="Admin name" />
                    <select name="status" class="w-full px-3 py-2 rounded border border-gray-300 dark:border-slate-600">
                        <option value="online">Online</option>
                        <option value="offline">Offline</option>
                        <option value="busy">Busy</option>
                    </select>
                    <input type="file" name="avatar" accept="image/*" class="w-full text-sm" />
                    <button class="px-3 py-2 rounded bg-green-500 text-white">Update Profile</button>
                </form>
            </section>
            <section class="bg-white dark:bg-slate-800 rounded-xl p-4 shadow">
                <h3 class="text-lg font-semibold mb-2">Analytics</h3>
                <ul class="text-sm space-y-2" id="analytics"></ul>
            </section>
            <section class="bg-white dark:bg-slate-800 rounded-xl p-4 shadow">
                <h3 class="text-lg font-semibold mb-2">Export</h3>
                <button id="export-csv" class="w-full px-3 py-2 rounded bg-gray-100 dark:bg-slate-700 mb-2">Export CSV</button>
                <button id="export-pdf" class="w-full px-3 py-2 rounded bg-gray-100 dark:bg-slate-700">Export PDF</button>
            </section>
        </div>
    </div>

    <script>
        const ADMIN_ID = <?= (int)$admin['id'] ?>;
    </script>
    <script src="/assets/js/admin.js"></script>
</body>
</html>
