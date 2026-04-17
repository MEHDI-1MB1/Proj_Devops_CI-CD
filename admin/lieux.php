<?php
global $pdo;
require_once '../config/db.php';
require_once '../includes/functions.php';

require_role('admin');

 

 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_lieu'])) {
    $nom = trim($_POST['nom']);
    if (!empty($nom)) {
         
        $stmt = $pdo->prepare("INSERT INTO lieux (nom) VALUES (?)");
        $stmt->execute([$nom]);
    }
}

 
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $pdo->prepare("DELETE FROM lieux WHERE id = ?")->execute([$id]);
    header("Location: lieux.php");  
    exit();
}

 
$stmt = $pdo->query("SELECT * FROM lieux ORDER BY nom");
$lieux = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lieux - Admin</title>
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
        .avatar { width: 40px; height: 40px; background-color: var(--accent-yellow); color: var(--sidebar-bg); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; }

        .nav-link {
            color: #b0b8c4;
            padding: 15px 25px;
            font-size: 0.95rem;
            transition: 0.3s;
            border-left: 4px solid transparent;
        }
        .nav-link:hover { color: white; background: rgba(255,255,255,0.05); }
        .nav-link.active {
            color: var(--accent-yellow);
            background: linear-gradient(90deg, rgba(255,193,7,0.1) 0%, rgba(255,193,7,0) 100%);
            border-left: 4px solid var(--accent-yellow);
        }
        .nav-link i { width: 25px; margin-right: 10px; }

        .main-content { padding: 2rem 3rem; }

        .card-custom { background: white; border-radius: 15px; border: none; box-shadow: 0 4px 6px rgba(0,0,0,0.02); padding: 25px; }
        .list-group-item { border: none; border-bottom: 1px solid #f0f0f0; padding: 15px; display: flex; justify-content: space-between; align-items: center; }
        .list-group-item:last-child { border-bottom: none; }

        .btn-add { background: var(--accent-yellow); color: var(--sidebar-bg); border: none; border-radius: 50px; padding: 10px 25px; font-weight: 600; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include 'sidebar.php'; ?>

        <div class="col-md-9 col-lg-10 main-content">
            <div class="d-flex justify-content-between align-items-center mb-5">
                <h2 class="fw-bold text-dark m-0">Gestion des Lieux</h2>
            </div>

            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card-custom h-100">
                        <h5 class="fw-bold mb-4">Ajouter un lieu</h5>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label text-muted small text-uppercase fw-bold">Nom du lieu</label>
                                <input type="text" name="nom" class="form-control rounded-pill" placeholder="Ex: Cuisine, Jardin..." required>
                            </div>
                            <button type="submit" name="add_lieu" class="btn btn-add w-100 shadow-sm">Ajouter</button>
                        </form>
                    </div>
                </div>

                <div class="col-md-6 mb-4">
                    <div class="card-custom h-100">
                        <h5 class="fw-bold mb-4">Lieux existants</h5>
                        <ul class="list-group">
                            <?php if (count($lieux) > 0): ?>
                                <?php foreach ($lieux as $lieu): ?>
                                    <li class="list-group-item">
                                        <span class="fw-medium"><?php echo htmlspecialchars($lieu['nom']); ?></span>
                                        <a href="?delete=<?php echo $lieu['id']; ?>" class="text-danger small" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce lieu ?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li class="list-group-item text-muted fst-italic">Aucun lieu enregistré.</li>
                            <?php endif; ?>
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