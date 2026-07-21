-- MySQL dump 10.13  Distrib 8.0.30, for Win64 (x86_64)
--
-- Host: ::1    Database: tracerstudy
-- ------------------------------------------------------
-- Server version	8.0.30

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `version` varchar(255) NOT NULL,
  `class` varchar(255) NOT NULL,
  `group` varchar(255) NOT NULL,
  `namespace` varchar(255) NOT NULL,
  `time` int NOT NULL,
  `batch` int unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2026-05-08-170900','App\\Database\\Migrations\\CreateAuthTables','default','App',1778234997,1),(2,'2026-05-08-193300','App\\Database\\Migrations\\CreateMasterDataTables','default','App',1778243640,2),(4,'2026-05-08-194800','App\\Database\\Migrations\\CreateAlumniTracerTables','default','App',1778244581,3),(5,'2026-04-18-000001','App\\Database\\Migrations\\CreateTablePeran','default','App',1780480688,4),(6,'2026-04-18-000002','App\\Database\\Migrations\\CreateTablePengguna','default','App',1780480688,4),(7,'2026-04-18-000003','App\\Database\\Migrations\\CreateTbKompetensi','default','App',1780480689,4),(8,'2026-04-18-000004','App\\Database\\Migrations\\CreateTbAngkatan','default','App',1780480689,4),(9,'2026-04-18-000005','App\\Database\\Migrations\\CreateTbAktivitas','default','App',1780480689,4),(10,'2026-04-18-000006','App\\Database\\Migrations\\AlterTbAktivitasForTracerStudy','default','App',1780480689,4),(11,'2026-04-18-000010','App\\Database\\Migrations\\CreateTbAlumni','default','App',1780480689,4),(12,'2026-04-19-000012','App\\Database\\Migrations\\CreateTbTracerAlumni','default','App',1780480689,4),(13,'2026-05-23-000028','App\\Database\\Migrations\\CreateTbNotifikasi','default','App',1780480689,4),(14,'2026-05-24-000029','App\\Database\\Migrations\\RemoveDraftFromTracerStatus','default','App',1780480689,4),(15,'2026-06-03-000030','App\\Database\\Migrations\\PurgeBkkModule','default','App',1780480718,5),(16,'2026-06-03-000031','App\\Database\\Migrations\\DropRemainingRecruitmentTables','default','App',1780480718,5),(17,'2026-06-09-000032','App\\Database\\Migrations\\DropTbJurusan','default','App',1780938599,6),(18,'2026-06-09-000033','App\\Database\\Migrations\\CreateTbPengajuanLegalisir','default','App',1780939159,7),(19,'2026-06-09-000034','App\\Database\\Migrations\\AddDiperbaruiPadaToTbNotifikasi','default','App',1780940088,8),(20,'2026-06-17-000035','App\\Database\\Migrations\\RemovePimpinanSekolahRole','default','App',1781671743,9),(21,'2026-06-25-000036','App\\Database\\Migrations\\NormalizeTracerStudyTermsAndAlumniStatus','default','App',1782391574,10),(22,'2026-06-29-000037','App\\Database\\Migrations\\CleanupLegacyJurusanFromAlumni','default','App',1783005702,11),(23,'2026-07-02-000038','App\\Database\\Migrations\\RemoveAlumniActivationFlow','default','App',1783005702,11),(24,'2026-07-16-000039','App\\Database\\Migrations\\DropLegacySlugAktivitas','default','App',1784141769,12),(25,'2026-07-20-000040','App\\Database\\Migrations\\LoadSidangDemoData','default','App',1784522868,13),(26,'2026-07-20-000041','App\\Database\\Migrations\\RestoreAlumniAccountActivation','default','App',1784556711,14),(27,'2026-07-21-000042','App\\Database\\Migrations\\NormalizeSchoolCompetencies','default','App',1784604038,15);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tb_aktivitas`
--

