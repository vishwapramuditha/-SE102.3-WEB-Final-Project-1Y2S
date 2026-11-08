<?php
/**
 * Public & Student Navigation Header
 * Module: Web and Mobile Application Development (SE102.3)
 * NSBM Green University - Faculty of Computing
 */

require_once __DIR__ . '/../config/functions.php';

$currentUser = currentUser();
$db = getDB();

// Dynamic base prefix for assets and links across subdirectories (e.g. /student/ or /admin/)
$scriptPath = $_SERVER['PHP_SELF'];
$basePrefix = (strpos($scriptPath, '/student/') !== false || strpos($scriptPath, '/admin/') !== false) ? '../' : '';

// Fetch ticker headlines (breaking, urgent announcements, and latest news)
$tickerStories = [];
if ($db) {
    try {
        // 1. Breaking stories
        $stmtBreak = $db->query("SELECT id, title, slug, 'breaking' as item_type FROM articles WHERE is_breaking = 1 AND status = 'approved' ORDER BY published_at DESC LIMIT 3");
        $tickerStories = $stmtBreak->fetchAll();

        // 2. Urgent announcements
        $stmtAnn = $db->query("SELECT id, title, NULL as slug, 'urgent' as item_type FROM announcements WHERE is_active = 1 AND priority = 'urgent' ORDER BY created_at DESC LIMIT 2");
        $annItems = $stmtAnn->fetchAll();
        foreach ($annItems as $ai) {
            $tickerStories[] = $ai;
        }

        // 3. Latest stories to fill track
        if (count($tickerStories) < 5) {
            $limit = 5 - count($tickerStories);
            $stmtRec = $db->query("SELECT id, title, slug, 'latest' as item_type FROM articles WHERE status = 'approved' AND is_breaking = 0 ORDER BY published_at DESC LIMIT $limit");
            $recItems = $stmtRec->fetchAll();
            foreach ($recItems as $ri) {
                $tickerStories[] = $ri;
            }
        }
    } catch (PDOException $e) {
        // Silently continue
    }
}

if (empty($tickerStories)) {
    $tickerStories = [
        ['title' => 'Welcome to NSBM NewsDesk — Campus updates and announcements published daily', 'slug' => null, 'item_type' => 'latest'],
        ['title' => 'Faculty of Computing | Web and Mobile Application Development (SE102.3)', 'slug' => null, 'item_type' => 'latest'],
        ['title' => 'Green Wave Hackathon 2026: Registrations Open for All Faculties', 'slug' => null, 'item_type' => 'breaking'],
        ['title' => 'NSBM Green University — Pitipana, Homagama, Sri Lanka', 'slug' => null, 'item_type' => 'latest'],
    ];
}

// Duplicate array for seamless infinite marquee loop
$tickerLoop = array_merge($tickerStories, $tickerStories);

// Fetch categories for menu
$navCategories = [];
if ($db) {
    try {
        $stmt = $db->query("SELECT id, name, slug FROM categories ORDER BY id ASC");
        $navCategories = $stmt->fetchAll();
    } catch (PDOException $e) {
    }
}

$pageTitle = isset($pageTitle) ? $pageTitle . ' - NSBM NewsDesk' : 'NSBM NewsDesk - Campus News & Announcements';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <!-- Typography: Inter + Playfair Display (Editorial Stack) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Playfair+Display:ital,wght@0,700;0,800;1,700&display=swap"
        rel="stylesheet">
    <!-- Main Style -->
    <link rel="stylesheet" href="<?php echo $basePrefix; ?>assets/css/style.css">
    <link rel="icon" type="image/svg+xml" href="<?php echo $basePrefix; ?>assets/images/nsbm-logo.svg">
    <!-- Immediate theme bootstrap to prevent flash of wrong mode -->
    <script>
        (function () {
            var saved = localStorage.getItem('nsbm_theme') || 'dark';
            document.documentElement.setAttribute('data-theme', saved);
        })();
    </script>
</head>

