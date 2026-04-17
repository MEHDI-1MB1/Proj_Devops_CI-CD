<?php
global $pdo;
require_once '../config/db.php';
require_once '../includes/functions.php';

require_role('gestionnaire');
$stmtUser = $pdo->prepare("SELECT nom FROM users WHERE id = ?");
$stmtUser->execute([$_SESSION['user_id']]);
$user_name = $stmtUser->fetchColumn();

if (empty($user_name)) {
    $user_name = 'Gestionnaire';
}

 
$stats = [];
$stats['reglees'] = $pdo->query("SELECT COUNT(*) FROM reclamations WHERE statut = 'resolu'")->fetchColumn();
$stats['en_cours'] = $pdo->query("SELECT COUNT(*) FROM reclamations WHERE statut = 'en_cours'")->fetchColumn();
$stats['annulees'] = $pdo->query("SELECT COUNT(*) FROM reclamations WHERE statut = 'rejete'")->fetchColumn();

 
$sql = "
    SELECT r.*, c.nom as categorie_nom, u.nom as user_nom 
    FROM reclamations r 
    JOIN categories c ON r.categorie_id = c.id 
    JOIN users u ON r.user_id = u.id 
    ORDER BY r.date_creation DESC
    LIMIT 5
";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$reclamations = $stmt->fetchAll(PDO::FETCH_ASSOC);

 
 
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
$stmt->execute([$_SESSION['user_id']]);
$all_notifs_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

 
$filtered_notifs = [];
$unread_count = 0;

foreach($all_notifs_raw as $n) {
     
    if($n['is_read'] == 0) $unread_count++;

     
     
     
     
    $is_low_priority = (strpos($n['message'], 'Faible') !== false);

    if (!$is_low_priority) {
        $filtered_notifs[] = $n;
    }
}
 