DROP TABLE IF EXISTS `tb_aktivitas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tb_aktivitas` (
  `id_aktivitas` int unsigned NOT NULL AUTO_INCREMENT,
  `nama_aktivitas` varchar(100) NOT NULL,
  `keterangan` text,
  `status_aktif` tinyint(1) NOT NULL DEFAULT '1',
  `dibuat_pada` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `diperbarui_pada` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_aktivitas`),
  UNIQUE KEY `uk_tb_aktivitas_nama_aktivitas` (`nama_aktivitas`),
  KEY `idx_tb_aktivitas_status_aktif` (`status_aktif`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tb_aktivitas`
--

LOCK TABLES `tb_aktivitas` WRITE;
/*!40000 ALTER TABLE `tb_aktivitas` DISABLE KEYS */;
INSERT INTO `tb_aktivitas` VALUES (1,'Bekerja','Alumni bekerja di perusahaan, instansi, atau lembaga tertentu.',1,'2026-05-08 19:34:05','2026-05-08 19:34:05'),(2,'Kuliah','Alumni melanjutkan pendidikan ke perguruan tinggi.',1,'2026-05-08 19:34:05','2026-05-08 19:34:05'),(3,'Wirausaha','Alumni menjalankan usaha secara mandiri.',1,'2026-05-08 19:34:05','2026-05-08 19:34:05'),(4,'Mencari Kerja','Alumni belum bekerja dan/atau sedang mencari pekerjaan.',1,'2026-05-08 19:34:05','2026-05-08 19:34:05');
/*!40000 ALTER TABLE `tb_aktivitas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tb_alumni`
--

DROP TABLE IF EXISTS `tb_alumni`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tb_alumni` (
  `id_alumni` int unsigned NOT NULL AUTO_INCREMENT,
  `id_pengguna` int unsigned DEFAULT NULL,
  `id_angkatan` int unsigned DEFAULT NULL,
  `id_kompetensi` int unsigned DEFAULT NULL,
  `nis` varchar(30) DEFAULT NULL,
  `nisn` varchar(30) DEFAULT NULL,
  `no_ijazah` varchar(50) DEFAULT NULL,
  `nama_lengkap` varchar(150) DEFAULT NULL,
  `jenis_kelamin` char(1) DEFAULT NULL,
  `tempat_lahir` varchar(100) DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `alamat` text,
  `nomor_telepon` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `status_verifikasi` varchar(30) NOT NULL DEFAULT 'menunggu_aktivasi',
  `catatan_verifikasi` text,
  `diverifikasi_oleh` int unsigned DEFAULT NULL,
  `diverifikasi_pada` datetime DEFAULT NULL,
  `dibuat_pada` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `diperbarui_pada` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status_pendaftaran` enum('menunggu_aktivasi','aktif','terdaftar') NOT NULL DEFAULT 'menunggu_aktivasi',
  `terdaftar_pada` datetime DEFAULT NULL,
  PRIMARY KEY (`id_alumni`),
  UNIQUE KEY `uk_tb_alumni_id_pengguna` (`id_pengguna`),
  UNIQUE KEY `uk_tb_alumni_nis` (`nis`),
  UNIQUE KEY `uk_tb_alumni_nisn` (`nisn`),
  UNIQUE KEY `uk_tb_alumni_no_ijazah` (`no_ijazah`),
  KEY `idx_tb_alumni_id_angkatan` (`id_angkatan`),
  KEY `idx_tb_alumni_status_verifikasi` (`status_verifikasi`),
  KEY `idx_tb_alumni_diverifikasi_oleh` (`diverifikasi_oleh`),
  KEY `idx_tb_alumni_email` (`email`),
  KEY `idx_tb_alumni_id_kompetensi` (`id_kompetensi`),
  CONSTRAINT `fk_tb_alumni_angkatan` FOREIGN KEY (`id_angkatan`) REFERENCES `tb_angkatan` (`id_angkatan`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_tb_alumni_kompetensi` FOREIGN KEY (`id_kompetensi`) REFERENCES `tb_kompetensi` (`id_kompetensi`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_tb_alumni_pengguna` FOREIGN KEY (`id_pengguna`) REFERENCES `tb_pengguna` (`id_pengguna`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_tb_alumni_verifikator` FOREIGN KEY (`diverifikasi_oleh`) REFERENCES `tb_pengguna` (`id_pengguna`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tb_alumni`
--

LOCK TABLES `tb_alumni` WRITE;
/*!40000 ALTER TABLE `tb_alumni` DISABLE KEYS */;
/*!40000 ALTER TABLE `tb_alumni` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tb_angkatan`
--

DROP TABLE IF EXISTS `tb_angkatan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tb_angkatan` (
  `id_angkatan` int unsigned NOT NULL AUTO_INCREMENT,
  `tahun_lulus` smallint unsigned NOT NULL,
  `status_aktif` tinyint(1) NOT NULL DEFAULT '1',
  `dibuat_pada` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `diperbarui_pada` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_angkatan`),
  UNIQUE KEY `uk_tb_angkatan_tahun_lulus` (`tahun_lulus`),
  KEY `idx_tb_angkatan_status_aktif` (`status_aktif`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tb_angkatan`
--

LOCK TABLES `tb_angkatan` WRITE;
/*!40000 ALTER TABLE `tb_angkatan` DISABLE KEYS */;
INSERT INTO `tb_angkatan` VALUES (1,2022,1,'2026-05-08 19:34:05','2026-05-08 19:34:05'),(2,2023,1,'2026-05-08 19:34:05','2026-05-08 19:34:05'),(3,2024,1,'2026-05-08 19:34:05','2026-05-08 19:34:05'),(4,2025,1,'2026-05-08 19:34:05','2026-05-08 19:34:05');
/*!40000 ALTER TABLE `tb_angkatan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tb_kompetensi`
--

DROP TABLE IF EXISTS `tb_kompetensi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tb_kompetensi` (
  `id_kompetensi` int unsigned NOT NULL AUTO_INCREMENT,
  `nama_kompetensi` varchar(100) NOT NULL,
  `akronim` varchar(20) NOT NULL,
  `status_aktif` tinyint(1) NOT NULL DEFAULT '1',
  `dibuat_pada` datetime DEFAULT CURRENT_TIMESTAMP,
  `diperbarui_pada` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_kompetensi`),
  UNIQUE KEY `uk_tb_kompetensi_nama` (`nama_kompetensi`),
  UNIQUE KEY `uk_tb_kompetensi_akronim` (`akronim`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tb_kompetensi`
--

LOCK TABLES `tb_kompetensi` WRITE;
/*!40000 ALTER TABLE `tb_kompetensi` DISABLE KEYS */;
INSERT INTO `tb_kompetensi` VALUES (1,'Akuntansi dan Keuangan Lembaga','AKL',1,'2026-05-08 19:34:05','2026-07-21 10:28:23'),(2,'Teknik Jaringan Komputer dan Telekomunikasi','TJK',1,'2026-05-08 19:34:05','2026-07-21 10:28:08'),(3,'Manajemen Perkantoran dan Layanan Bisnis','MPLB',1,'2026-05-08 19:34:05','2026-07-21 10:28:15');
/*!40000 ALTER TABLE `tb_kompetensi` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tb_notifikasi`
--

DROP TABLE IF EXISTS `tb_notifikasi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tb_notifikasi` (
  `id_notifikasi` int unsigned NOT NULL AUTO_INCREMENT,
  `id_pengguna` int unsigned NOT NULL,
  `tipe` varchar(50) NOT NULL,
  `judul` varchar(150) NOT NULL,
  `pesan` text NOT NULL,
  `target_url` varchar(255) DEFAULT NULL,
  `dibaca` tinyint(1) NOT NULL DEFAULT '0',
  `dibaca_pada` datetime DEFAULT NULL,
  `dibuat_pada` datetime DEFAULT CURRENT_TIMESTAMP,
  `diperbarui_pada` datetime DEFAULT NULL,
  PRIMARY KEY (`id_notifikasi`),
  KEY `idx_tb_notifikasi_pengguna` (`id_pengguna`),
  KEY `idx_tb_notifikasi_dibaca` (`dibaca`)
) ENGINE=InnoDB AUTO_INCREMENT=60 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tb_notifikasi`
--

LOCK TABLES `tb_notifikasi` WRITE;
/*!40000 ALTER TABLE `tb_notifikasi` DISABLE KEYS */;
/*!40000 ALTER TABLE `tb_notifikasi` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tb_pengajuan_legalisir`
--

DROP TABLE IF EXISTS `tb_pengajuan_legalisir`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tb_pengajuan_legalisir` (
  `id_pengajuan_legalisir` int unsigned NOT NULL AUTO_INCREMENT,
  `id_alumni` int unsigned NOT NULL,
  `jenis_dokumen` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `jumlah_lembar` int unsigned NOT NULL DEFAULT '1',
  `keperluan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `status` enum('diajukan','diproses','selesai','ditolak') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'diajukan',
  `catatan_admin` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `diproses_oleh` int unsigned DEFAULT NULL,
  `diproses_pada` datetime DEFAULT NULL,
  `selesai_pada` datetime DEFAULT NULL,
  `dibuat_pada` datetime DEFAULT CURRENT_TIMESTAMP,
  `diperbarui_pada` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_pengajuan_legalisir`),
  KEY `idx_legalisir_alumni_status` (`id_alumni`,`status`),
  KEY `diproses_oleh` (`diproses_oleh`),
  CONSTRAINT `tb_pengajuan_legalisir_diproses_oleh_foreign` FOREIGN KEY (`diproses_oleh`) REFERENCES `tb_pengguna` (`id_pengguna`) ON DELETE CASCADE ON UPDATE SET NULL,
  CONSTRAINT `tb_pengajuan_legalisir_id_alumni_foreign` FOREIGN KEY (`id_alumni`) REFERENCES `tb_alumni` (`id_alumni`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tb_pengajuan_legalisir`
--

LOCK TABLES `tb_pengajuan_legalisir` WRITE;
/*!40000 ALTER TABLE `tb_pengajuan_legalisir` DISABLE KEYS */;
/*!40000 ALTER TABLE `tb_pengajuan_legalisir` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tb_pengguna`
--

DROP TABLE IF EXISTS `tb_pengguna`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tb_pengguna` (
  `id_pengguna` int unsigned NOT NULL AUTO_INCREMENT,
  `id_peran` int unsigned NOT NULL,
  `nama_lengkap` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `kata_sandi` varchar(255) NOT NULL,
  `nomor_telepon` varchar(20) DEFAULT NULL,
  `foto_profil` varchar(255) DEFAULT NULL,
  `status_aktif` tinyint(1) NOT NULL DEFAULT '1',
  `token_reset` varchar(255) DEFAULT NULL,
  `token_reset_expired` datetime DEFAULT NULL,
  `terakhir_login` datetime DEFAULT NULL,
  `dibuat_pada` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `diperbarui_pada` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_pengguna`),
  UNIQUE KEY `uk_tb_pengguna_email` (`email`),
  KEY `idx_tb_pengguna_id_peran` (`id_peran`),
  KEY `idx_tb_pengguna_token_reset` (`token_reset`),
  CONSTRAINT `fk_tb_pengguna_peran` FOREIGN KEY (`id_peran`) REFERENCES `tb_peran` (`id_peran`)
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tb_pengguna`
--

LOCK TABLES `tb_pengguna` WRITE;
/*!40000 ALTER TABLE `tb_pengguna` DISABLE KEYS */;
INSERT INTO `tb_pengguna` VALUES (1,1,'Super Administrator','superadmin@tp3.test','$2y$12$67acQfedJ22smkJ7mO3xFuACHeoresSy65rMIuHoXrEqV9rYG69FO',NULL,NULL,1,NULL,NULL,NULL,'2026-05-08 17:10:01','2026-05-08 17:10:01'),(2,1,'Super Administrator','superadmin@tracerstudy.local','$2y$10$SbIJrqaNEVNncqHH0EMKietMHFqNx9xH9sPymiUBLsfEdsdq9Q55S',NULL,NULL,1,NULL,NULL,'2026-07-16 00:57:13','2026-06-03 17:06:33','2026-07-16 00:57:13'),(5,1,'Super Administrator','superadmin@tracer.com','$2y$10$sBIa0lOIEw9IMhq4iTm29.YnP5r3J.JZ2R34sEPXgECeOJ.5rZe4a',NULL,NULL,1,NULL,NULL,'2026-07-21 10:18:12','2026-06-10 00:38:52','2026-07-21 10:18:12'),(38,2,'Admin Tracer','tracerterput3@gmail.com','$2y$10$gsOXLWQpVHB3Qm2C9MrMK.YwAPoOs/J8YDekaoR3Nv6wBgOE4qSMW',NULL,NULL,1,NULL,NULL,'2026-07-21 01:21:34','2026-07-20 21:58:56','2026-07-21 01:21:34');
/*!40000 ALTER TABLE `tb_pengguna` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tb_peran`
--

DROP TABLE IF EXISTS `tb_peran`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tb_peran` (
  `id_peran` int unsigned NOT NULL AUTO_INCREMENT,
  `nama_peran` varchar(50) NOT NULL,
  `slug_peran` varchar(50) NOT NULL,
  `keterangan` text,
  `dibuat_pada` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `diperbarui_pada` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_peran`),
  UNIQUE KEY `uk_tb_peran_slug_peran` (`slug_peran`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tb_peran`
--

LOCK TABLES `tb_peran` WRITE;
/*!40000 ALTER TABLE `tb_peran` DISABLE KEYS */;
INSERT INTO `tb_peran` VALUES (1,'Super Admin','superadmin','Akses penuh ke aplikasi tracer study','2026-05-08 17:10:01','2026-06-03 17:06:33'),(2,'Admin Sekolah','admin_sekolah','Mengelola master data sekolah dan tracer alumni','2026-05-08 17:10:01','2026-06-03 13:47:32'),(3,'Alumni','alumni','Akun alumni untuk mengisi profil dan tracer study','2026-05-08 17:10:01','2026-06-03 16:58:38');
/*!40000 ALTER TABLE `tb_peran` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tb_tracer_alumni`
--

DROP TABLE IF EXISTS `tb_tracer_alumni`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tb_tracer_alumni` (
  `id_tracer` int unsigned NOT NULL AUTO_INCREMENT,
  `id_alumni` int unsigned NOT NULL,
  `id_aktivitas` int unsigned NOT NULL,
  `status` enum('terkirim','terverifikasi','disetujui') NOT NULL DEFAULT 'terkirim',
  `tanggal_pengisian` datetime DEFAULT NULL,
  `nama_perusahaan` varchar(150) DEFAULT NULL,
  `posisi_kerja` varchar(100) DEFAULT NULL,
  `nama_instansi` varchar(150) DEFAULT NULL,
  `bidang_instansi` varchar(100) DEFAULT NULL,
  `alamat_instansi` text,
  `bidang_pekerjaan` varchar(100) DEFAULT NULL,
  `alamat_perusahaan` text,
  `tahun_mulai_kerja` smallint unsigned DEFAULT NULL,
  `penghasilan_range` varchar(50) DEFAULT NULL,
  `universitas` varchar(150) DEFAULT NULL,
  `nama_perguruan_tinggi` varchar(150) DEFAULT NULL,
  `program_studi` varchar(100) DEFAULT NULL,
  `jenjang` varchar(50) DEFAULT NULL,
  `status_kuliah` varchar(50) DEFAULT NULL,
  `nama_usaha` varchar(150) DEFAULT NULL,
  `bidang_usaha` varchar(100) DEFAULT NULL,
  `modal_awal` decimal(15,2) DEFAULT NULL,
  `alamat_usaha` text,
  `penghasilan_usaha` varchar(50) DEFAULT NULL,
  `kendala_mencari_kerja` text,
  `minat_bidang_kerja` varchar(150) DEFAULT NULL,
  `keahlian_yang_dibutuhkan` text,
  `relevan_jurusan` tinyint(1) DEFAULT NULL,
  `saran_untuk_sekolah` text,
  `rencana_kedepan` text,
  `diverifikasi_oleh` int unsigned DEFAULT NULL,
  `diverifikasi_pada` datetime DEFAULT NULL,
  `disetujui_oleh` int unsigned DEFAULT NULL,
  `disetujui_pada` datetime DEFAULT NULL,
  `catatan_verifikasi` text,
  `dibuat_pada` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `diperbarui_pada` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_tracer`),
  UNIQUE KEY `uk_tb_tracer_alumni_id_alumni` (`id_alumni`),
  KEY `idx_tb_tracer_alumni_id_aktivitas` (`id_aktivitas`),
  KEY `idx_tb_tracer_alumni_status` (`status`),
  KEY `idx_tb_tracer_alumni_tanggal_pengisian` (`tanggal_pengisian`),
  KEY `idx_tb_tracer_alumni_diverifikasi_oleh` (`diverifikasi_oleh`),
  CONSTRAINT `fk_tb_tracer_alumni_aktivitas` FOREIGN KEY (`id_aktivitas`) REFERENCES `tb_aktivitas` (`id_aktivitas`) ON UPDATE CASCADE,
  CONSTRAINT `fk_tb_tracer_alumni_alumni` FOREIGN KEY (`id_alumni`) REFERENCES `tb_alumni` (`id_alumni`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_tb_tracer_alumni_verifikator` FOREIGN KEY (`diverifikasi_oleh`) REFERENCES `tb_pengguna` (`id_pengguna`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tb_tracer_alumni`
--

LOCK TABLES `tb_tracer_alumni` WRITE;
/*!40000 ALTER TABLE `tb_tracer_alumni` DISABLE KEYS */;
/*!40000 ALTER TABLE `tb_tracer_alumni` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'tracerstudy'
--

--
-- Dumping routines for database 'tracerstudy'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-07-21 10:32:32
