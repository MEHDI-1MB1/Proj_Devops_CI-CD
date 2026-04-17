<?php
global $pdo;
require_once '../config/db.php';
require_once '../includes/functions.php';

require_role('admin');

 
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$notifs = $stmt->fetchAll(PDO::FETCH_ASSOC);

 
 





?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Admin</title>
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
        .nav-link:hover { color: white; background: rgba(255,255,255,0.05); }
        .nav-link i { width: 25px; margin-right: 10px; }

        .main-content { padding: 2rem 3rem; }

        .card-custom { background: white; border-radius: 15px; border: none; box-shadow: 0 4px 6px rgba(0,0,0,0.02); padding: 0; overflow: hidden; }

         
        .list-group-item {
            padding: 20px 25px;
            border: none;
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.2s;
        }
        .list-group-item:last-child { border-bottom: none; }
        .list-group-item:hover { background-color: #fcfcfc; }

        .notif-unread { background-color: #fff9e6; }  
        .notif-unread .notif-text { font-weight: 600; color: #333; }

        .notif-icon {
            width: 45px; height: 45px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin-right: 20px;
            font-size: 1.1rem;
        }
        .time-badge { font-size: 0.8rem; color: #999; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include 'sidebar.php'; ?>

        <div class="col-md-9 col-lg-10 main-content">
            <div class="d-flex justify-content-between align-items-center mb-5">
                <h2 class="fw-bold text-dark m-0">Historique des Notifications</h2>
                <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="markAllRead()">
                    <i class="fas fa-check-double me-2"></i> Tout marquer comme lu
                </button>
            </div>

            <div class="card-custom">
                <?php if (count($notifs) > 0): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach($notifs as $n): ?>
                            <?php
                             
                            $iconClass = 'fa-info';
                            $bgClass = 'bg-light text-muted';

                            if (strpos($n['type'], 'new_user') !== false) {
                                $iconClass = 'fa-user-plus';
                                $bgClass = 'bg-primary bg-opacity-10 text-primary';
                            } elseif (strpos($n['type'], 'urgence') !== false) {
                                $iconClass = 'fa-exclamation-triangle';
                                $bgClass = 'bg-danger bg-opacity-10 text-danger';
                            }
                            ?>

                            <div class="list-group-item d-flex align-items-center <?php echo ($n['is_read'] == 0) ? 'notif-unread' : ''; ?>">
                                <div class="notif-icon <?php echo $bgClass; ?>">
                                    <i class="fas <?php echo $iconClass; ?>"></i>
                                </div>

                                <div class="flex-grow-1">
                                    <div class="notif-text mb-1"><?php echo htmlspecialchars($n['message']); ?></div>
                                    <div class="time-badge">
                                        <i class="far fa-clock me-1"></i>
                                        <?php echo date('d/m/Y à H:i', strtotime($n['created_at'])); ?>
                                    </div>
                                </div>

                                <?php if ($n['is_read'] == 0): ?>
                                    <div class="ms-3">
                                        <span class="badge bg-warning rounded-circle p-1" style="width: 10px; height: 10px; display: block;"></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="p-5 text-center text-muted">
                        <i class="far fa-bell-slash fa-3x mb-3 opacity-25"></i>
                        <h5>Aucune notification</h5>
                        <p>Votre historique est vide pour le moment.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function markAllRead() {
        if(confirm("Voulez-vous marquer toutes les notifications comme lues ?")) {
            fetch('../mark_read.php')
                .then(response => {
                    if (response.ok) {
                        location.reload(); 
                    }
                });
        }
    }
</script>
</body>
</html>