$filtered_notifs = array_slice($filtered_notifs, 0, 6);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord - Gestionnaire</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --sidebar-bg: #051b34; --body-bg: #f3f5f9; --accent-yellow: #ffc107; --text-muted: #6c757d; }
        body { font-family: 'Poppins', sans-serif; background-color: var(--body-bg); overflow-x: hidden; }
        .sidebar { background-color: var(--sidebar-bg); min-height: 100vh; color: white; padding-top: 2rem; }
        .nav-link { color: #b0b8c4; padding: 15px 25px; transition: 0.3s; }
        .nav-link:hover { color: white; background: rgba(255,255,255,0.05); }
        .nav-link.active { color: var(--accent-yellow); background: linear-gradient(90deg, rgba(255,193,7,0.1) 0%, rgba(255,193,7,0) 100%); border-left: 4px solid var(--accent-yellow); }
        .nav-link i { width: 25px; margin-right: 10px; }
        .main-content { padding: 2rem 3rem; }
        .stat-card { background: white; border: none; border-radius: 15px; padding: 20px; display: flex; align-items: center; box-shadow: 0 4px 6px rgba(0,0,0,0.02); height: 100%; }
        .icon-box { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; margin-right: 15px; }
        .icon-blue { background: #e0f7fa; color: #00bcd4; }
        .icon-gray { background: #eceff1; color: #607d8b; }
        .icon-red { background: #ffebee; color: #ef5350; }
        .stat-num { font-size: 1.8rem; font-weight: 700; line-height: 1; color: var(--sidebar-bg); }
        .stat-desc { font-size: 0.85rem; color: var(--text-muted); }
        .card-custom { background: white; border-radius: 15px; border: none; box-shadow: 0 4px 6px rgba(0,0,0,0.02); padding: 25px; }
        .table thead th { border-bottom: 2px solid #f0f0f0; color: #aaa; font-weight: 500; font-size: 0.85rem; text-transform: uppercase; padding-bottom: 15px; }
        .table td { vertical-align: middle; padding: 15px 5px; font-weight: 500; color: var(--sidebar-bg); font-size: 0.95rem; }
        .status-text { font-weight: 600; }
        .text-blue { color: #00bcd4; }
        .text-gray { color: #bdbdbd; }
        .text-yellow { color: #fbc02d; }
        .text-red { color: #ef5350; }
         

         
        .user-profile-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 10px 15px;
            margin: 0 20px 40px 20px;
        }

        .avatar-small {
            width: 40px;
            height: 40px;
            background-color: var(--accent-yellow);
            color: var(--sidebar-bg);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            object-fit: cover;
        }
        .btn-traiter { background-color: var(--sidebar-bg); color: white; border-radius: 20px; padding: 5px 15px; font-size: 0.8rem; text-decoration: none; transition: all 0.3s; }
        .btn-traiter:hover { background-color: var(--accent-yellow); color: var(--sidebar-bg); }

         
        .notification-dropdown { width: 320px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border-radius: 15px; padding: 0; overflow: hidden; }
        .notif-header { background: var(--sidebar-bg); color: white; padding: 15px; font-weight: 600; font-size: 0.9rem; }
        .notif-item { padding: 12px 15px; border-bottom: 1px solid #f0f0f0; display: block; text-decoration: none; transition: 0.2s; position: relative; }
        .notif-item:hover { background-color: #f8f9fa; }
        .notif-item.unread { background-color: #fff9e6; }
        .notif-item.unread:before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 4px; background-color: var(--accent-yellow); }
        .notif-icon { width: 35px; height: 35px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 12px; font-size: 0.9rem; flex-shrink: 0; }
        .notif-content { flex-grow: 1; }
        .notif-title { font-size: 0.85rem; font-weight: 600; color: #333; margin-bottom: 2px; }
        .notif-time { font-size: 0.75rem; color: #999; }
        .badge-notification { position: absolute; top: 0; right: 0; transform: translate(30%, -30%); font-size: 0.65rem; padding: 4px 6px; border-radius: 50%; border: 2px solid white; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <?php include 'sidebar.php'; ?>

        <div class="col-md-9 col-lg-10 main-content">
            <div class="d-flex justify-content-between align-items-center mb-5 flex-wrap gap-3">
                <h2 class="fw-bold text-dark m-0">Bonjour, <?php echo htmlspecialchars($user_name); ?></h2>

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
                                <?php if (count($filtered_notifs) > 0): ?>
                                    <?php foreach ($filtered_notifs as $notif): ?>
                                        <?php
                                         
                                        $icon = 'fa-info'; $bg = 'bg-light text-muted';

                                        if ($notif['type'] == 'urgence') {
                                            $icon = 'fa-exclamation-triangle';
                                            $bg = 'bg-danger bg-opacity-10 text-danger';
                                        }
                                        elseif ($notif['type'] == 'reclamation_commentaire') {
                                            $icon = 'fa-comment-alt';
                                            $bg = 'bg-success bg-opacity-10 text-success';
                                        }
                                        elseif ($notif['type'] == 'reclamation_new') {
                                            $icon = 'fa-file-alt';
                                            $bg = 'bg-primary bg-opacity-10 text-primary';
                                        }
                                        ?>
                                        <a href="<?php echo htmlspecialchars($notif['lien']); ?>" class="notif-item d-flex align-items-start <?php echo ($notif['is_read'] == 0) ? 'unread' : ''; ?>">
                                            <div class="notif-icon <?php echo $bg; ?>">
                                                <i class="fas <?php echo $icon; ?>"></i>
                                            </div>
                                            <div class="notif-content">
                                                <div class="notif-title"><?php echo htmlspecialchars($notif['message']); ?></div>
                                                <div class="notif-time"><?php echo date('d/m H:i', strtotime($notif['created_at'])); ?></div>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="p-4 text-center text-muted small">
                                        <i class="far fa-bell-slash mb-2"></i><br>Aucune notification importante
                                    </div>
                                <?php endif; ?>
                            </div>
                            <a href="notifications.php" class="d-block text-center py-2 small text-muted bg-light border-top text-decoration-none">Voir tout l'historique</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="icon-box icon-blue"><i class="fas fa-check-circle"></i></div>
                        <div>
                            <div class="stat-num"><?php echo $stats['reglees']; ?></div>
                            <div class="stat-desc">Total Réglées</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="icon-box icon-gray"><i class="fas fa-spinner"></i></div>
                        <div>
                            <div class="stat-num"><?php echo $stats['en_cours']; ?></div>
                            <div class="stat-desc">Total En cours</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="icon-box icon-red"><i class="fas fa-times-circle"></i></div>
                        <div>
                            <div class="stat-num"><?php echo $stats['annulees']; ?></div>
                            <div class="stat-desc">Total Annulées</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-bold m-0 text-dark">Réclamations récentes</h5>
                        <a href="reclamations.php" class="text-muted text-decoration-underline small">voir tout</a>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-borderless table-hover align-middle">
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>Réclamant</th>
                                <th>Titre</th>
                                <th>Lieu</th>
                                <th>Urgence</th>
                                <th>Date</th>
                                <th>Statut</th>
                                <th>Action</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (count($reclamations) > 0): ?>
                                <?php foreach ($reclamations as $rec): ?>
                                    <tr>
                                        <td class="text-muted">#<?php echo $rec['id']; ?></td>
                                        <td class="fw-bold"><?php echo htmlspecialchars($rec['user_nom']); ?></td>
                                        <td><?php echo htmlspecialchars($rec['objet']); ?></td>

                                        <td class="text-muted small">
                                            <i class="fas fa-map-marker-alt me-1"></i>
                                            <?php echo htmlspecialchars($rec['lieu'] ?? '-'); ?>
                                        </td>

                                        <td>
                                            <?php
                                            $urg = $rec['urgence'] ?? 'Faible';
                                            $badgeClass = 'bg-secondary bg-opacity-10 text-secondary';
                                            if ($urg === 'Moyenne') $badgeClass = 'bg-warning bg-opacity-25 text-warning-emphasis';
                                            if ($urg === 'Haute') $badgeClass = 'bg-danger bg-opacity-10 text-danger';
                                            ?>
                                            <span class="badge rounded-pill <?php echo $badgeClass; ?>">
                                                <?php echo htmlspecialchars($urg); ?>
                                            </span>
                                        </td>

                                        <td class="text-muted small"><?php echo date('d M H:i', strtotime($rec['date_creation'])); ?></td>

                                        <?php
                                        $statusClass = 'text-gray';
                                        $statusText = $rec['statut'];
                                        if ($rec['statut'] == 'resolu') { $statusClass = 'text-blue'; $statusText = 'Réglée'; }
                                        elseif ($rec['statut'] == 'en_cours') { $statusClass = 'text-gray'; $statusText = 'En cours'; }
                                        elseif ($rec['statut'] == 'en_attente') { $statusClass = 'text-yellow'; $statusText = 'En attente'; }
                                        elseif ($rec['statut'] == 'rejete') { $statusClass = 'text-red'; $statusText = 'Annulée'; }
                                        ?>
                                        <td class="status-text <?php echo $statusClass; ?>"><?php echo ucfirst($statusText); ?></td>

                                        <td>
                                            <a href="edit.php?id=<?php echo $rec['id']; ?>" class="btn-traiter">Traiter</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="8" class="text-center text-muted">Aucune réclamation récente.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function markNotificationsRead() {
        fetch('../mark_read.php')
            .then(response => {
                if (response.ok) {
                    const badge = document.getElementById('notifBadge');
                    if (badge) badge.remove();

                    const bellIcon = document.querySelector('#notifDropdown i');
                    if (bellIcon) {
                        bellIcon.classList.remove('text-danger'); 
                        bellIcon.classList.remove('text-warning');
                        bellIcon.classList.add('text-secondary');
                    }

                    
                    document.querySelectorAll('.notif-item.unread').forEach(el => {
                        el.classList.remove('unread');
                    });
                }
            });
    }
</script>
</body>
</html>