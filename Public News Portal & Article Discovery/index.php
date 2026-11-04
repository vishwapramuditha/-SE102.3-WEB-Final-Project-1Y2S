<?php
require_once __DIR__ . '/config/functions.php';

$pageTitle = 'Home';
$db = getDB();

$featuredArticle = null;
$secondaryFeatured = [];
$recentArticles = [];
$announcements = [];
$categories = [];

if ($db) {
    try {
        // Fetch categories with article counts
        $catStmt = $db->query("
            SELECT c.*, COUNT(a.id) as article_count 
            FROM categories c 
            LEFT JOIN articles a ON c.id = a.category_id AND a.status = 'approved'
            GROUP BY c.id 
            ORDER BY c.id ASC
        ");
        $categories = $catStmt->fetchAll();

        // 1. Featured Article (Hero)
        $featStmt = $db->query("
            SELECT a.*, c.name as category_name, c.slug as category_slug, c.badge_color, u.full_name as author_name,
                   (SELECT COUNT(*) FROM comments cm WHERE cm.article_id = a.id AND cm.status = 'approved') as comments_count
            FROM articles a
            JOIN categories c ON a.category_id = c.id
            JOIN users u ON a.author_id = u.id
            WHERE a.status = 'approved' AND a.is_featured = 1
            ORDER BY a.published_at DESC
            LIMIT 1
        ");
        $featuredArticle = $featStmt->fetch();

        // Fallback if no featured
        if (!$featuredArticle) {
            $featStmt = $db->query("
                SELECT a.*, c.name as category_name, c.slug as category_slug, c.badge_color, u.full_name as author_name,
                       (SELECT COUNT(*) FROM comments cm WHERE cm.article_id = a.id AND cm.status = 'approved') as comments_count
                FROM articles a
                JOIN categories c ON a.category_id = c.id
                JOIN users u ON a.author_id = u.id
                WHERE a.status = 'approved'
                ORDER BY a.published_at DESC
                LIMIT 1
            ");
            $featuredArticle = $featStmt->fetch();
        }

        // 2. Secondary featured articles
        $excludeId = $featuredArticle ? $featuredArticle['id'] : 0;
        $secStmt = $db->prepare("
            SELECT a.*, c.name as category_name, c.slug as category_slug, c.badge_color, u.full_name as author_name
            FROM articles a
            JOIN categories c ON a.category_id = c.id
            JOIN users u ON a.author_id = u.id
            WHERE a.status = 'approved' AND a.id != ?
            ORDER BY a.published_at DESC
            LIMIT 2
        ");
        $secStmt->execute([$excludeId]);
        $secondaryFeatured = $secStmt->fetchAll();

        // 3. Recent Articles Grid with Pagination
        $excludeIds = [$excludeId];
        foreach ($secondaryFeatured as $sec) {
            $excludeIds[] = $sec['id'];
        }
        $inClause = implode(',', array_fill(0, count($excludeIds), '?'));

        $feedPage = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        $feedPerPage = 6;

        $countStmt = $db->prepare("
            SELECT COUNT(*) 
            FROM articles a 
            WHERE a.status = 'approved' AND a.id NOT IN ($inClause)
        ");
        $countStmt->execute($excludeIds);
        $totalFeedArticles = (int) $countStmt->fetchColumn();
        $feedTotalPages = max(1, (int) ceil($totalFeedArticles / $feedPerPage));
        if ($feedPage > $feedTotalPages) {
            $feedPage = $feedTotalPages;
        }
        $feedOffset = ($feedPage - 1) * $feedPerPage;

        $recStmt = $db->prepare("
            SELECT a.*, c.name as category_name, c.slug as category_slug, c.badge_color, u.full_name as author_name,
                   (SELECT COUNT(*) FROM comments cm WHERE cm.article_id = a.id AND cm.status = 'approved') as comments_count
            FROM articles a
            JOIN categories c ON a.category_id = c.id
            JOIN users u ON a.author_id = u.id
            WHERE a.status = 'approved' AND a.id NOT IN ($inClause)
            ORDER BY a.published_at DESC
            LIMIT $feedPerPage OFFSET $feedOffset
        ");
        $recStmt->execute($excludeIds);
        $recentArticles = $recStmt->fetchAll();

        // 4. Campus Announcements (Active)
        $annStmt = $db->query("
            SELECT an.*, u.full_name as author_name
            FROM announcements an
            JOIN users u ON an.author_id = u.id
            WHERE an.is_active = 1
            ORDER BY FIELD(an.priority, 'urgent', 'high', 'normal'), an.created_at DESC
            LIMIT 5
        ");
        $announcements = $annStmt->fetchAll();

    } catch (PDOException $e) {
        $dbError = $e->getMessage();
    }
}

include __DIR__ . '/includes/header.php';
?>

<main class="container">
    <!-- HERO SECTION -->
    <?php if ($featuredArticle): ?>
        <section class="hero-section">
            <div class="hero-grid">
                <!-- Main Hero Story -->
                <a href="article.php?slug=<?php echo htmlspecialchars($featuredArticle['slug']); ?>"
                    class="hero-primary-card">
                    <img src="<?php echo htmlspecialchars(getArticleImage($featuredArticle['image_url'] ?? '', $featuredArticle['category_slug'] ?? '')); ?>"
                        alt="<?php echo htmlspecialchars($featuredArticle['title']); ?>"
                        loading="eager"
                        onerror="this.onerror=null; this.src='assets/images/default-news.svg';">
                    <div class="hero-overlay">
                        <div class="hero-badge-row">
                            <span class="badge-cat"
                                style="background-color: <?php echo htmlspecialchars($featuredArticle['badge_color'] ?: 'var(--brand-dark)'); ?>;">
                                <?php echo htmlspecialchars($featuredArticle['category_name']); ?>
                            </span>
                            <?php if ($featuredArticle['is_breaking']): ?>
                                <span class="ticker-badge" style="font-size:0.75rem;">Breaking</span>
                            <?php endif; ?>
                        </div>
                        <h1 class="hero-title"><?php echo htmlspecialchars($featuredArticle['title']); ?></h1>
                        <p class="hero-excerpt">
                            <?php echo htmlspecialchars($featuredArticle['excerpt'] ?: truncateWords($featuredArticle['content'], 25)); ?>
                        </p>
                        <div class="meta-row">
                            <span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" style="vertical-align:-1px; margin-right:2px;">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                    <circle cx="12" cy="7" r="4" />
                                </svg> <?php echo htmlspecialchars($featuredArticle['author_name']); ?></span>
                            <span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" style="vertical-align:-1px; margin-right:2px;">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                                    <line x1="16" y1="2" x2="16" y2="6" />
                                    <line x1="8" y1="2" x2="8" y2="6" />
                                    <line x1="3" y1="10" x2="21" y2="10" />
                                </svg> <?php echo formatDate($featuredArticle['published_at']); ?></span>
                            <span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" style="vertical-align:-1px; margin-right:2px;">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg> <?php echo number_format($featuredArticle['views_count']); ?> views</span>
                            <span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" style="vertical-align:-1px; margin-right:2px;">
                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                                </svg> <?php echo $featuredArticle['comments_count']; ?> comments</span>
                        </div>
                    </div>
                </a>

                <!-- Secondary Hero Column -->
                <div class="hero-sidebar">
                    <?php foreach ($secondaryFeatured as $sec): ?>
                        <a href="article.php?slug=<?php echo htmlspecialchars($sec['slug']); ?>" class="hero-side-card">
                            <div style="height: 140px; overflow: hidden; background: var(--surface-2); position: relative;">
                                <img src="<?php echo htmlspecialchars(getArticleImage($sec['image_url'] ?? '', $sec['category_slug'] ?? '')); ?>"
                                    alt="<?php echo htmlspecialchars($sec['title']); ?>"
                                    loading="lazy"
                                    onerror="this.onerror=null; this.src='assets/images/default-news.svg';"
                                    style="width:100%; height:100%; object-fit:cover;">
                            </div>
                            <div class="hero-side-body">
                                <div>
                                    <span class="badge-cat"
                                        style="background-color: <?php echo htmlspecialchars($sec['badge_color'] ?: 'var(--brand-dark)'); ?>; margin-bottom: 6px;">
                                        <?php echo htmlspecialchars($sec['category_name']); ?>
                                    </span>
                                    <h3 class="hero-side-title"><?php echo htmlspecialchars($sec['title']); ?></h3>
                                </div>
                                <div class="card-meta" style="margin-top: 10px;">
                                    <span><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" style="vertical-align:-1px; margin-right:2px;">
                                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                                            <line x1="16" y1="2" x2="16" y2="6" />
                                            <line x1="8" y1="2" x2="8" y2="6" />
                                            <line x1="3" y1="10" x2="21" y2="10" />
                                        </svg> <?php echo timeAgo($sec['published_at']); ?></span>
                                    <span><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" style="vertical-align:-1px; margin-right:2px;">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                            <circle cx="12" cy="12" r="3" />
                                        </svg> <?php echo number_format($sec['views_count']); ?></span>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- FILTER & SEARCH STRIP -->
    <section class="filter-search-bar">
        <div class="filter-row">
            <!-- Category Pills -->
            <div class="category-pills">
                <a href="news.php" class="pill-link active">
                    All Stories
                </a>
                <?php foreach ($categories as $cat): ?>
                    <a href="news.php?category=<?php echo urlencode($cat['slug']); ?>" class="pill-link">
                        <?php echo htmlspecialchars($cat['name']); ?>
                        <span class="pill-count"><?php echo $cat['article_count']; ?></span>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Search Form -->
            <form action="news.php" method="GET" class="search-box-form">
                <input type="text" name="q" class="input-field" placeholder="Search news by title or keyword..."
                    required>
                <button type="submit" class="btn btn-primary btn-sm">Search</button>
            </form>
        </div>
    </section>

    <!-- MAIN TWO-COLUMN CONTENT GRID -->
    <div class="news-layout-grid">
        <!-- LEFT: Recent News Feed -->
        <section id="campus-feed">
            <div class="section-header">
                <div>
                    <h2 class="section-title">Latest Campus Stories</h2>
                    <p style="color: var(--text-3); font-size: 0.85rem; margin: 2px 0 0 0;">Recent reports from
                        faculties, student clubs &amp; societies</p>
                </div>
                <a href="news.php" class="btn btn-outline btn-sm">
                    View All in Archive
                    (<?php echo $totalFeedArticles + count($secondaryFeatured) + ($featuredArticle ? 1 : 0); ?>) &rarr;
                </a>
            </div>

            <?php if (!empty($recentArticles)): ?>
                <div class="news-cards-grid">
                    <?php foreach ($recentArticles as $art): ?>
                        <?php $isBooked = isLoggedIn() ? isBookmarked($_SESSION['user_id'], $art['id']) : false; ?>
                        <article class="news-card">
                            <div class="card-img-wrap">
                                <a href="article.php?slug=<?php echo htmlspecialchars($art['slug']); ?>">
                                    <img src="<?php echo htmlspecialchars(getArticleImage($art['image_url'] ?? '', $art['category_slug'] ?? '')); ?>"
                                        alt="<?php echo htmlspecialchars($art['title']); ?>"
                                        loading="lazy"
                                        onerror="this.onerror=null; this.src='assets/images/default-news.svg';">
                                </a>
                                <span class="badge-cat card-badge-pos"
                                    style="background-color: <?php echo htmlspecialchars($art['badge_color'] ?: 'var(--brand-dark)'); ?>;">
                                    <?php echo htmlspecialchars($art['category_name']); ?>
                                </span>
                                <button type="button"
                                    class="card-bookmark-btn bookmark-toggle-btn <?php echo $isBooked ? 'active' : ''; ?>"
                                    data-article-id="<?php echo $art['id']; ?>"
                                    title="<?php echo $isBooked ? 'Remove Bookmark' : 'Save Article'; ?>">
                                    <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                                        <path
                                            d="M2 2v13.5a.5.5 0 0 0 .74.439L8 13.069l5.26 2.87A.5.5 0 0 0 14 15.5V2a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2z" />
                                    </svg>
                                </button>
                            </div>

                            <div class="card-body">
                                <div class="card-meta">
                                    <span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" style="vertical-align:-1px; margin-right:2px;">
                                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                                            <line x1="16" y1="2" x2="16" y2="6" />
                                            <line x1="8" y1="2" x2="8" y2="6" />
                                            <line x1="3" y1="10" x2="21" y2="10" />
                                        </svg> <?php echo formatDate($art['published_at']); ?></span>
                                    <span>&bull;</span>
                                    <span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" style="vertical-align:-1px; margin-right:2px;">
                                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                            <circle cx="12" cy="7" r="4" />
                                        </svg> <?php echo htmlspecialchars($art['author_name']); ?></span>
                                </div>
                                <h3 class="card-title">
                                    <a href="article.php?slug=<?php echo htmlspecialchars($art['slug']); ?>">
                                        <?php echo htmlspecialchars($art['title']); ?>
                                    </a>
                                </h3>
                                <p class="card-snippet">
                                    <?php echo htmlspecialchars($art['excerpt'] ?: truncateWords($art['content'], 20)); ?>
                                </p>
                                <div class="card-footer">
                                    <div class="card-stats">
                                        <span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                stroke-width="2" style="vertical-align:-1px; margin-right:2px;">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                                <circle cx="12" cy="12" r="3" />
                                            </svg> <?php echo number_format($art['views_count']); ?></span>
                                        <span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                stroke-width="2" style="vertical-align:-1px; margin-right:2px;">
                                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                                            </svg> <?php echo $art['comments_count']; ?></span>
                                    </div>
                                    <a href="article.php?slug=<?php echo htmlspecialchars($art['slug']); ?>"
                                        style="font-weight: 700; font-size: 0.85rem; color: var(--brand);">
                                        Read More &rarr;
                                    </a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <!-- Feed Page Bar -->
                <?php if ($feedTotalPages > 1): ?>
                    <div class="pagination-wrapper" style="margin-top: 28px; padding-top: 18px;">
                        <div class="pagination-info">
                            Showing page <strong><?php echo $feedPage; ?></strong> of
                            <strong><?php echo $feedTotalPages; ?></strong> &bull;
                            <strong><?php echo $totalFeedArticles; ?></strong> campus stories
                        </div>
                        <nav class="pagination-bar" aria-label="Campus Stories Navigation">
                            <a href="index.php?page=<?php echo max(1, $feedPage - 1); ?>#campus-feed"
                                class="page-btn <?php echo ($feedPage <= 1) ? 'disabled' : ''; ?>"
                                aria-label="Previous Stories">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2.5">
                                    <polyline points="15 18 9 12 15 6" />
                                </svg>
                                <span>Previous</span>
                            </a>

                            <?php for ($p = 1; $p <= $feedTotalPages; $p++): ?>
                                <a href="index.php?page=<?php echo $p; ?>#campus-feed"
                                    class="page-pill <?php echo ($p === $feedPage) ? 'active' : ''; ?>">
                                    <?php echo $p; ?>
                                </a>
                            <?php endfor; ?>

                            <a href="index.php?page=<?php echo min($feedTotalPages, $feedPage + 1); ?>#campus-feed"
                                class="page-btn <?php echo ($feedPage >= $feedTotalPages) ? 'disabled' : ''; ?>"
                                aria-label="Next Stories">
                                <span>Next</span>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2.5">
                                    <polyline points="9 18 15 12 9 6" />
                                </svg>
                            </a>
                        </nav>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div
                    style="background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 40px; text-align: center;">
                    <p style="color: var(--text-3); font-size: 1rem; margin-bottom: 12px;">No news stories published yet.
                    </p>
                    <a href="setup.php" class="btn btn-primary btn-sm">Load Sample Database</a>
                </div>
            <?php endif; ?>
        </section>

        <!-- RIGHT: Announcements & Subscriptions Sidebar -->
        <aside>
            <!-- Campus Announcements Box -->
            <div class="announcements-widget">
                <div class="widget-title">
                    <span><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" style="vertical-align:-2px; margin-right:4px;">
                            <path d="M22 2L11 13" />
                            <polygon points="22 2 15 22 11 13 2 9 22 2" />
                        </svg> Campus Announcements</span>
                    <a href="announcements.php" style="font-size: 0.82rem; font-weight: 600;">View All &rarr;</a>
                </div>

                <div class="announcement-list">
                    <?php if (!empty($announcements)): ?>
                        <?php foreach ($announcements as $ann): ?>
                            <div class="announcement-item">
                                <div class="announcement-header">
                                    <span class="badge-priority badge-<?php echo htmlspecialchars($ann['priority']); ?>">
                                        <?php echo htmlspecialchars($ann['priority']); ?>
                                    </span>
                                </div>
                                <div class="announcement-title">
                                    <?php echo htmlspecialchars($ann['title']); ?>
                                </div>
                                <div class="announcement-snippet">
                                    <?php echo htmlspecialchars(truncateWords($ann['content'], 18)); ?>
                                </div>
                                <div class="announcement-date">
                                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2" style="vertical-align:-1px; margin-right:2px;">
                                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                                        <line x1="16" y1="2" x2="16" y2="6" />
                                        <line x1="8" y1="2" x2="8" y2="6" />
                                        <line x1="3" y1="10" x2="21" y2="10" />
                                    </svg> <?php echo formatDate($ann['created_at']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: var(--text-4); font-size: 0.9rem;">No active announcements currently.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Student Category Subscription Prompt -->
            <div class="subscribe-widget">
                <h4><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        style="vertical-align:-2px; margin-right:4px;">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
                        <path d="M13.73 21a2 2 0 0 1-3.46 0" />
                    </svg> Stay Informed!</h4>
                <p>Personalize your news feed by subscribing to specific categories: Academic, Events, Sports, or
                    General.</p>
                <?php if (isLoggedIn()): ?>
                    <a href="student/subscriptions.php" class="btn btn-accent btn-sm" style="width: 100%;">
                        Manage Subscriptions
                    </a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-accent btn-sm" style="width: 100%;">
                        Sign In to Subscribe
                    </a>
                <?php endif; ?>
            </div>
        </aside>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
