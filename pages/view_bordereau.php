<?php
// Désactiver la mise en mémoire tampon de sortie
ob_clean();

require('../fpdf/fpdf.php');
require_once '../inc/functions/connexion.php';

if (isset($_GET['numero'])) {
    $numero_bordereau = $_GET['numero'];

    // Récupérer les informations du bordereau
    $sql_bordereau = "SELECT b.*, 
                     CONCAT(COALESCE(a.nom, ''), ' ', COALESCE(a.prenom, '')) AS nom_complet_agent
                     FROM bordereau b
                     INNER JOIN agents a ON b.id_agent = a.id_agent
                     WHERE b.numero_bordereau = :numero_bordereau";

    $stmt_bordereau = $conn->prepare($sql_bordereau);
    $stmt_bordereau->bindParam(':numero_bordereau', $numero_bordereau);
    $stmt_bordereau->execute();
    $bordereau = $stmt_bordereau->fetch(PDO::FETCH_ASSOC);

    if ($bordereau) {
        // Calculer le montant total du bordereau
        $sql_montant = "SELECT SUM(t.poids * t.prix_unitaire) as montant_total
                        FROM tickets t
                        WHERE t.numero_bordereau = :numero_bordereau";
        
        $stmt_montant = $conn->prepare($sql_montant);
        $stmt_montant->bindParam(':numero_bordereau', $numero_bordereau);
        $stmt_montant->execute();
        $result_montant = $stmt_montant->fetch(PDO::FETCH_ASSOC);
        $montant_total_bordereau = $result_montant['montant_total'] ?? 0;

        class PDF extends FPDF {
            function Header() {
                if (file_exists('../dist/img/logo.png')) {
                    $this->Image('../dist/img/logo.png', 10, 10, 30);
                }
                
                // Titre de l'entreprise en vert
                $this->SetTextColor(0, 128, 0);
                $this->SetFont('Arial', 'B', 16);
                $this->Cell(0, 10, 'UNIPALM COOP - CA', 0, 1, 'C');
                
                // Sous-titre en vert clair
                $this->SetTextColor(144, 238, 144);
                $this->SetFont('Arial', '', 11);
                $this->Cell(0, 5, mb_convert_encoding('Société Coopérative Agricole Unie pour le Palmier', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
                
                $this->Ln(15);
            }

            function Footer() {
                $this->SetY(-20);
                
                // Ligne verte
                $this->SetDrawColor(144, 238, 144);
                $this->Line(10, $this->GetY(), 200, $this->GetY());
                
                // Informations de contact en noir
                $this->SetTextColor(0, 0, 0);
                $this->SetFont('Arial', '', 8);
                $this->Cell(0, 5, mb_convert_encoding('Siège Social : Divo Quartier millionnaire non loin de l\'hôtel Boya', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
                $this->Cell(0, 5, mb_convert_encoding('NCC : 2050R910 / TEL : (00225) 27 34 75 92 36 / 07 49 17 16 32', 'ISO-8859-1', 'UTF-8'), 0, 0, 'C');
            }
        }

        $pdf = new PDF();
        $pdf->AddPage();
        $pdf->SetAutoPageBreak(true, 35);

        // Titre du bordereau
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(0, 10, mb_convert_encoding('BORDEREAU DE DÉCHARGEMENT N° ', 'ISO-8859-1', 'UTF-8') . $bordereau['numero_bordereau'], 0, 1, 'C');
        $pdf->Ln(5);

        // Section informations du bordereau - Design professionnel
        // En-tête avec fond coloré
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetFillColor(52, 73, 94); // Bleu foncé professionnel
        $pdf->SetTextColor(255, 255, 255); // Texte blanc
        $pdf->Cell(190, 10, mb_convert_encoding('INFORMATIONS DU BORDEREAU', 'ISO-8859-1', 'UTF-8'), 1, 1, 'C', true);
        
        // Réinitialiser les couleurs pour le contenu
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFillColor(248, 249, 250); // Gris très clair pour alternance
        
        // Informations en deux colonnes pour un look plus professionnel
        $pdf->SetFont('Arial', 'B', 10);
        
        // Ligne 1: Agent et Période
        $pdf->Cell(95, 8, mb_convert_encoding('AGENT RESPONSABLE', 'ISO-8859-1', 'UTF-8'), 'LTB', 0, 'L', true);
        $pdf->Cell(95, 8, mb_convert_encoding('PÉRIODE DE COLLECTE', 'ISO-8859-1', 'UTF-8'), 'RTB', 1, 'L', true);
        
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(95, 8, mb_convert_encoding($bordereau['nom_complet_agent'], 'ISO-8859-1', 'UTF-8'), 'LB', 0, 'L');
        $pdf->Cell(95, 8, date('d/m/Y', strtotime($bordereau['date_debut'])) . ' au ' . date('d/m/Y', strtotime($bordereau['date_fin'])), 'RB', 1, 'L');
        
        // Ligne 2: Poids et Montant
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(95, 8, mb_convert_encoding('POIDS TOTAL COLLECTÉ', 'ISO-8859-1', 'UTF-8'), 'LTB', 0, 'L', true);
        $pdf->Cell(95, 8, mb_convert_encoding('MONTANT TOTAL', 'ISO-8859-1', 'UTF-8'), 'RTB', 1, 'L', true);
        
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(22, 160, 133); // Vert pour le poids
        $pdf->Cell(95, 8, number_format($bordereau['poids_total'], 0, ',', ' ') . ' KG', 'LB', 0, 'L');
        $pdf->SetTextColor(231, 76, 60); // Rouge pour le montant
        $pdf->Cell(95, 8, number_format($montant_total_bordereau, 0, ',', ' ') . ' FCFA', 'RB', 1, 'L');
        
        // Ligne 3: Date de création centrée
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(190, 8, mb_convert_encoding('DATE DE CRÉATION', 'ISO-8859-1', 'UTF-8'), 'LTR', 1, 'C', true);
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(190, 8, mb_convert_encoding(date('d/m/Y à H:i', strtotime($bordereau['created_at'])), 'ISO-8859-1', 'UTF-8'), 'LBR', 1, 'C');
        
        $pdf->Ln(10);

        // En-têtes du tableau des tickets
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetFillColor(197, 217, 241); // Bleu clair comme dans l'image
        $pdf->Cell(25, 8, 'Date', 1, 0, 'C', true);
        $pdf->Cell(55, 8, mb_convert_encoding('N° Ticket', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $pdf->Cell(25, 8, 'Usine', 1, 0, 'C', true);
        $pdf->Cell(25, 8, mb_convert_encoding('Véhicule', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        $pdf->Cell(15, 8, 'Poids (Kg)', 1, 0, 'C', true);
        $pdf->Cell(10, 8, 'Prix Unit.', 1, 0, 'C', true);
        $pdf->Cell(25, 8, 'Montant', 1, 1, 'C', true);

        // Récupérer les tickets associés au bordereau avec les prix unitaires
        $sql = "SELECT t.*, u.nom_usine, 
                v.matricule_vehicule, 
                v.type_vehicule,
                t.prix_unitaire,
                (t.poids * t.prix_unitaire) as montant_ticket
                FROM tickets t
                INNER JOIN usines u ON t.id_usine = u.id_usine
                INNER JOIN vehicules v ON t.vehicule_id = v.vehicules_id
                WHERE t.numero_bordereau = :numero_bordereau
                ORDER BY u.nom_usine, t.date_ticket, t.created_at";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':numero_bordereau', $numero_bordereau);
        $stmt->execute();
        $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Données du tableau
        $pdf->SetFont('Arial', '', 8);
        $total_poids = 0;
        $total_montant = 0;
        $current_usine = '';
        $sous_total_poids = 0;
        $sous_total_montant = 0;

        foreach ($tickets as $ticket) {
            if ($current_usine != $ticket['nom_usine'] && $current_usine != '') {
                // Sous-total
                $pdf->SetFont('Arial', 'I', 8);
                $pdf->SetFillColor(240, 240, 240);
                $pdf->Cell(130, 8, 'Sous-total ' . mb_convert_encoding($current_usine, 'ISO-8859-1', 'UTF-8'), 1, 0, 'R', true);
                $pdf->Cell(15, 8, number_format($sous_total_poids, 0, ',', ' '), 1, 0, 'R', true);
                $pdf->Cell(10, 8, '', 1, 0, 'C', true); // Colonne prix unitaire vide pour sous-total
                $pdf->Cell(25, 8, number_format($sous_total_montant, 0, ',', ' '), 1, 1, 'R', true);
                $sous_total_poids = 0;
                $sous_total_montant = 0;
                $pdf->SetFont('Arial', '', 8);
            }

            $current_usine = $ticket['nom_usine'];
            $poids = $ticket['poids'];
            $prix_unitaire = $ticket['prix_unitaire'];
            $montant = $ticket['montant_ticket'];

            $pdf->Cell(25, 8, date('d/m/Y', strtotime($ticket['date_ticket'])), 1, 0, 'C');
            $pdf->Cell(55, 8, mb_convert_encoding($ticket['numero_ticket'], 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
            $pdf->Cell(25, 8, mb_convert_encoding($ticket['nom_usine'], 'ISO-8859-1', 'UTF-8'), 1, 0, 'L');
            $pdf->Cell(25, 8, mb_convert_encoding($ticket['matricule_vehicule'], 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
            $pdf->Cell(15, 8, number_format($poids, 0, ',', ' '), 1, 0, 'R');
            $pdf->Cell(10, 8, number_format($prix_unitaire, 0, ',', ' '), 1, 0, 'R');
            $pdf->Cell(25, 8, number_format($montant, 0, ',', ' '), 1, 1, 'R');

            $total_poids += $poids;
            $total_montant += $montant;
            $sous_total_poids += $poids;
            $sous_total_montant += $montant;
        }

        // Dernier sous-total
        if ($current_usine != '') {
            $pdf->SetFont('Arial', 'I', 8);
            $pdf->SetFillColor(240, 240, 240);
            $pdf->Cell(130, 8, 'Sous-total ' . mb_convert_encoding($current_usine, 'ISO-8859-1', 'UTF-8'), 1, 0, 'R', true);
            $pdf->Cell(15, 8, number_format($sous_total_poids, 0, ',', ' '), 1, 0, 'R', true);
            $pdf->Cell(10, 8, '', 1, 0, 'C', true); // Colonne prix unitaire vide pour sous-total
            $pdf->Cell(25, 8, number_format($sous_total_montant, 0, ',', ' '), 1, 1, 'R', true);
        }

        // Total général
        $pdf->Ln(3);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetFillColor(197, 217, 241);
        $pdf->Cell(130, 10, 'TOTAL GENERAL', 1, 0, 'R', true);
        $pdf->Cell(15, 10, number_format($total_poids, 0, ',', ' '), 1, 0, 'R', true);
        $pdf->Cell(10, 10, '', 1, 0, 'C', true); // Colonne prix unitaire vide pour total
        $pdf->Cell(25, 10, number_format($total_montant, 0, ',', ' '), 1, 1, 'R', true);

        // Zone de signatures
        $pdf->Ln(20);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(95, 7, "Signature de l'agent:", 0, 0, 'L');
        $pdf->Cell(95, 7, "Signature du responsable:", 0, 1, 'L');
        
        $pdf->Cell(95, 20, '', 'B', 0, 'L');
        $pdf->Cell(95, 20, '', 'B', 1, 'L');

        $pdf->Output('I', 'Bordereau_' . $numero_bordereau . '.pdf');
    } else {
        die("Bordereau non trouvé");
    }
} else {
    die("Numéro de bordereau non spécifié");
}
?>
