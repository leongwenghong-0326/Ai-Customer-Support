/**
 * NovaServe AI - Real-time Interactive Chat Client Engine
 * Fully integrated multi-lingual auto-detection (EN/ZH/MS), STT speech input, and TTS voice playback
 */

document.addEventListener('DOMContentLoaded', () => {
    const chatContainer = document.getElementById('chatMessagesContainer');
    const messageInput = document.getElementById('chatMessageInput');
    const sendBtn = document.getElementById('chatSendBtn');
    const voiceInputBtn = document.getElementById('voiceInputBtn');
    const voiceLangBadge = document.getElementById('voiceLangBadge');
    const clearChatBtn = document.getElementById('clearChatBtn');
    const escalateBtn = document.getElementById('escalateToHumanBtn');
    const resolveChatBtn = document.getElementById('resolveChatBtn');
    const chatStatusBadge = document.getElementById('chatStatusBadge');
    const suggestedChips = document.querySelectorAll('.chip-btn');
    const activeConvIdInput = document.getElementById('activeConversationId');

    const voice = new VoiceController();
    window.voiceController = voice;

    // Last detected natural language across conversation (default 'en')
    let currentConversationLang = 'en';

    // Auto-detect language from initial UI text
    const initChatRows = document.querySelectorAll('.chat-message-row.ai .chat-text');
    if (initChatRows.length > 0) {
        const lastSample = initChatRows[initChatRows.length - 1].innerText;
        currentConversationLang = voice.detectLanguage(lastSample);
    }

    const updateVoiceLangBadge = (lang) => {
        if (!voiceLangBadge) return;
        const upper = (lang || 'en').toUpperCase();
        voiceLangBadge.textContent = upper;
        if (lang === 'zh') {
            voiceLangBadge.className = 'badge bg-danger-soft text-danger border border-danger-subtle';
        } else if (lang === 'ms') {
            voiceLangBadge.className = 'badge bg-warning-soft text-warning border border-warning-subtle';
        } else {
            voiceLangBadge.className = 'badge bg-primary-soft text-primary border border-primary-subtle';
        }
    };
    updateVoiceLangBadge(currentConversationLang);

    // Scroll chat to bottom
    const scrollToBottom = () => {
        if (chatContainer) {
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }
    };
    scrollToBottom();

    // Voice Input Toggle (Speech Recognition)
    if (voiceInputBtn) {
        if (!voice.isRecognitionSupported()) {
            voiceInputBtn.classList.add('disabled');
            voiceInputBtn.title = 'Speech recognition not supported in this browser. Please use Chrome, Edge, or Safari.';
        } else {
            voiceInputBtn.addEventListener('click', () => {
                if (voice.isRecording) {
                    voice.stopListening();
                    voiceInputBtn.classList.remove('btn-voice-recording');
                    voiceInputBtn.innerHTML = '<i class="fa-solid fa-microphone"></i><span class="badge bg-primary-soft text-primary border border-primary-subtle" id="voiceLangBadge" style="font-size: 0.65rem; padding: 2px 4px;">' + currentConversationLang.toUpperCase() + '</span>';
                } else {
                    // Check if messageInput already has text to detect language, or fallback to current conversation language
                    let targetLang = currentConversationLang;
                    if (messageInput.value.trim()) {
                        targetLang = voice.detectLanguage(messageInput.value.trim());
                    }
                    voice.setRecognitionLanguage(targetLang);
                    updateVoiceLangBadge(targetLang);

                    let placeholderText = 'Listening... Speak clearly into microphone';
                    if (targetLang === 'zh') {
                        placeholderText = '正在倾听... 请对着麦克风说话（支持普通话）';
                    } else if (targetLang === 'ms') {
                        placeholderText = 'Sedang mendengar... Sila bercakap melalui mikrofon';
                    }

                    voice.startListening({
                        onStart: () => {
                            voiceInputBtn.classList.add('btn-voice-recording');
                            voiceInputBtn.innerHTML = '<i class="fa-solid fa-microphone-lines text-danger"></i>';
                            messageInput.placeholder = placeholderText;
                        },
                        onResult: (text) => {
                            messageInput.value = text;
                            // Re-detect language dynamically from recognized speech
                            const detected = voice.detectLanguage(text);
                            if (detected !== currentConversationLang) {
                                currentConversationLang = detected;
                                updateVoiceLangBadge(detected);
                            }
                        },
                        onError: (err) => {
                            voiceInputBtn.classList.remove('btn-voice-recording');
                            voiceInputBtn.innerHTML = '<i class="fa-solid fa-microphone"></i><span class="badge bg-primary-soft text-primary border border-primary-subtle" id="voiceLangBadge" style="font-size: 0.65rem; padding: 2px 4px;">' + currentConversationLang.toUpperCase() + '</span>';
                            messageInput.placeholder = 'Type your question or message...';
                            if (err !== 'no-speech') {
                                window.showToast?.('Voice error: ' + err, 'danger');
                            }
                        },
                        onEnd: () => {
                            voiceInputBtn.classList.remove('btn-voice-recording');
                            voiceInputBtn.innerHTML = '<i class="fa-solid fa-microphone"></i><span class="badge bg-primary-soft text-primary border border-primary-subtle" id="voiceLangBadge" style="font-size: 0.65rem; padding: 2px 4px;">' + currentConversationLang.toUpperCase() + '</span>';
                            messageInput.placeholder = 'Type your question or message...';
                        }
                    });
                }
            });
        }
    }

    // Append User Message to UI
    const appendUserMessage = (text) => {
        const timeStr = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        const html = `
            <div class="chat-message-row user">
                <div class="user-avatar-circle flex-shrink-0">
                    <i class="fa-solid fa-user"></i>
                </div>
                <div class="chat-bubble">
                    <div class="chat-text">${escapeHtml(text)}</div>
                    <div class="chat-bubble-meta text-end">${timeStr}</div>
                </div>
            </div>
        `;
        chatContainer.insertAdjacentHTML('beforeend', html);
        scrollToBottom();
    };

    // Show Typing Indicator
    const showTypingIndicator = () => {
        const id = 'typing_' + Date.now();
        const html = `
            <div class="chat-message-row ai" id="${id}">
                <div class="brand-logo-icon flex-shrink-0" style="width:30px;height:30px;font-size:0.9rem;">
                    <i class="fa-solid fa-brain text-primary"></i>
                </div>
                <div class="typing-indicator">
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                </div>
            </div>
        `;
        chatContainer.insertAdjacentHTML('beforeend', html);
        scrollToBottom();
        return id;
    };

    // Append AI Message to UI
    const appendAIMessage = (data) => {
        const timeStr = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        const msgId = data.message_id || Date.now();
        const convId = data.conversation_id || (activeConvIdInput ? activeConvIdInput.value : '');
        const detectedLang = data.detected_language || currentConversationLang || 'en';

        // Format sources if present
        let sourcesHtml = '';
        if (data.sources && data.sources.length > 0) {
            const listItems = data.sources.map(s => `<li><i class="fa-solid fa-bookmark me-1 text-primary"></i> <strong>${escapeHtml(s.title)}</strong></li>`).join('');
            sourcesHtml = `
                <div class="sources-card">
                    <div class="fw-bold mb-1 text-primary"><i class="fa-solid fa-check-double me-1"></i> Verified References:</div>
                    <ul class="mb-0 ps-3">${listItems}</ul>
                </div>
            `;
        }

        // Confidence badge
        let confidenceHtml = '';
        if (data.confidence) {
            const confClass = data.confidence >= 80 ? 'text-success' : (data.confidence >= 65 ? 'text-warning' : 'text-danger');
            confidenceHtml = `<span class="badge bg-card-dark ${confClass} border border-secondary-subtle ms-2"><i class="fa-solid fa-gauge-simple me-1"></i>${data.confidence}%</span>`;
        }

        const html = `
            <div class="chat-message-row ai" data-message-id="${msgId}" data-lang="${escapeHtml(detectedLang)}">
                <div class="brand-logo-icon flex-shrink-0" style="width:30px;height:30px;font-size:0.9rem;">
                    <i class="fa-solid fa-brain text-primary"></i>
                </div>
                <div class="chat-bubble">
                    <div class="chat-text" style="white-space: pre-wrap;">${escapeHtml(data.answer)}</div>
                    ${sourcesHtml}
                    <div class="d-flex justify-content-between align-items-center mt-2 pt-1 border-top border-secondary-subtle">
                        <div class="feedback-actions">
                            <button class="btn-feedback" onclick="rateMessage(${convId}, ${msgId}, 'positive', this)" title="Helpful">
                                <i class="fa-regular fa-thumbs-up"></i> Helpful
                            </button>
                            <button class="btn-feedback" onclick="rateMessage(${convId}, ${msgId}, 'negative', this)" title="Not helpful">
                                <i class="fa-regular fa-thumbs-down"></i> Not Helpful
                            </button>
                            <button class="btn-feedback ms-1" onclick="speakAIMessage(this)" title="Listen to response (TTS)">
                                <i class="fa-solid fa-volume-high"></i>
                            </button>
                        </div>
                        <div class="chat-bubble-meta d-flex align-items-center">
                            <span>${timeStr}</span>
                            ${confidenceHtml}
                        </div>
                    </div>
                </div>
            </div>
        `;
        chatContainer.insertAdjacentHTML('beforeend', html);
        scrollToBottom();
    };

    // Send Message Handler
    const sendMessage = async () => {
        const text = messageInput.value.trim();
        if (!text) return;

        // Auto detect input language
        const autoLang = voice.detectLanguage(text);
        currentConversationLang = autoLang;
        updateVoiceLangBadge(autoLang);
        voice.setRecognitionLanguage(autoLang);

        messageInput.value = '';
        messageInput.focus();
        appendUserMessage(text);

        const typingId = showTypingIndicator();
        sendBtn.disabled = true;

        try {
            const convId = activeConvIdInput ? activeConvIdInput.value : '';

            const response = await fetch('api/chat.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    message: text,
                    conversation_id: convId,
                    language: autoLang
                })
            });

            const typingEl = document.getElementById(typingId);
            if (typingEl) typingEl.remove();

            const resJson = await response.json();
            if (resJson.success) {
                if (activeConvIdInput && resJson.conversation_id) {
                    activeConvIdInput.value = resJson.conversation_id;
                }
                if (resJson.detected_language) {
                    currentConversationLang = resJson.detected_language;
                    updateVoiceLangBadge(currentConversationLang);
                    voice.setRecognitionLanguage(currentConversationLang);
                }
                appendAIMessage(resJson);

                // If reopened or active, clear resolved banner and restore status badge
                const existingResolvedBanner = document.getElementById('chatResolvedBanner');
                if (existingResolvedBanner) {
                    existingResolvedBanner.remove();
                }
                if (chatStatusBadge) {
                    if (resJson.is_escalated) {
                        chatStatusBadge.className = 'badge bg-warning-soft text-warning border border-warning-subtle d-flex align-items-center gap-1';
                        chatStatusBadge.innerHTML = '<i class="fa-solid fa-headset"></i> Live Specialist';
                    } else {
                        chatStatusBadge.className = 'badge bg-success-soft text-success border border-success-subtle d-flex align-items-center gap-1';
                        chatStatusBadge.innerHTML = '<span class="status-indicator-dot me-1"></span> 24/7 Online';
                    }
                }
                if (resolveChatBtn) {
                    resolveChatBtn.style.display = '';
                }
                if (messageInput) {
                    messageInput.placeholder = 'Type your question or message...';
                }

                // If escalated, show banner
                if (resJson.is_escalated) {
                    const noticeHtml = `
                        <div class="alert alert-warning py-2 px-3 small border-0 shadow-sm my-2 text-center">
                            <i class="fa-solid fa-user-shield me-2"></i>
                            <strong>Human Support Alert:</strong> A support specialist has been queued to assist with this conversation.
                        </div>
                    `;
                    chatContainer.insertAdjacentHTML('beforeend', noticeHtml);
                    scrollToBottom();
                }
            } else {
                appendAIMessage({
                    answer: resJson.error || 'A temporary communication error occurred. Please try again.',
                    confidence: 0
                });
            }
        } catch (err) {
            const typingEl = document.getElementById(typingId);
            if (typingEl) typingEl.remove();
            appendAIMessage({
                answer: 'Network connectivity glitch. Please verify your connection or try again shortly.',
                confidence: 0
            });
        } finally {
            sendBtn.disabled = false;
        }
    };

    // Event Listeners
    if (sendBtn) {
        sendBtn.addEventListener('click', sendMessage);
    }
    if (messageInput) {
        messageInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        // Real-time language detection while user types
        messageInput.addEventListener('input', () => {
            const val = messageInput.value.trim();
            if (val.length >= 2) {
                const liveLang = voice.detectLanguage(val);
                if (liveLang !== currentConversationLang) {
                    currentConversationLang = liveLang;
                    updateVoiceLangBadge(liveLang);
                    voice.setRecognitionLanguage(liveLang);
                }
            }
        });
    }

    // Suggested Chips Click
    suggestedChips.forEach(chip => {
        chip.addEventListener('click', () => {
            messageInput.value = chip.innerText.trim();
            sendMessage();
        });
    });

    // Clear / Restart Chat
    if (clearChatBtn) {
        clearChatBtn.addEventListener('click', () => {
            if (confirm('Restart chat and start a fresh session?')) {
                window.location.href = 'chat.php?new=1';
            }
        });
    }

    // Escalate to Human Button
    if (escalateBtn) {
        escalateBtn.addEventListener('click', async () => {
            const convId = activeConvIdInput ? activeConvIdInput.value : '';
            if (!convId) {
                window.showToast?.('Please send a message first before escalating.', 'warning');
                return;
            }

            if (!confirm('Would you like to request an immediate takeover by human support staff?')) {
                return;
            }

            try {
                const response = await fetch('api/escalate.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        conversation_id: convId,
                        reason: 'Customer initiated human takeover request'
                    })
                });
                const resJson = await response.json();
                if (resJson.success) {
                    window.showToast?.('Human escalation requested. A support specialist is joining shortly!', 'success');
                    const alertHtml = `
                        <div class="alert alert-info py-2 px-3 small border-0 shadow-sm my-2 text-center">
                            <i class="fa-solid fa-headset me-2"></i>
                            <strong>Connected to Human Queue:</strong> Support engineers have been dispatched to this live thread.
                        </div>
                    `;
                    chatContainer.insertAdjacentHTML('beforeend', alertHtml);
                    scrollToBottom();
                } else {
                    window.showToast?.(resJson.error || 'Failed to escalate.', 'danger');
                }
            } catch (e) {
                window.showToast?.('Error requesting human takeover.', 'danger');
            }
        });
    }

    // Mark Problem Solved / Resolved Button
    if (resolveChatBtn) {
        resolveChatBtn.addEventListener('click', async () => {
            const convId = activeConvIdInput ? activeConvIdInput.value : '';
            if (!convId) {
                window.showToast?.('No active conversation to resolve.', 'warning');
                return;
            }

            if (!confirm('Mark this inquiry as solved? You can still reopen it anytime by typing a message.')) {
                return;
            }

            try {
                const response = await fetch('api/escalate.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        conversation_id: convId,
                        action: 'resolve'
                    })
                });
                const resJson = await response.json();
                if (resJson.success) {
                    window.showToast?.('Problem marked as resolved. Thank you!', 'success');

                    // Update status badge to Resolved
                    if (chatStatusBadge) {
                        chatStatusBadge.className = 'badge bg-success text-white border border-success d-flex align-items-center gap-1';
                        chatStatusBadge.innerHTML = '<i class="fa-solid fa-circle-check"></i> Problem Resolved';
                    }

                    // Display system resolved message in chat
                    const resolvedNoticeHtml = `
                        <div class="chat-status-banner alert alert-success border-0 shadow-sm p-3 my-3 rounded-3 d-flex align-items-center justify-content-between flex-wrap gap-2" id="chatResolvedBanner">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px;">
                                    <i class="fa-solid fa-check fs-5"></i>
                                </div>
                                <div>
                                    <div class="d-flex align-items-center gap-2">
                                        <h6 class="fw-bold mb-0 text-white">Problem Marked as Resolved</h6>
                                        <span class="badge bg-success-soft text-success border border-success-subtle small">Completed</span>
                                    </div>
                                    <small class="text-white-50">You have marked this problem as solved. Need more help? You can send another message anytime to reopen, or click the restart button <i class="fa-solid fa-rotate-left mx-1"></i> above to start fresh.</small>
                                </div>
                            </div>
                        </div>
                    `;
                    chatContainer.insertAdjacentHTML('beforeend', resolvedNoticeHtml);
                    scrollToBottom();

                    // Hide resolve button
                    resolveChatBtn.style.display = 'none';

                    if (messageInput) {
                        messageInput.placeholder = 'Inquiry is marked as resolved. Type a message to reopen...';
                    }
                } else {
                    window.showToast?.(resJson.error || 'Failed to resolve conversation.', 'danger');
                }
            } catch (e) {
                window.showToast?.('Error resolving inquiry.', 'danger');
            }
        });
    }

    // Global Functions for Inline Callbacks
    window.rateMessage = async (convId, msgId, rating, btn) => {
        try {
            const comment = (rating === 'negative') ? prompt('Optional: What could be improved about this answer?') : '';
            const res = await fetch('api/feedback.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    conversation_id: convId,
                    message_id: msgId,
                    rating: rating,
                    comment: comment || ''
                })
            });
            const resJson = await res.json();
            if (resJson.success) {
                const parent = btn.parentElement;
                parent.innerHTML = `<span class="text-success small"><i class="fa-solid fa-check"></i> Feedback recorded</span>`;
                window.showToast?.('Thank you for rating this response!', 'success');
            }
        } catch (e) {
            window.showToast?.('Failed to submit rating.', 'danger');
        }
    };

    /**
     * TTS Audio Playback with Multi-language Detection
     */
    window.speakAIMessage = (btn) => {
        const bubble = btn.closest('.chat-bubble');
        const textEl = bubble ? bubble.querySelector('.chat-text') : null;
        if (!textEl) return;

        const voiceCtrl = window.voiceController;
        if (!voiceCtrl || !voiceCtrl.isSynthesisSupported()) {
            window.showToast?.('Voice playback (TTS) is not supported in this browser.', 'warning');
            return;
        }

        // Toggle stop if already speaking this button
        if (btn.classList.contains('speaking')) {
            voiceCtrl.stopSpeaking();
            btn.classList.remove('speaking');
            btn.innerHTML = '<i class="fa-solid fa-volume-high"></i>';
            return;
        }

        // Reset all other speaking buttons
        document.querySelectorAll('.btn-feedback.speaking').forEach(b => {
            b.classList.remove('speaking');
            b.innerHTML = '<i class="fa-solid fa-volume-high"></i>';
        });

        // Set current active speaking state
        btn.classList.add('speaking');
        btn.innerHTML = '<i class="fa-solid fa-volume-xmark text-danger"></i>';

        const rowEl = btn.closest('.chat-message-row');
        const explicitLang = rowEl ? (rowEl.dataset.lang || '') : '';

        voiceCtrl.speakText(textEl.innerText, explicitLang, () => {
            btn.classList.remove('speaking');
            btn.innerHTML = '<i class="fa-solid fa-volume-high"></i>';
        });
    };

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
