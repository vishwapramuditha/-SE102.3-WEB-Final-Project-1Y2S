<?php
/**
 * Student Saved Bookmarks & Reading List
 * Module: Web and Mobile Application Development (SE102.3)
 * NSBM Green University - Faculty of Computing
 */

require_once __DIR__ . '/../config/functions.php';
requireLogin('../login.php');

$user = currentUser();
$db = getDB();

if (!$db) {
    die("Database connection error. Please run setup.php.");
}

// Remove bookmark action
if (isset($_GET['remove']) && is_numeric($_GET['remove'])) {
    $artId = (int)$_GET['remove'];
    try {
        $del = $db->prepare("DELETE FROM bookmarks WHERE user_id = ? AND article_id = ?");
        $del->execute([$user['id'], $artId]);
        setFlashMessage('info', 'Article removed from saved bookmarks.');
        redirect('bookmarks.php');
    } catch (PDOException $e) {}
}

// Fetch bookmarked articles
$bookmarks = [];
try {
    $stmt = $db->prepare("
        SELECT a.*, c.name as category_name, c.badge_color, b.created_at as bookmarked_at,
               (SELECT COUNT(*) FROM comments cm WHERE cm.article_id = a.id AND cm.status = 'approved') as comments_count
        FROM bookmarks b
        JOIN articles a ON b.article_id = a.id
        JOIN categories c ON a.category_id = c.id
        WHERE b.user_id = ? AND a.status = 'approved'
        ORDER BY b.created_at DESC
    ");
    $stmt->execute([$user['id']]);
    $bookmarks = $stmt->fetchAll();
} catch (PDOException $e) {}

$pageTitle = 'Saved Bookmarks';
include __DIR__ . '/../includes/header.php';
?>

<main class="container" style="padding-top: 30px; padding-bottom: 60px;">
    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <a href="dashboard.php">Student Portal</a>
        <span>&rsaquo;</span>
        <span>Reading List / Bookmarks</span>
    </div>

    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px;">
        <div>
            <h1 style="font-size: 1.6rem; font-weight: 800; color: var(--text-1); margin-bottom: 4px;">
                My Saved Articles &amp; Reading List
            </h1>
            <p style="color: var(--text-3); font-size: 0.9rem;">
                Articles bookmarked for offline study, revision, or later reading.
            </p>
        </div>

        <span style="font-size: 0.9rem; font-weight: 700; color: var(--brand); background: var(--brand-dim); padding: 6px 14px; border-radius: 20px;">
            <?php echo count($bookmarks); ?> Saved Stories
        </span>
    </div>

    <?php if (!empty($bookmarks)): ?>
        <div class="news-cards-grid" style="grid-template-columns: repeat(3, 1fr);">
            <?php foreach ($bookmarks as $art): ?>
                <article class="news-card" id="bookmark-card-<?php echo $art['id']; ?>">
                    <div class="card-img-wrap" style="height: 180px;">
                        <a href="../article.php?slug=<?php echo htmlspecialchars($art['slug']); ?>">
                            <img src="<?php echo htmlspecialchars(getArticleImage($art['image_url'] ?? '', $art['category_slug'] ?? '', '../')); ?>"
                                alt="<?php echo htmlspecialchars($art['title']); ?>"
                                loading="lazy"
                                onerror="this.onerror=null; this.src='../assets/images/default-news.svg';">
                        </a>
                        <span class="badge-cat card-badge-pos" style="background-color: <?php echo htmlspecialchars($art['badge_color'] ?: '#006a4e'); ?>;">
                            <?php echo htmlspecialchars($art['category_name']); ?>
                        </span>
                        <a href="bookmarks.php?remove=<?php echo $art['id']; ?>" class="card-bookmark-btn active" title="Remove from bookmarks" onclick="return confirm('Remove from bookmarks?');">
                            <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M2 2v13.5a.5.5 0 0 0 .74.439L8 13.069l5.26 2.87A.5.5 0 0 0 14 15.5V2a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2z"/></svg>
                        </a>
                    </div>

                    <div class="card-body">
                        <div class="card-meta">
                            <span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-1px; margin-right:3px;"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg> <?php echo formatDate($art['published_at']); ?></span>
                        </div>
                        <h3 class="card-title" style="font-size: 1.05rem;">
                            <a href="../article.php?slug=<?php echo htmlspecialchars($art['slug']); ?>">
                                <?php echo htmlspecialchars($art['title']); ?>
                            </a>
                        </h3>
                        <p class="card-snippet" style="font-size: 0.88rem;">
                            <?php echo htmlspecialchars($art['excerpt'] ?: truncateWords($art['content'], 18)); ?>
                        </p>
                        <div class="card-footer">
                            <span style="font-size: 0.78rem; color: var(--text-4);">
                                Saved <?php echo timeAgo($art['bookmarked_at']); ?>
                            </span>
                            <div style="display: flex; gap: 8px;">
                                <a href="../article.php?slug=<?php echo htmlspecialchars($art['slug']); ?>" style="font-weight: 700; font-size: 0.85rem; color: var(--brand);">
                                    Read Article &rarr;
                                </a>
                            </div>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div style="background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 48px; text-align: center;">
            <div style="display: flex; justify-content: center; margin-bottom: 14px; color: var(--text-4);">
                <svg width="46" height="46" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z"/></svg>
            </div>
            <h3 style="font-size: 1.2rem; font-weight: 700; color: var(--text-1); margin-bottom: 6px;">Your reading list is empty</h3>
            <p style="color: var(--text-3); font-size: 0.92rem; margin-bottom: 18px;">Click the bookmark icon on any campus news article to save it here.</p>
            <a href="../news.php" class="btn btn-primary btn-sm">Explore News Feed</a>
        </div>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
