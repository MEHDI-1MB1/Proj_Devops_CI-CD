<?php
global $pdo;
require_once '../config/db.php';
require_once '../includes/functions.php';

require_role('admin');

 

 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_faq'])) {
    $question = trim($_POST['question']);
    $reponse  = trim($_POST['reponse']);
    $ordre    = !empty($_POST['ordre']) ? intval($_POST['ordre']) : 1;

    if (!empty($question) && !empty($reponse)) {
        $stmt = $pdo->prepare("INSERT INTO faqs (question, reponse, ordre) VALUES (?, ?, ?)");
        $stmt->execute([$question, $reponse, $ordre]);
    }
}

 
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $pdo->prepare("DELETE FROM faqs WHERE id = ?")->execute([$id]);
    header("Location: faqs.php");
    exit();
}

 
$stmt = $pdo->query("SELECT * FROM faqs ORDER BY ordre ASC, id DESC");
$faqs = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQ - Admin</title>
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
        .nav-link { color: #b0b8c4; padding: 15px 25px; transition: 0.3s; }
        .nav-link:hover, .nav-link.active { color: var(--accent-yellow); background: rgba(255,255,255,0.05); border-left: 4px solid var(--accent-yellow); }
        .nav-link i { width: 25px; margin-right: 10px; }

        .main-content { padding: 2rem 3rem; }

        .card-custom { background: white; border-radius: 15px; border: none; box-shadow: 0 4px 6px rgba(0,0,0,0.02); padding: 25px; }

         
        .list-group-item { border: none; border-bottom: 1px solid #f0f0f0; padding: 20px 15px; }
        .list-group-item:last-child { border-bottom: none; }
        .faq-question { color: var(--sidebar-bg); font-weight: 600; display: block; margin-bottom: 5px; }
        .faq-answer { color: var(--text-muted); font-size: 0.9rem; white-space: pre-line; }  

        .btn-add { background: var(--accent-yellow); color: var(--sidebar-bg); border: none; border-radius: 50px; padding: 10px 25px; font-weight: 600; }
        .badge-ordre { background-color: #e9ecef; color: #495057; font-size: 0.75rem; padding: 4px 8px; border-radius: 4px; margin-right: 8px; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include 'sidebar.php'; ?>

        <div class="col-md-9 col-lg-10 main-content">
            <div class="d-flex justify-content-between align-items-center mb-5">
                <h2 class="fw-bold text-dark m-0">Gestion FAQ</h2>
            </div>

            <div class="row">
                <div class="col-md-5 mb-4">
                    <div class="card-custom h-100">
                        <h5 class="fw-bold mb-4">Nouvelle Question</h5>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label text-muted small text-uppercase fw-bold">Question</label>
                                <input type="text" name="question" class="form-control rounded-3" placeholder="Ex: Comment changer mon mot de passe ?" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label text-muted small text-uppercase fw-bold">Réponse</label>
                                <textarea name="reponse" class="form-control rounded-3" rows="5" placeholder="Votre réponse ici..." required></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label text-muted small text-uppercase fw-bold">Ordre d'affichage</label>
                                <input type="number" name="ordre" class="form-control rounded-3" value="1" min="1">
                            </div>

                            <button type="submit" name="add_faq" class="btn btn-add w-100 shadow-sm mt-2">Ajouter la question</button>
                        </form>
                    </div>
                </div>

                <div class="col-md-7 mb-4">
                    <div class="card-custom h-100">
                        <h5 class="fw-bold mb-4">Questions Fréquentes</h5>
                        <div style="max-height: 600px; overflow-y: auto;">
                            <ul class="list-group">
                                <?php if (count($faqs) > 0): ?>
                                    <?php foreach ($faqs as $faq): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-start">
                                            <div class="me-3">
                                                <span class="badge-ordre">#<?php echo $faq['ordre']; ?></span>
                                                <span class="faq-question"><?php echo htmlspecialchars($faq['question']); ?></span>
                                                <div class="faq-answer"><?php echo nl2br(htmlspecialchars($faq['reponse'])); ?></div>
                                            </div>
                                            <a href="?delete=<?php echo $faq['id']; ?>" class="btn btn-sm btn-outline-danger border-0" onclick="return confirm('Supprimer cette question ?');">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li class="list-group-item text-center text-muted py-5">
                                        <i class="fas fa-question-circle fa-2x mb-3 opacity-25"></i><br>
                                        Aucune question enregistrée.
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>