<body>

    <!-- TOP TICKER BAR — Broadcast News Ticker Strip -->
    <div class="top-ticker-bar">
        <div class="container top-ticker-inner">
            <!-- Left Branding Badge -->
            <div class="ticker-lead">
                <span class="ticker-pulse-dot"></span>
                <span class="ticker-badge">BREAKING</span>
                <span class="ticker-channel-tag">LIVE FEED</span>
            </div>

            <!-- Continuous Smooth Ticker Scroll -->
            <div class="ticker-track">
                <div class="ticker-marquee">
                    <?php foreach ($tickerLoop as $item): ?>
                        <div class="ticker-item">
                            <?php if ($item['item_type'] === 'breaking'): ?>
                                <span class="ticker-item-badge ticker-badge-red">ALERT</span>
                            <?php elseif ($item['item_type'] === 'urgent'): ?>
                                <span class="ticker-item-badge ticker-badge-orange">URGENT</span>
                            <?php else: ?>
                                <span class="ticker-item-badge ticker-badge-green">CAMPUS</span>
                            <?php endif; ?>

                            <?php if (!empty($item['slug'])): ?>
                                <a href="<?php echo $basePrefix; ?>article.php?slug=<?php echo htmlspecialchars($item['slug']); ?>"
                                    class="ticker-headline">
                                    <?php echo htmlspecialchars($item['title']); ?>
                                </a>
                            <?php elseif ($item['item_type'] === 'urgent'): ?>
                                <a href="<?php echo $basePrefix; ?>announcements.php" class="ticker-headline">
                                    <?php echo htmlspecialchars($item['title']); ?>
                                </a>
                            <?php else: ?>
                                <span class="ticker-headline"><?php echo htmlspecialchars($item['title']); ?></span>
                            <?php endif; ?>
                            <span class="ticker-divider">&bull;</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Right Location, Date & Quick Links -->
            <div class="ticker-right">
                <div class="ticker-meta-item">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" />
                        <circle cx="12" cy="10" r="3" />
                    </svg>
                    <span>Pitipana, Homagama</span>
                </div>
                <div class="ticker-meta-item">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                        <line x1="16" y1="2" x2="16" y2="6" />
                        <line x1="8" y1="2" x2="8" y2="6" />
                        <line x1="3" y1="10" x2="21" y2="10" />
                    </svg>
                    <span><?php echo date('D, M d, Y'); ?></span>
                </div>
                <a href="<?php echo $basePrefix; ?>news.php" class="ticker-action-link">Archive &rarr;</a>
            </div>
        </div>
    </div>

    <!-- MAIN NAVBAR -->
    <header class="main-header">
        <div class="container navbar">
            <a href="<?php echo $basePrefix; ?>index.php" class="brand-link"
                aria-label="NSBM Green University NewsDesk">
                <img src="<?php echo $basePrefix; ?>assets/images/nsbm-logo-dark.svg"
                    alt="NSBM Green University NewsDesk" class="brand-logo brand-logo-dark">
            </a>

            <!-- Navigation Links -->
            <nav id="navbarMenu">
                <ul class="nav-links">
                    <li><a href="<?php echo $basePrefix; ?>index.php"
                            class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>">Home</a>
                    </li>
                    <li><a href="<?php echo $basePrefix; ?>news.php"
                            class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'news.php') ? 'active' : ''; ?>">All
                            News</a></li>
                    <li><a href="<?php echo $basePrefix; ?>announcements.php"
                            class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'announcements.php') ? 'active' : ''; ?>">Announcements</a>
                    </li>
                    <?php foreach ($navCategories as $cat): ?>
                        <li><a href="<?php echo $basePrefix; ?>news.php?category=<?php echo urlencode($cat['slug']); ?>"
                                class="nav-link"><?php echo htmlspecialchars($cat['name']); ?></a></li>
                    <?php endforeach; ?>

                    <!-- Mobile Drawer Extra Shortcuts -->
                    <li class="mobile-drawer-divider" style="display:none;"></li>
                    <?php if ($currentUser): ?>
                        <?php if ($currentUser['role'] === 'admin'): ?>
                            <li class="mobile-drawer-item" style="display:none;"><a
                                    href="<?php echo $basePrefix; ?>admin/index.php" class="nav-link"><svg width="15"
                                        height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="3" />
                                        <path
                                            d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z" />
                                    </svg> Admin Console</a></li>
                        <?php else: ?>
                            <li class="mobile-drawer-item" style="display:none;"><a
                                    href="<?php echo $basePrefix; ?>student/dashboard.php" class="nav-link"><svg width="15"
                                        height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                        <circle cx="12" cy="7" r="4" />
                                    </svg> Student Dashboard</a></li>
                            <li class="mobile-drawer-item" style="display:none;"><a
                                    href="<?php echo $basePrefix; ?>student/submit_article.php" class="nav-link"
                                    style="color:var(--brand); font-weight:700;"><svg width="15" height="15" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M12 20h9" />
                                        <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z" />
                                    </svg> Submit Campus News</a></li>
                            <li class="mobile-drawer-item" style="display:none;"><a
                                    href="<?php echo $basePrefix; ?>student/bookmarks.php" class="nav-link"><svg width="15"
                                        height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z" />
                                    </svg> Saved Stories</a></li>
                        <?php endif; ?>
                        <li class="mobile-drawer-item" style="display:none;"><a href="<?php echo $basePrefix; ?>logout.php"
                                class="nav-link" style="color:var(--urgent);"><svg width="15" height="15"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                                    <polyline points="16 17 21 12 16 7" />
                                    <line x1="21" y1="12" x2="9" y2="12" />
                                </svg> Sign Out</a></li>
                    <?php else: ?>
                        <li class="mobile-drawer-item" style="display:none;"><a href="<?php echo $basePrefix; ?>login.php"
                                class="nav-link"><svg width="15" height="15" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                                </svg> Student Sign In</a></li>
                        <li class="mobile-drawer-item" style="display:none;"><a
                                href="<?php echo $basePrefix; ?>register.php" class="nav-link"
                                style="color:var(--brand); font-weight:700;"><svg width="15" height="15" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                    <circle cx="8.5" cy="7" r="4" />
                                    <line x1="20" y1="8" x2="20" y2="14" />
                                    <line x1="23" y1="11" x2="17" y2="11" />
                                </svg> Register Account</a></li>
                    <?php endif; ?>
                </ul>
            </nav>

            <!-- Actions / Auth state -->
            <div class="nav-actions">
                <!-- Theme Toggle Button -->
                <button type="button" class="theme-toggle-btn" id="themeToggleBtn" title="Switch Theme (Dark/Light)">
                    <span class="theme-icon">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="5" />
                            <line x1="12" y1="1" x2="12" y2="3" />
                            <line x1="12" y1="21" x2="12" y2="23" />
                            <line x1="4.22" y1="4.22" x2="5.64" y2="5.64" />
                            <line x1="18.36" y1="18.36" x2="19.78" y2="19.78" />
                            <line x1="1" y1="12" x2="3" y2="12" />
                            <line x1="21" y1="12" x2="23" y2="12" />
                            <line x1="4.22" y1="19.78" x2="5.64" y2="18.36" />
                            <line x1="18.36" y1="5.64" x2="19.78" y2="4.22" />
                        </svg>
                    </span>
                </button>

                <!-- Mobile Menu Toggle Button -->
                <button type="button" class="mobile-nav-toggle" id="mobileNavToggle" aria-label="Toggle Mobile Menu"
                    aria-expanded="false">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>

                <?php if ($currentUser): ?>
                    <?php if ($currentUser['role'] === 'admin'): ?>
                        <a href="<?php echo $basePrefix; ?>admin/index.php" class="btn btn-primary btn-sm">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="7" height="7" />
                                <rect x="14" y="3" width="7" height="7" />
                                <rect x="14" y="14" width="7" height="7" />
                                <rect x="3" y="14" width="7" height="7" />
                            </svg>
                            <span>Admin Panel</span>
                        </a>
                        <a href="<?php echo $basePrefix; ?>logout.php" class="btn btn-outline btn-sm btn-signout"
                            title="Sign Out of NewsDesk">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                                <polyline points="16 17 21 12 16 7" />
                                <line x1="21" y1="12" x2="9" y2="12" />
                            </svg>
                            <span>Sign Out</span>
                        </a>
                    <?php else: ?>
                        <a href="<?php echo $basePrefix; ?>student/dashboard.php" class="user-menu-pill"
                            title="Student Dashboard">
                            <span class="user-avatar-mini"><?php echo strtoupper(substr($currentUser['name'], 0, 1)); ?></span>
                            <span><?php echo htmlspecialchars(explode(' ', $currentUser['name'])[0]); ?></span>
                        </a>
                        <a href="<?php echo $basePrefix; ?>student/submit_article.php" class="btn btn-primary btn-sm">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="5" x2="12" y2="19" />
                                <line x1="5" y1="12" x2="19" y2="12" />
                            </svg>
                            <span>Submit News</span>
                        </a>
                        <a href="<?php echo $basePrefix; ?>logout.php" class="btn btn-outline btn-sm btn-signout"
                            title="Sign Out of NewsDesk">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                                <polyline points="16 17 21 12 16 7" />
                                <line x1="21" y1="12" x2="9" y2="12" />
                            </svg>
                            <span>Sign Out</span>
                        </a>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="<?php echo $basePrefix; ?>login.php" class="btn btn-outline btn-sm">Sign In</a>
                    <a href="<?php echo $basePrefix; ?>register.php" class="btn btn-primary btn-sm">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </header>



    <!-- CONTAINER FOR FLASH MESSAGES -->
    <div class="container">
        <?php $flash = getFlashMessage(); ?>
        <?php if ($flash): ?>
            <div class="flash-alert <?php echo htmlspecialchars($flash['type']); ?>">
                <span><?php echo htmlspecialchars($flash['message']); ?></span>
                <button type="button" class="flash-close">&times;</button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['db_error'])): ?>
            <div class="flash-alert danger">
                <span><?php echo $_SESSION['db_error'];
                unset($_SESSION['db_error']); ?></span>
            </div>
        <?php endif; ?>
    </div>