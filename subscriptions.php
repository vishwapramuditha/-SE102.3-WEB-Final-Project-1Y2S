<?php
/**
 * Student Category Subscriptions & Preferences
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

// Handle Form Submission: Update Subscriptions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_subscriptions'])) {
    $selectedCategoryIds = isset($_POST['categories']) && is_array($_POST['categories'])
        ? array_map('intval', $_POST['categories']) : [];
    try {
        $db->beginTransaction();
        $del = $db->prepare("DELETE FROM category_subscriptions WHERE user_id = ?");
        $del->execute([$user['id']]);
        if (!empty($selectedCategoryIds)) {
            $ins = $db->prepare("INSERT INTO category_subscriptions (user_id, category_id) VALUES (?, ?)");
            foreach ($selectedCategoryIds as $catId) {
                $ins->execute([$user['id'], $catId]);
            }
        }
        $db->commit();
        setFlashMessage('success', 'Your news category preferences have been updated!');
        redirect('subscriptions.php');
    } catch (PDOException $e) {
        $db->rollBack();
        setFlashMessage('danger', 'Error updating subscriptions: ' . $e->getMessage());
    }
}
