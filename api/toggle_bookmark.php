<?php
/**
 * AJAX Endpoint: Toggle Article Bookmark
 * Module: Web and Mobile Application Development (SE102.3)
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/functions.php';

if (!isLoggedIn()) {
    echo json_encode(['status' => 'unauthorized', 'message' => 'Please sign in first.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit();
}

$articleId = isset($_POST['article_id']) ? (int)$_POST['article_id'] : 0;
$user = currentUser();
$userId = $user['id'];

if (!$articleId || !$userId) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid parameters.']);
    exit();
}

$db = getDB();
if (!$db) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed.']);
    exit();
}

try {
    // Check if already bookmarked
    $stmt = $db->prepare("SELECT id FROM bookmarks WHERE user_id = ? AND article_id = ?");
    $stmt->execute([$userId, $articleId]);
    $existing = $stmt->fetch();

    if ($existing) {
        // Remove bookmark
        $del = $db->prepare("DELETE FROM bookmarks WHERE id = ?");
        $del->execute([$existing['id']]);
        echo json_encode(['status' => 'success', 'bookmarked' => false, 'message' => 'Article removed from bookmarks.']);
    } else {
        // Add bookmark
        $ins = $db->prepare("INSERT INTO bookmarks (user_id, article_id) VALUES (?, ?)");
        $ins->execute([$userId, $articleId]);
        echo json_encode(['status' => 'success', 'bookmarked' => true, 'message' => 'Article saved to bookmarks!']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
