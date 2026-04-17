<?php
global $pdo;
require_once '../config/db.php';
require_once '../includes/functions.php';

require_role('admin');

 

 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $nom = trim($_POST['nom']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $password_raw = $_POST['password'];
    $password = password_hash($password_raw, PASSWORD_DEFAULT);

    if (!empty($nom) && !empty($email) && !empty($password_raw)) {
        $stmt = $pdo->prepare("INSERT INTO users (nom, email, password, role) VALUES (?, ?, ?, ?)");
        try {
            $stmt->execute([$nom, $email, $password, $role]);
            $success = "Utilisateur ajouté avec succès.";
        } catch (Exception $e) {
            $error = "Erreur: " . $e->getMessage();
        }
    }
}

 
if (isset($_POST['delete_user'])) {
    $id = $_POST['user_id'];
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    try {
        $stmt->execute([$id]);
        $success = "Utilisateur supprimé avec succès.";
    } catch (Exception $e) {
        $error = "Erreur de suppression: " . $e->getMessage();
    }
}

 
if (isset($_POST['edit_user'])) {
    $id = $_POST['user_id'];
    $nom = trim($_POST['nom']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $password_raw = $_POST['password'];

    $sql = "UPDATE users SET nom = ?, email = ?, role = ? WHERE id = ?";
    $params = [$nom, $email, $role, $id];

    if (!empty($password_raw)) {
        $sql = "UPDATE users SET nom = ?, email = ?, role = ?, password = ? WHERE id = ?";
        $params = [$nom, $email, $role, password_hash($password_raw, PASSWORD_DEFAULT), $id];
    }

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $success = "Utilisateur modifié avec succès.";
    } catch(Exception $e) {
        $error = "Erreur modification: " . $e->getMessage();
    }
}

 

 
$limit = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

 
$where = "1=1";
$params = [];

 
$role_filter = $_GET['role'] ?? '';
if (!empty($role_filter)) {
    $where .= " AND role = :role";
    $params[':role'] = $role_filter;
}

 
$search_filter = $_GET['search'] ?? '';
if (!empty($search_filter)) {
    $where .= " AND (nom LIKE :search OR email LIKE :search)";
    $params[':search'] = "%$search_filter%";
}

 
$sql_count = "SELECT COUNT(*) FROM users WHERE $where";
$stmt_count = $pdo->prepare($sql_count);
foreach($params as $key => $val) {
    $stmt_count->bindValue($key, $val);
}
$stmt_count->execute();
$total_records = $stmt_count->fetchColumn();
$total_pages = ceil($total_records / $limit);

 
$sql = "SELECT * FROM users WHERE $where ORDER BY id DESC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

 
try {
     
    $start_date = (new DateTime('first day of this month'))->modify('-5 months');  
    $end_date   = new DateTime('last day of this month');
    
    $debut_str = $start_date->format('Y-m-d 00:00:00');
    $fin_str   = $end_date->format('Y-m-d 23:59:59');

     
    $baseline = ['admin' => 0, 'gestionnaire' => 0, 'reclamant' => 0];
    $sql_base = "SELECT role, COUNT(*) as cnt FROM users WHERE date_creation < ? GROUP BY role";
    $stmt_base = $pdo->prepare($sql_base);
    $stmt_base->execute([$debut_str]);
    while($row = $stmt_base->fetch(PDO::FETCH_ASSOC)) {
        if(isset($baseline[$row['role']])) $baseline[$row['role']] = $row['cnt'];
    }

     
    $sql_month = "SELECT role, DATE_FORMAT(date_creation, '%Y-%m') as mois, COUNT(*) as cnt 
                  FROM users 
                  WHERE date_creation BETWEEN ? AND ? 
                  GROUP BY role, DATE_FORMAT(date_creation, '%Y-%m')";
    $stmt_month = $pdo->prepare($sql_month);
    $stmt_month->execute([$debut_str, $fin_str]);
    $month_data = $stmt_month->fetchAll(PDO::FETCH_ASSOC);

    $dates = [];
    $roles_data = ['admin' => [], 'gestionnaire' => [], 'reclamant' => []];
    $running_total = $baseline;

     
    $period = new DatePeriod(
        $start_date, 
        new DateInterval('P1M'), 
        $end_date->modify('+1 day')  
    );

    foreach ($period as $dt) {
        $current_month_key = $dt->format('Y-m');
        $dates[] = $dt->format('M Y');  

        foreach (['admin', 'gestionnaire', 'reclamant'] as $role) {
            $monthly_count = 0;
            foreach ($month_data as $record) {
                if ($record['mois'] === $current_month_key && $record['role'] === $role) {
                    $monthly_count = $record['cnt'];
                    break;
                }
            }
             
             
            $running_total[$role] += $monthly_count;
            $roles_data[$role][] = $running_total[$role];
        }
    }
} catch (Exception $e) {
    $dates = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Utilisateurs - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --sidebar-bg: #051b34;  
            --body-bg: #f3f5f9;
            --accent-yellow: #FFC107;  
            --text-muted: #6c757d;
        }
        body { font-family: 'Poppins', sans-serif; background-color: var(--body-bg); overflow-x: hidden; }

         
        .sidebar { background-color: var(--sidebar-bg); min-height: 100vh; color: white; padding-top: 2rem; }
        .nav-link { color: #b0b8c4; padding: 15px 25px; transition: 0.3s; border-left: 4px solid transparent; }
        .nav-link:hover { color: white; background: rgba(255,255,255,0.05); }
        .nav-link.active { color: var(--accent-yellow); background: linear-gradient(90deg, rgba(255,193,7,0.1) 0%, rgba(255,193,7,0) 100%); border-left: 4px solid var(--accent-yellow); }

        .main-content { padding: 2rem 3rem; }
        .card-custom { background: white; border-radius: 15px; border: none; box-shadow: 0 4px 6px rgba(0,0,0,0.02); padding: 25px; }

         
        .filter-bar {
            background-color: #ffffff;
            padding: 1.5rem;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(0,0,0,0.02);
            margin-top: 2rem;  
            margin-bottom: 2rem;
        }
        .form-label-custom {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 700;
            color: #94a3b8;
            margin-bottom: 0.5rem;
        }
        .form-select-custom, .form-control-custom {
            border: 2px solid #f1f5f9;
            background-color: #f8fafc;
            border-radius: 12px;
            padding: 0.6rem 1rem;
            font-weight: 600;
            color: #475569;
            width: 100%;
            transition: all 0.2s ease;
        }
        .form-select-custom:focus, .form-control-custom:focus {
            border-color: var(--accent-yellow);  
            box-shadow: 0 0 0 4px rgba(255, 193, 7, 0.1);
            background-color: #fff;
            outline: none;
        }

         
        .role-badge { padding: 5px 12px; border-radius: 15px; font-size: 0.75rem; font-weight: 600; }
        .role-admin { background-color: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        .role-gestionnaire { background-color: rgba(139, 92, 246, 0.1); color: #8b5cf6; }
        .role-reclamant { background-color:rgba(6, 182, 212, 0.1); color: #06b6d4; }

         
        .pagination .page-link { color: var(--sidebar-bg); border: none; margin: 0 5px; border-radius: 50%; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; font-weight: 600; }
        .pagination .page-item.active .page-link { background-color: var(--sidebar-bg); color: var(--accent-yellow); box-shadow: 0 3px 10px rgba(5, 27, 52, 0.2); }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include 'sidebar.php'; ?>

        <div class="col-md-9 col-lg-10 main-content">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="d-flex align-items-center gap-3">
                    <h2 class="fw-bold text-dark m-0">Gestion Utilisateurs</h2>
                    <span class="badge bg-dark rounded-pill px-3 py-2"><?php echo $total_records; ?> Total</span>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-12">
                    <div class="card-custom">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold text-secondary">Évolution Inscriptions (6 derniers mois)</h5>
                        </div>
                        <div style="height: 250px; width: 100%;">
                            <canvas id="usersEvolutionChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="filter-bar">
                <form method="GET" class="w-100 d-flex flex-wrap align-items-end gap-3">
                    <input type="hidden" name="page" value="1">

                    <div class="flex-grow-1" style="max-width: 250px;">
                        <div class="form-label-custom"><i class="fas fa-search me-1"></i> Recherche</div>
                        <input type="text" name="search" class="form-control-custom" placeholder="Nom ou Email..." value="<?php echo htmlspecialchars($search_filter); ?>" onchange="this.form.submit()">
                    </div>

                    <div class="flex-grow-1" style="max-width: 250px;">
                        <div class="form-label-custom"><i class="fas fa-user-tag me-1"></i> Rôle</div>
                        <select name="role" class="form-select form-select-custom" onchange="this.form.submit()">
                            <option value="">Tous les rôles</option>
                            <option value="reclamant" <?php if ($role_filter == 'reclamant') echo 'selected'; ?>>Réclamant</option>
                            <option value="gestionnaire" <?php if ($role_filter == 'gestionnaire') echo 'selected'; ?>>Gestionnaire</option>
                            <option value="admin" <?php if ($role_filter == 'admin') echo 'selected'; ?>>Admin</option>
                        </select>
                    </div>

                    <div class="ms-auto d-flex gap-2">
                        <button type="button" class="btn fw-bold px-4 rounded-pill shadow-sm"
                                style="background-color: #FFC107; color: #051b34;"
                                data-bs-toggle="modal" data-bs-target="#addUserModal">
                            <i class="fas fa-plus-circle me-2"></i> Nouveau
                        </button>

                        <a href="generate_users_pdf.php?role=<?php echo $role_filter; ?>" target="_blank" class="btn btn-dark text-white fw-bold px-4 rounded-pill shadow-sm">
                            <i class="fas fa-file-pdf me-2"></i> PDF
                        </a>

                        <?php if(!empty($role_filter) || !empty($search_filter)): ?>
                            <a href="users.php" class="btn btn-light text-muted border fw-bold px-4 rounded-pill">
                                <i class="fas fa-times me-2"></i> Effacer
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="card-custom">
                <div class="table-responsive">
                    <table class="table table-borderless table-hover">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Rôle</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (count($users) > 0): ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td class="text-muted">#<?php echo $user['id']; ?></td>
                                    <td class="fw-bold"><?php echo htmlspecialchars($user['nom']); ?></td>
                                    <td class="text-muted"><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><span class="role-badge role-<?php echo $user['role']; ?>"><?php echo ucfirst($user['role']); ?></span></td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-light rounded-circle" data-bs-toggle="dropdown">
                                                <i class="fas fa-ellipsis-v text-muted"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end border-0 shadow">
                                                <li>
                                                    <button class="dropdown-item small" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                                        <i class="fas fa-edit me-2 text-warning"></i> Modifier
                                                    </button>
                                                </li>
                                                <li>
                                                    <form method="POST" onsubmit="return confirm('Supprimer cet utilisateur ?');">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <button type="submit" name="delete_user" class="dropdown-item small text-danger">
                                                            <i class="fas fa-trash me-2"></i> Supprimer
                                                        </button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <h6 class="text-muted">Aucun utilisateur trouvé.</h6>
                                </td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($total_pages > 1): ?>
                    <div class="d-flex justify-content-center mt-4">
                        <nav aria-label="Page navigation">
                            <ul class="pagination">
                                <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&role=<?php echo htmlspecialchars($role_filter); ?>&search=<?php echo htmlspecialchars($search_filter); ?>">&laquo;</a>
                                </li>
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&role=<?php echo htmlspecialchars($role_filter); ?>&search=<?php echo htmlspecialchars($search_filter); ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&role=<?php echo htmlspecialchars($role_filter); ?>&search=<?php echo htmlspecialchars($search_filter); ?>">&raquo;</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">Ajouter un utilisateur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Nom complet</label>
                        <input type="text" name="nom" class="form-control rounded-pill" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control rounded-pill" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mot de passe</label>
                        <input type="password" name="password" class="form-control rounded-pill" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Rôle</label>
                        <select name="role" class="form-select rounded-pill">
                            <option value="reclamant">Réclamant</option>
                            <option value="gestionnaire">Gestionnaire</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <button type="submit" name="add_user" class="btn btn-dark w-100 rounded-pill fw-bold mt-2">Enregistrer</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">Modifier l'utilisateur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    <div class="mb-3">
                        <label class="form-label">Nom complet</label>
                        <input type="text" name="nom" id="edit_nom" class="form-control rounded-pill" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" id="edit_email" class="form-control rounded-pill" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nouveau mot de passe</label>
                        <input type="password" name="password" class="form-control rounded-pill" placeholder="Laisser vide pour ne pas changer">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Rôle</label>
                        <select name="role" id="edit_role" class="form-select rounded-pill">
                            <option value="reclamant">Réclamant</option>
                            <option value="gestionnaire">Gestionnaire</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <button type="submit" name="edit_user" class="btn btn-warning w-100 rounded-pill fw-bold mt-2">Mettre à jour</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const ctx = document.getElementById('usersEvolutionChart').getContext('2d');
    const labels = <?php echo json_encode($dates); ?>;
    const dataAdmin = <?php echo json_encode($roles_data['admin']); ?>;
    const dataGest = <?php echo json_encode($roles_data['gestionnaire']); ?>;
    const dataRecl = <?php echo json_encode($roles_data['reclamant']); ?>;

    if(labels.length > 0) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    { label: 'Réclamants', data: dataRecl, borderColor: '#06b6d4', backgroundColor: 'rgba(6, 182, 212, 0.1)', tension: 0.3, fill: true },
                    { label: 'Gestionnaires', data: dataGest, borderColor: '#8b5cf6', backgroundColor: 'rgba(139, 92, 246, 0.1)', tension: 0.3, fill: true },
                    { label: 'Administrateurs', data: dataAdmin, borderColor: '#3b82f6', backgroundColor: 'rgba(59, 130, 246, 0.1)', tension: 0.3, fill: true }
                ]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top' } }, scales: { x: { grid: { display: false } } } }
        });
    }

    function openEditModal(user) {
        document.getElementById('edit_user_id').value = user.id;
        document.getElementById('edit_nom').value = user.nom;
        document.getElementById('edit_email').value = user.email;
        document.getElementById('edit_role').value = user.role;
        new bootstrap.Modal(document.getElementById('editUserModal')).show();
    }
</script>
</body>
</html>