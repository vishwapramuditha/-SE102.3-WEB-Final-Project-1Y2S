<?php
/**
 * Admin Top Header Bar Component
 * Module: Web and Mobile Application Development (SE102.3)
 * NSBM Green University - Faculty of Computing
 */

$user = currentUser();

// Theme bootstrap — must run before page renders to avoid flash
?>
<script>
    (function() {
        var saved = localStorage.getItem('nsbm_theme') || 'dark';
        document.documentElement.setAttribute('data-theme', saved);
    })();
</script>

<header class="admin-topbar">
    <div class="admin-topbar-left">
        <div>
            <div class="admin-topbar-breadcrumb">
                <span>Admin</span>
                <span>/</span>
                <span style="color: var(--text-2); font-weight: 500;"><?php echo htmlspecialchars($pageTitle ?? 'Dashboard'); ?></span>
            </div>
        </div>
    </div>

    <div class="admin-topbar-right">
        <!-- Theme Toggle -->
        <button type="button" class="theme-toggle-btn" id="themeToggleBtn" title="Switch Theme (Dark/Light)">
            <span class="theme-icon">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
            </span>
        </button>

        <!-- New Article Quick Action -->
        <a href="article_create.php" class="btn btn-primary btn-sm">
            <svg width="13" height="13" fill="currentColor" viewBox="0 0 16 16">
                <path d="M8 0a1 1 0 0 1 1 1v6h6a1 1 0 1 1 0 2H9v6a1 1 0 1 1-2 0V9H1a1 1 0 0 1 0-2h6V1a1 1 0 0 1 1-1z"/>
            </svg>
            New Article
        </a>

        <div class="admin-topbar-divider"></div>

        <!-- User Info -->
        <div class="admin-user-block">
            <div class="admin-user-info">
                <span class="admin-user-name"><?php echo htmlspecialchars($user['name'] ?? 'Administrator'); ?></span>
                <span class="admin-user-role">Staff &middot; Admin</span>
            </div>
            <div class="user-avatar-mini" style="width:34px; height:34px; font-size:0.85rem;">
                <?php echo strtoupper(substr($user['name'] ?? 'A', 0, 1)); ?>
            </div>
        </div>
    </div>
</header>


<div class="admin-content">
    <?php $flash = getFlashMessage(); ?>
    <?php if ($flash): ?>
        <div class="flash-alert <?php echo htmlspecialchars($flash['type']); ?>">
            <span><?php echo htmlspecialchars($flash['message']); ?></span>
            <button type="button" class="flash-close">&times;</button>
        </div>
    <?php endif; ?>
