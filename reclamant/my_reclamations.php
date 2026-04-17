<?php
global $pdo;
require_once '../config/db.php';
require_once '../includes/functions.php';

require_role('reclamant');

$user_id = $_SESSION['user_id'];
$success_msg = '';
$error_msg = '';

 
if (isset($_GET['cancel_id'])) {
    $cancel_id = $_GET['cancel_id'];

     
    $stmtCheck = $pdo->prepare("SELECT id FROM reclamations WHERE id = ? AND user_id = ? AND statut = 'en_attente'");
    $stmtCheck->execute([$cancel_id, $user_id]);

    if ($stmtCheck->fetch()) {
        $stmtUpdate = $pdo->prepare("UPDATE reclamations SET statut = 'rejete' WHERE id = ?");
        $stmtUpdate->execute([$cancel_id]);
        $success_msg = "La réclamation #$cancel_id a été annulée avec succès.";
    } else {
        $error_msg = "Impossible d'annuler cette réclamation (elle est déjà en cours de traitement ou terminée).";
    }
}

 
$stmt = $pdo->prepare("
    SELECT r.*, c.nom as categorie_nom 
    FROM reclamations r 
    JOIN categories c ON r.categorie_id = c.id 
    WHERE r.user_id = ? 
    ORDER BY r.date_creation DESC
");
$stmt->execute([$user_id]);
$reclamations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Réclamations - ReCité</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --sidebar-bg: #051b34; --body-bg: #f3f5f9; --accent-yellow: #ffc107; --text-muted: #6c757d; }
        body { font-family: 'Poppins', sans-serif; background-color: var(--body-bg); overflow-x: hidden; }

         
        .sidebar { background-color: var(--sidebar-bg); min-height: 100vh; color: white; padding-top: 2rem; }
        .user-profile-card { background: rgba(255, 255, 255, 0.1); border-radius: 12px; padding: 10px 15px; margin: 0 20px 40px 20px; display: flex; align-items: center; }
        .avatar { width: 40px; height: 40px; background-color: var(--accent-yellow); color: var(--sidebar-bg); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; margin-right: 15px; }
        .nav-link { color: #b0b8c4; padding: 15px 25px; font-size: 0.95rem; transition: 0.3s; border-left: 4px solid transparent; }
        .nav-link:hover { color: white; background: rgba(255,255,255,0.05); }
        .nav-link.active { color: var(--accent-yellow); background: linear-gradient(90deg, rgba(255,193,7,0.1) 0%, rgba(255,193,7,0) 100%); border-left: 4px solid var(--accent-yellow); }
        .nav-link i { width: 25px; margin-right: 10px; }

        .main-content { padding: 2rem 3rem; }
        .card-custom { background: white; border-radius: 15px; border: none; box-shadow: 0 4px 6px rgba(0,0,0,0.02); padding: 25px; animation: fadeIn 0.5s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

         
        .table thead th { border-bottom: 2px solid #f0f0f0; color: #aaa; font-weight: 600; font-size: 0.8rem; text-transform: uppercase; padding-bottom: 15px; letter-spacing: 0.5px; }
        .table td { vertical-align: middle; padding: 15px 10px; font-weight: 500; color: var(--sidebar-bg); font-size: 0.95rem; }

        .status-text { font-weight: 600; }
        .text-blue { color: #00bcd4; }
        .text-gray { color: #bdbdbd; }
        .text-yellow { color: #fbc02d; }
        .text-red { color: #ef5350; }

        .btn-new { background-color: var(--accent-yellow); color: var(--sidebar-bg); border: none; border-radius: 50px; padding: 10px 25px; font-weight: 600; text-decoration: none; transition: 0.3s; }
        .btn-new:hover { background-color: #e0a800; color: var(--sidebar-bg); transform: translateY(-2px); }

         
        .action-btn {
            padding: 6px 16px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: all 0.2s ease;
            border: 1px solid transparent;
            margin-left: 6px;
        }
        .action-btn i { font-size: 0.8rem; }
        .action-btn span { margin-left: 6px; }  

         
        .btn-view-custom { background-color: #f8f9fa; color: #6c757d; border-color: #e9ecef; width: 36px; height: 36px; padding: 0; justify-content: center; }
        .btn-view-custom:hover { background-color: #e2e6ea; color: #495057; transform: translateY(-2px); }

         
        .btn-edit-custom { background-color: #e3f2fd; color: #0d6efd; border-color: transparent; }
        .btn-edit-custom:hover { background-color: #0d6efd; color: white; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(13, 110, 253, 0.2); }

         
        .btn-cancel-custom { background-color: #ffebee; color: #dc3545; border-color: transparent; }
        .btn-cancel-custom:hover { background-color: #dc3545; color: white; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(220, 53, 69, 0.2); }

    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include 'sidebar.php'; ?>

        <div class="col-md-9 col-lg-10 main-content">

            <div class="d-flex justify-content-between align-items-center mb-5">
                <div>
                    <h2 class="fw-bold text-dark m-0">Mes Réclamations</h2>
                    <p class="text-muted small m-0">Suivez l'état de vos demandes en temps réel.</p>
                </div>
                <a href="create.php" class="btn-new shadow-sm">
                    <i class="fas fa-plus me-2"></i> Nouvelle Réclamation
                </a>
            </div>

            <?php if ($success_msg): ?>
                <div class="alert alert-success rounded-3 shadow-sm mb-4 border-0 bg-success bg-opacity-10 text-success fw-bold">
                    <i class="fas fa-check-circle me-2"></i> <?php echo $success_msg; ?>
                </div>
            <?php endif; ?>
            <?php if ($error_msg): ?>
                <div class="alert alert-danger rounded-3 shadow-sm mb-4 border-0 bg-danger bg-opacity-10 text-danger fw-bold">
                    <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error_msg; ?>
                </div>
            <?php endif; ?>

            <div class="card-custom">
                <div class="table-responsive">
                    <table class="table table-borderless table-hover align-middle">
                        <thead>
                        <tr>
                            <th style="width: 50px;">ID</th>
                            <th>Sujet & Lieu</th>
                            <th>Urgence</th>
                            <th>Statut</th>
                            <th class="text-end">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (count($reclamations) > 0): ?>
                            <?php foreach ($reclamations as $rec): ?>
                                <tr>
                                    <td class="text-muted small">#<?php echo $rec['id']; ?></td>

                                    <td>
                                        <div class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($rec['objet']); ?></div>
                                        <div class="small text-muted">
                                            <i class="fas fa-map-marker-alt text-secondary me-1"></i> <?php echo htmlspecialchars($rec['lieu'] ?? 'Non spécifié'); ?>
                                            <span class="mx-2">•</span>
                                            <span class="text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;"><?php echo htmlspecialchars($rec['categorie_nom']); ?></span>
                                        </div>
                                    </td>

                                    <td>
                                        <?php
                                        $urg = $rec['urgence'] ?? 'Faible';
                                        $badgeClass = 'bg-secondary bg-opacity-10 text-secondary';
                                        if ($urg === 'Moyenne') $badgeClass = 'bg-warning bg-opacity-25 text-warning-emphasis';
                                        elseif ($urg === 'Haute') $badgeClass = 'bg-danger bg-opacity-10 text-danger';
                                        ?>
                                        <span class="badge rounded-pill <?php echo $badgeClass; ?> px-3 py-2 fw-normal">
                                            <?php echo htmlspecialchars($urg); ?>
                                        </span>
                                    </td>

                                    <?php
                                    $statusClass = 'text-gray';
                                    $statusText = $rec['statut'];
                                    $iconStatus = 'fa-circle';

                                    if ($rec['statut'] == 'resolu') { $statusClass = 'text-blue'; $statusText = 'Réglée'; $iconStatus = 'fa-check-circle'; }
                                    elseif ($rec['statut'] == 'en_cours') { $statusClass = 'text-gray'; $statusText = 'En cours'; $iconStatus = 'fa-spinner fa-spin'; }
                                    elseif ($rec['statut'] == 'en_attente') { $statusClass = 'text-yellow'; $statusText = 'En attente'; $iconStatus = 'fa-clock'; }
                                    elseif ($rec['statut'] == 'rejete') { $statusClass = 'text-red'; $statusText = 'Annulée'; $iconStatus = 'fa-times-circle'; }
                                    ?>
                                    <td class="status-text <?php echo $statusClass; ?>">
                                        <i class="fas <?php echo $iconStatus; ?> me-1"></i> <?php echo ucfirst($statusText); ?>
                                    </td>

                                    <td class="text-end">
                                        <div class="d-flex justify-content-end align-items-center">
                                            <a href="view.php?id=<?php echo $rec['id']; ?>" class="action-btn btn-view-custom" title="Voir les détails">
                                                <i class="fas fa-eye"></i>
                                            </a>

                                            <?php if ($rec['statut'] == 'en_attente'): ?>
                                                <a href="edit_reclamation.php?id=<?php echo $rec['id']; ?>" class="action-btn btn-edit-custom">
                                                    <i class="fas fa-pen"></i><span>Modifier</span>
                                                </a>

                                                <a href="my_reclamations.php?cancel_id=<?php echo $rec['id']; ?>"
                                                   class="action-btn btn-cancel-custom"
                                                   onclick="return confirm('Êtes-vous sûr de vouloir annuler cette réclamation ? Cette action est irréversible.');">
                                                    <i class="fas fa-times"></i><span>Annuler</span>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="mb-3 text-muted opacity-25"><i class="far fa-folder-open fa-3x"></i></div>
                                    <h6 class="text-muted">Aucune réclamation pour le moment.</h6>
                                    <a href="create.php" class="btn btn-sm btn-outline-primary rounded-pill mt-2">Créer ma première demande</a>
                                </td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>