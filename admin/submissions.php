<?php
/**
 * Student & Club Article Approval / Moderation
 * Module: Web and Mobile Application Development (SE102.3)
 * NSBM Green University - Faculty of Computing
 */

require_once __DIR__ . '/../config/functions.php';
requireAdmin('../login.php');

$db = getDB();
if (!$db) {
    die("Database connection error. Please run setup.php.");
}

// 1. Handle Approve Action
if (isset($_POST['action']) && $_POST['action'] === 'approve' && isset($_POST['article_id'])) {
    $artId = (int)$_POST['article_id'];
    try {
        $upd = $db->prepare("UPDATE articles SET status = 'approved', admin_feedback = NULL, published_at = NOW() WHERE id = ?");
        $upd->execute([$artId]);
        setFlashMessage('success', 'Article #' . $artId . ' has been approved and published to the live campus feed!');
        redirect('submissions.php');
    } catch (PDOException $e) {
        setFlashMessage('danger', 'Error approving article: ' . $e->getMessage());
    }
}

// 2. Handle Reject Action with Feedback
if (isset($_POST['action']) && $_POST['action'] === 'reject' && isset($_POST['article_id'])) {
    $artId = (int)$_POST['article_id'];
    $feedback = trim($_POST['admin_feedback'] ?? 'Please review formatting and content guidelines.');

    try {
        $upd = $db->prepare("UPDATE articles SET status = 'rejected', admin_feedback = ? WHERE id = ?");
        $upd->execute([$feedback, $artId]);
        setFlashMessage('info', 'Article #' . $artId . ' marked as rejected with feedback sent to author.');
        redirect('submissions.php');
    } catch (PDOException $e) {
        setFlashMessage('danger', 'Error rejecting article: ' . $e->getMessage());
    }
}

// 3. Filter submissions by status
$statusFilter = trim($_GET['status'] ?? 'pending');
$where = ["1=1"];
$params = [];

if (in_array($statusFilter, ['pending', 'approved', 'rejected', 'draft'])) {
    $where[] = "a.status = ?";
    $params[] = $statusFilter;
}

// Fetch submissions
$submissions = [];
try {
    $sql = "
        SELECT a.*, c.name as category_name, c.badge_color,
               u.full_name as author_name, u.student_id, u.email as author_email, u.department as author_department
        FROM articles a
        JOIN categories c ON a.category_id = c.id
        JOIN users u ON a.author_id = u.id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY a.created_at DESC
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $submissions = $stmt->fetchAll();
} catch (PDOException $e) {}

