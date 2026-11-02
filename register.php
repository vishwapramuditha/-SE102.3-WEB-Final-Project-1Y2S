<?php
/**
 * Student Registration Portal
 * Module: Web and Mobile Application Development (SE102.3)
 * NSBM Green University - Faculty of Computing
 */

require_once __DIR__ . '/config/functions.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$pageTitle = 'Student Registration';
$error = '';
$fullName = '';
$studentId = '';
$email = '';
$department = 'Faculty of Computing';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $studentId = trim($_POST['student_id'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $department = trim($_POST['department'] ?? 'Faculty of Computing');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($fullName) || empty($email) || empty($password) || empty($confirmPassword)) {
        $error = 'Please fill out all required fields.';
    } elseif (strlen($fullName) < 3) {
        $error = 'Full Name must be at least 3 characters long.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Password confirmation does not match.';
    } else {
        $db = getDB();
        if (!$db) {
            $error = 'Database error. Please run setup.php first.';
        } else {
            try {
                // Check if email already registered
                $checkStmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
                $checkStmt->execute([$email]);
                if ($checkStmt->fetch()) {
                    $error = 'An account with this email address already exists. Please sign in.';
                } else {
                    // Create account
                    $hashed = password_hash($password, PASSWORD_DEFAULT);
                    $ins = $db->prepare("
                        INSERT INTO users (full_name, student_id, email, password, role, department, created_at)
                        VALUES (?, ?, ?, ?, 'student', ?, NOW())
                    ");
                    $ins->execute([$fullName, $studentId, $email, $hashed, $department]);
                    $newUserId = $db->lastInsertId();

                    // Auto subscribe new student to Academic & Events by default
                    try {
                        $sub = $db->prepare("INSERT INTO category_subscriptions (user_id, category_id) VALUES (?, 1), (?, 2)");
                        $sub->execute([$newUserId, $newUserId]);
                    } catch (Exception $ex) {}

                    // Auto login
                    $_SESSION['user_id'] = $newUserId;
                    $_SESSION['user_name'] = $fullName;
                    $_SESSION['user_email'] = $email;
                    $_SESSION['user_role'] = 'student';
                    $_SESSION['student_id'] = $studentId;
                    $_SESSION['user_department'] = $department;

                    setFlashMessage('success', 'Registration successful! Welcome to NSBM NewsDesk, ' . htmlspecialchars($fullName) . '.');
                    redirect('student/dashboard.php');
                }
            } catch (PDOException $e) {
                $error = 'Database registration error: ' . $e->getMessage();
            }
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<main class="container" style="padding-top: 36px; padding-bottom: 60px;">
    <div class="auth-card" style="max-width: 520px;">
        <div style="text-align: center; margin-bottom: 24px;">
            <a href="index.php" class="brand-link" style="justify-content: center; display: inline-flex; margin-bottom: 12px;" aria-label="NSBM Green University NewsDesk">
                <img src="assets/images/nsbm-logo-dark.svg" alt="NSBM Green University NewsDesk" class="brand-logo brand-logo-dark" style="height: 50px;">
                <img src="assets/images/nsbm-logo-light.svg" alt="NSBM Green University NewsDesk" class="brand-logo brand-logo-light" style="height: 50px;">
            </a>
            <h1 style="font-size: 1.45rem; font-weight: 800; color: var(--text-1); margin-bottom: 4px;">Student Registration</h1>
            <p style="color: var(--text-3); font-size: 0.88rem;">Create your student account to submit stories and save articles</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="flash-alert danger" style="margin-top: 0;">
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST" id="registerForm">
            <div class="form-group">
                <label for="fullName" class="form-label">Full Name <span class="required">*</span></label>
                <input type="text" name="full_name" id="fullName" class="form-control" placeholder="e.g. Kavindu Perera" required value="<?php echo htmlspecialchars($fullName); ?>">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="studentId" class="form-label">Student ID</label>
                    <input type="text" name="student_id" id="studentId" class="form-control" placeholder="NSBM/SOC/26.1/1042" value="<?php echo htmlspecialchars($studentId); ?>">
                </div>

                <div class="form-group">
                    <label for="department" class="form-label">Faculty / Department</label>
                    <select name="department" id="department" class="form-control">
                        <option value="Faculty of Computing" <?php echo ($department === 'Faculty of Computing') ? 'selected' : ''; ?>>Faculty of Computing</option>
                        <option value="Faculty of Business" <?php echo ($department === 'Faculty of Business') ? 'selected' : ''; ?>>Faculty of Business</option>
                        <option value="Faculty of Engineering" <?php echo ($department === 'Faculty of Engineering') ? 'selected' : ''; ?>>Faculty of Engineering</option>
                        <option value="Faculty of Science" <?php echo ($department === 'Faculty of Science') ? 'selected' : ''; ?>>Faculty of Science</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="email" class="form-label">Email Address <span class="required">*</span></label>
                <input type="email" name="email" id="email" class="form-control" placeholder="student@nsbm.ac.lk" required value="<?php echo htmlspecialchars($email); ?>">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="password" class="form-label">Password <span class="required">*</span></label>
                    <input type="password" name="password" id="password" class="form-control" placeholder="Min 6 characters" required>
                </div>

                <div class="form-group">
                    <label for="confirmPassword" class="form-label">Confirm Password <span class="required">*</span></label>
                    <input type="password" name="confirm_password" id="confirmPassword" class="form-control" placeholder="Repeat password" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 1rem; margin-top: 8px;">
                Create Account &rarr;
            </button>
        </form>

        <div style="text-align: center; margin-top: 20px; font-size: 0.88rem; color: var(--text-3);">
            Already have an account? <a href="login.php" style="font-weight: 700; color: var(--brand);">Sign in here</a>
        </div>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
