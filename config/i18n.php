<?php
/**
 * NovaServe AI - Internationalization (i18n) Engine
 * Supported Languages: English (en), Bahasa Melayu (ms), Chinese (zh)
 */

if (!isset($_SESSION['app_lang'])) {
    $_SESSION['app_lang'] = 'en';
}

// Allow user to switch language via GET parameter ?lang=en|ms|zh
if (isset($_GET['lang'])) {
    $requestedLang = strtolower(trim($_GET['lang']));
    if (in_array($requestedLang, ['en', 'ms', 'zh'], true)) {
        $_SESSION['app_lang'] = $requestedLang;
    }
}

function get_current_lang(): string {
    return $_SESSION['app_lang'] ?? 'en';
}

$LANG_DICTIONARY = [
    'en' => [
        'app_name' => 'NovaServe AI',
        'tagline' => 'Intelligent Customer Support Platform',
        'nav_home' => 'Home',
        'nav_chat' => 'AI Support Chat',
        'nav_kb' => 'Knowledge Base',
        'nav_faq' => 'FAQs',
        'nav_tickets' => 'Submit Ticket',
        'nav_my_tickets' => 'My Tickets',
        'nav_login' => 'Sign In',
        'nav_register' => 'Register',
        'nav_logout' => 'Sign Out',
        'nav_admin' => 'Admin Console',
        'hero_title' => 'Instant Resolution Powered by Enterprise AI',
        'hero_subtitle' => 'Ask our intelligent assistant anything, explore certified solutions, or connect with our specialized support engineers seamlessly.',
        'start_chat' => 'Start AI Conversation',
        'search_placeholder' => 'Search knowledge base, error codes, billing policies...',
        'popular_topics' => 'Popular Inquiries',
        'recent_articles' => 'Recent Knowledge Articles',
        'frequently_asked' => 'Frequently Asked Questions',
        'contact_us' => 'Contact Support',
        'operating_hours' => 'Operating Hours',
        'copyright' => 'All rights reserved.',
        'talk_to_human' => 'Talk to a Human',
        'type_message' => 'Type your question or message...',
        'send' => 'Send',
        'voice_input' => 'Voice Input',
        'voice_listening' => 'Listening... Speak now',
        'voice_stop' => 'Stop Listening',
        'voice_not_supported' => 'Voice recognition is not supported in this browser.',
        'listen_response' => 'Listen to AI Response',
        'sources_used' => 'Verified References & Sources',
        'was_helpful' => 'Was this answer helpful?',
        'thank_feedback' => 'Thank you for your feedback!',
        'confidence' => 'Confidence',
        'ticket_number' => 'Ticket Number',
        'subject' => 'Subject',
        'category' => 'Category',
        'priority' => 'Priority',
        'status' => 'Status',
        'created_at' => 'Created Date',
        'reply' => 'Reply',
        'attach_files' => 'Attach Supporting Files (JPG, PNG, PDF, DOC, TXT up to 5MB)',
        'internal_note' => 'Internal Note (Staff Only)',
        'escalate_notice' => 'A support representative has been alerted to review this conversation.'
    ],
    'ms' => [
        'app_name' => 'NovaServe AI',
        'tagline' => 'Platform Sokongan Pelanggan Pintar',
        'nav_home' => 'Laman Utama',
        'nav_chat' => 'Sembang Bantuan AI',
        'nav_kb' => 'Pangkalan Pengetahuan',
        'nav_faq' => 'Soalan Lazim (FAQ)',
        'nav_tickets' => 'Hantar Tiket',
        'nav_my_tickets' => 'Tiket Saya',
        'nav_login' => 'Log Masuk',
        'nav_register' => 'Daftar Akaun',
        'nav_logout' => 'Log Keluar',
        'nav_admin' => 'Konsol Pentadbir',
        'hero_title' => 'Penyelesaian Pantas Dikuasakan AI Perusahaan',
        'hero_subtitle' => 'Tanyakan pembantu pintar kami tentang apa jua, terokai panduan rasmi, atau hubungi jurutera sokongan kami dengan mudah.',
        'start_chat' => 'Mulakan Perbualan AI',
        'search_placeholder' => 'Cari pangkalan pengetahuan, kod ralat, polisi bayaran...',
        'popular_topics' => 'Topik Popular',
        'recent_articles' => 'Artikel Terkini',
        'frequently_asked' => 'Soalan Yang Kerap Ditanya',
        'contact_us' => 'Hubungi Sokongan',
        'operating_hours' => 'Waktu Operasi',
        'copyright' => 'Hak cipta terpelihara.',
        'talk_to_human' => 'Bercakap dengan Pegawai Khidmat',
        'type_message' => 'Taipkan soalan atau mesej anda...',
        'send' => 'Hantar',
        'voice_input' => 'Input Suara',
        'voice_listening' => 'Sedang mendengar... Sila bercakap',
        'voice_stop' => 'Hentikan Rakaman',
        'voice_not_supported' => 'Pengecaman suara tidak disokong oleh pelayar web ini.',
        'listen_response' => 'Dengar Jawapan AI',
        'sources_used' => 'Rujukan & Sumber Disahkan',
        'was_helpful' => 'Adakah jawapan ini membantu?',
        'thank_feedback' => 'Terima kasih atas maklum balas anda!',
        'confidence' => 'Keyakinan AI',
        'ticket_number' => 'Nombor Tiket',
        'subject' => 'Subjek',
        'category' => 'Kategori',
        'priority' => 'Keutamaan',
        'status' => 'Status',
        'created_at' => 'Tarikh Dicipta',
        'reply' => 'Balas',
        'attach_files' => 'Lampirkan Fail Sokongan (JPG, PNG, PDF, DOC, TXT maks 5MB)',
        'internal_note' => 'Nota Dalaman (Staf Sahaja)',
        'escalate_notice' => 'Wakil sokongan manusia telah dimaklumkan untuk menyemak perbualan ini.'
    ],
    'zh' => [
        'app_name' => 'NovaServe AI',
        'tagline' => '智能企业客户服务与支持平台',
        'nav_home' => '首页',
        'nav_chat' => 'AI 智能客服',
        'nav_kb' => '知识库',
        'nav_faq' => '常见问题',
        'nav_tickets' => '提交工单',
        'nav_my_tickets' => '我的工单',
        'nav_login' => '登录',
        'nav_register' => '注册',
        'nav_logout' => '退出登录',
        'nav_admin' => '管理后台',
        'hero_title' => '智能驱动 · 高效极速的企业级客服体验',
        'hero_subtitle' => '随时向 AI 助手提问，快速检索官方认证知识库，或一键转接人工技术专家。',
        'start_chat' => '开启 AI 智能对话',
        'search_placeholder' => '搜索知识库文档、排错指南、账单政策...',
        'popular_topics' => '热门主题',
        'recent_articles' => '最新精选知识文章',
        'frequently_asked' => '常见问题与解答',
        'contact_us' => '联系人工技术支持',
        'operating_hours' => '服务运营时间',
        'copyright' => '保留所有权利。',
        'talk_to_human' => '转接人工客服',
        'type_message' => '输入您的问题或描述...',
        'send' => '发送',
        'voice_input' => '语音输入',
        'voice_listening' => '正在聆听... 请讲话',
        'voice_stop' => '停止录音',
        'voice_not_supported' => '当前浏览器不支持 Web Speech 语音识别。',
        'listen_response' => '朗读 AI 回答',
        'sources_used' => '官方认证参考来源',
        'was_helpful' => '这个回答是否有帮助？',
        'thank_feedback' => '感谢您的宝贵评价！',
        'confidence' => '置信度',
        'ticket_number' => '工单编号',
        'subject' => '主题',
        'category' => '服务分类',
        'priority' => '优先级',
        'status' => '处理状态',
        'created_at' => '创建时间',
        'reply' => '追加回复',
        'attach_files' => '上传附件材料（支持 JPG, PNG, PDF, DOC, TXT，单文件上限 5MB）',
        'internal_note' => '内部私密备忘（仅客服可见）',
        'escalate_notice' => '已通知人工支持代表为您进行进一步协助。'
    ]
];

function __(string $key, ?string $default = null): string {
    global $LANG_DICTIONARY;
    $lang = get_current_lang();
    if (isset($LANG_DICTIONARY[$lang][$key])) {
        return $LANG_DICTIONARY[$lang][$key];
    }
    if (isset($LANG_DICTIONARY['en'][$key])) {
        return $LANG_DICTIONARY['en'][$key];
    }
    return $default ?? $key;
}
