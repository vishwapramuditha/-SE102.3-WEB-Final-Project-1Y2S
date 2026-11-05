<?php
/**
 * Campus Announcements Management (Admin CRUD)
 * Module: Web and Mobile Application Development (SE102.3)
 * NSBM Green University - Faculty of Computing
 */

require_once __DIR__ . '/../config/functions.php';
requireAdmin('../login.php');

$user = currentUser();
$db = getDB();
if (!$db) {
    die("Database connection error. Please run setup.php.");
}

$editAnnouncement = null;
$error = '';

// 1. Delete Action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delId = (int)$_GET['id'];
    try {
        $del = $db->prepare("DELETE FROM announcements WHERE id = ?");
        $del->execute([$delId]);
        setFlashMessage('info', 'Announcement #' . $delId . ' deleted successfully.');
        redirect('announcements.php');
    } catch (PDOException $e) {
        setFlashMessage('danger', 'Error deleting announcement: ' . $e->getMessage());
    }
}

// 2. Toggle Active Status
if (isset($_GET['toggle_status']) && is_numeric($_GET['toggle_status'])) {
    $toggleId = (int)$_GET['toggle_status'];
    try {
        $upd = $db->prepare("UPDATE announcements SET is_active = 1 - is_active WHERE id = ?");
        $upd->execute([$toggleId]);
        setFlashMessage('success', 'Announcement visibility updated.');
        redirect('announcements.php');
    } catch (PDOException $e) {}
}

// 3. Edit Trigger
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM announcements WHERE id = ?");
    $stmt->execute([$editId]);
    $editAnnouncement = $stmt->fetch();
}

