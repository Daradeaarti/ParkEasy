/**
 * ParkEasy - ParkBot AI Assistant Widget
 * Features: Natural language conversation, real-time database queries,
 * voice input (Web Speech API), voice synthesis audio response, and quick chips.
 */

(function () {
    // Inject CSS for the AI Widget
    const style = document.createElement('style');
    style.innerHTML = `
        .parkbot-widget-btn {
            position: fixed;
            bottom: 24px;
            right: 24px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #f84646, #e02424);
            color: #ffffff;
            box-shadow: 0 8px 25px rgba(248, 70, 70, 0.45);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 99999;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 2px solid rgba(255, 255, 255, 0.3);
        }
        .parkbot-widget-btn:hover {
            transform: scale(1.08) rotate(5deg);
            box-shadow: 0 12px 30px rgba(248, 70, 70, 0.6);
        }
        .parkbot-widget-btn svg {
            width: 30px;
            height: 30px;
            fill: currentColor;
        }
        .parkbot-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            background: #10b981;
            color: white;
            font-size: 10px;
            font-weight: 800;
            padding: 3px 6px;
            border-radius: 10px;
            border: 2px solid white;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.15); }
        }
        .parkbot-modal {
            position: fixed;
            bottom: 96px;
            right: 24px;
            width: 380px;
            max-width: calc(100vw - 32px);
            height: 540px;
            max-height: calc(100vh - 120px);
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.25);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            z-index: 99999;
            transition: all 0.3s ease;
            transform: scale(0.9) translateY(20px);
            opacity: 0;
            pointer-events: none;
            font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            border: 1px solid rgba(0,0,0,0.08);
        }
        .parkbot-modal.open {
            transform: scale(1) translateY(0);
            opacity: 1;
            pointer-events: auto;
        }
        .parkbot-header {
            background: linear-gradient(135deg, #2d2d2d, #1a1a1a);
            color: white;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid #f84646;
        }
        .parkbot-header-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .parkbot-avatar {
            width: 38px;
            height: 38px;
            background: #f84646;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .parkbot-header-text h4 {
            margin: 0;
            font-size: 15px;
            font-weight: 700;
        }
        .parkbot-header-text span {
            font-size: 11px;
            color: #10b981;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .parkbot-close-btn {
            background: transparent;
            border: none;
            color: #999;
            cursor: pointer;
            font-size: 20px;
            padding: 4px;
            line-height: 1;
            transition: color 0.2s;
        }
        .parkbot-close-btn:hover {
            color: white;
        }
        .parkbot-messages {
            flex: 1;
            padding: 16px;
            overflow-y: auto;
            background: #fdfbfb;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .parkbot-msg {
            max-width: 85%;
            padding: 12px 16px;
            border-radius: 16px;
            font-size: 13.5px;
            line-height: 1.5;
            word-break: break-word;
        }
        .parkbot-msg-bot {
            align-self: flex-start;
            background: #ffffff;
            color: #2c3e50;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border-top-left-radius: 4px;
            border: 1px solid #f0e6e6;
        }
        .parkbot-msg-user {
            align-self: flex-end;
            background: #f84646;
            color: #ffffff;
            border-top-right-radius: 4px;
        }
        .parkbot-chips {
            display: flex;
            gap: 6px;
            overflow-x: auto;
            padding: 8px 12px;
            background: #ffffff;
            border-top: 1px solid #f0f0f0;
            scrollbar-width: none;
        }
        .parkbot-chips::-webkit-scrollbar {
            display: none;
        }
        .parkbot-chip {
            background: #fff0f5;
            color: #f84646;
            border: 1px solid #ffd6e0;
            border-radius: 14px;
            padding: 6px 12px;
            font-size: 11.5px;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.2s;
        }
        .parkbot-chip:hover {
            background: #f84646;
            color: #ffffff;
        }
        .parkbot-input-area {
            padding: 12px;
            background: #ffffff;
            display: flex;
            align-items: center;
            gap: 8px;
            border-top: 1px solid #eaeaea;
        }
        .parkbot-input {
            flex: 1;
            border: 1px solid #ddd;
            border-radius: 20px;
            padding: 10px 16px;
            font-size: 13.5px;
            outline: none;
            transition: border-color 0.2s;
        }
        .parkbot-input:focus {
            border-color: #f84646;
        }
        .parkbot-action-btn {
            background: #f84646;
            color: white;
            border: none;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: transform 0.2s;
            flex-shrink: 0;
        }
        .parkbot-action-btn:hover {
            transform: scale(1.08);
        }
        .parkbot-voice-btn {
            background: #f3f4f6;
            color: #4b5563;
        }
        .parkbot-voice-btn.listening {
            background: #ef4444;
            color: white;
            animation: pulse 1s infinite;
        }
        .parkbot-typing {
            display: flex;
            gap: 4px;
            padding: 8px 12px;
            align-self: flex-start;
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #eee;
        }
        .parkbot-dot {
            width: 6px;
            height: 6px;
            background: #aaa;
            border-radius: 50%;
            animation: bounce 1.2s infinite ease-in-out;
        }
        .parkbot-dot:nth-child(2) { animation-delay: 0.2s; }
        .parkbot-dot:nth-child(3) { animation-delay: 0.4s; }
        @keyframes bounce {
            0%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-6px); }
        }
    `;
    document.head.appendChild(style);

    // Create DOM structure
    const widgetBtn = document.createElement('div');
    widgetBtn.className = 'parkbot-widget-btn';
    widgetBtn.id = 'parkbot-btn';
    widgetBtn.innerHTML = `
        <span class="parkbot-badge">AI</span>
        <svg viewBox="0 0 24 24"><path d="M12 2a2 2 0 0 1 2 2c0 .74-.4 1.38-1 1.72V7h4a3 3 0 0 1 3 3v8a3 3 0 0 1-3 3H7a3 3 0 0 1-3-3v-8a3 3 0 0 1 3-3h4V5.72c-.6-.34-1-.98-1-1.72a2 2 0 0 1 2-2M7.5 13a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3m9 0a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3M8 17h8a1 1 0 0 0 0-2H8a1 1 0 0 0 0 2"/></svg>
    `;

    const modal = document.createElement('div');
    modal.className = 'parkbot-modal';
    modal.id = 'parkbot-modal';
    modal.innerHTML = `
        <div class="parkbot-header">
            <div class="parkbot-header-info">
                <div class="parkbot-avatar">
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="white"><path d="M12 2a2 2 0 0 1 2 2c0 .74-.4 1.38-1 1.72V7h4a3 3 0 0 1 3 3v8a3 3 0 0 1-3 3H7a3 3 0 0 1-3-3v-8a3 3 0 0 1 3-3h4V5.72c-.6-.34-1-.98-1-1.72a2 2 0 0 1 2-2M7.5 13a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3m9 0a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3M8 17h8a1 1 0 0 0 0-2H8a1 1 0 0 0 0 2"/></svg>
                </div>
                <div class="parkbot-header-text">
                    <h4>ParkBot AI Assistant</h4>
                    <span>● Online • Real-time Data</span>
                </div>
            </div>
            <button class="parkbot-close-btn" id="parkbot-close">&times;</button>
        </div>
        <div class="parkbot-messages" id="parkbot-messages">
            <div class="parkbot-msg parkbot-msg-bot">
                👋 Hello! I am <strong>ParkBot AI</strong>. How can I help you find or book parking today?
            </div>
        </div>
        <div class="parkbot-chips">
            <div class="parkbot-chip" data-q="Where is the cheapest parking?">💰 Cheapest Parking</div>
            <div class="parkbot-chip" data-q="Which spots have EV charging?">⚡ EV Charging</div>
            <div class="parkbot-chip" data-q="Which lots have the most open slots?">🟢 Top Open Slots</div>
            <div class="parkbot-chip" data-q="Parking near Kalyan station">📍 Kalyan Station</div>
            <div class="parkbot-chip" data-q="How do I cancel my booking?">🔄 Cancel Booking</div>
        </div>
        <div class="parkbot-input-area">
            <input type="text" class="parkbot-input" id="parkbot-input" placeholder="Ask anything about parking..." />
            <button class="parkbot-action-btn parkbot-voice-btn" id="parkbot-voice" title="Speak to AI">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3m5.3-3c0 3-2.54 5.1-5.3 5.1S6.7 14 6.7 11H5c0 3.41 2.72 6.23 6 6.72V21h2v-3.28c3.28-.48 6-3.3 6-6.72z"/></svg>
            </button>
            <button class="parkbot-action-btn" id="parkbot-send" title="Send message">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
            </button>
        </div>
    `;

    document.body.appendChild(widgetBtn);
    document.body.appendChild(modal);

    // DOM Elements
    const modalEl = document.getElementById('parkbot-modal');
    const closeBtn = document.getElementById('parkbot-close');
    const sendBtn = document.getElementById('parkbot-send');
    const voiceBtn = document.getElementById('parkbot-voice');
    const inputEl = document.getElementById('parkbot-input');
    const messagesEl = document.getElementById('parkbot-messages');
    const chips = document.querySelectorAll('.parkbot-chip');

    // Toggle Modal
    widgetBtn.addEventListener('click', () => {
        modalEl.classList.toggle('open');
        if (modalEl.classList.contains('open')) {
            inputEl.focus();
        }
    });

    closeBtn.addEventListener('click', () => {
        modalEl.classList.remove('open');
    });

    // Helper to format markdown boldly
    function formatText(text) {
        return text
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>')
            .replace(/\n/g, '<br>');
    }

    // Append Message
    function appendMessage(sender, text) {
        const msgDiv = document.createElement('div');
        msgDiv.className = `parkbot-msg parkbot-msg-${sender}`;
        msgDiv.innerHTML = formatText(text);
        messagesEl.appendChild(msgDiv);
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    // Typing indicator
    function showTyping() {
        const typing = document.createElement('div');
        typing.className = 'parkbot-typing';
        typing.id = 'parkbot-typing';
        typing.innerHTML = '<div class="parkbot-dot"></div><div class="parkbot-dot"></div><div class="parkbot-dot"></div>';
        messagesEl.appendChild(typing);
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    function removeTyping() {
        const el = document.getElementById('parkbot-typing');
        if (el) el.remove();
    }

    // Send Message logic
    async function sendMessage(text) {
        const query = text || inputEl.value.trim();
        if (!query) return;

        appendMessage('user', query);
        inputEl.value = '';
        showTyping();

        try {
            const res = await fetch('api_ai_chat.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: query })
            });
            const data = await res.json();
            removeTyping();

            if (data && data.reply) {
                appendMessage('bot', data.reply);
            } else {
                appendMessage('bot', 'I could not process that at the moment. Please try again.');
            }
        } catch (err) {
            removeTyping();
            appendMessage('bot', '⚠️ Connection error. Please check your network or try again.');
        }
    }

    sendBtn.addEventListener('click', () => sendMessage());
    inputEl.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') sendMessage();
    });

    // Quick Chips click
    chips.forEach(chip => {
        chip.addEventListener('click', () => {
            const q = chip.getAttribute('data-q');
            sendMessage(q);
        });
    });

    // Web Speech API for Free Voice Input
    if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        const recognition = new SpeechRecognition();
        recognition.continuous = false;
        recognition.interimResults = false;
        recognition.lang = 'en-IN';

        let isListening = false;

        voiceBtn.addEventListener('click', () => {
            if (isListening) {
                recognition.stop();
            } else {
                recognition.start();
            }
        });

        recognition.onstart = () => {
            isListening = true;
            voiceBtn.classList.add('listening');
            inputEl.placeholder = 'Listening... Speak now';
        };

        recognition.onresult = (event) => {
            const transcript = event.results[0][0].transcript;
            inputEl.value = transcript;
            sendMessage(transcript);
        };

        recognition.onend = () => {
            isListening = false;
            voiceBtn.classList.remove('listening');
            inputEl.placeholder = 'Ask anything about parking...';
        };

        recognition.onerror = () => {
            isListening = false;
            voiceBtn.classList.remove('listening');
            inputEl.placeholder = 'Ask anything about parking...';
        };
    } else {
        voiceBtn.style.display = 'none';
    }
})();
