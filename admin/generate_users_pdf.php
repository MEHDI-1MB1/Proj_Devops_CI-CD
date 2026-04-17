<?php
require('../config/db.php');
require('../includes/fpdf.php');  

class PDF extends FPDF {
     
    function Header() {
         
         

        $this->SetFont('Arial','B',15);
         
        $this->Cell(0,10,utf8_decode('Liste des Utilisateurs'),0,1,'C');
        $this->Ln(5);

         
        $this->SetFont('Arial','I',10);
        $this->Cell(0,10,'Genere le : ' . date('d/m/Y'),0,1,'R');
        $this->Ln(5);

         
        $this->SetFont('Arial','B',10);
        $this->SetFillColor(5, 27, 52);  
        $this->SetTextColor(255);  

         
        $this->Cell(15,10,'ID',1,0,'C',true);
        $this->Cell(55,10,'Nom Complet',1,0,'L',true);
        $this->Cell(65,10,'Email',1,0,'L',true);
        $this->Cell(30,10,utf8_decode('Rôle'),1,0,'C',true);
        $this->Cell(25,10,'Date',1,1,'C',true);
    }

     
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
    }
}

 
$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial','',10);
$pdf->SetTextColor(0);  

 
global $pdo;
$stmt = $pdo->query("SELECT * FROM users ORDER BY id DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

 
foreach($users as $user) {
     
     

    $pdf->Cell(15,10,$user['id'],1,0,'C');
    $pdf->Cell(55,10,utf8_decode($user['nom']),1,0,'L');
    $pdf->Cell(65,10,utf8_decode($user['email']),1,0,'L');

     
    $role = ucfirst($user['role']);
    $pdf->Cell(30,10,utf8_decode($role),1,0,'C');

     
    $date = isset($user['date_creation']) ? date('d/m/Y', strtotime($user['date_creation'])) : '-';
    $pdf->Cell(25,10,$date,1,1,'C');
}

$pdf->Output('D', 'liste_utilisateurs.pdf');  
?>