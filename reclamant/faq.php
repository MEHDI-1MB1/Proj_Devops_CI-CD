<?php
global $pdo;
require_once '../config/db.php';
require_once '../includes/functions.php';

require_role('reclamant');

$user_id = $_SESSION['user_id'];

// --- RÉCUPÉRATION DES FAQ DEPUIS LA BDD ---
$stmt = $pdo->query("SELECT * FROM faqs ORDER BY ordre ASC");
$faqs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aide & FAQ - ReCité</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --sidebar-bg: #051b34; --body-bg: #f3f5f9; --accent-yellow: #ffc107; --text-muted: #6c757d; }
        body { font-family: 'Poppins', sans-serif; background-color: var(--body-bg); overflow-x: hidden; }

        .sidebar { background-color: var(--sidebar-bg); min-height: 100vh; color: white; padding-top: 2rem; }
        .user-profile-card { background: rgba(255, 255, 255, 0.1); border-radius: 12px; padding: 10px 15px; margin: 0 20px 40px 20px; }
        .nav-link { color: #b0b8c4; padding: 15px 25px; transition: 0.3s; border-left: 4px solid transparent; }
        .nav-link:hover { color: white; background: rgba(255,255,255,0.05); }
        .nav-link.active { color: var(--accent-yellow); background: linear-gradient(90deg, rgba(255,193,7,0.1) 0%, rgba(255,193,7,0) 100%); border-left: 4px solid var(--accent-yellow); }
        .nav-link i { width: 25px; margin-right: 10px; }
        .main-content { padding: 2rem 3rem; }

        /* Processus */
        .process-card { background: white; border: none; border-radius: 20px; padding: 30px 20px; text-align: center; height: 100%; box-shadow: 0 5px 15px rgba(0,0,0,0.03); transition: transform 0.3s ease; position: relative; }
        .process-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.08); }
        .step-badge { width: 40px; height: 40px; background-color: var(--sidebar-bg); color: var(--accent-yellow); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.1rem; margin: 0 auto 20px auto; }
        .process-icon-wrapper { width: 50px; height: 50px; background-color: #f0f2f5; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px auto; color: #b0b8c4; font-size: 1.2rem; }
        .process-title { font-weight: 700; color: #051b34; margin-bottom: 15px; }
        .process-text { font-size: 0.85rem; color: #6c757d; line-height: 1.6; }

        /* FAQ Dynamique */
        .accordion-item { border: none; border-radius: 15px !important; margin-bottom: 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.02); overflow: hidden; }
        .accordion-button { font-weight: 600; color: #051b34; background-color: white; padding: 20px; border: none; box-shadow: none !important; }
        .accordion-button:not(.collapsed) { background-color: rgba(255, 193, 7, 0.1); color: #051b34; }
        .accordion-button:after { background-image: url("data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23051b34'><path fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/></svg>"); }
        .accordion-body { font-size: 0.95rem; color: #555; line-height: 1.7; padding: 20px; background-color: white; border-top: 1px solid #f0f0f0; }

        .contact-box { background: var(--sidebar-bg); color: white; border-radius: 20px; padding: 30px; text-align: center; }
        .btn-contact { background: var(--accent-yellow); color: var(--sidebar-bg); font-weight: 700; border-radius: 50px; padding: 10px 30px; border: none; transition: 0.3s; }
        .btn-contact:hover { background: #e0a800; transform: translateY(-2px); }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <?php include 'sidebar.php'; ?>

        <div class="col-md-9 col-lg-10 main-content">

            <div class="d-flex justify-content-between align-items-center mb-5">
                <div>
                    <h2 class="fw-bold text-dark m-0">Aide & Support</h2>
                    <p class="text-muted m-0">Comment utiliser ReCité et réponses aux questions fréquentes.</p>
                </div>

            </div>

            <div class="mb-5">
                <h4 class="fw-bold text-dark mb-2">Comment ça marche ?</h4>


                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="process-card">
                            <div class="step-badge">1</div>
                            <div class="process-title">Déclarer</div>
                            <div class="process-icon-wrapper"><i class="fas fa-plus"></i></div>
                            <p class="process-text">Cliquez sur <strong>"Réclamer"</strong>, remplissez le formulaire (Titre, Type, Photo) et validez.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="process-card">
                            <div class="step-badge">2</div>
                            <div class="process-title">Traitement</div>
                            <div class="process-icon-wrapper"><i class="fas fa-sync-alt fa-spin"></i></div>
                            <p class="process-text">Nous recevons votre demande. Le statut passe <strong>"En cours"</strong>. Un technicien est désigné.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="process-card">
                            <div class="step-badge">3</div>
                            <div class="process-title">Résolution</div>
                            <div class="process-icon-wrapper text-success" style="background-color: #e8f5e9;"><i class="fas fa-check"></i></div>
                            <p class="process-text">Une fois réparé, vous recevez une notification. Vous pouvez confirmer et <strong>noter l'intervention</strong>.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <h4 class="fw-bold text-dark mb-4">Questions Fréquentes</h4>
                <div class="col-lg-8">


                    <div class="accordion" id="faqAccordion">
                        <?php if (count($faqs) > 0): ?>
                            <?php foreach ($faqs as $index => $f): ?>
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button <?php echo ($index !== 0) ? 'collapsed' : ''; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#faq<?php echo $f['id']; ?>">
                                            <?php echo htmlspecialchars($f['question']); ?>
                                        </button>
                                    </h2>
                                    <div id="faq<?php echo $f['id']; ?>" class="accordion-collapse collapse <?php echo ($index === 0) ? 'show' : ''; ?>" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body">
                                            <?php echo nl2br(html_entity_decode($f['reponse'])); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="alert alert-light text-center text-muted border">
                                Aucune question disponible pour le moment.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="contact-box" style="position: sticky; top: 2rem;">
                        <div class="mb-3"><i class="fas fa-headset fa-3x" style="color: var(--accent-yellow);"></i></div>
                        <h4 class="fw-bold mb-3">Besoin d'aide ?</h4>
                        <p class="small text-white-50 mb-4">Vous ne trouvez pas la réponse à votre question ? Notre équipe technique est disponible.</p>
                        <a href="mailto:support@recite.ma" class="btn btn-contact w-100 mb-3">Contacter le support</a>
                        <div class="small text-white-50"><i class="fas fa-phone me-2"></i> 05 22 00 00 00</div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>