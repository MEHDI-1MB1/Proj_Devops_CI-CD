<?php
global $pdo;
require_once '../config/db.php';
require_once '../includes/functions.php';

require_role('admin');

$user_name = $_SESSION['user_name'] ?? 'Administrateur';
$user_id = $_SESSION['user_id'];

 
 
$stmtNotif = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$stmtNotif->execute([$user_id]);
$notifications = $stmtNotif->fetchAll(PDO::FETCH_ASSOC);

 
$unread_count = 0;
foreach ($notifications as $n) {
    if ($n['is_read'] == 0) $unread_count++;
}

 
$stats = [];
$stats['total_reclamations'] = $pdo->query("SELECT COUNT(*) FROM reclamations")->fetchColumn();
$stats['total_users'] = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$stats['resolues'] = $pdo->query("SELECT COUNT(*) FROM reclamations WHERE statut = 'resolu'")->fetchColumn();

 
 
$status_counts = [
        'en_attente' => 0, 'en_cours' => 0, 'resolu' => 0, 'rejete' => 0
];
$stmt = $pdo->query("SELECT statut, COUNT(*) as count FROM reclamations GROUP BY statut");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $status_counts[$row['statut']] = $row['count'];
}

 
$categories_labels = [];
$categories_data = [];
$stmt = $pdo->query("
    SELECT c.nom, COUNT(r.id) as count 
    FROM categories c 
    LEFT JOIN reclamations r ON c.id = r.categorie_id 
    GROUP BY c.id 
    ORDER BY count DESC 
    LIMIT 5
");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $categories_labels[] = $row['nom'];
    $categories_data[] = $row['count'];
}

 
$stmt = $pdo->query("SELECT * FROM users ORDER BY id DESC LIMIT 5");
$recent_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --sidebar-bg: #051b34; --body-bg: #f8f9fa; --accent-yellow: #ffc107; }
        body { font-family: 'Poppins', sans-serif; background-color: var(--body-bg); overflow-x: hidden; }

         
        .sidebar { background-color: var(--sidebar-bg); min-height: 100vh; color: white; padding-top: 2rem; }
        .nav-link { color: #b0b8c4; padding: 15px 25px; transition: 0.3s; }
        .nav-link:hover { color: white; background: rgba(255,255,255,0.05); }
        .nav-link.active { color: var(--accent-yellow); background: linear-gradient(90deg, rgba(255,193,7,0.1) 0%, rgba(255,193,7,0) 100%); border-left: 4px solid var(--accent-yellow); }
        .nav-link i { width: 25px; margin-right: 10px; }
        .main-content { padding: 2rem 3rem; }

         
        .stat-card {
            background: white; border: none; border-radius: 16px; padding: 25px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03); height: 100%; transition: transform 0.2s;
        }
        .stat-card:hover { transform: translateY(-5px); }
        .icon-box {
            width: 55px; height: 55px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem; margin-right: 20px;
        }
        .icon-purple { background: #f3e5f5; color: #ab47bc; }
        .icon-blue { background: #e3f2fd; color: #1e88e5; }
        .icon-green { background: #e8f5e9; color: #43a047; }

        .stat-num { font-size: 2rem; font-weight: 700; color: #051b34; line-height: 1; margin-bottom: 5px; }
        .stat-desc { font-size: 0.9rem; color: #8898aa; font-weight: 500; }

        .chart-container {
            background: white; border-radius: 16px; padding: 25px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03); height: 100%;
        }
        .chart-title { font-size: 1rem; font-weight: 700; color: #344767; margin-bottom: 20px; }

         
        .table-custom { width: 100%; border-collapse: separate; border-spacing: 0; }
        .table-custom thead th { border-bottom: 1px solid #f0f0f0; padding: 15px; color: #8898aa; font-weight: 600; font-size: 0.8rem; text-transform: uppercase; }
        .table-custom td { padding: 15px; vertical-align: middle; border-bottom: 1px solid #f9f9f9; }
        .role-badge { padding: 5px 12px; border-radius: 30px; font-size: 0.75rem; font-weight: 600; }
        .role-admin { background-color: #e3f2fd; color: #1565c0; }
        .role-gestionnaire { background-color: #fff8e1; color: #f57f17; }
        .role-reclamant { background-color: #e8f5e9; color: #2e7d32; }
         
        .notification-dropdown { width: 320px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border-radius: 15px; padding: 0; overflow: hidden; }
        .notif-header { background: var(--sidebar-bg); color: white; padding: 15px; font-weight: 600; font-size: 0.9rem; }
        .notif-item { padding: 12px 15px; border-bottom: 1px solid #f0f0f0; display: flex; align-items: start; text-decoration: none; transition: 0.2s; position: relative; color: #333; }
        .notif-item:hover { background-color: #f8f9fa; color: #333; }
        .notif-item.unread { background-color: #fff9e6; }
        .notif-item.unread:before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 4px; background-color: var(--accent-yellow); }
        .notif-icon-circle { width: 35px; height: 35px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 12px; font-size: 0.9rem; flex-shrink: 0; }
        .badge-notification { position: absolute; top: -5px; right: -5px; font-size: 0.65rem; padding: 4px 6px; border-radius: 50%; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <?php include 'sidebar.php'; ?>

        <div class="col-md-9 col-lg-10 main-content">

            <div class="d-flex justify-content-between align-items-center mb-5">
                <div>
                    <h2 class="fw-bold" style="color: #051b34;">Tableau de Bord</h2>
                    <p class="text-muted m-0">Aperçu global de la gestion.</p>
                </div>

                <div class="d-flex align-items-center gap-3">
                    <div class="dropdown">
                        <button class="btn btn-white bg-white shadow-sm rounded-circle p-2 position-relative" id="notifDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="width: 45px; height: 45px;" onclick="markNotificationsRead()">
                            <i class="fas fa-bell <?php echo ($unread_count > 0) ? 'text-warning' : 'text-secondary'; ?>"></i>
                            <?php if ($unread_count > 0): ?>
                                <span class="badge bg-danger badge-notification" id="notifBadge"><?php echo $unread_count; ?></span>
                            <?php endif; ?>
                        </button>

                        <div class="dropdown-menu dropdown-menu-end notification-dropdown" aria-labelledby="notifDropdown">
                            <div class="notif-header d-flex justify-content-between align-items-center">
                                <span>Notifications</span>
                                <small class="opacity-75"><?php echo $unread_count; ?> nouvelles</small>
                            </div>
                            <div style="max-height: 300px; overflow-y: auto;">
                                <?php if (count($notifications) > 0): ?>
                                    <?php foreach ($notifications as $notif): ?>
                                        <a href="users.php" class="notif-item <?php echo ($notif['is_read'] == 0) ? 'unread' : ''; ?>">
                                            <div class="notif-icon-circle bg-primary bg-opacity-10 text-primary">
                                                <i class="fas fa-user-plus"></i>
                                            </div>
                                            <div>
                                                <div style="font-size: 0.85rem; font-weight: 600;"><?php echo htmlspecialchars($notif['message']); ?></div>
                                                <div style="font-size: 0.75rem; color: #999;"><?php echo date('d/m H:i', strtotime($notif['created_at'])); ?></div>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="p-4 text-center text-muted small">Aucune notification</div>
                                <?php endif; ?>
                            </div>
                            <a href="notifications.php" class="d-block text-center py-2 small text-muted bg-light border-top text-decoration-none">Voir tout l'historique</a>
                        </div>
                    </div>

                    <div class="text-end text-muted small ms-2">
                        <i class="far fa-calendar-alt me-1"></i> <?php echo date('d F Y'); ?>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-5">
                <div class="col-md-4">
                    <div class="stat-card d-flex align-items-center">
                        <div class="icon-box icon-purple"><i class="fas fa-users"></i></div>
                        <div>
                            <div class="stat-num"><?php echo $stats['total_users']; ?></div>
                            <div class="stat-desc">Utilisateurs inscrits</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card d-flex align-items-center">
                        <div class="icon-box icon-blue"><i class="fas fa-file-signature"></i></div>
                        <div>
                            <div class="stat-num"><?php echo $stats['total_reclamations']; ?></div>
                            <div class="stat-desc">Total Réclamations</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card d-flex align-items-center">
                        <div class="icon-box icon-green"><i class="fas fa-check-circle"></i></div>
                        <div>
                            <div class="stat-num"><?php echo $stats['resolues']; ?></div>
                            <div class="stat-desc">Réclamations Résolues</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-5">
                <div class="col-md-5">
                    <div class="chart-container">
                        <div class="chart-title">Répartition par Statut</div>
                        <div style="height: 250px; position: relative;">
                            <canvas id="statusChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-md-7">
                    <div class="chart-container">
                        <div class="chart-title">Réclamations par Catégorie</div>
                        <div style="height: 250px; position: relative;">
                            <canvas id="categoryChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="chart-container" style="padding: 0; overflow: hidden;">
                        <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold m-0 text-dark">Derniers Inscrits</h6>
                            <a href="users.php" class="btn btn-sm btn-outline-primary rounded-pill px-3">Tout voir</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table-custom">
                                <thead>
                                <tr>
                                    <th class="ps-4">Utilisateur</th>
                                    <th>Email</th>
                                    <th>Rôle</th>
                                    <th>Date Inscription</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($recent_users as $u): ?>
                                    <tr>
                                        <td class="ps-4 fw-bold text-dark"><?php echo htmlspecialchars($u['nom']); ?></td>
                                        <td class="text-muted"><?php echo htmlspecialchars($u['email']); ?></td>
                                        <td>
                                            <span class="role-badge role-<?php echo $u['role']; ?>">
                                                <?php echo ucfirst($u['role']); ?>
                                            </span>
                                        </td>
                                        <td class="text-muted small"><?php echo date('d/m/Y', strtotime($u['date_creation'] ?? 'now')); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>


    // --- JS pour Marquer comme lu ---
    function markNotificationsRead() {
        // Le fetch vers '../mark_read.php' car dashboard est souvent dans un dossier /admin/
        // Si dashboard.php est à la racine, mettez juste 'mark_read.php'
        fetch('../mark_read.php')
            .then(response => {
                if (response.ok) {
                    const badge = document.getElementById('notifBadge');
                    if (badge) badge.remove();

                    const bellIcon = document.querySelector('#notifDropdown i');
                    if(bellIcon) {
                        bellIcon.classList.remove('text-warning');
                        bellIcon.classList.add('text-secondary');
                    }

                    document.querySelectorAll('.notif-item.unread').forEach(el => {
                        el.classList.remove('unread');
                    });
                }
            });
    }


    // --- GRAPHIQUE 1 : STATUT (Doughnut) ---
    const ctxStatus = document.getElementById('statusChart').getContext('2d');
    new Chart(ctxStatus, {
        type: 'doughnut',
        data: {
            labels: ['En attente', 'En cours', 'Résolu', 'Annulé'],
            datasets: [{
                data: [
                    <?php echo $status_counts['en_attente']; ?>,
                    <?php echo $status_counts['en_cours']; ?>,
                    <?php echo $status_counts['resolu']; ?>,
                    <?php echo $status_counts['rejete']; ?>
                ],
                backgroundColor: [
                    '#ffc107', // En attente : Votre Jaune Accent
                    '#4e73df', // En cours : Bleu Roi
                    '#051b34', // Résolu : Votre Bleu Foncé (Marque la fin du processus)
                    '#858796'  // Rejeté : Gris neutre (Pour ne pas attirer l'attention inutilement)
                ],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'right', labels: { usePointStyle: true, font: { family: 'Poppins' } } }
            },
            cutout: '70%' // Donne l'effet anneau fin
        }
    });

    // --- GRAPHIQUE 2 : CATEGORIES (Bar) ---
    const ctxCat = document.getElementById('categoryChart').getContext('2d');
    new Chart(ctxCat, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($categories_labels); ?>, // PHP Array to JS Array
            datasets: [{
                label: 'Nombre de réclamations',
                data: <?php echo json_encode($categories_data); ?>,
                backgroundColor: '#051b34', // Bleu foncé sidebar
                borderRadius: 5,
                barThickness: 30
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false } // Pas besoin de légende pour une seule série
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { borderDash: [2, 4], color: '#f0f0f0' },
                    ticks: { precision: 0 } // Pas de décimales (pas de demi-réclamation)
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });
</script>

</body>
</html>

















