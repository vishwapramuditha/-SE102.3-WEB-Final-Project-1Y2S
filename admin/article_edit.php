<?php
/**
 * Edit News Article (Admin)
 * Module: Web and Mobile Application Development (SE102.3)
 * NSBM Green University - Faculty of Computing
 */

require_once __DIR__ . '/../config/functions.php';
requireAdmin('../login.php');

$db = getDB();
if (!$db) {
    die("Database connection error. Please run setup.php.");
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $db->prepare("SELECT * FROM articles WHERE id = ?");
$stmt->execute([$id]);
$article = $stmt->fetch();

if (!$article) {
    setFlashMessage('danger', 'Article not found.');
    redirect('articles.php');
}

$categories = [];
try {
    $cStmt = $db->query("SELECT * FROM categories ORDER BY name ASC");
    $categories = $cStmt->fetchAll();
} catch (PDOException $e) {}

$error = '';
$title = $article['title'];
$categoryId = $article['category_id'];
$excerpt = $article['excerpt'];
$content = $article['content'];
$status = $article['status'];
$isFeatured = $article['is_featured'];
$isBreaking = $article['is_breaking'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $excerpt = trim($_POST['excerpt'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $status = in_array($_POST['status'] ?? '', ['approved', 'draft', 'pending', 'rejected']) ? $_POST['status'] : 'approved';
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    $isBreaking = isset($_POST['is_breaking']) ? 1 : 0;

    if (empty($title) || empty($categoryId) || empty($content)) {
        $error = 'Please fill out Headline, Category, and Content.';
    } elseif (strlen($title) < 5) {
        $error = 'Headline must be at least 5 characters long.';
    } else {
        $imagePath = $article['image_url']; // keep existing by default
        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadResult = uploadImage($_FILES['cover_image'], 'assets/uploads/articles/');
            if (!$uploadResult['success']) {
                $error = 'Image upload error: ' . $uploadResult['error'];
            } else {
                $imagePath = $uploadResult['filename'];
            }
        }

        if (empty($error)) {
            try {
                $upd = $db->prepare("
                    UPDATE articles 
                    SET title = ?, excerpt = ?, content = ?, category_id = ?, 
                        image_url = ?, status = ?, is_featured = ?, is_breaking = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $upd->execute([
                    $title,
                    $excerpt ?: truncateWords($content, 20),
                    $content,
                    $categoryId,
                    $imagePath,
                    $status,
                    $isFeatured,
                    $isBreaking,
                    $id
                ]);

                setFlashMessage('success', 'Article #' . $id . ' updated successfully!');
                redirect('articles.php');
            } catch (PDOException $e) {
                $error = 'Database update error: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Edit Article #' . $id;
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

            <div style="max-width: 820px; margin: 0 auto;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
                    <div>
                        <h1 style="font-size: 1.5rem; font-weight: 800; color: var(--text-1); margin-bottom: 4px;">
                            Edit Article #<?php echo $id; ?>
                        </h1>
                        <p style="color: var(--text-3); font-size: 0.9rem;">Modify headline, content, category, or promotional tags.</p>
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <a href="../article.php?slug=<?php echo htmlspecialchars($article['slug']); ?>" target="_blank" class="btn btn-outline btn-sm">Preview Live</a>
                        <a href="articles.php" class="btn btn-outline btn-sm">&larr; Back to Articles</a>
                    </div>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="flash-alert danger">
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>

                <div class="form-card" style="margin: 0; max-width: 100%;">
                    <form action="article_edit.php?id=<?php echo $id; ?>" method="POST" enctype="multipart/form-data" id="articleForm">
                        <div class="form-group">
                            <label for="articleTitle" class="form-label">Headline / Title <span class="required">*</span></label>
                            <input type="text" name="title" id="articleTitle" class="form-control" value="<?php echo htmlspecialchars($title); ?>" required>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="articleCategory" class="form-label">Category <span class="required">*</span></label>
                                <select name="category_id" id="articleCategory" class="form-control" required>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>" <?php echo ($categoryId == $cat['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="articleStatus" class="form-label">Publication Status</label>
                                <select name="status" id="articleStatus" class="form-control">
                                    <option value="approved" <?php echo ($status === 'approved') ? 'selected' : ''; ?>>Approved (Public)</option>
                                    <option value="pending" <?php echo ($status === 'pending') ? 'selected' : ''; ?>>Pending Review</option>
                                    <option value="rejected" <?php echo ($status === 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                                    <option value="draft" <?php echo ($status === 'draft') ? 'selected' : ''; ?>>Draft</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="articleExcerpt" class="form-label">Short Excerpt (Summary)</label>
                            <textarea name="excerpt" id="articleExcerpt" class="form-control" rows="2" style="min-height: 70px;"><?php echo htmlspecialchars($excerpt); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="articleContent" class="form-label">Full Article Content <span class="required">*</span></label>
                            <textarea name="content" id="articleContent" class="form-control" rows="8" required><?php echo htmlspecialchars($content); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="articleImageInput" class="form-label">Cover Photo</label>
                            <?php if (!empty($article['image_url'])): ?>
                                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 10px;">
                                    <img src="../<?php echo htmlspecialchars($article['image_url']); ?>" alt="" style="width: 100px; height: 60px; object-fit: cover; border-radius: 6px; border: 1px solid var(--border);">
                                    <span style="font-size: 0.82rem; color: var(--text-3);">Current cover photo. Select a new file below to replace it.</span>
                                </div>
                            <?php endif; ?>
                            <input type="file" name="cover_image" id="articleImageInput" class="form-control" accept="image/jpeg,image/png,image/webp">
                            <div id="imagePreviewContainer"></div>
                        </div>

                        <div style="background: var(--surface-2); border: 1px solid var(--border); border-radius: 8px; padding: 14px 18px; margin-bottom: 24px; display: flex; gap: 24px;">
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.9rem; font-weight: 600;">
                                <input type="checkbox" name="is_featured" value="1" <?php echo $isFeatured ? 'checked' : ''; ?> style="accent-color: var(--brand); width: 16px; height: 16px;">
                                <span style="display: inline-flex; align-items: center; gap: 5px;"><svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor" style="color: var(--accent);"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg> Mark as Featured Hero Story</span>
                            </label>

                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.9rem; font-weight: 600;">
                                <input type="checkbox" name="is_breaking" value="1" <?php echo $isBreaking ? 'checked' : ''; ?> style="accent-color: var(--urgent); width: 16px; height: 16px;">
                                <span style="display: inline-flex; align-items: center; gap: 5px;"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--urgent);"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg> Display in Breaking Ticker</span>
                            </label>
                        </div>

                        <div style="display: flex; justify-content: flex-end; gap: 12px;">
                            <a href="articles.php" class="btn btn-outline">Cancel</a>
                            <button type="submit" class="btn btn-primary">Save Changes &rarr;</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html>
