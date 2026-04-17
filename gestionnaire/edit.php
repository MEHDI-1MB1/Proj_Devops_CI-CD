<?php
global $pdo;
require_once '../config/db.php';
require_once '../includes/functions.php';

require_role('gestionnaire');

if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$reclamation_id = $_GET['id'];
$error = '';
$success = '';

 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nouveau_statut = $_POST['statut'];
    $commentaire = trim($_POST['commentaire']);

    if (!empty($nouveau_statut)) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT statut FROM reclamations WHERE id = ?");
            $stmt->execute([$reclamation_id]);
            $ancien_statut = $stmt->fetchColumn();

            $stmtUser = $pdo->prepare("SELECT user_id FROM reclamations WHERE id = ?");
            $stmtUser->execute([$reclamation_id]);
            $userId = $stmtUser->fetchColumn();

            if ($ancien_statut !== $nouveau_statut) {
                $stmt = $pdo->prepare("UPDATE reclamations SET statut = ? WHERE id = ?");
                $stmt->execute([$nouveau_statut, $reclamation_id]);

                $stmt = $pdo->prepare("INSERT INTO historique_statuts (reclamation_id, ancien_statut, nouveau_statut, user_id) VALUES (?, ?, ?, ?)");
                $stmt->execute([$reclamation_id, $ancien_statut, $nouveau_statut, $_SESSION['user_id']]);

                $msg = "Le statut de votre réclamation #$reclamation_id est passé à : " . ucfirst($nouveau_statut);
                addNotification($userId, 'reclamation_statut', $msg, "view.php?id=$reclamation_id");
            }

            if (!empty($commentaire)) {
                $stmt = $pdo->prepare("INSERT INTO commentaires (reclamation_id, user_id, contenu) VALUES (?, ?, ?)");
                $stmt->execute([$reclamation_id, $_SESSION['user_id'], $commentaire]);

                if (!isset($userId)) {
                    $stmtUser = $pdo->prepare("SELECT user_id FROM reclamations WHERE id = ?");
                    $stmtUser->execute([$reclamation_id]);
                    $userId = $stmtUser->fetchColumn();
                }

                $msg = "Nouveau commentaire sur votre réclamation #$reclamation_id";
                addNotification($userId, 'reclamation_commentaire', $msg, "view.php?id=$reclamation_id");
            }

            $pdo->commit();
            $success = "Réclamation mise à jour avec succès.";

            $stmt = $pdo->prepare("SELECT statut FROM reclamations WHERE id = ?");
            $stmt->execute([$reclamation_id]);
            $current_statut = $stmt->fetchColumn();

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Erreur : " . $e->getMessage();
        }
    }
}

 
$stmt = $pdo->prepare("
    SELECT r.*, c.nom as categorie_nom, u.nom as user_nom, u.email as user_email
    FROM reclamations r 
    JOIN categories c ON r.categorie_id = c.id 
    JOIN users u ON r.user_id = u.id 
    WHERE r.id = ?
");
$stmt->execute([$reclamation_id]);
$reclamation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reclamation) {
    header("Location: dashboard.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM pieces_jointes WHERE reclamation_id = ?");
$stmt->execute([$reclamation_id]);
$pieces = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT c.*, u.nom as auteur, u.role as auteur_role 
    FROM commentaires c 
    JOIN users u ON c.user_id = u.id 
    WHERE reclamation_id = ? 
    ORDER BY date_commentaire DESC
");
$stmt->execute([$reclamation_id]);
$commentaires = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Traiter Réclamation #<?php echo $reclamation['id']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --sidebar-bg: #051b34; --body-bg: #f3f5f9; --accent-yellow: #ffc107; }
        body { font-family: 'Poppins', sans-serif; background-color: var(--body-bg); overflow-x: hidden; }
        .sidebar { background-color: var(--sidebar-bg); min-height: 100vh; color: white; padding-top: 2rem; }
        .nav-link { color: #b0b8c4; padding: 15px 25px; transition: 0.3s; }
        .nav-link:hover { color: white; background: rgba(255,255,255,0.05); }
        .nav-link.active { color: var(--accent-yellow); background: linear-gradient(90deg, rgba(255,193,7,0.1) 0%, rgba(255,193,7,0) 100%); border-left: 4px solid var(--accent-yellow); }
        .nav-link i { width: 25px; margin-right: 10px; }
        .main-content { padding: 2rem 3rem; }
        .card-custom { background: white; border-radius: 15px; border: none; box-shadow: 0 4px 6px rgba(0,0,0,0.02); padding: 30px; margin-bottom: 20px; }
        .detail-label { color: #6c757d; font-size: 0.85rem; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px; }
        .detail-value { font-weight: 600; color: var(--sidebar-bg); font-size: 1.1rem; }
        .comment-box { background: #f8f9fa; border-radius: 10px; padding: 15px; margin-bottom: 15px; border-left: 4px solid #dee2e6; }
        .comment-admin { border-left-color: var(--accent-yellow); background: #fffbe6; }
        .comment-reclamant { border-left-color: var(--sidebar-bg); }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include 'sidebar.php'; ?>

        <div class="col-md-9 col-lg-10 main-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold mb-0">Traitement Réclamation #<?php echo $reclamation['id']; ?></h2>
                <a href="dashboard.php" class="btn btn-outline-secondary rounded-pill">Retour</a>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success rounded-3 shadow-sm"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger rounded-3 shadow-sm"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="row">
                <div class="col-lg-8">
                    <div class="card-custom">
                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <span class="detail-label">Réclamant</span>
                                <div class="detail-value"><?php echo htmlspecialchars($reclamation['user_nom']); ?></div>
                                <small class="text-muted"><?php echo htmlspecialchars($reclamation['user_email']); ?></small>
                            </div>
                            <div class="col-md-6">
                                <span class="detail-label">Date</span>
                                <div class="detail-value"><?php echo date('d/m/Y H:i', strtotime($reclamation['date_creation'])); ?></div>
                            </div>
                            <div class="col-md-6">
                                <span class="detail-label">Catégorie</span>
                                <div class="detail-value"><?php echo htmlspecialchars($reclamation['categorie_nom']); ?></div>
                            </div>
                            <div class="col-md-6">
                                <span class="detail-label">Objet</span>
                                <div class="detail-value"><?php echo htmlspecialchars($reclamation['objet']); ?></div>
                            </div>

                            <div class="col-md-6">
                                <span class="detail-label">Lieu</span>
                                <div class="detail-value">
                                    <i class="fas fa-map-marker-alt text-muted small me-1"></i>
                                    <?php echo htmlspecialchars($reclamation['lieu'] ?? 'Non spécifié'); ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <span class="detail-label">Niveau d'Urgence</span>
                                <div class="detail-value">
                                    <?php
                                    $urg = $reclamation['urgence'] ?? 'Faible';
                                    $badgeColor = 'bg-secondary';
                                    if($urg === 'Moyenne') $badgeColor = 'bg-warning text-dark';
                                    if($urg === 'Haute') $badgeColor = 'bg-danger';
                                    ?>
                                    <span class="badge <?php echo $badgeColor; ?>"><?php echo htmlspecialchars($urg); ?></span>
                                </div>
                            </div>
                        </div>

                        <h5 class="fw-bold mt-4 mb-3">Description</h5>
                        <p class="bg-light p-3 rounded-3"><?php echo nl2br(htmlspecialchars($reclamation['description'])); ?></p>

                        <?php if (count($pieces) > 0): ?>
                            <h5 class="fw-bold mt-4 mb-3">Pièces jointes</h5>
                            <div class="d-flex flex-wrap gap-3">
                                <?php foreach ($pieces as $piece): ?>
                                    <?php
                                     
                                    $raw_path = $piece['chemin_fichier'];

                                     
                                     
                                    if (strpos($raw_path, '/') === false && strpos($raw_path, '\\') === false) {
                                        $final_url = '../uploads/' . $raw_path;
                                    }
                                     
                                    elseif (strpos($raw_path, 'uploads/') === 0) {
                                        $final_url = '../' . $raw_path;
                                    }
                                     
                                    else {
                                        $final_url = $raw_path;
                                    }

                                    $nom_fichier = $piece['nom_fichier'];
                                    $ext = strtolower(pathinfo($raw_path, PATHINFO_EXTENSION));
                                    $is_image = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                    ?>

                                    <div class="card p-2 shadow-sm" style="width: 200px;">
                                        <div style="font-size: 10px; color: red; word-break: break-all; margin-bottom: 5px;">

                                        </div>

                                        <?php if ($is_image): ?>
                                            <a href="<?php echo htmlspecialchars($final_url); ?>" target="_blank">
                                                <img src="<?php echo htmlspecialchars($final_url); ?>"
                                                     class="card-img-top rounded"
                                                     alt="Preuve"
                                                     style="height: 120px; object-fit: cover;"
                                                     onerror="this.onerror=null; this.src='https://via.placeholder.com/150?text=Image+Introuvable';">
                                            </a>
                                        <?php else: ?>
                                            <div class="bg-light d-flex align-items-center justify-content-center rounded" style="height: 120px;">
                                                <i class="fas fa-file-alt fa-3x text-secondary"></i>
                                            </div>
                                        <?php endif; ?>

                                        <div class="text-center mt-2">
                                            <a href="<?php echo htmlspecialchars($final_url); ?>" target="_blank" class="btn btn-sm btn-primary w-100">
                                                <i class="fas fa-eye me-1"></i> Voir
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                    </div>

                    <div class="card-custom">
                        <h5 class="fw-bold mb-3">Historique & Commentaires</h5>
                        <?php if (count($commentaires) > 0): ?>
                            <?php foreach ($commentaires as $com): ?>
                                <?php $is_admin = in_array($com['auteur_role'], ['admin', 'gestionnaire']); ?>
                                <div class="comment-box <?php echo $is_admin ? 'comment-admin' : 'comment-reclamant'; ?>">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="fw-bold small"><?php echo htmlspecialchars($com['auteur']); ?> (<?php echo ucfirst($com['auteur_role']); ?>)</span>
                                        <small class="text-muted" style="font-size: 0.75rem;"><?php echo date('d/m H:i', strtotime($com['date_commentaire'])); ?></small>
                                    </div>
                                    <div class="small"><?php echo nl2br(htmlspecialchars($com['contenu'])); ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted small fst-italic">Aucun commentaire pour l'instant.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card-custom sticky-top" style="top: 20px;">
                        <h5 class="fw-bold mb-4">Action</h5>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Statut actuel</label>
                                <select name="statut" class="form-select">
                                    <option value="en_attente" <?php if ($reclamation['statut'] == 'en_attente') echo 'selected'; ?>>En attente</option>
                                    <option value="en_cours" <?php if ($reclamation['statut'] == 'en_cours') echo 'selected'; ?>>En cours</option>
                                    <option value="resolu" <?php if ($reclamation['statut'] == 'resolu') echo 'selected'; ?>>Résolu</option>
                                    <option value="rejete" <?php if ($reclamation['statut'] == 'rejete') echo 'selected'; ?>>Rejeté</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Ajouter une note / réponse</label>
                                <textarea name="commentaire" class="form-control" rows="4" placeholder="Message à destination du réclamant..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-warning w-100 fw-bold rounded-pill">Mettre à jour</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>