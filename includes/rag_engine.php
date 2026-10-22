<?php
/**
 * NovaServe AI - RAG (Retrieval-Augmented Generation) Knowledge Retrieval Engine
 * Extracts keywords, queries FAQs and Articles, ranks relevance, and formats context
 */

require_once __DIR__ . '/../config/database.php';

class RAGEngine {
    private static array $stopWords = [
        'a', 'an', 'and', 'are', 'as', 'at', 'be', 'by', 'for', 'from', 'has', 'he',
        'in', 'is', 'it', 'its', 'of', 'on', 'that', 'the', 'to', 'was', 'were', 'will',
        'with', 'the', 'this', 'can', 'you', 'how', 'what', 'where', 'when', 'why',
        'i', 'my', 'me', 'we', 'our', 'do', 'does', 'did', 'have', 'yang', 'dan', 'di',
        'ke', 'dari', 'untuk', 'pada', 'adalah', 'ini', 'itu', 'saya', 'kami', 'boleh',
        'bagaimana', 'apa', 'mana', 'bila', 'mengapa', '的', '了', '在', '是', '我', '有',
        '和', '就', '不', '人', '都', '一', '一个', '上', '也', '很', '到', '说', '要', '去',
        '你', '会', '着', '没有', '看', '好', '自己', '这', '怎样', '如何', '什么', '请问'
    ];

    public static function extractKeywords(string $query): array {
        // Normalize
        $cleaned = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', mb_strtolower($query));
        $tokens = preg_split('/\s+/u', $cleaned, -1, PREG_SPLIT_NO_EMPTY);
        $keywords = [];

        foreach ($tokens as $token) {
            $token = trim($token);
            if (mb_strlen($token) < 2) continue;
            if (in_array($token, self::$stopWords, true)) continue;
            $keywords[] = $token;
        }

        // If no keywords after filter (e.g. very short query), fallback to original tokens
        if (empty($keywords) && !empty($tokens)) {
            $keywords = array_slice($tokens, 0, 3);
        }

        return array_unique($keywords);
    }

    public static function searchKnowledge(string $query, int $maxItems = 3): array {
        $db = Database::getInstance();
        $keywords = self::extractKeywords($query);
        $results = [];

        if (empty($keywords)) {
            return [
                'context_text' => '',
                'sources' => [],
                'best_match' => null,
                'relevance_score' => 0
            ];
        }

        // 1. Search FAQ Items
        $allFaqs = $db->fetchAll("SELECT id, question, answer, views_count FROM faq_items WHERE is_active = 1");
        foreach ($allFaqs as $faq) {
            $score = 0;
            $qLower = mb_strtolower($faq['question']);
            $aLower = mb_strtolower($faq['answer']);

            foreach ($keywords as $kw) {
                if (mb_strpos($qLower, $kw) !== false) {
                    $score += 15; // Question match high priority
                }
                if (mb_strpos($aLower, $kw) !== false) {
                    $score += 5; // Answer body match
                }
            }

            if ($score > 0) {
                $results[] = [
                    'type' => 'faq',
                    'id' => (int)$faq['id'],
                    'title' => $faq['question'],
                    'content' => strip_tags($faq['answer']),
                    'score' => $score
                ];
            }
        }

        // 2. Search Knowledge Articles
        $allArticles = $db->fetchAll("SELECT id, title, slug, summary, content, tags FROM knowledge_articles WHERE status = 'published'");
        foreach ($allArticles as $art) {
            $score = 0;
            $tLower = mb_strtolower($art['title']);
            $sLower = mb_strtolower($art['summary']);
            $tagsLower = mb_strtolower($art['tags'] ?? '');
            $cLower = mb_strtolower(strip_tags($art['content']));

            foreach ($keywords as $kw) {
                if (mb_strpos($tLower, $kw) !== false) {
                    $score += 15;
                }
                if (mb_strpos($tagsLower, $kw) !== false) {
                    $score += 10;
                }
                if (mb_strpos($sLower, $kw) !== false) {
                    $score += 8;
                }
                if (mb_strpos($cLower, $kw) !== false) {
                    $score += 3;
                }
            }

            if ($score > 0) {
                $results[] = [
                    'type' => 'article',
                    'id' => (int)$art['id'],
                    'title' => $art['title'],
                    'slug' => $art['slug'],
                    'content' => $art['summary'] . "\n" . mb_substr(strip_tags($art['content']), 0, 500),
                    'score' => $score
                ];
            }
        }

        // Sort by score descending
        usort($results, fn($a, $b) => $b['score'] <=> $a['score']);
        $topMatches = array_slice($results, 0, $maxItems);

        $contextLines = [];
        $sources = [];
        $bestMatch = $topMatches[0] ?? null;
        $maxScore = $bestMatch['score'] ?? 0;

        foreach ($topMatches as $item) {
            $label = ($item['type'] === 'faq') ? '[Official FAQ]' : '[Knowledge Article]';
            $contextLines[] = "{$label} {$item['title']}:\n{$item['content']}\n";
            $sources[] = [
                'type' => $item['type'],
                'id' => $item['id'],
                'title' => $item['title']
            ];
        }

        return [
            'context_text' => implode("\n---\n", $contextLines),
            'sources' => $sources,
            'best_match' => $bestMatch,
            'relevance_score' => $maxScore
        ];
    }
}
