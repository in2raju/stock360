<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

if (!isset($pdo)) {
    require 'db.php';
}

$loginUserTypeId = $_SESSION['user']['user_type_id'] ?? '';
$loginUserId     = $_SESSION['user']['user_id'] ?? '';
$brCode          = $_SESSION['user']['br_code'] ?? '';

$sqlMenus = "SELECT m.MENU_ID, m.MENU_NAME, m.MENU_LINK, m.PARENT_ID
             FROM menu_info m
             INNER JOIN user_menu_view_permission ump 
                 ON m.MENU_ID = ump.MENU_ID
             WHERE ump.USER_TYPE_ID = :user_type_id
               AND ump.CAN_VIEW = true
             ORDER BY m.MENU_ID";

$stmtMenus = $pdo->prepare($sqlMenus);
$stmtMenus->execute(['user_type_id' => $loginUserTypeId]);
$menus = $stmtMenus->fetchAll(PDO::FETCH_ASSOC);

$menuTree = [];
foreach ($menus as $menu) {
    $parentId = $menu['PARENT_ID'] ?: 0;
    $menuTree[$parentId][] = $menu;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($_SESSION['user']['org_name'] ?? 'Stock36') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        /* === Smart Gradient Navbar === */
        .navbar {
            background: linear-gradient(90deg, rgb(204, 18, 18) 0%, rgb(204, 18, 18) 100%);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
            transition: background 0.3s ease;
        }

        .navbar-brand {
            font-family: "SF Mono", "Roboto Mono", "Menlo", "Consolas", monospace;
            font-weight: 600;
            font-size: 1.3rem;
            color: #ffffff !important;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }

        .navbar-brand:hover {
            color: #00e6e6 !important;
            text-shadow: 0 0 6px rgba(0, 230, 230, 0.8);
        }

        /* === Nav Links === */
        .navbar-nav .nav-link {
            color: #dbe4ff !important;
            transition: color 0.3s ease, transform 0.2s ease;
        }

        .navbar-nav .nav-link:hover {
            color: #00e6e6 !important;
            transform: scale(1.05);
        }

        /* === Dropdown hover behavior === */
        .navbar-nav .dropdown:hover > .dropdown-menu {
            display: block;
            margin-top: 0;
        }

        .dropdown-menu {
            background-color: #1e2633;
            border: 1px solid #2b3542;
            border-radius: 8px;
            animation: fadeIn 0.2s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(5px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .dropdown-item {
            color: #cfd8e3;
            transition: all 0.2s ease;
        }

        .dropdown-item:hover {
            background-color: #0d6efd;
            color: #ffffff;
        }

        /* === Responsive adjustments === */
        @media (max-width: 992px) {
            .navbar-nav .dropdown:hover > .dropdown-menu {
                display: none;
            }
        }

        .navbar-toggler {
            border-color: rgba(255, 255, 255, 0.5);
        }

        .navbar-toggler-icon {
            filter: invert(1);
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid">
        <!-- Brand -->
        <a class="navbar-brand" href="home.php">
            <?= htmlspecialchars($_SESSION['user']['org_name'] ?? 'Stock36') ?>
        </a>

        <!-- Toggler -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navbar Content -->
        <div class="collapse navbar-collapse" id="navbarContent">
            <!-- Left menus -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <?php
                if (isset($menuTree[0])) {
                    foreach ($menuTree[0] as $menu) {
                        $menuId = $menu['MENU_ID'];
                        if (isset($menuTree[$menuId])) {
                            echo '<li class="nav-item dropdown">';
                            echo '<a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">'
                                . htmlspecialchars($menu['MENU_NAME']) . '</a>';
                            echo '<ul class="dropdown-menu">';
                            foreach ($menuTree[$menuId] as $sub) {
                                echo '<li><a class="dropdown-item" href="' . htmlspecialchars($sub['MENU_LINK']) . '">'
                                    . htmlspecialchars($sub['MENU_NAME']) . '</a></li>';
                            }
                            echo '</ul></li>';
                        } else {
                            echo '<li class="nav-item">';
                            echo '<a class="nav-link" href="' . htmlspecialchars($menu['MENU_LINK']) . '">'
                                . htmlspecialchars($menu['MENU_NAME']) . '</a></li>';
                        }
                    }
                }
                ?>
            </ul>

            <!-- User Dropdown -->
<ul class="navbar-nav ms-auto" style="margin-right: 100px;"> <!-- small gap from right -->
    <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
            <i class="bi bi-person-circle me-1"></i>
            <?= htmlspecialchars($loginUserId) ?>
        </a>
        <ul class="dropdown-menu dropdown-menu-end">
            <li>
                <a class="dropdown-item" href="profile.php">
                    <i class="bi bi-person me-2"></i>Profile
                </a>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <a class="dropdown-item" href="logout.php">
                    <i class="bi bi-box-arrow-right me-2"></i>Logout
                </a>
            </li>
        </ul>
    </li>
</ul>
        </div>
    </div>
</nav>



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
