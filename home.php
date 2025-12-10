<?php 
require 'db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

include 'header.php';
?>

<!-- Main content -->
<main class="flex-grow-1 container py-5">
    <h1 class="mb-3">Dashboard</h1>

    <p>
        Welcome to <?= htmlspecialchars($_SESSION['user']['org_name'] ?? 'Organization') ?>. 
        Use the menu above to navigate.
    </p>
</main>

<?php include 'footer.php'; ?>
