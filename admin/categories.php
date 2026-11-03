<?php
/**
 * Manage News Categories (Admin CRUD)
 * Module: Web and Mobile Application Development (SE102.3)
 * NSBM Green University - Faculty of Computing
 */

require_once __DIR__ . '/../config/functions.php';
requireAdmin('../login.php');

$db = getDB();
if (!$db) {
    die("Database connection error. Please run setup.php.");
}

$editCategory = null;
$error = '';

// 1. Handle Delete Action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delId = (int)$_GET['id'];
    try {
        // Count articles in category
        $cntStmt = $db->prepare("SELECT COUNT(*) FROM articles WHERE category_id = ?");
        $cntStmt->execute([$delId]);
        $artCount = (int)$cntStmt->fetchColumn();

        if ($artCount > 0) {
            setFlashMessage('warning', 'Category cannot be deleted directly because it contains ' . $artCount . ' articles. Please reassign or delete articles first.');
        } else {
            $del = $db->prepare("DELETE FROM categories WHERE id = ?");
            $del->execute([$delId]);
            setFlashMessage('info', 'Category deleted successfully.');
        }
        redirect('categories.php');
    } catch (PDOException $e) {
        setFlashMessage('danger', 'Error deleting category: ' . $e->getMessage());
    }
}

// 2. Handle Edit Trigger
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$editId]);
    $editCategory = $stmt->fetch();
}

// 3. Handle Add / Update POST Form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $badgeColor = trim($_POST['badge_color'] ?? '#006a4e');
    $categoryId = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;

    if (empty($name)) {
        $error = 'Category name is required.';
    } else {
        if (empty($slug)) {
            $slug = slugify($name);
        } else {
            $slug = slugify($slug);
        }

        try {
            if ($categoryId > 0) {
                // Update
                $upd = $db->prepare("UPDATE categories SET name = ?, slug = ?, description = ?, badge_color = ? WHERE id = ?");
                $upd->execute([$name, $slug, $description, $badgeColor, $categoryId]);
                setFlashMessage('success', 'Category "' . htmlspecialchars($name) . '" updated successfully!');
            } else {
                // Create
                $ins = $db->prepare("INSERT INTO categories (name, slug, description, badge_color, created_at) VALUES (?, ?, ?, ?, NOW())");
                $ins->execute([$name, $slug, $description, $badgeColor]);
                setFlashMessage('success', 'Category "' . htmlspecialchars($name) . '" created successfully!');
            }
            redirect('categories.php');
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// 4. Fetch All Categories with Article Counts
$categories = [];
try {
    $stmt = $db->query("
        SELECT c.*, COUNT(a.id) as article_count
        FROM categories c
        LEFT JOIN articles a ON c.id = a.category_id
        GROUP BY c.id
        ORDER BY c.id ASC
    ");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {}

$pageTitle = 'Manage Categories';
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

            <div class="admin-page-header"><h1>Campus News Categories (CRUD)</h1><p>Manage default campus beats: Academic, Events, Sports, General, or add custom topics.</p></div>

            <?php if (!empty($error)): ?>
                <div class="flash-alert danger">
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: 1fr 1.8fr; gap: 28px; align-items: flex-start;">
                <!-- Left: Add / Edit Form -->
                <div style="background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border-top: 4px solid #006a4e;">
                    <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--text-1); margin-bottom: 16px;">
                        <?php echo $editCategory ? 'Edit Category #' . $editCategory['id'] : '+ Add New Category'; ?>
                    </h3>

                    <form action="categories.php" method="POST">
                        <?php if ($editCategory): ?>
                            <input type="hidden" name="category_id" value="<?php echo $editCategory['id']; ?>">
                        <?php endif; ?>

                        <div class="form-group">
                            <label class="form-label">Category Name <span class="required">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Academic, Sports..." value="<?php echo htmlspecialchars($editCategory['name'] ?? ''); ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Slug (URL friendly)</label>
                            <input type="text" name="slug" class="form-control" placeholder="e.g. academic" value="<?php echo htmlspecialchars($editCategory['slug'] ?? ''); ?>">
                            <div class="form-hint">Leave blank to generate automatically.</div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Badge Color</label>
                            <div style="display: flex; gap: 8px; align-items: center;">
                                <input type="color" name="badge_color" value="<?php echo htmlspecialchars($editCategory['badge_color'] ?? '#006a4e'); ?>" style="width: 44px; height: 38px; border-radius: 6px; border: 1px solid var(--border); cursor: pointer;">
                                <span style="font-size: 0.85rem; color: var(--text-3);">Choose brand color for badges &amp; pills</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2" style="min-height: 70px;" placeholder="Brief explanation of stories covered in this section..."><?php echo htmlspecialchars($editCategory['description'] ?? ''); ?></textarea>
                        </div>

                        <div style="display: flex; gap: 8px;">
                            <button type="submit" class="btn btn-primary" style="flex: 1;">
                                <?php echo $editCategory ? 'Update Category' : 'Create Category'; ?>
                            </button>
                            <?php if ($editCategory): ?>
                                <a href="categories.php" class="btn btn-outline">Cancel</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <!-- Right: Categories Table -->
                <div class="table-card" style="margin: 0;">
                    <div class="table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Category &amp; Badge</th>
                                    <th>Slug</th>
                                    <th>Articles</th>
                                    <th style="text-align: right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($categories)): ?>
                                    <?php foreach ($categories as $cat): ?>
                                        <tr>
                                            <td><code>#<?php echo $cat['id']; ?></code></td>
                                            <td>
                                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 2px;">
                                                    <span class="badge-cat" style="background-color: <?php echo htmlspecialchars($cat['badge_color'] ?: '#006a4e'); ?>; font-size: 0.72rem;">
                                                        <?php echo htmlspecialchars($cat['name']); ?>
                                                    </span>
                                                </div>
                                                <div style="font-size: 0.8rem; color: var(--text-3);"><?php echo htmlspecialchars($cat['description'] ?: 'No description provided'); ?></div>
                                            </td>
                                            <td><code><?php echo htmlspecialchars($cat['slug']); ?></code></td>
                                            <td><strong><?php echo $cat['article_count']; ?></strong> stories</td>
                                            <td style="text-align: right;">
                                                <div style="display: flex; gap: 6px; justify-content: flex-end;">
                                                    <a href="categories.php?edit=<?php echo $cat['id']; ?>" class="btn btn-outline btn-sm" style="padding: 3px 8px;">
                                                        Edit
                                                    </a>
                                                    <a href="categories.php?action=delete&id=<?php echo $cat['id']; ?>" class="btn btn-danger btn-sm" style="padding: 3px 8px;" onclick="return confirm('Delete this category?');">
                                                        Delete
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" style="text-align: center; padding: 30px; color: var(--text-3);">
                                            No categories configured.
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
