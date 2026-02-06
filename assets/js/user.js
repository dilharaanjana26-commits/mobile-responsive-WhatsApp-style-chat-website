const messagesEl = document.getElementById('messages');
const form = document.getElementById('message-form');
const messageInput = document.getElementById('message-input');
const mediaInput = document.getElementById('media');
const uploadBtn = document.getElementById('upload-btn');
const emojiBtn = document.getElementById('emoji-btn');
const emojiPanel = document.getElementById('emoji-panel');
const recordBtn = document.getElementById('record-btn');
const profileModal = document.getElementById('profile-modal');
const editProfileBtn = document.getElementById('edit-profile');
const closeModalBtn = document.getElementById('close-modal');
const profileForm = document.getElementById('profile-form');
const typingIndicator = document.getElementById('typing-indicator');
const messageStatus = document.getElementById('message-status');
const messageSound = document.getElementById('message-sound');
const toggleThemeBtn = document.getElementById('toggle-theme');

const themeKey = 'chat-theme';

const fingerprint = async () => {
    const data = [
        navigator.userAgent,
        screen.width,
        screen.height,
        Intl.DateTimeFormat().resolvedOptions().timeZone,
        navigator.language
    ].join('|');

    const encoder = new TextEncoder();
    const hashBuffer = await crypto.subtle.digest('SHA-256', encoder.encode(data));
    return Array.from(new Uint8Array(hashBuffer))
        .map((b) => b.toString(16).padStart(2, '0'))
        .join('');
};

const renderMessage = (message) => {
    const wrapper = document.createElement('div');
    wrapper.className = `flex flex-col ${message.sender === 'user' ? 'items-end' : 'items-start'}`;

    const bubble = document.createElement('div');
    bubble.className = `chat-bubble ${message.sender === 'user' ? 'user' : 'admin'}`;

    if (message.type !== 'text') {
        const link = document.createElement('a');
        link.href = message.media;
        link.target = '_blank';
        link.className = 'underline text-sm';
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
};

const loadMessages = async () => {
    const response = await fetch('/user_api.php?action=poll');
    const data = await response.json();
    if (data.messages) {
        messagesEl.innerHTML = '';
        data.messages.forEach(renderMessage);
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }
    if (data.playSound) {
        messageSound.play().catch(() => {});
    }
};

const sendMessage = async () => {
    const formData = new FormData(form);
    if (mediaInput.files.length) {
        formData.append('media', mediaInput.files[0]);
    }

    const response = await fetch('/user_api.php?action=send', {
        method: 'POST',
        body: formData,
    });
    const data = await response.json();
    if (data.error) {
        alert(data.error);
        return;
    }
    messageInput.value = '';
    mediaInput.value = '';
    messageStatus.textContent = 'Sent';
    await loadMessages();
};

const sendVoiceMessage = async (blob) => {
    const formData = new FormData(form);
    const file = new File([blob], `voice-${Date.now()}.webm`, { type: 'audio/webm' });
    formData.append('media', file);
    const response = await fetch('/user_api.php?action=send', {
        method: 'POST',
        body: formData,
    });
    const data = await response.json();
    if (data.error) {
        alert(data.error);
        return;
    }
    await loadMessages();
};
const saveProfile = async (event) => {
    event.preventDefault();
    const formData = new FormData(profileForm);
    const response = await fetch('/user_api.php?action=profile', {
        method: 'POST',
        body: formData,
    });
    const data = await response.json();
    if (data.error) {
        alert(data.error);
        return;
    }
    window.location.reload();
};

const initSession = async () => {
    const fp = await fingerprint();
    await fetch('/user_api.php?action=init', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ fingerprint: fp }),
    });
    await loadMessages();
};

form.addEventListener('submit', (event) => {
    event.preventDefault();
    sendMessage();
});

uploadBtn.addEventListener('click', () => mediaInput.click());
emojiBtn.addEventListener('click', () => emojiPanel.classList.toggle('hidden'));
emojiPanel.addEventListener('click', (event) => {
    if (event.target.classList.contains('emoji')) {
        messageInput.value += event.target.textContent;
        emojiPanel.classList.add('hidden');
        messageInput.focus();
    }
});
editProfileBtn.addEventListener('click', () => profileModal.classList.remove('hidden'));
closeModalBtn.addEventListener('click', () => profileModal.classList.add('hidden'));
profileForm.addEventListener('submit', saveProfile);

messageInput.addEventListener('input', async () => {
    await fetch('/user_api.php?action=typing', { method: 'POST' });
});

toggleThemeBtn.addEventListener('click', () => {
    const isDark = document.documentElement.classList.toggle('dark');
    localStorage.setItem(themeKey, isDark ? 'dark' : 'light');
});

let mediaRecorder;
let recordedChunks = [];
recordBtn.addEventListener('click', async () => {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        alert('Voice recording not supported in this browser.');
        return;
    }
    if (mediaRecorder && mediaRecorder.state === 'recording') {
        mediaRecorder.stop();
        recordBtn.textContent = '🎙️';
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
    recordBtn.textContent = '⏹️';
});

const storedTheme = localStorage.getItem(themeKey);
if (storedTheme === 'dark') {
    document.documentElement.classList.add('dark');
}

initSession();
setInterval(loadMessages, 2000);
