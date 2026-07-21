-- MySQL dump 10.13  Distrib 5.7.44, for Linux (x86_64)
--
-- Host: localhost    Database: telegram_bot
-- ------------------------------------------------------
-- Server version	5.7.44-log

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
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admins`
--

LOCK TABLES `admins` WRITE;
/*!40000 ALTER TABLE `admins` DISABLE KEYS */;
INSERT INTO `admins` VALUES (1,'root','admin123','2026-07-19 10:33:57');
/*!40000 ALTER TABLE `admins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `broadcasts`
--

DROP TABLE IF EXISTS `broadcasts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `broadcasts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `message` text,
  `keyboard` json DEFAULT NULL,
  `sent_count` int(11) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `broadcasts`
--

LOCK TABLES `broadcasts` WRITE;
/*!40000 ALTER TABLE `broadcasts` DISABLE KEYS */;
INSERT INTO `broadcasts` VALUES (1,'534254',NULL,0,'2026-07-19 10:41:11'),(2,'妹妹',NULL,0,'2026-07-19 11:00:05'),(3,'妹妹',NULL,0,'2026-07-19 11:01:08'),(4,'程序编写吃不消',NULL,0,'2026-07-19 11:01:37'),(5,'💎转账  2.5TRX=  1 笔转账\r\n\r\n100%到    100%到    100%到\r\n\r\n唯一地址     唯一地址   唯一地址\r\n<code>THJsXwUM1be7qdtjYNcp2yWpCMecjGv7au</code>\r\n(点击地址即可复制)\r\n\r\n/start      /start     /start (刷新导航栏)\r\n\r\n发卡网：https://huaer.shop','[[{\"text\": \"👍 点赞\", \"callback_data\": \"like\"}], [{\"url\": \"https://huaer.shop\", \"text\": \"🔗 花儿官网\"}]]',0,'2026-07-20 03:17:21'),(6,'💎转账  2.5TRX=  1 笔转账\r\n\r\n100%到    100%到    100%到\r\n\r\n唯一地址     唯一地址   唯一地址\r\n<code>THJsXwUM1be7qdtjYNcp2yWpCMecjGv7au</code>\r\n(点击地址即可复制)\r\n\r\n/start      /start     /start (刷新导航栏)\r\n\r\n发卡网：https://huaer.shop','[[{\"text\": \"👍 点赞\", \"callback_data\": \"like\"}], [{\"url\": \"https://huaer.shop\", \"text\": \"🔗 花儿官网\"}]]',0,'2026-07-20 08:10:03'),(7,'你好你好',NULL,0,'2026-07-21 02:03:23'),(8,'你好你好',NULL,0,'2026-07-21 02:05:27');
/*!40000 ALTER TABLE `broadcasts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `menu`
--

DROP TABLE IF EXISTS `menu`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `command` varchar(50) NOT NULL,
  `content` text NOT NULL,
  `photo_url` varchar(500) DEFAULT NULL,
  `sort` int(11) DEFAULT '0',
  `enabled` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `menu`
--

LOCK TABLES `menu` WRITE;
/*!40000 ALTER TABLE `menu` DISABLE KEYS */;
INSERT INTO `menu` VALUES (1,'测试','/ceshi','ceshichengg','',0,0,'2026-07-21 02:41:16'),(2,'测试','/ceshi','ceshichengg','',0,0,'2026-07-21 02:54:47'),(3,'测试','/ceshi','ceshichengg','',0,0,'2026-07-21 02:54:53');
/*!40000 ALTER TABLE `menu` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `menu_buttons`
--

DROP TABLE IF EXISTS `menu_buttons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menu_buttons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `button_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `command` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reply_text` text COLLATE utf8mb4_unicode_ci,
  `sort_order` int(11) DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `reply_type` enum('text','photo') COLLATE utf8mb4_unicode_ci DEFAULT 'text',
  `photo_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `inline_buttons` json DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `menu_buttons`
--

LOCK TABLES `menu_buttons` WRITE;
/*!40000 ALTER TABLE `menu_buttons` DISABLE KEYS */;
INSERT INTO `menu_buttons` VALUES (8,'飞机号发卡网','tghao','https://huaer.shop',2,1,'2026-07-19 17:02:10','text','',NULL),(9,'🔋能量租赁','trx','⚡️ 购买1小时能量闪租转账地址(点击复制)\r\n\r\n<code>TPPPPPFJm3wDDJC9s3ntJmYhhhGArN8KVS</code>\r\n\r\n⚡️ 转账 2.5TRX = 获取购买1笔\r\n⚡️ 转账 5TRX = 获取购买2笔\r\n⚡️ 转账 7.5TRX = 获取购买3笔\r\n⚡️ 转账 10TRX = 获取购买4笔\r\n⚡️ 转账 12.5TRX = 获取购买5笔\r\n⚡️ 转账 15TRX = 获取购买6笔\r\n⚡️ 转账 25TRX = 获取购买10笔\r\n⚡️ 转账 50TRX = 获取购买20笔\r\n\r\n\r\n⚠️温馨提示⚠️\r\n- 购买1笔能量数为6.5W\r\n- 向无U的地址转账, 需要双倍的能量\r\n- 请在1小时内使用能量，否则会过期回收\r\n- 务必转入以上对应金额的TRX，否则会租用失败',1,1,'2026-07-19 17:02:10','text','https://huaer.shop/img/TPPPPP.jpg',NULL),(10,'飞机会员星星','vip','TG官方会员开通服务\r\n当前实时价格：\r\n1个月 / 5UU   ≈  38RMB\r\n3个月 / 14UU ≈ 100RMB\r\n6个月 / 18UU ≈ 130RMB\r\n12个月/ 32UU ≈ 230RMB               \r\n\r\n⭐星星价格：0.02U/个\r\n（支持单次购买50-10000个星星）\r\n\r\n国内外飞机号，养2-5个月  1.5U/10RMB\r\n\r\n客服：@huaer11 (https://t.me/huaer11)',3,1,'2026-07-19 17:02:10','text',NULL,NULL),(14,'联系客服','contact','联系客服     @huaer11\r\n双向BOT     @huaer11bot\r\n花儿频道     @huaer00',7,1,'2026-07-19 17:02:10','text',NULL,NULL);
/*!40000 ALTER TABLE `menu_buttons` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) NOT NULL COMMENT 'Telegram User ID',
  `username` varchar(255) DEFAULT NULL,
  `first_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) DEFAULT NULL,
  `is_blocked` tinyint(1) DEFAULT '0',
  `joined_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_active` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (5712250938,'HuaerTRX','花·飞机号/飞机会员/白资·收UU/售TRX','',0,'2026-07-20 02:43:44','2026-07-21 03:15:15'),(8295042957,'HuaerKF','花 · 客服','',0,'2026-07-19 10:59:21','2026-07-21 08:57:07');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'telegram_bot'
--

--
-- Dumping routines for database 'telegram_bot'
--
/*!50003 DROP PROCEDURE IF EXISTS `AddMenuColumns` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`telegram_bot`@`localhost` PROCEDURE `AddMenuColumns`()
BEGIN

    IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS 

                   WHERE TABLE_SCHEMA = 'telegram_bot' 

                   AND TABLE_NAME = 'menu_buttons' 

                   AND COLUMN_NAME = 'reply_type') THEN

        ALTER TABLE menu_buttons ADD COLUMN reply_type ENUM('text', 'photo') DEFAULT 'text';

    END IF;



    IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS 

                   WHERE TABLE_SCHEMA = 'telegram_bot' 

                   AND TABLE_NAME = 'menu_buttons' 

                   AND COLUMN_NAME = 'photo_url') THEN

        ALTER TABLE menu_buttons ADD COLUMN photo_url VARCHAR(500) NULL;

    END IF;



    IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS 

                   WHERE TABLE_SCHEMA = 'telegram_bot' 

                   AND TABLE_NAME = 'menu_buttons' 

                   AND COLUMN_NAME = 'inline_buttons') THEN

        ALTER TABLE menu_buttons ADD COLUMN inline_buttons JSON NULL;

    END IF;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-07-21  9:00:23
