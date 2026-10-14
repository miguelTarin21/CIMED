-- ============================================================
--  MIGRACIÓN: video único → tabla videos (múltiples videos)
--  Ejecutar en phpMyAdmin sobre la BD simulador_medico
-- ============================================================

-- 1. Crear tabla videos (si no existe)
CREATE TABLE IF NOT EXISTS `videos` (
  `id`      int(11)      NOT NULL AUTO_INCREMENT,
  `nombre`  varchar(255) NOT NULL,
  `ruta`    varchar(255) NOT NULL,
  `id_caso` int(11)      NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_video_caso` (`id_caso`),
  CONSTRAINT `videos_ibfk_1`
    FOREIGN KEY (`id_caso`) REFERENCES `casos` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. Migrar y eliminar la columna video_nombre de forma segura
DELIMITER //

CREATE PROCEDURE MigrateVideosIfColumnExists()
BEGIN
    -- Verificar si la columna video_nombre existe en la tabla casos
    IF EXISTS (
        SELECT 1 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
          AND TABLE_NAME = 'casos' 
          AND COLUMN_NAME = 'video_nombre'
    ) THEN
        -- Migrar los videos existentes de la columna video_nombre
        INSERT INTO `videos` (`nombre`, `ruta`, `id_caso`)
        SELECT
          `video_nombre`,
          CONCAT('./uploads/videos/', `video_nombre`),
          `id`
        FROM `casos`
        WHERE `video_nombre` IS NOT NULL
          AND `video_nombre` != '';
        
        -- Eliminar la columna video_nombre de casos
        ALTER TABLE `casos` DROP COLUMN `video_nombre`;
    END IF;
END //

DELIMITER ;

-- Ejecutar el procedimiento de migración
CALL MigrateVideosIfColumnExists();

-- Eliminar el procedimiento después de usarlo
DROP PROCEDURE IF EXISTS MigrateVideosIfColumnExists;
