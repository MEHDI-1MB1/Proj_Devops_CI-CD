<?php
 
global $pdo;
require_once 'config/db.php';
require_once 'includes/functions.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    if (!empty($email)) {
         
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->rowCount() > 0) {
             
            $token = bin2hex(random_bytes(32));
             
            $expires = date("Y-m-d H:i:s", strtotime("+1 hour"));

             
            $update = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE email = ?");

            if ($update->execute([$token, $expires, $email])) {
                 
                 
                 
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                $host = $_SERVER['HTTP_HOST'];
                $path = dirname($_SERVER['PHP_SELF']);
                 
                $path = rtrim($path, '/\\');
                
                $link = "$protocol://$host$path/reset_password.php?token=$token&email=$email";

                $subject = "Réinitialisation de mot de passe - ReCité";
                $msg = "Cliquez sur ce lien pour réinitialiser votre mot de passe : " . $link;
                $headers = "From: no-reply@recite.ma";

                 
                $message = "<strong>MODE DEV (XAMPP) :</strong><br>L'envoi d'email est simulé.<br>";
                $message .= "Copie ce lien dans ton navigateur : <br>";
                $message .= "<a href='" . htmlspecialchars($link) . "'>$link</a>";
            }
        } else {
             
            $message = "Si cet email existe, un lien a été envoyé.";
        }
    } else {
        $error = "Veuillez entrer votre adresse email.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReCité - Mot de passe oublié</title>
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
                        <h3>Récupération</h3>
                        <p class="text-white-50">Entrez votre email pour recevoir un lien de réinitialisation.</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger error-message text-break"><i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <?php if ($message): ?>
                        <div class="alert alert-success success-message text-break"><i class="bi bi-check-circle-fill me-2"></i><?php echo $message; ?></div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email <span class="required">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-transparent border-0 text-white" style="position: absolute; z-index: 10; top: 10px; left: 10px;">
                                    <i class="bi bi-envelope"></i>
                                </span>
                                <input type="email" class="form-control ps-5" id="email" name="email" required placeholder="Ex: etudiant@recite.ma">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-login text-dark fw-bold">Envoyer le lien</button>

                        <div class="text-center mt-4">
                            <a href="index.php" class="text-decoration-none text-light opacity-75 hover-opacity-100">
                                <i class="bi bi-arrow-left me-1"></i> Retour à la connexion
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>