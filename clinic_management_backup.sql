-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: clinic_management
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Dumping data for table `clinic_installments`
--

LOCK TABLES `clinic_installments` WRITE;
/*!40000 ALTER TABLE `clinic_installments` DISABLE KEYS */;
/*!40000 ALTER TABLE `clinic_installments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `clinic_records`
--

LOCK TABLES `clinic_records` WRITE;
/*!40000 ALTER TABLE `clinic_records` DISABLE KEYS */;
INSERT INTO `clinic_records` VALUES (2,'Rent','bato','85858585','55445544','6797353e5904c_ASIM.pdf','2025-01-02','2026-01-02',4500.000,'installment',0.000,12,375.000,3750.000,'2025-03-02','pending','','2025-01-27 07:26:54','2025-02-02 09:20:40','2025-01-27',12);
/*!40000 ALTER TABLE `clinic_records` ENABLE KEYS */;
UNLOCK TABLES;
