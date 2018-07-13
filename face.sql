/*
 Navicat Premium Data Transfer

 Source Server         : localhost
 Source Server Type    : MySQL
 Source Server Version : 100125
 Source Host           : localhost:3306
 Source Schema         : face

 Target Server Type    : MySQL
 Target Server Version : 100125
 File Encoding         : 65001

 Date: 10/07/2018 02:31:28
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for cases
-- ----------------------------
DROP TABLE IF EXISTS `cases`;
CREATE TABLE `cases`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `organizationId` tinyint(4) NOT NULL,
  `userId` int(11) NOT NULL,
  `status` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for faces
-- ----------------------------
DROP TABLE IF EXISTS `faces`;
CREATE TABLE `faces`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `faceToken` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `savedPath` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `facesetId` int(11) NOT NULL,
  `imageId` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `dob` date NOT NULL,
  `faceMatches` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 30 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Records of faces
-- ----------------------------
INSERT INTO `faces` VALUES (17, '947a94c22ade66409779775f7468c9fa', 'org1-1/faceset-1/947a94c22ade66409779775f7468c9fa.png', 11, 'eFHub2fD7Ti924lGTxLo4Q==', 'aa', '2018-07-17', 0, '2018-07-09 16:57:22', '2018-07-09 16:57:22');
INSERT INTO `faces` VALUES (18, '9f647f912b6641d1b508660b90495f5e', 'org1-1/faceset-1/9f647f912b6641d1b508660b90495f5e.png', 11, 'fEAuAWNMWDKlFrg/PRlpmQ==', 'aa', '2018-07-17', 0, '2018-07-09 16:58:28', '2018-07-09 16:58:28');
INSERT INTO `faces` VALUES (19, '7ed396dd2079b3cc4b80bc51d9d507d2', 'org1-1/faceset-1/7ed396dd2079b3cc4b80bc51d9d507d2.png', 11, '/s/UnUmdUh2kTB7D1xQnng==', 'aa', '2018-07-17', 0, '2018-07-09 16:59:03', '2018-07-09 16:59:03');
INSERT INTO `faces` VALUES (20, '3b5f23f065b3c83c76afe2350628e2b1', 'org2-3/faceset-1/3b5f23f065b3c83c76afe2350628e2b1.png', 12, 'fEAuAWNMWDKlFrg/PRlpmQ==', 'g', '2018-07-24', 0, '2018-07-09 17:01:55', '2018-07-09 17:01:55');
INSERT INTO `faces` VALUES (21, '3a8e2b752fc5c338b327dbf8780a7ece', 'org2-3/faceset-1/3a8e2b752fc5c338b327dbf8780a7ece.png', 12, '/s/UnUmdUh2kTB7D1xQnng==', 'g', '2018-07-24', 0, '2018-07-09 17:02:47', '2018-07-09 17:02:47');
INSERT INTO `faces` VALUES (22, '1b9336612337679f4373821bb682c9e8', 'org2-3/faceset-1/1b9336612337679f4373821bb682c9e8.png', 12, 'eFHub2fD7Ti924lGTxLo4Q==', 'g', '2018-07-24', 0, '2018-07-09 17:03:33', '2018-07-09 17:03:33');
INSERT INTO `faces` VALUES (24, '16029579a07841c341a5d080ce44d346', 'org2-3/faceset-2/16029579a07841c341a5d080ce44d346.png', 14, 'eFHub2fD7Ti924lGTxLo4Q==', 'g', '2018-07-24', 0, '2018-07-09 17:11:06', '2018-07-09 17:11:06');
INSERT INTO `faces` VALUES (25, '438b21243e64936f69baa719664e7936', 'org2-3/faceset-2/438b21243e64936f69baa719664e7936.png', 14, 'fEAuAWNMWDKlFrg/PRlpmQ==', 'g', '2018-07-24', 0, '2018-07-09 17:13:36', '2018-07-09 17:13:36');
INSERT INTO `faces` VALUES (26, 'b7660e8cdaabfa5c7028f69d3cc7e612', 'org2-3/faceset-2/b7660e8cdaabfa5c7028f69d3cc7e612.png', 14, '/s/UnUmdUh2kTB7D1xQnng==', 'g', '2018-07-24', 0, '2018-07-09 17:14:40', '2018-07-09 17:14:40');
INSERT INTO `faces` VALUES (27, 'd078ed085cdda6ae4409194bd3a2d35c', 'org1-1/faceset-2/d078ed085cdda6ae4409194bd3a2d35c.png', 15, '/s/UnUmdUh2kTB7D1xQnng==', 'ww', '2018-06-24', 0, '2018-07-09 17:23:50', '2018-07-09 17:23:50');
INSERT INTO `faces` VALUES (28, 'eb398071bf3d3210bf70ed320d29792d', 'org1-1/faceset-2/eb398071bf3d3210bf70ed320d29792d.png', 15, 'fEAuAWNMWDKlFrg/PRlpmQ==', 'ww', '2018-06-24', 0, '2018-07-09 17:27:10', '2018-07-09 17:27:10');
INSERT INTO `faces` VALUES (29, 'a90889f3da85e3dbf663bea696c1bf9f', 'org1-1/faceset-2/a90889f3da85e3dbf663bea696c1bf9f.png', 15, 'eFHub2fD7Ti924lGTxLo4Q==', 'ww', '2018-06-24', 0, '2018-07-09 17:29:51', '2018-07-09 17:29:51');

-- ----------------------------
-- Table structure for facesets
-- ----------------------------
DROP TABLE IF EXISTS `facesets`;
CREATE TABLE `facesets`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `facesetToken` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `organizationId` tinyint(4) NOT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 16 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Records of facesets
-- ----------------------------
INSERT INTO `facesets` VALUES (11, '57f8bcc8b78f2a12017019f12932ac3e', 1, '2018-07-09 16:57:22', '2018-07-09 16:57:22');
INSERT INTO `facesets` VALUES (12, '2f226f1af11aa75804896f48407b22c2', 3, '2018-07-09 17:01:55', '2018-07-09 17:01:55');
INSERT INTO `facesets` VALUES (14, 'e89e318b2e0b8fa24ed4aab09791de31', 3, '2018-07-09 17:11:06', '2018-07-09 17:11:06');
INSERT INTO `facesets` VALUES (15, 'ff84bbef9d87a9fb31754799373b7795', 1, '2018-07-09 17:23:50', '2018-07-09 17:23:50');

-- ----------------------------
-- Table structure for images
-- ----------------------------
DROP TABLE IF EXISTS `images`;
CREATE TABLE `images`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `caseId` int(11) NOT NULL,
  `filename` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `uploaded` datetime(0) NOT NULL,
  `lastSearched` datetime(0) NOT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for migrations
-- ----------------------------
DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `migration` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 9 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Records of migrations
-- ----------------------------
INSERT INTO `migrations` VALUES (1, '2014_10_12_000000_create_users_table', 1);
INSERT INTO `migrations` VALUES (2, '2014_10_12_100000_create_password_resets_table', 1);
INSERT INTO `migrations` VALUES (3, '2018_07_07_053024_create_organizations_table', 1);
INSERT INTO `migrations` VALUES (4, '2018_07_07_053113_create_facesets_table', 1);
INSERT INTO `migrations` VALUES (5, '2018_07_07_053128_create_faces_table', 1);
INSERT INTO `migrations` VALUES (6, '2018_07_07_053148_create_cases_table', 1);
INSERT INTO `migrations` VALUES (7, '2018_07_07_053205_create_images_table', 1);
INSERT INTO `migrations` VALUES (8, '2018_07_07_053215_create_stats_table', 1);

-- ----------------------------
-- Table structure for organizations
-- ----------------------------
DROP TABLE IF EXISTS `organizations`;
CREATE TABLE `organizations`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `contactName` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `contactEmail` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `contactPhone` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `active_facesetToken` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `accountStatus` tinyint(1) NOT NULL DEFAULT 1,
  `subscriptionExp` date NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 4 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Records of organizations
-- ----------------------------
INSERT INTO `organizations` VALUES (1, 'org1', '', '', '', 'ff84bbef9d87a9fb31754799373b7795', 1, NULL, NULL, '2018-07-09 17:23:50');
INSERT INTO `organizations` VALUES (3, 'org2', '', '', '', 'e89e318b2e0b8fa24ed4aab09791de31', 1, NULL, NULL, '2018-07-09 17:11:06');

-- ----------------------------
-- Table structure for password_resets
-- ----------------------------
DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE `password_resets`  (
  `email` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  INDEX `password_resets_email_index`(`email`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Records of password_resets
-- ----------------------------
INSERT INTO `password_resets` VALUES ('Caohero@yandex.com', '$2y$10$PNHTqKWBjUCAhq7nMNTn/.ArmF/1tIg5.OQebPSHpo.VNmvQwct0e', '2018-07-07 06:32:06');

-- ----------------------------
-- Table structure for stats
-- ----------------------------
DROP TABLE IF EXISTS `stats`;
CREATE TABLE `stats`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `organizationId` tinyint(4) NOT NULL,
  `searches` int(11) NOT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for users
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `organizationId` tinyint(4) NOT NULL,
  `password` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `activation_code` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 0,
  `lastlogin` datetime(0) NULL DEFAULT NULL,
  `loginCount` int(11) NOT NULL DEFAULT 0,
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `users_email_unique`(`email`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 4 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Records of users
-- ----------------------------
INSERT INTO `users` VALUES (3, 'a', 'a@a.com', 1, '$2y$10$PK2pNCCUxCJU1oTjohOQw.ViI4iJ4mDO2y4oplGBlXn3miTrEQtiy', NULL, 0, NULL, 0, 'NMCB1K1gNfk8ofWB6yVTl8coTgT44Cl5UIravpH781ASiqFgpWEsF4vznyR0', '2018-07-07 09:15:14', '2018-07-07 09:15:14');

SET FOREIGN_KEY_CHECKS = 1;
