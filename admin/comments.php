<?php
/**
 * Reader Comments Moderation (Admin)
 * Module: Web and Mobile Application Development (SE102.3)
 * NSBM Green University - Faculty of Computing
 */

require_once __DIR__ . '/../config/functions.php';
requireAdmin('../login.php');

$db = getDB();
if (!$db) {
    die("Database connection error. Please run setup.php.");
}

// 1. Actions: Approve, Hide, Delete
if (isset($_GET['action']) && isset($_GET['id'])) {
    $commentId = (int)$_GET['id'];
    $action = $_GET['action'];

    try {
        if ($action === 'approve') {
            $stmt = $db->prepare("UPDATE comments SET status = 'approved' WHERE id = ?");
            $stmt->execute([$commentId]);
            setFlashMessage('success', 'Comment marked as approved.');
        } elseif ($action === 'hide') {
            $stmt = $db->prepare("UPDATE comments SET status = 'hidden' WHERE id = ?");
            $stmt->execute([$commentId]);
            setFlashMessage('info', 'Comment hidden from public view.');
        } elseif ($action === 'delete') {
            $stmt = $db->prepare("DELETE FROM comments WHERE id = ?");
            $stmt->execute([$commentId]);
            setFlashMessage('info', 'Comment permanently removed.');
        }
        redirect('comments.php');
    } catch (PDOException $e) {
        setFlashMessage('danger', 'Error updating comment: ' . $e->getMessage());
    }
}

// 2. Filter by status
$statusFilter = trim($_GET['status'] ?? '');
$where = ["1=1"];
$params = [];

if (in_array($statusFilter, ['approved', 'pending', 'hidden'])) {
    $where[] = "cm.status = ?";
    $params[] = $statusFilter;
}

// 3. Fetch comments
$comments = [];
try {
    $sql = "
        SELECT cm.*, u.full_name as author_name, u.student_id, u.email as author_email,
               a.title as article_title, a.slug as article_slug
        FROM comments cm
        JOIN users u ON cm.user_id = u.id
        JOIN articles a ON cm.article_id = a.id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY cm.created_at DESC
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $comments = $stmt->fetchAll();
} catch (PDOException $e) {}

$pageTitle = 'Moderate Reader Comments';
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
</head>
<body>
    <div class="admin-layout">
        <?php include __DIR__ . '/../includes/admin_sidebar.php'; ?>

        <div class="admin-main">
            <?php include __DIR__ . '/../includes/admin_header.php'; ?>

            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
                <div>
                    <h1 style="font-size: 1.5rem; font-weight: 800; color: var(--text-1); margin-bottom: 4px;">
                        Reader Comments Moderation
                    </h1>
                    <p style="color: var(--text-3); font-size: 0.9rem;">
                        Review, moderate, and remove comments to ensure courteous academic dialogue.
                    </p>
                </div>

                <div style="display: flex; gap: 8px;">
                    <a href="comments.php" class="btn btn-sm <?php echo empty($statusFilter) ? 'btn-primary' : 'btn-outline'; ?>">All Comments</a>
                    <a href="comments.php?status=approved" class="btn btn-sm <?php echo ($statusFilter === 'approved') ? 'btn-primary' : 'btn-outline'; ?>">Approved</a>
                    <a href="comments.php?status=pending" class="btn btn-sm <?php echo ($statusFilter === 'pending') ? 'btn-primary' : 'btn-outline'; ?>">Pending</a>
                    <a href="comments.php?status=hidden" class="btn btn-sm <?php echo ($statusFilter === 'hidden') ? 'btn-primary' : 'btn-outline'; ?>">Hidden</a>
                </div>
            </div>

            <div class="table-card">
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 50px;">ID</th>
                                <th>Student / Commenter</th>
                                <th>Comment Text</th>
                                <th>Story Reference</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th style="text-align: right;">Moderation Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($comments)): ?>
                                <?php foreach ($comments as $cm): ?>
                                    <tr>
                                        <td><code>#<?php echo $cm['id']; ?></code></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($cm['author_name']); ?></strong><br>
                                            <span style="font-size: 0.78rem; color: var(--text-3);"><?php echo htmlspecialchars($cm['student_id'] ?: 'Student'); ?></span>
                                        </td>
                                        <td style="max-width: 320px;">
                                            <div style="font-size: 0.88rem; color: var(--text-2); line-height: 1.4;">
                                                "<?php echo htmlspecialchars($cm['comment_text']); ?>"
                                            </div>
                                        </td>
                                        <td>
                                            <a href="../article.php?slug=<?php echo htmlspecialchars($cm['article_slug']); ?>" target="_blank" style="color: var(--brand); font-size: 0.85rem; font-weight: 600;">
                                                <?php echo htmlspecialchars(truncateWords($cm['article_title'], 6)); ?>
                                            </a>
                                        </td>
                                        <td style="font-size: 0.82rem; color: var(--text-3);">
                                            <?php echo timeAgo($cm['created_at']); ?>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo htmlspecialchars($cm['status']); ?>">
                                                <?php echo ucfirst($cm['status']); ?>
                                            </span>
                                        </td>
                                        <td style="text-align: right;">
                                            <div style="display: flex; gap: 6px; justify-content: flex-end;">
                                                <?php if ($cm['status'] !== 'approved'): ?>
                                                    <a href="comments.php?action=approve&id=<?php echo $cm['id']; ?>" class="btn btn-primary btn-sm" style="padding: 3px 8px; font-size: 0.78rem;">
                                                        Approve
                                                    </a>
                                                <?php endif; ?>
                                                <?php if ($cm['status'] !== 'hidden'): ?>
                                                    <a href="comments.php?action=hide&id=<?php echo $cm['id']; ?>" class="btn btn-outline btn-sm" style="padding: 3px 8px; font-size: 0.78rem;">
                                                        Hide
                                                    </a>
                                                <?php endif; ?>
                                                <a href="comments.php?action=delete&id=<?php echo $cm['id']; ?>" class="btn btn-danger btn-sm" style="padding: 3px 8px; font-size: 0.78rem;" onclick="return confirm('Permanently remove this comment?');">
                                                    Delete
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 40px; color: var(--text-3);">
                                        No comments found.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html>
