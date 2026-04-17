<?php
global $pdo;
require_once '../config/db.php';
require_once '../includes/functions.php';

require_role('gestionnaire');

$user_id = $_SESSION['user_id'];
$success_msg = '';
$error_msg = '';

 
if (isset($_POST['update_avatar']) && isset($_FILES['avatar'])) {
    $avatar = $_FILES['avatar'];
    if ($avatar['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($avatar['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($ext, $allowed)) {
            $filename = 'avatar_' . $user_id . '_' . time() . '.' . $ext;
            $upload_dir = '../uploads/avatars/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            
            if (move_uploaded_file($avatar['tmp_name'], $upload_dir . $filename)) {
                $stmt = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
                $stmt->execute([$filename, $user_id]);
                $_SESSION['user_avatar'] = $filename;
                $success_msg = "Photo de profil mise à jour !";
            } else {
                $error_msg = "Erreur lors de l'upload.";
            }
        } else {
            $error_msg = "Format non supporté (JPG, PNG uniquement).";
        }
    }
}

 
if (isset($_POST['update_info'])) {
    $nom = trim($_POST['nom']);
    $email = trim($_POST['email']);
    $telephone = trim($_POST['telephone']);
    
    if (!empty($nom) && !empty($email)) {
        $stmt = $pdo->prepare("UPDATE users SET nom = ?, email = ?, telephone = ? WHERE id = ?");
        $stmt->execute([$nom, $email, $telephone, $user_id]);
        $_SESSION['user_name'] = $nom;
        $_SESSION['user_nom'] = $nom;
        $success_msg = "Informations mises à jour.";
    } else {
        $error_msg = "Champs obligatoires manquants.";
    }
}

 
if (isset($_POST['update_password'])) {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];
    
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (password_verify($current, $user_data['password'])) {
        if ($new === $confirm) {
            if (strlen($new) >= 6) {
                $hash = password_hash($new, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hash, $user_id]);
                $success_msg = "Mot de passe modifié.";
            } else {
                $error_msg = "Le mot de passe est trop court (min 6).";
            }
        } else {
            $error_msg = "Les mots de passe ne correspondent pas.";
        }
    } else {
        $error_msg = "Mot de passe actuel incorrect.";
    }
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Gestionnaire - ReCité</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --sidebar-bg: #051b34; --body-bg: #f3f5f9; --accent-yellow: #ffc107; --text-muted: #6c757d; }
        body { font-family: 'Poppins', sans-serif; background-color: var(--body-bg); overflow-x: hidden; }
        
        .sidebar { background-color: var(--sidebar-bg); min-height: 100vh; color: white; padding-top: 2rem; }
        .user-profile-card { background: rgba(255, 255, 255, 0.1); border-radius: 12px; padding: 10px 15px; margin: 0 20px 40px 20px; }
        .avatar-small { width: 40px; height: 40px; background-color: var(--accent-yellow); color: var(--sidebar-bg); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; object-fit: cover; }
        
        .nav-link { color: #b0b8c4; padding: 15px 25px; transition: 0.3s; border-left: 4px solid transparent; }
        .nav-link:hover { color: white; background: rgba(255,255,255,0.05); }
        .nav-link.active { color: var(--accent-yellow); background: linear-gradient(90deg, rgba(255,193,7,0.1) 0%, rgba(255,193,7,0) 100%); border-left: 4px solid var(--accent-yellow); }
        .nav-link i { width: 25px; margin-right: 10px; }
        
        .main-content { padding: 2rem 3rem; }
        
         
        .profile-card { background: white; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); padding: 40px; text-align: center; height: 100%; }
        .profile-avatar-wrapper { position: relative; width: 140px; height: 140px; margin: 0 auto 20px; }
        .profile-avatar-lg { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; border: 5px solid #f8f9fa; aspect-ratio: 1/1; display: block; }
        .avatar-placeholder { width: 100%; height: 100%; background: var(--sidebar-bg); color: var(--accent-yellow); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 3.5rem; font-weight: bold; border: 5px solid #f8f9fa; aspect-ratio: 1/1; }
        
        .avatar-edit-btn { position: absolute; bottom: 5px; right: 5px; background: var(--accent-yellow); color: var(--sidebar-bg); width: 35px; height: 35px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.2s; z-index: 10; }
        .avatar-edit-btn:hover { transform: scale(1.1); }
        
        .settings-card { background: white; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); overflow: hidden; height: 100%; }
        .nav-tabs { border-bottom: 1px solid #f0f0f0; padding: 0 20px; }
        .nav-tabs .nav-link { border: none; color: var(--text-muted); padding: 20px 25px; font-weight: 500; background: transparent; }
        .nav-tabs .nav-link.active { color: var(--sidebar-bg); border-bottom: 3px solid var(--accent-yellow); }
        .nav-tabs .nav-link:hover { color: var(--sidebar-bg); }
        .tab-content { padding: 30px; }
        .form-control { border-radius: 10px; padding: 12px; border: 1px solid #eee; background: #fcfcfc; }
        .form-control:focus { border-color: var(--accent-yellow); background: white; box-shadow: none; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        
        <?php include 'sidebar.php'; ?>

        <div class="col-md-9 col-lg-10 main-content">
            <?php if ($success_msg): ?>
                <div class="alert alert-success border-0 shadow-sm rounded-3 mb-4"><i class="fas fa-check-circle me-2"></i> <?php echo $success_msg; ?></div>
            <?php endif; ?>
            <?php if ($error_msg): ?>
                <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4"><i class="fas fa-exclamation-circle me-2"></i> <?php echo $error_msg; ?></div>
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="profile-card">
                        <div class="profile-avatar-wrapper">
                            <?php if (!empty($user['avatar'])): ?>
                                <img src="../uploads/avatars/<?php echo htmlspecialchars($user['avatar']); ?>" class="profile-avatar-lg">
                            <?php else: ?>
                                <div class="avatar-placeholder">
                                    <?php $vals = explode(' ', $user['nom']); echo strtoupper(substr($vals[0],0,1).substr($vals[1]??'',0,1)); ?>
                                </div>
                            <?php endif; ?>
                            <label for="avatarUpload" class="avatar-edit-btn"><i class="fas fa-camera"></i></label>
                            <form id="avatarForm" method="POST" enctype="multipart/form-data">
                                <input type="file" id="avatarUpload" name="avatar" class="d-none" onchange="document.getElementById('avatarForm').submit();" accept="image/*">
                                <input type="hidden" name="update_avatar" value="1">
                            </form>
                        </div>
                        <h4 class="fw-bold mb-1"><?php echo htmlspecialchars($user['nom']); ?></h4>
                        <p class="text-muted">Gestionnaire de Cité</p>
                        
                        <div class="text-start mt-4 pt-4 border-top">
                             <div class="d-flex align-items-center mb-3 text-muted">
                                <i class="fas fa-envelope me-3 text-warning"></i> <?php echo htmlspecialchars($user['email']); ?>
                            </div>
                            <div class="d-flex align-items-center mb-3 text-muted">
                                <i class="fas fa-phone me-3 text-warning"></i> <?php echo htmlspecialchars($user['telephone'] ?? 'Non renseigné'); ?>
                            </div>
                            <div class="d-flex align-items-center text-muted">
                                <i class="fas fa-calendar-alt me-3 text-warning"></i>
                                Membre depuis <?php echo isset($user['date_creation']) ? date('Y', strtotime($user['date_creation'])) : date('Y'); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="settings-card">
                        <ul class="nav nav-tabs" id="pTab" role="tablist">
                            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#info">Informations</button></li>
                            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#sec">Sécurité</button></li>
                        </ul>
                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="info">
                                <h5 class="fw-bold mb-4">Modifier mes informations</h5>
                                <form method="POST">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Nom complet</label>
                                            <input type="text" name="nom" class="form-control" value="<?php echo htmlspecialchars($user['nom']); ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Email</label>
                                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Téléphone</label>
                                            <input type="text" name="telephone" class="form-control" value="<?php echo htmlspecialchars($user['telephone'] ?? ''); ?>" placeholder="+212 6...">
                                        </div>

                                    </div>
                                    <div class="text-end mt-4">
                                        <button type="submit" name="update_info" class="btn btn-dark rounded-pill px-4">Enregistrer</button>
                                    </div>
                                </form>
                            </div>
                            <div class="tab-pane fade" id="sec">
                                <h5 class="fw-bold mb-4">Changer mot de passe</h5>
                                <form method="POST">
                                    <div class="mb-3"><label class="form-label">Actuel</label><input type="password" name="current_password" class="form-control" required></div>
                                    <div class="row mb-3">
                                        <div class="col-md-6"><label class="form-label">Nouveau</label><input type="password" name="new_password" class="form-control" required></div>
                                        <div class="col-md-6"><label class="form-label">Confirmer</label><input type="password" name="confirm_password" class="form-control" required></div>
                                    </div>
                                    <div class="text-end">
                                        <button type="submit" name="update_password" class="btn btn-danger rounded-pill px-4">Mettre à jour</button>
                                    </div>
                                </form>
                            </div>
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
