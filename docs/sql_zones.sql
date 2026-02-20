-- =====================================================
-- Script SQL: Création de la table zones
-- Association: Un utilisateur = une zone
-- =====================================================

-- Création de la table zones
CREATE TABLE `zones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Ajout de la colonne zone_id dans la table utilisateurs
ALTER TABLE `utilisateurs` 
ADD COLUMN `zone_id` int(11) DEFAULT NULL AFTER `role`,
ADD CONSTRAINT `fk_utilisateur_zone` FOREIGN KEY (`zone_id`) REFERENCES `zones` (`id`) ON DELETE SET NULL;

-- Index pour améliorer les performances
CREATE INDEX `idx_zone_nom` ON `zones` (`nom`);
CREATE INDEX `idx_utilisateur_zone` ON `utilisateurs` (`zone_id`);

-- =====================================================
-- Exemples d'insertion de zones (à adapter)
-- =====================================================
-- INSERT INTO `zones` (`nom`) VALUES
-- ('Zone Nord'),
-- ('Zone Sud'),
-- ('Zone Est'),
-- ('Zone Ouest');

-- =====================================================
-- Pour assigner une zone à un utilisateur:
-- UPDATE `utilisateurs` SET `zone_id` = 1 WHERE `id` = 4;
-- =====================================================
