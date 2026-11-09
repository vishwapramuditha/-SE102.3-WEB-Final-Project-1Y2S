<?php
/**
 * Public & Student Navigation Footer
 * Module: Web and Mobile Application Development (SE102.3)
 * NSBM Green University - Faculty of Computing
 */

$scriptPath = $_SERVER['PHP_SELF'];
$basePrefix = (strpos($scriptPath, '/student/') !== false || strpos($scriptPath, '/admin/') !== false) ? '../' : '';
?>
    <!-- FOOTER -->
    <footer class="site-footer">
        <div class="container">
            <div class="footer-grid">
                <!-- Col 1: Brand & About -->
                <div class="footer-col">
                    <img src="<?php echo $basePrefix; ?>assets/images/nsbm-logo-dark.svg" alt="NSBM Green University NewsDesk" style="height: 48px; width: auto; margin-bottom: 16px;">
                    <p style="font-size: 0.88rem; color: var(--text-3); line-height: 1.6; margin-bottom: 16px;">
                        The official digital news and announcement management system of NSBM Green University. Providing timely, verified campus updates for undergraduates, academic staff, and student societies.
                    </p>
                    <div style="font-size: 0.82rem; color: var(--text-4);">
                        Faculty of Computing &bull; Batch 26.1 IFSL
                    </div>
                </div>

                <!-- Col 2: Navigation -->
                <div class="footer-col">
                    <h5>Quick Navigation</h5>
                    <ul class="footer-links">
                        <li><a href="<?php echo $basePrefix; ?>index.php">Campus Home</a></li>
                        <li><a href="<?php echo $basePrefix; ?>news.php">All News &amp; Articles</a></li>
                        <li><a href="<?php echo $basePrefix; ?>announcements.php">Official Announcements</a></li>
                        <li><a href="<?php echo $basePrefix; ?>student/submit_article.php">Submit News Story</a></li>
                        <li><a href="<?php echo $basePrefix; ?>student/dashboard.php">Student Portal</a></li>
                    </ul>
                </div>

                <!-- Col 3: Categories -->
                <div class="footer-col">
                    <h5>News Categories</h5>
                    <ul class="footer-links">
                        <li><a href="<?php echo $basePrefix; ?>news.php?category=academic">Academic &amp; Research</a></li>
                        <li><a href="<?php echo $basePrefix; ?>news.php?category=events">Campus Events</a></li>
                        <li><a href="<?php echo $basePrefix; ?>news.php?category=sports">Sports &amp; Athletics</a></li>
                        <li><a href="<?php echo $basePrefix; ?>news.php?category=general">General University News</a></li>
                    </ul>
                </div>

                <!-- Col 4: Project & Administration -->
                <div class="footer-col">
                    <h5>Project Scope</h5>
                    <p style="font-size: 0.85rem; color: var(--text-3); line-height: 1.5; margin-bottom: 12px;">
                        SE102.3 – Web &amp; Mobile Application Development Final Project. Complete CRUD, session auth, role management &amp; MySQL integration.
                    </p>
                    <div>
                        <a href="<?php echo $basePrefix; ?>admin/index.php" class="btn btn-outline btn-sm" style="font-size: 0.8rem; display: inline-flex; align-items: center;">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:5px;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg> Admin Portal
                        </a>
                    </div>
                </div>
            </div>

            <div class="footer-bottom">
                <div>
                    &copy; <?php echo date('Y'); ?> NSBM Green University. All rights reserved.
                </div>
                <div style="display: flex; gap: 16px;">
                    <a href="<?php echo $basePrefix; ?>news.php" style="color: var(--text-4);">News Feed</a>
                    <a href="<?php echo $basePrefix; ?>announcements.php" style="color: var(--text-4);">Announcements</a>
                    <a href="<?php echo $basePrefix; ?>setup.php" style="color: var(--text-4);">DB Setup</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Main JS Script -->
    <script src="<?php echo $basePrefix; ?>assets/js/main.js"></script>
</body>
</html>
