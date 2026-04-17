<?php
global $pdo;
require_once '../config/db.php';
require_once '../includes/functions.php';

require_role('reclamant');

if (!isset($_GET['id'])) {
    header("Location: my_reclamations.php");
    exit();
}

$id = $_GET['id'];
$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

 
$stmt = $pdo->prepare("SELECT * FROM reclamations WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $user_id]);
$reclamation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reclamation || $reclamation['statut'] !== 'en_attente') {
    header("Location: my_reclamations.php");
    exit();
}

 
if (isset($_GET['delete_pj'])) {
    $pj_id = $_GET['delete_pj'];

     
    $stmtPj = $pdo->prepare("SELECT * FROM pieces_jointes WHERE id = ? AND reclamation_id = ?");
    $stmtPj->execute([$pj_id, $id]);
    $pj = $stmtPj->fetch();

    if ($pj) {
         
        $file_path = '../uploads/' . $pj['chemin_fichier'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
         
        $pdo->prepare("DELETE FROM pieces_jointes WHERE id = ?")->execute([$pj_id]);
        $success = "Preuve supprimée avec succès.";
    }
}

 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $objet = trim($_POST['objet']);
    $categorie_id = $_POST['categorie_id'];
    $description = trim($_POST['description']);
    $lieu = $_POST['lieu'];
    $urgence = $_POST['urgency'];

    if (!empty($objet) && !empty($description)) {
        try {
             
            $stmtUpdate = $pdo->prepare("UPDATE reclamations SET objet = ?, categorie_id = ?, description = ?, lieu = ?, urgence = ? WHERE id = ?");
            $stmtUpdate->execute([$objet, $categorie_id, $description, $lieu, $urgence, $id]);

             
            if (isset($_FILES['new_proof']) && $_FILES['new_proof']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = '../uploads/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

                $fileExt = strtolower(pathinfo($_FILES['new_proof']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'pdf'];

                if (in_array($fileExt, $allowed)) {
                    $fileName = 'rec_' . $id . '_' . time() . '.' . $fileExt;
                    if (move_uploaded_file($_FILES['new_proof']['tmp_name'], $uploadDir . $fileName)) {
                        $stmtFile = $pdo->prepare("INSERT INTO pieces_jointes (reclamation_id, chemin_fichier, nom_fichier) VALUES (?, ?, ?)");
                        $stmtFile->execute([$id, $fileName, $_FILES['new_proof']['name']]);
                    }
                } else {
                    $error = "Format de fichier non supporté (JPG, PNG, PDF uniquement).";
                }
            }

            if (empty($error)) {
                $success = "Réclamation modifiée avec succès.";
                 
                $reclamation['objet'] = $objet;
                $reclamation['categorie_id'] = $categorie_id;
                $reclamation['description'] = $description;
                $reclamation['lieu'] = $lieu;
                $reclamation['urgence'] = $urgence;
            }

        } catch (Exception $e) {
            $error = "Erreur : " . $e->getMessage();
        }
    } else {
        $error = "Veuillez remplir tous les champs obligatoires.";
    }
}

 
$categories = $pdo->query("SELECT * FROM categories ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
$lieux_db = $pdo->query("SELECT * FROM lieux ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
 
$pieces = $pdo->prepare("SELECT * FROM pieces_jointes WHERE reclamation_id = ?");
$pieces->execute([$id]);
$current_proofs = $pieces->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Réclamation - ReCité</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --sidebar-bg: #051b34; --body-bg: #f3f5f9; --accent-yellow: #ffc107; }
        body { font-family: 'Poppins', sans-serif; background-color: var(--body-bg); overflow-x: hidden; }

        .sidebar { background-color: var(--sidebar-bg); min-height: 100vh; color: white; padding-top: 2rem; }
        .user-profile-card { background: rgba(255, 255, 255, 0.1); border-radius: 12px; padding: 10px 15px; margin: 0 20px 40px 20px; display: flex; align-items: center; }
        .avatar { width: 40px; height: 40px; background-color: var(--accent-yellow); color: var(--sidebar-bg); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; margin-right: 15px; }
        .nav-link { color: #b0b8c4; padding: 15px 25px; transition: 0.3s; border-left: 4px solid transparent; }
        .nav-link:hover { color: white; background: rgba(255,255,255,0.05); }
        .nav-link.active { color: var(--accent-yellow); background: linear-gradient(90deg, rgba(255,193,7,0.1) 0%, rgba(255,193,7,0) 100%); border-left: 4px solid var(--accent-yellow); }
        .nav-link i { width: 25px; margin-right: 10px; }

        .main-content { padding: 2rem 3rem; }
        .form-card { background: white; border-radius: 20px; padding: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .btn-submit { background: var(--sidebar-bg); color: white; border-radius: 50px; padding: 10px 30px; font-weight: 600; border: none; }
        .btn-submit:hover { background: #082d56; color: var(--accent-yellow); }

         
        .proof-item { background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 8px; padding: 8px 12px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; font-size: 0.9rem; }
        .btn-delete-pj { color: #dc3545; cursor: pointer; transition: 0.2s; }
        .btn-delete-pj:hover { color: #bb2d3b; transform: scale(1.1); }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include 'sidebar.php'; ?>

        <div class="col-md-9 col-lg-10 main-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold text-dark m-0">Modifier Réclamation #<?php echo $id; ?></h2>
                <a href="my_reclamations.php" class="btn btn-light rounded-circle shadow-sm"><i class="fas fa-times"></i></a>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success rounded-3 shadow-sm mb-4 border-0 bg-success bg-opacity-10 text-success fw-bold">
                    <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger rounded-3 shadow-sm mb-4 border-0 bg-danger bg-opacity-10 text-danger fw-bold">
                    <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="form-card">
                <form method="POST" enctype="multipart/form-data">
                    <div class="row g-4">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Titre de la réclamation</label>
                                <input type="text" name="objet" class="form-control" value="<?php echo htmlspecialchars($reclamation['objet']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Description détaillée</label>
                                <textarea name="description" class="form-control" rows="6" required><?php echo htmlspecialchars($reclamation['description']); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Preuves / Photos</label>

                                <?php if (count($current_proofs) > 0): ?>
                                    <div class="mb-3">
                                        <?php foreach ($current_proofs as $pj): ?>
                                            <div class="proof-item">
                                                <span><i class="fas fa-paperclip me-2 text-muted"></i> <?php echo htmlspecialchars($pj['nom_fichier']); ?></span>
                                                <a href="edit_reclamation.php?id=<?php echo $id; ?>&delete_pj=<?php echo $pj['id']; ?>"
                                                   class="btn-delete-pj"
                                                   onclick="return confirm('Supprimer ce fichier ?');"
                                                   title="Supprimer">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="input-group">
                                    <input type="file" name="new_proof" class="form-control" accept="image/*,.pdf">
                                    <span class="input-group-text text-muted small">Max 5 Mo</span>
                                </div>
                                <div class="form-text small text-muted">Vous pouvez ajouter un fichier supplémentaire (JPG, PNG, PDF).</div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="bg-light p-4 rounded-4">
                                <div class="mb-4">
                                    <label class="form-label fw-bold small text-uppercase text-muted">Catégorie</label>
                                    <select name="categorie_id" class="form-select bg-white border-0 shadow-sm">
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo $cat['id']; ?>" <?php echo ($cat['id'] == $reclamation['categorie_id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($cat['nom']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label fw-bold small text-uppercase text-muted">Lieu</label>
                                    <select name="lieu" class="form-select bg-white border-0 shadow-sm">
                                        <?php foreach ($lieux_db as $l): ?>
                                            <option value="<?php echo $l['nom']; ?>" <?php echo ($l['nom'] == $reclamation['lieu']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($l['nom']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label fw-bold small text-uppercase text-muted">Priorité</label>
                                    <select name="urgency" class="form-select bg-white border-0 shadow-sm">
                                        <option value="Faible" <?php echo ($reclamation['urgence'] == 'Faible') ? 'selected' : ''; ?>>Faible</option>
                                        <option value="Moyenne" <?php echo ($reclamation['urgence'] == 'Moyenne') ? 'selected' : ''; ?>>Moyenne</option>
                                        <option value="Haute" <?php echo ($reclamation['urgence'] == 'Haute') ? 'selected' : ''; ?>>Haute</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-end mt-4 pt-3 border-top">
                        <a href="my_reclamations.php" class="btn btn-light me-2 text-muted fw-bold">Annuler</a>
                        <button type="submit" class="btn btn-submit shadow-sm px-4">
                            <i class="fas fa-save me-2"></i> Enregistrer les modifications
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>