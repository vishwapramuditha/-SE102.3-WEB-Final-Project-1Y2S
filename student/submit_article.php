<?php
/**
 * Student & Club Article Submission Form
 * Module: Web and Mobile Application Development (SE102.3)
 * NSBM Green University - Faculty of Computing
 */

require_once __DIR__ . '/../config/functions.php';
requireLogin('../login.php');

$user = currentUser();
$db = getDB();

if (!$db) {
    die("Database connection error. Please run setup.php.");
}

$categories = [];
try {
    $stmt = $db->query("SELECT * FROM categories ORDER BY name ASC");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {}

$error = '';
$title = '';
$categoryId = '';
$excerpt = '';
$content = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $excerpt = trim($_POST['excerpt'] ?? '');
    $content = trim($_POST['content'] ?? '');

    // Form validation
    if (empty($title) || empty($categoryId) || empty($content)) {
        $error = 'Please fill out all mandatory fields (Title, Category, Content).';
    } elseif (strlen($title) < 5) {
        $error = 'Article headline must be at least 5 characters long.';
    } elseif (strlen($content) < 20) {
        $error = 'Article content is too brief. Please provide meaningful details.';
    } else {
        // Handle cover photo upload
        $imagePath = null;
        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadResult = uploadImage($_FILES['cover_image'], 'assets/uploads/articles/');
            if (!$uploadResult['success']) {
                $error = 'Image upload error: ' . $uploadResult['error'];
            } else {
                $imagePath = $uploadResult['filename'];
            }
        }

        if (empty($error)) {
            $slug = slugify($title) . '-' . time();
            try {
                $stmt = $db->prepare("
                    INSERT INTO articles (title, slug, excerpt, content, category_id, author_id, image_url, status, is_featured, is_breaking, views_count, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', 0, 0, 0, NOW())
                ");
                $stmt->execute([
                    $title,
                    $slug,
                    $excerpt ?: truncateWords($content, 20),
                    $content,
                    $categoryId,
                    $user['id'],
                    $imagePath
                ]);

                setFlashMessage('success', 'Your story "' . htmlspecialchars($title) . '" has been submitted for editorial board approval!');
                redirect('my_articles.php');
            } catch (PDOException $e) {
                $error = 'Database submission error: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Submit News Article';
include __DIR__ . '/../includes/header.php';
?>

<main class="container" style="padding-top: 30px; padding-bottom: 60px;">
    <div style="max-width: 780px; margin: 0 auto;">
        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="dashboard.php">Student Portal</a>
            <span>&rsaquo;</span>
            <span>Submit News Article</span>
        </div>

        <div style="background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 36px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border-top: 5px solid #006a4e;">
            <div style="margin-bottom: 24px;">
                <h1 style="font-size: 1.6rem; font-weight: 800; color: var(--text-1); margin-bottom: 4px;">
                    Submit Campus Article or Announcement
                </h1>
                <p style="color: var(--text-3); font-size: 0.9rem;">
                    Submit club activities, academic projects, or student events for review by the NSBM NewsDesk editors.
                </p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="flash-alert danger">
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <form action="submit_article.php" method="POST" enctype="multipart/form-data" id="articleForm">
                <!-- Headline -->
                <div class="form-group">
                    <label for="articleTitle" class="form-label">
                        Headline / Title <span class="required">*</span>
                    </label>
                    <input type="text" name="title" id="articleTitle" class="form-control" placeholder="e.g. Computing Society Announces 2026 CodeFest Hackathon" value="<?php echo htmlspecialchars($title); ?>" required>
                    <div class="form-hint">Make it catchy and descriptive (min 5 characters).</div>
                </div>

                <!-- Category -->
                <div class="form-group">
                    <label for="articleCategory" class="form-label">
                        Category <span class="required">*</span>
                    </label>
                    <select name="category_id" id="articleCategory" class="form-control" required>
                        <option value="">-- Select News Category --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo ($categoryId == $cat['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Short Summary / Excerpt -->
                <div class="form-group">
                    <label for="articleExcerpt" class="form-label">
                        Short Summary (Excerpt)
                    </label>
                    <textarea name="excerpt" id="articleExcerpt" class="form-control" rows="2" style="min-height: 70px;" placeholder="Brief 1-2 sentence preview for news cards..."><?php echo htmlspecialchars($excerpt); ?></textarea>
                </div>

                <!-- Full Content -->
                <div class="form-group">
                    <label for="articleContent" class="form-label">
                        Full Article Content <span class="required">*</span>
                    </label>
                    <textarea name="content" id="articleContent" class="form-control" rows="8" placeholder="Provide full event details, schedule, venue, registration links, or project summary..." required><?php echo htmlspecialchars($content); ?></textarea>
                </div>

                <!-- Cover Image Upload -->
                <div class="form-group">
                    <label for="articleImageInput" class="form-label">
                        Cover Photo (Optional)
                    </label>
                    <input type="file" name="cover_image" id="articleImageInput" class="form-control" accept="image/jpeg,image/png,image/webp">
                    <div class="form-hint">Recommended resolution: 800x450 px (JPG, PNG, WEBP). Max size: 5MB.</div>
                    <div id="imagePreviewContainer"></div>
                </div>

                <!-- Submission Disclaimer -->
                <div style="background: var(--surface-2); border: 1px solid var(--border); border-radius: 8px; padding: 14px 18px; margin-bottom: 24px; font-size: 0.85rem; color: var(--text-3);">
                    ℹ <strong>Note for Undergraduates:</strong> All student-submitted articles undergo editorial review before appearing on the public campus portal. You can monitor the approval status in your dashboard.
                </div>

                <!-- Action Buttons -->
                <div style="display: flex; gap: 12px; justify-content: flex-end;">
                    <a href="dashboard.php" class="btn btn-outline">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        Submit for Approval &rarr;
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
