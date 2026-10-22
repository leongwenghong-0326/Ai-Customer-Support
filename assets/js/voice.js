/**
 * NovaServe AI - Browser Voice Interaction Controller
 * Web Speech API: SpeechRecognition (Voice Input) & SpeechSynthesis (Voice Output)
 * Features auto language detection, Chrome/Edge stutter/cancel recovery, and voice matching.
 */

class VoiceController {
    constructor() {
        this.recognition = null;
        this.isRecording = false;
        this.synth = window.speechSynthesis || null;
        this.currentUtterance = null;
        this.availableVoices = [];
        this.currentRecognitionLang = 'en';

        this.initRecognition();
        this.initSynthesis();
    }

    isRecognitionSupported() {
        return ('webkitSpeechRecognition' in window) || ('SpeechRecognition' in window);
    }

    isSynthesisSupported() {
        return this.synth !== null;
    }

    /**
     * Auto detect language from text
     * Returns: 'zh' | 'ms' | 'en'
     */
    detectLanguage(text) {
        if (!text || typeof text !== 'string') return 'en';
        const trimmed = text.trim();

        // 1. Chinese Han characters
        if (/[\u4e00-\u9fa5]/.test(trimmed)) {
            return 'zh';
        }

        // 2. High-frequency Malay keywords
        const lower = ' ' + trimmed.toLowerCase() + ' ';
        const malayTokens = [
            'saya', 'anda', 'awak', 'kamu', 'kita', 'dia',
            'apa', 'bagaimana', 'kenapa', 'mengapa', 'bila', 'mana',
            'boleh', 'tidak', 'tak', 'bukan', 'ada', 'tiada',
            'terima kasih', 'tolong', 'bantuan', 'bayaran', 'akaun',
            'masalah', 'maklumat', 'harga', 'hubungi', 'sokongan',
            'selamat', 'pagi', 'petang', 'malam', 'tiket', 'aduan', 'resit'
        ];

        for (const token of malayTokens) {
            if (lower.includes(' ' + token + ' ')) {
                return 'ms';
            }
        }

        return 'en';
    }

    initRecognition() {
        if (!this.isRecognitionSupported()) {
            return;
        }

        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        this.recognition = new SpeechRecognition();
        this.recognition.continuous = false;
        this.recognition.interimResults = true;
        this.recognition.maxAlternatives = 1;

        const currentLang = document.documentElement.lang || 'en';
        this.setRecognitionLanguage(currentLang);
    }

    setRecognitionLanguage(lang) {
        if (!this.recognition) return;
        if (lang === 'zh') {
            this.recognition.lang = 'zh-CN';
        } else if (lang === 'ms') {
            this.recognition.lang = 'ms-MY';
        } else {
            this.recognition.lang = 'en-US';
        }
        this.currentRecognitionLang = lang;
    }

    initSynthesis() {
        if (!this.isSynthesisSupported()) return;

        const populateVoices = () => {
            const voices = this.synth.getVoices();
            if (voices && voices.length > 0) {
                this.availableVoices = voices;
            }
        };

        populateVoices();
        if (this.synth.onvoiceschanged !== undefined) {
            this.synth.onvoiceschanged = populateVoices;
        }
    }

    startListening(callbacks) {
        if (!this.isRecognitionSupported()) {
            if (callbacks.onError) callbacks.onError('Speech recognition is not supported in this browser. Please try Chrome, Edge, or Safari.');
            return;
        }

        if (this.isRecording) {
            this.stopListening();
            return;
        }

        try {
            this.recognition.onstart = () => {
                this.isRecording = true;
                if (callbacks.onStart) callbacks.onStart(this.currentRecognitionLang);
            };

            this.recognition.onresult = (event) => {
                let transcript = '';
                for (let i = event.resultIndex; i < event.results.length; ++i) {
                    transcript += event.results[i][0].transcript;
                }
                if (callbacks.onResult) callbacks.onResult(transcript);
            };

            this.recognition.onerror = (event) => {
                this.isRecording = false;
                if (callbacks.onError) callbacks.onError(event.error);
            };

            this.recognition.onend = () => {
                this.isRecording = false;
                if (callbacks.onEnd) callbacks.onEnd();
            };

            this.recognition.start();
        } catch (e) {
            this.isRecording = false;
            if (callbacks.onError) callbacks.onError(e.message);
        }
    }

