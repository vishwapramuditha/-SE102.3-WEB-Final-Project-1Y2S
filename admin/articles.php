<?php
/**
 * Admin Dashboard Control Centre
 * Module: Web and Mobile Application Development (SE102.3)
 * NSBM Green University - Faculty of Computing
 */

require_once __DIR__ . '/../config/functions.php';
requireAdmin('../login.php');

$db = getDB();
if (!$db) {
    die("Database connection error. Please run setup.php.");
}

// KPI Metrics
$totalArticles = 0;
$pendingSubmissions = 0;
$totalViews = 0;
$totalComments = 0;

$recentSubmissions = [];
$recentArticles = [];
$recentComments = [];

try {
    // 1. Total articles
    $totalArticles = (int)$db->query("SELECT COUNT(*) FROM articles WHERE status = 'approved'")->fetchColumn();

    // 2. Pending submissions
    $pendingSubmissions = (int)$db->query("SELECT COUNT(*) FROM articles WHERE status = 'pending'")->fetchColumn();

    // 3. Total views
    $totalViews = (int)$db->query("SELECT COALESCE(SUM(views_count), 0) FROM articles")->fetchColumn();

    // 4. Total comments
    $totalComments = (int)$db->query("SELECT COUNT(*) FROM comments")->fetchColumn();

    // 5. Recent student submissions awaiting review
    $stmt = $db->query("
        SELECT a.*, c.name as category_name, c.badge_color, u.full_name as author_name, u.student_id
        FROM articles a
        JOIN categories c ON a.category_id = c.id
        JOIN users u ON a.author_id = u.id
        WHERE a.status = 'pending'
        ORDER BY a.created_at DESC
        LIMIT 5
    ");
    $recentSubmissions = $stmt->fetchAll();

    // 6. Recent published articles
    $stmt = $db->query("
        SELECT a.*, c.name as category_name, c.badge_color, u.full_name as author_name
        FROM articles a
        JOIN categories c ON a.category_id = c.id
        JOIN users u ON a.author_id = u.id
        WHERE a.status = 'approved'
        ORDER BY a.published_at DESC
        LIMIT 5
    ");
    $recentArticles = $stmt->fetchAll();

    // 7. Recent comments
    $stmt = $db->query("
        SELECT cm.*, u.full_name as user_name, a.title as article_title, a.slug as article_slug
        FROM comments cm
        JOIN users u ON cm.user_id = u.id
        JOIN articles a ON cm.article_id = a.id
        ORDER BY cm.created_at DESC
        LIMIT 4
    ");
    $recentComments = $stmt->fetchAll();

} catch (PDOException $e) {}

$pageTitle = 'Dashboard Overview';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> – NSBM NewsDesk Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Playfair+Display:ital,wght@0,700;0,800;1,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="icon" type="image/svg+xml" href="../assets/images/nsbm-logo.svg">
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <?php include __DIR__ . '/../includes/admin_sidebar.php'; ?>

        <!-- Main Body -->
        <div class="admin-main">
            <?php include __DIR__ . '/../includes/admin_header.php'; ?>

            <!-- Page Header -->
            <div class="admin-page-header">
                <h1>Dashboard Overview</h1>
                <p>Welcome back! Here's a snapshot of the NSBM NewsDesk activity.</p>
            </div>

            <!-- KPI Metric Cards -->
            <div class="kpi-grid">
                <div class="kpi-card">
                    <span class="kpi-title">Published Articles</span>
                    <div class="kpi-number"><?php echo number_format($totalArticles); ?></div>
                    <span class="kpi-sub">Live on campus portal</span>
                </div>

                <div class="kpi-card danger">
                    <span class="kpi-title">Pending Submissions</span>
                    <div class="kpi-number" style="color: <?php echo $pendingSubmissions > 0 ? 'var(--urgent)' : 'var(--text-1)'; ?>;">
                        <?php echo $pendingSubmissions; ?>
                    </div>
                    <span class="kpi-sub">Awaiting editorial review</span>
                </div>

                <div class="kpi-card info">
                    <span class="kpi-title">Total Readership</span>
                    <div class="kpi-number"><?php echo number_format($totalViews); ?></div>
                    <span class="kpi-sub">Cumulative page views</span>
                </div>

                <div class="kpi-card warning">
                    <span class="kpi-title">Reader Comments</span>
                    <div class="kpi-number"><?php echo number_format($totalComments); ?></div>
                    <span class="kpi-sub">Student engagement posts</span>
                </div>
            </div>

            <!-- Pending Review Action Card -->
            <?php if (!empty($recentSubmissions)): ?>
                <div class="table-card" style="margin-bottom: 28px; border-left: 4px solid var(--accent);">
                    <div class="table-header table-header-alert">
                        <div>
                            <h3 style="display: flex; align-items: center;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:8px; color: var(--accent); flex-shrink: 0;"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg> Action Required: <?php echo count($recentSubmissions); ?> Submission<?php echo count($recentSubmissions) !== 1 ? 's' : ''; ?> Awaiting Approval</h3>
                            <p>Review and approve or reject submissions from student clubs and societies.</p>
                        </div>
                        <a href="submissions.php" class="btn btn-accent btn-sm">Review All &rarr;</a>
                    </div>
                    <div class="table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Headline</th>
                                    <th>Author</th>
                                    <th>Category</th>
                                    <th>Submitted</th>
                                    <th style="text-align: right;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentSubmissions as $sub): ?>
                                    <tr>
                                        <td><strong style="color: var(--text-1);"><?php echo htmlspecialchars($sub['title']); ?></strong></td>
                                        <td style="color: var(--text-2);"><?php echo htmlspecialchars($sub['author_name']); ?> <span style="font-size:0.75rem; color:var(--text-4);">(<?php echo htmlspecialchars($sub['student_id']); ?>)</span></td>
                                        <td>
                                            <span class="badge-cat" style="background-color: <?php echo htmlspecialchars($sub['badge_color'] ?: '#006a4e'); ?>; font-size:0.72rem;">
                                                <?php echo htmlspecialchars($sub['category_name']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo timeAgo($sub['created_at']); ?></td>
                                        <td style="text-align: right;">
                                            <a href="submissions.php?review=<?php echo $sub['id']; ?>" class="btn btn-primary btn-sm" style="padding: 4px 10px; font-size: 0.8rem;">
                                                Review &rarr;
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Two Column Grid: Published News & Latest Comments -->
            <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 24px;">
                <!-- Left: Recently Published Articles -->
                <div class="table-card">
                    <div class="table-header">
                        <h3>Recent Published Stories</h3>
                        <a href="articles.php" style="font-size: 0.85rem; font-weight: 600; color: var(--brand);">Manage Articles &rarr;</a>
                    </div>
                    <div class="table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>Views</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentArticles as $art): ?>
                                    <tr>
                                        <td>
                                            <a href="../article.php?slug=<?php echo htmlspecialchars($art['slug']); ?>" target="_blank"
                                               style="font-weight: 600; color: var(--text-1);">
                                                <?php echo htmlspecialchars(truncateWords($art['title'], 6)); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <span class="badge-cat" style="background-color: <?php echo htmlspecialchars($art['badge_color'] ?: '#006a4e'); ?>; font-size:0.7rem; padding: 2px 6px;">
                                                <?php echo htmlspecialchars($art['category_name']); ?>
                                            </span>
                                        </td>
                                        <td style="color: var(--text-3); font-size: 0.85rem;">
                                            <?php echo number_format($art['views_count']); ?>
                                        </td>
                                        <td style="font-size: 0.8rem; color: var(--text-3);"><?php echo formatDate($art['published_at']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Right: Latest Reader Comments -->
                <div class="table-card">
                    <div class="table-header">
                        <h3>Latest Comments</h3>
                        <a href="comments.php" style="font-size: 0.85rem; font-weight: 600; color: var(--brand);">Moderate &rarr;</a>
                    </div>
                    <div style="padding: 16px; display: flex; flex-direction: column; gap: 14px;">
                        <?php if (!empty($recentComments)): ?>
                            <?php foreach ($recentComments as $cm): ?>
                                <div class="admin-comment-card">
                                    <div class="admin-comment-row">
                                        <span class="admin-comment-author"><?php echo htmlspecialchars($cm['user_name']); ?></span>
                                        <span><?php echo timeAgo($cm['created_at']); ?></span>
                                    </div>
                                    <div class="admin-comment-text">
                                        "<?php echo htmlspecialchars(truncateWords($cm['comment_text'], 14)); ?>"
                                    </div>
                                    <div class="admin-comment-article">
                                        on: <?php echo htmlspecialchars(truncateWords($cm['article_title'], 5)); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="color: var(--text-4); font-size: 0.88rem;">No comments recorded yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html>
