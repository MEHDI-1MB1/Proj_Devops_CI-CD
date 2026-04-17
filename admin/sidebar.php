<?php

$current_page = basename($_SERVER['PHP_SELF']);


$sidebar_name = isset($user['nom']) ? $user['nom'] : ($_SESSION['user_nom'] ?? ($_SESSION['user_name'] ?? 'Directeur'));
$sidebar_avatar = isset($user['avatar']) ? $user['avatar'] : ($_SESSION['user_avatar'] ?? '');
if (empty($sidebar_name)) $sidebar_name = 'Directeur';


function render_avatar($avatar, $name) {
    if (!empty($avatar)) {
        return '<img src="../uploads/avatars/'.htmlspecialchars($avatar).'" class="avatar-small me-3" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; aspect-ratio: 1/1;" alt="Avatar">';
    } else {
        $vals = explode(' ', $name);
        $initials = isset($vals[1]) ? strtoupper(substr($vals[0], 0, 1) . substr($vals[1], 0, 1)) : strtoupper(substr($vals[0], 0, 2));
        return '<div class="avatar-small me-3" style="width: 40px; height: 40px; background-color: var(--accent-yellow); color: var(--sidebar-bg); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700;">'.$initials.'</div>';
    }
}
?>
<style>
     
    .sidebar { background-color: #051b34; min-height: 100vh; color: white; padding-top: 2rem; }
    .nav-link { 
        color: #b0b8c4 !important; 
        padding: 15px 25px !important; 
        font-size: 1rem !important;
        font-weight: 500 !important;
        transition: 0.3s; 
        border-left: 4px solid transparent; 
        display: flex;
        align-items: center;
    }
    .nav-link:hover { 
        color: white !important; 
        background: rgba(255,255,255,0.05); 
    }
    .nav-link.active { 
        color: #ffc107 !important; 
        background: linear-gradient(90deg, rgba(255,193,7,0.1) 0%, rgba(255,193,7,0) 100%); 
        border-left: 4px solid #ffc107; 
    }
    .nav-link i { 
        width: 25px; 
        margin-right: 10px; 
        text-align: center;
    }
</style>

<div class="d-md-none w-100 bg-white shadow-sm p-3 mb-3 d-flex justify-content-between align-items-center">
    <img src="../assets/img/Rec_Blanc.png" alt="Logo" style="height: 40px; filter: invert(1) brightness(0.2);">
    <button class="btn btn-light" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminSidebar">
        <i class="fas fa-bars fa-lg text-dark"></i>
    </button>
</div>

<div class="offcanvas offcanvas-start" tabindex="-1" id="adminSidebar" style="background-color: var(--sidebar-bg); color: white;">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title text-white">Menu Admin</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
        <ul class="nav flex-column">
            <li class="nav-item"><a href="dashboard.php" class="nav-link <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>"><i class="fas fa-chart-simple"></i> Vue d'ensemble</a></li>
            <li class="nav-item"><a href="users.php" class="nav-link <?php echo ($current_page == 'users.php') ? 'active' : ''; ?>"><i class="fas fa-users"></i> Utilisateurs</a></li>
            <li class="nav-item"><a href="heatmap.php" class="nav-link <?php echo ($current_page == 'heatmap.php') ? 'active' : ''; ?>"><i class="fas fa-fire-alt"></i> Carte Thermique</a></li>
            <li class="nav-item"><a href="categories.php" class="nav-link <?php echo ($current_page == 'categories.php') ? 'active' : ''; ?>"><i class="fas fa-tags"></i> Catégories</a></li>
            <li class="nav-item"><a href="lieux.php" class="nav-link <?php echo ($current_page == 'lieux.php') ? 'active' : ''; ?>"><i class="fas fa-map-marker-alt"></i> Lieux</a></li>
            <li class="nav-item"><a href="faqs.php" class="nav-link <?php echo ($current_page == 'faqs.php') ? 'active' : ''; ?>"><i class="fas fa-circle-question"></i> FAQs</a></li>
            <li class="nav-item"><a href="profile.php" class="nav-link <?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>"><i class="fas fa-user-circle"></i> Profil</a></li>
            <li class="nav-item mt-4"><a href="../logout.php" class="nav-link text-danger fw-bold"><i class="fas fa-power-off"></i> Se déconnecter</a></li>
        </ul>
    </div>
</div>

<div class="col-md-3 col-lg-2 sidebar d-none d-md-block px-0">
    <div style="margin: 0 20px 25px 20px;">
        <img src="../assets/img/Rec_Blanc.png" alt="Logo Syndic" style="width: 137px; height: auto;">
    </div>
    <div class="user-profile-card d-flex align-items-center" style="margin: 0 20px 40px 20px; padding: 10px 15px; background: rgba(255, 255, 255, 0.1); border-radius: 12px;">
        <?php echo render_avatar($sidebar_avatar, $sidebar_name); ?>
        <div>
            <div class="fw-bold lh-1"><?php echo htmlspecialchars($sidebar_name); ?></div>
            <small class="text-white-50" style="font-size: 12px;">Administrateur</small>
        </div>
    </div>

    <ul class="nav flex-column">
        <li class="nav-item">
            <a href="dashboard.php" class="nav-link <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                <i class="fas fa-chart-simple"></i> Vue d'ensemble
            </a>
        </li>
        <li class="nav-item">
            <a href="users.php" class="nav-link <?php echo ($current_page == 'users.php') ? 'active' : ''; ?>">
                <i class="fas fa-users"></i> Utilisateurs
            </a>
        </li>
        <li class="nav-item">
            <a href="heatmap.php" class="nav-link <?php echo ($current_page == 'heatmap.php') ? 'active' : ''; ?>">
                <i class="fas fa-fire-alt"></i> Carte Thermique
            </a>
        </li>
        <li class="nav-item">
            <a href="categories.php" class="nav-link <?php echo ($current_page == 'categories.php') ? 'active' : ''; ?>">
                <i class="fas fa-tags"></i> Catégories
            </a>
        </li>
        <li class="nav-item">
            <a href="lieux.php" class="nav-link <?php echo ($current_page == 'lieux.php') ? 'active' : ''; ?>">
                <i class="fas fa-map-marker-alt"></i> Lieux
            </a>
        </li>
        <li class="nav-item">
            <a href="faqs.php" class="nav-link <?php echo ($current_page == 'faqs.php') ? 'active' : ''; ?>">
                <i class="fas fa-circle-question"></i> FAQs
            </a>
        </li>
        <li class="nav-item">
            <a href="profile.php" class="nav-link <?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>">
                <i class="fas fa-user-circle"></i> Profil
            </a>
        </li>
    </ul>

    <div class="mt-5 pt-5 px-4">
        <a href="../logout.php" class="text-danger text-decoration-none small fw-bold">
            <i class="fas fa-power-off me-2"></i> Se déconnecter
        </a>
    </div>
</div>