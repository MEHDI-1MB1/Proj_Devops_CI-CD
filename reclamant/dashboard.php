<?php
global $pdo;
require_once '../config/db.php';
require_once '../includes/functions.php';

require_role('reclamant');

$user_id = $_SESSION['user_id'];

 
$stmtUser = $pdo->prepare("SELECT nom FROM users WHERE id = ?");
$stmtUser->execute([$user_id]);
$user_name = $stmtUser->fetchColumn();

 
if (empty($user_name)) {
    $user_name = 'Utilisateur';
}
 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_avis'])) {
    $note = intval($_POST['rating']);
    $commentaire = trim($_POST['avis_comment']);

    if ($note > 0) {
        $stmtAvis = $pdo->prepare("INSERT INTO avis (user_id, note, commentaire) VALUES (?, ?, ?)");
        $stmtAvis->execute([$user_id, $note, $commentaire]);
         
        header("Location: dashboard.php?avis_success=1");
        exit();
    }
}
 
$stats = ['reglees' => 0, 'en_cours' => 0, 'annulees' => 0];

$stmt = $pdo->prepare("SELECT COUNT(*) FROM reclamations WHERE user_id = ? AND statut = 'resolu'");
$stmt->execute([$user_id]);
$stats['reglees'] = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM reclamations WHERE user_id = ? AND statut IN ('en_cours', 'en_attente')");
$stmt->execute([$user_id]);
$stats['en_cours'] = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM reclamations WHERE user_id = ? AND statut = 'rejete'");
$stmt->execute([$user_id]);
$stats['annulees'] = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT r.*, c.nom as categorie_nom FROM reclamations r JOIN categories c ON r.categorie_id = c.id WHERE r.user_id = ? ORDER BY r.date_creation DESC LIMIT 5");
$stmt->execute([$user_id]);
$reclamations = $stmt->fetchAll(PDO::FETCH_ASSOC);

 
 
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt->execute([$user_id]);
$all_notifs = $stmt->fetchAll(PDO::FETCH_ASSOC);

 
$unread_count = 0;
foreach($all_notifs as $n) {
    if($n['is_read'] == 0) $unread_count++;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord - ReCité</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root { --sidebar-bg: #051b34; --body-bg: #f3f5f9; --accent-yellow: #ffc107; --text-muted: #6c757d; }
        body { font-family: 'Poppins', sans-serif; background-color: var(--body-bg); overflow-x: hidden; }
        .sidebar { background-color: var(--sidebar-bg); min-height: 100vh; color: white; padding-top: 2rem; }
        .user-profile-card { background: rgba(255, 255, 255, 0.1); border-radius: 12px; padding: 10px 15px; margin: 0 20px 40px 20px; }
        .avatar { width: 40px; height: 40px; background-color: var(--accent-yellow); color: var(--sidebar-bg); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; }
        .nav-link { color: #b0b8c4; padding: 15px 25px; transition: 0.3s; border-left: 4px solid transparent; }
        .nav-link:hover { color: white; background: rgba(255,255,255,0.05); }
        .nav-link.active { color: var(--accent-yellow); background: linear-gradient(90deg, rgba(255,193,7,0.1) 0%, rgba(255,193,7,0) 100%); border-left: 4px solid var(--accent-yellow); }
        .nav-link i { width: 25px; margin-right: 10px; }
        .main-content { padding: 2rem 3rem; }
        .search-input { border-radius: 50px; border: none; padding-left: 15px; }
        .input-group-text { border-radius: 50px 0 0 50px; border: none; background: white; }
        .btn-reclamer { background-color: var(--accent-yellow); border: none; border-radius: 50px; padding: 10px 25px; font-weight: 600; color: var(--sidebar-bg); }
        .btn-reclamer:hover { background-color: #e0a800; color: var(--sidebar-bg); }
        .stat-card { background: white; border: none; border-radius: 15px; padding: 20px; display: flex; align-items: center; box-shadow: 0 4px 6px rgba(0,0,0,0.02); height: 100%; }
        .icon-box { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; margin-right: 15px; }
        .icon-blue { background: #e0f7fa; color: #00bcd4; }
        .icon-gray { background: #eceff1; color: #607d8b; }
        .icon-red { background: #ffebee; color: #ef5350; }
        .stat-num { font-size: 1.8rem; font-weight: 700; line-height: 1; color: var(--sidebar-bg); }
        .stat-desc { font-size: 0.85rem; color: var(--text-muted); }
        .card-custom { background: white; border-radius: 15px; border: none; box-shadow: 0 4px 6px rgba(0,0,0,0.02); padding: 25px; }
        .table thead th { border-bottom: 2px solid #f0f0f0; color: #aaa; font-weight: 500; font-size: 0.9rem; padding-bottom: 15px; }
        .table td { vertical-align: middle; padding: 15px 5px; font-weight: 500; color: var(--sidebar-bg); }
        .status-text { font-weight: 600; }
        .text-blue { color: #00bcd4; }
        .text-gray { color: #bdbdbd; }
        .text-yellow { color: #fbc02d; }
        .text-red { color: #ef5350; }
        .announcement-item { background: #f8f9fa; border-radius: 10px; padding: 15px; margin-bottom: 15px; border-left: 4px solid #dfe6ed; }
        .announcement-item:hover {
            background: #ffffff;
            border-left-color: var(--accent-yellow);  
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transform: translateX(5px);  
        }
        .emergency-card { background-color: var(--sidebar-bg); color: white; border-radius: 15px; text-align: center; padding: 30px 20px; }
        .emergency-card:hover {
            transform: scale(1.02);  
            box-shadow: 0 10px 30px rgba(5, 27, 52, 0.3);
        }
        .btn-gardien { background-color: var(--accent-yellow); color: var(--sidebar-bg); font-weight: 700; border-radius: 50px; width: 100%; padding: 10px; border: none; }
        .btn-gardien:hover { background-color: #e0a800; }
        .modal-pro-header { background-color: #051B34; color: #FFFFFF; border-bottom: none; padding: 20px 25px; }
        .btn-pro-primary { background-color: #FFC107; color: #051B34; border: 1px solid #FFC107; font-weight: 700; padding: 12px; transition: all 0.3s ease; }
        .btn-pro-primary:hover { background-color: #e0a800; border-color: #e0a800; color: #051B34; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .btn-pro-secondary { background-color: transparent; color: #051B34; border: 2px solid #051B34; font-weight: 600; padding: 12px; transition: all 0.3s ease; }
        .btn-whatsapp-effect:hover { background-color: #25D366 !important; border-color: #25D366 !important; color: #FFFFFF !important; }
        .modal-pro-footer { background-color: #F8F9FA; border-top: 1px solid #e9ecef; font-size: 0.85rem; color: #051B34; }
        .emergency-badge { background-color: #ffe5e5; color: #cc0000; padding: 2px 6px; border-radius: 4px; font-weight: bold; margin-left: 5px; }

         
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
         
        .rating { display: flex; flex-direction: row-reverse; justify-content: center; gap: 10px; }
        .rating input { display: none; }
        .rating label { cursor: pointer; color: #ddd; font-size: 2rem; transition: color 0.2s; }
        .rating input:checked ~ label,
        .rating label:hover,
        .rating label:hover ~ label { color: #ffc107; }
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
                                <?php if (count($all_notifs) > 0): ?>
                                    <?php foreach ($all_notifs as $notif): ?>
                                        <?php
                                         
                                        $icon = 'fa-info'; $bg = 'bg-light text-muted';
                                        if ($notif['type'] == 'reclamation_statut') { $icon = 'fa-exchange-alt'; $bg = 'bg-primary bg-opacity-10 text-primary'; }
                                        elseif ($notif['type'] == 'reclamation_commentaire') { $icon = 'fa-comment-alt'; $bg = 'bg-success bg-opacity-10 text-success'; }
                                        elseif ($notif['type'] == 'annonce') { $icon = 'fa-bullhorn'; $bg = 'bg-warning bg-opacity-10 text-warning'; }
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
                                        <i class="far fa-bell-slash mb-2"></i><br>Aucune notification
                                    </div>
                                <?php endif; ?>
                            </div>
                            <a href="notifications.php" class="d-block text-center py-2 small text-muted bg-light border-top text-decoration-none">Voir tout l'historique</a>
                        </div>
                    </div>
                    <a href="create.php" class="btn btn-reclamer shadow-sm text-decoration-none">
                        + Réclamer
                    </a>
                </div>
            </div>
            <?php if (isset($_GET['avis_success'])): ?>
                <div class="alert alert-info border-0 shadow-sm rounded-3 mb-4 fw-bold alert-dismissible fade show" role="alert">
                    <i class="fas fa-heart me-2"></i> Merci pour votre avis !
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['msg']) && $_GET['msg'] == 'created'): ?>
                <div class="alert alert-success border-0 shadow-sm rounded-3 mb-4 fw-bold alert-dismissible fade show" role="alert"">
                    <i class="fas fa-check-circle me-2"></i> Réclamation envoyée avec succès !
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>

            <?php endif; ?>

            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="icon-box icon-blue"><i class="fas fa-file-circle-check"></i></div>
                        <div>
                            <div class="stat-num"><?php echo $stats['reglees']; ?></div>
                            <div class="stat-desc">Réclamations réglées</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="icon-box icon-gray"><i class="fas fa-clock"></i></div>
                        <div>
                            <div class="stat-num"><?php echo $stats['en_cours']; ?></div>
                            <div class="stat-desc">Réclamations en cours</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="icon-box icon-red"><i class="fas fa-ban"></i></div>
                        <div>
                            <div class="stat-num"><?php echo $stats['annulees']; ?></div>
                            <div class="stat-desc">Réclamations annulées</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-xl-8">
                    <div class="card-custom h-100">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="fw-bold m-0 text-dark">Réclamations récentes</h5>
                            <a href="my_reclamations.php" class="text-muted text-decoration-underline small">voir tout</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-borderless table-hover">
                                <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Titre</th>
                                    <th>Date</th>
                                    <th>Statut</th>
                                    <th>Categorie</th>
                                    <th>Action</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php if (count($reclamations) > 0): ?>
                                    <?php foreach ($reclamations as $rec): ?>
                                        <tr>
                                            <td>#<?php echo $rec['id']; ?></td>
                                            <td><?php echo htmlspecialchars($rec['objet']); ?></td>
                                            <td class="text-muted"><?php echo date('d M', strtotime($rec['date_creation'])); ?></td>
                                            <?php
                                            $statusClass = 'text-gray';
                                            $statusText = $rec['statut'];
                                            if ($rec['statut'] == 'resolu') { $statusClass = 'text-blue'; $statusText = 'Réglée'; }
                                            elseif ($rec['statut'] == 'en_cours') { $statusClass = 'text-gray'; $statusText = 'En cours'; }
                                            elseif ($rec['statut'] == 'en_attente') { $statusClass = 'text-yellow'; $statusText = 'En attente'; }
                                            elseif ($rec['statut'] == 'rejete') { $statusClass = 'text-red'; $statusText = 'Annulée'; }
                                            ?>
                                            <td class="status-text <?php echo $statusClass; ?>"><?php echo ucfirst($statusText); ?></td>
                                            <td class="small fw-bold text-uppercase"><?php echo htmlspecialchars($rec['categorie_nom']); ?></td>
                                            <td>
                                                <a href="view.php?id=<?php echo $rec['id']; ?>" class="text-muted"><i class="fas fa-eye"></i></a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">Aucune réclamation récente.</td>
                                    </tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4">
                    <div class="card-custom mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold m-0"><i class="fas fa-bullhorn me-2"></i> Annonces </h6>
                            <a href="announcements.php" class="text-muted text-decoration-underline small">voir tout</a>
                        </div>

                        <?php
                        $stmtAnn = $pdo->query("SELECT * FROM annonces ORDER BY date_evenement DESC LIMIT 2");
                        while($a = $stmtAnn->fetch(PDO::FETCH_ASSOC)):
                            ?>
                            <div class="announcement-item">
                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($a['titre']); ?></div>
                                <small class="text-muted d-block mb-1">Le <?php echo date('d/m/Y', strtotime($a['date_evenement'])); ?></small>
                            </div>
                        <?php endwhile; ?>
                    </div>

                    <div class="emergency-card shadow">
                        <div class="mb-3">
                            <i class="fas fa-headset fa-3x" style="color: var(--accent-yellow);"></i>
                        </div>
                        <h4 class="fw-bold">Urgence technique</h4>
                        <p class="small text-white-50 mb-4">Disponible 24h/7j</p>
                        <button class="btn btn-gardien" data-bs-toggle="modal" data-bs-target="#modalGardien">Appeler le Gardien</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalGardien" tabindex="-1" aria-labelledby="modalGardienLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header modal-pro-header">
                <h5 class="modal-title d-flex align-items-center gap-2">Assistance Technique</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-4">
                <h4 class="fw-bold" style="color: #051B34;">Ahmed</h4>
                <p class="mb-4" style="color: #6c757d; font-size: 0.9rem;">Permanence technique & Sécurité</p>
                <div class="d-grid gap-3">
                    <a href="tel:+212600000000" class="btn btn-pro-primary">Appeler Maintenant</a>
                    <a href="https://wa.me/212600000000" class="btn btn-pro-secondary btn-whatsapp-effect">Contacter sur WhatsApp</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="avisModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header" style="background-color: var(--sidebar-bg); color: white;">
                <h5 class="modal-title fw-bold">Votre avis nous intéresse !</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <p class="text-muted mb-4">Comment évaluez-vous votre expérience de dépôt de réclamation ?</p>

                <form method="POST" action="dashboard.php">
                    <input type="hidden" name="submit_avis" value="1">

                    <div class="rating mb-4">
                        <input type="radio" name="rating" id="star5" value="5"><label for="star5" title="Excellent"><i class="fas fa-star"></i></label>
                        <input type="radio" name="rating" id="star4" value="4"><label for="star4" title="Très bien"><i class="fas fa-star"></i></label>
                        <input type="radio" name="rating" id="star3" value="3"><label for="star3" title="Bien"><i class="fas fa-star"></i></label>
                        <input type="radio" name="rating" id="star2" value="2"><label for="star2" title="Moyen"><i class="fas fa-star"></i></label>
                        <input type="radio" name="rating" id="star1" value="1"><label for="star1" title="Mauvais"><i class="fas fa-star"></i></label>
                    </div>

                    <div class="mb-3">
                        <textarea name="avis_comment" class="form-control" rows="3" placeholder="Un commentaire ou une suggestion ? (Optionnel)"></textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-light text-muted" data-bs-dismiss="modal">Plus tard</button>
                        <button type="submit" class="btn" style="background-color: var(--accent-yellow); color: var(--sidebar-bg); font-weight: bold;">Envoyer mon avis</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    
    function markNotificationsRead() {
        const badge = document.getElementById('notifBadge');
        const icon = document.querySelector('#notifDropdown i');

        
        if(!badge) return;

        
        fetch('../mark_read.php')
            .then(response => {
                if(response.ok) {
                    
                    badge.remove();
                    
                    icon.classList.remove('text-warning');
                    icon.classList.add('text-secondary');

                    
                    document.querySelectorAll('.notif-item.unread').forEach(el => {
                        el.classList.remove('unread');
                    });
                }
            });
    }
    
    document.addEventListener("DOMContentLoaded", function() {
        
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('msg') === 'created') {
            var myModal = new bootstrap.Modal(document.getElementById('avisModal'));
            myModal.show();
        }
    });
</script>
</body>
</html>