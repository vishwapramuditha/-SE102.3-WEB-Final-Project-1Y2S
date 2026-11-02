<?php
/**
 * User & Administrator Login Portal
 * Module: Web and Mobile Application Development (SE102.3)
 * NSBM Green University - Faculty of Computing
 */

require_once __DIR__ . '/config/functions.php';

// If already logged in, redirect based on role
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('admin/index.php');
    } else {
        redirect('student/dashboard.php');
    }
}

$pageTitle = 'Sign In';
$error = '';
$redirectUrl = isset($_GET['redirect']) ? trim($_GET['redirect']) : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter both your email address and password.';
    } else {
        $db = getDB();
        if (!$db) {
            $error = 'Database connection error. Please run setup.php first.';
        } else {
            try {
                $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    // Initialize authenticated session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['full_name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['student_id'] = $user['student_id'];
                    $_SESSION['user_department'] = $user['department'];

                    setFlashMessage('success', 'Welcome back, ' . htmlspecialchars($user['full_name']) . '!');

                    if (!empty($redirectUrl) && !str_starts_with($redirectUrl, 'http')) {
                        redirect($redirectUrl);
                    } elseif ($user['role'] === 'admin') {
                        redirect('admin/index.php');
                    } else {
                        redirect('student/dashboard.php');
                    }
                } else {
                    $error = 'Invalid email address or password. Please try again.';
                }
            } catch (PDOException $e) {
                $error = 'Authentication failed: ' . $e->getMessage();
            }
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<main class="container" style="padding-top: 40px; padding-bottom: 60px;">
    <div class="auth-card">
        <div style="text-align: center; margin-bottom: 28px;">
            <a href="index.php" class="brand-link" style="justify-content: center; display: inline-flex; margin-bottom: 14px;" aria-label="NSBM Green University NewsDesk">
                <img src="assets/images/nsbm-logo-dark.svg" alt="NSBM Green University NewsDesk" class="brand-logo brand-logo-dark" style="height: 50px;">
                <img src="assets/images/nsbm-logo-light.svg" alt="NSBM Green University NewsDesk" class="brand-logo brand-logo-light" style="height: 50px;">
            </a>
            <h1 style="font-family: 'Playfair Display', Georgia, serif; font-size: 1.55rem; font-weight: 800; color: var(--text-1); margin-bottom: 5px; letter-spacing: -0.02em;">Sign in to NewsDesk</h1>
            <p style="color: var(--text-3); font-size: 0.875rem;">Enter your student or administration credentials</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="flash-alert danger" style="margin-top: 0;">
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <form action="login.php<?php echo !empty($redirectUrl) ? '?redirect=' . urlencode($redirectUrl) : ''; ?>" method="POST" id="loginForm">
            <div class="form-group">
                <label for="loginEmail" class="form-label">Email Address <span class="required">*</span></label>
                <input type="email" name="email" id="loginEmail" class="form-control" placeholder="e.g. student@nsbm.ac.lk" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                    <label for="loginPassword" class="form-label" style="margin-bottom: 0;">Password <span class="required">*</span></label>
                </div>
                <input type="password" name="password" id="loginPassword" class="form-control" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 11px; font-size: 0.95rem; margin-top: 8px;">
                Sign In &rarr;
            </button>
        </form>

        <!-- Quick Demo Credentials for Grading -->
        <div style="margin-top: 24px; padding-top: 18px; border-top: 1px solid var(--border);">
            <p style="font-size: 0.75rem; font-weight: 700; color: var(--text-3); text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 10px; text-align: center;">
                Academic Evaluation Quick Autofill:
            </p>
            <div style="display: flex; gap: 8px;">
                <button type="button" class="btn btn-outline btn-sm" style="flex: 1; font-size: 0.8rem; display: inline-flex; align-items: center; justify-content: center;" onclick="document.getElementById('loginEmail').value='admin@nsbm.ac.lk'; document.getElementById('loginPassword').value='admin123';">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:5px;"><circle cx="7.5" cy="15.5" r="5.5"/><path d="m21 2-9.6 9.6"/><path d="m15.5 7.5 3 3L22 7l-3-3"/></svg> Fill Admin
                </button>
                <button type="button" class="btn btn-outline btn-sm" style="flex: 1; font-size: 0.8rem; display: inline-flex; align-items: center; justify-content: center;" onclick="document.getElementById('loginEmail').value='student@nsbm.ac.lk'; document.getElementById('loginPassword').value='student123';">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:5px;"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg> Fill Student
                </button>
            </div>
        </div>

        <div style="text-align: center; margin-top: 20px; font-size: 0.875rem; color: var(--text-3);">
            Don't have an account yet? <a href="register.php" style="font-weight: 600; color: var(--brand);">Register as Student</a>
        </div>
    </div>
</main>


<?php include __DIR__ . '/includes/footer.php'; ?>