    stopListening() {
        if (this.recognition && this.isRecording) {
            try {
                this.recognition.stop();
            } catch (e) {
                // Ignore any internal state errors
            }
            this.isRecording = false;
        }
    }

    /**
     * Speak text aloud with auto language detection and voice matching
     */
    speakText(text, explicitLang = null, onComplete = null) {
        if (!this.isSynthesisSupported()) {
            if (onComplete) onComplete();
            return;
        }

        // Clean any residual playback
        this.stopSpeaking();

        // Strip Markdown, HTML tags, citations, and brackets before reading aloud
        const cleanText = (text || '')
            .replace(/<[^>]*>/g, '')
            .replace(/\[\^?.*?\]/g, '')
            .replace(/[#*`_~]/g, '')
            .replace(/\n+/g, ' ')
            .trim();

        if (!cleanText) {
            if (onComplete) onComplete();
            return;
        }

        // Auto-detect language if not explicitly provided or if content obviously differs
        const autoLang = this.detectLanguage(cleanText);
        const resolvedLang = (explicitLang && explicitLang !== 'en') ? explicitLang : autoLang;

        let langCode = 'en-US';
        if (resolvedLang === 'zh') {
            langCode = 'zh-CN';
        } else if (resolvedLang === 'ms') {
            langCode = 'ms-MY';
        } else {
            langCode = 'en-US';
        }

        try {
            // Chromium fix: Unpause if paused
            if (this.synth.paused) {
                this.synth.resume();
            }

            const utterance = new SpeechSynthesisUtterance(cleanText);
            utterance.rate = 1.0;
            utterance.pitch = 1.0;
            utterance.lang = langCode;

            // Fetch latest voices
            let voices = this.synth.getVoices();
            if (voices && voices.length > 0) {
                this.availableVoices = voices;
            }

            // Find matching voice
            if (this.availableVoices.length > 0) {
                let matchedVoice = null;
                if (resolvedLang === 'zh') {
                    matchedVoice = this.availableVoices.find(v => v.lang === 'zh-CN' || v.lang === 'zh_CN') ||
                                   this.availableVoices.find(v => v.lang.startsWith('zh'));
                } else if (resolvedLang === 'ms') {
                    matchedVoice = this.availableVoices.find(v => v.lang === 'ms-MY' || v.lang === 'ms_MY') ||
                                   this.availableVoices.find(v => v.lang.startsWith('ms')) ||
                                   this.availableVoices.find(v => v.lang.startsWith('id')); // Indonesian fallback
                } else {
                    matchedVoice = this.availableVoices.find(v => v.lang === 'en-US' || v.lang === 'en_US') ||
                                   this.availableVoices.find(v => v.lang.startsWith('en'));
                }

                if (matchedVoice) {
                    utterance.voice = matchedVoice;
                }
            }

            // Prevent Chromium garbage collection bug
            window._speechActiveUtterance = utterance;
            this.currentUtterance = utterance;

            const handleFinished = () => {
                window._speechActiveUtterance = null;
                this.currentUtterance = null;
                if (onComplete) {
                    onComplete();
                }
            };

            utterance.onend = handleFinished;
            utterance.onerror = (e) => {
                console.warn('SpeechSynthesis error:', e);
                handleFinished();
            };

            // Speak utterance
            this.synth.speak(utterance);
        } catch (err) {
            console.error('SpeechSynthesis exception:', err);
            window._speechActiveUtterance = null;
            if (onComplete) onComplete();
        }
    }

    stopSpeaking() {
        if (this.isSynthesisSupported()) {
            try {
                this.synth.cancel();
            } catch (e) {
                // Ignore cancel errors
            }
            window._speechActiveUtterance = null;
            this.currentUtterance = null;
        }
    }
}

window.VoiceController = VoiceController;
