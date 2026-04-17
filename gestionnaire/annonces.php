<?php
global $pdo;
require_once '../config/db.php';
require_once '../includes/functions.php';

require_role('gestionnaire');

$success_msg = '';
$error_msg = '';

 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $titre = trim($_POST['titre']);
    $type = $_POST['type'];
    $date_evenement = $_POST['date_evenement'];
    $description = trim($_POST['description']);
    $action = $_POST['action'];  

    if (!empty($titre) && !empty($date_evenement) && !empty($description)) {
        try {
            if ($action === 'create') {
                 
                $stmt = $pdo->prepare("INSERT INTO annonces (titre, type, date_evenement, description) VALUES (?, ?, ?, ?)");
                $stmt->execute([$titre, $type, $date_evenement, $description]);
                $success_msg = "Annonce publiée avec succès !";

                 
                 
                $stmtUsers = $pdo->query("SELECT id FROM users WHERE role = 'reclamant'");
                $users = $stmtUsers->fetchAll(PDO::FETCH_COLUMN);

                foreach ($users as $uid) {
                    addNotification(
                            $uid,
                            'annonce',
                            "Nouvelle annonce : " . substr($titre, 0, 30) . "...",
                            "announcements.php"
                    );
                }
                 

            } elseif ($action === 'update' && !empty($_POST['id'])) {
                $stmt = $pdo->prepare("UPDATE annonces SET titre = ?, type = ?, date_evenement = ?, description = ? WHERE id = ?");
                $stmt->execute([$titre, $type, $date_evenement, $description, $_POST['id']]);
                $success_msg = "Annonce modifiée avec succès !";
            }
        } catch (Exception $e) {
            $error_msg = "Erreur SGBD : " . $e->getMessage();
        }
    } else {
        $error_msg = "Veuillez remplir tous les champs obligatoires.";
    }
}

 
 
if (isset($_GET['delete'])) {
    $id_to_delete = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM annonces WHERE id = ?");
    $stmt->execute([$id_to_delete]);
    header("Location: annonces.php");
    exit();
}

