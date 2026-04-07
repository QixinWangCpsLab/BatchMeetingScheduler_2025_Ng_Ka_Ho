<?php
session_start();

if (!isset($_SESSION['chat_history'])) {
    $_SESSION['chat_history'] = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <link rel="icon" href="images/favicon.ico" />
    <title>PolyU reservation help chatbot</title>
    <link rel="stylesheet" href="styles/bootstrap.min.css">
    <link rel="stylesheet" href="styles/main.css">
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/dompurify@3.2.6/dist/purify.min.js"></script>
</head>
<body class="bg-poly">
<div class="container py-4 py-md-5">
    <main class="w-100 m-auto chatbot-main">
        <div class="card chatbot-shell">
            <div class="card-body p-3 p-md-4 p-xl-5">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                    <div>
                        <h1 class="mb-2 text-poly">Reservation help chatbot</h1>
                        <p class="chatbot-subtitle mb-0">
                            Ask how to create meetings, choose timeslots, check results, or use the reservation system pages.
                        </p>
                    </div>
                    <a class="btn btn-secondary fw-bold" href="index.html">Back to homepage</a>
                </div>

                <div class="chat-box mb-3" id="chatBox">
                    <?php if (empty($_SESSION['chat_history'])): ?>
                        <div class="message-row assistant">
                            <div class="bubble">
                                <div class="meta">Assistant</div>
                                <div class="markdown">
                                    Ask about teacher or student tasks, for example:
                                    <ul>
                                        <li>How do I create a new meeting?</li>
                                        <li>How can a student choose timeslots?</li>
                                        <li>Where do I check allocation results?</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($_SESSION['chat_history'] as $item): ?>
                        <div class="message-row <?php echo $item['role'] === 'user' ? 'user' : 'assistant'; ?>">
                            <div class="bubble">
                                <div class="meta"><?php echo $item['role'] === 'user' ? 'You' : 'Assistant'; ?></div>
                                <?php if ($item['role'] === 'user'): ?>
                                    <div class="markdown"><?php echo nl2br(htmlspecialchars($item['text'])); ?></div>
                                <?php else: ?>
                                    <div class="markdown assistant-markdown"
                                         data-markdown="<?php echo htmlspecialchars($item['text'], ENT_QUOTES); ?>"></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="composer">
                    <form id="chatForm" class="composer-form">
                        <label class="visually-hidden" for="messageInput">Message</label>
                        <textarea id="messageInput" name="message" class="form-control" placeholder="Ask how to use the reservation system..." required></textarea>
                        <button id="sendBtn" type="submit" class="btn btn-poly fw-bold text-white">Send</button>
                    </form>

                    <div class="toolbar mt-3">
                        <div class="status" id="statusText">Ready</div>
                        <button class="btn btn-outline-danger fw-bold" id="clearBtn" type="button">Clear chat</button>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
    const chatBox = document.getElementById('chatBox');
    const chatForm = document.getElementById('chatForm');
    const messageInput = document.getElementById('messageInput');
    const sendBtn = document.getElementById('sendBtn');
    const clearBtn = document.getElementById('clearBtn');
    const statusText = document.getElementById('statusText');

    marked.setOptions({
        breaks: true,
        gfm: true
    });

    function renderMarkdown(md) {
        const raw = marked.parse(md || '');
        return DOMPurify.sanitize(raw);
    }

    function scrollToBottom() {
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    function addMessage(role, text, isTyping = false) {
        const row = document.createElement('div');
        row.className = 'message-row ' + (role === 'user' ? 'user' : 'assistant');

        const bubble = document.createElement('div');
        bubble.className = 'bubble';

        const meta = document.createElement('div');
        meta.className = 'meta';
        meta.textContent = role === 'user' ? 'You' : 'Assistant';

        const content = document.createElement('div');
        content.className = 'markdown';

        if (isTyping) {
            content.innerHTML = '<div class="typing"><span></span><span></span><span></span></div>';
        } else if (role === 'user') {
            content.innerHTML = DOMPurify.sanitize((text || '').replace(/\n/g, '<br>'));
        } else {
            content.innerHTML = renderMarkdown(text);
        }

        bubble.appendChild(meta);
        bubble.appendChild(content);
        row.appendChild(bubble);
        chatBox.appendChild(row);

        scrollToBottom();
        return row;
    }

    function hydrateExistingAssistantMessages() {
        document.querySelectorAll('.assistant-markdown').forEach(el => {
            const md = el.getAttribute('data-markdown') || '';
            el.innerHTML = renderMarkdown(md);
            el.classList.remove('assistant-markdown');
            el.removeAttribute('data-markdown');
        });
    }

    chatForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        const message = messageInput.value.trim();
        if (!message) {
            return;
        }

        addMessage('user', message);
        messageInput.value = '';
        messageInput.focus();

        sendBtn.disabled = true;
        statusText.textContent = 'Assistant is preparing a reply...';

        const typingRow = addMessage('assistant', '', true);

        try {
            const response = await fetch('api_chat.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ message })
            });

            const data = await response.json();
            typingRow.remove();

            if (!response.ok || !data.ok) {
                addMessage('assistant', `**Error:** ${data.error || 'Unknown error'}`);
            } else {
                addMessage('assistant', data.reply || '');
            }
        } catch (error) {
            typingRow.remove();
            addMessage('assistant', `**Network error:** ${error.message}`);
        } finally {
            sendBtn.disabled = false;
            statusText.textContent = 'Ready';
            scrollToBottom();
        }
    });

    clearBtn.addEventListener('click', async function () {
        try {
            const response = await fetch('api_chat.php?action=clear', {
                method: 'POST'
            });

            const data = await response.json();
            if (data.ok) {
                chatBox.innerHTML = '';
                addMessage('assistant', 'Chat cleared. Ask a new question about the reservation system.');
                statusText.textContent = 'Chat cleared';
            } else {
                statusText.textContent = 'Failed to clear chat';
            }
        } catch (error) {
            statusText.textContent = 'Failed to clear chat';
        }
    });

    hydrateExistingAssistantMessages();
    scrollToBottom();
</script>
</body>
</html>
