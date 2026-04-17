<?php
global $pdo;
require_once '../config/db.php';
require_once '../includes/functions.php';

require_role('reclamant');

$user_name = $_SESSION['user_name'] ?? 'Utilisateur';
$user_id = $_SESSION['user_id'];

 
$stmt = $pdo->query("SELECT * FROM categories ORDER BY nom");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT * FROM lieux ORDER BY nom");
$lieux_db = $stmt->fetchAll(PDO::FETCH_ASSOC);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $objet = trim($_POST['objet']);
    $categorie_id = $_POST['categorie_id'];
    $description = trim($_POST['description']);
    $lieu = $_POST['lieu'] ?? null;
    $urgency = $_POST['urgency'] ?? 'Faible';

    if (!empty($objet) && !empty($categorie_id) && !empty($description)) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO reclamations (user_id, categorie_id, objet, description, lieu, urgence, statut) VALUES (?, ?, ?, ?, ?, ?, 'en_attente')");
            $stmt->execute([$user_id, $categorie_id, $objet, $description, $lieu, $urgency]);

            $reclamation_id = $pdo->lastInsertId();

             
            if ($urgency === 'Haute' || $urgency === 'Moyenne') {
                notifyAllManagers('urgence', "⚠️ URGENCE ($urgency) : " . substr($objet, 0, 20) . "...", "edit.php?id=$reclamation_id");
            } else {
                notifyAllManagers('reclamation_new', "Nouvelle réclamation : " . substr($objet, 0, 20), "edit.php?id=$reclamation_id");
            }

             
            if (isset($_FILES['piece_jointe'])) {
                $uploadDir = '../uploads/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

                $count = count($_FILES['piece_jointe']['name']);

                for ($i = 0; $i < $count; $i++) {
                    if ($_FILES['piece_jointe']['error'][$i] === UPLOAD_ERR_OK) {
                        $fileExt = strtolower(pathinfo($_FILES['piece_jointe']['name'][$i], PATHINFO_EXTENSION));
                        $allowed = ['jpg', 'jpeg', 'png', 'pdf'];

                        if (in_array($fileExt, $allowed)) {
                            $fileName = 'rec_' . $reclamation_id . '_' . time() . '_' . $i . '.' . $fileExt;
                            if (move_uploaded_file($_FILES['piece_jointe']['tmp_name'][$i], $uploadDir . $fileName)) {
                                $stmtFile = $pdo->prepare("INSERT INTO pieces_jointes (reclamation_id, chemin_fichier, nom_fichier) VALUES (?, ?, ?)");
                                $stmtFile->execute([$reclamation_id, $fileName, $_FILES['piece_jointe']['name'][$i]]);
                            }
                        }
                    }
                }
            }
             

            $pdo->commit();
            header("Location: dashboard.php?msg=created");
            exit();

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Erreur technique : " . $e->getMessage();
        }
    } else {
        $error = "Veuillez remplir les champs obligatoires.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelle Réclamation - ReCité</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root { --sidebar-bg: #051b34; --body-bg: #f3f5f9; --accent-yellow: #ffc107; --text-muted: #6c757d; --text-dark: #2C3E50; }
        body { font-family: 'Poppins', sans-serif; background-color: var(--body-bg); overflow-x: hidden; }

        .sidebar { background-color: var(--sidebar-bg); min-height: 100vh; color: white; padding-top: 2rem; }
        .user-profile-card { background: rgba(255, 255, 255, 0.1); border-radius: 12px; padding: 10px 15px; margin: 0 20px 40px 20px; }
        .avatar-small { width: 40px; height: 40px; background-color: var(--accent-yellow); color: var(--sidebar-bg); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; object-fit: cover; }
        .nav-link { color: #b0b8c4; padding: 15px 25px; transition: 0.3s; border-left: 4px solid transparent; }
        .nav-link:hover { color: white; background: rgba(255,255,255,0.05); }
        .nav-link.active { color: var(--accent-yellow); background: linear-gradient(90deg, rgba(255,193,7,0.1) 0%, rgba(255,193,7,0) 100%); border-left: 4px solid var(--accent-yellow); }
        .nav-link i { width: 25px; margin-right: 10px; }

        .main-content { padding: 2rem 3rem; }
        .form-card { background: white; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); padding: 40px; border: 1px solid rgba(0,0,0,0.02); }
        .form-label { font-weight: 600; color: var(--text-dark); margin-bottom: 8px; font-size: 0.9rem; }
        .form-control, .form-select { border-radius: 10px; padding: 12px 15px; border: 1px solid #e0e0e0; background-color: #f9f9f9; transition: 0.3s; }
        .form-control:focus, .form-select:focus { background-color: white; border-color: var(--accent-yellow); box-shadow: 0 0 0 4px rgba(255, 193, 7, 0.1); }
        .required-star { color: #dc3545; }

         
        .urgency-group { display: flex; gap: 15px; }
        .urgency-option { flex: 1; position: relative; }
        .urgency-input { position: absolute; opacity: 0; width: 100%; height: 100%; cursor: pointer; z-index: 10; }
        .urgency-label { display: block; padding: 15px; background: #fff; border: 2px solid #e0e0e0; border-radius: 12px; text-align: center; cursor: pointer; transition: 0.3s; color: #6c757d; font-weight: 500; }
        .urgency-input:checked + .urgency-label { border-color: var(--accent-yellow); background-color: #fff9e6; color: #000; }

        .btn-submit { background: var(--sidebar-bg); color: white; padding: 15px 40px; border-radius: 50px; font-weight: 600; border: none; transition: 0.3s; }
        .btn-submit:hover { background: #082d56; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(5, 27, 52, 0.2); color: var(--accent-yellow); }
        .btn-cancel { background: #f1f3f5; color: var(--text-dark); padding: 15px 30px; border-radius: 50px; font-weight: 600; text-decoration: none; margin-right: 15px; transition: 0.3s; }
        .btn-cancel:hover { background: #e9ecef; color: black; }

         
        .proof-grid { display: flex; flex-wrap: wrap; gap: 15px; }

         
        .proof-box-add {
            width: 100px; height: 100px;
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            cursor: pointer;
            transition: 0.2s;
            background: #f8f9fa;
            color: #6c757d;
        }
        .proof-box-add:hover { border-color: var(--accent-yellow); color: var(--sidebar-bg); background: #fff; }
        .proof-box-add i { font-size: 1.5rem; margin-bottom: 5px; }
        .proof-box-add span { font-size: 0.7rem; font-weight: 600; }

         
        .proof-box-item {
            width: 100px; height: 100px;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            position: relative;
            background: #fff;
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            padding: 5px;
            overflow: hidden;
        }
        .proof-icon { font-size: 2rem; color: #0d6efd; margin-bottom: 5px; }
        .proof-name { font-size: 0.65rem; text-align: center; width: 100%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #333; }

         
        .btn-remove-proof {
            position: absolute; top: -5px; right: -5px;
            background: #dc3545; color: white;
            border-radius: 50%; width: 22px; height: 22px;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.7rem; cursor: pointer; border: 2px solid white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .btn-remove-proof:hover { background: #bb2d3b; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <?php include 'sidebar.php'; ?>

        <div class="col-md-9 col-lg-10 main-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold text-dark m-0">Nouvelle Réclamation</h2>
                    <p class="text-muted small m-0">Signalez un incident ou posez une question au résponsable.</p>
                </div>
                <a href="dashboard.php" class="btn btn-light rounded-circle shadow-sm"><i class="fas fa-times"></i></a>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4">
                    <i class="fas fa-exclamation-triangle me-2"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="form-card">
                <form method="POST" enctype="multipart/form-data" id="reclForm">
                    <div class="row g-4">
                        <div class="col-lg-8">
                            <div class="mb-4">
                                <label class="form-label">Titre de la réclamation <span class="required-star">*</span></label>
                                <input type="text" name="objet" class="form-control" placeholder="Ex: Fuite d'eau cuisine..." required>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Description détaillée <span class="required-star">*</span></label>
                                <textarea name="description" class="form-control" rows="6" placeholder="Décrivez le problème en détail..." required></textarea>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Preuves (Photos/PDF)</label>

                                <div class="proof-grid" id="proofGrid">
                                    <div class="proof-box-add" onclick="triggerFileInput()">
                                        <i class="fas fa-plus"></i>
                                        <span>Ajouter</span>
                                    </div>
                                </div>

                                <div id="hiddenInputsContainer" style="display:none;"></div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="p-3 bg-light rounded-4">
                                <div class="mb-4">
                                    <label class="form-label">Catégorie <span class="required-star">*</span></label>
                                    <select name="categorie_id" class="form-select" required>
                                        <option value="" selected disabled>Sélectionner...</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['nom']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label">Lieu (Optionnel)</label>
                                    <select name="lieu" class="form-select">
                                        <option value="">Non spécifié</option>
                                        <?php foreach ($lieux_db as $lieu_item): ?>
                                            <option value="<?php echo htmlspecialchars($lieu_item['nom']); ?>"><?php echo htmlspecialchars($lieu_item['nom']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label">Priorité</label>
                                    <div class="d-flex flex-column gap-2">
                                        <div class="urgency-option">
                                            <input type="radio" name="urgency" value="Faible" id="urg-low" class="urgency-input" checked>
                                            <label for="urg-low" class="urgency-label text-start px-3 py-2 d-flex align-items-center">
                                                <i class="fas fa-coffee me-3 text-secondary"></i> Normale (Faible)
                                            </label>
                                        </div>
                                        <div class="urgency-option">
                                            <input type="radio" name="urgency" value="Moyenne" id="urg-med" class="urgency-input">
                                            <label for="urg-med" class="urgency-label text-start px-3 py-2 d-flex align-items-center">
                                                <i class="fas fa-clock me-3 text-warning"></i> Urgente (Moyenne)
                                            </label>
                                        </div>
                                        <div class="urgency-option">
                                            <input type="radio" name="urgency" value="Haute" id="urg-high" class="urgency-input">
                                            <label for="urg-high" class="urgency-label text-start px-3 py-2 d-flex align-items-center">
                                                <i class="fas fa-fire me-3 text-danger"></i> Très Urgente (Haute)
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                        <a href="dashboard.php" class="btn btn-cancel">Annuler</a>
                        <button type="submit" class="btn btn-submit">Envoyer la réclamation <i class="fas fa-paper-plane ms-2"></i></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    
    let fileCounter = 0;

    function triggerFileInput() {
        
        fileCounter++;
        const inputId = 'fileInput_' + fileCounter;

        const input = document.createElement('input');
        input.type = 'file';
        input.name = 'piece_jointe[]';
        input.accept = 'image/*,.pdf';
        input.id = inputId;
        input.style.display = 'none'; 

        
        input.onchange = function(e) {
            handleFileSelect(e, inputId);
        };

        
        document.getElementById('hiddenInputsContainer').appendChild(input);

        
        input.click();
    }

    function handleFileSelect(event, inputId) {
        const file = event.target.files[0];
        if (!file) {
            
            document.getElementById(inputId).remove();
            return;
        }

        
        const grid = document.getElementById('proofGrid');
        const addBtn = grid.querySelector('.proof-box-add');

        const box = document.createElement('div');
        box.className = 'proof-box-item';
        box.id = 'box_' + inputId;

        
        let iconClass = 'fa-file';
        if (file.type.includes('image')) iconClass = 'fa-image';
        if (file.type.includes('pdf')) iconClass = 'fa-file-pdf';

        box.innerHTML = `
            <div class="btn-remove-proof" onclick="removeFile('${inputId}')"><i class="fas fa-times"></i></div>
            <i class="fas ${iconClass} proof-icon"></i>
            <div class="proof-name" title="${file.name}">${file.name}</div>
        `;

        
        grid.insertBefore(box, addBtn);
    }

    function removeFile(inputId) {
        
        const input = document.getElementById(inputId);
        if (input) input.remove();

        
        const box = document.getElementById('box_' + inputId);
        if (box) box.remove();
    }
</script>
</body>
</html>