DROP TABLE IF EXISTS `vcr_user_basic`#
CREATE TABLE `vcr_user_basic` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '用户ID',
  `ll_id` int(20) NOT NULL COMMENT '了了用户ID',
  `name` varchar(20) NOT NULL DEFAULT '' COMMENT '用户姓名',
  `nickname` varchar(100) CHARACTER SET utf8mb4 NOT NULL COMMENT '用户昵称',
  `phone` varchar(20) DEFAULT NULL COMMENT '用户手机号',
  `openid` varchar(50) NOT NULL COMMENT '微信openid用户唯一标识',
  `headimgurl` varchar(255) DEFAULT NULL COMMENT '头像',
  `registrationtime` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '注册时间',
  `last_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '最后登录时间',
  `studytime` float(5,2) unsigned DEFAULT '1.00' COMMENT '总计学习时间',
  `curriculum` int(10) unsigned DEFAULT '0' COMMENT '累加完成课程',
  `root_organization_ids` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`,`ll_id`)
) ENGINE=MyISAM AUTO_INCREMENT=45 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='用户信息表'