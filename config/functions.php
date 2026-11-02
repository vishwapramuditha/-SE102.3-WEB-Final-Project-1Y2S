<?php
/**
 * Core Helper Functions
 * Module: Web and Mobile Application Development (SE102.3)
 * NSBM Green University - Faculty of Computing
 */

require_once __DIR__ . '/db.php';

/**
 * Sanitize User Input Strings
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

/**
 * Safe Redirect Helper
 */
function redirect($url) {
    header("Location: " . $url);
    exit();
}

/**
 * Check if a user is currently logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Return current logged-in user array
 */
function currentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id'         => $_SESSION['user_id'] ?? null,
        'name'       => $_SESSION['user_name'] ?? 'User',
        'email'      => $_SESSION['user_email'] ?? '',
        'role'       => $_SESSION['user_role'] ?? 'student',
        'student_id' => $_SESSION['student_id'] ?? '',
        'department' => $_SESSION['user_department'] ?? 'Faculty of Computing',
    ];
}

/**
 * Check if the active user has the Admin role
 */
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Check if the active user has the Student role
 */
function isStudent() {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'student';
}

/**
 * Middleware: Enforce login requirement
 */
function requireLogin($redirectUrl = 'login.php') {
    if (!isLoggedIn()) {
        setFlashMessage('warning', 'Please sign in to access this page.');
        redirect($redirectUrl);
    }
}

/**
 * Middleware: Enforce Admin access requirement
 */
function requireAdmin($redirectUrl = '../login.php') {
    if (!isAdmin()) {
        setFlashMessage('danger', 'Unauthorized access! Administrator credentials required.');
        redirect($redirectUrl);
    }
}

/**
 * Middleware: Enforce Student access requirement
 */
function requireStudent($redirectUrl = '../login.php') {
    if (!isStudent()) {
        setFlashMessage('warning', 'Please log in with a student account to access this section.');
        redirect($redirectUrl);
    }
}

/**
 * Set flash message for alert banners
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type'    => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

/**
 * Retrieve and clear flash message
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $flash;
    }
    return null;
}

/**
 * Generate CSRF Token
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF Token
 */
function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$token);
}

/**
 * Format timestamp into readable date
 */
function formatDate($datetime, $format = 'M d, Y') {
    if (!$datetime) return 'N/A';
    $time = strtotime($datetime);
    return date($format, $time);
}

/**
 * Friendly "Time Ago" formatter
 */
function timeAgo($datetime) {
    if (!$datetime) return 'Just now';
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;

    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' min' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hr' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M d, Y', $timestamp);
    }
}

/**
 * Slugify text for clean titles and URLs
 */
function slugify($text) {
    // replace non letter or digits by -
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    // transliterate
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    // remove unwanted characters
    $text = preg_replace('~[^-\w]+~', '', $text);
    // trim
    $text = trim($text, '-');
    // remove duplicate -
    $text = preg_replace('~-+~', '-', $text);
    // lowercase
    $text = strtolower($text);
    if (empty($text)) {
        return 'n-a-' . time();
    }
    return $text;
}

/**
 * Truncate text by word limit
 */
function truncateWords($text, $limit = 25) {
    $text = strip_tags($text);
    $words = explode(' ', $text);
    if (count($words) > $limit) {
        return implode(' ', array_slice($words, 0, $limit)) . '...';
    }
    return $text;
}

/**
 * Handle Article Image Upload
 */
function uploadImage($file, $targetDir = 'assets/uploads/articles/') {
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'error' => 'Invalid file parameter.'];
    }

    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => true, 'filename' => null]; // No file uploaded is ok (optional)
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Upload failed with error code ' . $file['error']];
    }

    // Check size (Max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'error' => 'Exceeded filesize limit (Max 5MB).'];
    }

    // Check MIME type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif'
    ];

    if (!array_key_exists($mimeType, $allowedTypes)) {
        return ['success' => false, 'error' => 'Invalid image format. Allowed: JPG, PNG, WEBP, GIF.'];
    }

    $ext = $allowedTypes[$mimeType];
    $newFilename = 'news_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;

    // Resolve full target path
    $basePath = dirname(__DIR__) . '/' . trim($targetDir, '/') . '/';
    if (!is_dir($basePath)) {
        mkdir($basePath, 0777, true);
    }

    $destination = $basePath . $newFilename;
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => false, 'error' => 'Failed to save uploaded file on server.'];
    }

    return [
        'success'  => true,
        'filename' => trim($targetDir, '/') . '/' . $newFilename
    ];
}

