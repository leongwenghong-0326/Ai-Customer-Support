<?php
/**
 * NovaServe AI - Intent & Sentiment Classification Engine
 * Analyzes textual inputs for 14 operational intents and 4 emotional sentiments
 */

class TextAnalyzer {
    // 14 Intent Categories
    public const INTENTS = [
        'Greeting',
        'Product Information',
        'Pricing',
        'Payment',
        'Opening Hours',
        'Location',
        'Account Support',
        'Technical Support',
        'Refund',
        'Cancellation',
        'Complaint',
        'Order/Service Status',
        'Human Support',
        'General Question'
    ];

    // 4 Sentiment States
    public const SENTIMENTS = [
        'Positive',
        'Neutral',
        'Negative',
        'Angry'
    ];

    public static function detectIntent(string $text): string {
        $lower = mb_strtolower(trim($text));

        // 1. Human Support Request
        if (preg_match('/\b(human|agent|representative|operator|real person|speak to someone|talk to a human|customer service agent)\b/i', $lower) ||
            preg_match('/(人工|转人工|找客服|真人|客服代表|pegawai|manusia)/u', $lower)) {
            return 'Human Support';
        }

        // 2. Complaint
        if (preg_match('/\b(unacceptable|horrible|terrible|awful|worst|cheat|scam|fraud|sue|lawyer|ridiculous|disgusted|furious)\b/i', $lower) ||
            preg_match('/(投诉|欺诈|骗子|差劲|恶心|糟糕|aduan|teruk|menipu)/u', $lower)) {
            return 'Complaint';
        }

        // 3. Refund
        if (preg_match('/\b(refund|money back|reimburse|chargeback|reimbursement)\b/i', $lower) ||
            preg_match('/(退款|退钱|返款|bayaran balik)/u', $lower)) {
            return 'Refund';
        }

        // 4. Cancellation
        if (preg_match('/\b(cancel|cancellation|terminate|stop subscription|unsubscribe|delete account)\b/i', $lower) ||
            preg_match('/(取消|退订|注销|batal|tamatkan)/u', $lower)) {
            return 'Cancellation';
        }

        // 5. Pricing
        if (preg_match('/\b(price|pricing|cost|how much|discount|quote|rate|plans|tier|tier pricing)\b/i', $lower) ||
            preg_match('/(价格|多少钱|费用|折扣|报价|harga|kos|diskaun)/u', $lower)) {
            return 'Pricing';
        }

        // 6. Payment
        if (preg_match('/\b(payment|invoice|credit card|pay|bill|billing|paypal|wire transfer|receipt)\b/i', $lower) ||
            preg_match('/(付款|支付|发票|账单|收据|bayar|invois|resit)/u', $lower)) {
            return 'Payment';
        }

        // 7. Opening Hours
        if (preg_match('/\b(opening hours|business hours|schedule|working hours|time open|when are you open)\b/i', $lower) ||
            preg_match('/(营业时间|工作时间|几点上班|几点开门|waktu operasi|waktu kerja)/u', $lower)) {
            return 'Opening Hours';
        }

        // 8. Location
        if (preg_match('/\b(location|address|office|where are you located|headquarters|country)\b/i', $lower) ||
            preg_match('/(地址|位置|在哪里|公司总部|lokasi|alamat)/u', $lower)) {
            return 'Location';
        }

        // 9. Account Support
        if (preg_match('/\b(password|login|sign in|reset password|2fa|authenticator|profile|username|account locked)\b/i', $lower) ||
            preg_match('/(密码|登录|重置密码|双重验证|账号锁|kata laluan|log masuk|akaun)/u', $lower)) {
            return 'Account Support';
        }

        // 10. Technical Support
        if (preg_match('/\b(api|error|bug|code|server down|connection|webhook|failed|glitch|latency|troubleshoot|microphone)\b/i', $lower) ||
            preg_match('/(接口|报错|代码|服务器|连接失败|网络故障|麦克风|masalah teknikal|ralat)/u', $lower)) {
            return 'Technical Support';
        }

        // 11. Product Information
        if (preg_match('/\b(features|product|service|what is novaserve|capabilities|specs|overview|architecture)\b/i', $lower) ||
            preg_match('/(功能|产品介绍|服务特性|什么是|ciri-ciri|produk)/u', $lower)) {
            return 'Product Information';
        }

        // 12. Order / Service Status
        if (preg_match('/\b(order status|service status|ticket status|tracking|progress|delivery)\b/i', $lower) ||
            preg_match('/(状态|工单进度|服务状态|查询进度|status pesanan|status tiket)/u', $lower)) {
            return 'Order/Service Status';
        }

        // 13. Greeting
        if (preg_match('/^(hi|hello|hey|good morning|good afternoon|good evening|greetings)\b/i', $lower) ||
            preg_match('/^(你好|您好|早安|午安|晚安|hai|salam|halo)/u', $lower)) {
            return 'Greeting';
        }

        return 'General Question';
    }

