/*
 Navicat Premium Data Transfer

 Source Server         : mccode@离岛免税
 Source Server Type    : MySQL
 Source Server Version : 80020
 Source Host           : 8.129.22.68:3306
 Source Schema         : duty_free20201225

 Target Server Type    : MySQL
 Target Server Version : 80020
 File Encoding         : 65001

 Date: 24/02/2021 11:32:22
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for seckill
-- ----------------------------
DROP TABLE IF EXISTS `seckill`;
CREATE TABLE `seckill`  (
  `id` int(0) NOT NULL AUTO_INCREMENT,
  `seckill_code` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `name` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '名称',
  `note` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '备注',
  `status_val` tinyint(1) NOT NULL COMMENT '状态',
  `create_time` int(0) UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int(0) UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  `run_time` int(0) UNSIGNED NOT NULL DEFAULT 0 COMMENT '启动时间',
  `stop_time` int(0) UNSIGNED NOT NULL DEFAULT 0 COMMENT '停止时间',
  `start_time` int(0) UNSIGNED NOT NULL DEFAULT 0 COMMENT '开始时间',
  `end_time` int(0) UNSIGNED NOT NULL DEFAULT 0 COMMENT '结束时间',
  `per_user_limit` int(0) UNSIGNED NOT NULL DEFAULT 0 COMMENT '每个人抢购件数限制',
  `per_user_day_limit` int(0) UNSIGNED NOT NULL DEFAULT 0 COMMENT '每人每天限制数量',
  `limit_count` int(0) UNSIGNED NOT NULL DEFAULT 0 COMMENT '限制数量',
  `success_count` int(0) UNSIGNED NOT NULL DEFAULT 0 COMMENT '秒杀成功数量',
  `fail_count` int(0) UNSIGNED NOT NULL DEFAULT 0 COMMENT '失败数量',
  `consumer` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '回调执行方法',
  `group_code` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '分组',
  `admin_id` int(0) UNSIGNED NOT NULL DEFAULT 0 COMMENT '管理员ID',
  `del_flag` tinyint(0) UNSIGNED NOT NULL DEFAULT 0 COMMENT '删除标识',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `seckill_code`(`seckill_code`) USING BTREE,
  INDEX `group_code`(`group_code`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of seckill
-- ----------------------------
INSERT INTO `seckill` VALUES (1, 'e6f708a383accc9fc4fbc548377522e5', '迎春-优惠券秒杀', '迎春-优惠券秒杀', 1, 1614009487, 1614134817, 1614076980, 1614131490, 1612969194, 1616549602, 0, 0, 11, 11, 89, '\\common\\services\\seckill\\SeckillService::insertCoupon', 'ee0149ede992859506d76a606874e7ac', 4, 1);
INSERT INTO `seckill` VALUES (2, '0e05fd4b2f322d000b52b21069b2fa10', '红包', '红包', 1, 1614009487, 1614135442, 1614135433, 1614135439, 1612882791, 1616549602, 5, 5, 1000, 0, 2, '\\common\\services\\seckill\\SeckillService::insertRed', 'ee0149ede992859506d76a606874e7ac', 4, 1);
INSERT INTO `seckill` VALUES (3, '2e963eb19f093a27d338c91302dee446', '200元处理异常订单', '', 1, 1614073370, 1614134827, 1614076974, 1614131487, 1614009600, 1614182400, 0, 0, 12, 12, 189, '\\common\\services\\seckill\\SeckillService::insertRed', 'ee0149ede992859506d76a606874e7ac', 4, 1);
INSERT INTO `seckill` VALUES (4, 'a7979ac10d31bec6051b154bb3a647e3', '元宵红包秒杀', '例子', 2, 1614135799, 1614136288, 1614136288, 1614136284, 1614096000, 1614355200, 2, 2, 100, 2, 6, '\\common\\services\\seckill\\call\\ExampleService::run', 'ee0149ede992859506d76a606874e7ac', 4, 0);

-- ----------------------------
-- Table structure for seckill_group
-- ----------------------------
DROP TABLE IF EXISTS `seckill_group`;
CREATE TABLE `seckill_group`  (
  `id` int(0) NOT NULL AUTO_INCREMENT,
  `group_code` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `name` varchar(40) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `note` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '说明',
  `per_user_day_group_limit` int(0) UNSIGNED NOT NULL DEFAULT 0,
  `per_user_group_limit` int(0) UNSIGNED NOT NULL DEFAULT 0,
  `create_time` int(0) UNSIGNED NOT NULL DEFAULT 0,
  `update_time` int(0) UNSIGNED NOT NULL DEFAULT 0,
  `admin_id` int(0) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `code`(`group_code`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 4 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of seckill_group
-- ----------------------------
INSERT INTO `seckill_group` VALUES (1, 'ee0149ede992859506d76a606874e7ac', '元宵抢券活动', '元宵抢券活动', 0, 0, 1613986072, 1614076917, 4);

-- ----------------------------
-- Table structure for seckill_request
-- ----------------------------
DROP TABLE IF EXISTS `seckill_request`;
CREATE TABLE `seckill_request`  (
  `id` int(0) UNSIGNED NOT NULL AUTO_INCREMENT,
  `seckill_code` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `group_code` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `status` tinyint(0) UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态',
  `num` int(0) NOT NULL DEFAULT -1 COMMENT '请求顺序',
  `create_time` bigint(0) UNSIGNED NOT NULL DEFAULT 0 COMMENT '秒杀请求时间',
  `platfromid` tinyint(0) UNSIGNED NOT NULL DEFAULT 0 COMMENT '客户端',
  `user_id` int(0) NOT NULL COMMENT '用户ID',
  `code` smallint(0) UNSIGNED NOT NULL DEFAULT 0 COMMENT '代码',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `code`(`seckill_code`) USING BTREE,
  INDEX `group_code`(`group_code`) USING BTREE,
  INDEX `status`(`status`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 31890 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of seckill_request
-- ----------------------------
INSERT INTO `seckill_request` VALUES (33459, '0e05fd4b2f322d000b52b21069b2fa10', 'ee0149ede992859506d76a606874e7ac', 1, -1, 1614134958650, 1, 67638643, 1005);
INSERT INTO `seckill_request` VALUES (33460, '0e05fd4b2f322d000b52b21069b2fa10', 'ee0149ede992859506d76a606874e7ac', 1, -1, 1614134977459, 1, 67638643, 1005);
INSERT INTO `seckill_request` VALUES (33461, 'a7979ac10d31bec6051b154bb3a647e3', 'ee0149ede992859506d76a606874e7ac', 2, 1, 1614135843871, 1, 67638643, 0);
INSERT INTO `seckill_request` VALUES (33462, 'a7979ac10d31bec6051b154bb3a647e3', 'ee0149ede992859506d76a606874e7ac', 1, -1, 1614136030423, 1, 67638643, 1005);
INSERT INTO `seckill_request` VALUES (33463, 'a7979ac10d31bec6051b154bb3a647e3', 'ee0149ede992859506d76a606874e7ac', 1, -1, 1614136062199, 1, 67638643, 1005);
INSERT INTO `seckill_request` VALUES (33464, 'a7979ac10d31bec6051b154bb3a647e3', 'ee0149ede992859506d76a606874e7ac', 1, -1, 1614136062370, 1, 67638643, 1005);
INSERT INTO `seckill_request` VALUES (33465, 'a7979ac10d31bec6051b154bb3a647e3', 'ee0149ede992859506d76a606874e7ac', 1, -1, 1614136062690, 1, 67638643, 1005);
INSERT INTO `seckill_request` VALUES (33466, 'a7979ac10d31bec6051b154bb3a647e3', 'ee0149ede992859506d76a606874e7ac', 1, -1, 1614136063100, 1, 67638643, 1005);
INSERT INTO `seckill_request` VALUES (33467, 'a7979ac10d31bec6051b154bb3a647e3', 'ee0149ede992859506d76a606874e7ac', 1, -1, 1614136063525, 1, 67638643, 1005);
INSERT INTO `seckill_request` VALUES (33468, 'a7979ac10d31bec6051b154bb3a647e3', 'ee0149ede992859506d76a606874e7ac', 2, 2, 1614136106325, 1, 67638643, 0);

SET FOREIGN_KEY_CHECKS = 1;
