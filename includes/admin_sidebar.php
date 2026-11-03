<?php
/**
 * Admin Sidebar Navigation
 * Module: Web and Mobile Application Development (SE102.3)
 * NSBM Green University - Faculty of Computing
 */

$currentAdminPage = basename($_SERVER['PHP_SELF']);
$db = getDB();

// Count pending submissions
$pendingCount = 0;
if ($db) {
    try {
        $stmt = $db->query("SELECT COUNT(*) FROM articles WHERE status = 'pending'");
        $pendingCount = (int)$stmt->fetchColumn();
    } catch (PDOException $e) {}
}

// Count pending comments
$pendingComments = 0;
if ($db) {
    try {
        $stmt = $db->query("SELECT COUNT(*) FROM comments WHERE status = 'pending'");
        $pendingComments = (int)$stmt->fetchColumn();
    } catch (PDOException $e) {}
}
?>
<aside class="admin-sidebar">
    <!-- Brand -->
    <div class="sidebar-brand">
        <a href="../index.php" style="display: flex; align-items: center; gap: 12px; text-decoration: none; color: inherit;">
            <img src="../assets/images/nsbm-logo-dark.svg" alt="NSBM Green University" class="sidebar-brand-logo">
            <div class="sidebar-brand-text">
                <span class="sidebar-brand-name">NSBM NewsDesk</span>
                <span class="sidebar-brand-sub">Admin Console</span>
            </div>
        </a>
    </div>

    <!-- Navigation -->
    <ul class="sidebar-menu">
        <li class="sidebar-section-label" style="list-style:none; padding-left:18px; padding-top:14px;">Main</li>

        <li class="sidebar-item <?php echo ($currentAdminPage === 'index.php') ? 'active' : ''; ?>" style="list-style:none;">
            <a href="index.php">
                <span class="sidebar-item-inner">
                    <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
                        <rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
                    </svg>
                    <span class="sidebar-label">Dashboard</span>
                </span>
            </a>
        </li>

        <li class="sidebar-section-label" style="list-style:none; padding-left:18px; padding-top:10px;">Content</li>

        <li class="sidebar-item <?php echo in_array($currentAdminPage, ['articles.php', 'article_create.php', 'article_edit.php']) ? 'active' : ''; ?>" style="list-style:none;">
            <a href="articles.php">
                <span class="sidebar-item-inner">
                    <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 4h16v4H4z"/><path d="M4 12h10"/><path d="M4 16h7"/><rect x="14" y="12" width="6" height="6" rx="1"/>
                    </svg>
                    <span class="sidebar-label">News Articles</span>
                </span>
            </a>
        </li>

        <li class="sidebar-item <?php echo ($currentAdminPage === 'categories.php') ? 'active' : ''; ?>" style="list-style:none;">
            <a href="categories.php">
                <span class="sidebar-item-inner">
                    <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/>
                        <rect x="9" y="3" width="6" height="4" rx="1"/><path d="M9 12h6"/><path d="M9 16h4"/>
                    </svg>
                    <span class="sidebar-label">Categories</span>
                </span>
            </a>
        </li>

        <li class="sidebar-item <?php echo ($currentAdminPage === 'submissions.php') ? 'active' : ''; ?>" style="list-style:none;">
            <a href="submissions.php">
                <span class="sidebar-item-inner">
                    <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/>
                    </svg>
                    <span class="sidebar-label">Submissions</span>
                </span>
                <?php if ($pendingCount > 0): ?>
                    <span class="badge-count danger"><?php echo $pendingCount; ?></span>
                <?php endif; ?>
            </a>
        </li>

        <li class="sidebar-item <?php echo in_array($currentAdminPage, ['announcements.php', 'announcement_create.php']) ? 'active' : ''; ?>" style="list-style:none;">
            <a href="announcements.php">
                <span class="sidebar-item-inner">
                    <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
                    </svg>
                    <span class="sidebar-label">Announcements</span>
                </span>
            </a>
        </li>

        <li class="sidebar-item <?php echo ($currentAdminPage === 'comments.php') ? 'active' : ''; ?>" style="list-style:none;">
            <a href="comments.php">
                <span class="sidebar-item-inner">
                    <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                    </svg>
                    <span class="sidebar-label">Comments</span>
                </span>
                <?php if ($pendingComments > 0): ?>
                    <span class="badge-count danger"><?php echo $pendingComments; ?></span>
                <?php endif; ?>
            </a>
        </li>

        <li class="sidebar-section-label" style="list-style:none; padding-left:18px; padding-top:10px;">Analytics</li>

        <li class="sidebar-item <?php echo ($currentAdminPage === 'reports.php') ? 'active' : ''; ?>" style="list-style:none;">
            <a href="reports.php">
                <span class="sidebar-item-inner">
                    <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/>
                        <line x1="6" y1="20" x2="6" y2="14"/><line x1="2" y1="20" x2="22" y2="20"/>
                    </svg>
                    <span class="sidebar-label">Readership Reports</span>
                </span>
            </a>
        </li>
    </ul>

    <!-- Footer Links -->
    <div class="sidebar-footer">
        <a href="../index.php" target="_blank" class="sidebar-footer-link link-portal">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/>
                <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
            </svg>
            <span>View Public Portal</span>
        </a>
        <a href="../logout.php" class="sidebar-footer-link link-signout">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                <polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
            <span>Sign Out</span>
        </a>
    </div>
</aside>
