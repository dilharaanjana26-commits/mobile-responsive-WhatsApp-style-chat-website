const userListEl = document.getElementById('user-list');
const messagesEl = document.getElementById('admin-messages');
const activeUserEl = document.getElementById('active-user');
const activeUserMetaEl = document.getElementById('active-user-meta');
const adminForm = document.getElementById('admin-message-form');
const adminInput = document.getElementById('admin-input');
const adminMedia = document.getElementById('admin-media');
const adminUpload = document.getElementById('admin-upload');
const adminEmojiBtn = document.getElementById('admin-emoji-btn');
const adminEmojiPanel = document.getElementById('admin-emoji-panel');
const adminRecordBtn = document.getElementById('admin-record-btn');
const quickRepliesEl = document.getElementById('quick-replies');
const addReplyBtn = document.getElementById('add-reply');
const analyticsEl = document.getElementById('analytics');
const autoReplyForm = document.getElementById('auto-reply-form');
const adminProfileForm = document.getElementById('admin-profile-form');
const exportCsvBtn = document.getElementById('export-csv');
const exportPdfBtn = document.getElementById('export-pdf');
const toggleThemeBtn = document.getElementById('toggle-theme');
const pinChatBtn = document.getElementById('pin-chat');
const archiveChatBtn = document.getElementById('archive-chat');
const blockChatBtn = document.getElementById('block-chat');

let activeUserId = null;
let quickReplies = [];

const renderUsers = (users) => {
    userListEl.innerHTML = '';
    users.forEach((user) => {
        const btn = document.createElement('button');
        btn.className = `w-full text-left px-3 py-2 rounded-lg border ${activeUserId === user.id ? 'border-green-500 bg-green-50' : 'border-transparent hover:bg-gray-100 dark:hover:bg-slate-700'}`;
        btn.innerHTML = `<div class="font-semibold">${user.name}</div><div class="text-xs text-gray-500">${user.last_message}</div>`;
        btn.addEventListener('click', () => {
            activeUserId = user.id;
            activeUserEl.textContent = user.name;
            activeUserMetaEl.textContent = user.status;
            loadMessages();
            loadUsers();
        });
        userListEl.appendChild(btn);
    });
};

const renderMessages = (messages) => {
    messagesEl.innerHTML = '';
    messages.forEach((message) => {
        const wrapper = document.createElement('div');
        wrapper.className = `flex flex-col ${message.sender === 'admin' ? 'items-end' : 'items-start'}`;
        const bubble = document.createElement('div');
        bubble.className = `chat-bubble ${message.sender === 'admin' ? 'user' : 'admin'}`;
        if (message.type !== 'text') {
            const link = document.createElement('a');
            link.href = message.media;
            link.target = '_blank';
            link.textContent = `View ${message.type}`;
            bubble.appendChild(link);
        }
        if (message.text) {
            const text = document.createElement('p');
            text.textContent = message.text;
            bubble.appendChild(text);
        }
        const meta = document.createElement('div');
        meta.className = 'message-meta';
        meta.textContent = `${message.time} · ${message.status}`;
        bubble.appendChild(meta);
        wrapper.appendChild(bubble);
        messagesEl.appendChild(wrapper);
    });
    messagesEl.scrollTop = messagesEl.scrollHeight;
};

const loadUsers = async () => {
    const response = await fetch('/admin/admin_api.php?action=users');
    const data = await response.json();
    if (data.users) {
        renderUsers(data.users);
    }
};

const loadMessages = async () => {
    if (!activeUserId) {
        messagesEl.innerHTML = '<p class="text-sm text-gray-500">Select a user to start chatting.</p>';
        return;
    }
    const response = await fetch(`/admin/admin_api.php?action=messages&user_id=${activeUserId}`);
    const data = await response.json();
    if (data.messages) {
        renderMessages(data.messages);
    }
};

const sendMessage = async () => {
    if (!activeUserId) {
        alert('Select a user first.');
        return;
    }
    const formData = new FormData(adminForm);
    formData.append('user_id', activeUserId);
    if (adminMedia.files.length) {
        formData.append('media', adminMedia.files[0]);
    }
    const response = await fetch('/admin/admin_api.php?action=send', {
        method: 'POST',
        body: formData,
    });
    const data = await response.json();
    if (data.error) {
        alert(data.error);
        return;
    }
    adminInput.value = '';
    adminMedia.value = '';
    loadMessages();
};

const sendVoiceMessage = async (blob) => {
    if (!activeUserId) {
        return;
    }
    const formData = new FormData(adminForm);
    const file = new File([blob], `voice-${Date.now()}.webm`, { type: 'audio/webm' });
    formData.append('user_id', activeUserId);
    formData.append('media', file);
    const response = await fetch('/admin/admin_api.php?action=send', {
        method: 'POST',
        body: formData,
    });
    const data = await response.json();
    if (data.error) {
        alert(data.error);
        return;
    }
    loadMessages();
};

