-- DÉBUT DU SCRIPT FINAL (2 BÂTIMENTS : A=GARÇONS, B=FILLES)

-- 1. Configuration initiale
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- 2. Réinitialisation de la base de données
DROP DATABASE IF EXISTS `gestion_reclamations`;
CREATE DATABASE IF NOT EXISTS `gestion_reclamations` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `gestion_reclamations`;

-- 3. Création des tables

-- Table Annonces
CREATE TABLE `annonces` (
                            `id` int(11) NOT NULL AUTO_INCREMENT,
                            `titre` varchar(255) NOT NULL,
                            `type` enum('maintenance','reunion','info','urgence') DEFAULT 'info',
                            `description` text DEFAULT NULL,
                            `date_evenement` date NOT NULL,
                            `date_creation` datetime DEFAULT current_timestamp(),
                            PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table Users
CREATE TABLE `users` (
                         `id` int(11) NOT NULL AUTO_INCREMENT,
                         `nom` varchar(100) NOT NULL,
                         `email` varchar(100) NOT NULL,
                         `password` varchar(255) NOT NULL,
                         `role` enum('reclamant','gestionnaire','admin') NOT NULL DEFAULT 'reclamant',
                         `code_logement` varchar(50) DEFAULT NULL,
                         `type_occupant` varchar(50) DEFAULT NULL,
                         `avatar` varchar(255) DEFAULT NULL,
                         `telephone` varchar(20) DEFAULT NULL,
                         `reset_token` varchar(255) DEFAULT NULL,
                         `reset_expires` datetime DEFAULT NULL,
                         `date_creation` datetime DEFAULT current_timestamp(),
                         PRIMARY KEY (`id`),
                         UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table Avis
CREATE TABLE `avis` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `user_id` int(11) NOT NULL,
                        `note` int(11) NOT NULL,
                        `commentaire` text DEFAULT NULL,
                        `date_creation` datetime DEFAULT current_timestamp(),
                        PRIMARY KEY (`id`),
                        KEY `user_id` (`user_id`),
                        CONSTRAINT `avis_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table Categories
CREATE TABLE `categories` (
                              `id` int(11) NOT NULL AUTO_INCREMENT,
                              `nom` varchar(100) NOT NULL,
                              PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table Reclamations
CREATE TABLE `reclamations` (
                                `id` int(11) NOT NULL AUTO_INCREMENT,
                                `user_id` int(11) NOT NULL,
                                `categorie_id` int(11) NOT NULL,
                                `objet` varchar(255) NOT NULL,
                                `description` text NOT NULL,
                                `lieu` varchar(255) DEFAULT NULL,
                                `urgence` enum('Faible','Moyenne','Haute','Urgent') DEFAULT 'Faible',
                                `statut` enum('en_attente','en_cours','resolu','rejete') NOT NULL DEFAULT 'en_attente',
                                `date_creation` datetime DEFAULT current_timestamp(),
                                PRIMARY KEY (`id`),
                                KEY `user_id` (`user_id`),
                                KEY `categorie_id` (`categorie_id`),
                                CONSTRAINT `reclamations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
                                CONSTRAINT `reclamations_ibfk_2` FOREIGN KEY (`categorie_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table Commentaires
CREATE TABLE `commentaires` (
                                `id` int(11) NOT NULL AUTO_INCREMENT,
                                `reclamation_id` int(11) NOT NULL,
                                `user_id` int(11) NOT NULL,
                                `contenu` text NOT NULL,
                                `date_commentaire` datetime DEFAULT current_timestamp(),
                                PRIMARY KEY (`id`),
                                KEY `reclamation_id` (`reclamation_id`),
                                KEY `user_id` (`user_id`),
                                CONSTRAINT `commentaires_ibfk_1` FOREIGN KEY (`reclamation_id`) REFERENCES `reclamations` (`id`) ON DELETE CASCADE,
                                CONSTRAINT `commentaires_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table FAQs
CREATE TABLE `faqs` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `question` varchar(255) NOT NULL,
                        `reponse` text NOT NULL,
                        `ordre` int(11) DEFAULT 0,
                        PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table Historique Statuts
CREATE TABLE `historique_statuts` (
                                      `id` int(11) NOT NULL AUTO_INCREMENT,
                                      `reclamation_id` int(11) NOT NULL,
                                      `ancien_statut` varchar(50) DEFAULT NULL,
                                      `nouveau_statut` varchar(50) NOT NULL,
                                      `user_id` int(11) NOT NULL,
                                      `date_changement` datetime DEFAULT current_timestamp(),
                                      PRIMARY KEY (`id`),
                                      KEY `reclamation_id` (`reclamation_id`),
                                      KEY `user_id` (`user_id`),
                                      CONSTRAINT `historique_statuts_ibfk_1` FOREIGN KEY (`reclamation_id`) REFERENCES `reclamations` (`id`) ON DELETE CASCADE,
                                      CONSTRAINT `historique_statuts_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table Lieux
CREATE TABLE `lieux` (
                         `id` int(11) NOT NULL AUTO_INCREMENT,
                         `nom` varchar(100) NOT NULL,
                         PRIMARY KEY (`id`),
                         UNIQUE KEY `nom` (`nom`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table Notifications
CREATE TABLE `notifications` (
                                 `id` int(11) NOT NULL AUTO_INCREMENT,
                                 `user_id` int(11) NOT NULL,
                                 `type` varchar(50) DEFAULT NULL,
                                 `message` text NOT NULL,
                                 `lien` varchar(255) DEFAULT NULL,
                                 `is_read` tinyint(4) DEFAULT 0,
                                 `created_at` datetime DEFAULT current_timestamp(),
                                 PRIMARY KEY (`id`),
                                 KEY `user_id` (`user_id`),
                                 CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table Pieces Jointes
CREATE TABLE `pieces_jointes` (
                                  `id` int(11) NOT NULL AUTO_INCREMENT,
                                  `reclamation_id` int(11) NOT NULL,
                                  `chemin_fichier` varchar(255) NOT NULL,
                                  `nom_fichier` varchar(255) NOT NULL,
                                  PRIMARY KEY (`id`),
                                  KEY `reclamation_id` (`reclamation_id`),
                                  CONSTRAINT `pieces_jointes_ibfk_1` FOREIGN KEY (`reclamation_id`) REFERENCES `reclamations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. INSERTION DES DONNÉES

-- Annonces
INSERT INTO `annonces` (`id`, `titre`, `type`, `description`, `date_evenement`, `date_creation`) VALUES
                                                                                                     (1, 'Coupure d\'eau - Bâtiment A', 'maintenance', 'Une coupure d\'eau est prévue ce samedi pour des travaux de maintenance au Bâtiment A (Garçons).', '2025-12-12', '2025-12-08 09:00:00'),
                                                                                                     (2, 'Réunion des résidents', 'reunion', 'Réunion trimestrielle avec l\'administration à l\'amphithéâtre central.', '2025-12-15', '2025-12-10 14:00:00'),
                                                                                                     (3, 'Nouvelle procédure Wi-Fi', 'info', 'Veuillez récupérer vos nouveaux identifiants Wi-Fi au bureau d\'accueil du Bâtiment B.', '2025-12-10', '2025-12-05 08:30:00'),
(4, 'Alerte Incendie - Exercice', 'urgence', 'Un exercice d\'évacuation aura lieu le 20 décembre.', '2025-12-20', '2025-12-11 10:00:00');

-- Categories
INSERT INTO `categories` (`id`, `nom`) VALUES
                                           (1, 'Plomberie'),
                                           (2, 'Électricité'),
                                           (3, 'Menuiserie'),
                                           (4, 'Internet / Réseau'),
                                           (5, 'Hygiène / Nettoyage'),
                                           (6, 'Sécurité'),
                                           (7, 'Autre');

-- FAQs
INSERT INTO `faqs` (`id`, `question`, `reponse`, `ordre`) VALUES
                                                              (1, 'Comment suivre l\'avancement de ma réclamation ?', 'Vous pouvez suivre l\'état de votre demande directement depuis votre tableau de bord.', 1),
                                                              (2, 'Quel est le délai de traitement ?', 'Les urgences sont traitées sous 24h, les autres sous 3 à 5 jours ouvrables.', 2),
                                                              (3, 'Problème de connexion Wi-Fi ?', 'Vérifiez d\'abord si vous captez le signal. Si le problème persiste, signalez-le ici.', 3);

-- Lieux (Types de lieux génériques)
INSERT INTO `lieux` (`id`, `nom`) VALUES
(1, 'Chambre'),
(2, 'Couloir'),
(3, 'Cuisine Collective'),
(4, 'Douches / Toilettes'),
(5, 'Salle d\'études'),
                                                              (6, 'Jardin / Extérieur'),
                                                              (7, 'Autre');

-- Users
-- Logic :
-- Bâtiment A = Garçons (BAT-A-XXX)
-- Bâtiment B = Filles (BAT-B-XXX)
INSERT INTO `users` (`id`, `nom`, `email`, `password`, `role`, `code_logement`, `type_occupant`, `telephone`, `date_creation`) VALUES
-- Admin
(1, 'Directeur', 'admin.directeur@recite.ma', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NULL, NULL, '0661111111', '2025-11-01 10:00:00'),

-- Gestionnaires (Un pour chaque bâtiment par exemple)
(2, 'Gestionnaire Bat A', 'gestionnaire.a@recite.ma', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'gestionnaire', NULL, NULL, '0662222222', '2025-11-01 10:00:00'),
(3, 'Gestionnaire Bat B', 'gestionnaire.b@recite.ma', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'gestionnaire', NULL, NULL, '0663333333', '2025-11-01 10:00:00'),

-- Étudiants Garçons (Bâtiment A - Max 300)
(6, 'Alami Ahmed', 'alami.ahmed@etu.ac.ma', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'reclamant', 'BAT-A-101', 'resident', '0600000001', '2025-11-15 09:00:00'),
(8, 'ElIdrissi Youssef', 'elidrissi.youssef@etu.ac.ma', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'reclamant', 'BAT-A-112', 'resident', '0600000003', '2025-11-16 10:00:00'),
(10, 'Berrada Omar', 'berrada.omar@etu.ac.ma', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'reclamant', 'BAT-A-299', 'resident', '0600000005', '2025-11-17 08:30:00'),
(12, 'Bennani Karim', 'bennani.karim@etu.ac.ma', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'reclamant', 'BAT-A-102', 'resident', '0600000007', '2025-11-18 15:30:00'),
(14, 'Chraibi Mehdi', 'chraibi.mehdi@etu.ac.ma', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'reclamant', 'BAT-A-122', 'resident', '0600000009', '2025-11-20 16:45:00'),
(16, 'Bouhali Anass', 'bouhali.anass@etu.ac.ma', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'reclamant', 'BAT-A-103', 'resident', '0600000011', '2025-11-21 08:00:00'),
(18, 'Daoudi Rachid', 'daoudi.rachid@etu.ac.ma', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'reclamant', 'BAT-A-108', 'resident', '0600000013', '2025-11-22 12:00:00'),
(20, 'Hilali Tariq', 'hilali.tariq@etu.ac.ma', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'reclamant', 'BAT-A-114', 'resident', '0600000015', '2025-11-23 13:30:00'),
(22, 'Naciri Bilal', 'naciri.bilal@etu.ac.ma', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'reclamant', 'BAT-A-113', 'resident', '0600000017', '2025-11-25 15:00:00'),
(24, 'Jettou Othman', 'jettou.othman@etu.ac.ma', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'reclamant', 'BAT-A-116', 'resident', '0600000019', '2025-11-26 10:00:00'),

-- Étudiants Filles (Bâtiment B - Max 300)
(7, 'Benani Fatima', 'benani.fatima@etu.ac.ma', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'reclamant', 'BAT-B-204', 'resident', '0600000002', '2025-11-15 09:00:00'),
(9, 'Tazi Salma', 'tazi.salma@etu.ac.ma', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'reclamant', 'BAT-B-105', 'resident', '0600000004', '2025-11-16 11:00:00'),
(11, 'Moutawakil Hajar', 'moutawakil.hajar@etu.ac.ma', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'reclamant', 'BAT-B-111', 'resident', '0600000006', '2025-11-18 14:00:00'),
(13, 'Amrani Noura', 'amrani.noura@etu.ac.ma', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'reclamant', 'BAT-B-115', 'resident', '0600000008', '2025-11-19 09:15:00'),
(15, 'ElFassi Khadija', 'elfassi.khadija@etu.ac.ma', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'reclamant', 'BAT-B-101', 'resident', '0600000010', '2025-11-20 17:00:00'),
(17, 'Zaki Imane', 'zaki.imane@etu.ac.ma', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'reclamant', 'BAT-B-106', 'resident', '0600000012', '2025-11-22 10:30:00'),
(19, 'Mansouri Sanae', 'mansouri.sanae@etu.ac.ma', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'reclamant', 'BAT-B-205', 'resident', '0600000014', '2025-11-23 11:00:00'),
(21, 'Sefrioui Meryem', 'sefrioui.meryem@etu.ac.ma', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'reclamant', 'BAT-B-290', 'resident', '0600000016', '2025-11-24 09:00:00'),
(23, 'Kabbaj Samira', 'kabbaj.samira@etu.ac.ma', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'reclamant', 'BAT-B-102', 'resident', '0600000018', '2025-11-25 16:30:00'),
(25, 'Filali Zineb', 'filali.zineb@etu.ac.ma', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'reclamant', 'BAT-B-104', 'resident', '0600000020', '2025-11-26 11:45:00');

-- Reclamations (Toutes entre le 01 et le 11 Décembre 2025)
INSERT INTO `reclamations` (`id`, `user_id`, `categorie_id`, `objet`, `description`, `lieu`, `urgence`, `statut`, `date_creation`) VALUES
                                                                                                                                       (1, 6, 1, 'Fuite d\'eau lavabo', 'Le robinet de ma chambre goutte sans arrêt depuis 2 jours.', 'Chambre', 'Moyenne', 'en_attente', '2025-12-01 08:30:00'),
(2, 7, 2, 'Ampoule grillée couloir', 'L\'ampoule du couloir B au 2ème étage ne marche plus, il fait très sombre.', 'Couloir', 'Faible', 'en_cours', '2025-12-02 18:45:00'),
                                                                                                                                       (3, 8, 4, 'Connexion introuvable', 'Le signal Wi-Fi est très faible dans le bloc A.', 'Chambre', 'Haute', 'en_attente', '2025-12-03 09:15:00'),
                                                                                                                                       (4, 9, 3, 'Porte placard bloquée', 'La porte de mon placard est coincée, impossible de l\'ouvrir.', 'Chambre', 'Faible', 'resolu', '2025-12-03 14:00:00'),
(5, 10, 5, 'Poubelles non vidées', 'Les poubelles du couloir D débordent, odeur désagréable.', 'Couloir', 'Moyenne', 'en_attente', '2025-12-04 07:30:00'),
(6, 11, 7, 'Bruit excessif voisins', 'Les voisins de la chambre d\'à côté font du bruit tard le soir.', 'Chambre', 'Faible', 'rejete', '2025-12-05 23:00:00'),
                                                                                                                                       (7, 12, 1, 'Chasse d\'eau en panne', 'La chasse d\'eau des toilettes communes du 1er étage ne fonctionne plus.', 'Douches / Toilettes', 'Urgent', 'en_cours', '2025-12-06 06:30:00'),
                                                                                                                                       (8, 13, 2, 'Prise électrique défectueuse', 'Une prise dans ma chambre fait des étincelles quand je branche mon PC.', 'Chambre', 'Urgent', 'en_attente', '2025-12-06 10:45:00'),
                                                                                                                                       (9, 14, 4, 'Coupures fréquentes', 'Le wi-fi se coupe toutes les 5 minutes.', 'Salle d\'études', 'Moyenne', 'resolu', '2025-12-07 15:20:00'),
(10, 15, 6, 'Fenêtre cassée', 'La vitre de la fenêtre du couloir est brisée.', 'Couloir', 'Haute', 'en_cours', '2025-12-07 19:00:00'),
(11, 16, 1, 'Pas d\'eau chaude', 'Il n\'y a plus d\'eau chaude dans les douches depuis ce matin.', 'Douches / Toilettes', 'Haute', 'en_attente', '2025-12-08 07:00:00'),
                                                                                                                                       (12, 17, 3, 'Lit grinçant', 'Le sommier de mon lit grince énormément.', 'Chambre', 'Faible', 'en_attente', '2025-12-08 22:30:00'),
                                                                                                                                       (13, 18, 7, 'Clé perdue', 'J\'ai perdu la clé de ma boîte aux lettres.', 'Hall d\'entrée', 'Faible', 'resolu', '2025-12-09 09:00:00'),
                                                                                                                                       (14, 19, 5, 'Nettoyage non fait', 'Le ménage n\'a pas été fait dans la cuisine commune.', 'Cuisine Collective', 'Moyenne', 'en_attente', '2025-12-09 13:45:00'),
(15, 20, 2, 'Interrupteur cassé', 'L\'interrupteur de la lumière principale ne tient plus.', 'Chambre', 'Moyenne', 'en_cours', '2025-12-10 11:15:00'),
                                                                                                                                       (16, 21, 4, 'Connexion lente', 'Impossible de charger les cours en ligne.', 'Chambre', 'Haute', 'resolu', '2025-12-10 16:30:00'),
                                                                                                                                       (17, 22, 3, 'Chaise bancale', 'Un pied de ma chaise de bureau est cassé.', 'Chambre', 'Faible', 'en_attente', '2025-12-11 08:45:00'),
                                                                                                                                       (18, 23, 1, 'Fuite radiateur', 'De l\'eau coule lentement du radiateur.', 'Chambre', 'Moyenne', 'en_attente', '2025-12-11 10:20:00'),
(19, 24, 6, 'Lumière extérieure HS', 'Il fait noir total devant l\'entrée du bloc B.', 'Jardin / Extérieur', 'Haute', 'resolu', '2025-12-11 19:30:00'),
                                                                                                                                       (20, 25, 5, 'Insectes', 'Présence de fourmis dans la cuisine.', 'Cuisine Collective', 'Moyenne', 'en_attente', '2025-12-11 20:00:00');

-- --------------------------------------------------------
-- AJOUTS POUR RENDRE LA BASE VIVANTE (Interactions)
-- --------------------------------------------------------

-- 1. Ajout de commentaires (Dialogues Gestionnaire <-> Étudiant)
INSERT INTO `commentaires` (`reclamation_id`, `user_id`, `contenu`, `date_commentaire`) VALUES
                                                                                            (2, 2, 'Bonjour Fatima, le technicien passera demain à 10h. Merci de votre patience.', '2025-12-03 09:00:00'),
                                                                                            (2, 7, 'D\'accord merci, je serai présente.', '2025-12-03 11:00:00'),
(7, 2, 'Nous avons commandé la pièce manquante pour la chasse d\'eau.', '2025-12-06 14:00:00'),
                                                                                            (9, 3, 'Le redémarrage du routeur a été effectué. Pouvez-vous confirmer si cela fonctionne ?', '2025-12-07 16:00:00'),
                                                                                            (9, 14, 'Oui c\'est beaucoup mieux merci !', '2025-12-07 17:30:00'),
(16, 2, 'C\'est noté, nous allons vérifier le câblage.', '2025-12-10 17:00:00');

-- 2. Ajout de l'historique des statuts (Pour montrer l'évolution dans le temps)
INSERT INTO `historique_statuts` (`reclamation_id`, `ancien_statut`, `nouveau_statut`, `user_id`, `date_changement`) VALUES
                                                                                                                         (2, 'en_attente', 'en_cours', 2, '2025-12-03 08:30:00'),
                                                                                                                         (4, 'en_attente', 'en_cours', 3, '2025-12-03 14:30:00'),
                                                                                                                         (4, 'en_cours', 'resolu', 3, '2025-12-04 10:00:00'),
                                                                                                                         (6, 'en_attente', 'rejete', 2, '2025-12-06 09:00:00'),
                                                                                                                         (9, 'en_attente', 'en_cours', 3, '2025-12-07 15:45:00'),
                                                                                                                         (9, 'en_cours', 'resolu', 3, '2025-12-07 18:00:00'),
                                                                                                                         (13, 'en_attente', 'resolu', 2, '2025-12-09 11:00:00'),
                                                                                                                         (19, 'en_attente', 'resolu', 3, '2025-12-11 20:30:00');

-- 3. Ajout de Notifications (Pour tester la cloche de notification)
INSERT INTO `notifications` (`user_id`, `type`, `message`, `lien`, `is_read`, `created_at`) VALUES
                                                                                                (7, 'commentaire', 'Nouveau commentaire du gestionnaire sur votre réclamation #2', '/reclamations/2', 0, '2025-12-03 09:00:00'),
                                                                                                (14, 'statut', 'Votre réclamation #9 est passée au statut : Résolu', '/reclamations/9', 1, '2025-12-07 18:00:00'),
                                                                                                (11, 'statut', 'Votre réclamation #6 a été rejetée.', '/reclamations/6', 0, '2025-12-06 09:00:00'),
                                                                                                (6, 'info', 'Coupure d\'eau prévue ce samedi.', '/annonces/1', 0, '2025-12-08 09:00:00');

-- 4. Ajout de Pièces Jointes fictives (Pour tester l'affichage des fichiers)
    INSERT INTO `pieces_jointes` (`reclamation_id`, `chemin_fichier`, `nom_fichier`) VALUES
    (1, 'uploads/fuite_robinet.jpg', 'fuite_robinet.jpg'),
    (8, 'uploads/prise_brule.jpg', 'prise_danger.jpg'),
    (10, 'uploads/vitre_cassee.png', 'img_vitre.png');

COMMIT;
-- FIN DU SCRIPT COMPLET