    public static function detectSentiment(string $text): string {
        $lower = mb_strtolower(trim($text));

        // Angry keywords and intensifiers
        if (preg_match('/\b(angry|furious|pissed|hate|scam|cheat|sue|threat|lawsuit|disaster|garbage|pathetic|worst company|useless|stolen|robbed)\b/i', $lower) ||
            preg_match('/(愤怒|生气|太差了|骗钱|垃圾|恶劣|报警|起诉|投诉到底|marah|menipu|teruk gila)/u', $lower) ||
            preg_match('/(!{3,}|\?{3,})/i', $text)) {
            return 'Angry';
        }

        // Negative keywords
        if (preg_match('/\b(bad|poor|broken|slow|issue|problem|difficult|not working|fail|failed|disappointed|confused|wrong|annoying)\b/i', $lower) ||
            preg_match('/(不好|太慢|错误|失败|失望|困惑|无法使用|有问题|tidak berfungsi|masalah|kecewa)/u', $lower)) {
            return 'Negative';
        }

        // Positive keywords
        if (preg_match('/\b(good|great|awesome|excellent|thank|thanks|helpful|love|perfect|amazing|appreciate|wonderful|pleased)\b/i', $lower) ||
            preg_match('/(好|太棒了|感谢|谢谢|满意|给力|喜欢|terima kasih|bagus|hebat)/u', $lower)) {
            return 'Positive';
        }

        return 'Neutral';
    }

    /**
     * Automatically detect the natural language of the user input
     * Returns 'zh' (Chinese), 'ms' (Malay), or 'en' (English)
     */
    public static function detectLanguage(string $text, string $fallback = 'en'): string {
        $trimmed = trim($text);
        if (empty($trimmed)) {
            return in_array($fallback, ['en', 'ms', 'zh'], true) ? $fallback : 'en';
        }

        // 1. Chinese Detection: Contains any Chinese Han characters
        if (preg_match('/[\x{4e00}-\x{9fa5}]/u', $trimmed)) {
            return 'zh';
        }

        // 2. Malay Detection: Match against common high-frequency Malay grammatical tokens
        $lower = ' ' . mb_strtolower($trimmed) . ' ';
        $malayTokens = [
            'saya', 'anda', 'awak', 'kamu', 'kita', 'dia',
            'apa', 'bagaimana', 'kenapa', 'mengapa', 'bila', 'mana',
            'boleh', 'tidak', 'tak', 'bukan', 'ada', 'tiada',
            'terima kasih', 'tolong', 'bantuan', 'bayaran', 'akaun',
            'masalah', 'maklumat', 'harga', 'hubungi', 'sokongan',
            'selamat', 'pagi', 'petang', 'malam', 'tiket', 'aduan', 'resit'
        ];

        foreach ($malayTokens as $token) {
            if (str_contains($lower, ' ' . $token . ' ')) {
                return 'ms';
            }
        }

        return in_array($fallback, ['en', 'ms', 'zh'], true) ? $fallback : 'en';
    }
}