$stmt = $pdo->query("SELECT * FROM annonces ORDER BY date_evenement DESC, date_creation DESC");
$annonces = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Annonces - Gestionnaire</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --sidebar-bg: #051b34;
            --body-bg: #f3f5f9;
            --accent-yellow: #ffc107;
            --text-muted: #6c757d;
        }

        body { font-family: 'Poppins', sans-serif; background-color: var(--body-bg); overflow-x: hidden; }

         
        .sidebar { background-color: var(--sidebar-bg); min-height: 100vh; color: white; padding-top: 2rem; }
        .user-profile-card { background: rgba(255, 255, 255, 0.1); border-radius: 12px; padding: 10px 15px; margin: 0 20px 40px 20px; }
        .avatar-small { width: 40px; height: 40px; background-color: var(--accent-yellow); color: var(--sidebar-bg); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; }
        .nav-link { color: #b0b8c4; padding: 15px 25px; transition: 0.3s; border-left: 4px solid transparent; }
        .nav-link:hover { color: white; background: rgba(255,255,255,0.05); }
        .nav-link.active { color: var(--accent-yellow); background: linear-gradient(90deg, rgba(255,193,7,0.1) 0%, rgba(255,193,7,0) 100%); border-left: 4px solid var(--accent-yellow); }
        .nav-link i { width: 25px; margin-right: 10px; }
        .main-content { padding: 2rem 3rem; }

         
        .annonce-card {
            background: white;
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
            margin-bottom: 20px;
            transition: transform 0.2s;
            overflow: hidden;
        }
        .annonce-card:hover { transform: translateY(-3px); box-shadow: 0 10px 15px rgba(0,0,0,0.05); }

        .icon-box {
            width: 45px; height: 45px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem; margin-right: 15px;
        }
        .type-maintenance { background: #fff8e1; color: #ffa000; }
        .type-reunion { background: #e3f2fd; color: #1e88e5; }
        .type-info { background: #f5f5f5; color: #757575; }
        .type-urgence { background: #ffebee; color: #e53935; }

        .date-badge {
            background: #f8f9fa; border: 1px solid #e9ecef;
            border-radius: 20px; padding: 5px 12px;
            font-size: 0.8rem; color: #6c757d; font-weight: 500;
        }

        .btn-add {
            background: var(--sidebar-bg); color: white;
            padding: 10px 20px; border-radius: 30px; border: none;
            font-weight: 500; box-shadow: 0 4px 10px rgba(5, 27, 52, 0.2);
            transition: 0.3s;
        }
        .btn-add:hover { background: var(--accent-yellow); color: var(--sidebar-bg); }

        .actions-btn { opacity: 0.3; transition: 0.3s; }
        .annonce-card:hover .actions-btn { opacity: 1; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <?php include 'sidebar.php'; ?>

        <div class="col-md-9 col-lg-10 main-content">

            <div class="d-flex justify-content-between align-items-center mb-5">
                <div>
                    <h2 class="fw-bold text-dark m-0">Annonces & Actualités</h2>
                    <p class="text-muted m-0">Gérez la communication avec les résidents.</p>
                </div>
                <button class="btn btn-add" onclick="openModal('create')">
                    <i class="fas fa-plus me-2"></i> Nouvelle Annonce
                </button>
            </div>

            <?php if ($success_msg): ?>
                <div class="alert alert-success rounded-3 shadow-sm mb-4"><?php echo $success_msg; ?></div>
            <?php endif; ?>
            <?php if ($error_msg): ?>
                <div class="alert alert-danger rounded-3 shadow-sm mb-4"><?php echo $error_msg; ?></div>
            <?php endif; ?>

            <div class="row">
                <?php if (count($annonces) > 0): ?>
                    <?php foreach ($annonces as $a): ?>
                        <?php
                         
                        $icon = 'fa-info-circle';
                        $styleClass = 'type-info';
                        $typeName = 'Information';

                        if ($a['type'] == 'maintenance') { $icon = 'fa-screwdriver-wrench'; $styleClass = 'type-maintenance'; $typeName = 'Maintenance'; }
                        elseif ($a['type'] == 'reunion') { $icon = 'fa-users'; $styleClass = 'type-reunion'; $typeName = 'Réunion / AG'; }
                        elseif ($a['type'] == 'urgence') { $icon = 'fa-triangle-exclamation'; $styleClass = 'type-urgence'; $typeName = 'Urgence'; }
                        ?>
                        <div class="col-12">
                            <div class="annonce-card p-4">
                                <div class="d-flex flex-column flex-md-row gap-3">
                                    <div class="icon-box <?php echo $styleClass; ?> flex-shrink-0">
                                        <i class="fas <?php echo $icon; ?>"></i>
                                    </div>

                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h5 class="fw-bold text-dark mb-0"><?php echo htmlspecialchars($a['titre']); ?></h5>
                                            <div class="d-flex gap-2 align-items-center">
                                                <div class="date-badge">
                                                    <i class="far fa-calendar-alt me-1"></i>
                                                    <?php echo date('d M Y', strtotime($a['date_evenement'])); ?>
                                                </div>

                                                <div class="actions-btn">
                                                    <button class="btn btn-sm btn-outline-primary border-0"
                                                            onclick='openModal("update", <?php echo htmlspecialchars(json_encode($a), ENT_QUOTES, 'UTF-8'); ?>)'>
                                                        <i class="fas fa-pen"></i>
                                                    </button>
                                                    <a href="?delete=<?php echo $a['id']; ?>"
                                                       class="btn btn-sm btn-outline-danger border-0"
                                                       onclick="return confirm('Supprimer cette annonce ?');">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                        <p class="text-muted mb-0" style="font-size: 0.95rem;">
                                            <?php echo nl2br(htmlspecialchars($a['description'])); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center py-5">
                        <div class="text-muted opacity-50 mb-3"><i class="fas fa-newspaper fa-3x"></i></div>
                        <h5 class="text-muted">Aucune annonce publiée</h5>
                        <p class="small text-muted">Cliquez sur "Nouvelle Annonce" pour commencer.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="annonceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="modalTitle">Nouvelle Annonce</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form method="POST" id="formAnnonce">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="id" id="annonceId">

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase text-muted">Titre</label>
                        <input type="text" name="titre" id="inputTitre" class="form-control" placeholder="Ex: Panne Ascenseur" required>
                    </div>

                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold small text-uppercase text-muted">Type</label>
                            <select name="type" id="inputType" class="form-select">
                                <option value="maintenance">Maintenance </option>
                                <option value="reunion">Réunion / AG </option>
                                <option value="info">Information </option>
                                <option value="urgence">Urgence </option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold small text-uppercase text-muted">Date événement</label>
                            <input type="date" name="date_evenement" id="inputDate" class="form-control" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold small text-uppercase text-muted">Description</label>
                        <textarea name="description" id="inputDesc" class="form-control" rows="4" placeholder="Détails de l'annonce..." required></textarea>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-warning fw-bold text-dark py-2 rounded-pill">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    
    function openModal(mode, data = null) {
        const modalEl = document.getElementById('annonceModal');
        const modal = new bootstrap.Modal(modalEl);

        
        document.getElementById('formAnnonce').reset();

        if (mode === 'create') {
            document.getElementById('modalTitle').innerText = 'Nouvelle Annonce';
            document.getElementById('formAction').value = 'create';
            
            document.getElementById('inputDate').valueAsDate = new Date();


        } else {
            document.getElementById('modalTitle').innerText = 'Modifier l\'annonce';
            document.getElementById('formAction').value = 'update';

            
            document.getElementById('annonceId').value = data.id;
            document.getElementById('inputTitre').value = data.titre;
            document.getElementById('inputType').value = data.type;
            document.getElementById('inputDate').value = data.date_evenement;
            document.getElementById('inputDesc').value = data.description;
        }

        modal.show();
    }
</script>
</body>
</html>