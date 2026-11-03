<?php
/**
 * Manage News Articles (Admin CRUD)
 * Module: Web and Mobile Application Development (SE102.3)
 * NSBM Green University - Faculty of Computing
 */

require_once __DIR__ . '/../config/functions.php';
requireAdmin('../login.php');

$db = getDB();
if (!$db) {
    die("Database connection error. Please run setup.php.");
}

// 1. Handle Quick Toggles & Deletions
// A. Toggle Featured Flag
if (isset($_GET['toggle_featured']) && is_numeric($_GET['toggle_featured'])) {
    $artId = (int)$_GET['toggle_featured'];
    try {
        $stmt = $db->prepare("UPDATE articles SET is_featured = 1 - is_featured WHERE id = ?");
        $stmt->execute([$artId]);
        setFlashMessage('success', 'Featured status updated.');
        redirect('articles.php');
    } catch (PDOException $e) {}
}

// B. Toggle Breaking News Flag
if (isset($_GET['toggle_breaking']) && is_numeric($_GET['toggle_breaking'])) {
    $artId = (int)$_GET['toggle_breaking'];
    try {
        $stmt = $db->prepare("UPDATE articles SET is_breaking = 1 - is_breaking WHERE id = ?");
        $stmt->execute([$artId]);
        setFlashMessage('success', 'Breaking news flag updated.');
        redirect('articles.php');
    } catch (PDOException $e) {}
}

// C. Delete Article
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $artId = (int)$_GET['id'];
    try {
        $del = $db->prepare("DELETE FROM articles WHERE id = ?");
        $del->execute([$artId]);
        setFlashMessage('info', 'Article #' . $artId . ' deleted successfully.');
        redirect('articles.php');
    } catch (PDOException $e) {
        setFlashMessage('danger', 'Error deleting article: ' . $e->getMessage());
    }
}

// 2. Filters & Search Query
$searchQuery = trim($_GET['q'] ?? '');
$filterCategory = trim($_GET['category'] ?? '');
$filterStatus = trim($_GET['status'] ?? '');

$where = ["1=1"];
$params = [];

if (!empty($searchQuery)) {
    $where[] = "(a.title LIKE ? OR a.content LIKE ?)";
    $like = '%' . $searchQuery . '%';
    $params[] = $like;
    $params[] = $like;
}

if (!empty($filterCategory)) {
    $where[] = "c.slug = ?";
    $params[] = $filterCategory;
}

if (!empty($filterStatus)) {
    $where[] = "a.status = ?";
    $params[] = $filterStatus;
}

// Fetch categories for filter dropdown
$categories = [];
try {
    $cStmt = $db->query("SELECT * FROM categories ORDER BY name ASC");
    $categories = $cStmt->fetchAll();
} catch (PDOException $e) {}

// Fetch articles
$articles = [];
try {
    $sql = "
        SELECT a.*, c.name as category_name, c.badge_color, u.full_name as author_name,
               (SELECT COUNT(*) FROM comments cm WHERE cm.article_id = a.id) as comments_count
        FROM articles a
        JOIN categories c ON a.category_id = c.id
        JOIN users u ON a.author_id = u.id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY a.created_at DESC
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $articles = $stmt->fetchAll();
} catch (PDOException $e) {}

