-- RechaX Database Backup Archive
-- Generated: 2026-10-16 21:30:17
-- Host: localhost | Database: rechax_official

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `activity_logs`;
CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action_type` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_action` (`action_type`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=115 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `activity_logs` VALUES ('1','1',NULL,'LOGIN_SUCCESS','Admin logged in: admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-30 06:46:29');
INSERT INTO `activity_logs` VALUES ('2','1',NULL,'LOGIN_SUCCESS','Admin logged in: admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-31 18:32:57');
INSERT INTO `activity_logs` VALUES ('3',NULL,NULL,'LOGIN_FAILURE','Failed login attempt for username: johnnyislam112','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 06:09:21');
INSERT INTO `activity_logs` VALUES ('4','1',NULL,'LOGIN_SUCCESS','Admin logged in: admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 06:09:30');
INSERT INTO `activity_logs` VALUES ('5','1',NULL,'PRODUCT_UPDATED','Updated product: Weekly Diamond Pass (ID: 11)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-09-01 06:25:01');
INSERT INTO `activity_logs` VALUES ('6','1',NULL,'LOGIN_SUCCESS','Admin logged in: admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-03 06:05:34');
INSERT INTO `activity_logs` VALUES ('7','1',NULL,'LOGIN_SUCCESS','Admin logged in: admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-08 22:56:03');
INSERT INTO `activity_logs` VALUES ('8','1',NULL,'SECTION_STATUS_TOGGLED','Section ID 6 status changed to Hidden','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-08 23:15:11');
INSERT INTO `activity_logs` VALUES ('9','1',NULL,'SECTION_STATUS_TOGGLED','Section ID 4 status changed to Hidden','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-08 23:15:17');
INSERT INTO `activity_logs` VALUES ('10','1',NULL,'SECTION_STATUS_TOGGLED','Section ID 3 status changed to Hidden','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-08 23:15:22');
INSERT INTO `activity_logs` VALUES ('11','1',NULL,'GAME_UPDATED','Updated game: Mobile Legends (ID: 1)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-08 23:37:05');
INSERT INTO `activity_logs` VALUES ('12','1',NULL,'GAME_UPDATED','Updated game: Mobile Legends (ID: 1)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-08 23:39:30');
INSERT INTO `activity_logs` VALUES ('13','1',NULL,'LOGIN_SUCCESS','Admin logged in: admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-12 22:38:02');
INSERT INTO `activity_logs` VALUES ('14','1',NULL,'LOGIN_SUCCESS','Admin logged in: admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-15 16:59:18');
INSERT INTO `activity_logs` VALUES ('15','1',NULL,'LOGOUT','Admin logged out: Super Admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-15 21:24:17');
INSERT INTO `activity_logs` VALUES ('16','1',NULL,'LOGIN_SUCCESS','Admin logged in: admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-15 21:24:50');
INSERT INTO `activity_logs` VALUES ('17','1',NULL,'LOGOUT','Admin logged out: Super Admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-15 21:25:17');
INSERT INTO `activity_logs` VALUES ('18','1',NULL,'LOGIN_SUCCESS','Admin logged in: admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-15 21:55:49');
INSERT INTO `activity_logs` VALUES ('19','1',NULL,'LOGIN_SUCCESS','Admin logged in: admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-18 13:59:21');
INSERT INTO `activity_logs` VALUES ('20','1',NULL,'LOGIN_SUCCESS','Admin logged in: admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-21 00:16:01');
INSERT INTO `activity_logs` VALUES ('21','1',NULL,'SETTINGS_UPDATED','Updated platform configuration settings','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-21 00:28:48');
INSERT INTO `activity_logs` VALUES ('22','1',NULL,'SETTINGS_UPDATED','Updated platform configuration settings','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-21 00:29:10');
INSERT INTO `activity_logs` VALUES ('23','1',NULL,'GAME_UPDATED','Updated game: League of Legends (PC) (ID: 18)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-21 00:33:25');
INSERT INTO `activity_logs` VALUES ('24','1',NULL,'GAME_UPDATED','Updated game: League of Legends (PC) (ID: 18)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-21 00:53:08');
INSERT INTO `activity_logs` VALUES ('25','1',NULL,'GAME_UPDATED','Updated game: Genshin Impact (Login Top-Up) (ID: 17)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-21 00:53:25');
INSERT INTO `activity_logs` VALUES ('26','1',NULL,'GAME_UPDATED','Updated game: PUBG Mobile (Login Method) (ID: 16)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-21 00:53:38');
INSERT INTO `activity_logs` VALUES ('27','1',NULL,'GAME_UPDATED','Updated game: Razer Gold PIN (Global) (ID: 15)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-21 00:53:49');
INSERT INTO `activity_logs` VALUES ('28','1',NULL,'GAME_UPDATED','Updated game: Steam Wallet Code (Global) (ID: 14)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-21 00:54:00');
INSERT INTO `activity_logs` VALUES ('29','1',NULL,'GAME_UPDATED','Updated game: Google Play Gift Card (US/Global) (ID: 13)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-21 00:54:13');
INSERT INTO `activity_logs` VALUES ('30','1',NULL,'GAME_UPDATED','Updated game: Discord Nitro (ID: 12)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-21 00:54:51');
INSERT INTO `activity_logs` VALUES ('31','1',NULL,'GAME_UPDATED','Updated game: Clash of Clans (ID: 8)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-21 00:56:12');
INSERT INTO `activity_logs` VALUES ('32','1',NULL,'GAME_UPDATED','Updated game: Free Fire Weekly Membership (ID: 11)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-21 00:56:23');
INSERT INTO `activity_logs` VALUES ('33','1',NULL,'GAME_UPDATED','Updated game: MLBB Weekly Diamond Pass (ID: 10)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-21 00:57:06');
INSERT INTO `activity_logs` VALUES ('34','1',NULL,'GAME_UPDATED','Updated game: Clash Royale (ID: 9)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-21 00:57:17');
INSERT INTO `activity_logs` VALUES ('35','1',NULL,'GAME_UPDATED','Updated game: Call of Duty Mobile (ID: 6)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-21 00:57:28');
INSERT INTO `activity_logs` VALUES ('36','1',NULL,'GAME_UPDATED','Updated game: Genshin Impact (ID: 5)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-21 00:57:45');
INSERT INTO `activity_logs` VALUES ('37','1',NULL,'GAME_UPDATED','Updated game: Valorant (ID: 4)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-21 01:14:54');
INSERT INTO `activity_logs` VALUES ('38','1',NULL,'LOGIN_SUCCESS','Admin logged in: admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-21 16:53:31');
INSERT INTO `activity_logs` VALUES ('39','1',NULL,'GAME_UPDATED','Updated game: Free Fire (ID: 2)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-21 16:59:02');
INSERT INTO `activity_logs` VALUES ('40','1',NULL,'GAME_UPDATED','Updated game: Free Fire (ID: 2)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-21 18:53:04');
INSERT INTO `activity_logs` VALUES ('41',NULL,NULL,'LOGIN_FAILURE','Failed admin login attempt for username: johnnyislam112','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 21:55:01');
INSERT INTO `activity_logs` VALUES ('42','1',NULL,'LOGIN_SUCCESS','Admin logged in: admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 21:55:14');
INSERT INTO `activity_logs` VALUES ('43','1',NULL,'SECTION_UPDATED','Updated homepage section: Popular Games (ID: 1)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 21:57:38');
INSERT INTO `activity_logs` VALUES ('44','1',NULL,'SECTION_UPDATED','Updated homepage section: Popular Games (ID: 1)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 21:58:00');
INSERT INTO `activity_logs` VALUES ('45','1',NULL,'SECTION_UPDATED','Updated homepage section: Popular Games (ID: 1)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 21:58:17');
INSERT INTO `activity_logs` VALUES ('46','1',NULL,'SECTION_UPDATED','Updated homepage section: Subscriptions & Passes (ID: 5)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 21:59:11');
INSERT INTO `activity_logs` VALUES ('47','1',NULL,'SECTION_UPDATED','Updated homepage section: Popular Games (ID: 1)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 22:00:08');
INSERT INTO `activity_logs` VALUES ('48','1',NULL,'SECTION_ITEM_ADDED','Added game #1 to section #1','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 22:00:38');
INSERT INTO `activity_logs` VALUES ('49','1',NULL,'SECTION_ITEM_ADDED','Added game #2 to section #1','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 22:00:47');
INSERT INTO `activity_logs` VALUES ('50','1',NULL,'SECTION_ITEM_REMOVED','Removed item ID 4 from section','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 22:00:54');
INSERT INTO `activity_logs` VALUES ('51','1',NULL,'SECTION_ITEM_ADDED','Added game #2 to section #1','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 22:01:24');
INSERT INTO `activity_logs` VALUES ('52','1',NULL,'SECTION_DELETED','Deleted homepage section: PC Games (ID: 6). Games and products remain untouched.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 22:02:27');
INSERT INTO `activity_logs` VALUES ('53','1',NULL,'SECTION_DELETED','Deleted homepage section: Redeem Code / Gift Card / Gifting Service (ID: 4). Games and products remain untouched.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 22:02:37');
INSERT INTO `activity_logs` VALUES ('54','1',NULL,'SECTION_UPDATED','Updated homepage section: Subscriptions (ID: 5)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 22:05:27');
INSERT INTO `activity_logs` VALUES ('55','1',NULL,'SECTION_UPDATED','Updated homepage section: Subscriptions (ID: 5)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 22:05:46');
INSERT INTO `activity_logs` VALUES ('56','1',NULL,'PROMOTION_ADDED','Created promotion: EID MEGA SALE','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 22:09:04');
INSERT INTO `activity_logs` VALUES ('57','1',NULL,'PROMOTION_ADDED','Created promotion: EID MEGA SALE','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 22:44:37');
INSERT INTO `activity_logs` VALUES ('58','1',NULL,'PROMOTION_DELETED','Deleted promotion ID: 1','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 22:46:12');
INSERT INTO `activity_logs` VALUES ('59','1',NULL,'PROMOTION_UPDATED','Updated promotion: EID MEGA SALE (ID: 2)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 22:47:55');
INSERT INTO `activity_logs` VALUES ('60','1',NULL,'PROMOTION_UPDATED','Updated promotion: EID MEGA SALE (ID: 2)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 22:48:39');
INSERT INTO `activity_logs` VALUES ('61','1',NULL,'PROMOTION_UPDATED','Updated promotion: EID MEGA SALE (ID: 2)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 22:50:01');
INSERT INTO `activity_logs` VALUES ('62','1',NULL,'SUPPLIER_SYNCED','Synced balance for supplier ID: 1 ($839.37)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 22:55:41');
INSERT INTO `activity_logs` VALUES ('63','1',NULL,'SUPPLIER_SYNCED','Synced balance for supplier ID: 1 ($1396.41)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 22:55:50');
INSERT INTO `activity_logs` VALUES ('64','1',NULL,'SUPPLIER_SYNCED','Synced balance for supplier ID: 1 ($835.25)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','2026-09-22 22:56:02');
INSERT INTO `activity_logs` VALUES ('65','1',NULL,'LOGIN_SUCCESS','Admin logged in: admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','2026-09-26 22:26:52');
INSERT INTO `activity_logs` VALUES ('66','1',NULL,'LOGIN_SUCCESS','Admin logged in: admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','2026-09-27 22:56:30');
INSERT INTO `activity_logs` VALUES ('67','1',NULL,'LOGIN_SUCCESS','Admin logged in: admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','2026-10-02 19:43:51');
INSERT INTO `activity_logs` VALUES ('68','1',NULL,'SELECTOR_TOGGLED','Toggled selector status for game ID 2 to 1','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','2026-10-02 20:30:28');
INSERT INTO `activity_logs` VALUES ('69','1',NULL,'SELECTOR_TOGGLED','Toggled selector status for game ID 3 to 1','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','2026-10-02 20:30:30');
INSERT INTO `activity_logs` VALUES ('70','1',NULL,'SELECTOR_TOGGLED','Toggled selector status for game ID 7 to 1','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','2026-10-02 20:30:31');
INSERT INTO `activity_logs` VALUES ('71','1',NULL,'PROMOTION_DELETED','Deleted promotion ID: 2','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','2026-10-03 00:31:52');
INSERT INTO `activity_logs` VALUES ('72','1',NULL,'PROMOTION_DELETED','Deleted promotion ID: 2','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','2026-10-03 02:44:40');
INSERT INTO `activity_logs` VALUES ('73','1',NULL,'LOGIN_SUCCESS','Admin logged in: admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','2026-10-04 06:39:51');
INSERT INTO `activity_logs` VALUES ('74','1',NULL,'LOGIN_SUCCESS','Admin logged in: admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','2026-10-05 02:28:47');
INSERT INTO `activity_logs` VALUES ('75','1',NULL,'LOGIN_SUCCESS','Admin logged in: admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-08 23:51:52');
INSERT INTO `activity_logs` VALUES ('76','1',NULL,'LOGIN_SUCCESS','Admin logged in: admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-09 23:01:00');
INSERT INTO `activity_logs` VALUES ('77','1',NULL,'LOGIN_SUCCESS','Admin logged in: admin','::1','UNKNOWN','2026-10-10 03:07:33');
INSERT INTO `activity_logs` VALUES ('78','1',NULL,'MEDIA_DELETED','Deleted media file: games/adobe-creative-cloud.png','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-10 03:10:26');
INSERT INTO `activity_logs` VALUES ('79','1',NULL,'FEATURE_FLAG_TOGGLED','Toggled feature flag maintenance_mode to ENABLED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-10 03:25:14');
INSERT INTO `activity_logs` VALUES ('80','1',NULL,'FEATURE_FLAG_TOGGLED','Toggled feature flag maintenance_mode to DISABLED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-10 03:25:34');
INSERT INTO `activity_logs` VALUES ('81','1',NULL,'FEATURE_FLAG_TOGGLED','Toggled feature flag maintenance_mode to ENABLED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-10 03:25:35');
INSERT INTO `activity_logs` VALUES ('82','1',NULL,'FEATURE_FLAG_TOGGLED','Toggled feature flag maintenance_mode to DISABLED','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-10 03:25:43');
INSERT INTO `activity_logs` VALUES ('83','1',NULL,'LOGIN_SUCCESS','Admin logged in: admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-11 07:25:29');
INSERT INTO `activity_logs` VALUES ('84','1',NULL,'LOGIN_SUCCESS','Admin logged in: admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-13 01:59:26');
INSERT INTO `activity_logs` VALUES ('85','1',NULL,'LOGIN_SUCCESS','Admin logged in: admin','::1','UNKNOWN','2026-10-13 02:11:32');
INSERT INTO `activity_logs` VALUES ('86','1',NULL,'SETTINGS_UPDATED','Updated platform configuration settings','UNKNOWN','UNKNOWN','2026-10-13 02:12:03');
INSERT INTO `activity_logs` VALUES ('87','1',NULL,'SETTINGS_UPDATED','Updated platform configuration settings','UNKNOWN','UNKNOWN','2026-10-13 02:12:34');
INSERT INTO `activity_logs` VALUES ('88','1',NULL,'LOGIN_SUCCESS','Admin logged in: admin','::1','UNKNOWN','2026-10-13 02:13:04');
INSERT INTO `activity_logs` VALUES ('89','1',NULL,'SETTINGS_UPDATED','Updated platform configuration settings','UNKNOWN','UNKNOWN','2026-10-13 02:13:04');
INSERT INTO `activity_logs` VALUES ('90','1',NULL,'LOGIN_SUCCESS','Admin logged in: admin','::1','UNKNOWN','2026-10-13 02:29:59');
INSERT INTO `activity_logs` VALUES ('91','1',NULL,'SETTINGS_UPDATED','Updated platform configuration settings','UNKNOWN','UNKNOWN','2026-10-13 02:30:31');
INSERT INTO `activity_logs` VALUES ('92','1',NULL,'LOGIN_SUCCESS','Admin logged in: admin','::1','UNKNOWN','2026-10-13 02:38:09');
INSERT INTO `activity_logs` VALUES ('93','1',NULL,'SETTINGS_UPDATED','Updated platform configuration settings','UNKNOWN','UNKNOWN','2026-10-13 02:38:10');
INSERT INTO `activity_logs` VALUES ('94','1',NULL,'LOGIN_SUCCESS','Admin logged in: admin','::1','UNKNOWN','2026-10-13 03:09:08');
INSERT INTO `activity_logs` VALUES ('95','1',NULL,'SETTINGS_UPDATED','Updated platform configuration settings','UNKNOWN','UNKNOWN','2026-10-13 03:09:41');
INSERT INTO `activity_logs` VALUES ('96','1',NULL,'LOGIN_SUCCESS','Admin logged in: admin','::1','UNKNOWN','2026-10-13 03:28:51');
INSERT INTO `activity_logs` VALUES ('97','1',NULL,'SETTINGS_UPDATED','Updated platform configuration settings','UNKNOWN','UNKNOWN','2026-10-13 03:29:42');
INSERT INTO `activity_logs` VALUES ('98','1',NULL,'LOGIN_SUCCESS','Admin logged in: admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-14 17:23:25');
INSERT INTO `activity_logs` VALUES ('99','1',NULL,'ORDER_UPDATED','Updated Order #RX6ACE242D8221B13 status to cancelled, payment to failed','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-14 17:25:52');
INSERT INTO `activity_logs` VALUES ('100','1',NULL,'LOGIN_SUCCESS','Admin logged in: admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-15 21:58:58');
INSERT INTO `activity_logs` VALUES ('101','1',NULL,'LOGIN_SUCCESS','Admin logged in: admin','::1','UNKNOWN','2026-10-16 08:22:19');
INSERT INTO `activity_logs` VALUES ('102','1',NULL,'LOGIN_SUCCESS','Admin logged in: admin','::1','UNKNOWN','2026-10-16 08:37:42');
INSERT INTO `activity_logs` VALUES ('103','1',NULL,'LOGIN_SUCCESS','Admin logged in: admin','::1','UNKNOWN','2026-10-16 08:42:23');
INSERT INTO `activity_logs` VALUES ('104','1',NULL,'LOGIN_SUCCESS','Admin logged in: admin','::1','UNKNOWN','2026-10-16 09:08:27');
INSERT INTO `activity_logs` VALUES ('105','1',NULL,'MEDIA_DELETED','Deleted media file: games/google-one.png','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-16 09:10:27');
INSERT INTO `activity_logs` VALUES ('106','1',NULL,'LOGIN_SUCCESS','Admin logged in: admin','::1','UNKNOWN','2026-10-16 10:20:44');
INSERT INTO `activity_logs` VALUES ('107','1',NULL,'LOGIN_SUCCESS','Admin logged in: admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-16 19:13:32');
INSERT INTO `activity_logs` VALUES ('108','1',NULL,'SECTION_STATUS_TOGGLED','Section ID 5 status changed to Hidden','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-17 00:59:58');
INSERT INTO `activity_logs` VALUES ('109','1',NULL,'SECTION_STATUS_TOGGLED','Section ID 2 status changed to Hidden','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-17 01:28:25');
INSERT INTO `activity_logs` VALUES ('110','1',NULL,'SECTION_STATUS_TOGGLED','Section ID 1 status changed to Hidden','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-17 01:28:28');
INSERT INTO `activity_logs` VALUES ('111','1',NULL,'SECTION_STATUS_TOGGLED','Section ID 2 status changed to Active','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-17 01:28:41');
INSERT INTO `activity_logs` VALUES ('112','1',NULL,'SECTION_STATUS_TOGGLED','Section ID 1 status changed to Active','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-17 01:28:42');
INSERT INTO `activity_logs` VALUES ('113','1',NULL,'BACKUP_CREATED','Generated database backup archive: rechax_backup_2026-10-16_212958.sql','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-17 01:29:58');
INSERT INTO `activity_logs` VALUES ('114','1',NULL,'BACKUP_DELETED','Deleted backup archive: rechax_backup_2026-10-16_212958.sql','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-17 01:30:07');

DROP TABLE IF EXISTS `admin_login_logs`;
CREATE TABLE `admin_login_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) DEFAULT NULL,
  `username` varchar(100) NOT NULL,
  `username_attempted` varchar(100) DEFAULT NULL,
  `status` enum('success','failed') NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `failure_reason` varchar(255) DEFAULT NULL,
  `attempted_at` datetime DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_ip` (`ip_address`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `role` enum('superadmin','admin','moderator') DEFAULT 'admin',
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions`)),
  `two_factor_secret` varchar(255) DEFAULT NULL,
  `two_factor_enabled` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `admins` VALUES ('1','admin','admin@rechax.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Super Admin',NULL,'superadmin',NULL,NULL,'0','1','2026-10-16 19:13:32','2026-06-30 01:07:40');

DROP TABLE IF EXISTS `api_keys`;
CREATE TABLE `api_keys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `api_key` varchar(64) NOT NULL,
  `label` varchar(100) NOT NULL,
  `permissions` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_used_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `api_key` (`api_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `banners`;
CREATE TABLE `banners` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) DEFAULT NULL,
  `subtitle` varchar(300) DEFAULT NULL,
  `image` varchar(255) NOT NULL,
  `link_url` varchar(500) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_banners_active_sort` (`is_active`,`sort_order`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `banners` VALUES ('1','THE ASPIRANTS 2.0','ANGELA • RUBY • EXCLUSIVE EVENT','assets/img/banners/the_aspirants_event_banner.jpg','#','1','0','2026-08-31 18:35:04');
INSERT INTO `banners` VALUES ('3','','','assets/img/banners/1788191826_Honor of Kings4.jpg','','1','1','2026-08-31 21:57:06');
INSERT INTO `banners` VALUES ('4','','','assets/img/banners/1788394125_WhatsApp Image 2026-09-03 at 7.34.49 AM (2).jpeg','','1','2','2026-10-16 23:07:40');

DROP TABLE IF EXISTS `blog_posts`;
CREATE TABLE `blog_posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `excerpt` text DEFAULT NULL,
  `featured_image` varchar(255) DEFAULT NULL,
  `category` varchar(100) DEFAULT 'Guides',
  `author_admin_id` int(11) DEFAULT NULL,
  `seo_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `is_published` tinyint(1) DEFAULT 1,
  `views_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_slug` (`slug`),
  KEY `idx_published` (`is_published`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `cart`;
CREATE TABLE `cart` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `session_id` varchar(100) DEFAULT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `player_id` varchar(100) DEFAULT NULL,
  `server_id` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `coin_transactions`;
CREATE TABLE `coin_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` enum('purchase','usage','refund','admin_adjustment','bonus') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `balance_before` decimal(10,2) NOT NULL,
  `balance_after` decimal(10,2) NOT NULL,
  `reference_id` varchar(100) DEFAULT NULL,
  `description` text NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_type` (`type`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `coin_transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `coupons`;
CREATE TABLE `coupons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `type` enum('fixed','percent') DEFAULT 'fixed',
  `value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `min_purchase` decimal(10,2) DEFAULT 0.00,
  `max_discount` decimal(10,2) DEFAULT NULL,
  `start_date` datetime DEFAULT NULL,
  `usage_limit` int(11) DEFAULT NULL,
  `per_user_limit` int(11) DEFAULT 1,
  `game_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `used_count` int(11) DEFAULT 0,
  `expires_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `coupon_code` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `coupons` VALUES ('1','RECHAX10','percent','10.00','50.00',NULL,NULL,NULL,'1',NULL,NULL,'0',NULL,'1','2026-10-10 00:12:39',NULL);

DROP TABLE IF EXISTS `cron_jobs`;
CREATE TABLE `cron_jobs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `job_key` varchar(100) NOT NULL,
  `name` varchar(150) NOT NULL,
  `schedule_expression` varchar(50) DEFAULT '*/15 * * * *',
  `last_run_at` datetime DEFAULT NULL,
  `last_status` enum('success','failed','running') DEFAULT 'success',
  `last_message` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `job_key` (`job_key`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `cron_jobs` VALUES ('1','supplier_balance_sync','Supplier Balances & Health Sync','*/30 * * * *',NULL,'success',NULL,'1');
INSERT INTO `cron_jobs` VALUES ('2','order_status_sync','Pending API Order Status Checker','*/5 * * * *',NULL,'success',NULL,'1');
INSERT INTO `cron_jobs` VALUES ('3','webhook_retry_queue','Failed Outgoing Webhook Retry Queue','*/10 * * * *',NULL,'success',NULL,'1');
INSERT INTO `cron_jobs` VALUES ('4','database_backup_daily','Automated Daily Database Backup','0 3 * * *',NULL,'success',NULL,'1');
INSERT INTO `cron_jobs` VALUES ('5','expire_coupons_cleanup','Coupon Expiry & Inventory Sync','0 * * * *',NULL,'success',NULL,'1');

DROP TABLE IF EXISTS `feature_flags`;
CREATE TABLE `feature_flags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `flag_key` varchar(100) NOT NULL,
  `label` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `is_enabled` tinyint(1) DEFAULT 1,
  `config_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`config_data`)),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `flag_key` (`flag_key`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `feature_flags` VALUES ('1','reseller_system','B2B Reseller Portal & API','Enable wholesale reseller tier pricing and developer API access.','1',NULL,'2026-09-27 07:54:05');
INSERT INTO `feature_flags` VALUES ('2','gift_cards','Gift Cards & Digital Vouchers','Enable instant inventory code delivery for gift cards and redeem PINs.','1',NULL,'2026-09-27 07:54:05');
INSERT INTO `feature_flags` VALUES ('3','wallet_system','Customer Wallet & Add Money','Allow registered customers to deposit funds and pay via wallet balance.','1',NULL,'2026-09-27 07:54:05');
INSERT INTO `feature_flags` VALUES ('4','player_validation','Player ID Real-time Validation','Validate Player ID & Server ID against game servers before payment.','1',NULL,'2026-09-27 07:54:05');
INSERT INTO `feature_flags` VALUES ('5','supplier_fallback','Auto Supplier Fallback Routing','Automatically route order to backup supplier if primary API fails.','1',NULL,'2026-09-27 07:54:05');
INSERT INTO `feature_flags` VALUES ('6','whatsapp_alerts','WhatsApp / SMS Dispatcher','Send instant order receipt notifications via WhatsApp API gateway.','1',NULL,'2026-09-27 07:54:05');
INSERT INTO `feature_flags` VALUES ('7','blog_system','Gaming Blog & SEO Guides','Publish SEO gaming articles and news on the storefront.','1',NULL,'2026-09-27 07:54:05');
INSERT INTO `feature_flags` VALUES ('8','maintenance_mode','Storefront Maintenance Mode','Restrict customer access while maintenance is ongoing.','0',NULL,'2026-10-10 03:25:43');

DROP TABLE IF EXISTS `game_selector_configs`;
CREATE TABLE `game_selector_configs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `game_id` int(11) NOT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `selector_type` enum('region','product_type','package_category','platform','custom','none') NOT NULL DEFAULT 'region',
  `modal_title` varchar(200) DEFAULT NULL,
  `modal_subtitle` varchar(300) DEFAULT 'Select your region',
  `modal_icon` varchar(255) DEFAULT 'bi bi-globe',
  `layout_columns` tinyint(4) DEFAULT 4,
  `card_style` enum('cover_card','pill_card','compact_card') DEFAULT 'cover_card',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `game_id` (`game_id`),
  KEY `idx_enabled` (`is_enabled`),
  CONSTRAINT `game_selector_configs_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `game_selector_configs` VALUES ('1','1','1','region','MOBILE LEGENDS','Select your region','bi bi-globe','4','cover_card','2026-10-02 19:49:31','2026-10-02 19:49:31');
INSERT INTO `game_selector_configs` VALUES ('2','2','1','region','FREE FIRE','Select your region','bi bi-globe','4','cover_card','2026-10-02 20:30:28','2026-10-02 20:30:28');
INSERT INTO `game_selector_configs` VALUES ('3','3','1','region','PUBG MOBILE','Select your region','bi bi-globe','4','cover_card','2026-10-02 20:30:30','2026-10-02 20:30:30');
INSERT INTO `game_selector_configs` VALUES ('4','7','1','region','HONOR OF KINGS','Select your region','bi bi-globe','4','cover_card','2026-10-02 20:30:31','2026-10-02 20:30:31');

DROP TABLE IF EXISTS `game_selector_option_products`;
CREATE TABLE `game_selector_option_products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `option_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_option_product` (`option_id`,`product_id`),
  KEY `product_id` (`product_id`),
  KEY `idx_option` (`option_id`),
  CONSTRAINT `game_selector_option_products_ibfk_1` FOREIGN KEY (`option_id`) REFERENCES `game_selector_options` (`id`) ON DELETE CASCADE,
  CONSTRAINT `game_selector_option_products_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=189 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `game_selector_option_products` VALUES ('1','1','1','1');
INSERT INTO `game_selector_option_products` VALUES ('2','1','11','2');
INSERT INTO `game_selector_option_products` VALUES ('3','1','2','3');
INSERT INTO `game_selector_option_products` VALUES ('4','1','3','4');
INSERT INTO `game_selector_option_products` VALUES ('5','1','4','5');
INSERT INTO `game_selector_option_products` VALUES ('6','1','5','6');
INSERT INTO `game_selector_option_products` VALUES ('7','1','6','7');
INSERT INTO `game_selector_option_products` VALUES ('8','1','7','8');
INSERT INTO `game_selector_option_products` VALUES ('9','1','8','9');
INSERT INTO `game_selector_option_products` VALUES ('10','1','9','10');
INSERT INTO `game_selector_option_products` VALUES ('11','1','10','11');
INSERT INTO `game_selector_option_products` VALUES ('12','1','12','35');
INSERT INTO `game_selector_option_products` VALUES ('13','2','1','1');
INSERT INTO `game_selector_option_products` VALUES ('14','2','11','2');
INSERT INTO `game_selector_option_products` VALUES ('15','2','2','3');
INSERT INTO `game_selector_option_products` VALUES ('16','2','3','4');
INSERT INTO `game_selector_option_products` VALUES ('17','2','4','5');
INSERT INTO `game_selector_option_products` VALUES ('18','2','5','6');
INSERT INTO `game_selector_option_products` VALUES ('19','2','6','7');
INSERT INTO `game_selector_option_products` VALUES ('20','2','7','8');
INSERT INTO `game_selector_option_products` VALUES ('21','2','8','9');
INSERT INTO `game_selector_option_products` VALUES ('22','2','9','10');
INSERT INTO `game_selector_option_products` VALUES ('23','2','10','11');
INSERT INTO `game_selector_option_products` VALUES ('24','2','12','35');
INSERT INTO `game_selector_option_products` VALUES ('25','3','1','1');
INSERT INTO `game_selector_option_products` VALUES ('26','3','11','2');
INSERT INTO `game_selector_option_products` VALUES ('27','3','2','3');
INSERT INTO `game_selector_option_products` VALUES ('28','3','3','4');
INSERT INTO `game_selector_option_products` VALUES ('29','3','4','5');
INSERT INTO `game_selector_option_products` VALUES ('30','3','5','6');
INSERT INTO `game_selector_option_products` VALUES ('31','3','6','7');
INSERT INTO `game_selector_option_products` VALUES ('32','3','7','8');
INSERT INTO `game_selector_option_products` VALUES ('33','3','8','9');
INSERT INTO `game_selector_option_products` VALUES ('34','3','9','10');
INSERT INTO `game_selector_option_products` VALUES ('35','3','10','11');
INSERT INTO `game_selector_option_products` VALUES ('36','3','12','35');
INSERT INTO `game_selector_option_products` VALUES ('37','4','1','1');
INSERT INTO `game_selector_option_products` VALUES ('38','4','11','2');
INSERT INTO `game_selector_option_products` VALUES ('39','4','2','3');
INSERT INTO `game_selector_option_products` VALUES ('40','4','3','4');
INSERT INTO `game_selector_option_products` VALUES ('41','4','4','5');
INSERT INTO `game_selector_option_products` VALUES ('42','4','5','6');
INSERT INTO `game_selector_option_products` VALUES ('43','4','6','7');
INSERT INTO `game_selector_option_products` VALUES ('44','4','7','8');
INSERT INTO `game_selector_option_products` VALUES ('45','4','8','9');
INSERT INTO `game_selector_option_products` VALUES ('46','4','9','10');
INSERT INTO `game_selector_option_products` VALUES ('47','4','10','11');
INSERT INTO `game_selector_option_products` VALUES ('48','4','12','35');
INSERT INTO `game_selector_option_products` VALUES ('49','1','52','1');
INSERT INTO `game_selector_option_products` VALUES ('50','1','53','3');
INSERT INTO `game_selector_option_products` VALUES ('51','1','54','4');
INSERT INTO `game_selector_option_products` VALUES ('52','1','55','5');
INSERT INTO `game_selector_option_products` VALUES ('53','1','56','6');
INSERT INTO `game_selector_option_products` VALUES ('54','1','57','7');
INSERT INTO `game_selector_option_products` VALUES ('55','1','58','8');
INSERT INTO `game_selector_option_products` VALUES ('56','1','59','9');
INSERT INTO `game_selector_option_products` VALUES ('57','1','60','10');
INSERT INTO `game_selector_option_products` VALUES ('58','1','61','11');
INSERT INTO `game_selector_option_products` VALUES ('59','1','62','12');
INSERT INTO `game_selector_option_products` VALUES ('60','1','63','13');
INSERT INTO `game_selector_option_products` VALUES ('61','1','64','14');
INSERT INTO `game_selector_option_products` VALUES ('62','1','65','15');
INSERT INTO `game_selector_option_products` VALUES ('63','1','66','16');
INSERT INTO `game_selector_option_products` VALUES ('64','1','67','17');
INSERT INTO `game_selector_option_products` VALUES ('65','1','68','18');
INSERT INTO `game_selector_option_products` VALUES ('66','1','69','19');
INSERT INTO `game_selector_option_products` VALUES ('67','1','70','20');
INSERT INTO `game_selector_option_products` VALUES ('68','1','71','21');
INSERT INTO `game_selector_option_products` VALUES ('69','1','72','22');
INSERT INTO `game_selector_option_products` VALUES ('70','1','73','23');
INSERT INTO `game_selector_option_products` VALUES ('71','1','74','24');
INSERT INTO `game_selector_option_products` VALUES ('72','1','75','25');
INSERT INTO `game_selector_option_products` VALUES ('73','1','76','26');
INSERT INTO `game_selector_option_products` VALUES ('74','1','77','27');
INSERT INTO `game_selector_option_products` VALUES ('75','1','78','28');
INSERT INTO `game_selector_option_products` VALUES ('76','1','79','29');
INSERT INTO `game_selector_option_products` VALUES ('77','1','80','30');
INSERT INTO `game_selector_option_products` VALUES ('78','1','81','31');
INSERT INTO `game_selector_option_products` VALUES ('79','1','82','32');
INSERT INTO `game_selector_option_products` VALUES ('80','1','83','33');
INSERT INTO `game_selector_option_products` VALUES ('81','1','84','34');
INSERT INTO `game_selector_option_products` VALUES ('82','1','85','36');
INSERT INTO `game_selector_option_products` VALUES ('83','1','86','37');
INSERT INTO `game_selector_option_products` VALUES ('84','2','52','1');
INSERT INTO `game_selector_option_products` VALUES ('85','2','53','3');
INSERT INTO `game_selector_option_products` VALUES ('86','2','54','4');
INSERT INTO `game_selector_option_products` VALUES ('87','2','55','5');
INSERT INTO `game_selector_option_products` VALUES ('88','2','56','6');
INSERT INTO `game_selector_option_products` VALUES ('89','2','57','7');
INSERT INTO `game_selector_option_products` VALUES ('90','2','58','8');
INSERT INTO `game_selector_option_products` VALUES ('91','2','59','9');
INSERT INTO `game_selector_option_products` VALUES ('92','2','60','10');
INSERT INTO `game_selector_option_products` VALUES ('93','2','61','11');
INSERT INTO `game_selector_option_products` VALUES ('94','2','62','12');
INSERT INTO `game_selector_option_products` VALUES ('95','2','63','13');
INSERT INTO `game_selector_option_products` VALUES ('96','2','64','14');
INSERT INTO `game_selector_option_products` VALUES ('97','2','65','15');
INSERT INTO `game_selector_option_products` VALUES ('98','2','66','16');
INSERT INTO `game_selector_option_products` VALUES ('99','2','67','17');
INSERT INTO `game_selector_option_products` VALUES ('100','2','68','18');
INSERT INTO `game_selector_option_products` VALUES ('101','2','69','19');
INSERT INTO `game_selector_option_products` VALUES ('102','2','70','20');
INSERT INTO `game_selector_option_products` VALUES ('103','2','71','21');
INSERT INTO `game_selector_option_products` VALUES ('104','2','72','22');
INSERT INTO `game_selector_option_products` VALUES ('105','2','73','23');
INSERT INTO `game_selector_option_products` VALUES ('106','2','74','24');
INSERT INTO `game_selector_option_products` VALUES ('107','2','75','25');
INSERT INTO `game_selector_option_products` VALUES ('108','2','76','26');
INSERT INTO `game_selector_option_products` VALUES ('109','2','77','27');
INSERT INTO `game_selector_option_products` VALUES ('110','2','78','28');
INSERT INTO `game_selector_option_products` VALUES ('111','2','79','29');
INSERT INTO `game_selector_option_products` VALUES ('112','2','80','30');
INSERT INTO `game_selector_option_products` VALUES ('113','2','81','31');
INSERT INTO `game_selector_option_products` VALUES ('114','2','82','32');
INSERT INTO `game_selector_option_products` VALUES ('115','2','83','33');
INSERT INTO `game_selector_option_products` VALUES ('116','2','84','34');
INSERT INTO `game_selector_option_products` VALUES ('117','2','85','36');
INSERT INTO `game_selector_option_products` VALUES ('118','2','86','37');
INSERT INTO `game_selector_option_products` VALUES ('119','3','52','1');
INSERT INTO `game_selector_option_products` VALUES ('120','3','53','3');
INSERT INTO `game_selector_option_products` VALUES ('121','3','54','4');
INSERT INTO `game_selector_option_products` VALUES ('122','3','55','5');
INSERT INTO `game_selector_option_products` VALUES ('123','3','56','6');
INSERT INTO `game_selector_option_products` VALUES ('124','3','57','7');
INSERT INTO `game_selector_option_products` VALUES ('125','3','58','8');
INSERT INTO `game_selector_option_products` VALUES ('126','3','59','9');
INSERT INTO `game_selector_option_products` VALUES ('127','3','60','10');
INSERT INTO `game_selector_option_products` VALUES ('128','3','61','11');
INSERT INTO `game_selector_option_products` VALUES ('129','3','62','12');
INSERT INTO `game_selector_option_products` VALUES ('130','3','63','13');
INSERT INTO `game_selector_option_products` VALUES ('131','3','64','14');
INSERT INTO `game_selector_option_products` VALUES ('132','3','65','15');
INSERT INTO `game_selector_option_products` VALUES ('133','3','66','16');
INSERT INTO `game_selector_option_products` VALUES ('134','3','67','17');
INSERT INTO `game_selector_option_products` VALUES ('135','3','68','18');
INSERT INTO `game_selector_option_products` VALUES ('136','3','69','19');
INSERT INTO `game_selector_option_products` VALUES ('137','3','70','20');
INSERT INTO `game_selector_option_products` VALUES ('138','3','71','21');
INSERT INTO `game_selector_option_products` VALUES ('139','3','72','22');
INSERT INTO `game_selector_option_products` VALUES ('140','3','73','23');
INSERT INTO `game_selector_option_products` VALUES ('141','3','74','24');
INSERT INTO `game_selector_option_products` VALUES ('142','3','75','25');
INSERT INTO `game_selector_option_products` VALUES ('143','3','76','26');
INSERT INTO `game_selector_option_products` VALUES ('144','3','77','27');
INSERT INTO `game_selector_option_products` VALUES ('145','3','78','28');
INSERT INTO `game_selector_option_products` VALUES ('146','3','79','29');
INSERT INTO `game_selector_option_products` VALUES ('147','3','80','30');
INSERT INTO `game_selector_option_products` VALUES ('148','3','81','31');
INSERT INTO `game_selector_option_products` VALUES ('149','3','82','32');
INSERT INTO `game_selector_option_products` VALUES ('150','3','83','33');
INSERT INTO `game_selector_option_products` VALUES ('151','3','84','34');
INSERT INTO `game_selector_option_products` VALUES ('152','3','85','36');
INSERT INTO `game_selector_option_products` VALUES ('153','3','86','37');
INSERT INTO `game_selector_option_products` VALUES ('154','4','52','1');
INSERT INTO `game_selector_option_products` VALUES ('155','4','53','3');
INSERT INTO `game_selector_option_products` VALUES ('156','4','54','4');
INSERT INTO `game_selector_option_products` VALUES ('157','4','55','5');
INSERT INTO `game_selector_option_products` VALUES ('158','4','56','6');
INSERT INTO `game_selector_option_products` VALUES ('159','4','57','7');
INSERT INTO `game_selector_option_products` VALUES ('160','4','58','8');
INSERT INTO `game_selector_option_products` VALUES ('161','4','59','9');
INSERT INTO `game_selector_option_products` VALUES ('162','4','60','10');
INSERT INTO `game_selector_option_products` VALUES ('163','4','61','11');
INSERT INTO `game_selector_option_products` VALUES ('164','4','62','12');
INSERT INTO `game_selector_option_products` VALUES ('165','4','63','13');
INSERT INTO `game_selector_option_products` VALUES ('166','4','64','14');
INSERT INTO `game_selector_option_products` VALUES ('167','4','65','15');
INSERT INTO `game_selector_option_products` VALUES ('168','4','66','16');
INSERT INTO `game_selector_option_products` VALUES ('169','4','67','17');
INSERT INTO `game_selector_option_products` VALUES ('170','4','68','18');
INSERT INTO `game_selector_option_products` VALUES ('171','4','69','19');
INSERT INTO `game_selector_option_products` VALUES ('172','4','70','20');
INSERT INTO `game_selector_option_products` VALUES ('173','4','71','21');
INSERT INTO `game_selector_option_products` VALUES ('174','4','72','22');
INSERT INTO `game_selector_option_products` VALUES ('175','4','73','23');
INSERT INTO `game_selector_option_products` VALUES ('176','4','74','24');
INSERT INTO `game_selector_option_products` VALUES ('177','4','75','25');
INSERT INTO `game_selector_option_products` VALUES ('178','4','76','26');
INSERT INTO `game_selector_option_products` VALUES ('179','4','77','27');
INSERT INTO `game_selector_option_products` VALUES ('180','4','78','28');
INSERT INTO `game_selector_option_products` VALUES ('181','4','79','29');
INSERT INTO `game_selector_option_products` VALUES ('182','4','80','30');
INSERT INTO `game_selector_option_products` VALUES ('183','4','81','31');
INSERT INTO `game_selector_option_products` VALUES ('184','4','82','32');
INSERT INTO `game_selector_option_products` VALUES ('185','4','83','33');
INSERT INTO `game_selector_option_products` VALUES ('186','4','84','34');
INSERT INTO `game_selector_option_products` VALUES ('187','4','85','36');
INSERT INTO `game_selector_option_products` VALUES ('188','4','86','37');

DROP TABLE IF EXISTS `game_selector_options`;
CREATE TABLE `game_selector_options` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `config_id` int(11) NOT NULL,
  `parent_option_id` int(11) DEFAULT NULL,
  `region_id` int(11) DEFAULT NULL,
  `region_custom_text` varchar(150) DEFAULT NULL,
  `custom_icon` varchar(255) DEFAULT NULL,
  `name` varchar(200) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `badge_text` varchar(50) DEFAULT NULL,
  `badge_color` varchar(30) DEFAULT 'hot',
  `custom_instructions` text DEFAULT NULL,
  `player_fields_override` longtext DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `parent_option_id` (`parent_option_id`),
  KEY `region_id` (`region_id`),
  KEY `idx_config_active` (`config_id`,`is_active`),
  KEY `idx_sort` (`sort_order`),
  CONSTRAINT `game_selector_options_ibfk_1` FOREIGN KEY (`config_id`) REFERENCES `game_selector_configs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `game_selector_options_ibfk_2` FOREIGN KEY (`parent_option_id`) REFERENCES `game_selector_options` (`id`) ON DELETE SET NULL,
  CONSTRAINT `game_selector_options_ibfk_3` FOREIGN KEY (`region_id`) REFERENCES `regions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `game_selector_options` VALUES ('1','1',NULL,'1','Bangladesh Region','BD','Mobile Legends Bangladesh','bangladesh',NULL,'assets/uploads/games/mobile-legends-logo-1788889170.jpg',NULL,'hot',NULL,NULL,'1','0','1','2026-10-02 19:49:31');
INSERT INTO `game_selector_options` VALUES ('2','1',NULL,'2','Global Region','GLOBAL','Mobile Legends | Cheap','cheap-login',NULL,'assets/uploads/games/mobile-legends-logo-1788889170.jpg','LOGIN','danger',NULL,NULL,'1','0','2','2026-10-02 19:49:31');
INSERT INTO `game_selector_options` VALUES ('3','1',NULL,'3','Indonesia Region','ID','Mobile Legends Indonesia','indonesia',NULL,'assets/uploads/games/mobile-legends-logo-1788889170.jpg',NULL,'primary',NULL,NULL,'1','0','3','2026-10-02 19:49:31');
INSERT INTO `game_selector_options` VALUES ('4','1',NULL,'4','Malaysia Region','MY','Mobile Legends Malaysia','malaysia',NULL,'assets/uploads/games/mobile-legends-logo-1788889170.jpg',NULL,'success',NULL,NULL,'1','0','4','2026-10-02 19:49:31');

DROP TABLE IF EXISTS `games`;
CREATE TABLE `games` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `banner` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `seo_title` varchar(200) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `publisher` varchar(100) DEFAULT NULL,
  `platform` varchar(100) DEFAULT NULL,
  `requires_server_id` tinyint(1) DEFAULT 0,
  `server_label` varchar(50) DEFAULT 'Server ID',
  `player_label` varchar(50) DEFAULT 'User ID',
  `field_schema` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`field_schema`)),
  `validation_provider` varchar(50) DEFAULT 'none',
  `is_featured` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `category` varchar(50) DEFAULT 'game',
  `topup_type` enum('player_id','login','voucher','subscription') DEFAULT 'player_id',
  `parent_id` int(11) DEFAULT 0,
  `is_stock_out` tinyint(1) DEFAULT 0,
  `badge_text` varchar(50) DEFAULT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `banner_1` varchar(255) DEFAULT NULL,
  `banner_2` varchar(255) DEFAULT NULL,
  `banner_3` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_games_active_sort` (`is_active`,`sort_order`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `games` VALUES ('1','Mobile Legends','mobile-legends','assets/uploads/games/mobile-legends-logo-1788889170.jpg',NULL,'The most popular mobile MOBA game. Buy MLBB Diamonds instantly.',NULL,NULL,'','','1','Zone ID','User ID',NULL,'none','1','1','1','game','player_id','0','0','HOT','assets/uploads/games/mobile-legends-cover-1788889025.webp',NULL,NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `games` VALUES ('2','Free Fire','free-fire','assets/uploads/games/free-fire-logo-1789995184.jpg',NULL,'Buy Free Fire Diamonds instantly at the best price.',NULL,NULL,'Garena','Mobile','0','','User ID',NULL,'none','1','1','2','game','player_id','0','0','',NULL,NULL,NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `games` VALUES ('3','PUBG Mobile','pubg-mobile',NULL,NULL,'Buy PUBG UC instantly. Secure and fast delivery.',NULL,NULL,'Tencent','Mobile','0','','User ID',NULL,'none','1','1','3','game','player_id','0','0',NULL,NULL,NULL,NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `games` VALUES ('4','Valorant','valorant',NULL,NULL,'Buy Valorant Points for skins and battle passes.',NULL,NULL,'Riot Games','PC','0','','Riot ID',NULL,'none','1','0','4','game','player_id','0','0','',NULL,NULL,NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `games` VALUES ('5','Genshin Impact','genshin-impact',NULL,NULL,'Buy Genesis Crystals for your Genshin account.',NULL,NULL,'HoYoverse','Mobile/PC','0','','UID',NULL,'none','1','0','5','game','player_id','0','0','',NULL,NULL,NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `games` VALUES ('6','Call of Duty Mobile','codm',NULL,NULL,'Buy COD Points for CODM instantly.',NULL,NULL,'Activision','Mobile','0','','User ID',NULL,'none','0','0','6','game','player_id','0','0','',NULL,NULL,NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `games` VALUES ('7','Honor of Kings','honor-of-kings',NULL,NULL,'Buy Tokens for Honor of Kings game.',NULL,NULL,'TiMi Studio','Mobile','0','','User ID',NULL,'none','0','1','7','game','player_id','0','0',NULL,NULL,NULL,NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `games` VALUES ('8','Clash of Clans','clash-of-clans',NULL,NULL,'Buy Gems for Clash of Clans.',NULL,NULL,'Supercell','Mobile','0','','Player Tag',NULL,'none','0','0','8','game','player_id','0','0','',NULL,NULL,NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `games` VALUES ('9','Clash Royale','clash-royale',NULL,NULL,'Buy Gems for Clash Royale.',NULL,NULL,'Supercell','Mobile','0','','Player Tag',NULL,'none','0','0','9','game','player_id','0','0','',NULL,NULL,NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `games` VALUES ('10','MLBB Weekly Diamond Pass','mlbb-weekly-pass',NULL,NULL,'Get 210 Diamonds + 70 Starlight Points over 7 days directly to your MLBB account.',NULL,NULL,'Moonton','Mobile','1','Zone ID','User ID',NULL,'none','1','0','10','subscription','subscription','0','0','BEST VALUE',NULL,NULL,NULL,NULL,'2026-09-07 14:42:48');
INSERT INTO `games` VALUES ('11','Free Fire Weekly Membership','ff-weekly-membership',NULL,NULL,'Claim instant 100 Diamonds plus 350 Diamonds over 7 days in Garena Free Fire.',NULL,NULL,'Garena','Mobile','0','','Player ID',NULL,'none','1','0','11','subscription','subscription','0','0','WEEKLY',NULL,NULL,NULL,NULL,'2026-09-07 14:42:48');
INSERT INTO `games` VALUES ('12','Discord Nitro','discord-nitro',NULL,NULL,'Unlock custom emojis, HD video streaming, 500MB uploads, and server boosts.',NULL,NULL,'Discord Inc.','PC','0','','Discord User / Email',NULL,'none','1','0','12','subscription','subscription','0','0','VIP',NULL,NULL,NULL,NULL,'2026-09-07 14:42:48');
INSERT INTO `games` VALUES ('13','Google Play Gift Card (US/Global)','google-play-gift-card',NULL,NULL,'Redeemable code for Google Play Store balance to purchase apps, games, and in-app items.',NULL,NULL,'Google LLC','Mobile','0','','Receiver Email',NULL,'none','1','0','20','voucher','voucher','0','0','GIFT CODE',NULL,NULL,NULL,NULL,'2026-09-07 14:42:48');
INSERT INTO `games` VALUES ('14','Steam Wallet Code (Global)','steam-wallet-code',NULL,NULL,'Instant Steam digital gift cards for Steam balance to buy any PC game.',NULL,NULL,'Valve','PC','0','','Email / Steam ID',NULL,'none','1','0','21','voucher','voucher','0','0','INSTANT PIN',NULL,NULL,NULL,NULL,'2026-09-07 14:42:48');
INSERT INTO `games` VALUES ('15','Razer Gold PIN (Global)','razer-gold-pin',NULL,NULL,'The unified virtual credits for gamers worldwide. Usable on 42,000+ games.',NULL,NULL,'Razer Inc.','PC','0','','Email Delivery',NULL,'none','1','0','22','voucher','voucher','0','0','UNIVERSAL',NULL,NULL,NULL,NULL,'2026-09-07 14:42:48');
INSERT INTO `games` VALUES ('16','PUBG Mobile (Login Method)','pubg-mobile-login',NULL,NULL,'Fast and discounted login top-up for PUBG Mobile. Requires Twitter/FB or Login details.',NULL,NULL,'Tencent','Mobile','0','Login Password','Login Account',NULL,'none','1','0','30','game','login','0','0','CHEAPEST',NULL,NULL,NULL,NULL,'2026-09-07 14:42:48');
INSERT INTO `games` VALUES ('17','Genshin Impact (Login Top-Up)','genshin-impact-login',NULL,NULL,'Login based Genesis Crystals & Welkin Moon top-up with discounted bulk packages.',NULL,NULL,'HoYoverse','Mobile/PC','0','Server / Password','HoYoverse Account',NULL,'none','1','0','31','game','login','0','0','SPECIAL',NULL,NULL,NULL,NULL,'2026-09-07 14:42:48');
INSERT INTO `games` VALUES ('18','League of Legends (PC)','league-of-legends',NULL,NULL,'Buy Riot Points (RP) for League of Legends champions and exclusive skins.',NULL,NULL,'Riot Games','PC','0','','Riot ID & Tagline',NULL,'none','1','0','40','game','player_id','0','0','PC ONLY',NULL,NULL,NULL,NULL,'2026-09-07 14:42:48');

DROP TABLE IF EXISTS `gift_card_vouchers`;
CREATE TABLE `gift_card_vouchers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `card_code` varchar(255) NOT NULL,
  `card_pin` varchar(255) DEFAULT NULL,
  `cost_price` decimal(10,2) DEFAULT 0.00,
  `status` enum('available','allocated','redeemed','expired') DEFAULT 'available',
  `order_id` int(11) DEFAULT NULL,
  `assigned_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_prod_status` (`product_id`,`status`),
  CONSTRAINT `gift_card_vouchers_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `homepage_sections`;
CREATE TABLE `homepage_sections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `section_key` varchar(100) NOT NULL,
  `title` varchar(150) NOT NULL,
  `nav_title` varchar(80) DEFAULT NULL,
  `subtitle` varchar(255) DEFAULT NULL,
  `icon` varchar(100) DEFAULT 'bi-grid-fill',
  `section_type` enum('games','products','mixed') DEFAULT 'games',
  `selection_mode` enum('auto','manual') DEFAULT 'auto',
  `auto_filter_category` varchar(50) DEFAULT 'all',
  `auto_filter_type` varchar(50) DEFAULT 'featured',
  `max_items` int(11) DEFAULT 12,
  `desktop_cols` int(11) DEFAULT 6,
  `mobile_cols` int(11) DEFAULT 2,
  `show_view_more` tinyint(1) DEFAULT 1,
  `view_more_url` varchar(255) DEFAULT '/shop.php',
  `view_more_text` varchar(100) DEFAULT 'View All',
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `section_key` (`section_key`),
  KEY `idx_section_active_sort` (`is_active`,`sort_order`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `homepage_sections` VALUES ('1','popular-games','Popular Games','Popular','','bi-fire','games','manual','all','featured','12','6','2','1','/shop.php','More','1','1','2026-09-07 14:42:48','2026-10-17 01:28:42');
INSERT INTO `homepage_sections` VALUES ('2','topup-player-id','Top Up via Player ID / User ID','Player ID','No password required — direct, lightning-fast top-up using your Game ID','bi-person-badge-fill','games','auto','game','player_id','12','6','2','1','/shop.php','View More','2','1','2026-09-07 14:42:48','2026-10-17 01:28:41');
INSERT INTO `homepage_sections` VALUES ('3','topup-login-method','Top Up via Login Method','Login Method','Secure account top-up services with guaranteed delivery and big savings','bi-shield-lock-fill','games','auto','all','login_method','12','6','2','1','/shop.php','View More','3','0','2026-09-07 14:42:48','2026-09-08 23:15:22');
INSERT INTO `homepage_sections` VALUES ('5','subscriptions','Subscriptions','Subscriptions','','bi-gift-fill','games','auto','subscription','by_category','12','6','2','1','/shop.php','View All Subscriptions','4','0','2026-09-07 14:42:48','2026-10-17 00:59:58');

DROP TABLE IF EXISTS `notification_templates`;
CREATE TABLE `notification_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_key` varchar(100) NOT NULL,
  `title_template` varchar(255) NOT NULL,
  `body_template` text NOT NULL,
  `channels` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`channels`)),
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `event_key` (`event_key`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `notification_templates` VALUES ('1','order_created','Order #{order_number} Received','Hello {customer_name}, your order for {product_name} has been received and is being processed.','[\"web\",\"email\"]','1','2026-09-27 07:54:05');
INSERT INTO `notification_templates` VALUES ('2','payment_received','Payment Verified for #{order_number}','Your payment of {amount} has been successfully verified.','[\"web\",\"email\"]','1','2026-09-27 07:54:05');
INSERT INTO `notification_templates` VALUES ('3','order_completed','Order #{order_number} Completed!','Great news! Your top-up for {product_name} (Player ID: {player_id}) has been delivered successfully.','[\"web\",\"email\",\"sms\"]','1','2026-09-27 07:54:05');
INSERT INTO `notification_templates` VALUES ('4','order_failed','Order #{order_number} Failed','Your order #{order_number} could not be completed. Reason: {reason}. Our support team is investigating.','[\"web\",\"email\"]','1','2026-09-27 07:54:05');
INSERT INTO `notification_templates` VALUES ('5','refund_processed','Refund of {amount} Approved','A refund of {amount} has been credited to your account for Order #{order_number}.','[\"web\",\"email\"]','1','2026-09-27 07:54:05');
INSERT INTO `notification_templates` VALUES ('6','wallet_deposit','Wallet Deposit of {amount} Successful','Your wallet has been credited with {amount}. Current balance: {new_balance}.','[\"web\",\"email\"]','1','2026-09-27 07:54:05');

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(50) DEFAULT 'info',
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_read` (`user_id`,`is_read`),
  KEY `idx_admin_read` (`admin_id`,`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `order_status_history`;
CREATE TABLE `order_status_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `status_from` varchar(50) NOT NULL,
  `status_to` varchar(50) NOT NULL,
  `note` text DEFAULT NULL,
  `changed_by_admin_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_order_id` (`order_id`),
  CONSTRAINT `order_status_history_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_number` varchar(20) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `reseller_id` int(11) DEFAULT NULL,
  `product_id` int(11) NOT NULL,
  `product_variation_id` int(11) DEFAULT NULL,
  `game_id` int(11) NOT NULL,
  `region_id` int(11) DEFAULT NULL,
  `selector_option_id` int(11) DEFAULT NULL,
  `player_id` varchar(100) NOT NULL,
  `server_id` varchar(100) DEFAULT NULL,
  `dynamic_fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`dynamic_fields`)),
  `player_ign` varchar(100) DEFAULT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `customer_email` varchar(100) DEFAULT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL,
  `reseller_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cost_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_price` decimal(10,2) NOT NULL,
  `profit` decimal(10,2) NOT NULL DEFAULT 0.00,
  `coupon_code` varchar(50) DEFAULT NULL,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `payment_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_method` enum('bkash','nagad','rocket','wallet','stripe') DEFAULT 'bkash',
  `payment_number` varchar(50) DEFAULT NULL,
  `payment_trx` varchar(100) DEFAULT NULL,
  `payment_proof` varchar(255) DEFAULT NULL,
  `payment_status` enum('pending','paid','failed','refunded') DEFAULT 'pending',
  `order_status` enum('pending','payment_pending','paid','processing','completed','failed','cancelled','refunded','on_hold') DEFAULT 'pending',
  `delivery_status` varchar(50) DEFAULT 'pending',
  `order_source` enum('web','api','reseller','manual') DEFAULT 'web',
  `webhook_status` enum('none','pending','delivered','failed') DEFAULT 'none',
  `admin_note` text DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `supplier_order_id` varchar(100) DEFAULT NULL,
  `supplier_response` text DEFAULT NULL,
  `idempotency_key` varchar(64) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_number` (`order_number`),
  UNIQUE KEY `idempotency_key` (`idempotency_key`),
  KEY `product_id` (`product_id`),
  KEY `game_id` (`game_id`),
  KEY `user_id` (`user_id`),
  KEY `idx_order_status` (`order_status`),
  KEY `idx_payment_status` (`payment_status`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`),
  CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `orders` VALUES ('32','RX6ACE242D8221B13',NULL,NULL,'53',NULL,'1','1','1','692628915','8763',NULL,NULL,'Guest','','','1','490.00','0.00','0.00','490.00','0.00',NULL,'0.00','0.00','bkash','01303689321','dh4dhd64h4tht656',NULL,'failed','cancelled','pending','web','none','',NULL,NULL,NULL,NULL,'::1','2026-10-13 18:29:33','2026-10-14 17:25:52',NULL);

DROP TABLE IF EXISTS `payment_methods`;
CREATE TABLE `payment_methods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `method_key` varchar(50) NOT NULL,
  `method_label` varchar(100) NOT NULL,
  `account_number` varchar(100) DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `method_key` (`method_key`),
  KEY `idx_pm_active_sort` (`is_active`,`sort_order`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `payment_methods` VALUES ('5','upay','Upay','','assets/img/payments/1789742125_upay main.jpg','1','4','2026-08-30 06:53:53');
INSERT INTO `payment_methods` VALUES ('6','rocket','Rocket','','assets/img/payments/1788051245_Rocket_mobile_banking_logo.svg','1','3','2026-08-30 06:54:05');
INSERT INTO `payment_methods` VALUES ('7','nagad','Nagad','','assets/img/payments/1788051257_nagad@logotyp.us.svg','1','2','2026-08-30 06:54:17');
INSERT INTO `payment_methods` VALUES ('8','bkash','bKash','','assets/img/payments/1788051280_bkash@logotyp.us.svg','1','1','2026-08-30 06:54:40');

DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_number` varchar(30) NOT NULL,
  `reference_type` enum('order','deposit','reseller') NOT NULL,
  `reference_id` varchar(100) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `gateway_name` varchar(50) NOT NULL,
  `account_number` varchar(100) DEFAULT NULL,
  `transaction_ref` varchar(150) DEFAULT NULL,
  `gateway_trx_id` varchar(150) DEFAULT NULL,
  `gateway_response` text DEFAULT NULL,
  `status` enum('pending','verified','failed','refunded','cancelled') DEFAULT 'pending',
  `verified_by_admin_id` int(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_number` (`payment_number`),
  KEY `idx_ref` (`reference_type`,`reference_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_trx_ref` (`transaction_ref`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `price_history`;
CREATE TABLE `price_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `old_cost` decimal(10,2) DEFAULT 0.00,
  `new_cost` decimal(10,2) DEFAULT 0.00,
  `old_price` decimal(10,2) DEFAULT 0.00,
  `new_price` decimal(10,2) DEFAULT 0.00,
  `old_reseller_price` decimal(10,2) DEFAULT 0.00,
  `new_reseller_price` decimal(10,2) DEFAULT 0.00,
  `changed_by_admin_id` int(11) DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_product_id` (`product_id`),
  CONSTRAINT `price_history_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `product_packages`;
;

INSERT INTO `product_packages` VALUES ('1','1','86 Diamonds',NULL,'90.00','0.00','manual',NULL,NULL,'100.00','BDT','86','1','0','1','0',NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `product_packages` VALUES ('2','1','172 Diamonds',NULL,'175.00','0.00','manual',NULL,NULL,'195.00','BDT','172','2','0','1','0',NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `product_packages` VALUES ('3','1','257 Diamonds',NULL,'260.00','0.00','manual',NULL,NULL,'285.00','BDT','257','3','0','1','0',NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `product_packages` VALUES ('4','1','344 Diamonds',NULL,'345.00','0.00','manual',NULL,NULL,'380.00','BDT','344','4','1','1','0',NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `product_packages` VALUES ('5','1','429 Diamonds',NULL,'430.00','0.00','manual',NULL,NULL,'475.00','BDT','429','5','0','1','0',NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `product_packages` VALUES ('6','1','514 Diamonds',NULL,'515.00','0.00','manual',NULL,NULL,'565.00','BDT','514','6','0','1','0',NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `product_packages` VALUES ('7','1','706 Diamonds',NULL,'700.00','0.00','manual',NULL,NULL,'770.00','BDT','706','7','1','1','0',NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `product_packages` VALUES ('8','1','878 Diamonds',NULL,'870.00','0.00','manual',NULL,NULL,'960.00','BDT','878','8','0','1','0',NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `product_packages` VALUES ('9','1','1412 Diamonds',NULL,'1380.00','0.00','manual',NULL,NULL,'1520.00','BDT','1412','9','1','1','0',NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `product_packages` VALUES ('10','1','2195 Diamonds',NULL,'2140.00','0.00','manual',NULL,NULL,'2360.00','BDT','2195','10','0','1','0',NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `product_packages` VALUES ('11','1','Weekly Diamond Pass',NULL,'195.00','0.00','manual',NULL,'','210.00','BDT','0','2','1','1','0','assets/img/packages/pass-box.svg','NOTE','2026-06-30 01:07:40');
INSERT INTO `product_packages` VALUES ('12','1','Twilight Pass',NULL,'390.00','0.00','manual',NULL,NULL,'430.00','BDT','0','35','0','1','0','assets/img/packages/pass-ticket.svg','','2026-06-30 01:07:40');
INSERT INTO `product_packages` VALUES ('13','2','100 Diamonds',NULL,'80.00','0.00','manual',NULL,NULL,'90.00','BDT','100','1','0','1','0',NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `product_packages` VALUES ('14','2','200 Diamonds',NULL,'155.00','0.00','manual',NULL,NULL,'175.00','BDT','200','2','0','1','0',NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `product_packages` VALUES ('15','2','500 Diamonds',NULL,'380.00','0.00','manual',NULL,NULL,'420.00','BDT','500','3','1','1','0',NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `product_packages` VALUES ('16','2','1000 Diamonds',NULL,'750.00','0.00','manual',NULL,NULL,'830.00','BDT','1000','4','1','1','0',NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `product_packages` VALUES ('17','2','2000 Diamonds',NULL,'1480.00','0.00','manual',NULL,NULL,'1640.00','BDT','2000','5','0','1','0',NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `product_packages` VALUES ('18','2','Weekly Membership',NULL,'115.00','0.00','manual',NULL,NULL,'130.00','BDT','0','6','0','1','0',NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `product_packages` VALUES ('19','3','60 UC',NULL,'85.00','0.00','manual',NULL,NULL,'95.00','BDT','60','1','0','1','0',NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `product_packages` VALUES ('20','3','325 UC',NULL,'440.00','0.00','manual',NULL,NULL,'490.00','BDT','325','2','1','1','0',NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `product_packages` VALUES ('21','3','660 UC',NULL,'870.00','0.00','manual',NULL,NULL,'965.00','BDT','660','3','0','1','0',NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `product_packages` VALUES ('22','3','1800 UC',NULL,'2350.00','0.00','manual',NULL,NULL,'2600.00','BDT','1800','4','1','1','0',NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `product_packages` VALUES ('23','3','3850 UC',NULL,'4900.00','0.00','manual',NULL,NULL,'5400.00','BDT','3850','5','0','1','0',NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `product_packages` VALUES ('24','4','475 VP',NULL,'420.00','0.00','manual',NULL,NULL,'470.00','BDT','475','1','0','1','0',NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `product_packages` VALUES ('25','4','1000 VP',NULL,'850.00','0.00','manual',NULL,NULL,'940.00','BDT','1000','2','1','1','0',NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `product_packages` VALUES ('26','4','2050 VP',NULL,'1700.00','0.00','manual',NULL,NULL,'1880.00','BDT','2050','3','0','1','0',NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `product_packages` VALUES ('27','4','3650 VP',NULL,'2900.00','0.00','manual',NULL,NULL,'3200.00','BDT','3650','4','1','1','0',NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `product_packages` VALUES ('28','4','5350 VP',NULL,'4200.00','0.00','manual',NULL,NULL,'4650.00','BDT','5350','5','0','1','0',NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `product_packages` VALUES ('29','5','60 Genesis Crystals',NULL,'80.00','0.00','manual',NULL,NULL,'90.00','BDT','60','1','0','1','0',NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `product_packages` VALUES ('30','5','330 Genesis Crystals',NULL,'420.00','0.00','manual',NULL,NULL,'470.00','BDT','330','2','1','1','0',NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `product_packages` VALUES ('31','5','1090 Genesis Crystals',NULL,'1350.00','0.00','manual',NULL,NULL,'1500.00','BDT','1090','3','0','1','0',NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `product_packages` VALUES ('32','5','2240 Genesis Crystals',NULL,'2700.00','0.00','manual',NULL,NULL,'3000.00','BDT','2240','4','1','1','0',NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `product_packages` VALUES ('33','5','3880 Genesis Crystals',NULL,'4600.00','0.00','manual',NULL,NULL,'5100.00','BDT','3880','5','0','1','0',NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `product_packages` VALUES ('34','10','Basic Pack',NULL,'120.00','0.00','manual',NULL,NULL,'140.00','BDT',NULL,'1','1','1','0',NULL,NULL,'2026-09-07 14:42:48');
INSERT INTO `product_packages` VALUES ('35','10','Premium Pack',NULL,'550.00','0.00','manual',NULL,NULL,'620.00','BDT',NULL,'2','1','1','0',NULL,NULL,'2026-09-07 14:42:48');
INSERT INTO `product_packages` VALUES ('36','11','Basic Pack',NULL,'120.00','0.00','manual',NULL,NULL,'140.00','BDT',NULL,'1','1','1','0',NULL,NULL,'2026-09-07 14:42:48');
INSERT INTO `product_packages` VALUES ('37','11','Premium Pack',NULL,'550.00','0.00','manual',NULL,NULL,'620.00','BDT',NULL,'2','1','1','0',NULL,NULL,'2026-09-07 14:42:48');
INSERT INTO `product_packages` VALUES ('38','12','Basic Pack',NULL,'120.00','0.00','manual',NULL,NULL,'140.00','BDT',NULL,'1','1','1','0',NULL,NULL,'2026-09-07 14:42:48');
INSERT INTO `product_packages` VALUES ('39','12','Premium Pack',NULL,'550.00','0.00','manual',NULL,NULL,'620.00','BDT',NULL,'2','1','1','0',NULL,NULL,'2026-09-07 14:42:48');
INSERT INTO `product_packages` VALUES ('40','13','Basic Pack',NULL,'120.00','0.00','manual',NULL,NULL,'140.00','BDT',NULL,'1','1','1','0',NULL,NULL,'2026-09-07 14:42:48');
INSERT INTO `product_packages` VALUES ('41','13','Premium Pack',NULL,'550.00','0.00','manual',NULL,NULL,'620.00','BDT',NULL,'2','1','1','0',NULL,NULL,'2026-09-07 14:42:48');
INSERT INTO `product_packages` VALUES ('42','14','Basic Pack',NULL,'120.00','0.00','manual',NULL,NULL,'140.00','BDT',NULL,'1','1','1','0',NULL,NULL,'2026-09-07 14:42:48');
INSERT INTO `product_packages` VALUES ('43','14','Premium Pack',NULL,'550.00','0.00','manual',NULL,NULL,'620.00','BDT',NULL,'2','1','1','0',NULL,NULL,'2026-09-07 14:42:48');
INSERT INTO `product_packages` VALUES ('44','15','Basic Pack',NULL,'120.00','0.00','manual',NULL,NULL,'140.00','BDT',NULL,'1','1','1','0',NULL,NULL,'2026-09-07 14:42:48');
INSERT INTO `product_packages` VALUES ('45','15','Premium Pack',NULL,'550.00','0.00','manual',NULL,NULL,'620.00','BDT',NULL,'2','1','1','0',NULL,NULL,'2026-09-07 14:42:48');
INSERT INTO `product_packages` VALUES ('46','16','Basic Pack',NULL,'120.00','0.00','manual',NULL,NULL,'140.00','BDT',NULL,'1','1','1','0',NULL,NULL,'2026-09-07 14:42:48');
INSERT INTO `product_packages` VALUES ('47','16','Premium Pack',NULL,'550.00','0.00','manual',NULL,NULL,'620.00','BDT',NULL,'2','1','1','0',NULL,NULL,'2026-09-07 14:42:48');
INSERT INTO `product_packages` VALUES ('48','17','Basic Pack',NULL,'120.00','0.00','manual',NULL,NULL,'140.00','BDT',NULL,'1','1','1','0',NULL,NULL,'2026-09-07 14:42:48');
INSERT INTO `product_packages` VALUES ('49','17','Premium Pack',NULL,'550.00','0.00','manual',NULL,NULL,'620.00','BDT',NULL,'2','1','1','0',NULL,NULL,'2026-09-07 14:42:48');
INSERT INTO `product_packages` VALUES ('50','18','Basic Pack',NULL,'120.00','0.00','manual',NULL,NULL,'140.00','BDT',NULL,'1','1','1','0',NULL,NULL,'2026-09-07 14:42:48');
INSERT INTO `product_packages` VALUES ('51','18','Premium Pack',NULL,'550.00','0.00','manual',NULL,NULL,'620.00','BDT',NULL,'2','1','1','0',NULL,NULL,'2026-09-07 14:42:48');
INSERT INTO `product_packages` VALUES ('52','1','Super Value Pass',NULL,'98.00','0.00','manual',NULL,NULL,'105.00','BDT','0','1','1','1','0','assets/img/packages/pass-ticket.svg','NOTE','2026-10-09 23:03:07');
INSERT INTO `product_packages` VALUES ('53','1','275 Diamonds',NULL,'490.00','0.00','manual',NULL,NULL,'500.00','BDT','275','3','1','1','0','assets/img/packages/diamond-cluster.svg','','2026-10-09 23:03:07');
INSERT INTO `product_packages` VALUES ('54','1','2x Weekly+55 Diamonds',NULL,'493.00','0.00','manual',NULL,NULL,'505.00','BDT','55','4','1','1','0','assets/img/packages/pass-box.svg','NOTE','2026-10-09 23:03:07');
INSERT INTO `product_packages` VALUES ('55','1','5 Diamond',NULL,'13.00','0.00','manual',NULL,NULL,'15.00','BDT','5','5','0','1','0','assets/img/packages/diamond-single.svg','','2026-10-09 23:03:07');
INSERT INTO `product_packages` VALUES ('56','1','10+1 Diamond',NULL,'28.00','0.00','manual',NULL,NULL,'30.00','BDT','11','6','0','1','0','assets/img/packages/diamond-single.svg','NOTE','2026-10-09 23:03:07');
INSERT INTO `product_packages` VALUES ('57','1','13+1 Diamond',NULL,'32.00','0.00','manual',NULL,NULL,'35.00','BDT','14','7','0','1','0','assets/img/packages/diamond-single.svg','','2026-10-09 23:03:07');
INSERT INTO `product_packages` VALUES ('58','1','26+2 Diamond',NULL,'64.00','0.00','manual',NULL,NULL,'69.00','BDT','28','8','0','1','0','assets/img/packages/diamond-double.svg','','2026-10-09 23:03:07');
INSERT INTO `product_packages` VALUES ('59','1','50+5 Diamond',NULL,'103.00','0.00','manual',NULL,NULL,'105.00','BDT','55','9','0','1','0','assets/img/packages/diamond-double.svg','','2026-10-09 23:03:07');
INSERT INTO `product_packages` VALUES ('60','1','78+8 Diamond',NULL,'160.00','0.00','manual',NULL,NULL,'165.00','BDT','86','10','0','1','0','assets/img/packages/diamond-double.svg','','2026-10-09 23:03:07');
INSERT INTO `product_packages` VALUES ('61','1','100+10 Diamond',NULL,'205.00','0.00','manual',NULL,NULL,'215.00','BDT','110','11','0','1','0','assets/img/packages/diamond-cluster.svg','','2026-10-09 23:03:07');
INSERT INTO `product_packages` VALUES ('62','1','156+16 Diamond',NULL,'320.00','0.00','manual',NULL,NULL,'330.00','BDT','172','12','0','1','0','assets/img/packages/diamond-cluster.svg','','2026-10-09 23:03:07');
INSERT INTO `product_packages` VALUES ('63','1','234+23 Diamond',NULL,'480.00','0.00','manual',NULL,NULL,'495.00','BDT','257','13','0','1','0','assets/img/packages/diamond-cluster.svg','','2026-10-09 23:03:07');
INSERT INTO `product_packages` VALUES ('64','1','296+33 Diamond',NULL,'590.00','0.00','manual',NULL,NULL,'610.00','BDT','329','14','1','1','0','assets/img/packages/diamond-pile.svg','','2026-10-09 23:03:07');
INSERT INTO `product_packages` VALUES ('65','1','374+37 Diamond',NULL,'745.00','0.00','manual',NULL,NULL,'765.00','BDT','411','15','0','1','0','assets/img/packages/diamond-pile.svg','','2026-10-09 23:03:07');
INSERT INTO `product_packages` VALUES ('66','1','514+51 Diamond',NULL,'1010.00','0.00','manual',NULL,NULL,'1045.00','BDT','565','16','0','1','0','assets/img/packages/diamond-pile.svg','','2026-10-09 23:03:07');
INSERT INTO `product_packages` VALUES ('67','1','590+59 Diamond',NULL,'1165.00','0.00','manual',NULL,NULL,'1200.00','BDT','649','17','0','1','0','assets/img/packages/diamond-pile.svg','','2026-10-09 23:03:07');
INSERT INTO `product_packages` VALUES ('68','1','706+70 Diamond',NULL,'1390.00','0.00','manual',NULL,NULL,'1435.00','BDT','776','18','0','1','0','assets/img/packages/diamond-pile.svg','','2026-10-09 23:03:07');
INSERT INTO `product_packages` VALUES ('69','1','841+84 Diamond',NULL,'1650.00','0.00','manual',NULL,NULL,'1710.00','BDT','925','19','0','1','0','assets/img/packages/diamond-pile.svg','','2026-10-09 23:03:07');
INSERT INTO `product_packages` VALUES ('70','1','920+92 Diamond',NULL,'1810.00','0.00','manual',NULL,NULL,'1870.00','BDT','1012','20','0','1','0','assets/img/packages/diamond-chest.svg','','2026-10-09 23:03:07');
INSERT INTO `product_packages` VALUES ('71','1','1026+102 Diamond',NULL,'2015.00','0.00','manual',NULL,NULL,'2085.00','BDT','1128','21','0','1','0','assets/img/packages/diamond-chest.svg','','2026-10-09 23:03:07');
INSERT INTO `product_packages` VALUES ('72','1','1159+115 Diamond',NULL,'2280.00','0.00','manual',NULL,NULL,'2355.00','BDT','1274','22','0','1','0','assets/img/packages/diamond-chest.svg','','2026-10-09 23:03:07');
INSERT INTO `product_packages` VALUES ('73','1','1414+141 Diamond',NULL,'2780.00','0.00','manual',NULL,NULL,'2870.00','BDT','1555','23','0','1','0','assets/img/packages/diamond-chest.svg','','2026-10-09 23:03:07');
INSERT INTO `product_packages` VALUES ('74','1','1755+210 Diamond',NULL,'3500.00','0.00','manual',NULL,NULL,'3600.00','BDT','1965','24','0','1','0','assets/img/packages/diamond-chest.svg','','2026-10-09 23:03:07');
INSERT INTO `product_packages` VALUES ('75','1','2011+201 Diamond',NULL,'4000.00','0.00','manual',NULL,NULL,'4100.00','BDT','2212','25','0','1','0','assets/img/packages/diamond-chest.svg','','2026-10-09 23:03:07');
INSERT INTO `product_packages` VALUES ('76','1','3060+306 Diamond',NULL,'6200.00','0.00','manual',NULL,NULL,'6400.00','BDT','3366','26','0','1','0','assets/img/packages/diamond-chest.svg','','2026-10-09 23:03:07');
INSERT INTO `product_packages` VALUES ('77','1','3720+372 Diamond',NULL,'7550.00','0.00','manual',NULL,NULL,'7800.00','BDT','4092','27','0','1','0','assets/img/packages/diamond-chest.svg','','2026-10-09 23:03:07');
INSERT INTO `product_packages` VALUES ('78','1','4018+401 Diamond',NULL,'8100.00','0.00','manual',NULL,NULL,'8400.00','BDT','4419','28','0','1','0','assets/img/packages/diamond-chest.svg','','2026-10-09 23:03:07');
INSERT INTO `product_packages` VALUES ('79','1','5012+1002 Diamond',NULL,'10700.00','0.00','manual',NULL,NULL,'11200.00','BDT','6014','29','0','1','0','assets/img/packages/diamond-chest.svg','','2026-10-09 23:03:07');
INSERT INTO `product_packages` VALUES ('80','1','7290+1458 Diamond',NULL,'15700.00','0.00','manual',NULL,NULL,'16300.00','BDT','8748','30','0','1','0','assets/img/packages/diamond-chest.svg','','2026-10-09 23:03:07');
INSERT INTO `product_packages` VALUES ('81','1','1st Recharge - 50 Diamonds',NULL,'95.00','0.00','manual',NULL,NULL,'110.00','BDT','50','31','1','1','0','assets/img/packages/diamond-double.svg','SPECIAL','2026-10-09 23:03:07');
INSERT INTO `product_packages` VALUES ('82','1','1st Recharge Season Pack',NULL,'190.00','0.00','manual',NULL,NULL,'220.00','BDT','100','32','0','1','0','assets/img/packages/diamond-cluster.svg','BONUS','2026-10-09 23:03:07');
INSERT INTO `product_packages` VALUES ('83','1','Recharge Phase 1 Event Box',NULL,'195.00','0.00','manual',NULL,NULL,'210.00','BDT','100','33','0','1','0','assets/img/packages/pass-box.svg','EVENT','2026-10-09 23:03:07');
INSERT INTO `product_packages` VALUES ('84','1','Recharge Phase 2 Premium Box',NULL,'470.00','0.00','manual',NULL,NULL,'500.00','BDT','250','34','0','1','0','assets/img/packages/pass-box.svg','EVENT','2026-10-09 23:03:07');
INSERT INTO `product_packages` VALUES ('85','1','Starlight Plus Card',NULL,'750.00','0.00','manual',NULL,NULL,'820.00','BDT','0','36','1','1','0','assets/img/packages/pass-ticket.svg','VIP','2026-10-09 23:03:07');
INSERT INTO `product_packages` VALUES ('86','1','Collector Grand Bundle',NULL,'1850.00','0.00','manual',NULL,NULL,'2000.00','BDT','1000','37','0','1','0','assets/img/packages/diamond-chest.svg','EXCLUSIVE','2026-10-09 23:03:07');

DROP TABLE IF EXISTS `product_variations`;
CREATE TABLE `product_variations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `cost_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `price` decimal(10,2) NOT NULL,
  `reseller_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `supplier_variation_code` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_product_id` (`product_id`),
  CONSTRAINT `product_variations_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `game_id` int(11) NOT NULL,
  `region_id` int(11) DEFAULT NULL,
  `name` varchar(200) NOT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `product_type` varchar(50) DEFAULT 'diamonds',
  `category` varchar(50) NOT NULL DEFAULT 'diamonds',
  `amount` decimal(10,2) NOT NULL,
  `reseller_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cost_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `delivery_type` enum('instant_api','manual','voucher') DEFAULT 'manual',
  `validation_required` tinyint(1) DEFAULT 0,
  `validation_type` varchar(50) DEFAULT 'none',
  `supplier_id` int(11) DEFAULT NULL,
  `supplier_product_id` varchar(100) DEFAULT NULL,
  `original_price` decimal(10,2) DEFAULT NULL,
  `currency` varchar(10) DEFAULT 'BDT',
  `diamonds` int(11) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_popular` tinyint(1) DEFAULT 0,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `is_stock_out` tinyint(1) NOT NULL DEFAULT 0,
  `is_archived` tinyint(1) DEFAULT 0,
  `image` varchar(255) DEFAULT NULL,
  `badge` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `game_id` (`game_id`),
  KEY `idx_products_popular_active` (`is_popular`,`is_active`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=87 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `products` VALUES ('1','1',NULL,'86 Diamonds',NULL,NULL,'diamonds','diamonds','90.00','0.00','0.00','manual','0','none',NULL,NULL,'100.00','BDT','86','1','0','0','1','0','0',NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `products` VALUES ('2','1',NULL,'172 Diamonds',NULL,NULL,'diamonds','diamonds','175.00','0.00','0.00','manual','0','none',NULL,NULL,'195.00','BDT','172','2','0','0','1','0','0',NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `products` VALUES ('3','1',NULL,'257 Diamonds',NULL,NULL,'diamonds','diamonds','260.00','0.00','0.00','manual','0','none',NULL,NULL,'285.00','BDT','257','3','0','0','1','0','0',NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `products` VALUES ('4','1',NULL,'344 Diamonds',NULL,NULL,'diamonds','diamonds','345.00','0.00','0.00','manual','0','none',NULL,NULL,'380.00','BDT','344','4','1','0','1','0','0',NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `products` VALUES ('5','1',NULL,'429 Diamonds',NULL,NULL,'diamonds','diamonds','430.00','0.00','0.00','manual','0','none',NULL,NULL,'475.00','BDT','429','5','0','0','1','0','0',NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `products` VALUES ('6','1',NULL,'514 Diamonds',NULL,NULL,'diamonds','diamonds','515.00','0.00','0.00','manual','0','none',NULL,NULL,'565.00','BDT','514','6','0','0','1','0','0',NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `products` VALUES ('7','1',NULL,'706 Diamonds',NULL,NULL,'diamonds','diamonds','700.00','0.00','0.00','manual','0','none',NULL,NULL,'770.00','BDT','706','7','1','0','1','0','0',NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `products` VALUES ('8','1',NULL,'878 Diamonds',NULL,NULL,'diamonds','diamonds','870.00','0.00','0.00','manual','0','none',NULL,NULL,'960.00','BDT','878','8','0','0','1','0','0',NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `products` VALUES ('9','1',NULL,'1412 Diamonds',NULL,NULL,'diamonds','diamonds','1380.00','0.00','0.00','manual','0','none',NULL,NULL,'1520.00','BDT','1412','9','1','0','1','0','0',NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `products` VALUES ('10','1',NULL,'2195 Diamonds',NULL,NULL,'diamonds','diamonds','2140.00','0.00','0.00','manual','0','none',NULL,NULL,'2360.00','BDT','2195','10','0','0','1','0','0',NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `products` VALUES ('11','1',NULL,'Weekly Diamond Pass',NULL,NULL,'diamonds','pass','195.00','0.00','0.00','manual','0','none',NULL,'','210.00','BDT','0','2','1','1','1','0','0','assets/img/packages/pass-box.svg','NOTE','2026-06-30 01:07:40');
INSERT INTO `products` VALUES ('12','1',NULL,'Twilight Pass',NULL,NULL,'diamonds','pass','390.00','0.00','0.00','manual','0','none',NULL,NULL,'430.00','BDT','0','35','0','0','1','0','0','assets/img/packages/pass-ticket.svg','','2026-06-30 01:07:40');
INSERT INTO `products` VALUES ('13','2',NULL,'100 Diamonds',NULL,NULL,'diamonds','diamonds','80.00','0.00','0.00','manual','0','none',NULL,NULL,'90.00','BDT','100','1','0','0','1','0','0',NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `products` VALUES ('14','2',NULL,'200 Diamonds',NULL,NULL,'diamonds','diamonds','155.00','0.00','0.00','manual','0','none',NULL,NULL,'175.00','BDT','200','2','0','0','1','0','0',NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `products` VALUES ('15','2',NULL,'500 Diamonds',NULL,NULL,'diamonds','diamonds','380.00','0.00','0.00','manual','0','none',NULL,NULL,'420.00','BDT','500','3','1','0','1','0','0',NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `products` VALUES ('16','2',NULL,'1000 Diamonds',NULL,NULL,'diamonds','diamonds','750.00','0.00','0.00','manual','0','none',NULL,NULL,'830.00','BDT','1000','4','1','0','1','0','0',NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `products` VALUES ('17','2',NULL,'2000 Diamonds',NULL,NULL,'diamonds','diamonds','1480.00','0.00','0.00','manual','0','none',NULL,NULL,'1640.00','BDT','2000','5','0','0','1','0','0',NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `products` VALUES ('18','2',NULL,'Weekly Membership',NULL,NULL,'diamonds','diamonds','115.00','0.00','0.00','manual','0','none',NULL,NULL,'130.00','BDT','0','6','0','0','1','0','0',NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `products` VALUES ('19','3',NULL,'60 UC',NULL,NULL,'diamonds','diamonds','85.00','0.00','0.00','manual','0','none',NULL,NULL,'95.00','BDT','60','1','0','0','1','0','0',NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `products` VALUES ('20','3',NULL,'325 UC',NULL,NULL,'diamonds','diamonds','440.00','0.00','0.00','manual','0','none',NULL,NULL,'490.00','BDT','325','2','1','0','1','0','0',NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `products` VALUES ('21','3',NULL,'660 UC',NULL,NULL,'diamonds','diamonds','870.00','0.00','0.00','manual','0','none',NULL,NULL,'965.00','BDT','660','3','0','0','1','0','0',NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `products` VALUES ('22','3',NULL,'1800 UC',NULL,NULL,'diamonds','diamonds','2350.00','0.00','0.00','manual','0','none',NULL,NULL,'2600.00','BDT','1800','4','1','0','1','0','0',NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `products` VALUES ('23','3',NULL,'3850 UC',NULL,NULL,'diamonds','diamonds','4900.00','0.00','0.00','manual','0','none',NULL,NULL,'5400.00','BDT','3850','5','0','0','1','0','0',NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `products` VALUES ('24','4',NULL,'475 VP',NULL,NULL,'diamonds','diamonds','420.00','0.00','0.00','manual','0','none',NULL,NULL,'470.00','BDT','475','1','0','0','1','0','0',NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `products` VALUES ('25','4',NULL,'1000 VP',NULL,NULL,'diamonds','diamonds','850.00','0.00','0.00','manual','0','none',NULL,NULL,'940.00','BDT','1000','2','1','0','1','0','0',NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `products` VALUES ('26','4',NULL,'2050 VP',NULL,NULL,'diamonds','diamonds','1700.00','0.00','0.00','manual','0','none',NULL,NULL,'1880.00','BDT','2050','3','0','0','1','0','0',NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `products` VALUES ('27','4',NULL,'3650 VP',NULL,NULL,'diamonds','diamonds','2900.00','0.00','0.00','manual','0','none',NULL,NULL,'3200.00','BDT','3650','4','1','0','1','0','0',NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `products` VALUES ('28','4',NULL,'5350 VP',NULL,NULL,'diamonds','diamonds','4200.00','0.00','0.00','manual','0','none',NULL,NULL,'4650.00','BDT','5350','5','0','0','1','0','0',NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `products` VALUES ('29','5',NULL,'60 Genesis Crystals',NULL,NULL,'diamonds','diamonds','80.00','0.00','0.00','manual','0','none',NULL,NULL,'90.00','BDT','60','1','0','0','1','0','0',NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `products` VALUES ('30','5',NULL,'330 Genesis Crystals',NULL,NULL,'diamonds','diamonds','420.00','0.00','0.00','manual','0','none',NULL,NULL,'470.00','BDT','330','2','1','0','1','0','0',NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `products` VALUES ('31','5',NULL,'1090 Genesis Crystals',NULL,NULL,'diamonds','diamonds','1350.00','0.00','0.00','manual','0','none',NULL,NULL,'1500.00','BDT','1090','3','0','0','1','0','0',NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `products` VALUES ('32','5',NULL,'2240 Genesis Crystals',NULL,NULL,'diamonds','diamonds','2700.00','0.00','0.00','manual','0','none',NULL,NULL,'3000.00','BDT','2240','4','1','0','1','0','0',NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `products` VALUES ('33','5',NULL,'3880 Genesis Crystals',NULL,NULL,'diamonds','diamonds','4600.00','0.00','0.00','manual','0','none',NULL,NULL,'5100.00','BDT','3880','5','0','0','1','0','0',NULL,NULL,'2026-06-30 01:07:40');
INSERT INTO `products` VALUES ('34','10',NULL,'Basic Pack',NULL,NULL,'diamonds','diamonds','120.00','0.00','0.00','manual','0','none',NULL,NULL,'140.00','BDT',NULL,'1','1','0','1','0','0',NULL,NULL,'2026-09-07 14:42:48');
INSERT INTO `products` VALUES ('35','10',NULL,'Premium Pack',NULL,NULL,'diamonds','diamonds','550.00','0.00','0.00','manual','0','none',NULL,NULL,'620.00','BDT',NULL,'2','1','0','1','0','0',NULL,NULL,'2026-09-07 14:42:48');
INSERT INTO `products` VALUES ('36','11',NULL,'Basic Pack',NULL,NULL,'diamonds','diamonds','120.00','0.00','0.00','manual','0','none',NULL,NULL,'140.00','BDT',NULL,'1','1','0','1','0','0',NULL,NULL,'2026-09-07 14:42:48');
INSERT INTO `products` VALUES ('37','11',NULL,'Premium Pack',NULL,NULL,'diamonds','diamonds','550.00','0.00','0.00','manual','0','none',NULL,NULL,'620.00','BDT',NULL,'2','1','0','1','0','0',NULL,NULL,'2026-09-07 14:42:48');
INSERT INTO `products` VALUES ('38','12',NULL,'Basic Pack',NULL,NULL,'diamonds','diamonds','120.00','0.00','0.00','manual','0','none',NULL,NULL,'140.00','BDT',NULL,'1','1','0','1','0','0',NULL,NULL,'2026-09-07 14:42:48');
INSERT INTO `products` VALUES ('39','12',NULL,'Premium Pack',NULL,NULL,'diamonds','diamonds','550.00','0.00','0.00','manual','0','none',NULL,NULL,'620.00','BDT',NULL,'2','1','0','1','0','0',NULL,NULL,'2026-09-07 14:42:48');
INSERT INTO `products` VALUES ('40','13',NULL,'Basic Pack',NULL,NULL,'diamonds','diamonds','120.00','0.00','0.00','manual','0','none',NULL,NULL,'140.00','BDT',NULL,'1','1','0','1','0','0',NULL,NULL,'2026-09-07 14:42:48');
INSERT INTO `products` VALUES ('41','13',NULL,'Premium Pack',NULL,NULL,'diamonds','diamonds','550.00','0.00','0.00','manual','0','none',NULL,NULL,'620.00','BDT',NULL,'2','1','0','1','0','0',NULL,NULL,'2026-09-07 14:42:48');
INSERT INTO `products` VALUES ('42','14',NULL,'Basic Pack',NULL,NULL,'diamonds','diamonds','120.00','0.00','0.00','manual','0','none',NULL,NULL,'140.00','BDT',NULL,'1','1','0','1','0','0',NULL,NULL,'2026-09-07 14:42:48');
INSERT INTO `products` VALUES ('43','14',NULL,'Premium Pack',NULL,NULL,'diamonds','diamonds','550.00','0.00','0.00','manual','0','none',NULL,NULL,'620.00','BDT',NULL,'2','1','0','1','0','0',NULL,NULL,'2026-09-07 14:42:48');
INSERT INTO `products` VALUES ('44','15',NULL,'Basic Pack',NULL,NULL,'diamonds','diamonds','120.00','0.00','0.00','manual','0','none',NULL,NULL,'140.00','BDT',NULL,'1','1','0','1','0','0',NULL,NULL,'2026-09-07 14:42:48');
INSERT INTO `products` VALUES ('45','15',NULL,'Premium Pack',NULL,NULL,'diamonds','diamonds','550.00','0.00','0.00','manual','0','none',NULL,NULL,'620.00','BDT',NULL,'2','1','0','1','0','0',NULL,NULL,'2026-09-07 14:42:48');
INSERT INTO `products` VALUES ('46','16',NULL,'Basic Pack',NULL,NULL,'diamonds','diamonds','120.00','0.00','0.00','manual','0','none',NULL,NULL,'140.00','BDT',NULL,'1','1','0','1','0','0',NULL,NULL,'2026-09-07 14:42:48');
INSERT INTO `products` VALUES ('47','16',NULL,'Premium Pack',NULL,NULL,'diamonds','diamonds','550.00','0.00','0.00','manual','0','none',NULL,NULL,'620.00','BDT',NULL,'2','1','0','1','0','0',NULL,NULL,'2026-09-07 14:42:48');
INSERT INTO `products` VALUES ('48','17',NULL,'Basic Pack',NULL,NULL,'diamonds','diamonds','120.00','0.00','0.00','manual','0','none',NULL,NULL,'140.00','BDT',NULL,'1','1','0','1','0','0',NULL,NULL,'2026-09-07 14:42:48');
INSERT INTO `products` VALUES ('49','17',NULL,'Premium Pack',NULL,NULL,'diamonds','diamonds','550.00','0.00','0.00','manual','0','none',NULL,NULL,'620.00','BDT',NULL,'2','1','0','1','0','0',NULL,NULL,'2026-09-07 14:42:48');
INSERT INTO `products` VALUES ('50','18',NULL,'Basic Pack',NULL,NULL,'diamonds','diamonds','120.00','0.00','0.00','manual','0','none',NULL,NULL,'140.00','BDT',NULL,'1','1','0','1','0','0',NULL,NULL,'2026-09-07 14:42:48');
INSERT INTO `products` VALUES ('51','18',NULL,'Premium Pack',NULL,NULL,'diamonds','diamonds','550.00','0.00','0.00','manual','0','none',NULL,NULL,'620.00','BDT',NULL,'2','1','0','1','0','0',NULL,NULL,'2026-09-07 14:42:48');
INSERT INTO `products` VALUES ('52','1',NULL,'Super Value Pass',NULL,NULL,'diamonds','pass','98.00','0.00','0.00','manual','0','none',NULL,NULL,'105.00','BDT','0','1','1','1','1','0','0','assets/img/packages/pass-ticket.svg','NOTE','2026-10-09 23:03:07');
INSERT INTO `products` VALUES ('53','1',NULL,'275 Diamonds',NULL,NULL,'diamonds','diamonds','490.00','0.00','0.00','manual','0','none',NULL,NULL,'500.00','BDT','275','3','1','1','1','0','0','assets/img/packages/diamond-cluster.svg','','2026-10-09 23:03:07');
INSERT INTO `products` VALUES ('54','1',NULL,'2x Weekly+55 Diamonds',NULL,NULL,'diamonds','pass','493.00','0.00','0.00','manual','0','none',NULL,NULL,'505.00','BDT','55','4','1','1','1','0','0','assets/img/packages/pass-box.svg','NOTE','2026-10-09 23:03:07');
INSERT INTO `products` VALUES ('55','1',NULL,'5 Diamond',NULL,NULL,'diamonds','diamonds','13.00','0.00','0.00','manual','0','none',NULL,NULL,'15.00','BDT','5','5','0','0','1','1','0','assets/img/packages/diamond-single.svg','','2026-10-09 23:03:07');
INSERT INTO `products` VALUES ('56','1',NULL,'10+1 Diamond',NULL,NULL,'diamonds','diamonds','28.00','0.00','0.00','manual','0','none',NULL,NULL,'30.00','BDT','11','6','0','0','1','0','0','assets/img/packages/diamond-single.svg','NOTE','2026-10-09 23:03:07');
INSERT INTO `products` VALUES ('57','1',NULL,'13+1 Diamond',NULL,NULL,'diamonds','diamonds','32.00','0.00','0.00','manual','0','none',NULL,NULL,'35.00','BDT','14','7','0','0','1','0','0','assets/img/packages/diamond-single.svg','','2026-10-09 23:03:07');
INSERT INTO `products` VALUES ('58','1',NULL,'26+2 Diamond',NULL,NULL,'diamonds','diamonds','64.00','0.00','0.00','manual','0','none',NULL,NULL,'69.00','BDT','28','8','0','0','1','0','0','assets/img/packages/diamond-double.svg','','2026-10-09 23:03:07');
INSERT INTO `products` VALUES ('59','1',NULL,'50+5 Diamond',NULL,NULL,'diamonds','diamonds','103.00','0.00','0.00','manual','0','none',NULL,NULL,'105.00','BDT','55','9','0','0','1','0','0','assets/img/packages/diamond-double.svg','','2026-10-09 23:03:07');
INSERT INTO `products` VALUES ('60','1',NULL,'78+8 Diamond',NULL,NULL,'diamonds','diamonds','160.00','0.00','0.00','manual','0','none',NULL,NULL,'165.00','BDT','86','10','0','0','1','0','0','assets/img/packages/diamond-double.svg','','2026-10-09 23:03:07');
INSERT INTO `products` VALUES ('61','1',NULL,'100+10 Diamond',NULL,NULL,'diamonds','diamonds','205.00','0.00','0.00','manual','0','none',NULL,NULL,'215.00','BDT','110','11','0','0','1','0','0','assets/img/packages/diamond-cluster.svg','','2026-10-09 23:03:07');
INSERT INTO `products` VALUES ('62','1',NULL,'156+16 Diamond',NULL,NULL,'diamonds','diamonds','320.00','0.00','0.00','manual','0','none',NULL,NULL,'330.00','BDT','172','12','0','0','1','0','0','assets/img/packages/diamond-cluster.svg','','2026-10-09 23:03:07');
INSERT INTO `products` VALUES ('63','1',NULL,'234+23 Diamond',NULL,NULL,'diamonds','diamonds','480.00','0.00','0.00','manual','0','none',NULL,NULL,'495.00','BDT','257','13','0','0','1','0','0','assets/img/packages/diamond-cluster.svg','','2026-10-09 23:03:07');
INSERT INTO `products` VALUES ('64','1',NULL,'296+33 Diamond',NULL,NULL,'diamonds','diamonds','590.00','0.00','0.00','manual','0','none',NULL,NULL,'610.00','BDT','329','14','1','0','1','0','0','assets/img/packages/diamond-pile.svg','','2026-10-09 23:03:07');
INSERT INTO `products` VALUES ('65','1',NULL,'374+37 Diamond',NULL,NULL,'diamonds','diamonds','745.00','0.00','0.00','manual','0','none',NULL,NULL,'765.00','BDT','411','15','0','0','1','0','0','assets/img/packages/diamond-pile.svg','','2026-10-09 23:03:07');
INSERT INTO `products` VALUES ('66','1',NULL,'514+51 Diamond',NULL,NULL,'diamonds','diamonds','1010.00','0.00','0.00','manual','0','none',NULL,NULL,'1045.00','BDT','565','16','0','0','1','0','0','assets/img/packages/diamond-pile.svg','','2026-10-09 23:03:07');
INSERT INTO `products` VALUES ('67','1',NULL,'590+59 Diamond',NULL,NULL,'diamonds','diamonds','1165.00','0.00','0.00','manual','0','none',NULL,NULL,'1200.00','BDT','649','17','0','0','1','0','0','assets/img/packages/diamond-pile.svg','','2026-10-09 23:03:07');
INSERT INTO `products` VALUES ('68','1',NULL,'706+70 Diamond',NULL,NULL,'diamonds','diamonds','1390.00','0.00','0.00','manual','0','none',NULL,NULL,'1435.00','BDT','776','18','0','0','1','0','0','assets/img/packages/diamond-pile.svg','','2026-10-09 23:03:07');
INSERT INTO `products` VALUES ('69','1',NULL,'841+84 Diamond',NULL,NULL,'diamonds','diamonds','1650.00','0.00','0.00','manual','0','none',NULL,NULL,'1710.00','BDT','925','19','0','0','1','0','0','assets/img/packages/diamond-pile.svg','','2026-10-09 23:03:07');
INSERT INTO `products` VALUES ('70','1',NULL,'920+92 Diamond',NULL,NULL,'diamonds','diamonds','1810.00','0.00','0.00','manual','0','none',NULL,NULL,'1870.00','BDT','1012','20','0','0','1','0','0','assets/img/packages/diamond-chest.svg','','2026-10-09 23:03:07');
INSERT INTO `products` VALUES ('71','1',NULL,'1026+102 Diamond',NULL,NULL,'diamonds','diamonds','2015.00','0.00','0.00','manual','0','none',NULL,NULL,'2085.00','BDT','1128','21','0','0','1','0','0','assets/img/packages/diamond-chest.svg','','2026-10-09 23:03:07');
INSERT INTO `products` VALUES ('72','1',NULL,'1159+115 Diamond',NULL,NULL,'diamonds','diamonds','2280.00','0.00','0.00','manual','0','none',NULL,NULL,'2355.00','BDT','1274','22','0','0','1','0','0','assets/img/packages/diamond-chest.svg','','2026-10-09 23:03:07');
INSERT INTO `products` VALUES ('73','1',NULL,'1414+141 Diamond',NULL,NULL,'diamonds','diamonds','2780.00','0.00','0.00','manual','0','none',NULL,NULL,'2870.00','BDT','1555','23','0','0','1','0','0','assets/img/packages/diamond-chest.svg','','2026-10-09 23:03:07');
INSERT INTO `products` VALUES ('74','1',NULL,'1755+210 Diamond',NULL,NULL,'diamonds','diamonds','3500.00','0.00','0.00','manual','0','none',NULL,NULL,'3600.00','BDT','1965','24','0','0','1','0','0','assets/img/packages/diamond-chest.svg','','2026-10-09 23:03:07');
INSERT INTO `products` VALUES ('75','1',NULL,'2011+201 Diamond',NULL,NULL,'diamonds','diamonds','4000.00','0.00','0.00','manual','0','none',NULL,NULL,'4100.00','BDT','2212','25','0','0','1','0','0','assets/img/packages/diamond-chest.svg','','2026-10-09 23:03:07');
INSERT INTO `products` VALUES ('76','1',NULL,'3060+306 Diamond',NULL,NULL,'diamonds','diamonds','6200.00','0.00','0.00','manual','0','none',NULL,NULL,'6400.00','BDT','3366','26','0','0','1','0','0','assets/img/packages/diamond-chest.svg','','2026-10-09 23:03:07');
INSERT INTO `products` VALUES ('77','1',NULL,'3720+372 Diamond',NULL,NULL,'diamonds','diamonds','7550.00','0.00','0.00','manual','0','none',NULL,NULL,'7800.00','BDT','4092','27','0','0','1','0','0','assets/img/packages/diamond-chest.svg','','2026-10-09 23:03:07');
INSERT INTO `products` VALUES ('78','1',NULL,'4018+401 Diamond',NULL,NULL,'diamonds','diamonds','8100.00','0.00','0.00','manual','0','none',NULL,NULL,'8400.00','BDT','4419','28','0','0','1','0','0','assets/img/packages/diamond-chest.svg','','2026-10-09 23:03:07');
INSERT INTO `products` VALUES ('79','1',NULL,'5012+1002 Diamond',NULL,NULL,'diamonds','diamonds','10700.00','0.00','0.00','manual','0','none',NULL,NULL,'11200.00','BDT','6014','29','0','0','1','0','0','assets/img/packages/diamond-chest.svg','','2026-10-09 23:03:07');
INSERT INTO `products` VALUES ('80','1',NULL,'7290+1458 Diamond',NULL,NULL,'diamonds','diamonds','15700.00','0.00','0.00','manual','0','none',NULL,NULL,'16300.00','BDT','8748','30','0','0','1','0','0','assets/img/packages/diamond-chest.svg','','2026-10-09 23:03:07');
INSERT INTO `products` VALUES ('81','1',NULL,'1st Recharge - 50 Diamonds',NULL,NULL,'diamonds','first_recharge','95.00','0.00','0.00','manual','0','none',NULL,NULL,'110.00','BDT','50','31','1','0','1','0','0','assets/img/packages/diamond-double.svg','SPECIAL','2026-10-09 23:03:07');
INSERT INTO `products` VALUES ('82','1',NULL,'1st Recharge Season Pack',NULL,NULL,'diamonds','first_recharge','190.00','0.00','0.00','manual','0','none',NULL,NULL,'220.00','BDT','100','32','0','0','1','0','0','assets/img/packages/diamond-cluster.svg','BONUS','2026-10-09 23:03:07');
INSERT INTO `products` VALUES ('83','1',NULL,'Recharge Phase 1 Event Box',NULL,NULL,'diamonds','recharge_phase','195.00','0.00','0.00','manual','0','none',NULL,NULL,'210.00','BDT','100','33','0','0','1','0','0','assets/img/packages/pass-box.svg','EVENT','2026-10-09 23:03:07');
INSERT INTO `products` VALUES ('84','1',NULL,'Recharge Phase 2 Premium Box',NULL,NULL,'diamonds','recharge_phase','470.00','0.00','0.00','manual','0','none',NULL,NULL,'500.00','BDT','250','34','0','0','1','0','0','assets/img/packages/pass-box.svg','EVENT','2026-10-09 23:03:07');
INSERT INTO `products` VALUES ('85','1',NULL,'Starlight Plus Card',NULL,NULL,'diamonds','exclusive','750.00','0.00','0.00','manual','0','none',NULL,NULL,'820.00','BDT','0','36','1','0','1','0','0','assets/img/packages/pass-ticket.svg','VIP','2026-10-09 23:03:07');
INSERT INTO `products` VALUES ('86','1',NULL,'Collector Grand Bundle',NULL,NULL,'diamonds','exclusive','1850.00','0.00','0.00','manual','0','none',NULL,NULL,'2000.00','BDT','1000','37','0','0','1','0','0','assets/img/packages/diamond-chest.svg','EXCLUSIVE','2026-10-09 23:03:07');

DROP TABLE IF EXISTS `promotions`;
CREATE TABLE `promotions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `promo_type` enum('flash_sale','featured','first_order','cashback','festival') DEFAULT 'flash_sale',
  `discount_type` enum('percent','fixed') DEFAULT 'percent',
  `discount_value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `min_order_amount` decimal(10,2) DEFAULT 0.00,
  `max_discount_amount` decimal(10,2) DEFAULT NULL,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `banner_image` varchar(255) DEFAULT NULL,
  `applicable_game_ids` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `refund_requests`;
CREATE TABLE `refund_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `refund_number` varchar(30) NOT NULL,
  `order_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `refund_type` enum('full','partial','wallet','gateway') DEFAULT 'wallet',
  `reason` text NOT NULL,
  `status` enum('pending','approved','rejected','completed') DEFAULT 'pending',
  `admin_note` text DEFAULT NULL,
  `processed_by_admin_id` int(11) DEFAULT NULL,
  `processed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `refund_number` (`refund_number`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `refund_requests_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `regions`;
CREATE TABLE `regions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `code` varchar(20) NOT NULL,
  `currency` varchar(10) DEFAULT 'BDT',
  `currency_symbol` varchar(10) DEFAULT '৳',
  `exchange_rate` decimal(10,4) DEFAULT 1.0000,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `regions` VALUES ('1','Bangladesh','BD','BDT','৳','1.0000','1','1','2026-09-27 07:54:05');
INSERT INTO `regions` VALUES ('2','Global','GLOBAL','USD','$','122.5000','1','2','2026-09-27 07:54:05');
INSERT INTO `regions` VALUES ('3','Indonesia','ID','IDR','Rp','0.0078','1','3','2026-09-27 07:54:05');
INSERT INTO `regions` VALUES ('4','Malaysia','MY','MYR','RM','27.5000','1','4','2026-09-27 07:54:05');
INSERT INTO `regions` VALUES ('5','Singapore','SG','SGD','S$','92.0000','1','5','2026-09-27 07:54:05');
INSERT INTO `regions` VALUES ('6','Philippines','PH','PHP','₱','2.1500','1','6','2026-09-27 07:54:05');

DROP TABLE IF EXISTS `section_items`;
CREATE TABLE `section_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `section_id` int(11) NOT NULL,
  `item_type` enum('game','product') DEFAULT 'game',
  `game_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `custom_title` varchar(150) DEFAULT NULL,
  `custom_badge` varchar(50) DEFAULT NULL,
  `custom_badge_color` varchar(30) DEFAULT 'hot',
  `custom_link` varchar(255) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_section_sort` (`section_id`,`sort_order`),
  KEY `idx_game_id` (`game_id`),
  KEY `idx_product_id` (`product_id`),
  CONSTRAINT `section_items_ibfk_1` FOREIGN KEY (`section_id`) REFERENCES `homepage_sections` (`id`) ON DELETE CASCADE,
  CONSTRAINT `section_items_ibfk_2` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE,
  CONSTRAINT `section_items_ibfk_3` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `section_items` VALUES ('3','1','game','1',NULL,NULL,NULL,'hot',NULL,'1','1','2026-09-22 22:00:38');
INSERT INTO `section_items` VALUES ('5','1','game','2',NULL,NULL,'5%','hot',NULL,'2','1','2026-09-22 22:01:24');

DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=561 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `settings` VALUES ('1','site_name','TopUpers','2026-09-21 00:28:48');
INSERT INTO `settings` VALUES ('2','site_tagline','','2026-10-13 02:12:03');
INSERT INTO `settings` VALUES ('3','site_email','','2026-10-13 02:12:03');
INSERT INTO `settings` VALUES ('4','site_phone','','2026-10-13 02:12:03');
INSERT INTO `settings` VALUES ('5','site_address','','2026-10-13 02:12:03');
INSERT INTO `settings` VALUES ('6','currency','BDT','2026-06-30 01:07:40');
INSERT INTO `settings` VALUES ('7','currency_symbol','৳','2026-06-30 01:07:40');
INSERT INTO `settings` VALUES ('8','bkash_number','','2026-10-13 02:12:03');
INSERT INTO `settings` VALUES ('9','nagad_number','','2026-10-13 02:12:03');
INSERT INTO `settings` VALUES ('10','rocket_number','','2026-10-13 02:12:03');
INSERT INTO `settings` VALUES ('11','maintenance_mode','','2026-10-13 02:12:03');
INSERT INTO `settings` VALUES ('12','allow_registration','1','2026-06-30 01:07:40');
INSERT INTO `settings` VALUES ('13','order_prefix','RX','2026-06-30 01:07:40');
INSERT INTO `settings` VALUES ('14','site_logo','assets/img/logo_1791482115.png','2026-10-08 23:55:15');
INSERT INTO `settings` VALUES ('15','site_loader_active','1','2026-09-18 15:37:57');
INSERT INTO `settings` VALUES ('16','site_loader_style','neon','2026-10-09 23:02:46');
INSERT INTO `settings` VALUES ('21','site_currency','৳','2026-09-21 00:28:48');
INSERT INTO `settings` VALUES ('24','bkash_type','','2026-10-13 02:12:03');
INSERT INTO `settings` VALUES ('25','bkash_instruction','','2026-09-21 00:28:48');
INSERT INTO `settings` VALUES ('27','nagad_type','','2026-10-13 02:12:03');
INSERT INTO `settings` VALUES ('28','nagad_instruction','','2026-09-21 00:28:48');
INSERT INTO `settings` VALUES ('30','rocket_type','','2026-10-13 02:12:03');
INSERT INTO `settings` VALUES ('31','rocket_instruction','','2026-09-21 00:28:48');
INSERT INTO `settings` VALUES ('32','upay_number','','2026-09-21 00:28:48');
INSERT INTO `settings` VALUES ('33','upay_type','','2026-10-13 02:12:03');
INSERT INTO `settings` VALUES ('34','upay_instruction','','2026-09-21 00:28:48');
INSERT INTO `settings` VALUES ('35','min_deposit_amount','','2026-10-13 02:12:03');
INSERT INTO `settings` VALUES ('36','max_deposit_amount','','2026-10-13 02:12:03');
INSERT INTO `settings` VALUES ('37','min_withdrawal_amount','','2026-10-13 02:12:03');
INSERT INTO `settings` VALUES ('38','withdrawal_fee_percent','','2026-10-13 02:12:03');
INSERT INTO `settings` VALUES ('39','coins_rate_bdt','','2026-09-21 00:28:48');
INSERT INTO `settings` VALUES ('41','login_max_attempts','','2026-10-13 02:12:03');
INSERT INTO `settings` VALUES ('42','login_lockout_mins','','2026-10-13 02:12:03');
INSERT INTO `settings` VALUES ('43','session_lifetime_mins','','2026-10-13 02:12:03');
INSERT INTO `settings` VALUES ('44','telegram_bot_token','','2026-09-21 00:28:48');
INSERT INTO `settings` VALUES ('45','telegram_chat_id','','2026-09-21 00:28:48');
INSERT INTO `settings` VALUES ('46','notify_new_order','0','2026-10-13 02:12:03');
INSERT INTO `settings` VALUES ('47','notify_new_deposit','0','2026-10-13 02:12:03');
INSERT INTO `settings` VALUES ('48','notify_new_user','0','2026-10-13 02:12:03');
INSERT INTO `settings` VALUES ('49','footer_about','','2026-09-21 00:28:48');
INSERT INTO `settings` VALUES ('50','meta_keywords','','2026-09-21 00:28:48');
INSERT INTO `settings` VALUES ('51','meta_description','','2026-09-21 00:28:48');
INSERT INTO `settings` VALUES ('91','site_loader_img','assets/img/loaders/1791565329_file_00000000ce84821190b4de3aa580a3f5.png','2026-10-09 23:02:09');
INSERT INTO `settings` VALUES ('96','support_widget_active','0','2026-10-13 02:12:03');
INSERT INTO `settings` VALUES ('97','support_title','','2026-10-13 02:12:03');
INSERT INTO `settings` VALUES ('98','support_subtitle','','2026-10-13 02:12:03');
INSERT INTO `settings` VALUES ('99','support_message','','2026-10-13 02:12:03');
INSERT INTO `settings` VALUES ('100','support_whatsapp','','2026-10-13 02:12:03');
INSERT INTO `settings` VALUES ('101','support_messenger','','2026-10-13 02:12:03');
INSERT INTO `settings` VALUES ('102','support_telegram','','2026-10-13 02:12:03');
INSERT INTO `settings` VALUES ('103','support_email','','2026-10-13 02:12:03');
INSERT INTO `settings` VALUES ('152','currency_switcher_enabled','','2026-10-13 03:29:42');
INSERT INTO `settings` VALUES ('153','exchange_rate_mode','','2026-10-13 03:29:42');
INSERT INTO `settings` VALUES ('154','manual_rate_updated_at','','2026-10-13 03:29:42');
INSERT INTO `settings` VALUES ('155','manual_usd_bdt_rate','','2026-10-13 03:29:42');
INSERT INTO `settings` VALUES ('156','rate_buffer_percent','','2026-10-13 03:29:42');
INSERT INTO `settings` VALUES ('157','rate_max_age_hours','','2026-10-13 03:29:42');
INSERT INTO `settings` VALUES ('158','exchange_rate_api_provider','','2026-10-13 03:29:42');
INSERT INTO `settings` VALUES ('159','binance_active','','2026-10-13 03:29:42');
INSERT INTO `settings` VALUES ('160','binance_pay_id','','2026-10-13 03:29:42');
INSERT INTO `settings` VALUES ('161','binance_instructions','','2026-10-13 03:29:42');
INSERT INTO `settings` VALUES ('550','auto_usd_bdt_rate','123.1360','2026-10-16 10:03:32');
INSERT INTO `settings` VALUES ('551','auto_rate_last_updated','2026-10-16 02:02:31','2026-10-16 10:03:32');
INSERT INTO `settings` VALUES ('552','auto_rate_last_attempt','2026-10-16 06:03:31','2026-10-16 10:03:32');
INSERT INTO `settings` VALUES ('553','auto_rate_status','success','2026-10-16 10:03:32');
INSERT INTO `settings` VALUES ('554','auto_rate_error','','2026-10-16 10:03:32');
INSERT INTO `settings` VALUES ('555','google_login_enabled','0','2026-10-16 22:32:51');
INSERT INTO `settings` VALUES ('556','google_client_id','','2026-10-16 22:32:51');
INSERT INTO `settings` VALUES ('557','google_client_secret','','2026-10-16 22:32:51');

DROP TABLE IF EXISTS `supplier_api_logs`;
CREATE TABLE `supplier_api_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `supplier_id` int(11) DEFAULT NULL,
  `order_id` int(11) DEFAULT NULL,
  `request_id` varchar(100) DEFAULT NULL,
  `endpoint` varchar(255) NOT NULL,
  `http_status` int(11) DEFAULT 0,
  `response_time_ms` int(11) DEFAULT 0,
  `request_payload` text DEFAULT NULL,
  `response_body` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_supplier_id` (`supplier_id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `supplier_product_mappings`;
CREATE TABLE `supplier_product_mappings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `supplier_product_code` varchar(100) NOT NULL,
  `supplier_cost` decimal(10,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `priority` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_prod_sup` (`product_id`,`supplier_id`),
  KEY `supplier_id` (`supplier_id`),
  CONSTRAINT `supplier_product_mappings_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `supplier_product_mappings_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `suppliers`;
CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `api_endpoint` varchar(255) DEFAULT NULL,
  `api_key` varchar(255) DEFAULT NULL,
  `api_secret` varchar(255) DEFAULT NULL,
  `balance` decimal(12,2) DEFAULT 0.00,
  `currency` varchar(10) DEFAULT 'USD',
  `priority` int(11) DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `last_sync_at` datetime DEFAULT NULL,
  `last_error` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `suppliers` VALUES ('1','Smile One Global','smile-one','https://api.smile.one/v1',NULL,NULL,'835.25','USD','1','1','2026-09-22 22:56:02',NULL,'2026-08-31 09:37:26');
INSERT INTO `suppliers` VALUES ('2','Moogold Direct API','moogold','https://api.moogold.com/v1',NULL,NULL,'840.50','USD','2','1',NULL,NULL,'2026-08-31 09:37:26');
INSERT INTO `suppliers` VALUES ('3','Lapakgaming Reseller API','lapakgaming','https://api.lapakgaming.com/v2',NULL,NULL,'450.00','USD','3','1',NULL,NULL,'2026-08-31 09:37:26');

DROP TABLE IF EXISTS `support_tickets`;
CREATE TABLE `support_tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_number` varchar(30) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `order_id` int(11) DEFAULT NULL,
  `category` enum('general','order','payment','refund','topup_missing') DEFAULT 'general',
  `subject` varchar(200) NOT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `status` enum('open','in_progress','waiting_customer','resolved','closed') DEFAULT 'open',
  `assigned_admin_id` int(11) DEFAULT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `customer_email` varchar(100) DEFAULT NULL,
  `customer_phone` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ticket_number` (`ticket_number`),
  KEY `idx_status` (`status`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_ticket_number` (`ticket_number`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `ticket_replies`;
CREATE TABLE `ticket_replies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `is_internal` tinyint(1) DEFAULT 0,
  `attachment` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ticket_id` (`ticket_id`),
  CONSTRAINT `ticket_replies_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT 'default.png',
  `wallet_balance` decimal(10,2) DEFAULT 0.00,
  `coin_balance` decimal(10,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `is_verified` tinyint(1) DEFAULT 0,
  `is_reseller` tinyint(1) DEFAULT 0,
  `reseller_tier` varchar(50) DEFAULT 'standard',
  `reseller_discount_percent` decimal(5,2) DEFAULT 0.00,
  `reset_token` varchar(100) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` VALUES ('1','johnnyislam112','Johnny Islam','johnnyislampro@gmail.com','$2y$12$fejc8il7hYHg99do2fAp1eDMFmQFQw.NL.wDaapQu.ws7K9aDHR8C','01976436732','default.png','0.00','0.00','1','0','0','standard','0.00',NULL,NULL,'2026-08-31 18:38:09','2026-08-31 18:38:09');
INSERT INTO `users` VALUES ('2','testuser','Test Gamer Pro','test@example.com','$2y$10$T4dQAsCuVNPRbvpub.DG8OTCvlAlJgnYJypszUQceVahx6h3FTwyO','01712345678','avatar_2_762dc0dfbd9ba461.jpg','0.00','0.00','1','0','0','standard','0.00',NULL,NULL,'2026-10-09 07:41:11','2026-10-09 13:03:52');

DROP TABLE IF EXISTS `wallet_deposits`;
CREATE TABLE `wallet_deposits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `deposit_number` varchar(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `payment_number` varchar(50) NOT NULL,
  `payment_trx` varchar(100) NOT NULL,
  `status` enum('pending','completed','failed') DEFAULT 'pending',
  `admin_note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `deposit_number` (`deposit_number`),
  UNIQUE KEY `payment_trx` (`payment_trx`),
  KEY `user_id` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `wallet_deposits_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `wallet_transactions`;
CREATE TABLE `wallet_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` enum('deposit','purchase','refund','admin_credit','admin_debit','withdrawal','adjustment') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `balance_before` decimal(10,2) NOT NULL,
  `balance_after` decimal(10,2) NOT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` varchar(100) DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `description` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_type` (`type`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `wallet_transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `webhook_logs`;
CREATE TABLE `webhook_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `webhook_id` int(11) DEFAULT NULL,
  `event` varchar(50) NOT NULL,
  `payload` text NOT NULL,
  `response_code` int(11) DEFAULT 0,
  `response_body` text DEFAULT NULL,
  `retry_count` int(11) DEFAULT 0,
  `status` enum('success','failed','pending') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `webhooks`;
CREATE TABLE `webhooks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `url` varchar(255) NOT NULL,
  `secret` varchar(100) NOT NULL,
  `events` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`events`)),
  `user_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `withdrawals`;
CREATE TABLE `withdrawals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `withdrawal_number` varchar(30) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `fee` decimal(10,2) DEFAULT 0.00,
  `net_amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `account_details` text NOT NULL,
  `status` enum('pending','approved','processing','completed','rejected','cancelled') DEFAULT 'pending',
  `admin_note` text DEFAULT NULL,
  `processed_by_admin_id` int(11) DEFAULT NULL,
  `transaction_ref` varchar(150) DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `withdrawal_number` (`withdrawal_number`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `withdrawals_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS=1;
