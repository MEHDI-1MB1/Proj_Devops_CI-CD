<?php
session_start();

 
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

 
function redirect_by_role($role) {
    $prefix = '';
    $current_dir = basename(getcwd());
    if (in_array($current_dir, ['admin', 'reclamant', 'gestionnaire'])) {
        $prefix = '../';
    }

    switch ($role) {
        case 'reclamant':
            header("Location: " . $prefix . "reclamant/dashboard.php");
            break;
        case 'gestionnaire':
            header("Location: " . $prefix . "gestionnaire/dashboard.php");
            break;
        case 'admin':
            header("Location: " . $prefix . "admin/dashboard.php");
            break;
        default:
            header("Location: " . $prefix . "index.php");
            break;
    }
    exit();
}

 
function require_role($role) {
    if (!is_logged_in()) {
        $prefix = '';
        $current_dir = basename(getcwd());
        if (in_array($current_dir, ['admin', 'reclamant', 'gestionnaire'])) {
            $prefix = '../';
        }
        header("Location: " . $prefix . "index.php");
        exit();
    }
    if ($_SESSION['user_role'] !== $role) {
         
        redirect_by_role($_SESSION['user_role']);
    }
}

 

function addNotification($user_id, $type, $message, $link) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, message, lien) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $type, $message, $link]);
}
 




function getUserNotifications($user_id, $limit = 10) {
    global $pdo;
    $sql = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT " . (int)$limit;
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}




function countUnreadNotifications($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
    return $stmt->fetchColumn();
}




function notifyAllManagers($type, $message, $lien) {
    global $pdo;
     
    $stmt = $pdo->query("SELECT id FROM users WHERE role = 'gestionnaire'");
    $managers = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($managers as $manager_id) {
        addNotification($manager_id, $type, $message, $lien);
    }
}
?>
