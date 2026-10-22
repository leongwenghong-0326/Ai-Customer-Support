<?php
/**
 * NovaServe AI - FAQ REST API Endpoint
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';

$db = Database::getInstance();
$query = clean_string($_GET['q'] ?? '');
$categoryId = clean_int($_GET['category_id'] ?? 0);
$faqId = clean_int($_GET['id'] ?? 0);

try {
    if ($faqId > 0) {
        $db->query("UPDATE faq_items SET views_count = views_count + 1 WHERE id = :id", ['id' => $faqId]);
        echo json_encode(['success' => true]);
        exit;
    }

    $where = ["f.is_active = 1"];
    $params = [];

    if ($categoryId > 0) {
        $where[] = "f.category_id = :cid";
        $params['cid'] = $categoryId;
    }

    if (!empty($query)) {
        $where[] = "(f.question LIKE :q1 OR f.answer LIKE :q2)";
        $params['q1'] = '%' . $query . '%';
        $params['q2'] = '%' . $query . '%';
    }

    $whereSql = implode(' AND ', $where);
    $faqs = $db->fetchAll(
        "SELECT f.id, f.category_id, f.question, f.answer, f.views_count, c.name as category_name
         FROM faq_items f
         LEFT JOIN categories c ON f.category_id = c.id
         WHERE {$whereSql}
         ORDER BY f.sort_order ASC, f.views_count DESC
         LIMIT 40",
        $params
    );

    echo json_encode(['success' => true, 'faqs' => $faqs]);
} catch (Exception $e) {
    error_log("FAQ API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Unable to query FAQ items.']);
}