// 4. Save (Create or Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $priority = in_array($_POST['priority'] ?? '', ['urgent', 'high', 'normal']) ? $_POST['priority'] : 'normal';
    $expiryDate = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
    $annId = isset($_POST['announcement_id']) ? (int)$_POST['announcement_id'] : 0;

    if (empty($title) || empty($content)) {
        $error = 'Both Title and Content are required.';
    } else {
        try {
            if ($annId > 0) {
                // Update
                $upd = $db->prepare("UPDATE announcements SET title = ?, content = ?, priority = ?, expiry_date = ? WHERE id = ?");
                $upd->execute([$title, $content, $priority, $expiryDate, $annId]);
                setFlashMessage('success', 'Announcement updated successfully!');
            } else {
                // Insert
                $ins = $db->prepare("INSERT INTO announcements (title, content, priority, author_id, expiry_date, is_active, created_at) VALUES (?, ?, ?, ?, ?, 1, NOW())");
                $ins->execute([$title, $content, $priority, $user['id'], $expiryDate]);
                setFlashMessage('success', 'Campus announcement published successfully!');
            }
            redirect('announcements.php');
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// 5. Fetch all announcements
$announcements = [];
try {
    $stmt = $db->query("
        SELECT an.*, u.full_name as author_name
        FROM announcements an
        JOIN users u ON an.author_id = u.id
        ORDER BY an.created_at DESC
    ");
    $announcements = $stmt->fetchAll();
} catch (PDOException $e) {}

$pageTitle = 'Announcements';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> – NSBM NewsDesk Admin</title>
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

            <!-- Page Header -->
            <div class="admin-page-header">
                <h1>Campus Announcements</h1>
                <p>Publish official notices, timetables, and urgent alerts across the university portal.</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="flash-alert danger">
                    <span><?php echo htmlspecialchars($error); ?></span>
                    <button type="button" class="flash-close">&times;</button>
                </div>
            <?php endif; ?>

            <div class="admin-split-layout">
                <!-- Left: Create / Edit Form -->
                <div class="form-card-compact">
                    <div class="form-card-header">
                        <h3 style="display: flex; align-items: center;">
                            <?php if ($editAnnouncement): ?>
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:6px;"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                                Edit Notice #<?php echo $editAnnouncement['id']; ?>
                            <?php else: ?>
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:6px;"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                Publish New Notice
                            <?php endif; ?>
                        </h3>
                        <p><?php echo $editAnnouncement ? 'Update the notice details below.' : 'Fill in the details to publish a campus announcement.'; ?></p>
                    </div>

                    <form action="announcements.php" method="POST">
                        <?php if ($editAnnouncement): ?>
                            <input type="hidden" name="announcement_id" value="<?php echo $editAnnouncement['id']; ?>">
                        <?php endif; ?>

                        <div class="form-group">
                            <label class="form-label">Notice Headline <span class="required">*</span></label>
                            <input type="text" name="title" class="form-control"
                                   placeholder="e.g. Final Semester Examination Timetable"
                                   value="<?php echo htmlspecialchars($editAnnouncement['title'] ?? ''); ?>" required>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Priority Level</label>
                                <select name="priority" class="form-control">
                                    <option value="normal" <?php echo (($editAnnouncement['priority'] ?? '') === 'normal') ? 'selected' : ''; ?>>Normal</option>
                                    <option value="high"   <?php echo (($editAnnouncement['priority'] ?? '') === 'high')   ? 'selected' : ''; ?>>High Priority</option>
                                    <option value="urgent" <?php echo (($editAnnouncement['priority'] ?? '') === 'urgent') ? 'selected' : ''; ?>>Urgent Alert</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Expiry Date</label>
                                <input type="date" name="expiry_date" class="form-control"
                                       value="<?php echo htmlspecialchars($editAnnouncement['expiry_date'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Detailed Content <span class="required">*</span></label>
                            <textarea name="content" class="form-control" rows="5"
                                      placeholder="Full notice description, instructions, room numbers..." required><?php echo htmlspecialchars($editAnnouncement['content'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary" style="flex:1;">
                                <?php echo $editAnnouncement ? 'Save Changes' : 'Publish Notice'; ?>
                            </button>
                            <?php if ($editAnnouncement): ?>
                                <a href="announcements.php" class="btn btn-outline">Cancel</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <!-- Right: Announcements Table -->
                <div class="table-card" style="margin: 0;">
                    <div class="table-header">
                        <div>
                            <h3>All Announcements</h3>
                            <p><?php echo count($announcements); ?> notice<?php echo count($announcements) !== 1 ? 's' : ''; ?> total</p>
                        </div>
                    </div>
                    <div class="table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Title &amp; Excerpt</th>
                                    <th>Priority</th>
                                    <th>Expiry</th>
                                    <th>Status</th>
                                    <th style="text-align: right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($announcements)): ?>
                                    <?php foreach ($announcements as $ann): ?>
                                        <tr>
                                            <td><code>#<?php echo $ann['id']; ?></code></td>
                                            <td>
                                                <div style="font-weight: 600; color: var(--text-1); margin-bottom: 2px;">
                                                    <?php echo htmlspecialchars($ann['title']); ?>
                                                </div>
                                                <div style="font-size: 0.8rem; color: var(--text-3);">
                                                    <?php echo htmlspecialchars(truncateWords($ann['content'], 10)); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge-priority badge-<?php echo htmlspecialchars($ann['priority']); ?>">
                                                    <?php echo strtoupper($ann['priority']); ?>
                                                </span>
                                            </td>
                                            <td style="font-size: 0.82rem; color: var(--text-3);">
                                                <?php echo $ann['expiry_date'] ? formatDate($ann['expiry_date']) : '<span style="color:var(--text-4)">Never</span>'; ?>
                                            </td>
                                            <td>
                                                <a href="announcements.php?toggle_status=<?php echo $ann['id']; ?>"
                                                   class="btn btn-sm <?php echo $ann['is_active'] ? 'btn-primary' : 'btn-outline'; ?>"
                                                   style="padding: 3px 10px; font-size: 0.72rem;">
                                                    <?php echo $ann['is_active'] ? 'Active' : 'Disabled'; ?>
                                                </a>
                                            </td>
                                            <td style="text-align: right;">
                                                <div style="display: flex; gap: 6px; justify-content: flex-end;">
                                                    <a href="announcements.php?edit=<?php echo $ann['id']; ?>"
                                                       class="btn btn-outline btn-sm" style="padding: 3px 10px;">Edit</a>
                                                    <a href="announcements.php?action=delete&id=<?php echo $ann['id']; ?>"
                                                       class="btn btn-danger btn-sm" style="padding: 3px 10px;"
                                                       onclick="return confirm('Delete this announcement?');">Delete</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="table-empty">
                                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin: 0 auto 8px; display:block; opacity:0.3;">
                                                <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
                                            </svg>
                                            No announcements published yet.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html>
