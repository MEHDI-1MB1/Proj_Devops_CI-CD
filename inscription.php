<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['fullName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $housingId = trim($_POST['housingId'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');  
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';

     
    if (empty($fullName) || empty($email) || empty($housingId) || empty($telephone) || empty($password) || empty($confirmPassword)) {
        $error = "Veuillez remplir tous les champs obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Veuillez entrer une adresse email valide.";
    } elseif (!preg_match('/^[0-9]{10}$/', $telephone)) {  
        $error = "Le numéro de téléphone doit contenir 10 chiffres.";
    } elseif ($password !== $confirmPassword) {
        $error = "Les mots de passe ne correspondent pas.";
    } elseif (strlen($password) < 8) {
        $error = "Le mot de passe doit contenir au moins 8 caractères.";
    } else {
         
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = "Cette adresse email est déjà utilisée.";
        } else {
             
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = 'reclamant';  

             
             
            $stmt = $pdo->prepare("INSERT INTO users (nom, email, password, role, code_logement, telephone) VALUES (?, ?, ?, ?, ?, ?)");

            try {
                 
                if ($stmt->execute([$fullName, $email, $hashed_password, $role, $housingId, $telephone])) {

                     
                     
                    $stmtAdmins = $pdo->query("SELECT id FROM users WHERE role = 'admin'");
                    $admins = $stmtAdmins->fetchAll(PDO::FETCH_COLUMN);

                     
                    $notifMsg = "Nouvel inscrit : " . $fullName . " (" . $housingId . ")";
                    $notifSql = "INSERT INTO notifications (user_id, type, message, created_at) VALUES (?, 'new_user', ?, NOW())";
                    $notifStmt = $pdo->prepare($notifSql);

                     
                    foreach ($admins as $adminId) {
                        $notifStmt->execute([$adminId, $notifMsg]);
                    }
                     

                    header("Location: index.php?success=" . urlencode("Inscription réussie ! Vous pouvez maintenant vous connecter."));
                    exit;
                } else {
                    $error = "Une erreur est survenue lors de l'inscription.";
                }
            } catch (PDOException $e) {
                $error = "Erreur de base de données : " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReCité - Inscription</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        html, body {
            height: 100%;
            margin: 0;
        }

        body {
            background-color: #001F3F;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            position: relative;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        body::before {
            content: '';
            position: fixed;  
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
             
            background-image: url('assets/img/pic1.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            opacity: 0.70;
            z-index: 1;
        }

        .main-wrapper {
            position: relative;
            z-index: 2;
        }

         
        .main-wrapper {
            position: relative;
            z-index: 2;
            flex: 1 0 auto;
            display: flex;
            flex-direction: column;
        }

        .header-links {
            position: absolute;
            top: 20px;
            right: 30px;
            z-index: 3;
        }

        .header-links a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .header-links a:hover {
            text-decoration: underline;
            color: #FDB913;
        }

        .main-content {
            padding: 40px 20px;
            position: relative;
            z-index: 2;
        }

         
        .left-content {
            display: flex;
            flex-direction: column;
            justify-content: center;
            height: 100%;
            padding: 20px 20px 20px 80px;
        }

        .logo-container {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            margin-bottom: 10px;
        }

        .logo-img {
            width: 100%;
            max-width: 200px;
            height: auto;
            border-radius: 8px;
        }

        .description {
            font-size: 16px;
            color: rgba(255, 255, 255, 0.95);
            line-height: 1.6;
            margin-top: 0;
            text-align: left;
        }

         
        .right-content {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .register-box {
            background-color: rgba(103, 112, 118, 0.75);
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            max-width: 1000px;
            width: 100%;
        }

        .register-box h3 {
            color: #ffffff;
            margin-bottom: 15px;
            font-size: 35px;
            text-align: center;
        }

        .form-label {
            color: #ffffff;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .required {
            color: #ff4444;
            margin-left: 3px;
        }

        .form-control {
            background-color: #001F3F;
            border: 1px solid #001F3F;
            color: white;
            padding: 12px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        .form-control:focus {
            background-color: #001F3F;
            border-color: #001F3F;
            color: white;
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
            outline: 2px solid #FDB913;
            outline-offset: 2px;
        }

         
        .password-container {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: rgba(255, 255, 255, 0.6);
            font-size: 18px;
            transition: all 0.3s ease;
            z-index: 5;
        }

        .toggle-password:hover {
            color: #FDB913;
        }

         
        .password-field {
            padding-right: 3rem;
        }

        .form-select {
            background-color: #001F3F;
            border: 1px solid #001F3F;
            color: white;
            padding: 12px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .form-select:focus {
            background-color: #001F3F;
            border-color: #001F3F;
            color: white;
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
            outline: 2px solid #FDB913;
            outline-offset: 2px;
        }

        .password-requirements {
            background-color: rgba(0, 31, 63, 0.7);
            border-radius: 5px;
            padding: 15px;
            margin: 15px 0;
            border-left: 4px solid #FDB913;
        }

        .password-requirements h6 {
            color: #FDB913;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .password-requirements ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .password-requirements li {
            color: rgba(255, 255, 255, 0.9);
            font-size: 12px;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
        }

        .password-requirements li.valid {
            color: #4CAF50;
        }

        .password-requirements li.invalid {
            color: rgba(255, 255, 255, 0.6);
        }

        .requirement-icon {
            margin-right: 8px;
            font-size: 14px;
        }

        .btn-register {
            background-color: #FDB913;
            color: #2C3E50;
            border: none;
            padding: 12px;
            font-weight: 600;
            border-radius: 5px;
            width: 100%;
            margin-top: 10px;
            transition: all 0.3s ease;
        }

        .btn-register:hover {
            background-color: #E5A711;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(253, 185, 19, 0.4);
        }

        .btn-register:active {
            transform: translateY(0);
        }

        .footer-text {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
            color: #aaa9a9;
        }

        .footer-text a {
            color: #3badfb;
            text-decoration: none;
        }

        .footer-text a:hover {
            text-decoration: underline;
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #aaa9a9;
        }

        .login-link a {
            color: #3badfb;
            text-decoration: none;
            font-weight: 600;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

         
        .error-message {
            display: none;
            padding: 10px 15px;
            border-radius: 5px;
            margin-top: 15px;
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            font-size: 14px;
        }

        .success-message {
            display: none;
            padding: 10px 15px;
            border-radius: 5px;
            margin-top: 15px;
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            font-size: 14px;
        }
        
         
        .alert-php {
            padding: 10px 15px;
            border-radius: 5px;
            margin-top: 15px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

         
        .site-footer {
            background-color: rgba(0, 31, 63, 0.95);
            color: white;
            padding: 40px 0 20px;
            position: relative;
            z-index: 2;
            flex-shrink: 0;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }

        .footer-section h4 {
            color: #FDB913;
            margin-bottom: 15px;
            font-size: 18px;
            font-weight: 600;
        }

        .footer-section p {
            line-height: 1.8;
            opacity: 0.9;
            font-size: 14px;
        }

        .footer-section ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-section ul li {
            margin-bottom: 10px;
        }

        .footer-section ul li a {
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .footer-section ul li a:hover {
            color: #FDB913;
            padding-left: 5px;
        }

        .social-links {
            display: flex;
            gap: 15px;
            margin-top: 15px;
        }

        .social-links a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background-color: rgba(253, 185, 19, 0.2);
            border-radius: 50%;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 18px;
        }

        .social-links a:hover {
            background-color: #FDB913;
            color: #001F3F;
            transform: translateY(-3px);
        }

        .footer-bottom {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 14px;
            opacity: 0.8;
        }

         
        .btn-loading {
            position: relative;
            color: transparent;
        }

        .btn-loading::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            top: 50%;
            left: 50%;
            margin-left: -10px;
            margin-top: -10px;
            border: 2px solid #ffffff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

         
        @media (max-width: 1200px) {
            .left-content {
                padding: 20px 20px 20px 60px;
            }
        }

        @media (max-width: 992px) {
            .left-content {
                text-align: center;
                padding: 40px 20px;
            }

            .logo-container {
                align-items: center;
            }

            .logo-img {
                max-width: 400px;
            }

            .description {
                max-width: 100%;
                margin: 20px auto;
                text-align: center;
            }

            .footer-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 20px 10px;
            }

            .header-links {
                position: relative;
                top: 0;
                right: 0;
                text-align: center;
                padding: 15px;
                background-color: rgba(0, 31, 63, 0.9);
            }

            .header-links a {
                margin: 0 10px;
            }

            .left-content {
                padding: 20px 15px;
            }

            .logo-img {
                max-width: 300px;
            }

            .description {
                font-size: 15px;
                margin-top: 15px;
            }

            .right-content {
                padding: 20px 10px;
            }

            .register-box {
                padding: 25px;
                margin: 0 10px;
            }

            .register-box h3 {
                font-size: 24px;
                margin-bottom: 20px;
            }

            .footer-grid {
                grid-template-columns: 1fr;
                gap: 25px;
            }

            .footer-section {
                text-align: center;
            }

            .social-links {
                justify-content: center;
            }
        }

        @media (max-width: 576px) {
            .left-content {
                padding: 20px 10px;
            }

            .logo-img {
                max-width: 250px;
            }

            .description {
                font-size: 14px;
            }

            .register-box {
                padding: 20px;
            }

            .form-control, .form-select {
                padding: 10px;
            }

            .password-field {
                padding-right: 2.5rem;
            }

            .toggle-password {
                right: 12px;
                font-size: 16px;
            }

            .site-footer {
                padding: 30px 0 15px;
            }

            .header-links {
                position: relative;
                text-align: center;
                margin-bottom: 20px;
            }
        }

        @media (max-width: 400px) {
            .header-links a {
                font-size: 12px;
                margin: 0 8px;
            }

            .logo-img {
                max-width: 200px;
            }

            .register-box h3 {
                font-size: 22px;
            }

            .description {
                font-size: 13px;
            }
        }
    </style>
</head>
<body>

<div class="header-links">
    <a href="inscription.php">S'inscrire</a>
    <span style="margin-left: 10px; color: white">|</span>
    <a href="index.php">Se connecter</a>
</div>


<div class="main-wrapper">
    <div class="container-fluid">
            
            
            <div class="col-lg-12">

                <div class="col-lg-6">
                    <div class="left-content">
                        <div class="logo-container">
                            
                            <img src="assets/img/logo.png" alt="Logo ReCité" class="logo-img">
                        </div>
                    </div>
                </div>

                <div class="right-content">
                    <div class="register-box">
                        <h3>Inscription</h3>
                        
                        <?php if ($error): ?>
                            <div class="alert-php alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>

                        <form method="POST" action="inscription.php" aria-label="Formulaire d'inscription" id="registerForm">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="fullName" class="form-label">
                                        Nom complet <span class="required">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="fullName" name="fullName"
                                           placeholder="Entrez votre nom complet" required value="<?php echo htmlspecialchars($fullName ?? ''); ?>">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">
                                        Email <span class="required">*</span>
                                    </label>
                                    <input type="email" class="form-control" id="email" name="email"
                                           placeholder="Entrez votre email" required value="<?php echo htmlspecialchars($email ?? ''); ?>">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="housingId" class="form-label">
                                        ID de logement <span class="required">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="housingId" name="housingId"
                                           placeholder="Ex: BAT-A-101" required value="<?php echo htmlspecialchars($housingId ?? ''); ?>">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="telephone" class="form-label">
                                        Téléphone <span class="required">*</span>
                                    </label>
                                    <input type="tel" class="form-control" id="telephone" name="telephone" placeholder="Ex: 06 12 34 56 78"
                                            value="<?php echo htmlspecialchars($telephone ?? ''); ?>" pattern="[0-9]{10}" title="Veuillez entrer un numéro à 10 chiffres" required>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">
                                        Mot de passe <span class="required">*</span>
                                    </label>
                                    <div class="password-container">
                                        <input type="password" class="form-control password-field" id="password" name="password"
                                               placeholder="Créez un mot de passe" required minlength="8">
                                        <i class="bi bi-eye-slash toggle-password" data-target="password"
                                           role="button" aria-label="Afficher/masquer le mot de passe"></i>
                                    </div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="confirmPassword" class="form-label">
                                        Confirmer le mot de passe <span class="required">*</span>
                                    </label>
                                    <div class="password-container">
                                        <input type="password" class="form-control password-field" id="confirmPassword" name="confirmPassword"
                                               placeholder="Confirmez le mot de passe" required minlength="8">
                                        <i class="bi bi-eye-slash toggle-password" data-target="confirmPassword"
                                           role="button" aria-label="Afficher/masquer le mot de passe"></i>
                                    </div>
                                </div>
                            </div>

                            
                            <div class="password-requirements">
                                <h6>Exigences du mot de passe :</h6>
                                <ul>
                                    <li id="req-length" class="invalid">
                                        <i class="bi bi-x-circle requirement-icon"></i>
                                        8 caractères minimum
                                    </li>
                                    <li id="req-digit" class="invalid">
                                        <i class="bi bi-x-circle requirement-icon"></i>
                                        Au moins 1 chiffre
                                    </li>
                                    <li id="req-match" class="invalid">
                                        <i class="bi bi-x-circle requirement-icon"></i>
                                        Les mots de passe correspondent
                                    </li>
                                </ul>
                            </div>

                            <button type="submit" class="btn btn-register" id="submitBtn">
                                S'inscrire
                            </button>

                            
                            <div class="error-message" id="errorMessage"></div>
                            <div class="success-message" id="successMessage"></div>

                            <div class="footer-text">
                                En cliquant sur « S'inscrire », vous acceptez les
                                <a href="terms.php">conditions d'utilisation</a> et la
                                <a href="privacy.php">politique de confidentialité</a>
                            </div>

                            <div class="login-link">
                                Déjà un compte ? <a href="index.php">Se connecter</a>
                            </div>
                        </form>
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
                <p><i class="bi bi-telephone"></i> +212 5XX-XXXXXX</p>
                <p><i class="bi bi-geo-alt"></i> El Jadida, Maroc</p>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; 2025 ReCité.ma - Tous droits réservés</p>
        </div>
    </div>
</footer>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Page d\'inscription ReCité chargée avec succès');

        
        const registerForm = document.getElementById('registerForm');
        const fullNameInput = document.getElementById('fullName');
        const emailInput = document.getElementById('email');
        const housingIdInput = document.getElementById('housingId');
        const telephoneInput = document.getElementById('telephone'); 
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirmPassword');
        const submitBtn = document.getElementById('submitBtn');
        const errorMessage = document.getElementById('errorMessage');
        const successMessage = document.getElementById('successMessage');

        
        const reqLength = document.getElementById('req-length');
        const reqDigit = document.getElementById('req-digit');
        const reqMatch = document.getElementById('req-match');

        
        function showMessage(element, message) {
            element.textContent = message;
            element.style.display = 'block';
            const otherElement = element === errorMessage ? successMessage : errorMessage;
            otherElement.style.display = 'none';
        }

        function hideAllMessages() {
            errorMessage.style.display = 'none';
            successMessage.style.display = 'none';
        }

        function validatePassword(password, confirmPassword) {
            const hasMinLength = password.length >= 8;
            const hasDigit = /\d/.test(password);
            const passwordsMatch = password === confirmPassword && password !== '';

            updateRequirement(reqLength, hasMinLength);
            updateRequirement(reqDigit, hasDigit);
            updateRequirement(reqMatch, passwordsMatch);

            return hasMinLength && hasDigit && passwordsMatch;
        }

        function updateRequirement(element, isValid) {
            if (isValid) {
                element.classList.remove('invalid');
                element.classList.add('valid');
                element.querySelector('.requirement-icon').className = 'bi bi-check-circle requirement-icon';
            } else {
                element.classList.remove('valid');
                element.classList.add('invalid');
                element.querySelector('.requirement-icon').className = 'bi bi-x-circle requirement-icon';
            }
        }

        
        function validateForm(formData) {
            const { fullName, email, housingId, telephone, password, confirmPassword } = formData;

            if (!fullName || !email || !housingId || !telephone || !password || !confirmPassword) {
                showMessage(errorMessage, 'Veuillez remplir tous les champs obligatoires.');
                return false;
            }

            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                showMessage(errorMessage, 'Veuillez entrer une adresse email valide.');
                return false;
            }

            
            const phoneRegex = /^[0-9]{10}$/;
            if (!phoneRegex.test(telephone)) {
                showMessage(errorMessage, 'Veuillez entrer un numéro de téléphone valide (10 chiffres).');
                return false;
            }

            if (!validatePassword(password, confirmPassword)) {
                showMessage(errorMessage, 'Veuillez corriger les erreurs dans le mot de passe.');
                return false;
            }

            return true;
        }

        function setupPasswordToggles() {
            const toggles = document.querySelectorAll('.toggle-password');
            toggles.forEach(toggle => {
                toggle.addEventListener('click', function() {
                    const targetId = this.getAttribute('data-target');
                    const targetInput = document.getElementById(targetId);
                    if (targetInput) {
                        const type = targetInput.getAttribute('type') === 'password' ? 'text' : 'password';
                        targetInput.setAttribute('type', type);
                        this.classList.toggle('bi-eye');
                        this.classList.toggle('bi-eye-slash');
                    }
                });
            });
        }

        const inputs = document.querySelectorAll('.form-control');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.style.transform = 'scale(1.02)';
                hideAllMessages();
            });
            input.addEventListener('blur', function() {
                this.style.transform = 'scale(1)';
            });
            input.addEventListener('input', function() {
                hideAllMessages();
                if (this.id === 'password' || this.id === 'confirmPassword') {
                    validatePassword(passwordInput.value, confirmPasswordInput.value);
                }
            });
        });

        registerForm.addEventListener('submit', function(e) {
            const formData = {
                fullName: fullNameInput.value.trim(),
                email: emailInput.value.trim(),
                housingId: housingIdInput.value.trim(),
                telephone: telephoneInput.value.trim(), 
                password: passwordInput.value,
                confirmPassword: confirmPasswordInput.value
            };

            if (!validateForm(formData)) {
                e.preventDefault();
                return;
            }
            submitBtn.classList.add('btn-loading');
        });

        setupPasswordToggles();
        fullNameInput.focus();
    });
</script>
</body>
</html>
