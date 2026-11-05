<?php
/**
 * Campus Announcements Bulletin Board
 * Module: Web and Mobile Application Development (SE102.3)
 * NSBM Green University - Faculty of Computing
 */

require_once __DIR__ . '/config/functions.php';

$db = getDB();
$pageTitle = 'Campus Announcements';

$priorityFilter = isset($_GET['priority']) ? trim($_GET['priority']) : '';

$where = ["an.is_active = 1"];
$params = [];

if (in_array($priorityFilter, ['urgent', 'high', 'normal'])) {
    $where[] = "an.priority = ?";
    $params[] = $priorityFilter;
}

$announcements = [];
if ($db) {
    try {
        $sql = "
            SELECT an.*, u.full_name as author_name, u.department
            FROM announcements an
            JOIN users u ON an.author_id = u.id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY FIELD(an.priority, 'urgent', 'high', 'normal'), an.created_at DESC
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $announcements = $stmt->fetchAll();
    } catch (PDOException $e) {}
}

include __DIR__ . '/includes/header.php';
?>

<main class="container" style="padding-top: 24px; padding-bottom: 50px;">
    <!-- Page Header -->
    <div style="margin-bottom: 24px; display: flex; align-items: flex-end; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: clamp(1.4rem, 4vw, 2rem); font-weight: 800; color: var(--text-1); margin-bottom: 6px;">
                Official Campus Announcements
            </h1>
            <p style="color: var(--text-3); font-size: 0.92rem;">
                Official circulars and notices issued by university administration, examinations branch, and faculties.
            </p>
        </div>

        <!-- Priority Tabs (Responsive horizontal swipeable on mobile) -->
        <div style="display: flex; gap: 8px; overflow-x: auto; -webkit-overflow-scrolling: touch; scrollbar-width: none; padding-bottom: 4px; flex-shrink: 0;">
            <a href="announcements.php" class="btn btn-sm <?php echo empty($priorityFilter) ? 'btn-primary' : 'btn-outline'; ?>">All Priorities</a>
            <a href="announcements.php?priority=urgent" class="btn btn-sm <?php echo ($priorityFilter === 'urgent') ? 'btn-primary' : 'btn-outline'; ?>" style="border-color: var(--urgent-bg); color: var(--urgent);">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg> Urgent
            </a>
            <a href="announcements.php?priority=high" class="btn btn-sm <?php echo ($priorityFilter === 'high') ? 'btn-primary' : 'btn-outline'; ?>" style="border-color: var(--high-bg); color: var(--high);">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg> High Priority
            </a>
            <a href="announcements.php?priority=normal" class="btn btn-sm <?php echo ($priorityFilter === 'normal') ? 'btn-primary' : 'btn-outline'; ?>">Standard Notices</a>
        </div>
    </div>

    <!-- Announcements Cards List -->
    <div style="display: flex; flex-direction: column; gap: 16px;">
        <?php if (!empty($announcements)): ?>
            <?php foreach ($announcements as $ann): ?>
                <div style="background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 24px; box-shadow: var(--shadow-xs); border-left: 5px solid <?php echo ($ann['priority'] === 'urgent') ? 'var(--urgent)' : (($ann['priority'] === 'high') ? 'var(--high)' : 'var(--normal)'); ?>;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <span class="badge-priority badge-<?php echo htmlspecialchars($ann['priority']); ?>" style="padding: 4px 10px; font-size: 0.78rem;">
                                <?php echo strtoupper($ann['priority']); ?> PRIORITY
                            </span>
                            <span style="font-size: 0.85rem; color: var(--text-3);">
                                Posted by <strong><?php echo htmlspecialchars($ann['author_name']); ?></strong> (<?php echo htmlspecialchars($ann['department']); ?>)
                            </span>
                        </div>
                        <div style="font-size: 0.82rem; color: var(--text-4); display: flex; align-items: center; gap: 4px;">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            <span>Issued: <?php echo formatDate($ann['created_at']); ?></span>
                            <?php if ($ann['expiry_date']): ?>
                                <span>&bull; Valid until: <?php echo formatDate($ann['expiry_date']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--text-1); margin-bottom: 10px;">
                        <?php echo htmlspecialchars($ann['title']); ?>
                    </h2>

                    <div style="font-size: 0.98rem; color: var(--text-2); line-height: 1.6;">
                        <?php echo nl2br(htmlspecialchars($ann['content'])); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 48px; text-align: center;">
                <p style="color: var(--text-3); font-size: 1rem;">No announcements match the selected filter.</p>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
