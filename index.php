<?php
global $pdo;
require_once 'config/db.php';
require_once 'includes/functions.php';

 
if (is_logged_in()) {
    redirect_by_role($_SESSION['user_role']);
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (!empty($email) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
             
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_nom'] = $user['nom'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_avatar'] = $user['avatar'];

            redirect_by_role($user['role']);
        } else {
            $error = "Email ou mot de passe incorrect.";
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
    <title>ReCité - Connexion</title>
    
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
        <div class="row main-content align-items-center">
            
            <div class="col-lg-6">
                <div class="left-content">
                    <div class="logo-container">
                        <img src="assets/img/logo.png" alt="Logo ReCité" class="logo-img">
                    </div>

                    <p class="description">
                        ReCité.ma est une plateforme qui permet aux étudiants
                        de cité de signaler facilement les problèmes de leur quotidien
                        (propreté, infrastructures, sécurité).<br><br>
                        Son rôle est de servir de pont entre les étudiants et les responsables de cité pour
                        suivre et rendre visible la résolution de ces réclamations, afin de
                        lutter contre le sentiment d'impuissance.
                    </p>
                </div>
            </div>

            
            <div class="col-lg-6">
                <div class="right-content">
                    <div class="login-box">
                        <h3>Connexion</h3>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        
                        <?php if (isset($_GET['success'])): ?>
                            <div class="alert alert-success" role="alert"><?php echo htmlspecialchars($_GET['success']); ?></div>
                        <?php endif; ?>

                        <form method="POST" action="" aria-label="Formulaire de connexion" id="loginForm">
                            <div class="mb-3">
                                <label for="email" class="form-label">
                                    Email <span class="required">*</span>
                                </label>
                                <input type="email" class="form-control" id="email" name="email"
                                       placeholder="Entrez votre email" required>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    Mot de passe <span class="required">*</span>
                                </label>
                                <div class="password-container">
                                    <input type="password" class="form-control" id="password" name="password"
                                           placeholder="Entrez votre mot de passe" required>
                                    <i class="bi bi-eye-slash toggle-password" id="togglePassword"
                                       role="button" aria-label="Afficher/masquer le mot de passe"></i>
                                </div>

                                <div class="forgot-password">
                                    <a href="forgot_password.php">Mot de passe oublié ?</a>
                                </div>
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                <label class="form-check-label" for="remember">
                                    Se rappeler de moi
                                </label>
                            </div>

                            <button type="submit" class="btn btn-login" id="submitBtn">
                                Se connecter
                            </button>

                            <div class="footer-text">
                                En cliquant sur « Se connecter », vous acceptez les
                                <a href="terms.php">conditions d'utilisation</a> et la
                                <a href="privacy.php">politique de confidentialité</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<footer class="site-footer">
    <div class="footer-content">
        <div class="footer-grid">
            <div class="footer-section">
                <h4>À propos</h4>
                <p>ReCité.ma facilité la communication entre étudiants et responsables de cité pour améliorer les conditions de vie étudiante.</p>
                <div class="social-links">
                    <a href="https://web.facebook.com/login/?locale=fr_FR&_rdc=1&_rdr#" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                    <a href="https://x.com/?lang=fr" aria-label="Twitter"><i class="bi bi-twitter"></i></a>
                    <a href="https://www.instagram.com/" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                    <a href="https://fr.linkedin.com/" aria-label="LinkedIn"><i class="bi bi-linkedin"></i></a>
                </div>
            </div>


            <div class="footer-section">
                <h4>Informations légales</h4>
                <ul>
                    <li><a href="terms.php">Conditions d'utilisation</a></li>
                    <li><a href="privacy.php">Politique de confidentialité</a></li>
                    <li><a href="#">Mentions légales</a></li>
                    <li><a href="#">Nous contacter</a></li>
                </ul>
            </div>

            <div class="footer-section">
                <h4>Contact</h4>
                <p><i class="bi bi-envelope"></i> contact@recite.ma</p>
                <p><i class="bi bi-telephone"></i> +212 522-222222</p>
                <p><i class="bi bi-geo-alt"></i> El Jadida, Maroc</p>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; 2025 ReCité.ma - Tous droits réservés</p>
        </div>
    </div>
</footer>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');

        if (togglePassword && passwordInput) {
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);

                this.classList.toggle('bi-eye');
                this.classList.toggle('bi-eye-slash');
            });
        }
        
        
        const inputs = document.querySelectorAll('.form-control');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.style.transform = 'scale(1.02)';
            });
            input.addEventListener('blur', function() {
                this.style.transform = 'scale(1)';
            });
        });

        
        const loginForm = document.getElementById('loginForm');
        const submitBtn = document.getElementById('submitBtn');
        
        loginForm.addEventListener('submit', function() {
             submitBtn.classList.add('btn-loading');
        });
    });
</script>
</body>
</html>
// Auto-deploy test