// Specific review modal trigger
$reviewArticle = null;
if (isset($_GET['review']) && is_numeric($_GET['review'])) {
    $reviewId = (int)$_GET['review'];
    $rStmt = $db->prepare("
        SELECT a.*, c.name as category_name, c.badge_color,
               u.full_name as author_name, u.student_id, u.email as author_email, u.department as author_department
        FROM articles a
        JOIN categories c ON a.category_id = c.id
        JOIN users u ON a.author_id = u.id
        WHERE a.id = ?
    ");
    $rStmt->execute([$reviewId]);
    $reviewArticle = $rStmt->fetch();
}

$pageTitle = 'Student Submissions Moderation';
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
                        Student &amp; Club Submissions
                    </h1>
                    <p style="color: var(--text-3); font-size: 0.9rem;">
                        Review, approve, or provide editorial feedback on student-submitted stories.
                    </p>
                </div>

                <!-- Status Filter Tabs -->
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <a href="submissions.php?status=pending" class="btn btn-sm <?php echo ($statusFilter === 'pending') ? 'btn-primary' : 'btn-outline'; ?>" style="display: inline-flex; align-items: center; gap: 5px;">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        Pending Review
                    </a>
                    <a href="submissions.php?status=approved" class="btn btn-sm <?php echo ($statusFilter === 'approved') ? 'btn-primary' : 'btn-outline'; ?>" style="display: inline-flex; align-items: center; gap: 5px;">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        Approved
                    </a>
                    <a href="submissions.php?status=rejected" class="btn btn-sm <?php echo ($statusFilter === 'rejected') ? 'btn-primary' : 'btn-outline'; ?>" style="display: inline-flex; align-items: center; gap: 5px;">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        Rejected
                    </a>
                    <a href="submissions.php?status=all" class="btn btn-sm <?php echo ($statusFilter === 'all') ? 'btn-primary' : 'btn-outline'; ?>">
                        All Records
                    </a>
                </div>
            </div>

            <!-- Submissions Table -->
            <div class="table-card">
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Headline &amp; Excerpt</th>
                                <th>Category</th>
                                <th>Author Details</th>
                                <th>Submitted</th>
                                <th>Status</th>
                                <th style="text-align: right;">Review &amp; Decision</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($submissions)): ?>
                                <?php foreach ($submissions as $sub): ?>
                                    <tr>
                                        <td><code>#<?php echo $sub['id']; ?></code></td>
                                        <td>
                                            <div style="font-weight: 700; color: var(--text-1); margin-bottom: 3px;">
                                                <?php echo htmlspecialchars($sub['title']); ?>
                                            </div>
                                            <div style="font-size: 0.82rem; color: var(--text-3); line-height: 1.35;">
                                                <?php echo htmlspecialchars(truncateWords($sub['excerpt'] ?: $sub['content'], 12)); ?>
                                            </div>
                                            <?php if (!empty($sub['admin_feedback'])): ?>
                                                <div style="margin-top: 4px; font-size: 0.78rem; background: var(--urgent-bg); color: var(--urgent); padding: 2px 6px; border-radius: 4px; display: inline-block;">
                                                    Feedback: <?php echo htmlspecialchars($sub['admin_feedback']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge-cat" style="background-color: <?php echo htmlspecialchars($sub['badge_color'] ?: '#006a4e'); ?>; font-size: 0.72rem;">
                                                <?php echo htmlspecialchars($sub['category_name']); ?>
                                            </span>
                                        </td>
                                        <td style="font-size: 0.85rem;">
                                            <strong><?php echo htmlspecialchars($sub['author_name']); ?></strong><br>
                                            <span style="font-size: 0.76rem; color: var(--text-3);"><?php echo htmlspecialchars($sub['student_id'] ?: 'Student'); ?> &bull; <?php echo htmlspecialchars($sub['author_department']); ?></span>
                                        </td>
                                        <td style="font-size: 0.82rem; color: var(--text-3);">
                                            <?php echo formatDate($sub['created_at']); ?>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo htmlspecialchars($sub['status']); ?>">
                                                <?php echo ucfirst($sub['status']); ?>
                                            </span>
                                        </td>
                                        <td style="text-align: right;">
                                            <a href="submissions.php?status=<?php echo urlencode($statusFilter); ?>&review=<?php echo $sub['id']; ?>" class="btn btn-primary btn-sm" style="padding: 4px 12px; font-size: 0.82rem;">
                                                Inspect &amp; Decide &rarr;
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 40px; color: var(--text-3);">
                                        No submissions matching status "<?php echo htmlspecialchars($statusFilter); ?>".
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Review / Decision Modal Dialog -->
            <?php if ($reviewArticle): ?>
                <div class="modal-backdrop open">
                    <div class="modal-box" style="max-width: 680px; max-height: 90vh; overflow-y: auto;">
                        <div class="modal-header">
                            <div>
                                <span class="badge-cat" style="background-color: <?php echo htmlspecialchars($reviewArticle['badge_color'] ?: '#006a4e'); ?>; font-size: 0.72rem;">
                                    <?php echo htmlspecialchars($reviewArticle['category_name']); ?>
                                </span>
                                <h3 class="modal-title" style="margin-top: 6px;">Editorial Review: Story #<?php echo $reviewArticle['id']; ?></h3>
                            </div>
                            <a href="submissions.php?status=<?php echo urlencode($statusFilter); ?>" class="btn btn-outline btn-sm">&times; Close</a>
                        </div>

                        <!-- Story Details -->
                        <div style="margin-bottom: 20px;">
                            <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--text-1); margin-bottom: 8px;">
                                <?php echo htmlspecialchars($reviewArticle['title']); ?>
                            </h2>
                            <div style="font-size: 0.85rem; color: var(--text-3); margin-bottom: 14px; background: var(--surface-2); padding: 12px; border-radius: 6px; line-height: 1.7;">
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--brand);"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                    <span><strong>Author:</strong> <?php echo htmlspecialchars($reviewArticle['author_name']); ?> <span style="color: var(--text-4);">(<?php echo htmlspecialchars($reviewArticle['student_id']); ?>)</span></span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--brand);"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                                    <span><strong>Email:</strong> <?php echo htmlspecialchars($reviewArticle['author_email']); ?> &bull; <strong>Dept:</strong> <?php echo htmlspecialchars($reviewArticle['author_department']); ?></span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--brand);"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                    <span><strong>Submitted:</strong> <?php echo formatDate($reviewArticle['created_at']); ?></span>
                                </div>
                            </div>

                            <?php if (!empty($reviewArticle['image_url'])): ?>
                                <img src="../<?php echo htmlspecialchars($reviewArticle['image_url']); ?>" alt="" style="width: 100%; height: 220px; object-fit: cover; border-radius: 8px; margin-bottom: 14px;">
                            <?php endif; ?>

                            <div style="font-size: 0.95rem; color: var(--text-2); line-height: 1.6; max-height: 200px; overflow-y: auto; background: var(--surface-2); padding: 14px; border-radius: 8px; border: 1px solid var(--border); margin-bottom: 20px;">
                                <?php echo nl2br(htmlspecialchars($reviewArticle['content'])); ?>
                            </div>
                        </div>

                        <!-- Moderation Decision Form -->
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; border-top: 1px solid var(--border); padding-top: 20px;">
                            <!-- Approve Form -->
                            <form action="submissions.php" method="POST">
                                <input type="hidden" name="action" value="approve">
                                <input type="hidden" name="article_id" value="<?php echo $reviewArticle['id']; ?>">
                                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; gap: 6px;">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                    Approve &amp; Publish Live
                                </button>
                            </form>

                            <!-- Reject Form -->
                            <form action="submissions.php" method="POST">
                                <input type="hidden" name="action" value="reject">
                                <input type="hidden" name="article_id" value="<?php echo $reviewArticle['id']; ?>">
                                <input type="text" name="admin_feedback" class="form-control" placeholder="Reason for rejection / revision note..." style="margin-bottom: 8px; font-size: 0.85rem;" required>
                                <button type="submit" class="btn btn-danger" style="width: 100%; padding: 8px; font-size: 0.9rem; display: inline-flex; align-items: center; justify-content: center; gap: 6px;">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                    Reject with Feedback
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html>
