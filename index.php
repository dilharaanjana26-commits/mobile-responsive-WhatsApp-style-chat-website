<?php
require_once __DIR__ . '/lib.php';
$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsApp-style Chat</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body class="bg-gray-100 dark:bg-slate-900 text-gray-900 dark:text-gray-100 min-h-screen">
    <div class="max-w-5xl mx-auto p-4">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg overflow-hidden">
            <header class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-slate-700">
                <div>
                    <h1 class="text-xl font-semibold">Support Chat</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400" id="admin-status">Admin is online</p>
                </div>
                <button id="toggle-theme" class="px-3 py-2 rounded-full bg-gray-100 dark:bg-slate-700 text-sm">Toggle Mode</button>
            </header>

            <div class="grid md:grid-cols-[300px_1fr]">
                <aside class="border-r border-gray-200 dark:border-slate-700 p-4">
                    <div class="flex items-center gap-3">
                        <img id="user-avatar" src="<?= $user['avatar'] ?? '/assets/images/default-avatar.svg' ?>" class="w-12 h-12 rounded-full object-cover" alt="Your avatar">
                        <div>
                            <p class="font-semibold" id="user-name"><?= htmlspecialchars($user['name'] ?? 'New User') ?></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Device session (30 days)</p>
                        </div>
                    </div>
                    <button id="edit-profile" class="mt-4 w-full px-3 py-2 rounded-lg bg-green-500 text-white">Edit profile</button>
                    <div class="mt-6">
                        <h2 class="text-sm font-semibold uppercase text-gray-500">Quick actions</h2>
                        <div class="mt-3 space-y-2 text-sm">
                            <div class="flex justify-between"><span>Typing...</span><span id="typing-indicator" class="text-green-500 hidden">Admin is typing</span></div>
                            <div class="flex justify-between"><span>Message status</span><span id="message-status">Idle</span></div>
                        </div>
                    </div>
                </aside>

                <main class="flex flex-col h-[70vh]">
                    <div id="messages" class="flex-1 overflow-y-auto p-4 space-y-4 bg-gray-50 dark:bg-slate-900"></div>

                    <div class="border-t border-gray-200 dark:border-slate-700 p-4">
                        <form id="message-form" class="flex items-center gap-2 relative">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="file" id="media" name="media" class="hidden" />
                            <button type="button" id="upload-btn" class="p-2 rounded-full bg-gray-100 dark:bg-slate-700">📎</button>
                            <button type="button" id="emoji-btn" class="p-2 rounded-full bg-gray-100 dark:bg-slate-700">😊</button>
                            <button type="button" id="record-btn" class="p-2 rounded-full bg-gray-100 dark:bg-slate-700">🎙️</button>
                            <input id="message-input" name="message" class="flex-1 px-4 py-2 rounded-full border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800" placeholder="Type a message" autocomplete="off" />
                            <button type="submit" class="px-4 py-2 rounded-full bg-green-500 text-white">Send</button>
                            <div id="emoji-panel" class="hidden absolute bottom-16 left-4 bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl p-2 shadow-lg">
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
                        <div class="text-xs text-gray-400 mt-2">Supports text, images, videos, audio, PDF, ZIP. Voice recorder coming in Phase 2.</div>
                    </div>
                </main>
            </div>
        </div>
    </div>

    <div id="profile-modal" class="fixed inset-0 bg-black/40 hidden items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl p-6 w-full max-w-md">
            <h2 class="text-lg font-semibold mb-4">Profile</h2>
            <form id="profile-form" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <div>
                    <label class="block text-sm mb-1">Name</label>
                    <input name="name" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
                </div>
                <div>
                    <label class="block text-sm mb-1">Avatar</label>
                    <input type="file" name="avatar" accept="image/*" class="w-full text-sm">
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" id="close-modal" class="px-4 py-2 rounded-lg bg-gray-200 dark:bg-slate-700">Cancel</button>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-green-500 text-white">Save</button>
                </div>
            </form>
        </div>
    </div>

    <audio id="message-sound" src="/assets/sounds/notify.mp3" preload="auto"></audio>

    <script>
        const USER_ID = <?= $user['id'] ?? 'null' ?>;
    </script>
    <script src="/assets/js/user.js"></script>
</body>
</html>
