<?php
 
global $pdo;
require_once 'config/db.php';
require_once 'includes/functions.php';

$message = '';
$error = '';
$token = $_GET['token'] ?? '';
$email = $_GET['email'] ?? '';
$validLink = false;

 
if (!empty($token) && !empty($email)) {
     
    $stmt = $pdo->prepare("SELECT id, reset_expires FROM users WHERE email = ? AND reset_token = ?");
    $stmt->execute([$email, $token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
         
        if (strtotime($user['reset_expires']) > time()) {
            $validLink = true;
        } else {
             $error = "Ce lien a expiré (Délai de 1h dépassé).";
        }
    } else {
        $error = "Ce lien de réinitialisation est invalide.";
    }
} else {
    $error = "Lien incomplet.";
}

 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validLink) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (!empty($password) && !empty($confirm_password)) {
        if ($password === $confirm_password) {
             
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
             
            $update = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE email = ?");
            
            if ($update->execute([$hashed_password, $email])) {
                $message = "Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter.";
                $validLink = false;  
            } else {
                $error = "Une erreur est survenue lors de la mise à jour.";
            }
        } else {
            $error = "Les mots de passe ne correspondent pas.";
        }
    } else {
        $error = "Veuillez remplir tous les champs.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialisation du mot de passe - ReCité</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>


<div class="header-links">
    <a href="inscription.php">S'inscrire</a>
    <span style="margin-left: 10px; color: white">|</span>
    <a href="index.php">Se connecter</a>
</div>

<div class="main-wrapper">
    <div class="container-fluid">
        <div class="row main-content align-items-center justify-content-center" style="min-height: 100vh;">

            <div class="col-lg-5">
                <div class="login-box mx-auto">
                    <div class="text-center mb-4">
                        <img src="assets/img/logo.png" alt="Logo" style="max-height: 80px;" class="mb-3">
                        <h3>Nouveau mot de passe</h3>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger error-message"><i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo htmlspecialchars($error); ?></div>
                        <div class="text-center mt-3">
                            <a href="forgot_password.php" class="btn btn-outline-light btn-sm">Demander un nouveau lien</a>
                        </div>
                    <?php endif; ?>

                    <?php if ($message): ?>
                        <div class="alert alert-success success-message"><i class="bi bi-check-circle-fill me-2"></i><?php echo htmlspecialchars($message); ?></div>
                        <div class="text-center mt-3">
                            <a href="index.php" class="btn btn-login text-dark fw-bold">Se connecter</a>
                        </div>
                    <?php endif; ?>

                    <?php if ($validLink): ?>
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="password" class="form-label">Nouveau mot de passe <span class="required">*</span></label>
                            <div class="password-container">
                                <input type="password" class="form-control" id="password" name="password" required placeholder="Entrez le nouveau mot de passe">
                                <i class="bi bi-eye-slash toggle-password" id="togglePassword"></i>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirmer le mot de passe <span class="required">*</span></label>
                            <div class="password-container">
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required placeholder="Confirmez le mot de passe">
                                <i class="bi bi-eye-slash toggle-password" id="toggleConfirmPassword"></i>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-login text-dark fw-bold">Changer le mot de passe</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        function setupToggle(inputId, toggleId) {
            const toggle = document.getElementById(toggleId);
            const input = document.getElementById(inputId);
            
            if (toggle && input) {
                toggle.addEventListener('click', function() {
                    const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                    input.setAttribute('type', type);
                    this.classList.toggle('bi-eye');
                    this.classList.toggle('bi-eye-slash');
                });
            }
        }

        setupToggle('password', 'togglePassword');
        setupToggle('confirm_password', 'toggleConfirmPassword');
    });
</script>
</body>
</html>
