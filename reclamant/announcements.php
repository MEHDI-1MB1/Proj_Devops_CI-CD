<?php
 
global $pdo;
require_once '../config/db.php';
require_once '../includes/functions.php';

 
require_role('reclamant');

 
 
try {
    $stmt = $pdo->query("SELECT * FROM annonces ORDER BY date_evenement DESC, date_creation DESC");
    $annonces = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $annonces = [];
    $error = "Erreur de chargement : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Annonces - ReCité</title>
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

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--body-bg);
            overflow-x: hidden;
        }

         
        .sidebar { background-color: var(--sidebar-bg); min-height: 100vh; color: white; padding-top: 2rem; }
        .user-profile-card { background: rgba(255, 255, 255, 0.1); border-radius: 12px; padding: 10px 15px; margin: 0 20px 40px 20px; }
        .nav-link { color: #b0b8c4; padding: 15px 25px; transition: 0.3s; border-left: 4px solid transparent; }
        .nav-link:hover { color: white; background: rgba(255,255,255,0.05); }
        .nav-link.active { color: var(--accent-yellow); background: linear-gradient(90deg, rgba(255,193,7,0.1) 0%, rgba(255,193,7,0) 100%); border-left: 4px solid var(--accent-yellow); }
        .nav-link i { width: 25px; margin-right: 10px; }

         
        .main-content { padding: 2rem 3rem; }

         
        .announcement-card {
            background: white;
            border-radius: 15px;
            border: none;
            box-shadow: 0 5px 20px rgba(0,0,0,0.03);
            margin-bottom: 25px;
            padding: 0;
            overflow: hidden;
            transition: transform 0.3s;
        }
        .announcement-card:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(0,0,0,0.06); }

        .ann-header { padding: 20px 25px; border-bottom: 1px solid #f0f0f0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
        .ann-body { padding: 25px; font-size: 0.95rem; color: #555; line-height: 1.6; }

         
        .icon-box {
            width: 45px; height: 45px; border-radius: 10px; display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem; margin-right: 15px; flex-shrink: 0;
        }

         
        .theme-maintenance .icon-box { background: #fff8e1; color: #ffa000; }
        .theme-maintenance .date-badge { border-color: #ffa000; color: #ffa000; }

        .theme-reunion .icon-box { background: #e3f2fd; color: #1e88e5; }
        .theme-reunion .date-badge { border-color: #1e88e5; color: #1e88e5; }

        .theme-urgence .icon-box { background: #ffebee; color: #e53935; }
        .theme-urgence .date-badge { border-color: #e53935; color: #e53935; }

        .theme-info .icon-box { background: #f5f5f5; color: #616161; }
        .theme-info .date-badge { border-color: #e0e0e0; color: #757575; }

        .date-badge { background: #fff; border: 1px solid #eee; padding: 5px 15px; border-radius: 50px; font-size: 0.85rem; font-weight: 600; }
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
                    <p class="text-muted small m-0">Restez informé de la vie de la résidence</p>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-10 mx-auto">

                    <?php if (count($annonces) > 0): ?>
                        <?php foreach ($annonces as $ann): ?>
                            <?php
                             
                            $themeClass = 'theme-info';
                            $iconClass = 'fa-info-circle';

                            switch($ann['type']) {
                                case 'maintenance':
                                    $themeClass = 'theme-maintenance';
                                    $iconClass = 'fa-tools';
                                    break;
                                case 'reunion':
                                    $themeClass = 'theme-reunion';
                                    $iconClass = 'fa-users';
                                    break;
                                case 'urgence':
                                    $themeClass = 'theme-urgence';
                                    $iconClass = 'fa-exclamation-triangle';
                                    break;
                                default:
                                    $themeClass = 'theme-info';
                                    $iconClass = 'fa-newspaper';
                            }
                            ?>

                            <div class="announcement-card <?php echo $themeClass; ?>">
                                <div class="ann-header">
                                    <div class="d-flex align-items-center">
                                        <div class="icon-box">
                                            <i class="fas <?php echo $iconClass; ?>"></i>
                                        </div>
                                        <h5 class="fw-bold m-0 text-dark"><?php echo htmlspecialchars($ann['titre']); ?></h5>
                                    </div>
                                    <span class="date-badge">
                                        <i class="far fa-calendar-alt me-2"></i>
                                        <?php
                                        $dateObj = new DateTime($ann['date_evenement']);
                                        echo $dateObj->format('d/m/Y');
                                        ?>
                                    </span>
                                </div>
                                <div class="ann-body">
                                    <?php echo nl2br(htmlspecialchars($ann['description'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div class="text-center mt-5 text-muted small">
                            <i class="fas fa-check-circle me-2"></i> Vous êtes à jour avec les dernières annonces.
                        </div>

                    <?php else: ?>
                        <div class="text-center py-5">
                            <div class="text-muted opacity-50 mb-3"><i class="far fa-newspaper fa-3x"></i></div>
                            <h5 class="text-muted">Aucune annonce pour le moment.</h5>
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