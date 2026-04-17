<?php

require_once '../config/db.php';
require_once '../includes/functions.php';
require_once '../includes/fpdf.php';

require_role('gestionnaire');


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


$sql = "
    SELECT r.*, c.nom as categorie_nom, u.nom as user_nom 
    FROM reclamations r 
    JOIN categories c ON r.categorie_id = c.id 
    JOIN users u ON r.user_id = u.id 
    WHERE $where
    ORDER BY r.date_creation DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reclamations = $stmt->fetchAll(PDO::FETCH_ASSOC);


class PDF extends FPDF {
    function Header() {

        $this->SetFont('Arial','B',15);
        $this->SetTextColor(5, 27, 52);
        $this->Cell(0,10,utf8_decode('Rapport des Réclamations'),0,1,'C');
        $this->SetFont('Arial','I',10);
        $this->SetTextColor(128);
        $this->Cell(0,10,utf8_decode('Généré le : ' . date('d/m/Y H:i')),0,1,'C');
        $this->Ln(10);


        $this->SetFillColor(5, 27, 52);
        $this->SetTextColor(255);
        $this->SetFont('Arial','B',10);


        $this->Cell(15,10,'ID',1,0,'C',true);
        $this->Cell(45,10,utf8_decode('Réclamant'),1,0,'L',true);
        $this->Cell(60,10,utf8_decode('Sujet'),1,0,'L',true);
        $this->Cell(30,10,utf8_decode('Lieu'),1,0,'L',true);
        $this->Cell(30,10,utf8_decode('Urgence'),1,0,'C',true);
        $this->Cell(30,10,utf8_decode('Statut'),1,0,'C',true);
        $this->Cell(40,10,'Date',1,1,'C',true);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->SetTextColor(128);
        $this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
    }
}


$pdf = new PDF('L','mm','A4');
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial','',10);
$pdf->SetTextColor(0);


foreach($reclamations as $row) {

    $nom = utf8_decode(substr($row['user_nom'], 0, 20));
    $sujet = utf8_decode(substr($row['objet'], 0, 30));
    $lieu = utf8_decode($row['lieu'] ?? '-');
    $urgence = utf8_decode($row['urgence']);
    $statut = utf8_decode(ucfirst($row['statut']));
    $date = date('d/m/Y', strtotime($row['date_creation']));


    $pdf->SetTextColor(0);

    $pdf->Cell(15,10,'#'.$row['id'],1,0,'C');
    $pdf->Cell(45,10,$nom,1,0,'L');
    $pdf->Cell(60,10,$sujet,1,0,'L');
    $pdf->Cell(30,10,$lieu,1,0,'L');


    if($row['urgence'] == 'Haute') $pdf->SetTextColor(220, 53, 69);
    elseif($row['urgence'] == 'Moyenne') $pdf->SetTextColor(255, 193, 7);
    $pdf->Cell(30,10,$urgence,1,0,'C');
    $pdf->SetTextColor(0);  

    $pdf->Cell(30,10,$statut,1,0,'C');
    $pdf->Cell(40,10,$date,1,1,'C');
}

$pdf->Output('D', 'reclamations_export.pdf');
?>