<?php
global $pdo;
require_once '../config/db.php';
require_once '../includes/functions.php';

require_role('reclamant');

if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$id = $_GET['id'];
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'Utilisateur';

 
 
$stmt = $pdo->prepare("
    SELECT r.*, c.nom as categorie_nom 
    FROM reclamations r 
    JOIN categories c ON r.categorie_id = c.id 
    WHERE r.id = ? AND r.user_id = ?
");
$stmt->execute([$id, $user_id]);
$reclamation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reclamation) {
    header("Location: dashboard.php");
    exit();
}

 
$stmt = $pdo->prepare("SELECT * FROM pieces_jointes WHERE reclamation_id = ?");
$stmt->execute([$id]);
$pieces = $stmt->fetchAll(PDO::FETCH_ASSOC);

 
$stmt = $pdo->prepare("
    SELECT c.*, u.nom as auteur, u.role as auteur_role 
    FROM commentaires c 
    JOIN users u ON c.user_id = u.id 
    WHERE reclamation_id = ? 
    ORDER BY date_commentaire ASC
");
$stmt->execute([$id]);
$commentaires = $stmt->fetchAll(PDO::FETCH_ASSOC);

 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contenu'])) {
    $contenu = trim($_POST['contenu']);
    if (!empty($contenu)) {
        $stmt = $pdo->prepare("INSERT INTO commentaires (reclamation_id, user_id, contenu) VALUES (?, ?, ?)");
        $stmt->execute([$id, $user_id, $contenu]);

         
        notifyAllManagers(
                'reclamation_commentaire',
                "Nouveau message de $user_name sur la réclamation #$id",
                "edit.php?id=$id"
        );

        header("Location: view.php?id=$id");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détail Réclamation #<?php echo $id; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --sidebar-bg: #051b34; --body-bg: #f3f5f9; --accent-yellow: #ffc107; }
        body { font-family: 'Poppins', sans-serif; background-color: var(--body-bg); overflow-x: hidden; }
        .sidebar { background-color: var(--sidebar-bg); min-height: 100vh; color: white; padding-top: 2rem; }
        .user-profile-card { background: rgba(255, 255, 255, 0.1); border-radius: 12px; padding: 10px 15px; margin: 0 20px 40px 20px; }
        .avatar { width: 40px; height: 40px; background-color: var(--accent-yellow); color: var(--sidebar-bg); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; }
        .nav-link { color: #b0b8c4; padding: 15px 25px; transition: 0.3s; }
        .nav-link:hover { color: white; background: rgba(255,255,255,0.05); }
        .nav-link.active { color: var(--accent-yellow); background: rgba(255,193,7,0.1); border-left: 4px solid var(--accent-yellow); }
        .nav-link i { width: 25px; margin-right: 10px; }
        .main-content { padding: 2rem 3rem; }

        .card-custom { background: white; border-radius: 15px; border: none; box-shadow: 0 4px 6px rgba(0,0,0,0.02); padding: 30px; margin-bottom: 20px; }
        .status-badge { padding: 8px 20px; border-radius: 50px; font-weight: 600; font-size: 0.9rem; display: inline-block; }
        .status-resolu { background-color: #e0f7fa; color: #00bcd4; }
        .status-en_cours { background-color: #eceff1; color: #607d8b; }
        .status-en_attente { background-color: #fff8e1; color: #fbc02d; }
        .status-rejete { background-color: #ffebee; color: #ef5350; }

        .timeline-item { position: relative; padding-left: 30px; margin-bottom: 20px; }
        .timeline-item::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 2px; background: #eee; }
        .timeline-item::after { content: ''; position: absolute; left: -4px; top: 5px; width: 10px; height: 10px; border-radius: 50%; background: var(--sidebar-bg); }
        .admin-msg::after { background: var(--accent-yellow); }

        .msg-box { background: #f8f9fa; border-radius: 12px; padding: 15px; position: relative; }
        .msg-box.admin { background: #fffbe6; border: 1px solid #ffe58f; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 sidebar d-none d-md-block px-0">
            <div style="margin: 0 20px 25px 20px;">
                <img src="../assets/img/Rec_Blanc.png" alt="Logo Syndic" style="width: 137px; height: auto;">
            </div>
            <div class="user-profile-card d-flex align-items-center">
                <?php if (!empty($_SESSION['user_avatar'])): ?>
                    <img src="../uploads/avatars/<?php echo htmlspecialchars($_SESSION['user_avatar']); ?>" class="avatar-small me-3" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; aspect-ratio: 1/1;" alt="Avatar">
                <?php else: ?>
                    <div class="avatar-small me-3" style="width: 40px; height: 40px; background-color: var(--accent-yellow); color: var(--sidebar-bg); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700;">
                        <?php
                        $vals = explode(' ', $_SESSION['user_name'] ?? 'User');
                        echo strtoupper(substr($vals[0], 0, 1) . substr($vals[1] ?? '', 0, 1));
                        ?>
                    </div>
                <?php endif; ?>
                <div>
                    <div class="fw-bold lh-1"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Utilisateur'); ?></div>
                    <small class="text-white-50" style="font-size: 12px;">Réclamant</small>
                </div>
            </div>
            <ul class="nav flex-column">
                <li class="nav-item"><a href="dashboard.php" class="nav-link"><i class="fas fa-home"></i> Accueil</a></li>
                <li class="nav-item"><a href="my_reclamations.php" class="nav-link"><i class="fas fa-file-alt"></i> Mes réclamations</a></li>
                <li class="nav-item"><a href="announcements.php" class="nav-link"><i class="fas fa-bullhorn"></i> Annonces</a></li>
                <li class="nav-item"><a href="profile.php" class="nav-link"><i class="fas fa-user"></i> Mon profil</a></li>
            </ul>
            <div class="mt-5 pt-5 px-4">
                <a href="../logout.php" class="text-danger text-decoration-none small fw-bold"><i class="fas fa-power-off me-2"></i> Se déconnecter</a>
            </div>
        </div>

        <div class="col-md-9 col-lg-10 main-content">
            <div class="mb-4">
                <a href="my_reclamations.php" class="text-muted text-decoration-none"><i class="fas fa-arrow-left me-2"></i> Retour aux réclamations</a>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <div class="card-custom">
                        <div class="d-flex justify-content-between align-items-start mb-4">
                            <div>
                                <h3 class="fw-bold mb-1"><?php echo htmlspecialchars($reclamation['objet']); ?></h3>
                                <span class="text-muted small">Créé le <?php echo date('d/m/Y à H:i', strtotime($reclamation['date_creation'])); ?></span>
                            </div>
                            <span class="status-badge status-<?php echo $reclamation['statut']; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $reclamation['statut'])); ?>
                            </span>
                        </div>

                        <div class="p-3 bg-light rounded-3 mb-4">
                            <p class="mb-0 text-dark"><?php echo nl2br(htmlspecialchars($reclamation['description'])); ?></p>
                        </div>

                        <?php if (count($pieces) > 0): ?>
                            <h6 class="fw-bold mb-3">Pièces jointes</h6>
                            <div class="d-flex gap-2 mb-4">
                                <?php foreach ($pieces as $pj): ?>
                                    <a href="../uploads/<?php echo htmlspecialchars($pj['chemin_fichier']); ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-paperclip me-2"></i> <?php echo htmlspecialchars($pj['nom_fichier']); ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="card-custom">
                        <h5 class="fw-bold mb-4">Suivi & Messages</h5>

                        <div class="timeline">
                            <?php foreach ($commentaires as $com): ?>
                                <?php $isAdmin = in_array($com['auteur_role'], ['admin', 'gestionnaire']); ?>
                                <div class="timeline-item <?php echo $isAdmin ? 'admin-msg' : ''; ?>">
                                    <div class="d-flex justify-content-between mb-1">
                                        <strong class="small"><?php echo htmlspecialchars($com['auteur']); ?></strong>
                                        <small class="text-muted" style="font-size: 0.75rem;"><?php echo date('d/m H:i', strtotime($com['date_commentaire'])); ?></small>
                                    </div>
                                    <div class="msg-box <?php echo $isAdmin ? 'admin' : ''; ?>">
                                        <?php echo nl2br(htmlspecialchars($com['contenu'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <hr class="my-4">

                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-uppercase text-muted">Ajouter un message</label>
                                <textarea name="contenu" class="form-control" rows="3" placeholder="Votre message..." required></textarea>
                            </div>
                            <button type="submit" class="btn btn-dark rounded-pill px-4">Envoyer</button>
                        </form>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card-custom">
                        <h6 class="fw-bold mb-3 text-muted text-uppercase small">Détails</h6>
                        <ul class="list-unstyled">

                            <li class="mb-3">
                                <span class="d-block text-muted small">Catégorie</span>
                                <span class="fw-medium"><?php echo htmlspecialchars($reclamation['categorie_nom']); ?></span>
                            </li>

                            <li class="mb-3">
                                <span class="d-block text-muted small">Lieu</span>
                                <span class="fw-medium">
                                    <i class="fas fa-map-marker-alt text-secondary me-1"></i>
                                    <?php echo htmlspecialchars($reclamation['lieu'] ?? 'Non spécifié'); ?>
                                </span>
                            </li>

                            <li class="mb-3">
                                <span class="d-block text-muted small">Urgence</span>
                                <?php
                                $urg = $reclamation['urgence'] ?? 'Faible';
                                $badgeClass = 'bg-secondary';
                                if ($urg === 'Moyenne') $badgeClass = 'bg-warning text-dark';
                                if ($urg === 'Haute') $badgeClass = 'bg-danger';
                                ?>
                                <span class="badge rounded-pill <?php echo $badgeClass; ?> mt-1">
                                    <?php echo htmlspecialchars($urg); ?>
                                </span>
                            </li>

                            <li class="mb-3 border-top pt-3">
                                <span class="d-block text-muted small">Référence</span>
                                <span class="fw-medium font-monospace">#<?php echo str_pad($reclamation['id'], 6, '0', STR_PAD_LEFT); ?></span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>