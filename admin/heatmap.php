<?php
global $pdo;
require_once '../config/db.php';
require_once '../includes/functions.php';

require_role('admin');

 
 
$sql = "SELECT lieu, COUNT(*) as total 
        FROM reclamations 
        WHERE lieu IS NOT NULL AND lieu != '' 
        GROUP BY lieu 
        ORDER BY total DESC";
$stmt = $pdo->query($sql);
$zones = $stmt->fetchAll(PDO::FETCH_ASSOC);

 
 
$max_pannes = 0;
foreach ($zones as $z) {
    if ($z['total'] > $max_pannes) {
        $max_pannes = $z['total'];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carte Thermique - Admin</title>
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

         
        .heat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }

        .heat-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            transition: transform 0.3s ease;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
        }

        .heat-card:hover { transform: translateY(-5px); }

        .heat-score {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 10px 0;
            color: #333;
        }

        .heat-label {
            font-size: 1rem;
            font-weight: 600;
            color: #555;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

         
        .intensity-bar {
            height: 6px;
            width: 100%;
            background: #eee;
            border-radius: 10px;
            margin-top: 10px;
            overflow: hidden;
        }
        .intensity-fill { height: 100%; border-radius: 10px; }

         
        .legend-box { display: flex; gap: 15px; align-items: center; background: white; padding: 10px 20px; border-radius: 50px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .dot { width: 12px; height: 12px; border-radius: 50%; display: inline-block; margin-right: 5px; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include 'sidebar.php'; ?>

        <div class="col-md-9 col-lg-10 main-content">

            <div class="d-flex justify-content-between align-items-center mb-5">
                <div>
                    <h2 class="fw-bold text-dark m-0">Carte Thermique</h2>
                    <p class="text-muted m-0">Identification des zones à risque de la cité.</p>
                </div>

                <div class="legend-box d-none d-md-flex">
                    <small><span class="dot" style="background: #4caf50;"></span>Bon état</small>
                    <small><span class="dot" style="background: #ff9800;"></span>Attention</small>
                    <small><span class="dot" style="background: #f44336;"></span>Critique</small>
                </div>
            </div>

            <?php if (count($zones) > 0): ?>
                <div class="heat-grid">
                    <?php foreach ($zones as $zone): ?>
                        <?php
                         
                        $percent = ($max_pannes > 0) ? ($zone['total'] / $max_pannes) * 100 : 0;

                         
                         
                        $color = '#4caf50';
                        $bg_light = 'rgba(76, 175, 80, 0.1)';
                        $border_color = 'transparent';

                        if ($percent > 70) {
                             
                            $color = '#f44336';
                            $bg_light = 'rgba(244, 67, 54, 0.1)';
                            $border_color = '#f44336';
                        } elseif ($percent > 30) {
                             
                            $color = '#ff9800';
                            $bg_light = 'rgba(255, 152, 0, 0.1)';
                            $border_color = 'transparent';
                        }
                        ?>

                        <div class="heat-card" style="background-color: <?php echo $bg_light; ?>; border-color: <?php echo $border_color; ?>">
                            <div class="heat-label"><?php echo htmlspecialchars($zone['lieu']); ?></div>

                            <div class="heat-score" style="color: <?php echo $color; ?>">
                                <?php echo $zone['total']; ?>
                            </div>
                            <div class="small text-muted">incidents signalés</div>

                            <div class="intensity-bar">
                                <div class="intensity-fill" style="width: <?php echo $percent; ?>%; background-color: <?php echo $color; ?>;"></div>
                            </div>
                        </div>

                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-map-signs fa-3x mb-3 opacity-25"></i>
                    <h4>Aucune donnée de localisation disponible.</h4>
                </div>
            <?php endif; ?>

            <div class="mt-5 p-4 bg-white rounded-4 shadow-sm border-start border-4 border-danger">
                <h5 class="fw-bold text-dark"><i class="fas fa-lightbulb text-warning me-2"></i>Analyse Automatique</h5>
                <p class="text-muted mb-0">
                    Les zones affichées en <strong class="text-danger">Rouge</strong> représentent les lieux cumulant plus de 70% du volume maximal des pannes.
                    Il est recommandé de planifier une inspection technique prioritaire pour ces endroits.
                </p>
            </div>

        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>