/**
 * Increment Article View Count (with session protection to avoid duplicate refreshes)
 */
function incrementArticleViews($articleId) {
    $db = getDB();
    if (!$db) return;

    $viewKey = 'viewed_article_' . $articleId;
    if (!isset($_SESSION[$viewKey])) {
        $_SESSION[$viewKey] = true;
        try {
            $stmt = $db->prepare("UPDATE articles SET views_count = views_count + 1 WHERE id = ?");
            $stmt->execute([(int)$articleId]);
        } catch (PDOException $e) {
            // Silently log or ignore
        }
    }
}

/**
 * Check if an article is bookmarked by user
 */
function isBookmarked($userId, $articleId) {
    if (!$userId) return false;
    $db = getDB();
    if (!$db) return false;

    try {
        $stmt = $db->prepare("SELECT 1 FROM bookmarks WHERE user_id = ? AND article_id = ?");
        $stmt->execute([(int)$userId, (int)$articleId]);
        return (bool)$stmt->fetchColumn();
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Check if a student is subscribed to a category
 */
function isSubscribed($userId, $categoryId) {
    if (!$userId) return false;
    $db = getDB();
    if (!$db) return false;

    try {
        $stmt = $db->prepare("SELECT 1 FROM category_subscriptions WHERE user_id = ? AND category_id = ?");
        $stmt->execute([(int)$userId, (int)$categoryId]);
        return (bool)$stmt->fetchColumn();
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Get category-matched sample SVG banner
 */
function getSampleImage(string $categorySlug = '', string $basePrefix = ''): string {
    $map = [
        'academic'    => 'assets/images/sample-ai-lab.svg',
        'events'      => 'assets/images/sample-hackathon.svg',
        'sports'      => 'assets/images/sample-cricket.svg',
        'general'     => 'assets/images/sample-solar.svg',
        'research'    => 'assets/images/sample-research.svg',
        'robotics'    => 'assets/images/sample-robot.svg',
        'medical'     => 'assets/images/sample-medical.svg',
        'convocation' => 'assets/images/sample-convocation.svg',
        'careerfair'  => 'assets/images/sample-careerfair.svg',
        'cultural'    => 'assets/images/sample-cultural.svg',
        'swimming'    => 'assets/images/sample-swimming.svg',
    ];
    $slug = strtolower(trim($categorySlug));
    $path = $map[$slug] ?? 'assets/images/default-news.svg';
    return $basePrefix . $path;
}

/**
 * Resolves an article image URL, ensuring sample .jpg paths point to clean .svg files
 * and providing category-matched fallback banner images.
 */
function getArticleImage(?string $imageUrl, string $categorySlug = '', string $basePrefix = ''): string {
    if (!empty($imageUrl)) {
        // If image points to a sample jpg or invalid jpg that has an svg counterpart, use .svg
        if (strpos($imageUrl, 'sample-') !== false && substr($imageUrl, -4) === '.jpg') {
            $imageUrl = substr($imageUrl, 0, -4) . '.svg';
        }
        return $basePrefix . ltrim($imageUrl, '/');
    }
    return getSampleImage($categorySlug, $basePrefix);
}

/**
 * Ensures extended seed news articles exist in database (self-healing for existing installations)
 */
function syncExtendedSeedArticles(?PDO $db): void {
    if (!$db) return;
    try {
        $count = (int)$db->query("SELECT COUNT(*) FROM articles")->fetchColumn();
        if ($count < 12) {
            $schemaFile = __DIR__ . '/../database/schema.sql';
            if (file_exists($schemaFile)) {
                $content = file_get_contents($schemaFile);
                // Extract INSERT INTO `articles` query
                if (preg_match('/INSERT INTO `articles`[^;]+;/s', $content, $matches)) {
                    // Replace INSERT INTO with INSERT IGNORE INTO to safely skip existing rows
                    $sql = preg_replace('/^INSERT INTO `articles`/i', 'INSERT IGNORE INTO `articles`', $matches[0]);
                    $db->exec($sql);
                }
                // Also ensure comments are synced
                if (preg_match('/INSERT INTO `comments`[^;]+;/s', $content, $cMatches)) {
                    $cSql = preg_replace('/^INSERT INTO `comments`/i', 'INSERT IGNORE INTO `comments`', $cMatches[0]);
                    $db->exec($cSql);
                }
            }
        }
    } catch (Throwable $t) {
        // Silently bypass on permission or locked table issues
    }
}


