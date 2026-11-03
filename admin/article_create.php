<?php
/**
 * Create News Article (Admin)
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

$categories = [];
try {
    $cStmt = $db->query("SELECT * FROM categories ORDER BY name ASC");
    $categories = $cStmt->fetchAll();
} catch (PDOException $e) {}

$error = '';
$title = '';
$categoryId = '';
$excerpt = '';
$content = '';
$status = 'approved';
$isFeatured = 0;
$isBreaking = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $excerpt = trim($_POST['excerpt'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $status = in_array($_POST['status'] ?? '', ['approved', 'draft', 'pending']) ? $_POST['status'] : 'approved';
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    $isBreaking = isset($_POST['is_breaking']) ? 1 : 0;

    if (empty($title) || empty($categoryId) || empty($content)) {
        $error = 'Please fill out Headline, Category, and Content.';
    } elseif (strlen($title) < 5) {
        $error = 'Headline must be at least 5 characters long.';
    } else {
        // Handle cover photo upload
        $imagePath = null;
        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadResult = uploadImage($_FILES['cover_image'], 'assets/uploads/articles/');
            if (!$uploadResult['success']) {
                $error = 'Image error: ' . $uploadResult['error'];
            } else {
                $imagePath = $uploadResult['filename'];
            }
        }

        if (empty($error)) {
            $slug = slugify($title) . '-' . time();
            try {
                $stmt = $db->prepare("
                    INSERT INTO articles (title, slug, excerpt, content, category_id, author_id, image_url, status, is_featured, is_breaking, views_count, published_at, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW(), NOW())
                ");
                $stmt->execute([
                    $title,
                    $slug,
                    $excerpt ?: truncateWords($content, 20),
                    $content,
                    $categoryId,
                    $user['id'],
                    $imagePath,
                    $status,
                    $isFeatured,
                    $isBreaking
                ]);

                setFlashMessage('success', 'News article "' . htmlspecialchars($title) . '" created successfully!');
                redirect('articles.php');
            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Create News Article';
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
                            Publish Campus News Article
                        </h1>
                        <p style="color: var(--text-3); font-size: 0.9rem;">Author and publish official articles to the university feed.</p>
                    </div>
                    <a href="articles.php" class="btn btn-outline btn-sm">&larr; Back to Articles</a>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="flash-alert danger">
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>

                <div class="form-card" style="margin: 0; max-width: 100%;">
                    <form action="article_create.php" method="POST" enctype="multipart/form-data" id="articleForm">
                        <div class="form-group">
                            <label for="articleTitle" class="form-label">Headline / Title <span class="required">*</span></label>
                            <input type="text" name="title" id="articleTitle" class="form-control" placeholder="e.g. NSBM Announces 2026 Undergraduate Research Symposium" value="<?php echo htmlspecialchars($title); ?>" required>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="articleCategory" class="form-label">Category <span class="required">*</span></label>
                                <select name="category_id" id="articleCategory" class="form-control" required>
                                    <option value="">-- Select Category --</option>
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
                                    <option value="approved" <?php echo ($status === 'approved') ? 'selected' : ''; ?>>Approved (Publish Live)</option>
                                    <option value="draft" <?php echo ($status === 'draft') ? 'selected' : ''; ?>>Draft (Hidden)</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="articleExcerpt" class="form-label">Short Excerpt (Summary)</label>
                            <textarea name="excerpt" id="articleExcerpt" class="form-control" rows="2" style="min-height: 70px;" placeholder="Brief lead paragraph for preview cards..."><?php echo htmlspecialchars($excerpt); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="articleContent" class="form-label">Full Article Content <span class="required">*</span></label>
                            <textarea name="content" id="articleContent" class="form-control" rows="8" placeholder="Complete article body... (HTML tags like <p>, <strong> are supported)" required><?php echo htmlspecialchars($content); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="articleImageInput" class="form-label">Cover Photo</label>
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
                            <button type="submit" class="btn btn-primary">Publish Article &rarr;</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html>
