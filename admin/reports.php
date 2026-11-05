<?php
/**
 * Readership & Engagement Analytics Reports
 * Module: Web and Mobile Application Development (SE102.3)
 * NSBM Green University - Faculty of Computing
 */

require_once __DIR__ . '/../config/functions.php';
requireAdmin('../login.php');

$db = getDB();
if (!$db) {
    die("Database connection error. Please run setup.php.");
}

// 1. High-Level Metrics
$totalArticles = 0;
$totalViews = 0;
$totalComments = 0;
$totalStudents = 0;

try {
    $totalArticles = (int)$db->query("SELECT COUNT(*) FROM articles WHERE status = 'approved'")->fetchColumn();
    $totalViews = (int)$db->query("SELECT COALESCE(SUM(views_count), 0) FROM articles WHERE status = 'approved'")->fetchColumn();
    $totalComments = (int)$db->query("SELECT COUNT(*) FROM comments WHERE status = 'approved'")->fetchColumn();
    $totalStudents = (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
} catch (PDOException $e) {}

$avgViewsPerArticle = $totalArticles > 0 ? round($totalViews / $totalArticles) : 0;
$avgCommentsPerArticle = $totalArticles > 0 ? round($totalComments / $totalArticles, 1) : 0;

// 2. Readership by Category
$categoryStats = [];
try {
    $stmt = $db->query("
        SELECT c.id, c.name, c.badge_color, 
               COUNT(a.id) as article_count,
               COALESCE(SUM(a.views_count), 0) as category_views,
               COALESCE(SUM((SELECT COUNT(*) FROM comments cm WHERE cm.article_id = a.id)), 0) as category_comments
        FROM categories c
        LEFT JOIN articles a ON c.id = a.category_id AND a.status = 'approved'
        GROUP BY c.id
        ORDER BY category_views DESC
    ");
    $categoryStats = $stmt->fetchAll();
} catch (PDOException $e) {}

// 3. Top 5 Most Read Stories
$topArticles = [];
try {
    $stmt = $db->query("
        SELECT a.id, a.title, a.slug, a.views_count, a.published_at,
               c.name as category_name, c.badge_color,
               u.full_name as author_name,
               (SELECT COUNT(*) FROM comments cm WHERE cm.article_id = a.id) as comments_count
        FROM articles a
        JOIN categories c ON a.category_id = c.id
        JOIN users u ON a.author_id = u.id
        WHERE a.status = 'approved'
        ORDER BY a.views_count DESC
        LIMIT 5
    ");
    $topArticles = $stmt->fetchAll();
} catch (PDOException $e) {}

// 4. Most Active Student Contributors
$topContributors = [];
try {
    $stmt = $db->query("
        SELECT u.id, u.full_name, u.student_id, u.department,
               COUNT(a.id) as submissions_count,
               COALESCE(SUM(a.views_count), 0) as total_readership
        FROM users u
        JOIN articles a ON u.id = a.author_id
        WHERE u.role = 'student'
        GROUP BY u.id
        ORDER BY submissions_count DESC, total_readership DESC
        LIMIT 5
    ");
    $topContributors = $stmt->fetchAll();
} catch (PDOException $e) {}

$pageTitle = 'Readership & Engagement Reports';
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
    <style>
        @media print {
            .admin-sidebar, .admin-topbar, .btn, .no-print { display: none !important; }
            .admin-layout { display: block !important; }
            .admin-main { background: #ffffff !important; }
            .kpi-card, .table-card { box-shadow: none !important; border: 1px solid #ccc !important; }
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include __DIR__ . '/../includes/admin_sidebar.php'; ?>

        <div class="admin-main">
            <?php include __DIR__ . '/../includes/admin_header.php'; ?>

            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
                <div>
                    <h1 style="font-size: 1.5rem; font-weight: 800; color: var(--text-1); margin-bottom: 4px;">
                        Readership &amp; Engagement Reports
                    </h1>
                    <p style="color: var(--text-3); font-size: 0.9rem;">
                        Official analytics report generated for NSBM Faculty of Computing academic review.
                    </p>
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="button" class="btn btn-outline btn-sm no-print" onclick="window.print();" style="display: inline-flex; align-items: center; gap: 6px;">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                        Print / Export PDF
                    </button>
                    <a href="reports.php" class="btn btn-primary btn-sm no-print" style="display: inline-flex; align-items: center; gap: 6px;">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
                        Refresh Data
                    </a>
                </div>
            </div>

            <!-- KPI Metric Cards -->
            <div class="kpi-grid">
                <div class="kpi-card">
                    <span class="kpi-title">Total Readership</span>
                    <div class="kpi-number"><?php echo number_format($totalViews); ?></div>
                    <span class="kpi-sub">Campus views logged</span>
                </div>

                <div class="kpi-card info">
                    <span class="kpi-title">Avg. Views / Article</span>
                    <div class="kpi-number"><?php echo number_format($avgViewsPerArticle); ?></div>
                    <span class="kpi-sub">Reader engagement rate</span>
                </div>

                <div class="kpi-card warning">
                    <span class="kpi-title">Student Discussions</span>
                    <div class="kpi-number"><?php echo number_format($totalComments); ?></div>
                    <span class="kpi-sub"><?php echo $avgCommentsPerArticle; ?> avg per story</span>
                </div>

                <div class="kpi-card">
                    <span class="kpi-title">Registered Students</span>
                    <div class="kpi-number"><?php echo number_format($totalStudents); ?></div>
                    <span class="kpi-sub">Active undergrad accounts</span>
                </div>
            </div>

            <!-- Category Readership Distribution -->
            <div class="table-card" style="margin-bottom: 28px;">
                <div class="table-header">
                    <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--text-1); margin: 0;">
                        1. Category Readership Distribution
                    </h3>
                    <span style="font-size: 0.82rem; color: var(--text-3);">Beat-level readership breakdown</span>
                </div>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Category Name</th>
                                <th>Articles Published</th>
                                <th>Cumulative Views</th>
                                <th>Comments</th>
                                <th style="width: 280px;">Share of Readership</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categoryStats as $cStat): ?>
                                <?php 
                                    $pct = $totalViews > 0 ? round(($cStat['category_views'] / $totalViews) * 100, 1) : 0; 
                                ?>
                                <tr>
                                    <td>
                                        <span class="badge-cat" style="background-color: <?php echo htmlspecialchars($cStat['badge_color'] ?: '#006a4e'); ?>;">
                                            <?php echo htmlspecialchars($cStat['name']); ?>
                                        </span>
                                    </td>
                                    <td><strong><?php echo $cStat['article_count']; ?></strong> articles</td>
                                    <td><strong><?php echo number_format($cStat['category_views']); ?></strong> views</td>
                                    <td><?php echo $cStat['category_comments']; ?> comments</td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <div style="flex: 1; height: 8px; background: var(--surface-3); border-radius: 4px; overflow: hidden;">
                                                <div style="width: <?php echo $pct; ?>%; height: 100%; background: <?php echo htmlspecialchars($cStat['badge_color'] ?: '#006a4e'); ?>;"></div>
                                            </div>
                                            <span style="font-size: 0.82rem; font-weight: 700; width: 44px;"><?php echo $pct; ?>%</span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Two-Column Section: Top 5 Read Stories & Top Student Contributors -->
            <div style="display: grid; grid-template-columns: 1.4fr 1fr; gap: 24px;">
                <!-- Top 5 Most Read Stories -->
                <div class="table-card">
                    <div class="table-header">
                        <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--text-1); margin: 0;">
                            2. Top 5 Most-Read Campus Stories
                        </h3>
                    </div>
                    <div class="table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th style="width: 40px;">Rank</th>
                                    <th>Headline</th>
                                    <th>Category</th>
                                    <th>Views</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $rank = 1; ?>
                                <?php foreach ($topArticles as $top): ?>
                                    <tr>
                                        <td>
                                            <span style="display: inline-flex; width: 24px; height: 24px; border-radius: 50%; background: <?php echo $rank === 1 ? '#f59e0b' : 'var(--surface-3)'; ?>; color: <?php echo $rank === 1 ? '#ffffff' : 'var(--text-1)'; ?>; align-items: center; justify-content: center; font-size: 0.78rem; font-weight: 800;">
                                                <?php echo $rank++; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="../article.php?slug=<?php echo htmlspecialchars($top['slug']); ?>" target="_blank" style="font-weight: 700; color: var(--text-1); font-size: 0.88rem;">
                                                <?php echo htmlspecialchars($top['title']); ?>
                                            </a>
                                            <div style="font-size: 0.75rem; color: var(--text-3);">
                                                by <?php echo htmlspecialchars($top['author_name']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge-cat" style="background-color: <?php echo htmlspecialchars($top['badge_color'] ?: '#006a4e'); ?>; font-size: 0.68rem; padding: 2px 6px;">
                                                <?php echo htmlspecialchars($top['category_name']); ?>
                                            </span>
                                        </td>
                                        <td><strong><?php echo number_format($top['views_count']); ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Top Student Contributors -->
                <div class="table-card">
                    <div class="table-header">
                        <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--text-1); margin: 0;">
                            3. Top Student Contributors
                        </h3>
                    </div>
                    <div class="table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Submissions</th>
                                    <th>Readership</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($topContributors)): ?>
                                    <?php foreach ($topContributors as $contrib): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($contrib['full_name']); ?></strong><br>
                                                <span style="font-size: 0.75rem; color: var(--text-3);"><?php echo htmlspecialchars($contrib['student_id']); ?></span>
                                            </td>
                                            <td><strong><?php echo $contrib['submissions_count']; ?></strong> stories</td>
                                            <td><span style="display: inline-flex; align-items: center; gap: 4px;"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--text-3);"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg><?php echo number_format($contrib['total_readership']); ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" style="text-align: center; padding: 24px; color: var(--text-3);">
                                            No student contributions recorded yet.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- University Footer Tag for Reports -->
            <div style="margin-top: 32px; padding: 18px 24px; background: var(--surface); border: 1px solid var(--border); border-radius: 12px; display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem; color: var(--text-3);">
                <div>
                    NSBM Green University &bull; Faculty of Computing &bull; Module: SE102.3 Web &amp; Mobile App Development
                </div>
                <div>
                    Generated on: <?php echo date('F d, Y - h:i A'); ?>
                </div>
            </div>
        </div>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html>
