<?php
global $pdo;
require_once '../config/db.php';
require_once '../includes/functions.php';

require_role('gestionnaire');

 

 
$limit = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$where = "1=1";
$params = [];

 
$statut_filter = $_GET['statut'] ?? '';
if (!empty($statut_filter)) {
    $where .= " AND r.statut = ?";
    $params[] = $statut_filter;
}

 
$urgence_filter = $_GET['urgence'] ?? '';
if (!empty($urgence_filter)) {
    $where .= " AND r.urgence = ?";
    $params[] = $urgence_filter;
}

 
$date_filter = $_GET['date'] ?? '';
if (!empty($date_filter)) {
     
    $where .= " AND DATE(r.date_creation) = ?";
    $params[] = $date_filter;
}

 
$sql_count = "SELECT COUNT(*) FROM reclamations r WHERE $where";
$stmt_count = $pdo->prepare($sql_count);
$stmt_count->execute($params);
$total_records = $stmt_count->fetchColumn();
$total_pages = ceil($total_records / $limit);

 
$sql = "
    SELECT r.*, c.nom as categorie_nom, u.nom as user_nom, u.avatar 
    FROM reclamations r 
    JOIN categories c ON r.categorie_id = c.id 
    JOIN users u ON r.user_id = u.id 
    WHERE $where
    ORDER BY 
        CASE WHEN r.statut = 'en_attente' THEN 1 ELSE 2 END,
        CASE WHEN r.urgence = 'Haute' THEN 1 WHEN r.urgence = 'Moyenne' THEN 2 ELSE 3 END, 
        r.date_creation DESC
    LIMIT $limit OFFSET $offset
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reclamations = $stmt->fetchAll(PDO::FETCH_ASSOC);

 
try {
    $debut_mois = date('Y-m-01');
    $fin_mois   = date('Y-m-t');

    $sql_graph = "SELECT DATE(date_creation) as jour, COUNT(*) as cnt 
                  FROM reclamations 
                  WHERE date_creation BETWEEN ? AND ? 
                  GROUP BY DATE(date_creation)";
    $stmt_graph = $pdo->prepare($sql_graph);
    $stmt_graph->execute([$debut_mois, $fin_mois]);
    $raw_data = $stmt_graph->fetchAll(PDO::FETCH_ASSOC);

    $dates_labels = [];
    $data_counts = [];

    $period = new DatePeriod(
            new DateTime($debut_mois),
            new DateInterval('P1D'),
            (new DateTime($fin_mois))->modify('+1 day')
    );

    foreach ($period as $dt) {
        $current_date = $dt->format('Y-m-d');
        $dates_labels[] = $dt->format('d/m');

        $count = 0;
        foreach ($raw_data as $row) {
            if ($row['jour'] === $current_date) {
                $count = $row['cnt'];
                break;
            }
        }
        $data_counts[] = $count;
    }

} catch (Exception $e) {
    $dates_labels = [];
    $data_counts = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réclamations - Gestionnaire</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root { --sidebar-bg: #051b34; --body-bg: #f8f9fa; --accent-yellow: #ffc107; --text-dark: #2c3e50; }
        body { font-family: 'Poppins', sans-serif; background-color: var(--body-bg); overflow-x: hidden; }

        .sidebar { background-color: var(--sidebar-bg); min-height: 100vh; color: white; padding-top: 2rem; }
        .nav-link { color: #b0b8c4; padding: 15px 25px; transition: 0.3s; }
        .nav-link:hover { color: white; background: rgba(255,255,255,0.05); }
        .nav-link.active { color: var(--accent-yellow); background: linear-gradient(90deg, rgba(255,193,7,0.1) 0%, rgba(255,193,7,0) 100%); border-left: 4px solid var(--accent-yellow); }
        .nav-link i { width: 25px; margin-right: 10px; }
        .main-content { padding: 2rem 3rem; }

        .chart-card {
            background: white; border-radius: 16px; padding: 20px 25px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03); margin-bottom: 30px;
            border: 1px solid rgba(0,0,0,0.02);
            position: relative;
        }

        .filter-bar {
            background: white; border-radius: 16px; padding: 15px 25px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03); margin-bottom: 30px;
            display: flex; align-items: center; gap: 20px; border: 1px solid rgba(0,0,0,0.02);
        }
         
        .form-select-custom, .form-control-custom {
            border: 1px solid #eee; border-radius: 10px; padding: 10px 15px;
            background-color: #fcfcfc; font-size: 0.9rem; font-weight: 500; color: #555;
            cursor: pointer; transition: 0.3s; width: 100%;
        }
        .form-select-custom:focus, .form-control-custom:focus {
            box-shadow: 0 0 0 3px rgba(5, 27, 52, 0.1); border-color: var(--sidebar-bg); outline: none;
        }
        .form-label-custom { font-size: 0.75rem; text-transform: uppercase; color: #999; font-weight: 700; margin-bottom: 5px; letter-spacing: 0.5px; }

        .table-container { background: white; border-radius: 20px; box-shadow: 0 5px 25px rgba(0,0,0,0.04); overflow: hidden; border: 1px solid rgba(0,0,0,0.02); margin-bottom: 30px; }
        .table-modern { width: 100%; border-collapse: separate; border-spacing: 0; }
        .table-modern thead th { background: #fdfdfd; padding: 20px 25px; font-size: 0.75rem; text-transform: uppercase; color: #8898aa; font-weight: 700; border-bottom: 1px solid #f0f0f0; }
        .table-modern tbody td { padding: 20px 25px; vertical-align: middle; border-bottom: 1px solid #f9f9f9; color: var(--text-dark); font-size: 0.95rem; transition: 0.2s; }
        .table-modern tbody tr:last-child td { border-bottom: none; }
        .table-modern tbody tr:hover td { background-color: #fafbfc; }

        .badge-soft { padding: 6px 12px; border-radius: 30px; font-weight: 600; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 6px; }
        .badge-soft-success { background: #e0fbf2; color: #10b981; }
        .badge-soft-warning { background: #fffbeb; color: #f59e0b; }
        .badge-soft-danger { background: #fef2f2; color: #ef4444; }
        .badge-soft-secondary { background: #f3f4f6; color: #6b7280; }
        .badge-soft-info { background: #eff6ff; color: #3b82f6; }

        .avatar-circle { width: 40px; height: 40px; border-radius: 50%; background: #eef2f7; color: #5e72e4; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.9rem; margin-right: 15px; }
        .btn-action { width: 35px; height: 35px; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #8898aa; background: white; border: 1px solid #e2e8f0; transition: 0.2s; }
        .btn-action:hover { background: var(--sidebar-bg); color: white; border-color: var(--sidebar-bg); }

        .pagination .page-link { color: var(--sidebar-bg); border: none; margin: 0 5px; border-radius: 50%; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; font-weight: 600; }
        .pagination .page-item.active .page-link { background-color: var(--sidebar-bg); color: var(--accent-yellow); box-shadow: 0 3px 10px rgba(5, 27, 52, 0.2); }
        .pagination .page-link:hover { background-color: #eef2f7; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <?php include 'sidebar.php'; ?>

        <div class="col-md-9 col-lg-10 main-content">

            <div class="d-flex justify-content-between align-items-end mb-4">
                <div>
                    <h2 class="fw-bold text-dark mb-1">Gestion des Réclamations</h2>
                    <p class="text-muted small m-0">Gérez les demandes et suivez les incidents de la résidence.</p>
                </div>
                <div class="text-end">
                    <span class="badge bg-dark rounded-pill px-3 py-2"><?php echo $total_records; ?> Total</span>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-12">
                    <div class="chart-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold text-secondary" style="font-size: 1rem; text-transform: uppercase; letter-spacing: 0.5px;">
                                <i class="fas fa-chart-line me-2"></i> Évolution des réclamations (<?php echo date('F Y'); ?>)
                            </h5>
                        </div>
                        <div style="height: 250px; width: 100%;">
                            <canvas id="reclamationsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="filter-bar">
                <form method="GET" class="w-100 d-flex flex-wrap align-items-end gap-3">
                    <input type="hidden" name="page" value="1">

                    <div class="flex-grow-1" style="max-width: 200px;">
                        <div class="form-label-custom"><i class="fas fa-filter me-1"></i> Statut</div>
                        <select name="statut" class="form-select form-select-custom" onchange="this.form.submit()">
                            <option value="">Tous les statuts</option>
                            <option value="en_attente" <?php if ($statut_filter == 'en_attente') echo 'selected'; ?>>En attente</option>
                            <option value="en_cours" <?php if ($statut_filter == 'en_cours') echo 'selected'; ?>>En cours</option>
                            <option value="resolu" <?php if ($statut_filter == 'resolu') echo 'selected'; ?>>Résolu</option>
                            <option value="rejete" <?php if ($statut_filter == 'rejete') echo 'selected'; ?>>Rejeté</option>
                        </select>
                    </div>

                    <div class="flex-grow-1" style="max-width: 200px;">
                        <div class="form-label-custom"><i class="fas fa-fire me-1"></i> Urgence</div>
                        <select name="urgence" class="form-select form-select-custom" onchange="this.form.submit()">
                            <option value="">Tous les niveaux</option>
                            <option value="Haute" <?php if ($urgence_filter == 'Haute') echo 'selected'; ?>>Haute Urgence</option>
                            <option value="Moyenne" <?php if ($urgence_filter == 'Moyenne') echo 'selected'; ?>>Moyenne</option>
                            <option value="Faible" <?php if ($urgence_filter == 'Faible') echo 'selected'; ?>>Faible</option>
                        </select>
                    </div>

                    <div class="flex-grow-1" style="max-width: 200px;">
                        <div class="form-label-custom"><i class="fas fa-calendar-alt me-1"></i> Date</div>
                        <input type="date" name="date" class="form-control form-control-custom" value="<?php echo htmlspecialchars($date_filter); ?>" onchange="this.form.submit()">
                    </div>

                    <div class="ms-auto d-flex gap-2">
                        <a href="generate_pdf.php?statut=<?php echo $statut_filter; ?>&urgence=<?php echo $urgence_filter; ?>&date=<?php echo $date_filter; ?>" target="_blank" class="btn btn-dark text-white fw-bold px-4 rounded-pill shadow-sm">
                            <i class="fas fa-file-pdf me-2"></i> PDF
                        </a>

                        <?php if(!empty($statut_filter) || !empty($urgence_filter) || !empty($date_filter)): ?>
                            <a href="reclamations.php" class="btn btn-light text-muted border fw-bold px-4 rounded-pill">
                                <i class="fas fa-times me-2"></i> Effacer
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="table-container">
                <div class="table-responsive">
                    <table class="table-modern">
                        <thead>
                        <tr>
                            <th>Réclamant</th>
                            <th>Sujet & Lieu</th>
                            <th>Urgence</th>
                            <th>Catégorie</th>
                            <th>Statut</th>
                            <th class="text-end">Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (count($reclamations) > 0): ?>
                            <?php foreach ($reclamations as $rec): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle">
                                                <?php echo strtoupper(substr($rec['user_nom'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($rec['user_nom']); ?></div>
                                                <div class="small text-muted" style="font-size: 0.75rem;">
                                                    <?php echo date('d M Y', strtotime($rec['date_creation'])); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark mb-1"><?php echo htmlspecialchars($rec['objet']); ?></div>
                                        <?php if(!empty($rec['lieu'])): ?>
                                            <div class="small text-muted">
                                                <i class="fas fa-map-marker-alt text-danger me-1"></i>
                                                <?php echo htmlspecialchars($rec['lieu']); ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="small text-muted opacity-50">Lieu non précisé</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $u = $rec['urgence'] ?? 'Faible';
                                        if ($u === 'Haute') {
                                            echo '<span class="badge-soft badge-soft-danger"><i class="fas fa-fire"></i> Haute</span>';
                                        } elseif ($u === 'Moyenne') {
                                            echo '<span class="badge-soft badge-soft-warning"><i class="fas fa-bolt"></i> Moyenne</span>';
                                        } else {
                                            echo '<span class="badge-soft badge-soft-secondary"><i class="fas fa-coffee"></i> Faible</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                            <span class="small fw-bold text-uppercase text-secondary ls-1">
                                                <?php echo htmlspecialchars($rec['categorie_nom']); ?>
                                            </span>
                                    </td>
                                    <td>
                                        <?php
                                        $s = $rec['statut'];
                                        $cls = 'badge-soft-secondary';
                                        $txt = $s;
                                        $icon = 'fa-circle';

                                        if ($s === 'resolu') { $cls = 'badge-soft-success'; $txt = 'Réglée'; $icon = 'fa-check'; }
                                        elseif ($s === 'en_cours') { $cls = 'badge-soft-info'; $txt = 'En cours'; $icon = 'fa-spinner fa-spin'; }
                                        elseif ($s === 'en_attente') { $cls = 'badge-soft-warning'; $txt = 'En attente'; $icon = 'fa-clock'; }
                                        elseif ($s === 'rejete') { $cls = 'badge-soft-danger'; $txt = 'Annulée'; $icon = 'fa-times'; }
                                        ?>
                                        <span class="badge-soft <?php echo $cls; ?>">
                                                <i class="fas <?php echo $icon; ?>"></i> <?php echo $txt; ?>
                                            </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="edit.php?id=<?php echo $rec['id']; ?>" class="btn-action d-inline-flex text-decoration-none" title="Traiter">
                                            <i class="fas fa-arrow-right"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="opacity-25 mb-3"><i class="fas fa-filter fa-3x"></i></div>
                                    <h6 class="text-muted">Aucun résultat pour ces critères.</h6>
                                </td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&statut=<?php echo $statut_filter; ?>&urgence=<?php echo $urgence_filter; ?>&date=<?php echo $date_filter; ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&statut=<?php echo $statut_filter; ?>&urgence=<?php echo $urgence_filter; ?>&date=<?php echo $date_filter; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&statut=<?php echo $statut_filter; ?>&urgence=<?php echo $urgence_filter; ?>&date=<?php echo $date_filter; ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const ctx = document.getElementById('reclamationsChart').getContext('2d');
    const labels = <?php echo json_encode($dates_labels); ?>;
    const dataCounts = <?php echo json_encode($data_counts); ?>;

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Réclamations',
                data: dataCounts,
                borderColor: '#06b6d4',
                backgroundColor: 'rgba(6, 182, 212, 0.1)',
                borderWidth: 2,
                pointBackgroundColor: '#3b82f6',
                pointBorderColor: '#051b34',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { borderDash: [5, 5] } },
                x: { grid: { display: false }, ticks: { maxTicksLimit: 10 } }
            }
        }
    });
</script>
</body>
</html>