$pageTitle = 'Manage News Articles';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - NSBM NewsDesk Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Playfair+Display:ital,wght@0,700;0,800;1,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="icon" type="image/svg+xml" href="../assets/images/nsbm-logo.svg">
</head>
<body>
    <div class="admin-layout">
        <?php include __DIR__ . '/../includes/admin_sidebar.php'; ?>

        <div class="admin-main">
            <?php include __DIR__ . '/../includes/admin_header.php'; ?>

            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px;">
                <div>
                    <h1 style="font-size: 1.5rem; font-weight: 800; color: var(--text-1); margin-bottom: 4px;">
                        All News Articles (CRUD)
                    </h1>
                    <p style="color: var(--text-3); font-size: 0.9rem;">
                        Create, update, toggle featured/breaking, and delete campus stories.
                    </p>
                </div>
                <a href="article_create.php" class="btn btn-primary">
                    + Create New Article
                </a>
            </div>

            <!-- Filter Controls -->
            <div class="filter-search-bar" style="margin-bottom: 24px;">
                <form action="articles.php" method="GET" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                    <div style="flex: 2; min-width: 240px;">
                        <input type="text" name="q" value="<?php echo htmlspecialchars($searchQuery); ?>" placeholder="Search by title or content..." class="form-control" style="padding: 8px 12px;">
                    </div>

                    <div style="flex: 1; min-width: 160px;">
                        <select name="category" class="form-control" style="padding: 8px 12px;">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat['slug']); ?>" <?php echo ($filterCategory === $cat['slug']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="flex: 1; min-width: 140px;">
                        <select name="status" class="form-control" style="padding: 8px 12px;">
                            <option value="">All Statuses</option>
                            <option value="approved" <?php echo ($filterStatus === 'approved') ? 'selected' : ''; ?>>Approved</option>
                            <option value="pending" <?php echo ($filterStatus === 'pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="rejected" <?php echo ($filterStatus === 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                            <option value="draft" <?php echo ($filterStatus === 'draft') ? 'selected' : ''; ?>>Draft</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm" style="height: 38px;">Filter</button>
                    <?php if (!empty($searchQuery) || !empty($filterCategory) || !empty($filterStatus)): ?>
                        <a href="articles.php" class="btn btn-outline btn-sm" style="height: 38px;">Reset</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Articles Table -->
            <div class="table-card">
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 50px;">ID</th>
                                <th style="width: 70px;">Cover</th>
                                <th>Headline</th>
                                <th>Category</th>
                                <th>Author</th>
                                <th>Status</th>
                                <th style="text-align: center;">Featured</th>
                                <th style="text-align: center;">Breaking</th>
                                <th>Views</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($articles)): ?>
                                <?php foreach ($articles as $art): ?>
                                    <tr>
                                        <td><code>#<?php echo $art['id']; ?></code></td>
                                        <td>
                                            <div style="width: 50px; height: 36px; border-radius: 4px; overflow: hidden; background: var(--text-1);">
                                                <img src="../<?php echo htmlspecialchars($art['image_url'] ?: 'assets/images/default-news.svg'); ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                                            </div>
                                        </td>
                                        <td>
                                            <a href="../article.php?slug=<?php echo htmlspecialchars($art['slug']); ?>" target="_blank" style="font-weight: 700; color: var(--text-1); display: block; margin-bottom: 2px;">
                                                <?php echo htmlspecialchars($art['title']); ?>
                                            </a>
                                            <span style="font-size: 0.78rem; color: var(--text-3); display: inline-flex; align-items: center;"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:3px;"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg> <?php echo formatDate($art['published_at'] ?: $art['created_at']); ?></span>
                                        </td>
                                        <td>
                                            <span class="badge-cat" style="background-color: <?php echo htmlspecialchars($art['badge_color'] ?: '#006a4e'); ?>; font-size: 0.72rem;">
                                                <?php echo htmlspecialchars($art['category_name']); ?>
                                            </span>
                                        </td>
                                        <td style="font-size: 0.85rem;">
                                            <?php echo htmlspecialchars($art['author_name']); ?>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo htmlspecialchars($art['status']); ?>">
                                                <?php echo ucfirst($art['status']); ?>
                                            </span>
                                        </td>
                                        <!-- Featured Toggle -->
                                        <td style="text-align: center;">
                                            <a href="articles.php?toggle_featured=<?php echo $art['id']; ?>" class="btn btn-sm <?php echo $art['is_featured'] ? 'btn-accent' : 'btn-outline'; ?>" style="padding: 2px 8px; font-size: 0.74rem; display: inline-flex; align-items: center; gap: 3px;">
                                                <?php echo $art['is_featured'] ? '<svg width="11" height="11" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg> Yes' : 'No'; ?>
                                            </a>
                                        </td>
                                        <!-- Breaking Toggle -->
                                        <td style="text-align: center;">
                                            <a href="articles.php?toggle_breaking=<?php echo $art['id']; ?>" class="btn btn-sm <?php echo $art['is_breaking'] ? 'btn-danger' : 'btn-outline'; ?>" style="padding: 2px 8px; font-size: 0.74rem; display: inline-flex; align-items: center; gap: 3px;">
                                                <?php echo $art['is_breaking'] ? '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg> Active' : 'Off'; ?>
                                            </a>
                                        </td>
                                        <td><span style="display: inline-flex; align-items: center; gap: 3px;"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--text-3);"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg><?php echo number_format($art['views_count']); ?></span></td>
                                        <td style="text-align: right;">
                                            <div style="display: flex; gap: 6px; justify-content: flex-end;">
                                                <a href="article_edit.php?id=<?php echo $art['id']; ?>" class="btn btn-outline btn-sm" style="padding: 4px 10px;">
                                                    Edit
                                                </a>
                                                <a href="articles.php?action=delete&id=<?php echo $art['id']; ?>" class="btn btn-danger btn-sm" style="padding: 4px 10px;" onclick="return confirm('Permanently delete this article and all associated comments?');">
                                                    Delete
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" style="text-align: center; padding: 40px; color: var(--text-3);">
                                        No articles found matching the filter criteria.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html>
