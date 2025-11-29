<?php
require_once dirname(__FILE__) . '/../../functions/connexion.php';

// Fonction pour vérifier s'il y a un chevauchement de période pour une usine
function checkPeriodOverlap($conn, $id_usine, $date_debut, $date_fin = null, $exclude_id = null) {
    try {
        // Si date_fin est null, on considère que la période est ouverte (jusqu'à aujourd'hui ou indéfiniment)
        $sql = "SELECT id, prix, date_debut, date_fin, 
                       CASE 
                           WHEN date_fin IS NULL THEN 'Période ouverte'
                           ELSE CONCAT(DATE_FORMAT(date_debut, '%d/%m/%Y'), ' - ', DATE_FORMAT(date_fin, '%d/%m/%Y'))
                       END as periode_str
                FROM prix_unitaires 
                WHERE id_usine = :id_usine";
        
        // Ajouter la condition d'exclusion si on modifie un enregistrement existant
        if ($exclude_id) {
            $sql .= " AND id != :exclude_id";
        }
        
        // Logique de chevauchement de périodes SIMPLIFIÉE et CORRIGÉE
        $sql .= " AND (
                    -- Cas simple: Deux périodes se chevauchent si :
                    -- Le début de l'une est <= à la fin de l'autre ET
                    -- Le début de l'autre est <= à la fin de l'une
                    (
                        date_debut <= COALESCE(:date_fin, '9999-12-31') 
                        AND 
                        :date_debut <= COALESCE(date_fin, '9999-12-31')
                    )
                )";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id_usine', $id_usine, PDO::PARAM_INT);
        $stmt->bindParam(':date_debut', $date_debut);
        $stmt->bindParam(':date_fin', $date_fin);
        
        if ($exclude_id) {
            $stmt->bindParam(':exclude_id', $exclude_id, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Erreur lors de la vérification du chevauchement: " . $e->getMessage());
        return false;
    }
}

// Fonction pour créer un nouveau prix unitaire
function createPrixUnitaire($conn, $id_usine, $prix, $date_debut, $date_fin = null) {
    try {
        // Vérifier s'il y a un chevauchement de période
        $overlaps = checkPeriodOverlap($conn, $id_usine, $date_debut, $date_fin);
        
        if ($overlaps && count($overlaps) > 0) {
            // Retourner les informations sur le conflit
            return [
                'success' => false,
                'error' => 'period_overlap',
                'message' => 'Un prix unitaire existe déjà pour cette usine dans la période spécifiée.',
                'conflicting_periods' => $overlaps
            ];
        }
        
        $sql = "INSERT INTO prix_unitaires (id_usine, prix, date_debut, date_fin) VALUES (:id_usine, :prix, :date_debut, :date_fin)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id_usine', $id_usine, PDO::PARAM_INT);
        $stmt->bindParam(':prix', $prix);
        $stmt->bindParam(':date_debut', $date_debut);
        $stmt->bindParam(':date_fin', $date_fin);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Prix unitaire créé avec succès.'];
        } else {
            return ['success' => false, 'error' => 'database_error', 'message' => 'Erreur lors de la création du prix unitaire.'];
        }
        
    } catch (PDOException $e) {
        error_log("Erreur lors de la création du prix unitaire: " . $e->getMessage());
        return ['success' => false, 'error' => 'database_error', 'message' => 'Erreur de base de données: ' . $e->getMessage()];
    }
}

// Fonction pour mettre à jour un prix unitaire
function updatePrixUnitaire($conn, $id, $id_usine, $prix, $date_debut, $date_fin = null) {
    try {
        // Vérifier s'il y a un chevauchement de période (en excluant l'enregistrement actuel)
        $overlaps = checkPeriodOverlap($conn, $id_usine, $date_debut, $date_fin, $id);
        
        if ($overlaps && count($overlaps) > 0) {
            // Retourner les informations sur le conflit
            return [
                'success' => false,
                'error' => 'period_overlap',
                'message' => 'Un prix unitaire existe déjà pour cette usine dans la période spécifiée.',
                'conflicting_periods' => $overlaps
            ];
        }
        
        $sql = "UPDATE prix_unitaires SET id_usine = :id_usine, prix = :prix, date_debut = :date_debut, date_fin = :date_fin WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':id_usine', $id_usine, PDO::PARAM_INT);
        $stmt->bindParam(':prix', $prix);
        $stmt->bindParam(':date_debut', $date_debut);
        $stmt->bindParam(':date_fin', $date_fin);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Prix unitaire mis à jour avec succès.'];
        } else {
            return ['success' => false, 'error' => 'database_error', 'message' => 'Erreur lors de la mise à jour du prix unitaire.'];
        }
        
    } catch (PDOException $e) {
        error_log("Erreur lors de la mise à jour du prix unitaire: " . $e->getMessage());
        return ['success' => false, 'error' => 'database_error', 'message' => 'Erreur de base de données: ' . $e->getMessage()];
    }
}

// Fonction pour supprimer un prix unitaire
function deletePrixUnitaire($conn, $id) {
    try {
        $sql = "DELETE FROM prix_unitaires WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Erreur lors de la suppression du prix unitaire: " . $e->getMessage());
        return false;
    }
}

// Fonction pour récupérer un prix unitaire par son ID
function getPrixUnitaireById($conn, $id) {
    try {
        $sql = "SELECT * FROM prix_unitaires WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération du prix unitaire: " . $e->getMessage());
        return false;
    }
}

// Fonction pour récupérer tous les prix unitaires
function getAllPrixUnitaires($conn) {
    try {
        $sql = "SELECT prix_unitaires.*, usines.nom_usine 
                FROM prix_unitaires 
                INNER JOIN usines ON prix_unitaires.id_usine = usines.id_usine 
                ORDER BY prix_unitaires.date_debut DESC";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des prix unitaires: " . $e->getMessage());
        return false;
    }
}

// Fonction pour récupérer le dernier prix unitaire d'une usine
function getLastPrixUnitaire($conn, $id_usine) {
    try {
        $sql = "SELECT prix 
                FROM prix_unitaires 
                WHERE id_usine = :id_usine 
                ORDER BY date_debut DESC 
                LIMIT 1";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id_usine', $id_usine, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['prix'] : 0;
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération du dernier prix unitaire: " . $e->getMessage());
        return 0;
    }
}

// Fonction pour récupérer le prix unitaire en fonction de la date et de l'usine
function getPrixUnitaireByDateAndUsine($conn, $date_ticket, $id_usine) {
    try {
        // Vérifier si la date du ticket correspond à une période de prix unitaire pour cette usine
        $sql = "SELECT prix 
                FROM prix_unitaires 
                WHERE id_usine = ? 
                AND date_debut <= ?
                AND (date_fin IS NULL OR date_fin >= ?)
                ORDER BY date_debut DESC
                LIMIT 1";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$id_usine, $date_ticket, $date_ticket]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            return ['prix' => $result['prix'], 'is_default' => false];
        }
        
        // Si aucun prix n'est trouvé, retourner la valeur par défaut (0.00)
        return ['prix' => 0.00, 'is_default' => true];
        
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération du prix unitaire: " . $e->getMessage());
        return ['prix' => 0.00, 'is_default' => true];
    }
}
?>
