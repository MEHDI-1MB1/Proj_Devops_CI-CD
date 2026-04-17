<?php
 
global $pdo;
require_once '../config/db.php';
require_once '../includes/functions.php';

require_role('reclamant');

$user_id = $_SESSION['user_id'];

 
$stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
$stmt->execute([$user_id]);

 
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historique des Notifications - ReCité</title>
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

         
        .notif-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            border: 1px solid #f0f0f0;
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex;
            align-items: flex-start;
            gap: 20px;
        }
        .notif-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .notif-icon-box {
            width: 50px; height: 50px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

         
        .type-reclamation_statut { background: #e3f2fd; color: #1565c0; border-left: 4px solid #1565c0; }
        .type-reclamation_commentaire { background: #e8f5e9; color: #2e7d32; border-left: 4px solid #2e7d32; }
        .type-annonce { background: #fff8e1; color: #f57f17; border-left: 4px solid #f57f17; }
        .type-default { background: #f5f5f5; color: #616161; border-left: 4px solid #616161; }

        .notif-date { font-size: 0.8rem; color: #999; margin-top: 5px; display: block; }
        .btn-go { background: var(--sidebar-bg); color: white; border-radius: 20px; padding: 5px 15px; font-size: 0.85rem; text-decoration: none; transition: 0.3s; }
        .btn-go:hover { background: var(--accent-yellow); color: var(--sidebar-bg); }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <?php include 'sidebar.php'; ?>

        <div class="col-md-9 col-lg-10 main-content">

            <div class="d-flex justify-content-between align-items-center mb-5">
                <div>
                    <h2 class="fw-bold text-dark m-0">Historique des Notifications</h2>
                    <p class="text-muted m-0">Retrouvez toutes vos alertes passées.</p>
                </div>
                <a href="dashboard.php" class="btn btn-outline-secondary rounded-pill px-4">Retour</a>
            </div>

            <div class="row">
                <div class="col-lg-10 mx-auto">

                    <?php if (count($notifications) > 0): ?>
                        <?php foreach ($notifications as $n): ?>
                            <?php
                             
                            $style = 'type-default';
                            $icon = 'fa-bell';
                            $label = 'Notification';

                            if ($n['type'] == 'reclamation_statut') {
                                $style = 'type-reclamation_statut';
                                $icon = 'fa-sync-alt';
                                $label = 'Mise à jour Statut';
                            } elseif ($n['type'] == 'reclamation_commentaire') {
                                $style = 'type-reclamation_commentaire';
                                $icon = 'fa-comment-dots';
                                $label = 'Nouveau Message';
                            } elseif ($n['type'] == 'annonce') {
                                $style = 'type-annonce';
                                $icon = 'fa-bullhorn';
                                $label = 'Annonce Syndic';
                            }
                            ?>

                            <div class="notif-card <?php echo $style; ?>">
                                <div class="notif-icon-box <?php echo $style; ?>" style="background: transparent; border: none;">
                                    <i class="fas <?php echo $icon; ?>"></i>
                                </div>

                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <h6 class="fw-bold mb-0 text-uppercase small text-muted"><?php echo $label; ?></h6>
                                        <small class="text-muted"><i class="far fa-clock me-1"></i> <?php echo date('d/m/Y à H:i', strtotime($n['created_at'])); ?></small>
                                    </div>
                                    <p class="mb-2 text-dark fw-medium" style="font-size: 1.05rem;">
                                        <?php echo htmlspecialchars($n['message']); ?>
                                    </p>
                                </div>

                                <?php if (!empty($n['lien'])): ?>
                                    <div class="align-self-center">
                                        <a href="<?php echo htmlspecialchars($n['lien']); ?>" class="btn-go">
                                            Voir <i class="fas fa-arrow-right ms-1"></i>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>

                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="far fa-bell-slash fa-3x text-muted mb-3 opacity-25"></i>
                            <h5 class="text-muted">Aucune notification dans l'historique.</h5>
                        </div>
                    <?php endif; ?>

                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>