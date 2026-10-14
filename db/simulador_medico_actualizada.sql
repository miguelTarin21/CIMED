-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: simulador_medico
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `archivos`
--

DROP TABLE IF EXISTS `archivos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `archivos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `ruta` varchar(255) NOT NULL,
  `id_caso` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_caso` (`id_caso`),
  CONSTRAINT `archivos_ibfk_1` FOREIGN KEY (`id_caso`) REFERENCES `casos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `archivos`
--

LOCK TABLES `archivos` WRITE;
/*!40000 ALTER TABLE `archivos` DISABLE KEYS */;
INSERT INTO `archivos` VALUES (5,'Estudio1','./uploads/caso_5/reporte_firewall.docx.pdf',5),(8,'1','./uploads/caso_8/jrc118163_ai_watch._defining_artificial_intelligence_1.pdf',8),(9,'Estudio 2','./uploads/caso_9/reporte_firewall.docx.pdf',9),(10,'estudio 1','./uploads/caso_9/El-Modelo-Entidad-Relacion.pdf',9),(11,'jsjsjs','./uploads/caso_10/KelpWorld_final.pdf',10),(12,'estudio1','./uploads/caso_11/Meta 4.5 Herramienta basada en SNMP.pdf',11),(13,'Estudio2','./uploads/caso_11/1_Meta 4.5 Herramienta basada en SNMP.pdf',11);
/*!40000 ALTER TABLE `archivos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `carpetas`
--

DROP TABLE IF EXISTS `carpetas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `carpetas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(500) DEFAULT NULL,
  `id_usuario_creador` int(11) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_carpeta_usuario` (`id_usuario_creador`),
  CONSTRAINT `carpeta_usuario_fk` FOREIGN KEY (`id_usuario_creador`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `carpetas`
--

LOCK TABLES `carpetas` WRITE;
/*!40000 ALTER TABLE `carpetas` DISABLE KEYS */;
INSERT INTO `carpetas` VALUES (1,'Respiratorios','',16,'2026-04-09 06:08:09'),(2,'Respiratorios','',15,'2026-04-09 15:19:06'),(3,'Muscular','',15,'2026-05-07 02:32:02'),(4,'Cardiovascular','',15,'2026-05-07 03:49:56'),(5,'Pecho','',15,'2026-05-14 07:08:16');
/*!40000 ALTER TABLE `carpetas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `casos`
--

DROP TABLE IF EXISTS `casos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `casos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `visibilidad` int(11) NOT NULL DEFAULT 0,
  `clave` varchar(255) NOT NULL,
  `descripcion` text NOT NULL,
  `motivo_consulta` text NOT NULL,
  `id_usuario_creador` int(11) DEFAULT NULL,
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `id_carpeta` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_usuario_creador` (`id_usuario_creador`),
  KEY `fk_caso_carpeta` (`id_carpeta`),
  CONSTRAINT `caso_carpeta_fk` FOREIGN KEY (`id_carpeta`) REFERENCES `carpetas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `casos_usuario_fk` FOREIGN KEY (`id_usuario_creador`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `casos`
--

LOCK TABLES `casos` WRITE;
/*!40000 ALTER TABLE `casos` DISABLE KEYS */;
INSERT INTO `casos` VALUES (5,'02210769',1,'','pierde la capacidad de respirar','Presenta problemas respiratorios',16,'2026-04-09 06:24:12',1),(8,'2210760',1,'','Dolor en la espaldo','Dolor en la espalda',15,'2026-05-07 03:48:56',3),(9,'2210761',1,'','El Paciente se queja de dolores fuertes y repentinos en el pecho','Dolores fuertes en el pecgo',15,'2026-05-27 18:38:33',4),(10,'jsjsjs',1,'','jsjsjs','jsjsjs',15,'2026-05-14 06:59:17',3),(11,'02210762',1,'','El paciente empezo con dolores en el pecho','Dolor de Pecho',15,'2026-05-14 17:20:07',5);
/*!40000 ALTER TABLE `casos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `incisos`
--

DROP TABLE IF EXISTS `incisos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `incisos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_pregunta` int(11) DEFAULT NULL,
  `texto` varchar(255) DEFAULT NULL,
  `correcto` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_incisos_pregunta` (`id_pregunta`),
  CONSTRAINT `incisos_ibfk_1` FOREIGN KEY (`id_pregunta`) REFERENCES `preguntas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=89 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `incisos`
--

LOCK TABLES `incisos` WRITE;
/*!40000 ALTER TABLE `incisos` DISABLE KEYS */;
INSERT INTO `incisos` VALUES (25,13,'jsjsjs',1),(26,13,'jsjsjsjs',0),(55,28,'jsjsjsjs',1),(56,28,'jsjsjsj',0),(64,32,'jsjsjsjs',1),(65,32,'jsjsjsj',0),(72,36,'jsjsjsjs',1),(73,36,'jsjsjsj',0),(84,41,'jsjsjsjs',1),(85,41,'jsjsjsj',0),(86,41,'jsjsjs',0),(87,42,'jsjsj',1),(88,42,'jsjjss',0);
/*!40000 ALTER TABLE `incisos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pacientes`
--

DROP TABLE IF EXISTS `pacientes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pacientes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `sexo` varchar(50) NOT NULL,
  `edad` int(11) NOT NULL,
  `peso` float NOT NULL,
  `altura` float NOT NULL,
  `enfermedades` text DEFAULT NULL,
  `id_signos_vitales` int(11) DEFAULT NULL,
  `id_caso` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_perfil_paciente_caso` (`id_caso`),
  KEY `id_signos_vitales` (`id_signos_vitales`),
  CONSTRAINT `pacientes_ibfk_1` FOREIGN KEY (`id_caso`) REFERENCES `casos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `pacientes_ibfk_2` FOREIGN KEY (`id_signos_vitales`) REFERENCES `signos_pacientes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pacientes`
--

LOCK TABLES `pacientes` WRITE;
/*!40000 ALTER TABLE `pacientes` DISABLE KEYS */;
INSERT INTO `pacientes` VALUES (5,'Jorge Ruiz','Hombre',21,68,168,'jsjsjsjs',5,5),(8,'Jorge Alberto','Hombre',22,67.7,170,'Sin Antecedentes importantes',8,8),(9,'Josue Antonio Ruiz','Hombre',18,68,172,'Sin antrecedentes Importantes',9,9),(10,'jshshs','Hombre',18,79.8,160,'jsjsjsjs',10,10),(11,'Josuar Andreu','Hombre',16,64.8,170,'Sin antecedentes penales',11,11);
/*!40000 ALTER TABLE `pacientes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `preguntas`
--

DROP TABLE IF EXISTS `preguntas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `preguntas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `texto_pregunta` text NOT NULL,
  `feedback` text DEFAULT NULL,
  `id_caso` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_preguntas_caso` (`id_caso`),
  CONSTRAINT `preguntas_ibfk_1` FOREIGN KEY (`id_caso`) REFERENCES `casos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `preguntas`
--

LOCK TABLES `preguntas` WRITE;
/*!40000 ALTER TABLE `preguntas` DISABLE KEYS */;
INSERT INTO `preguntas` VALUES (13,'jsjsjsjs','jsjsjsjs',5),(28,'jsjsjsjs','jsjsjsj',8),(32,'jsjsjsjs','jasjasjsjs',10),(36,'jsjsjsjs','jsjsjs',11),(41,'jsjsjsjs','jsjsjsjs',9),(42,'jsjsjsjs','jsjsjs',9);
/*!40000 ALTER TABLE `preguntas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `signos_pacientes`
--

DROP TABLE IF EXISTS `signos_pacientes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `signos_pacientes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `FR` int(11) DEFAULT NULL,
  `FC` int(11) DEFAULT NULL,
  `TA` varchar(50) DEFAULT NULL,
  `Temperatura` double DEFAULT NULL,
  `SAT` int(11) DEFAULT NULL,
  `id_paciente` int(11) DEFAULT NULL,
  `ritmo` varchar(50) DEFAULT 'sinusal',
  PRIMARY KEY (`id`),
  KEY `id_paciente` (`id_paciente`),
  CONSTRAINT `signos_pacientes_ibfk_1` FOREIGN KEY (`id_paciente`) REFERENCES `pacientes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `signos_pacientes`
--

LOCK TABLES `signos_pacientes` WRITE;
/*!40000 ALTER TABLE `signos_pacientes` DISABLE KEYS */;
INSERT INTO `signos_pacientes` VALUES (5,18,92,'90/80',36.5,96,5,'sinusal'),(8,60,90,'90/80',35,90,8,'sinusal'),(9,20,150,'130/85',35,77,9,'taquicardia'),(10,60,90,'90/80',35,89,10,'sinusal'),(11,4,0,'60/40',35,90,11,'fv');
/*!40000 ALTER TABLE `signos_pacientes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `correo` varchar(255) NOT NULL,
  `contraseña` varchar(255) NOT NULL,
  `reset_token` varchar(64) NOT NULL,
  `es_admin` tinyint(1) NOT NULL DEFAULT 0,
  `clave_asignada` varchar(255) DEFAULT NULL,
  `superadmin` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`correo`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (15,'0717502b93ff97a3ab6cab132e014fd61f2749eb9094cb04d37b28ab895bc7cf','$2y$10$FkGBIUKaebmy4z2E9rkrCuIG/IlVp/UqFHM.uDkjTv5j4SZgvPkgu','',0,'f5bce76b7c8f7798c2b809d80a1edb8bff9c7246171d4b004d17398facf7a80d',0),(16,'0c4765c50569dff8a4876191afacf8e30bfbe82f6a8158a62998a6baae883d9e','$2y$10$85kiqvagDG16arFrlV5SguG2pw28iMpguvxbJhHuldac8JkiIVziK','',0,'15101cf8441eef7d0f63fa86011995584d425b02cbbc9eddb6a65544a1a10895',0),(17,'4704b83724653e21d363245e8e932d51585fdb4cc7e1e9e924c60b333c3a4e21','$2y$10$G1cu.V9RfpUn8St0NigmkeDFYH0hStpyA8L2Is2fwVRxEkQ5vcdyq','',0,'8599832b6b801c8db5a867432890cc3da24755b3939699109b0bbcc453b04af5',0),(18,'8e4c13ce1212e0284a592f07991af0d64960d65cfb45b7e017fe96141f5b7dc7','$2y$10$n7Zo.laPN3nGM5D5Hdu.WO6ZTPP7PkaLlb.9WHggaT8Ar27VCisja','',0,'81035bc92c674db6e010bcec910d38c80a61045d561df5fc21481c30ce8ecd4c',0);
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `videos`
--

DROP TABLE IF EXISTS `videos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `videos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `ruta` varchar(255) NOT NULL,
  `id_caso` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_video_caso` (`id_caso`),
  CONSTRAINT `videos_ibfk_1` FOREIGN KEY (`id_caso`) REFERENCES `casos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `videos`
--

LOCK TABLES `videos` WRITE;
/*!40000 ALTER TABLE `videos` DISABLE KEYS */;
INSERT INTO `videos` VALUES (1,'1778125736_9333582c81104aed871da7e524e50bc5.mov','./uploads/videos/1778125736_9333582c81104aed871da7e524e50bc5.mov',8),(2,'1778126263_9333582c81104aed871da7e524e50bc5.mov','./uploads/videos/1778126263_9333582c81104aed871da7e524e50bc5.mov',9),(3,'1778171791_1778125059_9333582c81104aed871da7e524e50bc5.mov','./uploads/videos/1778171791_1778125059_9333582c81104aed871da7e524e50bc5.mov',10),(5,'Clase2','./uploads/videos/1778742542_WhatsApp_Video_2026-05-13_at_8.20.33_PM.mp4',11),(6,'Clase 3','./uploads/videos/1778742542_WhatsApp_Video_2026-05-13_at_8.20.33_PM.mp4',11);
/*!40000 ALTER TABLE `videos` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-27 11:44:52
