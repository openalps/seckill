
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for seckill
-- ----------------------------
DROP TABLE IF EXISTS `seckill`;
CREATE TABLE `seckill`  (
  `id` int(0) NOT NULL AUTO_INCREMENT,
  `seckill_code` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `related_activity_code` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '关联活动代码',
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
  `tip_success_text` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '成功文案',
  `tip_unstock_text` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '领完文案',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `seckill_code`(`seckill_code`) USING BTREE,
  INDEX `group_code`(`group_code`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 11 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

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
) ENGINE = InnoDB AUTO_INCREMENT = 7 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

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
) ENGINE = InnoDB AUTO_INCREMENT = 35224 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

SET FOREIGN_KEY_CHECKS = 1;
