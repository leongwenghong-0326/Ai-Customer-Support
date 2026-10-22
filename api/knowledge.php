<?php
/**
 * NovaServe AI - Knowledge Base REST API Endpoint
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';

$db = Database::getInstance();
$query = clean_string($_GET['q'] ?? '');
$categoryId = clean_int($_GET['category_id'] ?? 0);
$articleId = clean_int($_GET['id'] ?? 0);

try {
    if ($articleId > 0) {
        // Fetch single article and increment views
        $article = $db->fetchOne(
            "SELECT a.*, c.name as category_name, u.full_name as author_name 
             FROM knowledge_articles a
             LEFT JOIN categories c ON a.category_id = c.id
             LEFT JOIN users u ON a.created_by = u.id
             WHERE a.id = :id AND a.status = 'published'",
            ['id' => $articleId]
        );

        if (!$article) {
            echo json_encode(['success' => false, 'error' => 'Article not found.']);
            exit;
        }

        $db->query("UPDATE knowledge_articles SET views_count = views_count + 1 WHERE id = :id", ['id' => $articleId]);
        echo json_encode(['success' => true, 'article' => $article]);
        exit;
    }

    // List articles
    $where = ["a.status = 'published'"];
    $params = [];

    if ($categoryId > 0) {
        $where[] = "a.category_id = :cid";
        $params['cid'] = $categoryId;
    }

    if (!empty($query)) {
        $where[] = "(a.title LIKE :q1 OR a.summary LIKE :q2 OR a.tags LIKE :q3)";
        $params['q1'] = '%' . $query . '%';
        $params['q2'] = '%' . $query . '%';
        $params['q3'] = '%' . $query . '%';
    }

    $whereSql = implode(' AND ', $where);
    $articles = $db->fetchAll(
        "SELECT a.id, a.title, a.slug, a.summary, a.tags, a.views_count, a.created_at, c.name as category_name
         FROM knowledge_articles a
         LEFT JOIN categories c ON a.category_id = c.id
         WHERE {$whereSql}
         ORDER BY a.views_count DESC, a.id DESC
         LIMIT 30",
        $params
    );

    echo json_encode(['success' => true, 'articles' => $articles]);
} catch (Exception $e) {
    error_log("Knowledge API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Unable to query knowledge records.']);
}
