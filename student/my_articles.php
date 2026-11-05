<?php
/**
 * Student Submissions List & Status Tracking
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

// Handle deletion of personal submission if in pending/draft state
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delId = (int)$_GET['id'];
    try {
        $stmt = $db->prepare("DELETE FROM articles WHERE id = ? AND author_id = ? AND status IN ('pending', 'draft', 'rejected')");
        $stmt->execute([$delId, $user['id']]);
        if ($stmt->rowCount() > 0) {
            setFlashMessage('info', 'Article submission removed successfully.');
        } else {
            setFlashMessage('danger', 'Unable to delete article (only pending or rejected articles can be removed).');
        }
        redirect('my_articles.php');
    } catch (PDOException $e) {
        setFlashMessage('danger', 'Error removing submission: ' . $e->getMessage());
    }
}

// Fetch all articles submitted by this student
$submissions = [];
try {
    $stmt = $db->prepare("
        SELECT a.*, c.name as category_name, c.badge_color,
               (SELECT COUNT(*) FROM comments cm WHERE cm.article_id = a.id AND cm.status = 'approved') as comments_count
        FROM articles a
        JOIN categories c ON a.category_id = c.id
        WHERE a.author_id = ?
        ORDER BY a.created_at DESC
    ");
    $stmt->execute([$user['id']]);
    $submissions = $stmt->fetchAll();
} catch (PDOException $e) {}

$pageTitle = 'My Submissions';
include __DIR__ . '/../includes/header.php';
?>

<main class="container" style="padding-top: 30px; padding-bottom: 60px;">
    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <a href="dashboard.php">Student Portal</a>
        <span>&rsaquo;</span>
        <span>My Submissions</span>
    </div>

    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px;">
        <div>
            <h1 style="font-size: 1.6rem; font-weight: 800; color: var(--text-1); margin-bottom: 4px;">
                My Submitted Articles &amp; Notices
            </h1>
            <p style="color: var(--text-3); font-size: 0.9rem;">
                Review the progress and administrative editorial decisions on your submissions.
            </p>
        </div>

        <a href="submit_article.php" class="btn btn-primary btn-sm">
            + Submit New Article
        </a>
    </div>

    <div class="table-card">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 70px;">Cover</th>
                        <th>Title &amp; Headline</th>
                        <th>Category</th>
                        <th>Date Submitted</th>
                        <th>Status</th>
                        <th>Views / Comments</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($submissions)): ?>
                        <?php foreach ($submissions as $art): ?>
                            <tr>
                                <td>
                                    <div style="width: 50px; height: 38px; border-radius: 6px; overflow: hidden; background: var(--surface-3);">
                                        <img src="<?php echo htmlspecialchars(getArticleImage($art['image_url'] ?? '', $art['category_slug'] ?? '', '../')); ?>"
                                            alt=""
                                            loading="lazy"
                                            onerror="this.onerror=null; this.src='../assets/images/default-news.svg';"
                                            style="width: 100%; height: 100%; object-fit: cover;">
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 700; color: var(--text-1); margin-bottom: 3px;">
                                        <?php echo htmlspecialchars($art['title']); ?>
                                    </div>
                                    <?php if ($art['status'] === 'rejected' && !empty($art['admin_feedback'])): ?>
                                        <div style="background: var(--urgent-bg); border-left: 3px solid var(--urgent); padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; color: var(--urgent); margin-top: 4px;">
                                            <strong>Editor Feedback:</strong> <?php echo htmlspecialchars($art['admin_feedback']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge-cat" style="background-color: <?php echo htmlspecialchars($art['badge_color'] ?: '#006a4e'); ?>; font-size: 0.72rem;">
                                        <?php echo htmlspecialchars($art['category_name']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span style="font-size: 0.85rem; color: var(--text-3);">
                                        <?php echo formatDate($art['created_at']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo htmlspecialchars($art['status']); ?>">
                                        <?php echo ucfirst($art['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span style="font-size: 0.85rem; color: var(--text-3); display: inline-flex; align-items: center; gap: 4px;">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-1px;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg> <?php echo number_format($art['views_count']); ?>
                                        <span>&bull;</span>
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-1px;"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg> <?php echo $art['comments_count']; ?>
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <div style="display: flex; gap: 6px; justify-content: flex-end;">
                                        <?php if ($art['status'] === 'approved'): ?>
                                            <a href="../article.php?slug=<?php echo htmlspecialchars($art['slug']); ?>" class="btn btn-outline btn-sm" style="padding: 4px 10px;">
                                                View Live
                                            </a>
                                        <?php else: ?>
                                            <a href="my_articles.php?action=delete&id=<?php echo $art['id']; ?>" class="btn btn-danger btn-sm" style="padding: 4px 10px;" onclick="return confirm('Are you sure you want to remove this submission?');">
                                                Delete
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px; color: var(--text-3);">
                                You haven't submitted any articles yet. Ready to share a story? 
                                <a href="submit_article.php" style="color: var(--brand); font-weight: 700;">Click here to submit</a>.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
