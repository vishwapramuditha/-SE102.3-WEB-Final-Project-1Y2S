<?php
/**
 * Student Dashboard & Portal Hub
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



try {
    $stmt = $db->prepare("SELECT COUNT(*) FROM articles WHERE author_id = ?");
    $stmt->execute([$user['id']]);
    $submissionCount = (int)$stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM articles WHERE author_id = ? AND status = 'approved'");
    $stmt->execute([$user['id']]);
    $approvedCount = (int)$stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM bookmarks WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $bookmarkCount = (int)$stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM category_subscriptions WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $subscriptionCount = (int)$stmt->fetchColumn();

    $stmt = $db->prepare("
        SELECT a.*, c.name as category_name, c.badge_color
        FROM articles a
        JOIN categories c ON a.category_id = c.id
        WHERE a.author_id = ?
        ORDER BY a.created_at DESC
        LIMIT 4
    ");
    $stmt->execute([$user['id']]);
    $mySubmissions = $stmt->fetchAll();

    $stmt = $db->prepare("
        SELECT a.*, c.name as category_name, c.badge_color, b.created_at as bookmarked_at
        FROM bookmarks b
        JOIN articles a ON b.article_id = a.id
        JOIN categories c ON a.category_id = c.id
        WHERE b.user_id = ?
        ORDER BY b.created_at DESC
        LIMIT 3
    ");
    $stmt->execute([$user['id']]);
    $myBookmarks = $stmt->fetchAll();

} catch (PDOException $e) {}

$pageTitle = 'Student Portal';
include __DIR__ . '/../includes/header.php';
?>

<main class="container" style="padding-top: 32px; padding-bottom: 64px;">

    <!-- Student Welcome Card -->
    <div style="background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: clamp(16px, 4vw, 24px); margin-bottom: 24px; box-shadow: var(--shadow-sm); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; border-left: 5px solid var(--brand);">
        <div style="display: flex; align-items: center; gap: 18px;">
            <div style="width: 60px; height: 60px; border-radius: 50%; background: var(--brand); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1.6rem; font-weight: 800; flex-shrink: 0;">
                <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
            </div>
            <div>
                <h1 style="font-size: 1.35rem; font-weight: 800; color: var(--text-1); margin-bottom: 5px; letter-spacing: -0.02em;">
                    Welcome back, <?php echo htmlspecialchars(explode(' ', $user['name'])[0]); ?>!
                </h1>
                <div style="display: flex; align-items: center; gap: 10px; font-size: 0.85rem; color: var(--text-3); flex-wrap: wrap;">
                    <span><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-1px; margin-right:3px;"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg> <strong style="color: var(--text-2);">ID:</strong> <?php echo htmlspecialchars($user['student_id'] ?: 'Batch 26.1'); ?></span>
                    <span>&bull;</span>
                    <span><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-1px; margin-right:3px;"><path d="M3 21h18M3 10h18M5 10v11M19 10v11M9 10v11M15 10v11M12 2 2 7h20z"/></svg> <?php echo htmlspecialchars($user['department']); ?></span>
                    <span>&bull;</span>
                    <span class="badge-cat" style="background: var(--brand); font-size: 0.68rem;">Verified Undergrad</span>
                </div>
            </div>
        </div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <a href="submit_article.php" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 6px;">
                <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M8 0a1 1 0 0 1 1 1v6h6a1 1 0 1 1 0 2H9v6a1 1 0 1 1-2 0V9H1a1 1 0 0 1 0-2h6V1a1 1 0 0 1 1-1z"/></svg>
                Submit News Story
            </a>
            <a href="subscriptions.php" class="btn btn-outline" style="display: inline-flex; align-items: center; gap: 6px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                Subscriptions
            </a>
        </div>
    </div>

    <!-- Student Metric Cards -->
    <div class="kpi-grid" style="margin-bottom: 24px;">
        <div class="kpi-card">
            <span class="kpi-title">Articles Submitted</span>
            <div class="kpi-number"><?php echo $submissionCount; ?></div>
            <span class="kpi-sub">Total stories submitted</span>
        </div>
        <div class="kpi-card info">
            <span class="kpi-title">Approved &amp; Live</span>
            <div class="kpi-number"><?php echo $approvedCount; ?></div>
            <span class="kpi-sub">Published to campus feed</span>
        </div>
        <div class="kpi-card warning">
            <span class="kpi-title">Saved Bookmarks</span>
            <div class="kpi-number"><?php echo $bookmarkCount; ?></div>
            <span class="kpi-sub">Articles for later reading</span>
        </div>
        <div class="kpi-card">
            <span class="kpi-title">Category Subscriptions</span>
            <div class="kpi-number"><?php echo $subscriptionCount; ?></div>
            <span class="kpi-sub">Active feed alerts</span>
        </div>
    </div>

    <!-- Quick Navigation Shortcuts -->
    <div class="student-shortcuts-grid">
        <?php
        $shortcuts = [
            [
                'href' => 'submit_article.php',
                'svg'  => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>',
                'label' => 'Submit Article',
                'sub'  => 'Post club / faculty news'
            ],
            [
                'href' => 'my_articles.php',
                'svg'  => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/></svg>',
                'label' => 'My Submissions',
                'sub'  => 'Track approval status'
            ],
            [
                'href' => 'bookmarks.php',
                'svg'  => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z"/></svg>',
                'label' => 'Reading List',
                'sub'  => 'View saved bookmarks'
            ],
            [
                'href' => 'subscriptions.php',
                'svg'  => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>',
                'label' => 'Subscriptions',
                'sub'  => 'Manage category alerts'
            ],
        ];
        foreach ($shortcuts as $sc): ?>
            <a href="<?php echo $sc['href']; ?>" style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--r-md); padding: 18px 20px; text-decoration: none; color: inherit; display: flex; align-items: center; gap: 14px; box-shadow: var(--shadow-xs); transition: all var(--base) var(--ease);"
               onmouseover="this.style.borderColor='var(--brand)';this.style.transform='translateY(-2px)';this.style.boxShadow='var(--shadow-sm)'"
               onmouseout="this.style.borderColor='var(--border)';this.style.transform='';this.style.boxShadow='var(--shadow-xs)'">
                <span style="width: 44px; height: 44px; border-radius: 10px; background: var(--surface-2); display: flex; align-items: center; justify-content: center; color: var(--brand); flex-shrink: 0;"><?php echo $sc['svg']; ?></span>
                <div>
                    <strong style="display: block; font-size: 0.93rem; color: var(--text-1); margin-bottom: 2px;"><?php echo $sc['label']; ?></strong>
                    <span style="font-size: 0.79rem; color: var(--text-3);"><?php echo $sc['sub']; ?></span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Two Columns: Recent Submissions & Bookmarks -->
    <div style="display: grid; grid-template-columns: 3fr 2fr; gap: 24px;">

        <!-- Left: My Submissions -->
        <div style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--r-lg); padding: 22px; box-shadow: var(--shadow-xs);">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; padding-bottom: 14px; border-bottom: 1px solid var(--border);">
                <h3 style="font-size: 1.05rem; font-weight: 700; color: var(--text-1); margin: 0;">My Submitted Articles</h3>
                <a href="my_articles.php" style="font-size: 0.84rem; font-weight: 600; color: var(--brand);">View All &rarr;</a>
            </div>

            <?php if (!empty($mySubmissions)): ?>
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <?php foreach ($mySubmissions as $sub): ?>
                        <div style="padding: 12px 14px; border: 1px solid var(--border); border-radius: var(--r-sm); display: flex; align-items: center; justify-content: space-between; gap: 12px; background: var(--surface-2);">
                            <div style="flex: 1; min-width: 0;">
                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                                    <span class="badge-cat" style="background-color: <?php echo htmlspecialchars($sub['badge_color'] ?: '#006a4e'); ?>; font-size: 0.68rem; padding: 1px 6px;">
                                        <?php echo htmlspecialchars($sub['category_name']); ?>
                                    </span>
                                    <span style="font-size: 0.76rem; color: var(--text-4);"><?php echo formatDate($sub['created_at']); ?></span>
                                </div>
                                <h4 style="font-size: 0.92rem; font-weight: 600; color: var(--text-1); margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 320px;">
                                    <?php echo htmlspecialchars($sub['title']); ?>
                                </h4>
                                <?php if ($sub['status'] === 'rejected' && !empty($sub['admin_feedback'])): ?>
                                    <div style="font-size: 0.78rem; color: var(--urgent); background: var(--urgent-bg); padding: 4px 8px; border-radius: var(--r-xs); margin-top: 5px;">
                                        <strong>Admin note:</strong> <?php echo htmlspecialchars($sub['admin_feedback']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <span class="status-badge status-<?php echo htmlspecialchars($sub['status']); ?>" style="flex-shrink: 0;">
                                <?php echo ucfirst($sub['status']); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 36px 0; color: var(--text-3);">
                    <div style="display: flex; justify-content: center; margin-bottom: 12px; color: var(--text-4);">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                    </div>
                    <p style="margin-bottom: 14px; font-size: 0.9rem;">You haven't submitted any news stories yet.</p>
                    <a href="submit_article.php" class="btn btn-primary btn-sm">Submit Your First Story</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Right: Recent Bookmarks -->
        <div style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--r-lg); padding: 22px; box-shadow: var(--shadow-xs);">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; padding-bottom: 14px; border-bottom: 1px solid var(--border);">
                <h3 style="font-size: 1.05rem; font-weight: 700; color: var(--text-1); margin: 0;">Saved Articles</h3>
                <a href="bookmarks.php" style="font-size: 0.84rem; font-weight: 600; color: var(--brand);">All Bookmarks &rarr;</a>
            </div>

            <?php if (!empty($myBookmarks)): ?>
                <div style="display: flex; flex-direction: column; gap: 14px;">
                    <?php foreach ($myBookmarks as $bm): ?>
                        <div style="display: flex; gap: 12px; align-items: flex-start; padding-bottom: 14px; border-bottom: 1px solid var(--border);">
                            <div style="width: 56px; height: 52px; border-radius: var(--r-sm); overflow: hidden; background: var(--surface-3); flex-shrink: 0;">
                                <img src="<?php echo htmlspecialchars(getArticleImage($bm['image_url'] ?? '', $bm['category_slug'] ?? '', '../')); ?>"
                                    alt=""
                                    loading="lazy"
                                    onerror="this.onerror=null; this.src='../assets/images/default-news.svg';"
                                    style="width: 100%; height: 100%; object-fit: cover;">
                            </div>
                            <div style="flex: 1; min-width: 0;">
                                <a href="../article.php?slug=<?php echo htmlspecialchars($bm['slug']); ?>" style="font-size: 0.88rem; font-weight: 600; color: var(--text-1); line-height: 1.35; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; margin-bottom: 4px; text-decoration: none;">
                                    <?php echo htmlspecialchars($bm['title']); ?>
                                </a>
                                <span style="font-size: 0.74rem; color: var(--text-4);">Saved <?php echo timeAgo($bm['bookmarked_at']); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 36px 0; color: var(--text-3);">
                    <div style="display: flex; justify-content: center; margin-bottom: 12px; color: var(--text-4);">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z"/></svg>
                    </div>
                    <p style="margin-bottom: 14px; font-size: 0.9rem;">No articles saved in your reading list.</p>
                    <a href="../news.php" class="btn btn-outline btn-sm">Explore News Feed</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
