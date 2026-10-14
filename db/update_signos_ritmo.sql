-- ============================================================
-- MIGRACIÓN: Agregar columna 'ritmo' a la tabla 'signos_pacientes'
-- Ejecutar en phpMyAdmin sobre la BD simulador_medico
-- ============================================================

ALTER TABLE `signos_pacientes` ADD COLUMN `ritmo` varchar(50) DEFAULT 'sinusal';