const loadQuickReplies = async () => {
    const response = await fetch('/admin/admin_api.php?action=quick_replies');
    const data = await response.json();
    quickReplies = data.replies || [];
    quickRepliesEl.innerHTML = '';
    quickReplies.forEach((reply) => {
        const btn = document.createElement('button');
        btn.className = 'w-full text-left px-3 py-2 rounded-lg bg-gray-100 dark:bg-slate-700 text-sm';
        btn.textContent = reply.title;
        btn.addEventListener('click', () => {
            adminInput.value = reply.body;
        });
        quickRepliesEl.appendChild(btn);
    });
};

const addQuickReply = async () => {
    const title = prompt('Quick reply title');
    const body = prompt('Quick reply text');
    if (!title || !body) {
        return;
    }
    const response = await fetch('/admin/admin_api.php?action=quick_reply_add', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ title, body }),
    });
    const data = await response.json();
    if (data.success) {
        loadQuickReplies();
    }
};

const loadAnalytics = async () => {
    const response = await fetch('/admin/admin_api.php?action=analytics');
    const data = await response.json();
    analyticsEl.innerHTML = '';
    Object.entries(data.analytics || {}).forEach(([key, value]) => {
        const li = document.createElement('li');
        li.textContent = `${key}: ${value}`;
        analyticsEl.appendChild(li);
    });
};

const saveAutoReply = async (event) => {
    event.preventDefault();
    const formData = new FormData(autoReplyForm);
    const response = await fetch('/admin/admin_api.php?action=auto_reply', {
        method: 'POST',
        body: formData,
    });
    const data = await response.json();
    if (!data.success) {
        alert(data.error || 'Unable to save');
    }
};

const loadAdminProfile = async () => {
    const response = await fetch('/admin/admin_api.php?action=profile');
    const data = await response.json();
    if (data.profile) {
        adminProfileForm.name.value = data.profile.name;
        adminProfileForm.status.value = data.profile.status;
    }
};

const saveAdminProfile = async (event) => {
    event.preventDefault();
    const formData = new FormData(adminProfileForm);
    const response = await fetch('/admin/admin_api.php?action=profile_update', {
        method: 'POST',
        body: formData,
    });
    const data = await response.json();
    if (!data.success) {
        alert(data.error || 'Unable to update profile');
    }
};

const exportData = (type) => {
    window.location.href = `/admin/admin_api.php?action=export&type=${type}`;
};

const updateUserFlag = async (field, value) => {
    if (!activeUserId) {
        alert('Select a user first.');
        return;
    }
    const response = await fetch('/admin/admin_api.php?action=user_action', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ user_id: activeUserId, field, value }),
    });
    const data = await response.json();
    if (data.success) {
        loadUsers();
    }
};

adminForm.addEventListener('submit', (event) => {
    event.preventDefault();
    sendMessage();
});

adminUpload.addEventListener('click', () => adminMedia.click());
adminEmojiBtn.addEventListener('click', () => adminEmojiPanel.classList.toggle('hidden'));
adminEmojiPanel.addEventListener('click', (event) => {
    if (event.target.classList.contains('emoji')) {
        adminInput.value += event.target.textContent;
        adminEmojiPanel.classList.add('hidden');
        adminInput.focus();
    }
});

let mediaRecorder;
let recordedChunks = [];
adminRecordBtn.addEventListener('click', async () => {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        alert('Voice recording not supported in this browser.');
        return;
    }
    if (mediaRecorder && mediaRecorder.state === 'recording') {
        mediaRecorder.stop();
        adminRecordBtn.textContent = '🎙️';
        return;
    }
    const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
    mediaRecorder = new MediaRecorder(stream);
    recordedChunks = [];
    mediaRecorder.ondataavailable = (event) => {
        if (event.data.size > 0) {
            recordedChunks.push(event.data);
        }
    };
    mediaRecorder.onstop = () => {
        const blob = new Blob(recordedChunks, { type: 'audio/webm' });
        sendVoiceMessage(blob);
        stream.getTracks().forEach((track) => track.stop());
    };
    mediaRecorder.start();
    adminRecordBtn.textContent = '⏹️';
});
addReplyBtn.addEventListener('click', addQuickReply);
autoReplyForm.addEventListener('submit', saveAutoReply);
adminProfileForm.addEventListener('submit', saveAdminProfile);
exportCsvBtn.addEventListener('click', () => exportData('csv'));
exportPdfBtn.addEventListener('click', () => exportData('pdf'));
pinChatBtn.addEventListener('click', () => updateUserFlag('is_pinned', 1));
archiveChatBtn.addEventListener('click', () => updateUserFlag('is_archived', 1));
blockChatBtn.addEventListener('click', () => updateUserFlag('is_blocked', 1));

toggleThemeBtn.addEventListener('click', () => {
    document.documentElement.classList.toggle('dark');
});

loadUsers();
loadMessages();
loadQuickReplies();
loadAnalytics();
loadAdminProfile();
setInterval(() => {
    loadUsers();
    loadMessages();
}, 2000);
