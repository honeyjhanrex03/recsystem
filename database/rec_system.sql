-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: rec_system
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
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','rec_staff','rec_chair','rec_member','rec_secretary') DEFAULT NULL,
  `academic_rank` varchar(100) DEFAULT NULL,
  `academic_degree` varchar(100) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive','pending') DEFAULT 'pending',
  `signature` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`admin_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admins`
--

LOCK TABLES `admins` WRITE;
/*!40000 ALTER TABLE `admins` DISABLE KEYS */;
INSERT INTO `admins` VALUES (1,'System Admin','admin@dnsc.edu.ph','$2y$10$c5EQYLa0XISx08dVqF.Ite0E7haQrpCYthPqHD5KuLxuaD1K1p59G','admin',NULL,NULL,NULL,'active','sig_1771731600_699a7a90b81cb.png','2026-02-21 03:32:53'),(2,'Dr. Robert Miller','robert.miller@dnsc.edu.ph','$2y$10$/nSFNacad4oWcOz33HGo7OWG/iHQR0sfQg292.zx0yDQlbG8w/.Ae','rec_member',NULL,NULL,NULL,'active','sig_1771731610_699a7a9a862c6.png','2026-02-21 03:56:58'),(3,'Maria Santos','m.santos@dnsc.edu.ph','$2y$10$09tSekxlHpOiU7qTZ1Eg6uymcDW079yl3udk.VoCJZsmwuAlDDFgu','rec_staff',NULL,NULL,NULL,'active','sig_1774421892_69c38784c1116.png','2026-02-21 03:56:58'),(4,'Juan Dela Cruz','j.delacruz@dnsc.edu.ph','$2y$10$b2hu2damudo1ySOiSClEO.TA.7SqeZZNRUgWX2kc2YosRXZ.zOITq','rec_member',NULL,NULL,NULL,'active','sig_1771731643_699a7abbedbef.png','2026-02-21 03:56:58'),(17,'Ana Reyes','a.reyes@dnsc.edu.ph','$2y$10$b2hu2damudo1ySOiSClEO.TA.7SqeZZNRUgWX2kc2YosRXZ.zOITq','rec_secretary',NULL,NULL,NULL,'active','sig_1771731627_699a7aab61df4.png','2026-02-22 02:44:42'),(18,'Carlos Bautista','c.bautista@dnsc.edu.ph','$2y$10$b2hu2damudo1ySOiSClEO.TA.7SqeZZNRUgWX2kc2YosRXZ.zOITq','rec_member',NULL,NULL,NULL,'active','sig_1773385950_69b3b8deaaf26.png','2026-02-22 02:44:42'),(19,'Mark Van Buladaco','markvan.buladaco@dnsc.edu.ph','$2y$10$X.4/rM/xHv1tXmLrcfMYA.HGE9L9UkLbFjSzGCaPZspHOq6/7s7.m','rec_chair',NULL,NULL,NULL,'active','sig_1771730981_699a7825e5984.png','2026-02-22 03:29:41'),(21,'NAVEA','aljemarie.canoy@dnsc.edu.ph','$2y$10$cn4nXfJqj5rvdv0JubOi7exdS/580lrEZzaSWTVQXnVGbqzkm9XV2','rec_member',NULL,NULL,NULL,'active','sig_file_1773626988_69b7666c69f1c.png','2026-03-16 02:09:48');
/*!40000 ALTER TABLE `admins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_logs` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `protocol_id` int(11) DEFAULT NULL,
  `action` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`log_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (1,22,1,'Author submitted protocol online. Awaiting REC Code.','2026-04-29 03:46:04'),(2,3,1,'Staff screening started.','2026-04-29 03:46:22'),(3,3,1,'Forwarded to REC Chair for Initial Review. Generated official code: 2026-001-INT.-JPD','2026-04-29 03:47:01'),(4,19,NULL,'Successful Login','2026-04-29 03:48:36'),(5,3,NULL,'Successful Login','2026-04-29 03:48:56'),(6,19,NULL,'Successful Login','2026-04-29 03:53:09'),(7,19,1,'Assigned 7 reviewers (Ana Reyes, Carlos Bautista, Dr. Robert Miller, Juan Dela Cruz, Maria Santos, NAVEA, Mark Van Buladaco (REC Chair)). Deadline auto-set to May 13, 2026 (14 days).','2026-04-29 03:54:22'),(8,19,1,'Made final decision: Approved','2026-04-29 04:02:06'),(9,19,1,'Reverted accidental approval back to Assigned state for member evaluation.','2026-04-29 04:07:16'),(10,19,1,'Submitted Form 10 & 12 review for 2026-001-INT.-JPD','2026-04-29 04:25:11'),(11,3,NULL,'Successful Login','2026-04-29 04:27:15'),(12,19,NULL,'Successful Login','2026-04-29 06:45:44'),(13,2,NULL,'Successful Login','2026-04-29 06:47:15'),(14,19,NULL,'Successful Login','2026-04-29 06:48:25'),(15,4,NULL,'Successful Login','2026-04-29 07:14:32'),(16,19,NULL,'Successful Login','2026-04-29 07:15:50'),(17,4,NULL,'Successful Login','2026-04-29 07:16:11'),(18,22,NULL,'Successful Login','2026-04-29 08:45:27'),(19,22,NULL,'Successful Login','2026-04-29 08:56:54'),(20,3,NULL,'Successful Login','2026-04-29 08:57:03'),(21,19,NULL,'Successful Login','2026-04-29 08:58:05'),(22,19,1,'Made final decision: Approved','2026-04-29 08:58:27'),(23,22,NULL,'Successful Login','2026-04-29 09:07:43'),(24,19,NULL,'Successful Login','2026-05-16 02:13:00'),(25,19,NULL,'Successful Login','2026-05-16 02:25:55'),(26,19,NULL,'Successful Login','2026-05-16 02:28:45'),(27,22,NULL,'Successful Login','2026-05-16 02:29:00'),(28,22,NULL,'Successful Login','2026-05-16 02:29:21'),(29,19,NULL,'Successful Login','2026-05-16 02:39:03'),(30,22,NULL,'Successful Login','2026-05-16 02:39:41'),(31,19,NULL,'Successful Login','2026-05-16 02:39:58'),(32,22,NULL,'Successful Login','2026-05-16 02:40:29'),(33,19,NULL,'Successful Login','2026-05-16 02:41:40');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `final_decisions`
--

DROP TABLE IF EXISTS `final_decisions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `final_decisions` (
  `decision_id` int(11) NOT NULL AUTO_INCREMENT,
  `protocol_id` int(11) NOT NULL,
  `chair_id` int(11) NOT NULL,
  `meeting_date` date DEFAULT NULL,
  `final_decision` enum('Approved','Minor Revision','Major Revision','Disapproved') NOT NULL,
  `remarks` text DEFAULT NULL,
  `decision_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`decision_id`),
  KEY `protocol_id` (`protocol_id`),
  KEY `chair_id` (`chair_id`),
  CONSTRAINT `final_decisions_ibfk_1` FOREIGN KEY (`protocol_id`) REFERENCES `protocols` (`protocol_id`) ON DELETE CASCADE,
  CONSTRAINT `final_decisions_ibfk_2` FOREIGN KEY (`chair_id`) REFERENCES `admins` (`admin_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `final_decisions`
--

LOCK TABLES `final_decisions` WRITE;
/*!40000 ALTER TABLE `final_decisions` DISABLE KEYS */;
INSERT INTO `final_decisions` VALUES (2,1,19,'2026-04-29','Approved','congrats','2026-04-29 08:58:27');
/*!40000 ALTER TABLE `final_decisions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `form10_answers`
--

DROP TABLE IF EXISTS `form10_answers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `form10_answers` (
  `answer_id` int(11) NOT NULL AUTO_INCREMENT,
  `protocol_id` int(11) NOT NULL,
  `reviewer_id` int(11) NOT NULL,
  `question` text NOT NULL,
  `answer` enum('Yes','No','Unable to Assess') NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`answer_id`),
  KEY `protocol_id` (`protocol_id`),
  KEY `reviewer_id` (`reviewer_id`),
  CONSTRAINT `form10_answers_ibfk_1` FOREIGN KEY (`protocol_id`) REFERENCES `protocols` (`protocol_id`) ON DELETE CASCADE,
  CONSTRAINT `form10_answers_ibfk_2` FOREIGN KEY (`reviewer_id`) REFERENCES `admins` (`admin_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `form10_answers`
--

LOCK TABLES `form10_answers` WRITE;
/*!40000 ALTER TABLE `form10_answers` DISABLE KEYS */;
INSERT INTO `form10_answers` VALUES (1,1,19,'Does the study have social value?','Yes','ssadasd','2026-04-29 04:25:11'),(2,1,19,'Is the study background adequate?','Yes','asdasd','2026-04-29 04:25:11'),(3,1,19,'Are the research questions supported by the Review of Literature?','Yes','','2026-04-29 04:25:11'),(4,1,19,'Are the study objectives Specific, Measurable, Attainable, Realistic, Time-bound?','Yes','','2026-04-29 04:25:11'),(5,1,19,'Is the research design appropriate?','Yes','','2026-04-29 04:25:11'),(6,1,19,'SUB|Is the research design appropriate?|Is the population identified and defined?','Yes','','2026-04-29 04:25:11'),(7,1,19,'SUB|Is the research design appropriate?|Is the selection of study participants described?','Yes','','2026-04-29 04:25:11'),(8,1,19,'SUB|Is the research design appropriate?|Is the sample size justified?','Yes','','2026-04-29 04:25:11'),(9,1,19,'SUB|Is the research design appropriate?|Is the plan for data analysis described?','Yes','','2026-04-29 04:25:11'),(10,1,19,'SUB|Is the research design appropriate?|Are there dummy tables?','Yes','','2026-04-29 04:25:11'),(11,1,19,'Does the research need to be carried out with human participants?','Yes','','2026-04-29 04:25:11'),(12,1,19,'Does the study have a vulnerability issue?','Yes','','2026-04-29 04:25:11'),(13,1,19,'Are appropriate mechanisms/interventions in place to address the vulnerability issue/s?','Yes','','2026-04-29 04:25:11'),(14,1,19,'Are there risks/probable harms to the human participants in the study?','Yes','','2026-04-29 04:25:11'),(15,1,19,'Are there measures to mitigate the risks?','Yes','','2026-04-29 04:25:11'),(16,1,19,'Is the informed consent procedure/form adequate and culturally appropriate?','Yes','','2026-04-29 04:25:11'),(17,1,19,'Is/are the investigator/s adequately trained and do they have sufficient experience to undertake the study?','Yes','','2026-04-29 04:25:11'),(18,1,19,'Is there a disclosure of conflict of interest?','Yes','','2026-04-29 04:25:11'),(19,1,19,'Are the research facilities adequate?','Yes','','2026-04-29 04:25:11'),(20,1,19,'Are there any other concerns in the study?','Yes','dfgfdgdfg','2026-04-29 04:25:11'),(21,1,17,'Simulated Question','Yes','Proceed.','2026-04-29 06:49:27'),(22,1,18,'Simulated Question','Yes','Proceed.','2026-04-29 06:49:27'),(23,1,2,'Simulated Question','Yes','Proceed.','2026-04-29 06:49:27'),(24,1,4,'Simulated Question','Yes','Proceed.','2026-04-29 06:49:27'),(25,1,3,'Simulated Question','Yes','Proceed.','2026-04-29 06:49:27'),(26,1,21,'Simulated Question','Yes','Proceed.','2026-04-29 06:49:27');
/*!40000 ALTER TABLE `form10_answers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `form12_answers`
--

DROP TABLE IF EXISTS `form12_answers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `form12_answers` (
  `answer_id` int(11) NOT NULL AUTO_INCREMENT,
  `protocol_id` int(11) NOT NULL,
  `reviewer_id` int(11) NOT NULL,
  `question` text NOT NULL,
  `answer` enum('Yes','No','Unable to Assess') NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`answer_id`),
  KEY `protocol_id` (`protocol_id`),
  KEY `reviewer_id` (`reviewer_id`),
  CONSTRAINT `form12_answers_ibfk_1` FOREIGN KEY (`protocol_id`) REFERENCES `protocols` (`protocol_id`) ON DELETE CASCADE,
  CONSTRAINT `form12_answers_ibfk_2` FOREIGN KEY (`reviewer_id`) REFERENCES `admins` (`admin_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `form12_answers`
--

LOCK TABLES `form12_answers` WRITE;
/*!40000 ALTER TABLE `form12_answers` DISABLE KEYS */;
INSERT INTO `form12_answers` VALUES (1,1,19,'Is it necessary to seek the informed consent of the participants?','Yes','dfgdfg','2026-04-29 04:25:11'),(2,1,19,'IFNO|Is it necessary to seek the informed consent of the participants?','','','2026-04-29 04:25:11'),(3,1,19,'If YES, are the participants provided with sufficient information regarding:','','dfbdfbd','2026-04-29 04:25:11'),(4,1,19,'SUB|If YES, are the participants provided with sufficient information regarding:|Purpose of the study?','Yes','','2026-04-29 04:25:11'),(5,1,19,'SUB|If YES, are the participants provided with sufficient information regarding:|Expected duration of participation?','Yes','','2026-04-29 04:25:11'),(6,1,19,'SUB|If YES, are the participants provided with sufficient information regarding:|Procedures to be carried out?','Yes','','2026-04-29 04:25:11'),(7,1,19,'SUB|If YES, are the participants provided with sufficient information regarding:|Discomforts and inconveniences?','Yes','','2026-04-29 04:25:11'),(8,1,19,'SUB|If YES, are the participants provided with sufficient information regarding:|Risks (including possible discrimination)?','Yes','','2026-04-29 04:25:11'),(9,1,19,'SUB|If YES, are the participants provided with sufficient information regarding:|Random assignment to the trial treatments?','Yes','','2026-04-29 04:25:11'),(10,1,19,'SUB|If YES, are the participants provided with sufficient information regarding:|Benefits to the participants?','Yes','','2026-04-29 04:25:11'),(11,1,19,'SUB|If YES, are the participants provided with sufficient information regarding:|Alternative treatments/procedures?','Yes','','2026-04-29 04:25:11'),(12,1,19,'SUB|If YES, are the participants provided with sufficient information regarding:|Compensation and/or medical treatments in case of injury?','Yes','','2026-04-29 04:25:11'),(13,1,19,'SUB|If YES, are the participants provided with sufficient information regarding:|Who to contact for pertinent questions and/or for assistance in a research-related injury?','Yes','','2026-04-29 04:25:11'),(14,1,19,'SUB|If YES, are the participants provided with sufficient information regarding:|Refusal to participate or discontinuance at any time will involve penalty or loss of benefits to which the subject is entitled?','Yes','','2026-04-29 04:25:11'),(15,1,19,'SUB|If YES, are the participants provided with sufficient information regarding:|Extent of confidentiality?','Yes','','2026-04-29 04:25:11'),(16,1,19,'Is the informed consent written or presented in simple language that participants can understand?','Yes','','2026-04-29 04:25:11'),(17,1,19,'Does the protocol include an adequate process for ensuring that consent is voluntary?','Yes','','2026-04-29 04:25:11'),(18,1,19,'Do you have any other concerns?','Yes','','2026-04-29 04:25:11');
/*!40000 ALTER TABLE `form12_answers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `form13_answers`
--

DROP TABLE IF EXISTS `form13_answers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `form13_answers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `protocol_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `category` varchar(255) NOT NULL,
  `is_submitted` enum('Yes','No','N/A') DEFAULT 'No',
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `protocol_id` (`protocol_id`),
  KEY `staff_id` (`staff_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `form13_answers`
--

LOCK TABLES `form13_answers` WRITE;
/*!40000 ALTER TABLE `form13_answers` DISABLE KEYS */;
INSERT INTO `form13_answers` VALUES (1,1,3,'Research Protocol','Yes','test','2026-04-29 03:46:49'),(2,1,3,'Informed Consent / Assent Consent','No','','2026-04-29 03:46:49'),(3,1,3,'Guide Questionnaire','No','','2026-04-29 03:46:49'),(4,1,3,'Curriculum Vitae','No','','2026-04-29 03:46:49'),(5,1,3,'Letter Request','No','','2026-04-29 03:46:49'),(6,1,3,'Endorsement','No','','2026-04-29 03:46:49');
/*!40000 ALTER TABLE `form13_answers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `form15_responses`
--

DROP TABLE IF EXISTS `form15_responses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `form15_responses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `protocol_id` int(11) NOT NULL,
  `author_id` int(11) NOT NULL,
  `rec_recommendation` text NOT NULL,
  `author_response` text NOT NULL,
  `page_reference` varchar(255) DEFAULT NULL,
  `rec_assessment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `protocol_id` (`protocol_id`),
  KEY `author_id` (`author_id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `form15_responses`
--

LOCK TABLES `form15_responses` WRITE;
/*!40000 ALTER TABLE `form15_responses` DISABLE KEYS */;
INSERT INTO `form15_responses` VALUES (1,1,22,'BOARD REVIEWER COMMENTS (Form 10):','asdasdasdas','1',NULL,'2026-04-29 08:56:35'),(2,1,22,'[Reviewer 3] Question: Simulated Question\r\n  [Reviewer Note] Proceed.','asdsadasd','2',NULL,'2026-04-29 08:56:35'),(3,1,22,'[Reviewer 5] Question: Simulated Question\r\n  [Reviewer Note] Proceed.','asdasd','2',NULL,'2026-04-29 08:56:35'),(4,1,22,'[Reviewer 4] Question: Simulated Question\r\n  [Reviewer Note] Proceed.','asdasd','3',NULL,'2026-04-29 08:56:35'),(5,1,22,'[Reviewer 1] Question: Simulated Question\r\n  [Reviewer Note] Proceed.','asdsad','4',NULL,'2026-04-29 08:56:35'),(6,1,22,'[Reviewer 2] Question: Simulated Question\r\n  [Reviewer Note] Proceed.','asdad','2',NULL,'2026-04-29 08:56:35'),(7,1,22,'[Reviewer 7] Question: Does the study have social value?\r\n  [Reviewer Note] ssadasd','','',NULL,'2026-04-29 08:56:35'),(8,1,22,'[Reviewer 7] Question: Is the study background adequate?\r\n  [Reviewer Note] asdasd','asda','1',NULL,'2026-04-29 08:56:35'),(9,1,22,'[Reviewer 7] Question: Are there any other concerns in the study?\r\n  [Reviewer Note] dfgfdgdfg','asdsad','2',NULL,'2026-04-29 08:56:35'),(10,1,22,'[Reviewer 6] Question: Simulated Question\r\n  [Reviewer Note] Proceed.\r\n\r\n\r\nCONSENT CHECKLIST CONCERNS (Form 12):','asdsad','2',NULL,'2026-04-29 08:56:35'),(11,1,22,'[Reviewer 7] Check: Is it necessary to seek the informed consent of the participants?\r\n  [Reviewer Note] dfgdfg','asdsad','2',NULL,'2026-04-29 08:56:35'),(12,1,22,'[Reviewer 7] Check: IFNO|Is it necessary to seek the informed consent of the participants?','asdsd','2',NULL,'2026-04-29 08:56:35'),(13,1,22,'[Reviewer 7] Check: If YES, are the participants provided with sufficient information regarding:\r\n  [Reviewer Note] dfbdfbd\r\n\r\n\r\nSUMMARY OF REVIEWER DECISIONS:','asdasd','1',NULL,'2026-04-29 08:56:35'),(14,1,22,'Reviewer 7: Approved','asdasd1','2',NULL,'2026-04-29 08:56:35'),(15,1,22,'Reviewer 1: Approved','asdascasdc','23',NULL,'2026-04-29 08:56:35'),(16,1,22,'Reviewer 2: Approved','asdsad','2',NULL,'2026-04-29 08:56:35'),(17,1,22,'Reviewer 3: Approved','asdasd','3',NULL,'2026-04-29 08:56:35'),(18,1,22,'Reviewer 4: Approved','asdasd','2',NULL,'2026-04-29 08:56:35'),(19,1,22,'Reviewer 5: Approved','asdsad','1',NULL,'2026-04-29 08:56:35'),(20,1,22,'Reviewer 6: Approved','asdasdasdasd','1',NULL,'2026-04-29 08:56:35');
/*!40000 ALTER TABLE `form15_responses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `form9_answers`
--

DROP TABLE IF EXISTS `form9_answers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `form9_answers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `protocol_id` int(11) NOT NULL,
  `chair_id` int(11) NOT NULL,
  `section` varchar(255) NOT NULL,
  `decision` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `protocol_id` (`protocol_id`),
  KEY `chair_id` (`chair_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `form9_answers`
--

LOCK TABLES `form9_answers` WRITE;
/*!40000 ALTER TABLE `form9_answers` DISABLE KEYS */;
/*!40000 ALTER TABLE `form9_answers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `member_files`
--

DROP TABLE IF EXISTS `member_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `member_files` (
  `file_id` int(11) NOT NULL AUTO_INCREMENT,
  `protocol_id` int(11) NOT NULL,
  `reviewer_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`file_id`),
  KEY `protocol_id` (`protocol_id`),
  KEY `reviewer_id` (`reviewer_id`),
  CONSTRAINT `member_files_ibfk_1` FOREIGN KEY (`protocol_id`) REFERENCES `protocols` (`protocol_id`) ON DELETE CASCADE,
  CONSTRAINT `member_files_ibfk_2` FOREIGN KEY (`reviewer_id`) REFERENCES `admins` (`admin_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `member_files`
--

LOCK TABLES `member_files` WRITE;
/*!40000 ALTER TABLE `member_files` DISABLE KEYS */;
/*!40000 ALTER TABLE `member_files` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `user_type` enum('author','admin') NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`notification_id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,22,'author','Protocol Submitted Successfully','Your protocol has been successfully submitted and is awaiting staff screening.','shared_view?id=1',1,'2026-04-29 03:46:07'),(2,3,'admin','New Protocol Submitted','A new protocol titled \"GWAPOO KO TESTG\" has been submitted and requires screening.','rec_staff/update_status.php?id=1',1,'2026-04-29 03:46:07'),(3,22,'author','Initial Assessment Completed','The REC Chair has completed the initial assessment. Review Type: FULL_BOARD','shared_view?id=1',1,'2026-04-29 03:53:56'),(4,3,'admin','Chair Confirmed Protocol','The REC Chair has confirmed protocol: \"GWAPOO KO TESTG\" and set Review Type to FULL_BOARD','rec_staff/update_status.php?id=1',1,'2026-04-29 03:53:56'),(5,17,'admin','Protocol Assigned for Review','You have been assigned to review the protocol: 2026-001-INT.-JPD - GWAPOO KO TESTG. Evaluation Deadline: May 13, 2026.','rec_member/review?id=1',0,'2026-04-29 03:54:22'),(6,18,'admin','Protocol Assigned for Review','You have been assigned to review the protocol: 2026-001-INT.-JPD - GWAPOO KO TESTG. Evaluation Deadline: May 13, 2026.','rec_member/review?id=1',0,'2026-04-29 03:54:25'),(7,2,'admin','Protocol Assigned for Review','You have been assigned to review the protocol: 2026-001-INT.-JPD - GWAPOO KO TESTG. Evaluation Deadline: May 13, 2026.','rec_member/review?id=1',1,'2026-04-29 03:54:27'),(8,4,'admin','Protocol Assigned for Review','You have been assigned to review the protocol: 2026-001-INT.-JPD - GWAPOO KO TESTG. Evaluation Deadline: May 13, 2026.','rec_member/review?id=1',1,'2026-04-29 03:54:29'),(9,3,'admin','Protocol Assigned for Review','You have been assigned to review the protocol: 2026-001-INT.-JPD - GWAPOO KO TESTG. Evaluation Deadline: May 13, 2026.','rec_member/review?id=1',1,'2026-04-29 03:54:31'),(10,21,'admin','Protocol Assigned for Review','You have been assigned to review the protocol: 2026-001-INT.-JPD - GWAPOO KO TESTG. Evaluation Deadline: May 13, 2026.','rec_member/review?id=1',0,'2026-04-29 03:54:34'),(11,19,'admin','Protocol Assigned for Review','You have been assigned to review the protocol: 2026-001-INT.-JPD - GWAPOO KO TESTG. Evaluation Deadline: May 13, 2026.','rec_member/review?id=1',1,'2026-04-29 03:54:36'),(12,22,'author','Reviewers Assigned','Reviewers have been assigned to your protocol \"GWAPOO KO TESTG\". Evaluation is now underway.','shared_view?id=1',1,'2026-04-29 03:54:38'),(13,3,'admin','Reviewers Assigned','Reviewers have been assigned to protocol: \"GWAPOO KO TESTG\" by the REC Chair.','rec_staff/update_status.php?id=1',1,'2026-04-29 03:54:38'),(14,19,'admin','Test Notification','If you see this, notifications are working!','rec_chair/protocols',1,'2026-04-29 03:54:44'),(15,22,'author','Final Decision Rendered','The REC Board has rendered a final decision on your protocol: \"GWAPOO KO TESTG\". Decision: APPROVED','shared_view?id=1',1,'2026-04-29 04:02:06'),(16,3,'admin','Decision Rendered','The REC Chair has finalized the decision for protocol: \"GWAPOO KO TESTG\". Result: APPROVED','rec_staff/update_status.php?id=1',1,'2026-04-29 04:02:06'),(17,3,'admin','Review Submitted','Reviewer Mark Van Buladaco has submitted an evaluation for protocol: \"GWAPOO KO TESTG\".','rec_chair/decision.php?id=1',1,'2026-04-29 04:25:11'),(18,19,'admin','Review Submitted','Reviewer Mark Van Buladaco has submitted an evaluation for protocol: \"GWAPOO KO TESTG\".','rec_chair/decision.php?id=1',1,'2026-04-29 04:25:11'),(19,22,'author','Revision Required (Fast-Track)','All board reviews for \"GWAPOO KO TESTG\" are complete. Fast-track triggered.','shared_view?id=1',1,'2026-04-29 06:49:27'),(20,22,'author','Final Decision Rendered','The REC Board has rendered a final decision on your protocol: \"GWAPOO KO TESTG\". Decision: APPROVED','shared_view?id=1',1,'2026-04-29 08:58:27'),(21,3,'admin','Decision Rendered','The REC Chair has finalized the decision for protocol: \"GWAPOO KO TESTG\". Result: APPROVED','rec_staff/update_status.php?id=1',0,'2026-04-29 08:58:27');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_resets` (
  `reset_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `status` enum('pending','completed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`reset_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `admins` (`admin_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_resets`
--

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `protocol_files`
--

DROP TABLE IF EXISTS `protocol_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `protocol_files` (
  `file_id` int(11) NOT NULL AUTO_INCREMENT,
  `protocol_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `document_type` varchar(50) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`file_id`),
  KEY `protocol_id` (`protocol_id`),
  CONSTRAINT `protocol_files_ibfk_1` FOREIGN KEY (`protocol_id`) REFERENCES `protocols` (`protocol_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `protocol_files`
--

LOCK TABLES `protocol_files` WRITE;
/*!40000 ALTER TABLE `protocol_files` DISABLE KEYS */;
INSERT INTO `protocol_files` VALUES (1,1,'Reasearch_G4_BSIT3E_FINAL.pdf','REC_1777434364_1_69f17efcb21aa.pdf','Protocol','2026-04-29 03:46:04'),(2,1,'REVISED_JABAGAT_GROUP_THE INFLUENCE OF INTERNET ACCESSIBILITY ON ONLINE LEARNING PERFORMANCE AMONG BSIT STUDENTS AT DAVAO DEL NORTE STATE COLLEGE.pdf','REC_REV_1777452995_1_69f1c7c3a572a.pdf',NULL,'2026-04-29 08:56:35');
/*!40000 ALTER TABLE `protocol_files` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `protocols`
--

DROP TABLE IF EXISTS `protocols`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `protocols` (
  `protocol_id` int(11) NOT NULL AUTO_INCREMENT,
  `rec_code` varchar(50) DEFAULT NULL,
  `tracking_code` varchar(50) DEFAULT NULL,
  `sequence_number` int(11) DEFAULT NULL,
  `protocol_type` enum('INT','EXT') DEFAULT 'INT',
  `author_initials` varchar(10) DEFAULT NULL,
  `submission_confirmed_at` timestamp NULL DEFAULT NULL,
  `title` text NOT NULL,
  `project_leader` varchar(255) NOT NULL,
  `author_email` varchar(255) DEFAULT NULL,
  `institution` varchar(255) NOT NULL,
  `review_type` enum('pending','exempt','expedited','full_board') NOT NULL DEFAULT 'pending',
  `status` enum('submitted','staff_review','needs_revision','initial_review','confirmed','assigned','under_review','revised','approved','rejected','clearance_released') NOT NULL DEFAULT 'submitted',
  `recommendations` text DEFAULT NULL,
  `final_notified` int(11) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `is_secretary_assigned` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `form13_status` enum('pending','completed') DEFAULT 'pending',
  `forwarded_to_chair_at` timestamp NULL DEFAULT NULL,
  `protocol_deadline` date DEFAULT NULL,
  PRIMARY KEY (`protocol_id`),
  UNIQUE KEY `rec_code` (`rec_code`),
  UNIQUE KEY `tracking_code` (`tracking_code`),
  KEY `created_by` (`created_by`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `protocols`
--

LOCK TABLES `protocols` WRITE;
/*!40000 ALTER TABLE `protocols` DISABLE KEYS */;
INSERT INTO `protocols` VALUES (1,'2026-001-INT.-JPD',NULL,1,'INT','JPD',NULL,'GWAPOO KO TESTG','Dela Pena, Jhanrex Philip, G','delapena.jhanrexphilip@dnsc.edu.ph','Davao Del Norte State College','full_board','approved','congrats',0,22,0,'2026-04-29 03:46:04','completed',NULL,NULL);
/*!40000 ALTER TABLE `protocols` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reviewer_assignments`
--

DROP TABLE IF EXISTS `reviewer_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reviewer_assignments` (
  `assignment_id` int(11) NOT NULL AUTO_INCREMENT,
  `protocol_id` int(11) NOT NULL,
  `reviewer_id` int(11) NOT NULL,
  `status` enum('pending','completed') DEFAULT 'pending',
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deadline` date DEFAULT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`assignment_id`),
  KEY `protocol_id` (`protocol_id`),
  KEY `reviewer_id` (`reviewer_id`),
  CONSTRAINT `reviewer_assignments_ibfk_1` FOREIGN KEY (`protocol_id`) REFERENCES `protocols` (`protocol_id`) ON DELETE CASCADE,
  CONSTRAINT `reviewer_assignments_ibfk_2` FOREIGN KEY (`reviewer_id`) REFERENCES `admins` (`admin_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reviewer_assignments`
--

LOCK TABLES `reviewer_assignments` WRITE;
/*!40000 ALTER TABLE `reviewer_assignments` DISABLE KEYS */;
INSERT INTO `reviewer_assignments` VALUES (1,1,17,'completed','2026-04-29 03:54:22','2026-05-13',0),(2,1,18,'completed','2026-04-29 03:54:22','2026-05-13',0),(3,1,2,'completed','2026-04-29 03:54:22','2026-05-13',0),(4,1,4,'completed','2026-04-29 03:54:22','2026-05-13',0),(5,1,3,'completed','2026-04-29 03:54:22','2026-05-13',0),(6,1,21,'completed','2026-04-29 03:54:22','2026-05-13',0),(7,1,19,'completed','2026-04-29 03:54:22','2026-05-13',0);
/*!40000 ALTER TABLE `reviewer_assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reviewer_recommendations`
--

DROP TABLE IF EXISTS `reviewer_recommendations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reviewer_recommendations` (
  `rec_id` int(11) NOT NULL AUTO_INCREMENT,
  `protocol_id` int(11) NOT NULL,
  `reviewer_id` int(11) NOT NULL,
  `form_type` tinyint(2) NOT NULL COMMENT '10 or 12',
  `recommendation` enum('Approved','Minor Revision','Major Revision','Disapproved') NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`rec_id`),
  KEY `protocol_reviewer` (`protocol_id`,`reviewer_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reviewer_recommendations`
--

LOCK TABLES `reviewer_recommendations` WRITE;
/*!40000 ALTER TABLE `reviewer_recommendations` DISABLE KEYS */;
INSERT INTO `reviewer_recommendations` VALUES (1,1,19,10,'Approved','','2026-04-29 04:25:11'),(2,1,19,12,'Approved','','2026-04-29 04:25:11'),(3,1,17,10,'Approved','Fast-tracked review.','2026-04-29 06:49:27'),(4,1,18,10,'Approved','Fast-tracked review.','2026-04-29 06:49:27'),(5,1,2,10,'Approved','Fast-tracked review.','2026-04-29 06:49:27'),(6,1,4,10,'Approved','Fast-tracked review.','2026-04-29 06:49:27'),(7,1,3,10,'Approved','Fast-tracked review.','2026-04-29 06:49:27'),(8,1,21,10,'Approved','Fast-tracked review.','2026-04-29 06:49:27');
/*!40000 ALTER TABLE `reviewer_recommendations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `secretary_assignments`
--

DROP TABLE IF EXISTS `secretary_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `secretary_assignments` (
  `assignment_id` int(11) NOT NULL AUTO_INCREMENT,
  `protocol_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`assignment_id`),
  KEY `protocol_id` (`protocol_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `secretary_assignments_ibfk_1` FOREIGN KEY (`protocol_id`) REFERENCES `protocols` (`protocol_id`) ON DELETE CASCADE,
  CONSTRAINT `secretary_assignments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `admins` (`admin_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `secretary_assignments`
--

LOCK TABLES `secretary_assignments` WRITE;
/*!40000 ALTER TABLE `secretary_assignments` DISABLE KEYS */;
/*!40000 ALTER TABLE `secretary_assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `last_name` varchar(100) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_initial` varchar(10) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('active','pending','suspended','inactive') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `signature` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (22,'Dela Pena','Jhanrex Philip','G','delapena.jhanrexphilip@dnsc.edu.ph','$2y$10$87gdxZKBz3gJWoN5UZxH2OOciGjPtKa5nb49UO/2rrpbyPKRuZFs2','active','2026-03-25 05:00:10',NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-16 11:24:32
