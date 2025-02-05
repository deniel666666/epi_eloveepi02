/*更新備註*/
    ALTER TABLE `eve_processes` CHANGE `schedule` `schedule` TEXT CHARACTER SET utf8 COLLATE utf8_bin NOT NULL 
    COMMENT '[腳色,人員,行文紀錄,開始時間,結束時時間,績效,內外單]';
	
/*調整eve_role_flow階段*/
    TRUNCATE `eve_role_flow`;
    ALTER TABLE `eve_role_flow` CHANGE `id` `id` INT( 11 ) NOT NULL;
    ALTER TABLE `eve_role_flow` DROP PRIMARY KEY;

    INSERT INTO `eve_role_flow` (`id`, `name`, `status`) VALUES
    (-1, '草稿', 1),
    (0, '送件中', 1),
    (1, '分配中', 1),
    (2, '進行中', 1),
    (3, '延遲', 1),
    (4, '不允許執行', 1),
    (5, '完成待驗', 0),
    (6, '竣工', 1),
    (7, '歸檔事件', 0),
    (8, '暫停', 1),
    (9, '歸檔', 0),
    (10, '垃圾桶', 0),
    (11, '未排定時間', 1);

    ALTER TABLE `eve_role_flow` ADD PRIMARY KEY(`id`);
    ALTER TABLE `eve_role_flow` AUTO_INCREMENT=12;

/*更改預設內外單值*/
    ALTER TABLE `eve_steps` CHANGE `count_type` `count_type` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '0.內單 1.外單';


/*2021-07-28 合約類型加入簽名、問題功能*/
    ALTER TABLE `crm_cum_cat` 
    ADD `imgs` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '合約圖片們' AFTER `content`, 
    ADD `signatures` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '合約簽名們' AFTER `imgs`, 
    ADD `questions` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '合約問題們' AFTER `signatures`;
    ALTER TABLE `crm_contract`
    ADD `imgs` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '合約圖片們' AFTER `count_type`, 
    ADD `signatures` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '合約簽名們' AFTER `imgs`, 
    ADD `questions` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '合約問題們' AFTER `signatures`;

/*2021-07-29 加入公司統一資訊*/
    ALTER TABLE `eip_company` 
    ADD `en_name` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '英文公司名' AFTER `name`, 
    ADD `tel` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '電話' AFTER `en_name`, 
    ADD `fax` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '傳真' AFTER `tel`, 
    ADD `addr` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '地址' AFTER `fax`, 
    ADD `addr_link` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '地址連結' AFTER `addr`, 
    ADD `version` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '系統版本號' AFTER `addr_link`;
    ALTER TABLE `eip_company` ADD `top_id` INT(11) NOT NULL COMMENT '本公司crm_id' AFTER `id`;
    UPDATE `eip_company` SET `top_id` = '1' WHERE `eip_company`.`id` = 1;
    UPDATE `eip_company` SET `en_name` = 'Photonic Corperation' WHERE `eip_company`.`id` = 1;
    UPDATE `eip_company` SET `tel` = '02-2738-6266' WHERE `eip_company`.`id` = 1;
    UPDATE `eip_company` SET `fax` = '02-2738-6255' WHERE `eip_company`.`id` = 1;
    UPDATE `eip_company` SET `addr` = '11054臺北市信義區基隆路２段189號16樓之8' WHERE `eip_company`.`id` = 1;
    UPDATE `eip_company` SET `addr_link` = 'https://g.page/EIP-webdesign?share' WHERE `eip_company`.`id` = 1;
    UPDATE `eip_company` SET `version` = '5.2' WHERE `eip_company`.`id` = 1;
    ALTER TABLE `eip_company` ADD `top_teamid` INT(11) NOT NULL COMMENT '系統最高組別id' AFTER `top_id`;
    ALTER TABLE `eip_company` ADD `top_adminid` INT(11) NOT NULL COMMENT '系統最高管理人員id' AFTER `top_teamid`;
    UPDATE `eip_company` SET `top_teamid` = '1' WHERE `eip_company`.`id` = 1;
    UPDATE `eip_company` SET `top_adminid` = '1' WHERE `eip_company`.`id` = 1;

/*2021-07-30加入會議記錄*/
    INSERT INTO `powercat`
    (`id`, `level`, `islevel`, `parent_id`, `title`, `codenamed`, `description`, `orders`, `status`, `create_time`, `update_time`, `readself`, `readall`, `newcate`, `updatecate`, `delcate`, `truncate`) VALUES 
    ('110', '2', '1', '1', '會議記錄', 'Conference', '', '9', '1', '0', '0', '1', '1', '1', '1', '1', '1');
    -- ALTER TABLE `access` 
    -- ADD `conference_new` FLOAT NOT NULL DEFAULT '0' COMMENT 'conference新增' AFTER `ssl_all`, 
    -- ADD `conference_red` FLOAT NOT NULL DEFAULT '0' COMMENT 'conference指定閱讀' AFTER `conference_new`, 
    -- ADD `conference_edi` FLOAT NOT NULL DEFAULT '0' COMMENT 'conference修改' AFTER `conference_red`, 
    -- ADD `conference_hid` FLOAT NOT NULL DEFAULT '0' COMMENT 'conference隱藏' AFTER `conference_edi`, 
    -- ADD `conference_del` FLOAT NOT NULL DEFAULT '0' COMMENT 'conference刪除' AFTER `conference_hid`, 
    -- ADD `conference_all` FLOAT NOT NULL DEFAULT '0' COMMENT 'conference看全部' AFTER `conference_del`;
    UPDATE `access` SET 
    `conference_new` = '1', 
    `conference_red` = '1', 
    `conference_edi` = '1', 
    `conference_hid` = '1', 
    `conference_del` = '1', 
    `conference_all` = '1'
    WHERE `access`.`id` = 1;

/*2021-08-05調整客戶類別名稱*/
    UPDATE `crm_cum_type` SET `name` = '潛在客戶' WHERE `crm_cum_type`.`id` = 2;


/*2021-08-31可自行新增匯入客戶名稱比排除對詞*/
    CREATE TABLE `im_importclient_replace` ( 
    	`id` INT(11) NOT NULL , 
    	`name` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT 'crm欄位名稱', 
    	`title` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '標題', 
    	`content` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '內容', 
    	`note` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '備註' 
    ) ENGINE = InnoDB;
    ALTER TABLE `im_importclient_replace`
      ADD PRIMARY KEY (`id`),
      ADD UNIQUE KEY `id` (`id`);
    ALTER TABLE `im_importclient_replace`
    	MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
    INSERT INTO `im_importclient_replace` (`name`, `title`, `content`, `note`) VALUES 
    ('name', '公司名稱', '公司,英屬開曼群島商,英屬維京群島', '匯入客戶名稱比排除對詞(,分隔)'),
    ('url1', '網址', 'http://,http//,https://,https//,www.,WWW.', '匯入客戶網址比排除對詞(,分隔)');

/*2021-09-07添加客戶資料*/
    INSERT INTO `eip_company` (`id`, `top_id`, `top_teamid`, `top_adminid`, `name`, `en_name`, `tel`, `fax`, `addr`, `addr_link`, `version`) VALUES 
    (NULL, '0', '0', '0', '傳訊光科技股份有限公司', 'Photonic Corperation', '02-2738-6266', '02-2738-6255', '11054臺北市信義區基隆路２段189號16樓之8', 'https://g.page/EIP-webdesign?share', '客戶資料');

/*2021-10-19更正客戶預設等級*/
    ALTER TABLE `crm_crm` CHANGE `levelid` `levelid` SMALLINT(3) NOT NULL DEFAULT '0' COMMENT '客户等级';
    UPDATE `crm_crm` SET `levelid` = '0' WHERE `crm_crm`.`levelid` = 999;

/*2021-11-03新增可控制logo、footer資訊的後台*/
    INSERT INTO `powercat` (`id`, `level`, `islevel`, `parent_id`, `title`, `codenamed`, `description`, `orders`, `status`, `create_time`, `update_time`, `readself`, `readall`, `newcate`, `updatecate`, `delcate`, `truncate`) VALUES 
    (NULL, '2', '0', '0', '系統管理', '', '可以的話請不要刪掉我', '7', '1', '0', '0', '0', '0', '0', '0', '0', '0');
    INSERT INTO `powercat` (`id`, `level`, `islevel`, `parent_id`, `title`, `codenamed`, `description`, `orders`, `status`, `create_time`, `update_time`, `readself`, `readall`, `newcate`, `updatecate`, `delcate`, `truncate`) VALUES 
    (NULL, '2', '1', '111', '參數設定', 'Parameter', '', '1', '1', '0', '0', '1', '1', '1', '1', '1', '1');
    ALTER TABLE `eip_company` 
    ADD `login_logo` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '登入畫面圖示' AFTER `version`, 
    ADD `head_logo` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '系統logo' AFTER `login_logo`, 
    ADD `note` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '說明文字' AFTER `head_logo`;
    UPDATE `eip_company` SET `version` = NULL WHERE `eip_company`.`id` = 2;
    UPDATE `eip_company` SET `note` = '客戶資料' WHERE `eip_company`.`id` = 2;
    UPDATE `eip_company` SET `note` = '系統商資料' WHERE `eip_company`.`id` = 1;
    UPDATE `eip_company` SET `login_logo` = 'logo.png' WHERE `eip_company`.`id` = 1;
    UPDATE `eip_company` SET `head_logo` = 'fast_hand.png' WHERE `eip_company`.`id` = 1;
    ALTER TABLE `eip_company` ADD `eip_name` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '系統名稱' AFTER `top_adminid`;
    UPDATE `eip_company` SET `eip_name` = '傳訊光-快手特助' WHERE `eip_company`.`id` = 1;
    ALTER TABLE `eip_company` ADD `eip_en_name` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '系統英文名稱' AFTER `eip_name`;
    UPDATE `eip_company` SET `eip_en_name` = 'Photonic EIP System' WHERE `eip_company`.`id` = 1;

/*2021-11-22甘特圖顯示驗收未過流程*/
    ALTER TABLE `wrong_job` ADD `datestart` TEXT NULL DEFAULT NULL COMMENT '開始時間' AFTER `money`;
    ALTER TABLE `wrong_job` CHANGE `dateline` `dateline` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '結束時間';

/*2021-12-14補上參數設定的權限管理*/
    ALTER TABLE `access` 
    ADD `parameter_new` FLOAT NOT NULL DEFAULT '0' AFTER `conference_all`, 
    ADD `parameter_red` FLOAT NOT NULL DEFAULT '0' AFTER `parameter_new`, 
    ADD `parameter_edi` FLOAT NOT NULL DEFAULT '0' AFTER `parameter_red`, 
    ADD `parameter_hid` FLOAT NOT NULL DEFAULT '0' AFTER `parameter_edi`, 
    ADD `parameter_del` FLOAT NOT NULL DEFAULT '0' AFTER `parameter_hid`, 
    ADD `parameter_all` FLOAT NOT NULL DEFAULT '0' AFTER `parameter_del`;
    UPDATE `access` SET 
    `parameter_new` = '1', 
    `parameter_red` = '1', 
    `parameter_edi` = '1', 
    `parameter_hid` = '1', 
    `parameter_del` = '1', 
    `parameter_all` = '1'
    WHERE `access`.`id` = 1;

/*2021-12-14常用選單功能*/
    INSERT INTO `powercat` 
    (`id`, `level`, `islevel`, `parent_id`, `title`, `codenamed`, `description`, `orders`, `status`, `create_time`, `update_time`, `readself`, `readall`, `newcate`, `updatecate`, `delcate`, `truncate`) VALUES 
    (NULL, '2', '1', '111', '常用選單', 'Commonmenu', '', '2', '1', '0', '0', '1', '1', '1', '1', '1', '1');
    ALTER TABLE `access` 
    ADD `commonmenu_new` FLOAT NOT NULL DEFAULT '1' AFTER `parameter_all`, 
    ADD `commonmenu_red` FLOAT NOT NULL DEFAULT '1' AFTER `commonmenu_new`, 
    ADD `commonmenu_edi` FLOAT NOT NULL DEFAULT '1' AFTER `commonmenu_red`, 
    ADD `commonmenu_hid` FLOAT NOT NULL DEFAULT '1' AFTER `commonmenu_edi`, 
    ADD `commonmenu_del` FLOAT NOT NULL DEFAULT '1' AFTER `commonmenu_hid`, 
    ADD `commonmenu_all` FLOAT NOT NULL DEFAULT '1' AFTER `commonmenu_del`;
    CREATE TABLE `common_menu` ( 
    	`id` INT NOT NULL , 
    	`user_id` INT NOT NULL COMMENT '使用者id' , 
    	`data` TEXT NOT NULL COMMENT '常用選單資料' 
    ) ENGINE = InnoDB;
    ALTER TABLE `common_menu` ADD PRIMARY KEY(`id`);
    ALTER TABLE `common_menu` CHANGE id id int(11) AUTO_INCREMENT;
    ALTER TABLE `common_menu` CHANGE `data` `data` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '常用選單資料';


/*2021-12-16修改寄信系統*/
    DROP TABLE `member_group`; DROP TABLE `message_list`, `message_log`;
    DROP TABLE `newsletter`, `newsletter_log`, `newsletter_log_time`, `newsletter_send_time`;
    CREATE TABLE `member_group` (
        `id` int(20) NOT NULL,
        `name` varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
        `data` text CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
        `member_group` text DEFAULT NULL,
        `total` int(100) DEFAULT NULL,
        `creat_time` int(255) DEFAULT NULL,
        `update_time` int(255) DEFAULT NULL,
        `status` int(11) DEFAULT 1
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;
    CREATE TABLE `message_list` (
        `id` int(11) NOT NULL,
        `number` varchar(11) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
        `title` varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
        `msg` text CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
        `create_time` int(11) DEFAULT NULL,
        `update_time` int(11) DEFAULT NULL,
        `send_time` int(11) DEFAULT NULL,
        `total` int(50) DEFAULT 0,
        `status` int(10) DEFAULT 1,
        `msgid` text CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
        `msg_status` varchar(10) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;
    CREATE TABLE `message_log` (
        `id` int(50) NOT NULL,
        `number` varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
        `title` varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
        `total` int(50) DEFAULT NULL,
        `success` text CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
        `error` text CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
        `error_msgid` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
        `old_msgid` text CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
        `create_time` int(11) NOT NULL,
        `send_time` int(11) DEFAULT NULL,
        `message_list_id` varchar(50) DEFAULT NULL
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;
    CREATE TABLE `newsletter` (
        `id` int(11) NOT NULL,
        `number` varchar(11) COLLATE utf8_unicode_ci DEFAULT NULL,
        `title` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
        `msg` text COLLATE utf8_unicode_ci DEFAULT NULL,
        `create_time` int(11) DEFAULT NULL,
        `update_time` int(11) DEFAULT NULL,
        `send_time` int(11) DEFAULT 0,
        `total` int(50) DEFAULT 0,
        `status` int(5) DEFAULT 1,
        `msgid` text COLLATE utf8_unicode_ci DEFAULT NULL,
        `msg_status` int(1) NOT NULL DEFAULT 0 COMMENT '狀態 0.未安排 1.排程中 2.取消 3.寄送中 4.已完成'
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
    CREATE TABLE `newsletter_group` (
        `id` int(11) NOT NULL,
        `newsletter_id` int(11) NOT NULL COMMENT '對應newsletter id',
        `group` int(11) NOT NULL COMMENT '哪一批次',
        `schedule_time` int(10) NOT NULL COMMENT '排程日期',
        `create_time` int(10) NOT NULL COMMENT '創建日期',
        `title` text CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '標題',
        `msg` text CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '信件原始內容',
        `do_send_group` text CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '發送群組(json list)'
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1;
    CREATE TABLE `newsletter_send_time` (
        `id` int(11) NOT NULL,
        `newsletter_id` int(11) NOT NULL COMMENT '對應newsletter',
        `send_target` text NOT NULL COMMENT '寄送對象',
        `newsletter_group_id` int(11) NOT NULL COMMENT '對應newsletter_group id',
        `msg` text CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
        `email` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
        `return_value` varchar(10) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
        `send_time` int(10) DEFAULT NULL,
        `title` varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
        `open` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否開啟 0.否 1.是',
        `open_time` varchar(20) DEFAULT NULL COMMENT '開啟時間'
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;
    ALTER TABLE `member_group`
        ADD PRIMARY KEY (`id`),
        ADD UNIQUE KEY `id` (`id`);
    ALTER TABLE `message_list`
        ADD PRIMARY KEY (`id`);
    ALTER TABLE `message_log`
        ADD PRIMARY KEY (`id`);
    ALTER TABLE `newsletter`
        ADD PRIMARY KEY (`id`);
    ALTER TABLE `newsletter_group`
        ADD PRIMARY KEY (`id`),
        ADD UNIQUE KEY `newsletter_id` (`newsletter_id`,`group`);
    ALTER TABLE `newsletter_send_time`
        ADD PRIMARY KEY (`id`);
    ALTER TABLE `member_group`
        MODIFY `id` int(20) NOT NULL AUTO_INCREMENT;
    ALTER TABLE `message_list`
        MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
    ALTER TABLE `message_log`
        MODIFY `id` int(50) NOT NULL AUTO_INCREMENT;
    ALTER TABLE `newsletter`
        MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
    ALTER TABLE `newsletter_group`
        MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
    ALTER TABLE `newsletter_send_time`
        MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
    CREATE TABLE `format` (
        `id` int(20) NOT NULL,
        `name` varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
        `data` text CHARACTER SET utf8 COLLATE utf8_unicode_ci,
        `creat_time` int(255) DEFAULT NULL,
        `update_time` int(255) DEFAULT NULL,
        `status` int(11) DEFAULT '1'
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;
    ALTER TABLE `format`
        ADD PRIMARY KEY (`id`),
        ADD UNIQUE KEY `id` (`id`);
    ALTER TABLE `format`
        MODIFY `id` int(20) NOT NULL AUTO_INCREMENT;

/*2021-12-23 EIP串接新CRM畫面+程式資料庫更新*/
    DROP TABLE `business_record`;
    UPDATE `salesrecord` set ctype='1' WHERE ctype="新進客戶";
    UPDATE `salesrecord` set ctype='2' WHERE ctype="潛在客戶";
    UPDATE `salesrecord` set ctype='3' WHERE ctype="現有客戶" || ctype="成交客戶";
    UPDATE `salesrecord` set ctype='4' WHERE ctype="資料";
    UPDATE `salesrecord` set ctype='5' WHERE ctype="開放客戶";
    UPDATE `salesrecord` set ctype='6' WHERE ctype="垃圾桶";

    INSERT INTO `crm_cum_level` (`id`, `name`, `cid`, `status`) VALUES ('0', '無', '1', '1');
    UPDATE `crm_cum_level` SET `id` = '0' WHERE `crm_cum_level`.`id`=7;
    ALTER TABLE `crm_contact` ADD `eid` INT(11) NOT NULL DEFAULT '0' COMMENT '建立者id' AFTER `radio`;
    ALTER TABLE `crm_website` ADD `eid` INT(11) NOT NULL DEFAULT '0' COMMENT '建立者' AFTER `website_pw`;
    ALTER TABLE `crm_contact` CHANGE `birth` `birth` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '生日' AFTER `dateline`;
    ALTER TABLE `crm_contact` CHANGE `extension` `extension` VARCHAR(36) CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT NULL COMMENT '分機' AFTER `phone`;
    ALTER TABLE `crm_contact` DROP `fax`;
    ALTER TABLE `crm_crm` 
    ADD `phone` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '公司電話' AFTER `url2`, 
    ADD `mobile` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '公司手機' AFTER `phone`, 
    ADD `mail` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '公司信箱' AFTER `mobile`;
    ALTER TABLE `crm_crm` 
    CHANGE `phone` `comphone` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '公司電話', 
    CHANGE `mobile` `commobile` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '公司手機', 
    CHANGE `mail` `commail` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '公司信箱', 
    CHANGE `line` `comline` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT NULL, 
    CHANGE `fb` `comfb` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT NULL;
    ALTER TABLE `crm_crm` CHANGE `bossfax` `bossfax` CHAR(20) CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT NULL COMMENT '负责人传真' AFTER `commobile`, CHANGE `comfb` `comfb` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT NULL AFTER `comline`, CHANGE `mom` `mom` CHAR(255) CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT NULL COMMENT '备注' AFTER `comfb`, CHANGE `addr` `addr` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT NULL COMMENT '地址' AFTER `mom`, CHANGE `zip` `zip` VARCHAR(32) CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT NULL COMMENT '邮编 没用到' AFTER `addr`, CHANGE `accounting_addr` `accounting_addr` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT '\'1\'' AFTER `zip`, CHANGE `shipment_addr` `shipment_addr` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT '\'1\'' AFTER `accounting_addr`, CHANGE `factory_addr` `factory_addr` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT '\'1\'' AFTER `shipment_addr`, CHANGE `register_addr` `register_addr` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT '\'1\'' AFTER `factory_addr`, CHANGE `industr` `industr` CHAR(50) CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT NULL COMMENT '产业' AFTER `register_addr`, CHANGE `industr2` `industr2` TEXT CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT NULL COMMENT '產業2' AFTER `industr`, CHANGE `bossname` `bossname` CHAR(20) CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT NULL COMMENT '负责人' AFTER `industr2`, CHANGE `bossphone` `bossphone` CHAR(100) CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT NULL COMMENT '负责人电话' AFTER `bossname`, CHANGE `bossmobile` `bossmobile` CHAR(20) CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT NULL COMMENT '负责人手机' AFTER `bossphone`, CHANGE `bossmail` `bossmail` CHAR(255) CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT NULL COMMENT '负责人email' AFTER `bossmobile`;
    ALTER TABLE `crm_crm` CHANGE `bossfax` `comfax` CHAR(20) CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT NULL COMMENT '傳真';
    ALTER TABLE `powercat` ADD `link` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '連結' AFTER `codenamed`;
    UPDATE `powercat` SET `link` = '/index.php/Mens/index' WHERE `powercat`.`id` = 1;
    UPDATE `powercat` SET `link` = '/index.php/File/index' WHERE `powercat`.`id` = 92;
    UPDATE `powercat` SET `link` = '/index.php/Custo/index' WHERE `powercat`.`id` = 15;
    UPDATE `powercat` SET `link` = '/index.php/Alllist/index' WHERE `powercat`.`id` = 56;
    UPDATE `powercat` SET `link` = '/index.php/Server/index' WHERE `powercat`.`id` = 59;
    UPDATE `powercat` SET `link` = '/index.php/Seo/index' WHERE `powercat`.`id` = 64;
    UPDATE `powercat` SET `link` = '/index.php/Fig/index' WHERE `powercat`.`id` = 67;
    UPDATE `powercat` SET `link` = '/index.php/Performance/index' WHERE `powercat`.`id` = 105;
    UPDATE `powercat` SET `link` = '/index.php/Parameter/parameter' WHERE `powercat`.`id` = 111;
    UPDATE `powercat` SET `link` = 'http://mail.erp2000.com/' WHERE `powercat`.`id` = 104;
    UPDATE `powercat` SET `parent_id` = '0', `status`=0 WHERE `powercat`.`id` = 76;
    UPDATE `powercat` SET `parent_id` = '0' WHERE `powercat`.`id` = 45;
    UPDATE `powercat` SET `parent_id` = '0' WHERE `powercat`.`id` = 49;
    CREATE TABLE `crm_seoprice_engine` ( `id` INT NOT NULL , `engine` TEXT NULL DEFAULT NULL COMMENT '搜尋引擎' ) ENGINE = InnoDB;
    ALTER TABLE `crm_seoprice_engine` ADD PRIMARY KEY( `id`);
    ALTER TABLE `crm_seoprice_engine` CHANGE id id int(11) AUTO_INCREMENT;
    INSERT INTO `crm_seoprice_engine` (`id`, `engine`) VALUES (NULL, '台灣google'), (NULL, '台灣yahoo'), (NULL, '國際google');
    UPDATE `eip_company` SET `version` = '7.0' WHERE `eip_company`.`id` = 1;
    ALTER TABLE `crm_crm` 
    CHANGE `accounting_addr` `accounting_addr` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT '1', 
    CHANGE `shipment_addr` `shipment_addr` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT '1', 
    CHANGE `factory_addr` `factory_addr` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT '1', 
    CHANGE `register_addr` `register_addr` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT '1';
    -- 2021-12-29 客戶加入特性管理功能
        INSERT INTO `powercat` 
        (`id`, `level`, `islevel`, `parent_id`, `title`, `codenamed`, `link`, `description`, `orders`, `status`, `create_time`, `update_time`, `readself`, `readall`, `newcate`, `updatecate`, `delcate`, `truncate`) VALUES 
        (NULL, '2', '1', '111', '客戶特性設定', 'Crmproperty', NULL, '', '3', '1', '0', '0', '1', '1', '1', '1', '1', '1');
        ALTER TABLE `access` 
        ADD `crmproperty_new` FLOAT NOT NULL DEFAULT '0' AFTER `commonmenu_all`, 
        ADD `crmproperty_red` FLOAT NOT NULL DEFAULT '0' AFTER `crmproperty_new`, 
        ADD `crmproperty_edi` FLOAT NOT NULL DEFAULT '0' AFTER `crmproperty_red`, 
        ADD `crmproperty_hid` FLOAT NOT NULL DEFAULT '0' AFTER `crmproperty_edi`, 
        ADD `crmproperty_del` FLOAT NOT NULL DEFAULT '0' AFTER `crmproperty_hid`, 
        ADD `crmproperty_all` FLOAT NOT NULL DEFAULT '0' AFTER `crmproperty_del`;
        UPDATE `access` SET 
        `crmproperty_new` = '1', 
        `crmproperty_red` = '1', 
        `crmproperty_edi` = '1', 
        `crmproperty_hid` = '1', 
        `crmproperty_del` = '1', 
        `crmproperty_all` = '1'
        WHERE `access`.`id` = 1;
        CREATE TABLE `crm_property` (
          `id` int(11) NOT NULL,
          `title` text COLLATE utf8_unicode_ci NOT NULL COMMENT '標題',
          `type` text COLLATE utf8_unicode_ci NOT NULL COMMENT '輸入方式',
          `required` tinyint(4) NOT NULL DEFAULT 0 COMMENT '必填 0.否 1.是',
          `special` tinyint(4) NOT NULL DEFAULT 0 COMMENT '特殊欄位 0.否 1.是',
          `limit` text COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '限定格式',
          `discription` text COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '說明',
          `options` text COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '選項(json格式)',
          `order_id` int(11) NOT NULL COMMENT '排序',
          `online` tinyint(4) NOT NULL DEFAULT 1 COMMENT '狀態 0.停用 1.啟用'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        ALTER TABLE `crm_property`
          ADD PRIMARY KEY (`id`);
        ALTER TABLE `crm_property`
          MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
        ALTER TABLE `crm_crm` ADD `fields_data` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '特性資料' AFTER `need_track`;

    -- 2020-01-04 CRM欄位名稱從資料庫撈取
        CREATE TABLE `system_parameter` ( 
            `id` INT NOT NULL , 
            `data` text NULL DEFAULT NULL COMMENT '資料' , 
            `note` text NULL DEFAULT NULL COMMENT '說明' 
        ) ENGINE = InnoDB CHARSET=utf8 COLLATE utf8_unicode_ci;
        ALTER TABLE `system_parameter` ADD PRIMARY KEY(`id`);
        ALTER TABLE `system_parameter` CHANGE id id int(11) AUTO_INCREMENT;
        INSERT INTO `system_parameter` (`id`, `data`, `note`) VALUES (
            NULL, 
            '{\r\n  \"負責人\": \"負責人\",\r\n   \"聯絡人\": \"聯絡人\",\r\n   \"協同人員\": \"協同人員\",\r\n \"特性管理\": \"特性管理\",\r\n \"訪談紀錄\": \"訪談紀錄\",\r\n \"小事\": \"小事\",\r\n \"綜合事項\": \"綜合事項\",\r\n \"合約歷程\": \"合約歷程\",\r\n \"事件列表\": \"事件列表\",\r\n \"公司資訊\": \"公司資訊\",\r\n \"網群\": \"網群\",\r\n \"請款週期\": \"請款週期\",\r\n \"收付款方式\": \"收付款方式\",\r\n   \"空間流量追蹤\": \"空間流量追蹤\",\r\n \"官方網站\": \"官方網站\",\r\n \"品牌網站\": \"品牌網站\",\r\n \"員工人數\": \"員工人數\",\r\n \"經濟部替代匯入\": \"經濟部替代匯入\",\r\n\r\n   \"客戶\": \"客戶\",\r\n \"名稱\": \"名稱\",\r\n \"職稱\": \"職稱\",\r\n \"簡稱\": \"簡稱\",\r\n \"統編\": \"統編\",\r\n \"地址\": \"地址\",\r\n \"傳真\": \"傳真\",\r\n \"起算日期\": \"起算日期\",\r\n \"產業別\": \"產業別\",\r\n   \"產業次項\": \"產業次項\",\r\n \"類別\": \"類別\",\r\n \"等級\": \"等級\",\r\n \"來源\": \"來源\",\r\n \"資本額\": \"資本額\",\r\n   \"關係企業\": \"關係企業\",\r\n \"業務分析\": \"業務分析\",\r\n \"特別說明\": \"特別說明\",\r\n \"拜訪地址\": \"拜訪地址\",\r\n \"會計地址\": \"會計地址\",\r\n \"出貨地址\": \"出貨地址\",\r\n \"工廠地址\": \"工廠地址\",\r\n \"登記地址\": \"登記地址\",\r\n\r\n \"公司名稱\": \"公司名稱\",\r\n \"公司電話\": \"電話\",\r\n   \"公司手機\": \"手機\",\r\n   \"公司MAIL\": \"MAIL\",\r\n   \"公司核准日\": \"核准日\",\r\n \"公司LINE_FB\": \"LINE / FB\",\r\n   \"公司備註\": \"備註\",\r\n\r\n   \"負責人暱稱\": \"暱稱\",\r\n  \"負責人電話\": \"電話\",\r\n  \"負責人手機\": \"手機\",\r\n  \"負責人MAIL\": \"MAIL\",\r\n  \"負責人生日\": \"生日\",\r\n  \"負責人LINE_FB\": \"LINE / FB\",\r\n  \"負責人備註\": \"備註\",\r\n\r\n  \"聯絡人暱稱\": \"暱稱\",\r\n  \"聯絡人電話\": \"電話\",\r\n  \"聯絡人手機\": \"手機\",\r\n  \"聯絡人MAIL\": \"MAIL\",\r\n  \"聯絡人生日\": \"生日\",\r\n  \"聯絡人LINE_FB\": \"LINE / FB\",\r\n  \"聯絡人備註\": \"備註\"\r\n}'
            ,
            '1.負責人,聯絡人,協同人員,特性管理,訪談紀錄,小事,綜合事項,合約歷程,事件列表,公司資訊,網群,請款週期,收付款方式,官方網站,品牌網站,員工人數,經濟部替代匯入 若設定為空值，則區塊隱藏\r\n2.若 負責人 被隱藏，預設聯絡對象抓取公司資料'
        );
        DROP TABLE crm_cum_span; -- 刪除無用資料表

        UPDATE `crm_cum_type` SET `name` = '新進' WHERE `crm_cum_type`.`id` = 1;
        UPDATE `crm_cum_type` SET `name` = '潛在' WHERE `crm_cum_type`.`id` = 2;
        UPDATE `crm_cum_type` SET `name` = '成交' WHERE `crm_cum_type`.`id` = 3;
        UPDATE `crm_cum_type` SET `name` = '開放' WHERE `crm_cum_type`.`id` = 5;

        ALTER TABLE `crm_crm` 
        CHANGE `industr` `industr` CHAR(50) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '' COMMENT '產業', 
        CHANGE `industr2` `industr2` TEXT CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '' COMMENT '產業次項';
        ALTER TABLE `crm_contact` CHANGE `dateline` `dateline` INT(10) NULL DEFAULT NULL COMMENT '建立/修改時間';
        ALTER TABLE `crm_contact` 
        CHANGE `birth` `birth` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '生日' AFTER `mobile`, 
        CHANGE `mom` `mom` TEXT CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT NULL COMMENT '备注' AFTER `birth`, 
        CHANGE `mail` `mail` CHAR(100) CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT NULL COMMENT 'mail' AFTER `mom`, 
        CHANGE `is_default` `is_default` TINYINT(1) NULL DEFAULT '1' COMMENT '默认联系人' AFTER `mail`, 
        CHANGE `radio` `radio` TINYINT(4) NOT NULL DEFAULT '0' AFTER `is_default`, 
        CHANGE `eid` `eid` INT(11) NOT NULL DEFAULT '0' COMMENT '建立者id' AFTER `radio`;

        ALTER TABLE `crm_crm` 
        CHANGE `bossname` `bossname` CHAR(20) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '' COMMENT '負責人姓名', 
        CHANGE `bossphone` `bossphone` CHAR(100) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '' COMMENT '負責人電話', 
        CHANGE `bossmobile` `bossmobile` CHAR(20) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '' COMMENT '負責人手機', 
        CHANGE `bossmail` `bossmail` CHAR(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '' COMMENT '負責人email', 
        CHANGE `bossline` `bossline` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '' COMMENT '負責人Line', 
        CHANGE `bossfb` `bossfb` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '' COMMENT '負責人FB', 
        CHANGE `bossbirth` `bossbirth` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '' COMMENT '負責人生日', 
        CHANGE `bossmom` `bossmom` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '' COMMENT '負責人備註', 
        CHANGE `bossposition` `bossposition` VARCHAR(36) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '' COMMENT '負責人職稱' AFTER `bossname`, 
        CHANGE `bossextension` `bossextension` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '' COMMENT '負責人分機' AFTER `bossphone`;
        ALTER TABLE `crm_crm` CHANGE `newclient_date` `newclient_date` VARCHAR(20) NULL DEFAULT NULL COMMENT '新進客戶業務起算日';

        ALTER TABLE `crm_crm`
          DROP `need_project`,
          DROP `need_visit`,
          DROP `need_proposal`,
          DROP `rest_day`,
          DROP `contact_time`;

        ALTER TABLE `crm_crm` 
        CHANGE `name` `name` CHAR(200) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT '客户名称', 
        CHANGE `nick` `nick` CHAR(10) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '简称', 
        CHANGE `no` `no` CHAR(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '统编', 
        CHANGE `url1` `url1` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '官网', 
        CHANGE `url2` `url2` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '其他网址', 
        CHANGE `comfax` `comfax` CHAR(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '傳真', 
        CHANGE `comline` `comline` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL, 
        CHANGE `comfb` `comfb` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL, 
        CHANGE `mom` `mom` CHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '备注', 
        CHANGE `addr` `addr` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '地址', 
        CHANGE `zip` `zip` VARCHAR(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci not NULL DEFAULT '' COMMENT '郵遞區號(配合addr)', 
        CHANGE `accounting_addr` `accounting_addr` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT '1', 
        CHANGE `shipment_addr` `shipment_addr` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT '1', 
        CHANGE `factory_addr` `factory_addr` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT '1', 
        CHANGE `register_addr` `register_addr` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT '1', 
        CHANGE `industr` `industr` CHAR(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '產業', 
        CHANGE `industr2` `industr2` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '產業次項', 
        CHANGE `bossname` `bossname` CHAR(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '負責人姓名', 
        CHANGE `bossposition` `bossposition` VARCHAR(36) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '負責人職稱', 
        CHANGE `bossphone` `bossphone` CHAR(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '負責人電話', 
        CHANGE `bossextension` `bossextension` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '負責人分機', 
        CHANGE `bossmobile` `bossmobile` CHAR(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '負責人手機', 
        CHANGE `bossmail` `bossmail` CHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '負責人email', 
        CHANGE `bossline` `bossline` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '負責人Line', 
        CHANGE `bossfb` `bossfb` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '負責人FB', 
        CHANGE `bossbirth` `bossbirth` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '負責人生日', 
        CHANGE `bossmom` `bossmom` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '負責人備註', 
        CHANGE `eipid` `eipid` VARCHAR(36) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT 'eip账号', 
        CHANGE `zbe` `zbe` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT '資本額', 
        CHANGE `hzrq` `hzrq` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT '核準日期', 
        CHANGE `eippwd` `eippwd` VARCHAR(36) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT 'eip密码', 
        CHANGE `explan` `explan` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT '特別說明', 
        CHANGE `affiliates1` `affiliates1` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT '關係企業1', 
        CHANGE `affiliates2` `affiliates2` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT '關係企業2', 
        CHANGE `affiliates3` `affiliates3` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT '關係企業3', 
        CHANGE `affiliates4` `affiliates4` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT '關係企業4', 
        CHANGE `busanalysis` `busanalysis` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT '業務分析', 
        CHANGE `sn_num` `sn_num` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL, 
        CHANGE `newclient_date` `newclient_date` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '新進客戶業務起算日';

        ALTER TABLE `im_importclient` CHANGE `excela` `excela` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT 'excela';
        ALTER TABLE `im_importclient` CHANGE `excelb` `excelb` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT 'excelb';
        ALTER TABLE `im_importclient` CHANGE `excelc` `excelc` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT 'excelc';
        ALTER TABLE `im_importclient` CHANGE `exceld` `exceld` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT 'exceld';
        ALTER TABLE `im_importclient` CHANGE `excele` `excele` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT 'excele';
        ALTER TABLE `im_importclient` CHANGE `excelf` `excelf` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT 'excelf';
        ALTER TABLE `im_importclient` CHANGE `excelg` `excelg` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT 'excelg';
        ALTER TABLE `im_importclient` CHANGE `excelh` `excelh` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT 'excelh';
        ALTER TABLE `im_importclient` CHANGE `exceli` `exceli` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT 'exceli';
        ALTER TABLE `im_importclient` CHANGE `excelj` `excelj` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT 'excelj';
        ALTER TABLE `im_importclient` CHANGE `excelk` `excelk` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT 'excelk';
        ALTER TABLE `im_importclient` CHANGE `excell` `excell` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT 'excell';
        ALTER TABLE `im_importclient` CHANGE `excelm` `excelm` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT 'excelm';
        ALTER TABLE `im_importclient` CHANGE `exceln` `exceln` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT 'exceln';
        ALTER TABLE `im_importclient` CHANGE `excelo` `excelo` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT 'excelo';
        ALTER TABLE `im_importclient` CHANGE `excelp` `excelp` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT 'excelp';
        ALTER TABLE `im_importclient` CHANGE `excelq` `excelq` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT 'excelq';
        ALTER TABLE `im_importclient` CHANGE `excelr` `excelr` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT 'excelr';
        ALTER TABLE `im_importclient` CHANGE `excels` `excels` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT 'excels';
        ALTER TABLE `im_importclient` CHANGE `excelt` `excelt` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT 'excelt';
        ALTER TABLE `im_importclient` CHANGE `excelu` `excelu` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT 'excelu';
        ALTER TABLE `im_importclient` CHANGE `excelv` `excelv` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT 'excelv';
        ALTER TABLE `im_importclient` CHANGE `excelw` `excelw` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT 'excelw';
        ALTER TABLE `im_importclient` CHANGE `excelx` `excelx` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT 'excelx';
        ALTER TABLE `im_importclient` CHANGE `excely` `excely` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT 'excely';
        ALTER TABLE `im_importclient` CHANGE `excelz` `excelz` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT 'excelz';

/*2022-01-07 合約分期自動出帳*/
    ALTER TABLE `crm_contract` CHANGE `contracttime` `contracttime` INT(11) NOT NULL DEFAULT '0' COMMENT '分期出帳數';
    ALTER TABLE `crm_contract` ADD `starttime` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '開始日期' AFTER `contracttime`;
    ALTER TABLE `crm_contract` CHANGE `endtime` `endtime` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '結束日期' AFTER `starttime`;
    ALTER TABLE `crm_contract` ADD `sale_items` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '出貨品項(分期使用)' AFTER `endtime`;
    INSERT INTO `system_parameter` (`id`, `data`, `note`) VALUES ('2', '[3, 6, 9, 12]', '合約分期出帳數選項');
    INSERT INTO `system_parameter` (`id`, `data`, `note`) VALUES ('3', '25', '定期出帳日期(請設定每月都有的日期)');
    INSERT INTO `system_parameter` (`id`, `data`, `note`) VALUES ('4', '[14, 5]', '未付款提醒([核可後幾日提醒, 提醒後幾日再提醒])，核可後幾日提醒設為負數則表示不使用此功能');
    ALTER TABLE `crm_othermoney` ADD `queryflag_time` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT '核可日期' AFTER `queryflag`;
    ALTER TABLE `crm_seomoney` ADD `queryflag_time` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT '核可日期' AFTER `queryflag`;
    ALTER TABLE `crm_othermoney` ADD `ticket_rand` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '發票隨機碼' AFTER `ticket`;
    ALTER TABLE `crm_othermoney` CHANGE `ticket` `ticket` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;
    ALTER TABLE `crm_othermoney` CHANGE `invoice` `invoice` TEXT CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT NULL COMMENT '發票開立方式(二聯,三聯,無)';
    INSERT INTO `system_parameter` (`id`, `data`, `note`) VALUES ('5', '0', '電子發票功能 0.不使用 1.使用');
    INSERT INTO `system_parameter` (`id`, `data`, `note`) VALUES ('6', '0', '請款提醒信功能 0.不使用 1.使用');

/*2022-01-19 雜項修改*/
    UPDATE `system_parameter` SET `data` = '{\r\n   \"負責人\": \"負責人\",\r\n   \"聯絡人\": \"聯絡人\",\r\n   \"協同人員\": \"協同人員\",\r\n \"特性管理\": \"特性管理\",\r\n \"訪談紀錄\": \"訪談紀錄\",\r\n \"小事\": \"小事\",\r\n \"綜合事項\": \"綜合事項\",\r\n \"合約歷程\": \"合約歷程\",\r\n \"事件列表\": \"事件列表\",\r\n \"公司資訊\": \"公司資訊\",\r\n \"網群\": \"網群\",\r\n \"請款週期\": \"請款週期\",\r\n \"收付款方式\": \"收付款方式\",\r\n   \"空間流量追蹤\": \"空間流量追蹤\",\r\n \"官方網站\": \"官方網站\",\r\n \"品牌網站\": \"品牌網站\",\r\n \"員工人數\": \"員工人數\",\r\n \"經濟部替代匯入\": \"經濟部替代匯入\",\r\n\r\n   \"客戶\": \"客戶\",\r\n \"名稱\": \"名稱\",\r\n \"職稱\": \"職稱/暱稱\",\r\n  \"簡稱\": \"簡稱\",\r\n \"統編\": \"統編\",\r\n \"地址\": \"地址\",\r\n \"傳真\": \"傳真\",\r\n \"起算日期\": \"起算日期\",\r\n \"產業別\": \"產業別\",\r\n   \"產業次項\": \"產業次項\",\r\n \"類別\": \"類別\",\r\n \"等級\": \"等級\",\r\n \"來源\": \"來源\",\r\n \"資本額\": \"資本額\",\r\n   \"關係企業\": \"關係企業\",\r\n \"業務分析\": \"業務分析\",\r\n \"特別說明\": \"特別說明\",\r\n \"拜訪地址\": \"拜訪地址\",\r\n \"會計地址\": \"會計地址\",\r\n \"出貨地址\": \"出貨地址\",\r\n \"工廠地址\": \"工廠地址\",\r\n \"登記地址\": \"登記地址\",\r\n\r\n \"公司名稱\": \"公司名稱\",\r\n \"公司電話\": \"電話\",\r\n   \"公司手機\": \"手機\",\r\n   \"公司MAIL\": \"MAIL\",\r\n   \"公司核准日\": \"核准日\",\r\n \"公司LINE_FB\": \"LINE / FB\",\r\n   \"公司LINE\": \"LINE\",\r\n   \"公司FB\": \"FB\",\r\n   \"公司備註\": \"備註\",\r\n\r\n   \"負責人暱稱\": \"姓名\",\r\n  \"負責人電話\": \"電話\",\r\n  \"負責人手機\": \"手機\",\r\n  \"負責人MAIL\": \"MAIL\",\r\n  \"負責人生日\": \"生日\",\r\n  \"負責人LINE_FB\": \"LINE / FB\",\r\n  \"負責人LINE\": \"LINE\",\r\n  \"負責人FB\": \"FB\",\r\n  \"負責人備註\": \"備註\",\r\n\r\n  \"聯絡人暱稱\": \"姓名\",\r\n  \"聯絡人電話\": \"電話\",\r\n  \"聯絡人手機\": \"手機\",\r\n  \"聯絡人MAIL\": \"MAIL\",\r\n  \"聯絡人生日\": \"生日\",\r\n  \"聯絡人LINE_FB\": \"LINE / FB\",\r\n  \"聯絡人LINE\": \"LINE\",\r\n  \"聯絡人FB\": \"FB\",\r\n  \"聯絡人備註\": \"備註\"\r\n}' WHERE `system_parameter`.`id` = 1;
    INSERT INTO `im_importclient_replace` (`id`, `name`, `title`, `content`, `note`) VALUES (NULL, 'comphone', '電話', '-,(,)', '匯入客戶電話比排除對詞(,分隔)');
    INSERT INTO `powercat` (`id`, `level`, `islevel`, `parent_id`, `title`, `codenamed`, `link`, `description`, `orders`, `status`, `create_time`, `update_time`, `readself`, `readall`, `newcate`, `updatecate`, `delcate`, `truncate`) VALUES 
    (NULL, '2', '1', '111', '產業次項設定', 'Industr', NULL, '', '4', '1', '0', '0', '1', '1', '1', '1', '1', '1'),
    (NULL, '2', '1', '111', '協同人員設定', 'Cumpri', NULL, '', '5', '1', '0', '0', '1', '1', '1', '1', '1', '1');

/*2022-01-21 修改共用文件分類*/
    DROP TABLE `file_type`;

/*2022-01-21 修改文章層記錄方式*/
    ALTER TABLE `file` ADD `parent_id` INT NOT NULL DEFAULT '0' COMMENT '父階層id 0.表示頂層' AFTER `num`;
    ALTER TABLE `file` ADD `order_id` INT NOT NULL DEFAULT '0' COMMENT '排序' AFTER `parent_id`;
    UPDATE file
    set  file.order_id = (
         select file_level.level1_order
         from file_level 
         where file_level.level1_file = file.id
    )
    where exists (
         select 1
         from file_level
         where file_level.level1_file = file.id
    );
    UPDATE file
    set  file.order_id = (
        select file_level2.level2_order
        from file_level2 
        where file_level2.level2_file = file.id
    ), file.parent_id = (
        select file_level2.level1_file
        from file_level2 
        where file_level2.level2_file = file.id
    )
    where exists (
        select 1
        from file_level2
        where file_level2.level2_file = file.id
    );
    UPDATE file
    set  file.order_id = (
        select file_level_sort.file_order
        from file_level_sort 
        where file_level_sort.file = file.id
    ), file.parent_id = (
        select file_level_sort.level2
        from file_level_sort 
        where file_level_sort.file = file.id
    )
    where exists (
        select 1
        from file_level_sort
        where file_level_sort.file = file.id
    );
    DROP TABLE `file_level`, `file_level2`, `file_level_sort`;

/*2022-01-24 CRM改名*/
    UPDATE `powercat` SET `title` = '客勤管理' WHERE `powercat`.`id` = 75;
    UPDATE `powercat` SET `title` = '專案管理' WHERE `powercat`.`id` = 67;

/*2020-01-24 合約類別設定移位*/
    INSERT INTO `powercat` (`id`, `level`, `islevel`, `parent_id`, `title`, `codenamed`, `link`, `description`, `orders`, `status`, `create_time`, `update_time`, `readself`, `readall`, `newcate`, `updatecate`, `delcate`, `truncate`) VALUES (NULL, '2', '1', '111', '合約類別設定', 'Crmcumcat', NULL, '', '6', '1', '0', '0', '1', '1', '1', '1', '1', '1');

/*2022-01-27 事件步驟加入預估&實際執行時間*/
    ALTER TABLE `eve_steps` 
    ADD `estimated_time` FLOAT NULL DEFAULT '0' COMMENT '估計時間' AFTER `end_time`, 
    ADD `exact_time` FLOAT NULL DEFAULT '0' COMMENT '實作時間' AFTER `estimated_time`;
    ALTER TABLE `wrong_job` 
    ADD `estimated_time` FLOAT NULL DEFAULT '0' COMMENT '估計時間' AFTER `dateline`, 
    ADD `exact_time` FLOAT NULL DEFAULT '0' COMMENT '實作時間' AFTER `estimated_time`;

/*2022-02-16*/
    /*合約分期可開關*/
    UPDATE `system_parameter` SET `note` = '定期出帳日期(請設定每月都有的日期，設0則表示不使用)' WHERE `system_parameter`.`id` = 3;
    /*移動出貨管理的參數設定*/
    INSERT INTO `powercat` (`id`, `level`, `islevel`, `parent_id`, `title`, `codenamed`, `link`, `description`, `orders`, `status`, `create_time`, `update_time`, `readself`, `readall`, `newcate`, `updatecate`, `delcate`, `truncate`) VALUES (NULL, '2', '1', '111', '出貨備註設定', 'Salesetting', NULL, '', '7', '1', '0', '0', '1', '1', '1', '1', '1', '1');
    /*合併部門與職稱*/
    DELETE FROM `powercat` WHERE `powercat`.`id` = 55;
    UPDATE `powercat` SET `parent_id` = '111', `title` = '職稱&部門管理', `orders` = '0' WHERE `powercat`.`id` = 73;
    UPDATE `powercat` SET `orders` = '10' WHERE `powercat`.`id` = 113;
    UPDATE `powercat` SET `orders` = '15' WHERE `powercat`.`id` = 73;
    UPDATE `powercat` SET `orders` = '20' WHERE `powercat`.`id` = 114;
    UPDATE `powercat` SET `orders` = '25' WHERE `powercat`.`id` = 115;
    UPDATE `powercat` SET `orders` = '30' WHERE `powercat`.`id` = 116;
    UPDATE `powercat` SET `orders` = '35' WHERE `powercat`.`id` = 117;
    UPDATE `powercat` SET `orders` = '40' WHERE `powercat`.`id` = 118;
    /*刪除無用權限*/
    ALTER TABLE `access`
    DROP `jobs_all`,
    DROP `commonmenu_del`,
    DROP `crmproperty_all`;
    ALTER TABLE `access`
    DROP `commonmenu_new`,
    DROP `commonmenu_red`,
    DROP `commonmenu_edi`,
    DROP `commonmenu_hid`,
    DROP `commonmenu_all`;

/*2022-03-04刪除無用內容&調整資料庫*/
    DROP TABLE `eve_code`, `eve_codeofeid`;
    INSERT INTO `powercat` (`id`, `level`, `islevel`, `parent_id`, `title`, `codenamed`, `link`, `description`, `orders`, `status`, `create_time`, `update_time`, `readself`, `readall`, `newcate`, `updatecate`, `delcate`, `truncate`) VALUES (NULL, '2', '1', '111', '系統操作紀錄', 'Demo', NULL, '', '999', '0', '0', '0', '1', '1', '1', '1', '1', '1');

/*2022-03-07更改選單順序*/
    UPDATE `powercat` SET `orders` = '0' WHERE `powercat`.`id` = 15;
    UPDATE `powercat` SET `orders` = '1' WHERE `powercat`.`id` = 67;
    UPDATE `powercat` SET `orders` = '3' WHERE `powercat`.`id` = 92;
    UPDATE `powercat` SET `orders` = '4' WHERE `powercat`.`id` = 56;
    UPDATE `powercat` SET `orders` = '5' WHERE `powercat`.`id` = 59;
    UPDATE `powercat` SET `orders` = '6' WHERE `powercat`.`id` = 64;
    UPDATE `powercat` SET `orders` = '7' WHERE `powercat`.`id` = 1;
    UPDATE `powercat` SET `orders` = '8' WHERE `powercat`.`id` = 105;
    UPDATE `powercat` SET `orders` = '9' WHERE `powercat`.`id` = 111;

/*2022-03-21 修改資料儲存長度*/
    ALTER TABLE `crm_hostcompany` CHANGE `dnsurl` `dnsurl` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

/*2022-03-21 新增合約類別權限*/
    ALTER TABLE `access` 
    ADD `crmcumcat_new` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '合約類別新增' AFTER `alllist_all`, 
    ADD `crmcumcat_red` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '合約類別讀取' AFTER `crmcumcat_new`, 
    ADD `crmcumcat_edi` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '合約類別編輯' AFTER `crmcumcat_red`, 
    ADD `crmcumcat_hid` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '合約類別假刪除' AFTER `crmcumcat_edi`,
    ADD `crmcumcat_del` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '合約類別真刪除' AFTER `crmcumcat_hid`;
    UPDATE `access` SET 
    `crmcumcat_new` = '1', 
    `crmcumcat_red` = '1', 
    `crmcumcat_edi` = '1', 
    `crmcumcat_hid` = '1', 
    `crmcumcat_del` = '1'
    WHERE `access`.`id` = 1;
/*2022-03-23調整權限*/
    ALTER TABLE `access`
      DROP `imcrm_new`,
      DROP `imcrm_del`,
      DROP `imcrm_all`;
    ALTER TABLE `access`
      DROP `imman_new`,
      DROP `imman_red`,
      DROP `imman_edi`,
      DROP `imman_hid`,
      DROP `imman_del`,
      DROP `imman_all`;
    DELETE FROM `powercat` WHERE `powercat`.`id` = 52;
    ALTER TABLE `access`
      DROP `prepaid_new`,
      DROP `prepaid_hid`,
      DROP `prepaid_del`;
    ALTER TABLE `access`
      DROP `quan_new`,
      DROP `quan_red`,
      DROP `quan_edi`,
      DROP `quan_hid`,
      DROP `quan_del`,
      DROP `quan_all`;
    ALTER TABLE `access`
      DROP `ques_new`,
      DROP `ques_red`,
      DROP `ques_edi`,
      DROP `ques_hid`,
      DROP `ques_del`,
      DROP `ques_all`;
    DELETE FROM `powercat` WHERE `powercat`.`id` = 47;
    DELETE FROM `powercat` WHERE `powercat`.`id` = 46;
    DROP TABLE `ques`, `ques_back`;
    ALTER TABLE `access` DROP `seoprice_all`;
    ALTER TABLE `access`
      DROP `seo_new`,
      DROP `seo_hid`,
      DROP `seo_del`;
    ALTER TABLE `access` DROP `sale_hid`;
    ALTER TABLE `crm_shipment` DROP `allow`;

/*2022-05-05小事新增回覆*/
    ALTER TABLE `crm_chats` ADD `do_response` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '處理回覆' AFTER `doevt`;

/*2022-05-17 選單改順序跟名稱*/
    UPDATE `powercat` SET `title` = '設定管理' WHERE `powercat`.`id` = 111;
    UPDATE `powercat` SET `title` = '客戶特性', `orders` = '10' WHERE `powercat`.`id` = 114;
    UPDATE `powercat` SET `title` = '產業次項', `orders` = '15' WHERE `powercat`.`id` = 115;
    UPDATE `powercat` SET `title` = '協同人員', `orders` = '20' WHERE `powercat`.`id` = 116;
    UPDATE `powercat` SET `title` = '合約類別', `orders` = '25' WHERE `powercat`.`id` = 117;
    UPDATE `powercat` SET `title` = '出貨備註', `orders` = '30' WHERE `powercat`.`id` = 118;
    UPDATE `powercat` SET `orders` = '35' WHERE `powercat`.`id` = 113;
    UPDATE `powercat` SET `title` = '頁尾文字', `orders` = '40' WHERE `powercat`.`id` = 112;
    UPDATE `powercat` SET `title` = '職稱&部門', `orders` = '5' WHERE `powercat`.`id` = 73;
    UPDATE `powercat` SET `title` = '合約列表' WHERE `powercat`.`id` = 57;
    UPDATE `powercat` SET `title` = '績效管理' WHERE `powercat`.`id` = 105;
    UPDATE `powercat` SET `title` = '績效設定', `orders` = '2' WHERE `powercat`.`id` = 106;
    UPDATE `powercat` SET `title` = '績效列表', `orders` = '0' WHERE `powercat`.`id` = 108;
    UPDATE `powercat` SET `title` = '分組設定' WHERE `powercat`.`id` = 58;

/*2022-06-06 記錄小事處理日期*/
    ALTER TABLE `crm_chats` ADD `do_time` VARCHAR(10) NOT NULL DEFAULT '' COMMENT '處理日期' AFTER `do_response`;
/*2022-06-08 修改訪談預設值*/
    ALTER TABLE `crm_chats` 
    CHANGE `chattype` `chattype` SMALLINT(1) NOT NULL DEFAULT '1' COMMENT '访谈方式 0.面談 1.電訪', 
    CHANGE `chattype2` `chattype2` SMALLINT(1) NOT NULL DEFAULT '1' COMMENT '預約方式 0.外訪 1.致電';
    UPDATE `eip_company` SET `version` = '8.0' WHERE `eip_company`.`id` = 1;
/*2022-06-15 加入推播功能*/
    CREATE TABLE `subscription` (
      `id` int(11) NOT NULL,
      `user_id` int(11) NOT NULL DEFAULT 0 COMMENT '會員id',
      `contentEncoding` text CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
      `endpoint` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
      `expirationTime` text CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
      `auth` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT '需組合成keys',
      `p256dh` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT '需組合成keys',
      `online` tinyint(4) NOT NULL DEFAULT 1 COMMENT '狀態 1.啟用 0.停用'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    ALTER TABLE `subscription`
      ADD PRIMARY KEY (`id`);
    ALTER TABLE `subscription`
      MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
    ALTER TABLE `subscription` CHANGE `user_id` `user_id` INT(11) NOT NULL DEFAULT '0' COMMENT '員工id';

/*2022-06-16 補上產業別權限*/
    ALTER TABLE `access` 
    ADD `industr_new` FLOAT NOT NULL DEFAULT '0' COMMENT '產業別新增' AFTER `crmproperty_del`, 
    ADD `industr_red` FLOAT NOT NULL DEFAULT '0' COMMENT '產業別指定閱讀 ' AFTER `industr_new`, 
    ADD `industr_edi` FLOAT NOT NULL DEFAULT '0' COMMENT '產業別修改 ' AFTER `industr_red`, 
    ADD `industr_del` FLOAT NOT NULL DEFAULT '0' COMMENT '產業別刪除' AFTER `industr_edi`;
    UPDATE `access` SET 
    `industr_new` = '1', 
    `industr_red` = '1', 
    `industr_edi` = '1', 
    `industr_del` = '1' 
    WHERE `access`.`id` = 1;

/*2022-06-24 調整參數控制檔案、使用phpmailer寄信*/
    UPDATE `eip_company` SET `version` = '8.1' WHERE `eip_company`.`id` = 1;

/*2022-07-05 調整往返單類別顯示機制*/
    UPDATE `eve_back_flow` SET `status` = '0' WHERE `eve_back_flow`.`id` = 1;

/*2022-07-12 調整往返單內容長度*/
    ALTER TABLE `wrong_job` CHANGE `content` `content` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;

/*2022-08-17 KM管理可自行新增刪除編輯文章類別*/
    INSERT INTO `powercat` (`id`, `level`, `islevel`, `parent_id`, `title`, `codenamed`, `link`, `description`, `orders`, `status`, `create_time`, `update_time`, `readself`, `readall`, `newcate`, `updatecate`, `delcate`, `truncate`) VALUES (NULL, '2', '1', '111', 'KM管理設定', 'Kmsetting', NULL, '', '45', '1', '0', '0', '1', '1', '1', '1', '1', '1');
    ALTER TABLE `access` 
    ADD `kmsetting_new` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'KM管理類別新增' AFTER `industr_del`, 
    ADD `kmsetting_red` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'KM管理類別讀取' AFTER `kmsetting_new`, 
    ADD `kmsetting_edi` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'KM管理類別編輯' AFTER `kmsetting_red`, 
    ADD `kmsetting_del` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'KM管理類別真刪除' AFTER `kmsetting_edi`;
    UPDATE `access` SET 
    `kmsetting_new` = '1', 
    `kmsetting_red` = '1', 
    `kmsetting_edi` = '1', 
    `kmsetting_del` = '1'
    WHERE `access`.`id` = 1;
    UPDATE `powercat` SET description = UPPER(SUBSTRING(`codenamed`,1,2)) WHERE parent_id = 92 OR id=89;
    CREATE TABLE `km_types` ( `id` INT NOT NULL , `title` VARCHAR(128) NOT NULL , `codenamed` VARCHAR(64) NOT NULL , `description` VARCHAR(10) NOT NULL , `orders` INT NOT NULL ) ENGINE = InnoDB;
    ALTER TABLE `km_types` ADD PRIMARY KEY( `id`);
    ALTER TABLE `km_types` CHANGE id  id int(11) AUTO_INCREMENT;
    ALTER TABLE `km_types` 
    CHANGE `title` `title` VARCHAR(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL, 
    CHANGE `codenamed` `codenamed` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL, 
    CHANGE `description` `description` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;
    INSERT INTO `km_types` (`id`, `title`, `codenamed`, `description`, `orders`) VALUES 
    (NULL, '我的文件', 'File', 'FI', '0'),
    (NULL, '教育訓練', 'Train', 'TR', '1'),
    (NULL, '會計部', 'Accountant', 'AC', '2'),
    (NULL, '業務部', 'Business', 'BU', '3'),
    (NULL, '企劃部', 'Plan', 'PL', '4'),
    (NULL, '設計部', 'Design', 'DE', '5'),
    (NULL, '工程部', 'Engineering', 'EN', '6'),
    (NULL, 'SEO部', 'Seoapart', 'SE', '7'),
    (NULL, '重要文件 ', 'Important', 'IM', '8');
    ALTER TABLE `km_types` ADD `status` TINYINT(1) NOT NULL DEFAULT '1' AFTER `orders`;
    ALTER TABLE `km_types` ADD `parent_id` INT NOT NULL DEFAULT '92' COMMENT '上級目錄' AFTER `status`;
    ALTER TABLE `km_types` ADD UNIQUE( `description`);
    UPDATE `powercat` SET `link` = '/index.php/km/index/type/FI.html' WHERE `powercat`.`id` = 92;
    DELETE FROM `powercat` WHERE `powercat`.`id` = 99;
    DELETE FROM `powercat` WHERE `powercat`.`id` = 98;
    DELETE FROM `powercat` WHERE `powercat`.`id` = 93;
    DELETE FROM `powercat` WHERE `powercat`.`id` = 94;
    DELETE FROM `powercat` WHERE `powercat`.`id` = 95;
    DELETE FROM `powercat` WHERE `powercat`.`id` = 96;
    DELETE FROM `powercat` WHERE `powercat`.`id` = 97;
    DELETE FROM `powercat` WHERE `powercat`.`id` = 100;
    DELETE FROM `powercat` WHERE `powercat`.`id` = 101;
    ALTER TABLE `access`
      DROP `important_new`,
      DROP `seoapart_new`,
      DROP `engineering_new`,
      DROP `design_new`,
      DROP `business_new`,
      DROP `accountant_new`,
      DROP `train_new`,
      DROP `plan_new`,
      DROP `file_new`,
      DROP `important_edi`,
      DROP `seoapart_edi`,
      DROP `engineering_edi`,
      DROP `design_edi`,
      DROP `business_edi`,
      DROP `accountant_edi`,
      DROP `train_edi`,
      DROP `plan_edi`,
      DROP `file_edi`,
      DROP `important_hid`,
      DROP `seoapart_hid`,
      DROP `engineering_hid`,
      DROP `design_hid`,
      DROP `business_hid`,
      DROP `accountant_hid`,
      DROP `train_hid`,
      DROP `plan_hid`,
      DROP `file_hid`,
      DROP `important_del`,
      DROP `seoapart_del`,
      DROP `engineering_del`,
      DROP `design_del`,
      DROP `business_del`,
      DROP `accountant_del`,
      DROP `train_del`,
      DROP `plan_del`,
      DROP `file_del`,
      DROP `important_red`,
      DROP `important_all`,
      DROP `seoapart_red`,
      DROP `seoapart_all`,
      DROP `engineering_red`,
      DROP `engineering_all`,
      DROP `design_red`,
      DROP `design_all`,
      DROP `business_red`,
      DROP `business_all`,
      DROP `accountant_red`,
      DROP `accountant_all`,
      DROP `train_red`,
      DROP `train_all`,
      DROP `plan_red`,
      DROP `plan_all`,
      DROP `file_red`,
      DROP `file_all`;
    ALTER TABLE `access`
      DROP `administrative_new`,
      DROP `administrative_red`,
      DROP `administrative_edi`,
      DROP `administrative_hid`,
      DROP `administrative_del`,
      DROP `administrative_all`,
      DROP `_new`,
      DROP `_red`,
      DROP `_edi`,
      DROP `_hid`,
      DROP `_del`,
      DROP `_all`;
    ALTER TABLE `access` ADD `km_access` TEXT NULL DEFAULT NULL COMMENT 'KM權限' AFTER `name`;
    ALTER TABLE `km_types` ADD `type` VARCHAR(32) NOT NULL DEFAULT 'km' COMMENT '選單類型' AFTER `parent_id`;
    DELETE FROM `powercat` WHERE `powercat`.`id` = 89;
    INSERT INTO `km_types` (`id`, `title`, `codenamed`, `description`, `orders`, `status`, `parent_id`, `type`) VALUES 
    (NULL, '公司規章', 'Administrative', 'AD', '6', '1', '1', 'km');
    UPDATE `powercat` SET `orders` = '10' WHERE `powercat`.`id` = 90;
    UPDATE `powercat` SET `orders` = '15' WHERE `powercat`.`id` = 91;
    UPDATE `powercat` SET `orders` = '20' WHERE `powercat`.`id` = 110;
    ALTER TABLE `powercat` ADD `type` VARCHAR(32) NOT NULL DEFAULT 'normal' COMMENT '選單類型' AFTER `codenamed`;
    ALTER TABLE `file` CHANGE `date` `date` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT '建立時間';
    ALTER TABLE `file` CHANGE `type` `type` INT(10) NOT NULL COMMENT '月日選單時間(不計時分秒，也用於計算文章數)';

/*2022-08-18 商品管理*/
    INSERT INTO `powercat` 
    (`id`, `level`, `islevel`, `parent_id`, `title`, `codenamed`, `type`, `link`, `description`, `orders`, `status`, `create_time`, `update_time`, `readself`, `readall`, `newcate`, `updatecate`, `delcate`, `truncate`) VALUES 
    (NULL, '2', '1', '111', '商品管理', 'Product', 'normal', NULL, '', '37', '1', '0', '0', '1', '1', '1', '1', '1', '1');
    ALTER TABLE `crm_cum_cat_unit` DROP `cat_id`;
    ALTER TABLE `crm_cum_cat_unit`  
    ADD `number` VARCHAR(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '商品代號'  AFTER `name`,  
    ADD `type` VARCHAR(265) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '規格'  AFTER `number`,  
    ADD `list_price` VARCHAR(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '網路價'  AFTER `type`,  
    ADD `sale_price` VARCHAR(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '售價'  AFTER `list_price`,  
    ADD `inventory` VARCHAR(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '庫存'  AFTER `sale_price`,  
    ADD `profit` FLOAT NOT NULL DEFAULT '0' COMMENT '業績BV'  AFTER `inventory`;
    ALTER TABLE `crm_cum_cat_unit` CHANGE `name` `name` VARCHAR(265) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT '品名';
    ALTER TABLE `access` 
    ADD `product_new` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '商品管理新增' AFTER `kmsetting_del`, 
    ADD `product_red` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '商品管理讀取' AFTER `product_new`, 
    ADD `product_edi` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '商品管理編輯' AFTER `product_red`, 
    ADD `product_hid` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '商品管理真刪除' AFTER `product_edi`;
    UPDATE `access` SET 
    `product_new` = '1', 
    `product_red` = '1', 
    `product_edi` = '1', 
    `product_hid` = '1'
    WHERE `access`.`id` = 1;

    /*2022-08-19 合約對應商品關係調整*/
        CREATE TABLE `crm_contract_unit` (
          `id` int(11) NOT NULL,
          `cat_unit_id` int(11) NOT NULL COMMENT 'crm_cum_cat_unit表ID',
          `pid` int(11) NOT NULL COMMENT 'crm_contract表ID',
          `name` varchar(265) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT '品名',
          `number` varchar(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '商品代號',
          `type` varchar(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '規格',
          `list_price` varchar(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '網路價',
          `sale_price` varchar(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '售價',
          `inventory` varchar(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '庫存',
          `profit` float NOT NULL DEFAULT 0 COMMENT '業績BV',
          `num` int(11) NOT NULL DEFAULT 0 COMMENT '數量',
          `total` int(11) NOT NULL DEFAULT 0 COMMENT '金額',
          `total_dis` int(11) NOT NULL DEFAULT 0 COMMENT '優惠總價',
          `cost` int(11) NOT NULL DEFAULT 0 COMMENT '成本'
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1;
        ALTER TABLE `crm_contract_unit`
          ADD PRIMARY KEY (`id`);
        ALTER TABLE `crm_contract_unit`
          MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

        INSERT INTO crm_contract_unit
        (`id`, `cat_unit_id`, `pid`, `name`, `num`, `total`, `total_dis`, `cost`) 
        SELECT null AS id,
               outunit1 AS cat_unit_id,
               pid, 
               cccu.name AS name,
               1 as num,
               income1 AS total,
               income1 AS total_dis,
               outcome1 AS cost
        FROM crm_contract_other AS cco
        LEFT JOIN crm_cum_cat_unit AS cccu ON cccu.id = cco.outunit1
        WHERE cco.outunit1!=0;
        INSERT INTO crm_contract_unit
        (`id`, `cat_unit_id`, `pid`, `name`, `num`, `total`, `total_dis`, `cost`) 
        SELECT null AS id,
               outunit2 AS cat_unit_id,
               pid, 
               cccu.name AS name,
               1 as num,
               income2 AS total,
               income2 AS total_dis,
               outcome2 AS cost
        FROM crm_contract_other AS cco
        LEFT JOIN crm_cum_cat_unit AS cccu ON cccu.id = cco.outunit2
        WHERE cco.outunit2!=0;
        INSERT INTO crm_contract_unit
        (`id`, `cat_unit_id`, `pid`, `name`, `num`, `total`, `total_dis`, `cost`) 
        SELECT null AS id,
               outunit3 AS cat_unit_id,
               pid, 
               cccu.name AS name,
               1 as num,
               income3 AS total,
               income3 AS total_dis,
               outcome3 AS cost
        FROM crm_contract_other AS cco
        LEFT JOIN crm_cum_cat_unit AS cccu ON cccu.id = cco.outunit3
        WHERE cco.outunit3!=0;
        INSERT INTO crm_contract_unit
        (`id`, `cat_unit_id`, `pid`, `name`, `num`, `total`, `total_dis`, `cost`) 
        SELECT null AS id,
               outunit4 AS cat_unit_id,
               pid, 
               cccu.name AS name,
               1 as num,
               income4 AS total,
               income4 AS total_dis,
               outcome4 AS cost
        FROM crm_contract_other AS cco
        LEFT JOIN crm_cum_cat_unit AS cccu ON cccu.id = cco.outunit4
        WHERE cco.outunit4!=0;
        INSERT INTO crm_contract_unit
        (`id`, `cat_unit_id`, `pid`, `name`, `num`, `total`, `total_dis`, `cost`) 
        SELECT null AS id,
               outunit5 AS cat_unit_id,
               pid, 
               cccu.name AS name,
               1 as num,
               income5 AS total,
               income5 AS total_dis,
               outcome5 AS cost
        FROM crm_contract_other AS cco
        LEFT JOIN crm_cum_cat_unit AS cccu ON cccu.id = cco.outunit5
        WHERE cco.outunit5!=0;
        INSERT INTO crm_contract_unit
        (`id`, `cat_unit_id`, `pid`, `name`, `num`, `total`, `total_dis`, `cost`) 
        SELECT null AS id,
               outunit6 AS cat_unit_id,
               pid, 
               cccu.name AS name,
               1 as num,
               income6 AS total,
               income6 AS total_dis,
               outcome6 AS cost
        FROM crm_contract_other AS cco
        LEFT JOIN crm_cum_cat_unit AS cccu ON cccu.id = cco.outunit6
        WHERE cco.outunit6!=0;
        INSERT INTO crm_contract_unit
        (`id`, `cat_unit_id`, `pid`, `name`, `num`, `total`, `total_dis`, `cost`) 
        SELECT null AS id,
               outunit7 AS cat_unit_id,
               pid, 
               cccu.name AS name,
               1 as num,
               income7 AS total,
               income7 AS total_dis,
               outcome7 AS cost
        FROM crm_contract_other AS cco
        LEFT JOIN crm_cum_cat_unit AS cccu ON cccu.id = cco.outunit7
        WHERE cco.outunit7!=0;
        INSERT INTO crm_contract_unit
        (`id`, `cat_unit_id`, `pid`, `name`, `num`, `total`, `total_dis`, `cost`) 
        SELECT null AS id,
               outunit8 AS cat_unit_id,
               pid, 
               cccu.name AS name,
               1 as num,
               income8 AS total,
               income8 AS total_dis,
               outcome8 AS cost
        FROM crm_contract_other AS cco
        LEFT JOIN crm_cum_cat_unit AS cccu ON cccu.id = cco.outunit8
        WHERE cco.outunit8!=0;
        INSERT INTO crm_contract_unit
        (`id`, `cat_unit_id`, `pid`, `name`, `num`, `total`, `total_dis`, `cost`) 
        SELECT null AS id,
               outunit9 AS cat_unit_id,
               pid, 
               cccu.name AS name,
               1 as num,
               income9 AS total,
               income9 AS total_dis,
               outcome9 AS cost
        FROM crm_contract_other AS cco
        LEFT JOIN crm_cum_cat_unit AS cccu ON cccu.id = cco.outunit9
        WHERE cco.outunit9!=0;
        INSERT INTO crm_contract_unit
        (`id`, `cat_unit_id`, `pid`, `name`, `num`, `total`, `total_dis`, `cost`) 
        SELECT null AS id,
               outunit10 AS cat_unit_id,
               pid, 
               cccu.name AS name,
               1 as num,
               income10 AS total,
               income10 AS total_dis,
               outcome10 AS cost
        FROM crm_contract_other AS cco
        LEFT JOIN crm_cum_cat_unit AS cccu ON cccu.id = cco.outunit10
        WHERE cco.outunit10!=0;
        DROP TABLE `crm_contract_other`;
    /*2022-08-23 調整出貨資料格式*/
        ALTER TABLE `crm_shipment` CHANGE `num` `num` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;
        ALTER TABLE `crm_shipment` DROP `we_code`;
        ALTER TABLE `crm_shipment` CHANGE `num` `num` VARCHAR(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;
        ALTER TABLE `crm_shipment` CHANGE `date` `date` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '批號(對應print_othertxt)';
    /*2022-08-23 添加出貨單欄位*/
        ALTER TABLE `print_othertxt` 
        ADD `sale_code` VARCHAR(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '銷貨編號' AFTER `bz`, 
        ADD `shipping_code` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '宅配單號' AFTER `sale_code`, 
        ADD `shipping_date` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '到貨日期' AFTER `shipping_code`, 
        ADD `receive_name` VARCHAR(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '代收人姓名' AFTER `shipping_date`, 
        ADD `receive_phone` VARCHAR(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '代收人電話/手機' AFTER `receive_name`, 
        ADD `phone_time` VARCHAR(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '來電時間' AFTER `receive_phone`, 
        ADD `bank` VARCHAR(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '銀行' AFTER `phone_time`, 
        ADD `card` VARCHAR(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '卡號/帳號' AFTER `bank`, 
        ADD `card_end_code` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '持卡人' AFTER `card`, 
        ADD `card_name` VARCHAR(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '未三碼' AFTER `card_end_code`, 
        ADD `card_date` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '有效期限' AFTER `card_name`, 
        ADD `card_period` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '分幾期' AFTER `card_date`, 
        ADD `invoice_code` VARCHAR(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '發票號' AFTER `card_period`;
        ALTER TABLE `print_othertxt` CHANGE `qh` `date` CHAR(10) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT '批號(對應crm_shipment)';
        ALTER TABLE `print_othertxt` ADD INDEX(`date`);
        update print_othertxt
        set print_othertxt.date = (
             select crm_shipment.date
             from crm_shipment 
             where print_othertxt.moneyid = crm_shipment.moneyid AND print_othertxt.moneyid!=0
             group by print_othertxt.id
        )
        where exists (
             select 1
             from crm_shipment
             where print_othertxt.moneyid = crm_shipment.moneyid AND print_othertxt.moneyid!=0
             group by print_othertxt.id
        );
        ALTER TABLE `print_othertxt` DROP `moneyid`;
    /*2022-08-24 合併 print_txt*/
        INSERT INTO print_othertxt
        (`id`, `date`, `caseid`, `txt`, `examine`, `bz`) 
        SELECT null AS id,
               CONCAT(qh, '-1') AS date,
               caseid, 
               txt,
               examine,
               bz
        FROM print_txt;
        DROP TABLE `print_txt`;
        ALTER TABLE print_othertxt RENAME print_txt;

/*2022-08-25 調整客戶轉換統計紀錄*/
    DELETE FROM `salesrecord` WHERE `salesrecord`.`ctype` = '-1';
    
/*2022-08-29 資料庫控制送貨單內容*/
    UPDATE `system_parameter` SET `data` = '{\r\n   \"負責人\": \"負責人\",\r\n   \"聯絡人\": \"聯絡人\",\r\n   \"協同人員\": \"協同人員\",\r\n \"特性管理\": \"特性管理\",\r\n \"訪談紀錄\": \"訪談紀錄\",\r\n \"小事\": \"小事\",\r\n \"綜合事項\": \"綜合事項\",\r\n \"合約歷程\": \"合約歷程\",\r\n \"事件列表\": \"事件列表\",\r\n \"公司資訊\": \"公司資訊\",\r\n \"網群\": \"網群\",\r\n \"請款週期\": \"請款週期\",\r\n \"收付款方式\": \"收付款方式\",\r\n   \"空間流量追蹤\": \"空間流量追蹤\",\r\n \"官方網站\": \"官方網站\",\r\n \"品牌網站\": \"品牌網站\",\r\n \"員工人數\": \"員工人數\",\r\n \"經濟部替代匯入\": \"經濟部替代匯入\",\r\n\r\n   \"客戶\": \"客戶\",\r\n \"名稱\": \"名稱\",\r\n \"職稱\": \"職稱/暱稱\",\r\n  \"簡稱\": \"簡稱\",\r\n \"統編\": \"統編\",\r\n \"地址\": \"地址\",\r\n \"傳真\": \"傳真\",\r\n \"起算日期\": \"起算日期\",\r\n \"產業別\": \"產業別\",\r\n   \"產業次項\": \"產業次項\",\r\n \"類別\": \"類別\",\r\n \"等級\": \"等級\",\r\n \"來源\": \"來源\",\r\n \"資本額\": \"資本額\",\r\n   \"關係企業\": \"關係企業\",\r\n \"業務分析\": \"業務分析\",\r\n \"特別說明\": \"特別說明\",\r\n \"拜訪地址\": \"拜訪地址\",\r\n \"會計地址\": \"會計地址\",\r\n \"出貨地址\": \"出貨地址\",\r\n \"工廠地址\": \"工廠地址\",\r\n \"登記地址\": \"登記地址\",\r\n\r\n \"公司名稱\": \"公司名稱\",\r\n \"公司電話\": \"電話\",\r\n   \"公司手機\": \"手機\",\r\n   \"公司MAIL\": \"MAIL\",\r\n   \"公司核准日\": \"核准日\",\r\n \"公司LINE_FB\": \"LINE / FB\",\r\n   \"公司LINE\": \"LINE\",\r\n   \"公司FB\": \"FB\",\r\n   \"公司備註\": \"備註\",\r\n\r\n   \"負責人暱稱\": \"姓名\",\r\n  \"負責人電話\": \"電話\",\r\n  \"負責人手機\": \"手機\",\r\n  \"負責人MAIL\": \"MAIL\",\r\n  \"負責人生日\": \"生日\",\r\n  \"負責人LINE_FB\": \"LINE / FB\",\r\n  \"負責人LINE\": \"LINE\",\r\n  \"負責人FB\": \"FB\",\r\n  \"負責人備註\": \"備註\",\r\n\r\n  \"聯絡人暱稱\": \"姓名\",\r\n  \"聯絡人電話\": \"電話\",\r\n  \"聯絡人手機\": \"手機\",\r\n  \"聯絡人MAIL\": \"MAIL\",\r\n  \"聯絡人生日\": \"生日\",\r\n  \"聯絡人LINE_FB\": \"LINE / FB\",\r\n  \"聯絡人LINE\": \"LINE\",\r\n  \"聯絡人FB\": \"FB\",\r\n  \"聯絡人備註\": \"備註\",\r\n\r\n  \"送貨地址\":\"地址\",\r\n    \"送貨單額外資訊\":\"\"\r\n}' WHERE `system_parameter`.`id` = 1;
/*2022-08-31 文章調整*/
    UPDATE `powercat` SET `description` = 'IN' WHERE `powercat`.`id` = 90;
    UPDATE `powercat` SET `description` = 'EX' WHERE `powercat`.`id` = 91;
    UPDATE `powercat` SET `description` = 'CO' WHERE `powercat`.`id` = 110;
/*2022-08-31 system_parameter修改*/
    UPDATE `system_parameter` SET `data` = '{\r\n   \"負責人\": \"負責人\",\r\n   \"聯絡人\": \"聯絡人\",\r\n   \"協同人員\": \"協同人員\",\r\n \"特性管理\": \"特性管理\",\r\n \"訪談紀錄\": \"訪談紀錄\",\r\n \"小事\": \"小事\",\r\n \"綜合事項\": \"綜合事項\",\r\n \"合約歷程\": \"合約歷程\",\r\n \"事件列表\": \"事件列表\",\r\n \"公司資訊\": \"公司資訊\",\r\n \"網群\": \"網群\",\r\n \"請款週期\": \"請款週期\",\r\n \"收付款方式\": \"收付款方式\",\r\n   \"空間流量追蹤\": \"空間流量追蹤\",\r\n \"官方網站\": \"官方網站\",\r\n \"品牌網站\": \"品牌網站\",\r\n \"員工人數\": \"員工人數\",\r\n \"經濟部替代匯入\": \"經濟部替代匯入\",\r\n\r\n   \"客戶\": \"客戶\",\r\n \"名稱\": \"名稱\",\r\n \"職稱\": \"職稱/暱稱\",\r\n  \"簡稱\": \"簡稱\",\r\n \"統編\": \"統編\",\r\n \"地址\": \"地址\",\r\n \"傳真\": \"傳真\",\r\n \"起算日期\": \"起算日期\",\r\n \"產業別\": \"產業別\",\r\n   \"產業次項\": \"產業次項\",\r\n \"類別\": \"類別\",\r\n \"等級\": \"等級\",\r\n \"來源\": \"來源\",\r\n \"資本額\": \"資本額\",\r\n   \"關係企業\": \"關係企業\",\r\n \"業務分析\": \"業務分析\",\r\n \"特別說明\": \"特別說明\",\r\n \"拜訪地址\": \"拜訪地址\",\r\n \"會計地址\": \"會計地址\",\r\n \"出貨地址\": \"出貨地址\",\r\n \"工廠地址\": \"工廠地址\",\r\n \"登記地址\": \"登記地址\",\r\n\r\n \"公司名稱\": \"公司名稱\",\r\n \"公司電話\": \"電話\",\r\n   \"公司手機\": \"手機\",\r\n   \"公司MAIL\": \"MAIL\",\r\n   \"公司核准日\": \"核准日\",\r\n \"公司LINE_FB\": \"LINE / FB\",\r\n   \"公司LINE\": \"LINE\",\r\n   \"公司FB\": \"FB\",\r\n   \"公司備註\": \"備註\",\r\n\r\n   \"負責人暱稱\": \"姓名\",\r\n  \"負責人電話\": \"電話\",\r\n  \"負責人手機\": \"手機\",\r\n  \"負責人MAIL\": \"MAIL\",\r\n  \"負責人生日\": \"生日\",\r\n  \"負責人LINE_FB\": \"LINE / FB\",\r\n  \"負責人LINE\": \"LINE\",\r\n  \"負責人FB\": \"FB\",\r\n  \"負責人備註\": \"備註\",\r\n\r\n  \"聯絡人暱稱\": \"姓名\",\r\n  \"聯絡人電話\": \"電話\",\r\n  \"聯絡人手機\": \"手機\",\r\n  \"聯絡人MAIL\": \"MAIL\",\r\n  \"聯絡人生日\": \"生日\",\r\n  \"聯絡人LINE_FB\": \"LINE / FB\",\r\n  \"聯絡人LINE\": \"LINE\",\r\n  \"聯絡人FB\": \"FB\",\r\n  \"聯絡人備註\": \"備註\",\r\n\r\n  \"送貨地址\":\"地址\",\r\n    \"送貨單額外資訊\":\"\",\r\n   \"合約\":\"合約\"\r\n}' WHERE `system_parameter`.`id` = 1;
    INSERT INTO `system_parameter` (`id`, `data`, `note`) VALUES (NULL, '1', '是否啟用匯出客戶功能 1.啟用 0.停用');

/*2022-09-06 system_parameter修改，可控商品管理網路價、售價*/
    UPDATE `system_parameter` SET `data` = '{\r\n   \"負責人\": \"負責人\",\r\n   \"聯絡人\": \"聯絡人\",\r\n   \"協同人員\": \"協同人員\",\r\n \"特性管理\": \"特性管理\",\r\n \"訪談紀錄\": \"訪談紀錄\",\r\n \"小事\": \"小事\",\r\n \"綜合事項\": \"綜合事項\",\r\n \"合約歷程\": \"合約歷程\",\r\n \"事件列表\": \"事件列表\",\r\n \"公司資訊\": \"公司資訊\",\r\n \"網群\": \"網群\",\r\n \"請款週期\": \"請款週期\",\r\n \"收付款方式\": \"收付款方式\",\r\n   \"空間流量追蹤\": \"空間流量追蹤\",\r\n \"官方網站\": \"官方網站\",\r\n \"品牌網站\": \"品牌網站\",\r\n \"員工人數\": \"員工人數\",\r\n \"經濟部替代匯入\": \"經濟部替代匯入\",\r\n\r\n   \"客戶\": \"客戶\",\r\n \"名稱\": \"名稱\",\r\n \"職稱\": \"職稱/暱稱\",\r\n  \"簡稱\": \"簡稱\",\r\n \"統編\": \"統編\",\r\n \"地址\": \"地址\",\r\n \"傳真\": \"傳真\",\r\n \"起算日期\": \"起算日期\",\r\n \"產業別\": \"產業別\",\r\n   \"產業次項\": \"產業次項\",\r\n \"類別\": \"類別\",\r\n \"等級\": \"等級\",\r\n \"來源\": \"來源\",\r\n \"資本額\": \"資本額\",\r\n   \"關係企業\": \"關係企業\",\r\n \"業務分析\": \"業務分析\",\r\n \"特別說明\": \"特別說明\",\r\n \"拜訪地址\": \"拜訪地址\",\r\n \"會計地址\": \"會計地址\",\r\n \"出貨地址\": \"出貨地址\",\r\n \"工廠地址\": \"工廠地址\",\r\n \"登記地址\": \"登記地址\",\r\n\r\n \"公司名稱\": \"公司名稱\",\r\n \"公司電話\": \"電話\",\r\n   \"公司手機\": \"手機\",\r\n   \"公司MAIL\": \"MAIL\",\r\n   \"公司核准日\": \"核准日\",\r\n \"公司LINE_FB\": \"LINE / FB\",\r\n   \"公司LINE\": \"LINE\",\r\n   \"公司FB\": \"FB\",\r\n   \"公司備註\": \"備註\",\r\n\r\n   \"負責人暱稱\": \"姓名\",\r\n  \"負責人電話\": \"電話\",\r\n  \"負責人手機\": \"手機\",\r\n  \"負責人MAIL\": \"MAIL\",\r\n  \"負責人生日\": \"生日\",\r\n  \"負責人LINE_FB\": \"LINE / FB\",\r\n  \"負責人LINE\": \"LINE\",\r\n  \"負責人FB\": \"FB\",\r\n  \"負責人備註\": \"備註\",\r\n\r\n  \"聯絡人暱稱\": \"姓名\",\r\n  \"聯絡人電話\": \"電話\",\r\n  \"聯絡人手機\": \"手機\",\r\n  \"聯絡人MAIL\": \"MAIL\",\r\n  \"聯絡人生日\": \"生日\",\r\n  \"聯絡人LINE_FB\": \"LINE / FB\",\r\n  \"聯絡人LINE\": \"LINE\",\r\n  \"聯絡人FB\": \"FB\",\r\n  \"聯絡人備註\": \"備註\",\r\n\r\n  \"商品網路價\":\"網路價\",\r\n  \"商品售價\":\"售價\",\r\n    \"送貨地址\":\"地址\",\r\n    \"送貨單額外資訊\":\"\",\r\n   \"合約\":\"合約\"\r\n}' WHERE `system_parameter`.`id` = 1;
/*2022-09-08 system_parameter更改預設值*/
    UPDATE `system_parameter` SET `data` = '{\r\n   \"負責人\": \"負責人\",\r\n   \"聯絡人\": \"聯絡人\",\r\n   \"協同人員\": \"協同人員\",\r\n \"特性管理\": \"特性管理\",\r\n \"訪談紀錄\": \"訪談紀錄\",\r\n \"小事\": \"小事\",\r\n \"綜合事項\": \"綜合事項\",\r\n \"合約歷程\": \"合約歷程\",\r\n \"事件列表\": \"事件列表\",\r\n \"公司資訊\": \"公司資訊\",\r\n \"網群\": \"網群\",\r\n \"請款週期\": \"請款週期\",\r\n \"收付款方式\": \"收付款方式\",\r\n   \"空間流量追蹤\": \"\",\r\n   \"官方網站\": \"官方網站\",\r\n \"品牌網站\": \"品牌網站\",\r\n \"員工人數\": \"員工人數\",\r\n \"經濟部替代匯入\": \"經濟部替代匯入\",\r\n\r\n   \"客戶\": \"客戶\",\r\n \"名稱\": \"名稱\",\r\n \"職稱\": \"職稱/暱稱\",\r\n  \"簡稱\": \"簡稱\",\r\n \"統編\": \"統編\",\r\n \"地址\": \"地址\",\r\n \"傳真\": \"傳真\",\r\n \"起算日期\": \"起算日期\",\r\n \"產業別\": \"產業別\",\r\n   \"產業次項\": \"產業次項\",\r\n \"類別\": \"類別\",\r\n \"等級\": \"等級\",\r\n \"來源\": \"來源\",\r\n \"資本額\": \"資本額\",\r\n   \"關係企業\": \"關係企業\",\r\n \"業務分析\": \"業務分析\",\r\n \"特別說明\": \"特別說明\",\r\n \"拜訪地址\": \"拜訪地址\",\r\n \"會計地址\": \"會計地址\",\r\n \"出貨地址\": \"出貨地址\",\r\n \"工廠地址\": \"工廠地址\",\r\n \"登記地址\": \"登記地址\",\r\n\r\n \"公司名稱\": \"公司名稱\",\r\n \"公司電話\": \"電話\",\r\n   \"公司手機\": \"手機\",\r\n   \"公司MAIL\": \"MAIL\",\r\n   \"公司核准日\": \"核准日\",\r\n \"公司LINE_FB\": \"LINE / FB\",\r\n   \"公司LINE\": \"LINE\",\r\n   \"公司FB\": \"FB\",\r\n   \"公司備註\": \"備註\",\r\n\r\n   \"負責人暱稱\": \"姓名\",\r\n  \"負責人電話\": \"電話\",\r\n  \"負責人手機\": \"手機\",\r\n  \"負責人MAIL\": \"MAIL\",\r\n  \"負責人生日\": \"生日\",\r\n  \"負責人LINE_FB\": \"LINE / FB\",\r\n  \"負責人LINE\": \"LINE\",\r\n  \"負責人FB\": \"FB\",\r\n  \"負責人備註\": \"備註\",\r\n\r\n  \"聯絡人暱稱\": \"姓名\",\r\n  \"聯絡人電話\": \"電話\",\r\n  \"聯絡人手機\": \"手機\",\r\n  \"聯絡人MAIL\": \"MAIL\",\r\n  \"聯絡人生日\": \"生日\",\r\n  \"聯絡人LINE_FB\": \"LINE / FB\",\r\n  \"聯絡人LINE\": \"LINE\",\r\n  \"聯絡人FB\": \"FB\",\r\n  \"聯絡人備註\": \"備註\",\r\n\r\n  \"商品網路價\":\"\",\r\n \"商品售價\":\"\",\r\n  \"送貨地址\":\"地址\",\r\n    \"送貨單額外資訊\":\"\",\r\n   \"合約\":\"合約\"\r\n}' WHERE `system_parameter`.`id` = 1;

/*2022-09-13 紀錄訪談當下的客戶類別*/
    ALTER TABLE `crm_chats` ADD `current_typeid` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '訪談當下客戶類別' AFTER `color_class`;
/*2022-09-13 紀錄查看處理回覆的時間*/
    ALTER TABLE `crm_chats` ADD `do_review_time` VARCHAR(10) NULL DEFAULT NULL COMMENT '查看回覆時間' AFTER `do_time`;
/*2022-09-13 限制新增員工人數*/
    ALTER TABLE `eip_user_right_type` ADD `limit_num` INT NULL DEFAULT '-1' COMMENT '人數上限 -1為無上限' AFTER `num`;
    UPDATE `eip_user_right_type` SET `limit_num` = '1' WHERE `eip_user_right_type`.`id` = 0;

/*2022-09-16 匯入客戶加入郵遞區號欄位，需刪除舊匯入資料*/
    TRUNCATE `im_importclient`;

/*2022-09-22 客戶總數加上限*/
    INSERT INTO `system_parameter` (`id`, `data`, `note`) VALUES (8, '-1', '客戶數上限');
    ALTER TABLE `im_importclient` CHANGE `status` `status` INT(11) NULL DEFAULT NULL COMMENT '狀態 11.操作人 0.操作中 1.待處理 2.獨立客';

/*2022-11-15 可開關線上簽核*/
    INSERT INTO `system_parameter` (`id`, `data`, `note`) VALUES (9, '1', '是否啟用線上簽名 0.停用 1.啟用');
/*2022-11-16 可開關商品管理*/
    -- 預設商品項目
    INSERT INTO `crm_cum_cat_unit` 
    (`id`, `name`, `number`, `type`, `list_price`, `sale_price`, `inventory`, `profit`, `orders`, `status`) VALUES 
    (1, '公司服務', '', NULL, NULL, NULL, NULL, '0', '0', '0');
/*2022-11-30 修改事件簿合約編號問題*/
    UPDATE `eve_events` SET case_name = REPLACE(REPLACE(case_name, ' ', ''), "\r\n", '');

/*2022-03-06 合併預收款至請款管理*/
/*本次更新完要執一次 /index.php/Getmoney/set_print_txt_moneyid再使用*/
    ALTER TABLE `crm_contract`
      DROP `exptime`,
      DROP `c_getedflag`,
      DROP `invoice_no`,
      DROP `tips`,
      DROP `shipment_note`,
      DROP `shipment_invoice`,
      DROP `shipment_status`;
    ALTER TABLE `crm_contract` DROP `outnote`;
    ALTER TABLE `crm_seomoney` ADD `prepaid` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '是否為預收 0.否 1.是' AFTER `caseid`;
    ALTER TABLE `crm_othermoney` ADD `prepaid` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '是否為預收 0.否 1.是' AFTER `caseid`;
    ALTER TABLE `crm_shipment` DROP `date`;
    ALTER TABLE `crm_shipment` ADD `date` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '年/月(計算批號用)' AFTER `num`;
    UPDATE `crm_shipment` SET `date` = CONCAT(SUBSTRING(FROM_UNIXTIME(`time`),1, 4),'/',SUBSTRING(FROM_UNIXTIME(`time`),6, 2));
    ALTER TABLE `crm_shipment` CHANGE `count` `count` INT(10) NOT NULL DEFAULT '0' COMMENT '次(計算批號用)';
    ALTER TABLE `crm_shipment` CHANGE `moneyid` `moneyid` INT(11) NOT NULL COMMENT '對應請款id';
    ALTER TABLE `print_txt` ADD `moneyid` INT NOT NULL DEFAULT '0' COMMENT '對應請款id' AFTER `caseid`;
    ALTER TABLE `crm_seomoney` CHANGE `dqmoney` `dqmoney` DECIMAL(10,2) NOT NULL COMMENT '當期出貨金額';
    ALTER TABLE `crm_seomoney` 
        CHANGE `qh` `qh` CHAR(10) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT '年/月(批號)' AFTER `caseid`, 
        CHANGE `count` `count` INT(10) NOT NULL COMMENT '次(批號)' AFTER `qh`;
    ALTER TABLE `crm_othermoney` CHANGE `dqmoney` `dqmoney` DECIMAL(10,2) NOT NULL COMMENT '當期出貨金額';
    ALTER TABLE `crm_othermoney` 
        CHANGE `qh` `qh` CHAR(10) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT '年/月(批號)' AFTER `caseid`, 
        CHANGE `count` `count` INT(10) NOT NULL COMMENT '次(批號)' AFTER `qh`;
    ALTER TABLE `crm_shipment` CHANGE `examine` `examine` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '0.未出貨 1.出貨';
    ALTER TABLE `crm_shipment` ADD `prepaid` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '是否為預收 0.否 1.是' AFTER `moneyid`;
    ALTER TABLE `crm_othermoney` ADD `xqj_tax` DECIMAL(10,2) NOT NULL DEFAULT '0.00' COMMENT '期金稅金' AFTER `xqj`;
    UPDATE `crm_othermoney` set xqj_tax = fax;
    ALTER TABLE `crm_seomoney` ADD `xqj_tax` DECIMAL(10,2) NOT NULL DEFAULT '0.00' COMMENT '期金稅金' AFTER `xqj`;
    UPDATE `crm_seomoney` set xqj_tax = fax;
    ALTER TABLE `crm_othermoney` ADD `ship_status` TINYINT(1) NOT NULL DEFAULT '1' COMMENT '完成款項設定 0.否 1.完成' AFTER `prepaid`;
    ALTER TABLE `crm_seomoney` ADD `ship_status` TINYINT(1) NOT NULL DEFAULT '1' COMMENT '完成款項設定 0.否 1.完成' AFTER `prepaid`;
    ALTER TABLE `crm_shipment`
      DROP `prepaid`,
      DROP `examine`,
      DROP `cflag`,
      DROP `date`,
      DROP `count`;
    ALTER TABLE `crm_shipment`
     CHANGE `moneyid` `moneyid` INT(11) NOT NULL COMMENT '對應請款id' AFTER `caseid`,
     CHANGE `name` `name` VARCHAR(36) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '产品名称' AFTER `moneyid`,
     CHANGE `money` `money` FLOAT(10,2) NOT NULL COMMENT '金额' AFTER `name`,
     CHANGE `content` `content` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL AFTER `money`,
     CHANGE `num` `num` VARCHAR(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL AFTER `content`;
    ALTER TABLE `print_txt` DROP `examine`;
    ALTER TABLE `print_txt` 
        CHANGE `txt` `txt` TEXT CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT NULL COMMENT '可以打印的出貨單內容', 
        CHANGE `bz` `bz` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '出貨單注意事項';
    ALTER TABLE `print_txt` CHANGE `date` `date` CHAR(10) CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT NULL COMMENT '舊對應欄位,已棄用';

    DELETE FROM `powercat` WHERE `powercat`.`id` = 102;
    ALTER TABLE `access`
      DROP `prepaid_red`,
      DROP `prepaid_edi`,
      DROP `prepaid_all`;

    UPDATE `crm_cum_type` SET `sort` = '1' WHERE `crm_cum_type`.`id` = 1;
    UPDATE `crm_cum_type` SET `sort` = '3' WHERE `crm_cum_type`.`id` = 3;
    UPDATE `crm_cum_type` SET `sort` = '4' WHERE `crm_cum_type`.`id` = 4;
    UPDATE `crm_cum_type` SET `sort` = '5' WHERE `crm_cum_type`.`id` = 5;

    ALTER TABLE `crm_seomoney` CHANGE `xqj` `xqj` DECIMAL(10,2) NULL DEFAULT NULL COMMENT '消期金';
    ALTER TABLE `crm_seomoney` DROP INDEX `caseid_2`;
    ALTER TABLE `crm_seomoney` ADD UNIQUE( `caseid`, `qh`, `count`);
    ALTER TABLE `crm_seomoney` ADD `qh_seo` CHAR(10) CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT NULL COMMENT '抓取SEO內容' AFTER `count`;
    UPDATE `crm_seomoney` SET `qh_seo` = `qh`;

    DELETE FROM `powercat` WHERE `powercat`.`id` = 69;
    ALTER TABLE `access`
      DROP `sale_new`,
      DROP `sale_red`,
      DROP `sale_edi`,
      DROP `sale_del`,
      DROP `sale_all`;
    ALTER TABLE `access`
      DROP `getmoney_hid`,
      DROP `crecords_new`,
      DROP `crecords_hid`,
      DROP `crecords_del`;
    ALTER TABLE `access` DROP `course_new`;

/*2023-03-22 自動出帳提醒修改、電子發票開立*/
    ALTER TABLE `crm_othermoney` ADD `queryflag_time_remind` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '核可請款日期(提醒用) ' AFTER `queryflag_time`;
    ALTER TABLE `crm_seomoney` ADD `queryflag_time_remind` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '核可請款日期(提醒用) ' AFTER `queryflag_time`;
    UPDATE `crm_othermoney` SET `queryflag_time_remind` = `queryflag_time`;
    UPDATE `crm_seomoney` SET `queryflag_time_remind` = `queryflag_time`;
    ALTER TABLE `crm_othermoney` CHANGE `queryflag_time` `queryflag_time` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '核可請款日期';
    ALTER TABLE `crm_seomoney` CHANGE `queryflag_time` `queryflag_time` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '核可請款日期';
    ALTER TABLE `crm_seomoney` ADD `ticket_rand` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '發票隨機碼' AFTER `ticket`;

/*2023-03-23 設定管理調整*/
    ALTER TABLE `powercat` CHANGE `description` `description` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '描述(文章類用於紀錄文號，算未讀數用)';
    UPDATE `powercat` SET `level` = `islevel`;
    UPDATE `powercat` SET `codenamed` = '', `link` = NULL WHERE parent_id=0;
    UPDATE `powercat` SET `codenamed` = '' WHERE `powercat`.`id` = 104;
    UPDATE `powercat` SET `codenamed` = 'Marketing' WHERE `powercat`.`id` = 104;
    ALTER TABLE `powercat` DROP `islevel`;
    ALTER TABLE `powercat` CHANGE `level` `level` TINYINT(10) UNSIGNED NOT NULL COMMENT '階層數';
    INSERT INTO `powercat` 
    (`id`, `level`, `parent_id`, `title`, `codenamed`, `type`, `link`, `description`, `orders`, `status`, `create_time`, `update_time`, `readself`, `readall`, `newcate`, `updatecate`, `delcate`, `truncate`) VALUES 
    ('122', '1', '111', '客戶管理', '', 'normal', NULL, '', '0', '1', '0', '0', '0', '0', '0', '0', '0', '0'),
    ('123', '1', '111', '專案管理', '', 'normal', NULL, '', '5', '1', '0', '0', '0', '0', '0', '0', '0', '0'),
    ('124', '1', '111', 'KM管理', '', 'normal', NULL, '', '10', '1', '0', '0', '0', '0', '0', '0', '0', '0'),
    ('125', '1', '111', '收款管理', '', 'normal', NULL, '', '15', '1', '0', '0', '0', '0', '0', '0', '0', '0'),
    ('126', '1', '111', '付款管理', '', 'normal', NULL, '', '20', '1', '0', '0', '0', '0', '0', '0', '0', '0'),
    ('127', '1', '111', '資源管理', '', 'normal', NULL, '', '25', '1', '0', '0', '0', '0', '0', '0', '0', '0'),
    ('128', '1', '111', 'SEO管理', '', 'normal', NULL, '', '30', '1', '0', '0', '0', '0', '0', '0', '0', '0'),
    ('129', '1', '111', '人事行政', '', 'normal', NULL, '', '35', '1', '0', '0', '0', '0', '0', '0', '0', '0'),
    ('130', '1', '111', '績效管理', '', 'normal', NULL, '', '40', '1', '0', '0', '0', '0', '0', '0', '0', '0'),
    ('131', '1', '111', '綜合管理', '', 'normal', NULL, '', '45', '1', '0', '0', '0', '0', '0', '0', '0', '0');
    UPDATE `powercat` SET `parent_id` = '122', `level` = 2 WHERE `powercat`.`id` = 114;
    UPDATE `powercat` SET `parent_id` = '122', `level` = 2 WHERE `powercat`.`id` = 115;
    UPDATE `powercat` SET `parent_id` = '122', `level` = 2 WHERE `powercat`.`id` = 116;
    UPDATE `powercat` SET `parent_id` = '124', `level` = 2 WHERE `powercat`.`id` = 120;
    UPDATE `powercat` SET `parent_id` = '125', `level` = 2 WHERE `powercat`.`id` = 117;
    UPDATE `powercat` SET `parent_id` = '125', `level` = 2 WHERE `powercat`.`id` = 121;
    UPDATE `powercat` SET `parent_id` = '125', `level` = 2 WHERE `powercat`.`id` = 118;
    UPDATE `powercat` SET `parent_id` = '129', `level` = 2 WHERE `powercat`.`id` = 73;
    UPDATE `powercat` SET `parent_id` = '131', `level` = 2 WHERE `powercat`.`id` = 113;
    UPDATE `powercat` SET `parent_id` = '131', `level` = 2 WHERE `powercat`.`id` = 112;
    UPDATE `powercat` SET `parent_id` = '131', `level` = 2 WHERE `powercat`.`id` = 119;

    UPDATE `powercat` SET `parent_id` = '123', `level` = 2 WHERE `powercat`.`id` = 68;
    UPDATE `powercat` SET `title` = '事件模組設定' WHERE `powercat`.`id` = 68;

    ALTER TABLE `access` 
        ADD `cumpri_red` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '協同人員指定瀏覽' AFTER `product_hid`, 
        ADD `cumpri_edi` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '協同人員可編輯' AFTER `cumpri_red`;
    ALTER TABLE `access` 
        ADD `salesetting_red` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '出貨備註指定瀏覽' AFTER `cumpri_edi`, 
        ADD `salesetting_edi` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '出貨備註可編輯' AFTER `salesetting_red`;

/*2023-03-23 設定管理-收款管理*/
    UPDATE `powercat` SET `orders` = '30' WHERE `powercat`.`id` = 121;
    UPDATE `powercat` SET `orders` = '35' WHERE `powercat`.`id` = 118;
    INSERT INTO `powercat` 
        (`id`, `level`, `parent_id`, `title`, `codenamed`, `type`, `link`, `description`, `orders`, `status`, `create_time`, `update_time`, `readself`, `readall`, `newcate`, `updatecate`, `delcate`, `truncate`) VALUES 
        ('132', '2', '125', '進項會計名稱', 'AccountantIn', 'normal', NULL, '', '40', '1', '0', '0', '1', '1', '1', '1', '1', '1');
    ALTER TABLE `access` 
        ADD `accountantin_new` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '進項會計名稱可新增' AFTER `salesetting_edi`, 
        ADD `accountantin_red` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '進項會計名稱指定瀏覽' AFTER `accountantin_new`, 
        ADD `accountantin_edi` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '進項會計名稱可編輯' AFTER `accountantin_red`,
        ADD `accountantin_hid` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '進項會計名稱可丟棄' AFTER `accountantin_edi`;
    CREATE TABLE `accountant_in` ( 
        `id` INT NOT NULL , 
        `parent_id` INT NOT NULL DEFAULT '0' COMMENT '父階層id' , 
        `name` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '名稱' , 
        `order_id` INT(11) NOT NULL DEFAULT '0' COMMENT '排序' , 
        `status` TINYINT(1) NOT NULL DEFAULT '1' COMMENT '狀態 1.使用中 0.刪除' 
    ) ENGINE = InnoDB;
    ALTER TABLE `accountant_in` ADD PRIMARY KEY( `id`);
    ALTER TABLE `accountant_in` CHANGE id id int(11) AUTO_INCREMENT;
    ALTER TABLE `crm_cum_cat_unit` ADD `accountant_id` INT(11) NOT NULL DEFAULT '0' COMMENT '對應進項會計id' AFTER `id`;

/*2023-03-28 設定管理-人事行政*/
    INSERT INTO `powercat` 
    (`id`, `level`, `parent_id`, `title`, `codenamed`, `type`, `link`, `description`, `orders`, `status`, `create_time`, `update_time`, `readself`, `readall`, `newcate`, `updatecate`, `delcate`, `truncate`) VALUES 
    ('133', '2', '129', '勞保級距', 'InsuranceLabor', 'normal', NULL, '', '10', '1', '0', '0', '0', '0', '0', '0', '0', '0'),
    ('134', '2', '129', '健保級距', 'InsuranceHealth', 'normal', NULL, '', '15', '1', '0', '0', '0', '0', '0', '0', '0', '0');
    ALTER TABLE `access` 
        ADD `insurancelabor_new` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '勞保級距可新增' AFTER `accountantin_hid`, 
        ADD `insurancelabor_red` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '勞保級距指定瀏覽' AFTER `insurancelabor_new`, 
        ADD `insurancelabor_hid` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '勞保級距可丟棄' AFTER `insurancelabor_red`;
    ALTER TABLE `access` 
        ADD `insurancehealth_new` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '健保級距可新增' AFTER `insurancelabor_hid`, 
        ADD `insurancehealth_red` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '健保級距指定瀏覽' AFTER `insurancehealth_new`, 
        ADD `insurancehealth_hid` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '健保級距可丟棄' AFTER `insurancehealth_red`;
    CREATE TABLE `insurance_labor` (
     `id` INT NOT NULL , 
     `top_limit` INT NOT NULL COMMENT '級距上限(含等於)' , 
     `price` INT NOT NULL COMMENT '金額' 
    ) ENGINE = InnoDB;
    ALTER TABLE `insurance_labor` ADD PRIMARY KEY( `id`);
    ALTER TABLE `insurance_labor` CHANGE id id int(11) AUTO_INCREMENT;
    ALTER TABLE `insurance_labor` ADD `type` TINYINT(1) NOT NULL DEFAULT '1' COMMENT '種類 0.打工 1.正職' AFTER `price`;
    CREATE TABLE `insurance_health` (
     `id` INT NOT NULL , 
     `price` INT NOT NULL COMMENT '月投保金額金額',
     `person` INT NOT NULL COMMENT '本人',
     `person_1` INT NOT NULL COMMENT '本人+1 眷口',
     `person_2` INT NOT NULL COMMENT '本人+2 眷口',
     `person_3` INT NOT NULL COMMENT '本人+3 眷口',
     `company_num` INT NOT NULL COMMENT '投保單位負擔金額',
     `government_num` INT NOT NULL COMMENT '政府補助金額'
    ) ENGINE = InnoDB;
    ALTER TABLE `insurance_health` ADD PRIMARY KEY( `id`);
    ALTER TABLE `insurance_health` CHANGE id id int(11) AUTO_INCREMENT;

    INSERT INTO `powercat` 
    (`id`, `level`, `parent_id`, `title`, `codenamed`, `type`, `link`, `description`, `orders`, `status`, `create_time`, `update_time`, `readself`, `readall`, `newcate`, `updatecate`, `delcate`, `truncate`) VALUES 
    ('135', '2', '129', '勞退級距', 'LaborRetired', 'normal', NULL, '', '20', '1', '0', '0', '0', '0', '0', '0', '0', '0');
    ALTER TABLE `access` 
        ADD `laborretired_new` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '勞退級距可新增' AFTER `insurancehealth_hid`, 
        ADD `laborretired_red` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '勞退級距指定瀏覽' AFTER `laborretired_new`, 
        ADD `laborretired_hid` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '勞退級距可丟棄' AFTER `laborretired_red`;
    CREATE TABLE `labor_retired` (
     `id` INT NOT NULL , 
     `price` INT NOT NULL COMMENT '投保級距',
     `insurance_labor_0` INT NOT NULL COMMENT '勞保+就保+災保(個人)',
     `insurance_labor_1` INT NOT NULL COMMENT '勞保+就保+災保(單位)',
     `insurance_health_0` INT NOT NULL COMMENT '健保費(個人)',
     `insurance_health_1` INT NOT NULL COMMENT '健保費(單位)',
     `labor_retired_0` INT NOT NULL COMMENT '勞工退休金(個人)',
     `labor_retired_1` INT NOT NULL COMMENT '勞工退休金(單位)'
    ) ENGINE = InnoDB;
    ALTER TABLE `labor_retired` ADD PRIMARY KEY( `id`);
    ALTER TABLE `labor_retired` CHANGE id id int(11) AUTO_INCREMENT;

    INSERT INTO `powercat` 
    (`id`, `level`, `parent_id`, `title`, `codenamed`, `type`, `link`, `description`, `orders`, `status`, `create_time`, `update_time`, `readself`, `readall`, `newcate`, `updatecate`, `delcate`, `truncate`) VALUES 
    ('136', '2', '129', '特休設定', 'SpecialRest', 'normal', NULL, '', '25', '1', '0', '0', '0', '0', '0', '0', '0', '0');
    ALTER TABLE `access` 
        ADD `specialrest_new` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '特休設定可新增' AFTER `laborretired_hid`, 
        ADD `specialrest_red` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '特休設定指定瀏覽' AFTER `specialrest_new`, 
        ADD `specialrest_hid` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '特休設定可丟棄' AFTER `specialrest_red`;
    CREATE TABLE `special_rest` (
     `id` INT NOT NULL , 
     `seniority` float NOT NULL COMMENT '年資(年)',
     `rest_day` INT NOT NULL COMMENT '天數'
    ) ENGINE = InnoDB;
    ALTER TABLE `special_rest` ADD PRIMARY KEY( `id`);
    ALTER TABLE `special_rest` CHANGE id id int(11) AUTO_INCREMENT;

    INSERT INTO `powercat` 
    (`id`, `level`, `parent_id`, `title`, `codenamed`, `type`, `link`, `description`, `orders`, `status`, `create_time`, `update_time`, `readself`, `readall`, `newcate`, `updatecate`, `delcate`, `truncate`) VALUES 
    ('137', '2', '129', '假種設定', 'RestType', 'normal', NULL, '', '30', '1', '0', '0', '0', '0', '0', '0', '0', '0');
    ALTER TABLE `access` 
        ADD `resttype_new` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '假種設定可新增' AFTER `specialrest_hid`, 
        ADD `resttype_red` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '假種設定指定瀏覽' AFTER `resttype_new`, 
        ADD `resttype_hid` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '假種設定可丟棄' AFTER `resttype_red`;
    CREATE TABLE `rest_type` (
     `id` INT NOT NULL , 
     `name` TEXT CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT '假種名稱',
     `deduct_percent` INT NOT NULL COMMENT '每小時扣薪(%)',
     `month_limit` INT NOT NULL COMMENT '每月上限(小時)',
     `order_id` INT(11) NOT NULL DEFAULT '0' COMMENT '排序' , 
     `status` TINYINT(1) NOT NULL DEFAULT '1' COMMENT '狀態 1.使用中 0.刪除' 
    ) ENGINE = InnoDB;
    ALTER TABLE `rest_type` ADD PRIMARY KEY( `id`);
    ALTER TABLE `rest_type` CHANGE id id int(11) AUTO_INCREMENT;
    INSERT INTO `rest_type` (`id`, `name`, `deduct_percent`, `month_limit`, `order_id`, `status`) VALUES (1, '特休', '0', '999', '0', '1');

    ALTER TABLE `access` ADD `demo_red` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '系統操作紀錄指定瀏覽' AFTER `resttype_hid`;
    ALTER TABLE `error_log` ADD `ip` VARCHAR(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT 'IP位置' AFTER `eid`;
    ALTER TABLE `error_log` CHANGE `ip` `ip` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT 'IP位置';

/*2023-03-30 設定管理-付款管理*/
    INSERT INTO `powercat` 
        (`id`, `level`, `parent_id`, `title`, `codenamed`, `type`, `link`, `description`, `orders`, `status`, `create_time`, `update_time`, `readself`, `readall`, `newcate`, `updatecate`, `delcate`, `truncate`) VALUES 
        ('138', '2', '126', '出項會計名稱', 'AccountantOut', 'normal', NULL, '', '15', '1', '0', '0', '1', '1', '1', '1', '1', '1');
    ALTER TABLE `access` 
        ADD `accountantout_new` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '出項會計名稱可新增' AFTER `demo_red`, 
        ADD `accountantout_red` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '出項會計名稱指定瀏覽' AFTER `accountantout_new`, 
        ADD `accountantout_edi` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '出項會計名稱可編輯' AFTER `accountantout_red`,
        ADD `accountantout_hid` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '出項會計名稱可丟棄' AFTER `accountantout_edi`;
    CREATE TABLE `accountant_out` ( 
        `id` INT NOT NULL , 
        `parent_id` INT NOT NULL DEFAULT '0' COMMENT '父階層id' , 
        `name` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '名稱' , 
        `order_id` INT(11) NOT NULL DEFAULT '0' COMMENT '排序' , 
        `status` TINYINT(1) NOT NULL DEFAULT '1' COMMENT '狀態 1.使用中 0.刪除' 
    ) ENGINE = InnoDB;
    ALTER TABLE `accountant_out` ADD PRIMARY KEY( `id`);
    ALTER TABLE `accountant_out` CHANGE id id int(11) AUTO_INCREMENT;
    INSERT INTO `accountant_out` 
        (`id`, `parent_id`, `name`, `order_id`, `status`) VALUES 
        (1, '0', '薪資', '0', '1'), 
        (2, '1', '本薪', '0', '1'), 
        (3, '1', '獎金', '0', '1');
/*2023-03-30 設定管理-獎金名目*/
    INSERT INTO `powercat` 
    (`id`, `level`, `parent_id`, `title`, `codenamed`, `type`, `link`, `description`, `orders`, `status`, `create_time`, `update_time`, `readself`, `readall`, `newcate`, `updatecate`, `delcate`, `truncate`) VALUES 
    ('139', '2', '129', '獎金名目設定', 'BonusType', 'normal', NULL, '', '35', '1', '0', '0', '0', '0', '0', '0', '0', '0');
    ALTER TABLE `access` 
        ADD `bonustype_new` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '獎金名目設定可新增' AFTER `accountantout_hid`, 
        ADD `bonustype_red` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '獎金名目設定指定瀏覽' AFTER `bonustype_new`, 
        ADD `bonustype_hid` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '獎金名目設定可丟棄' AFTER `bonustype_red`;
    CREATE TABLE `bonus_type` (
     `id` INT NOT NULL , 
     `name` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '名稱' , 
     `order_id` INT(11) NOT NULL DEFAULT '0' COMMENT '排序' , 
     `status` TINYINT(1) NOT NULL DEFAULT '1' COMMENT '狀態 1.使用中 0.刪除' 
    ) ENGINE = InnoDB;
    ALTER TABLE `bonus_type` ADD PRIMARY KEY( `id`);
    ALTER TABLE `bonus_type` CHANGE id id int(11) AUTO_INCREMENT;

    ALTER TABLE `access` 
        ADD `resttype_edi` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '假種設定可編輯' AFTER `resttype_red`;
/*2023-03-30 人事管理-假勤資訊*/
    CREATE TABLE `rest_records` (
     `id` INT NOT NULL , 
     `user_id` INT(11) NOT NULL DEFAULT '0' COMMENT '員工id' , 
     `rest_type_id` INT(11) NOT NULL DEFAULT '0' COMMENT '假別' , 
     `rest_day_s` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '開始日期' , 
     `rest_day_e` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '結束日期' , 
     `hours` INT(11) NOT NULL DEFAULT '0' COMMENT '請假時數' , 
     `reason` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '事由' , 
     `job_agent` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '職務代理人' , 
     `examiner` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '審核人員' , 
     `note` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '備註' 
    ) ENGINE = InnoDB;
    ALTER TABLE `rest_records` ADD PRIMARY KEY( `id`);
    ALTER TABLE `rest_records` CHANGE id id int(11) AUTO_INCREMENT;
/*2023-03-31 人事管理-特休紀錄*/
    CREATE TABLE `special_rest_accumulation` (
     `id` INT NOT NULL , 
     `user_id` INT(11) NOT NULL DEFAULT '0' COMMENT '員工id' , 
     `seniority` float NOT NULL COMMENT '年資(年)',
     `rest_day` INT(11) NOT NULL DEFAULT '0' COMMENT '特休天數' , 
     `datetime` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '變動時間'
    ) ENGINE = InnoDB;
    ALTER TABLE `special_rest_accumulation` ADD PRIMARY KEY( `id`);
    ALTER TABLE `special_rest_accumulation` CHANGE id id int(11) AUTO_INCREMENT;
    ALTER TABLE `eip_user_data`
      DROP `leave`,
      DROP `payroll`;
/*2023-03-31 人事管理-薪資歷程*/
    CREATE TABLE `salary_records` (
     `id` INT NOT NULL , 
     `user_id` INT(11) NOT NULL DEFAULT '0' COMMENT '員工id' , 
     `day_s` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '開始日期' , 
     `pay_hour` INT(11) NOT NULL DEFAULT '0' COMMENT '時薪' , 
     `pay_month` INT(11) NOT NULL DEFAULT '0' COMMENT '月薪' , 
     `bonus` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '加給項目' , 
     `note` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '備註' 
    ) ENGINE = InnoDB;
    ALTER TABLE `salary_records` ADD PRIMARY KEY( `id`);
    ALTER TABLE `salary_records` CHANGE id id int(11) AUTO_INCREMENT;

/*2023-04-06 人事管理-匯薪列表*/
    UPDATE `powercat` SET `title` = '投保級距' WHERE `powercat`.`id` = 133;
    UPDATE `powercat` SET `codenamed` = 'InsuranceLevel' WHERE `powercat`.`id` = 133;
    RENAME TABLE `insurance_labor` TO `insurance_level`;
    ALTER TABLE `access` 
        CHANGE `insurancelabor_new` `insurancelevel_new` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '投保級距可新增', 
        CHANGE `insurancelabor_red` `insurancelevel_red` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '投保級距指定瀏覽', 
        CHANGE `insurancelabor_hid` `insurancelevel_hid` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '投保級距可丟棄';
    UPDATE `powercat` SET `title` = '勞保&勞退金額' WHERE `powercat`.`id` = 135;
    UPDATE `powercat` SET `codenamed` = 'InsuranceLabor' WHERE `powercat`.`id` = 135;
    ALTER TABLE `access` 
        CHANGE `laborretired_new` `insurancelabor_new` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '勞保&勞退金額可新增', 
        CHANGE `laborretired_red` `insurancelabor_red` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '勞保&勞退金額指定瀏覽', 
        CHANGE `laborretired_hid` `insurancelabor_hid` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '勞保&勞退金額可丟棄';
    RENAME TABLE `labor_retired` TO `insurance_labor`;
    UPDATE `powercat` SET `title` = '健保金額' WHERE `powercat`.`id` = 134;
    ALTER TABLE `access` 
        CHANGE `insurancehealth_new` `insurancehealth_new` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '健保金額可新增', 
        CHANGE `insurancehealth_red` `insurancehealth_red` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '健保金額指定瀏覽', 
        CHANGE `insurancehealth_hid` `insurancehealth_hid` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '健保金額可丟棄';
    ALTER TABLE `insurance_labor`
      DROP `insurance_health_0`,
      DROP `insurance_health_1`;
    TRUNCATE `insurance_level`;
    INSERT INTO `insurance_level` (`id`, `top_limit`, `price`, `type`) VALUES
    (null, 1500, 1500, 0), (null, 3000, 3000, 0), (null, 4500, 4500, 0), (null, 6000, 6000, 0), (null, 7500, 7500, 0), (null, 8700, 8700, 0), (null, 9900, 9900, 0), (null, 11100, 11100, 0), (null, 12540, 12540, 0), (null, 13500, 13500, 0), (null, 15840, 15840, 0), (null, 16500, 16500, 0), (null, 17280, 17280, 0), (null, 17880, 17880, 0), (null, 19047, 19047, 0), (null, 20008, 20008, 0), (null, 21009, 21009, 0), (null, 22000, 22000, 0), (null, 23100, 23100, 0), (null, 24000, 24000, 0), (null, 25250, 25250, 0), (null, 26400, 26400, 1), (null, 27600, 27600, 1), (null, 28800, 28800, 1), (null, 30300, 30300, 1), (null, 31800, 31800, 1), (null, 33300, 33300, 1), (null, 34800, 34800, 1), (null, 36300, 36300, 1), (null, 38200, 38200, 1), (null, 40100, 40100, 1), (null, 42000, 42000, 1), (null, 43900, 43900, 1), (null, 45800, 45800, 1), (null, 48200, 48200, 1), (null, 50600, 50600, 1), (null, 53000, 53000, 1), (null, 55400, 55400, 1), (null, 57800, 57800, 1), (null, 60800, 60800, 1), (null, 63800, 63800, 1), (null, 66800, 66800, 1), (null, 69800, 69800, 1), (null, 72800, 72800, 1), (null, 76500, 76500, 1), (null, 80200, 80200, 1), (null, 83900, 83900, 1), (null, 87600, 87600, 1), (null, 92100, 92100, 1), (null, 96600, 96600, 1), (null, 101100, 101100, 1), (null, 105600, 105600, 1), (null, 110100, 110100, 1), (null, 115500, 115500, 1), (null, 120900, 120900, 1), (null, 126300, 126300, 1), (null, 131700, 131700, 1), (null, 137100, 137100, 1), (null, 142500, 142500, 1), (null, 147900, 147900, 1), (null, 150000, 150000, 1), (null, 156400, 156400, 1), (null, 162800, 162800, 1), (null, 169200, 169200, 1), (null, 175600, 175600, 1), (null, 182000, 182000, 1), (null, 189500, 189500, 1), (null, 197000, 197000, 1), (null, 204500, 204500, 1), (null, 212000, 212000, 1), (null, 219500, 219500, 1);
    TRUNCATE `insurance_health`;
    INSERT INTO `insurance_health` 
    (`id`, `price`, `person`, `person_1`, `person_2`, `person_3`, `company_num`, `government_num`) VALUES 
    (null, '26400', '409', '818', '1227', '1636', '1286', '214'),
    (null, '27600', '428', '856', '1284', '1712', '1344', '224'),
    (null, '28800', '447', '894', '1341', '1788', '1403', '234'),
    (null, '30300', '470', '940', '1410', '1880', '1476', '246'),
    (null, '31800', '493', '986', '1479', '1972', '1549', '258'),
    (null, '33300', '516', '1032', '1548', '2064', '1622', '270'),
    (null, '34800', '540', '1080', '1620', '2160', '1695', '282'),
    (null, '36300', '563', '1126', '1689', '2252', '1768', '295'),
    (null, '38200', '592', '1184', '1776', '2368', '1860', '310'),
    (null, '40100', '622', '1244', '1866', '2488', '1953', '325'),
    (null, '42000', '651', '1302', '1953', '2604', '2045', '341'),
    (null, '43900', '681', '1362', '2043', '2724', '2138', '356'),
    (null, '45800', '710', '1420', '2130', '2840', '2231', '372'),
    (null, '48200', '748', '1496', '2244', '2992', '2347', '391'),
    (null, '50600', '785', '1570', '2355', '3140', '2464', '411'),
    (null, '53000', '822', '1644', '2466', '3288', '2581', '430'),
    (null, '55400', '859', '1718', '2577', '3436', '2698', '450'),
    (null, '57800', '896', '1792', '2688', '3584', '2815', '469'),
    (null, '60800', '943', '1886', '2829', '3772', '2961', '494'),
    (null, '63800', '990', '1980', '2970', '3960', '3107', '518'),
    (null, '66800', '1036', '2072', '3108', '4144', '3253', '542'),
    (null, '69800', '1083', '2166', '3249', '4332', '3399', '567'),
    (null, '72800', '1129', '2258', '3387', '4516', '3545', '591'),
    (null, '76500', '1187', '2374', '3561', '4748', '3726', '621'),
    (null, '80200', '1244', '2488', '3732', '4976', '3906', '651'),
    (null, '83900', '1301', '2602', '3903', '5204', '4086', '681'),
    (null, '87600', '1359', '2718', '4077', '5436', '4266', '711'),
    (null, '92100', '1428', '2856', '4284', '5712', '4485', '748'),
    (null, '96600', '1498', '2996', '4494', '5992', '4705', '784'),
    (null, '101100', '1568', '3136', '4704', '6272', '4924', '821'),
    (null, '105600', '1638', '3276', '4914', '6552', '5143', '857'),
    (null, '110100', '1708', '3416', '5124', '6832', '5362', '894'),
    (null, '115500', '1791', '3582', '5373', '7164', '5625', '938'),
    (null, '120900', '1875', '3750', '5625', '7500', '5888', '981'),
    (null, '126300', '1959', '3918', '5877', '7836', '6151', '1025'),
    (null, '131700', '2043', '4086', '6129', '8172', '6414', '1069'),
    (null, '137100', '2126', '4252', '6378', '8504', '6677', '1113'),
    (null, '142500', '2210', '4420', '6630', '8840', '6940', '1157'),
    (null, '147900', '2294', '4588', '6882', '9176', '7203', '1200'),
    (null, '150000', '2327', '4654', '6981', '9308', '7305', '1218'),
    (null, '156400', '2426', '4852', '7278', '9704', '7617', '1269'),
    (null, '162800', '2525', '5050', '7575', '10100', '7929', '1321'),
    (null, '169200', '2624', '5248', '7872', '10496', '8240', '1373'),
    (null, '175600', '2724', '5448', '8172', '10896', '8552', '1425'),
    (null, '182000', '2823', '5646', '8469', '11292', '8864', '1477'),
    (null, '189500', '2939', '5878', '8817', '11756', '9229', '1538'),
    (null, '197000', '3055', '6110', '9165', '12220', '9594', '1599'),
    (null, '204500', '3172', '6344', '9516', '12688', '9959', '1660'),
    (null, '212000', '3288', '6576', '9864', '13152', '10325', '1721'),
    (null, '219500', '3404', '6808', '10212', '13616', '10690', '1782');
    TRUNCATE `insurance_labor`;
    INSERT INTO `insurance_labor` 
    (`id`, `price`, `insurance_labor_0`, `insurance_labor_1`, `labor_retired_0`, `labor_retired_1`) VALUES 
    (NULL, '1500', '266', '933', '0', '90'),
    (NULL, '3000', '266', '933', '0', '180'),
    (NULL, '4500', '266', '933', '0', '270'),
    (NULL, '6000', '266', '933', '0', '360'),
    (NULL, '7500', '266', '933', '0', '450'),
    (NULL, '8700', '266', '933', '0', '522'),
    (NULL, '9900', '266', '933', '0', '675'),
    (NULL, '11100', '266', '933', '0', '675'),
    (NULL, '12540', '301', '1054', '0', '752'),
    (NULL, '13500', '324', '1135', '0', '810'),
    (NULL, '15840', '380', '1331', '0', '950'),
    (NULL, '16500', '396', '1387', '0', '990'),
    (NULL, '17280', '415', '1452', '0', '1037'),
    (NULL, '17880', '429', '1502', '0', '1073'),
    (NULL, '19047', '457', '1600', '0', '1143'),
    (NULL, '20008', '480', '1681', '0', '1200'),
    (NULL, '21009', '504', '1765', '0', '1261'),
    (NULL, '22000', '528', '1848', '0', '1320'),
    (NULL, '23100', '554', '1941', '0', '1386'),
    (NULL, '24000', '576', '2016', '0', '1440'),
    (NULL, '25250', '607', '2121', '0', '1515'),
    (NULL, '26400', '634', '2218', '0', '1584'),
    (NULL, '27600', '662', '2318', '0', '1656'),
    (NULL, '28800', '692', '2420', '0', '1728'),
    (NULL, '30300', '728', '2545', '0', '1818'),
    (NULL, '31800', '764', '2672', '0', '1908'),
    (NULL, '33300', '800', '2797', '0', '1998'),
    (NULL, '34800', '836', '2924', '0', '2088'),
    (NULL, '36300', '872', '3049', '0', '2178'),
    (NULL, '38200', '916', '3208', '0', '2292'),
    (NULL, '40100', '962', '3369', '0', '2406'),
    (NULL, '42000', '1008', '3528', '0', '2520'),
    (NULL, '43900', '1054', '3687', '0', '2634'),
    (NULL, '45800', '1100', '3848', '0', '2748'),
    (NULL, '48200', '1100', '3848', '0', '2892'),
    (NULL, '50600', '1100', '3848', '0', '3036'),
    (NULL, '53000', '1100', '3848', '0', '3180'),
    (NULL, '55400', '1100', '3848', '0', '3324'),
    (NULL, '57800', '1100', '3848', '0', '3468'),
    (NULL, '60800', '1100', '3848', '0', '3648'),
    (NULL, '63800', '1100', '3848', '0', '3828'),
    (NULL, '66800', '1100', '3848', '0', '4008'),
    (NULL, '69800', '1100', '3848', '0', '4188'),
    (NULL, '72800', '1100', '3848', '0', '4368'),
    (NULL, '76500', '1100', '3848', '0', '4590'),
    (NULL, '80200', '1100', '3848', '0', '4812'),
    (NULL, '83900', '1100', '3848', '0', '5034'),
    (NULL, '87600', '1100', '3848', '0', '5256'),
    (NULL, '92100', '1100', '3848', '0', '5526'),
    (NULL, '96600', '1100', '3848', '0', '5796'),
    (NULL, '101100', '1100', '3848', '0', '6066'),
    (NULL, '105600', '1100', '3848', '0', '6336'),
    (NULL, '110100', '1100', '3848', '0', '6606'),
    (NULL, '115500', '1100', '3848', '0', '6930'),
    (NULL, '120900', '1100', '3848', '0', '7254'),
    (NULL, '126300', '1100', '3848', '0', '7578'),
    (NULL, '131700', '1100', '3848', '0', '7902'),
    (NULL, '137100', '1100', '3848', '0', '8226'),
    (NULL, '142500', '1100', '3848', '0', '8550'),
    (NULL, '147900', '1100', '3848', '0', '8874'),
    (NULL, '150000', '1100', '3848', '0', '9000');
    TRUNCATE `special_rest`;
    INSERT INTO `special_rest` (`id`, `seniority`, `rest_day`) VALUES
    (NULL, 0.5, 3), (NULL, 1, 7), (NULL, 3, 14), (NULL, 2, 10), (NULL, 4, 14), (NULL, 5, 15), (NULL, 6, 15), (NULL, 7, 15), (NULL, 8, 15), (NULL, 9, 15), (NULL, 10, 16), (NULL, 11, 17), (NULL, 12, 18), (NULL, 13, 19), (NULL, 14, 20), (NULL, 15, 21), (NULL, 16, 22), (NULL, 17, 23), (NULL, 18, 24), (NULL, 19, 25), (NULL, 20, 26), (NULL, 21, 27), (NULL, 22, 28), (NULL, 23, 29), (NULL, 24, 30), (NULL, 25, 30);

    INSERT INTO `powercat` 
    (`id`, `level`, `parent_id`, `title`, `codenamed`, `type`, `link`, `description`, `orders`, `status`, `create_time`, `update_time`, `readself`, `readall`, `newcate`, `updatecate`, `delcate`, `truncate`) VALUES 
    ('140', '1', '1', '匯薪列表', 'Salary', 'normal', NULL, '', '5', '1', '0', '0', '0', '0', '0', '0', '0', '0');
    ALTER TABLE `access` 
        ADD `salary_red` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '匯薪列表指定瀏覽' AFTER `bonustype_hid`, 
        ADD `salary_edi` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '匯薪列表可編輯' AFTER `salary_red`, 
        ADD `salary_hid` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '匯薪列表可丟棄' AFTER `salary_edi`;
    CREATE TABLE `salary` (
     `id` INT NOT NULL , 
     `user_id` INT(11) NOT NULL DEFAULT '0' COMMENT '員工id' , 
     `year` INT(11) NOT NULL DEFAULT '0' COMMENT '年' , 
     `month` INT(11) NOT NULL DEFAULT '0' COMMENT '月' , 
     `pay_hour` INT(11) NOT NULL DEFAULT '0' COMMENT '時薪' , 
     `hour_count` INT(11) NOT NULL DEFAULT '0' COMMENT '時薪時數' , 
     `pay_month` INT(11) NOT NULL DEFAULT '0' COMMENT '月薪' , 
     `month_count` INT(11) NOT NULL DEFAULT '0' COMMENT '月薪月數' , 
     `bonus_detail` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '加給項目' , 
     `insurance_level` INT(11) NOT NULL DEFAULT '0' COMMENT '投保級距' , 
     `insurance_labor` INT(11) NOT NULL DEFAULT '0' COMMENT '勞保個人負擔' , 
     `insurance_labor_company` INT(11) NOT NULL DEFAULT '0' COMMENT '勞保公司負擔' , 
     `insurance_health` INT(11) NOT NULL DEFAULT '0' COMMENT '健保個人負擔' , 
     `insurance_health_company` INT(11) NOT NULL DEFAULT '0' COMMENT '健保公司負擔' , 
     `labor_retired` INT(11) NOT NULL DEFAULT '0' COMMENT '勞退個人負擔' , 
     `labor_retired_company` INT(11) NOT NULL DEFAULT '0' COMMENT '勞退公司負擔' , 
     `note` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '備註' ,
     `status` TINYINT(1) NOT NULL DEFAULT '1' COMMENT '狀態 1.使用中 0.刪除' 
    ) ENGINE = InnoDB;
    ALTER TABLE `salary` ADD PRIMARY KEY(`id`);
    ALTER TABLE `salary` CHANGE id id int(11) AUTO_INCREMENT;
    ALTER TABLE `salary` ADD `hour_count_detail` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '時薪客戶明細' AFTER `hour_count`;
    ALTER TABLE `salary` ADD `month_count_detail` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '月薪客戶明細' AFTER `month_count`;
    ALTER TABLE `salary` 
    ADD `total_salary` INT(11) NOT NULL DEFAULT '0' COMMENT '總本薪' AFTER `bonus_detail`, 
    ADD `total_bonus` INT(11) NOT NULL DEFAULT '0' COMMENT '總加給-本薪' AFTER `total_salary`,
    ADD `total_bonus_award` INT(11) NOT NULL DEFAULT '0' COMMENT '總加給-獎金' AFTER `total_bonus`;
    ALTER TABLE `salary` 
    ADD `insurance_level_type` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '投保類型 0.部分工時 1.全工時' AFTER `insurance_level`;

    DROP TABLE `salary_records`;
    CREATE TABLE `salary_records` (
      `id` int(11) NOT NULL,
      `user_id` int(11) NOT NULL DEFAULT 0 COMMENT '員工id',
      `day_s` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '開始日期',
      `pay_hour` int(11) NOT NULL DEFAULT 0 COMMENT '時薪',
      `pay_month` int(11) NOT NULL DEFAULT 0 COMMENT '月薪',
      `bonus` text COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '加給項目',
      `insurance_level` int(11) NOT NULL COMMENT '投保級距',
      `insurance_level_type` tinyint(1) NOT NULL DEFAULT 0 COMMENT '投保類型 0.部分工時 1.全工時',
      `insurance_health_dependents` tinyint(1) NOT NULL DEFAULT 0 COMMENT '健保眷屬數',
      `note` text COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '備註'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
    ALTER TABLE `salary_records` ADD PRIMARY KEY( `id`);
    ALTER TABLE `salary_records` CHANGE id id int(11) AUTO_INCREMENT;
    ALTER TABLE `salary` ADD `rest_detail` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '請假紀錄' AFTER `bonus_detail`;
    ALTER TABLE `salary` ADD `total_rest_deduct` INT(11) NOT NULL DEFAULT '0' COMMENT '請假總扣薪' AFTER `total_bonus`;
    ALTER TABLE `bonus_type` ADD `rest_deduct` TINYINT(1) NOT NULL DEFAULT '1' COMMENT '請假是否會扣除' AFTER `name`;
    ALTER TABLE `bonus_type` CHANGE `rest_deduct` `type` TINYINT(1) NOT NULL DEFAULT '1' COMMENT '加給類型 0.獎金 1.本薪';
    UPDATE `powercat` SET `title` = '加給名目設定' WHERE `powercat`.`id` = 139;
    ALTER TABLE `salary` ADD `total_pay_hour` INT(11) NOT NULL DEFAULT '0' COMMENT '總時薪' AFTER `rest_detail`;
    ALTER TABLE `salary` CHANGE `total_salary` `total_pay_month` INT(11) NOT NULL DEFAULT '0' COMMENT '總月薪';
    ALTER TABLE `salary` ADD `total_salary` INT(11) NOT NULL DEFAULT '0' COMMENT '總薪資' AFTER `total_rest_deduct`;
    ALTER TABLE `eip_user` 
        ADD `bank` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '薪資帳戶銀行' AFTER `resaddr`, 
        ADD `bank_code` VARCHAR(16) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '銀行分行代號' AFTER `bank`, 
        ADD `bank_account` VARCHAR(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '薪資帳戶帳號' AFTER `bank_code`;
    ALTER TABLE `salary` ADD `confirm_time` INT(10) NOT NULL DEFAULT '0' COMMENT '核可時間' AFTER `note`;
    INSERT INTO `accountant_out` 
        (`id`, `parent_id`, `name`, `order_id`, `status`) VALUES 
        (4, '0', '保險', '0', '1'), 
        (5, '4', '勞保費', '0', '1'), 
        (6, '4', '健保費', '0', '1'), 
        (7, '4', '勞退提撥', '0', '1');
    ALTER TABLE `salary` 
        ADD `month_day` INT NOT NULL DEFAULT '0' COMMENT '當月天數' AFTER `rest_detail`, 
        ADD `salary_base_hour` FLOAT NOT NULL DEFAULT '0' COMMENT '當月每小時本薪' AFTER `month_day`;
    ALTER TABLE `salary` CHANGE `total_salary` `total_salary` INT(11) NOT NULL DEFAULT '0' COMMENT '總薪資(本+獎-假)';
    ALTER TABLE `salary` CHANGE `month_count` `month_count` FLOAT(11) NOT NULL DEFAULT '0' COMMENT '月薪月數';

    -- 合約區分收款付款
        ALTER TABLE `crm_cum_cat` ADD `get_or_pay` TINYINT(0) NOT NULL DEFAULT '0' COMMENT '收款或付款 0.收款 1.付款' AFTER `id`;
        ALTER TABLE `access` ADD `crmcumcatpay_new` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '合約類別(付款)可新增' AFTER `product_hid`, 
        ADD `crmcumcatpay_red` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '合約類別(付款)可瀏覽' AFTER `crmcumcatpay_new`, 
        ADD `crmcumcatpay_edi` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '合約類別(付款)可編輯' AFTER `crmcumcatpay_red`, 
        ADD `crmcumcatpay_hid` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '合約類別(付款)可丟棄' AFTER `crmcumcatpay_edi`;
        ALTER TABLE `access` DROP `crmcumcat_del`;

        UPDATE `powercat` SET `title` = '收款合約' WHERE `powercat`.`id` = 56;
        UPDATE `powercat` SET `title` = '收款合約' WHERE `powercat`.`id` = 125;
        UPDATE `powercat` SET `title` = '付款合約' WHERE `powercat`.`id` = 126;
        UPDATE `powercat` SET `title` = '收款類別' WHERE `powercat`.`id` = 117;
        INSERT INTO `powercat` 
        (`id`, `level`, `parent_id`, `title`, `codenamed`, `type`, `link`, `description`, `orders`, `status`, `create_time`, `update_time`, `readself`, `readall`, `newcate`, `updatecate`, `delcate`, `truncate`) VALUES 
        (141, '2', '126', '付款類別', 'Crmcumcatpay', 'normal', NULL, '', '0', '1', '0', '0', '1', '1', '1', '1', '1', '1'),
        (142, '0', '0', '付款合約', '', 'normal', NULL, '可以的話請不要刪掉我', '4', '1', '0', '0', '0', '0', '0', '0', '0', '0');
        INSERT INTO `powercat` 
        (`id`, `level`, `parent_id`, `title`, `codenamed`, `type`, `link`, `description`, `orders`, `status`, `create_time`, `update_time`, `readself`, `readall`, `newcate`, `updatecate`, `delcate`, `truncate`) VALUES 
        (143, '1', '142', '新增合約', 'Conlistpay', 'normal', NULL, '', '0', '1', '0', '0', '0', '0', '1', '0', '0', '0'), 
        (144, '1', '142', '合約列表', 'Alllistpay', 'normal', NULL, '', '1', '1', '0', '0', '0', '0', '1', '0', '0', '0'), 
        (145, '1', '142', '請款管理', 'Getmoneypay', 'normal', NULL, '', '3', '1', '0', '0', '0', '0', '0', '0', '0', '0'), 
        (146, '1', '142', '付款紀錄', 'Crecordspay', 'normal', NULL, '', '4', '1', '0', '0', '0', '0', '0', '0', '0', '0'), 
        (147, '1', '142', '合約歷程', 'Coursepay', 'normal', NULL, '', '5', '1', '0', '0', '0', '0', '0', '0', '0', '0');
    -- 會計項目區分收款付款
        CREATE TABLE `accountant_item` ( 
            `id` INT NOT NULL , 
            `get_or_pay` TINYINT(0) NOT NULL DEFAULT '0' COMMENT '收款或付款 0.收款 1.付款',
            `parent_id` INT NOT NULL DEFAULT '0' COMMENT '父階層id' , 
            `name` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '名稱' , 
            `order_id` INT(11) NOT NULL DEFAULT '0' COMMENT '排序' , 
            `status` TINYINT(1) NOT NULL DEFAULT '1' COMMENT '狀態 1.使用中 0.刪除' 
        ) ENGINE = InnoDB;
        ALTER TABLE `accountant_item` ADD PRIMARY KEY( `id`);
        ALTER TABLE `accountant_item` CHANGE id id int(11) AUTO_INCREMENT;
        INSERT INTO `accountant_item` 
            (`id`, `get_or_pay`, `parent_id`, `name`, `order_id`, `status`) VALUES 
            (1, 1, '0', '薪資', '0', '1'), 
            (2, 1, '1', '本薪', '0', '1'), 
            (3, 1, '1', '獎金', '0', '1'),
            (4, 1, '0', '保險', '0', '1'), 
            (5, 1, '4', '勞保費', '0', '1'), 
            (6, 1, '4', '健保費', '0', '1'), 
            (7, 1, '4', '勞退提撥', '0', '1');
        DROP TABLE `accountant_in`, `accountant_out`;
    -- 商品區分收款付款
        ALTER TABLE `crm_cum_cat_unit` ADD `get_or_pay` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '收款或付款 0.收款 1.付款' AFTER `id`;
        INSERT INTO `powercat` 
        (`id`, `level`, `parent_id`, `title`, `codenamed`, `type`, `link`, `description`, `orders`, `status`, `create_time`, `update_time`, `readself`, `readall`, `newcate`, `updatecate`, `delcate`, `truncate`) VALUES 
        (148, '2', '126', '付款項目', 'Productpay', 'normal', NULL, '', '3', '1', '0', '0', '1', '1', '1', '1', '1', '1');
        ALTER TABLE `access` 
        ADD `productpay_new` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '付款項目新增' AFTER `salary_hid`, 
        ADD `productpay_red` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '付款項目讀取' AFTER `productpay_new`, 
        ADD `productpay_edi` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '付款項目編輯' AFTER `productpay_red`, 
        ADD `productpay_hid` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '付款項目丟棄' AFTER `productpay_edi`;
    -- 合約區分收付款
        ALTER TABLE `access`
          DROP `conlist_new`,
          DROP `conlist_edi`,
          DROP `conlist_hid`,
          DROP `conlist_del`;
        ALTER TABLE `access` 
        ADD `conlistpay_red` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '新增付款合約瀏覽' AFTER `productpay_hid`, 
        ADD `conlistpay_all` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '新增付款合約看全部' AFTER `conlistpay_red`;
        ALTER TABLE `crm_contract` ADD `get_or_pay` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '收款或付款 0.收款 1.付款' AFTER `id`;
        ALTER TABLE `access` ADD `alllistpay_new` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '付款合約新增' AFTER `conlistpay_all`, 
        ADD `alllistpay_red` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '付款合約讀取' AFTER `alllistpay_new`, 
        ADD `alllistpay_edi` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '付款合約修改' AFTER `alllistpay_red`, 
        ADD `alllistpay_hid` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '付款合約丟棄' AFTER `alllistpay_edi`, 
        ADD `alllistpay_del` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '付款合約刪除' AFTER `alllistpay_hid`, 
        ADD `alllistpay_all` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '付款合約看全部' AFTER `alllistpay_del`;

    -- 調整管理員權限
        UPDATE `access` SET `km_access` = '{\"file_new\":\"1\",\"file_red\":\"1\",\"file_edi\":\"1\",\"file_hid\":\"1\",\"file_del\":\"1\",\"file_all\":\"1\",\"train_new\":\"1\",\"train_red\":\"1\",\"train_edi\":\"1\",\"train_hid\":\"1\",\"train_del\":\"1\",\"train_all\":\"1\",\"accountant_new\":\"1\",\"accountant_red\":\"1\",\"accountant_edi\":\"1\",\"accountant_hid\":\"1\",\"accountant_del\":\"1\",\"accountant_all\":\"1\",\"business_new\":\"1\",\"business_red\":\"1\",\"business_edi\":\"1\",\"business_hid\":\"1\",\"business_del\":\"1\",\"business_all\":\"1\",\"plan_new\":\"1\",\"plan_red\":\"1\",\"plan_edi\":\"1\",\"plan_hid\":\"1\",\"plan_del\":\"1\",\"plan_all\":\"1\",\"design_new\":\"1\",\"design_red\":\"1\",\"design_edi\":\"1\",\"design_hid\":\"1\",\"design_del\":\"1\",\"design_all\":\"1\",\"engineering_new\":\"1\",\"engineering_red\":\"1\",\"engineering_edi\":\"1\",\"engineering_hid\":\"1\",\"engineering_del\":\"1\",\"engineering_all\":\"1\",\"important_new\":\"1\",\"important_red\":\"1\",\"important_edi\":\"1\",\"important_hid\":\"1\",\"important_del\":\"1\",\"important_all\":\"1\",\"administrative_new\":\"1\",\"administrative_red\":\"1\",\"administrative_edi\":\"1\",\"administrative_hid\":\"1\",\"administrative_del\":\"1\",\"administrative_all\":\"1\"}' WHERE `access`.`id` = 1;

    -- 付款合約歷程權限
        ALTER TABLE `access` 
        ADD `coursepay_red` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '付款合約歷程讀取' AFTER `alllistpay_all`, 
        ADD `coursepay_all` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '付款合約歷程看全部' AFTER `coursepay_red`;
        ALTER TABLE `access`
          DROP `course_edi`,
          DROP `course_hid`,
          DROP `course_del`;
        ALTER TABLE `access`
          DROP `conlist_all`,
          DROP `conlistpay_all`;
    -- 請款管理付款權限
        ALTER TABLE `access` 
        ADD `getmoneypay_new` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '請款管理付款新增' AFTER `coursepay_all`, 
        ADD `getmoneypay_red` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '請款管理理付款讀取' AFTER `getmoneypay_new`, 
        ADD `getmoneypay_edi` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '請款管理理付款修改' AFTER `getmoneypay_red`, 
        ADD `getmoneypay_del` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '請款管理理付款刪除' AFTER `getmoneypay_edi`, 
        ADD `getmoneypay_all` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '請款管理理付款看全部' AFTER `getmoneypay_del`;
        UPDATE `powercat` SET `title` = '收款申請' WHERE `powercat`.`id` = 71;
        UPDATE `powercat` SET `title` = '付款申請' WHERE `powercat`.`id` = 145;
    -- 付款紀錄權限
        ALTER TABLE `access` 
        ADD `crecordspay_red` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '付款紀錄讀取' AFTER `getmoneypay_all`, 
        ADD `crecordspay_edi` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '付款紀錄修改' AFTER `crecordspay_red`, 
        ADD `crecordspay_all` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '付款紀錄看全部' AFTER `crecordspay_edi`;
    -- 更新參數(舊系統更新要留意，會洗掉原設定)
        UPDATE `system_parameter` SET `data` = '{\r\n  \"負責人\": \"負責人\",\r\n  \"聯絡人\": \"聯絡人\",\r\n  \"協同人員\": \"協同人員\",\r\n  \"特性管理\": \"特性管理\",\r\n  \"訪談紀錄\": \"訪談紀錄\",\r\n  \"小事\": \"小事\",\r\n  \"綜合事項\": \"綜合事項\",\r\n  \"收款合約\": \"收款合約\",\r\n  \"付款合約\": \"付款合約\",\r\n  \"合約歷程\": \"合約歷程\",\r\n  \"事件列表\": \"事件列表\",\r\n  \"公司資訊\": \"公司資訊\",\r\n  \"網群\": \"網群\",\r\n  \"請款週期\": \"請款週期\",\r\n  \"收付款方式\": \"收付款方式\",\r\n  \"空間流量追蹤\": \"\",\r\n  \"官方網站\": \"官方網站\",\r\n  \"品牌網站\": \"品牌網站\",\r\n  \"員工人數\": \"員工人數\",\r\n  \"經濟部替代匯入\": \"經濟部替代匯入\",\r\n\r\n  \"客戶\": \"客戶\",\r\n  \"名稱\": \"名稱\",\r\n  \"職稱\": \"職稱/暱稱\",\r\n  \"簡稱\": \"簡稱\",\r\n  \"統編\": \"統編\",\r\n  \"地址\": \"地址\",\r\n  \"傳真\": \"傳真\",\r\n  \"起算日期\": \"起算日期\",\r\n  \"產業別\": \"產業別\",\r\n  \"產業次項\": \"產業次項\",\r\n  \"類別\": \"類別\",\r\n  \"等級\": \"等級\",\r\n  \"來源\": \"來源\",\r\n  \"資本額\": \"資本額\",\r\n  \"關係企業\": \"關係企業\",\r\n  \"業務分析\": \"業務分析\",\r\n  \"特別說明\": \"特別說明\",\r\n  \"拜訪地址\": \"拜訪地址\",\r\n  \"會計地址\": \"會計地址\",\r\n  \"出貨地址\": \"出貨地址\",\r\n  \"工廠地址\": \"工廠地址\",\r\n  \"登記地址\": \"登記地址\",\r\n\r\n  \"公司名稱\": \"公司名稱\",\r\n  \"公司電話\": \"電話\",\r\n  \"公司手機\": \"手機\",\r\n  \"公司MAIL\": \"MAIL\",\r\n  \"公司核准日\": \"核准日\",\r\n  \"公司LINE_FB\": \"LINE / FB\",\r\n  \"公司LINE\": \"LINE\",\r\n  \"公司FB\": \"FB\",\r\n  \"公司備註\": \"備註\",\r\n\r\n  \"負責人暱稱\": \"姓名\",\r\n  \"負責人電話\": \"電話\",\r\n  \"負責人手機\": \"手機\",\r\n  \"負責人MAIL\": \"MAIL\",\r\n  \"負責人生日\": \"生日\",\r\n  \"負責人LINE_FB\": \"LINE / FB\",\r\n  \"負責人LINE\": \"LINE\",\r\n  \"負責人FB\": \"FB\",\r\n  \"負責人備註\": \"備註\",\r\n\r\n  \"聯絡人暱稱\": \"姓名\",\r\n  \"聯絡人電話\": \"電話\",\r\n  \"聯絡人手機\": \"手機\",\r\n  \"聯絡人MAIL\": \"MAIL\",\r\n  \"聯絡人生日\": \"生日\",\r\n  \"聯絡人LINE_FB\": \"LINE / FB\",\r\n  \"聯絡人LINE\": \"LINE\",\r\n  \"聯絡人FB\": \"FB\",\r\n  \"聯絡人備註\": \"備註\",\r\n\r\n  \"商品網路價\":\"商品網路價\",\r\n  \"商品售價\":\"商品售價\",\r\n  \"送貨地址\":\"地址\",\r\n  \"送貨單額外資訊\":\"\",\r\n  \"合約\":\"合約\"\r\n}' WHERE `system_parameter`.`id` = 1;

    -- 收支統計製作
        UPDATE `powercat` SET `title` = '營運統計' WHERE `powercat`.`id` = 105;
        UPDATE `powercat` SET `title` = '營運統計' WHERE `powercat`.`id` = 130;
        UPDATE `powercat` SET `parent_id` = '130' WHERE `powercat`.`id` = 106;
        UPDATE `powercat` SET `parent_id` = '130' WHERE `powercat`.`id` = 58;
        INSERT INTO `powercat` 
        (`id`, `level`, `parent_id`, `title`, `codenamed`, `type`, `link`, `description`, `orders`, `status`, `create_time`, `update_time`, `readself`, `readall`, `newcate`, `updatecate`, `delcate`, `truncate`) VALUES 
        (149, '1', '105', '收支報表', 'Balance', 'normal', NULL, '', '1', '1', '0', '0', '1', '1', '1', '1', '1', '1');
        ALTER TABLE `access` ADD `balance_red` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '收支總表讀取' AFTER `crecordspay_all`;
        CREATE TABLE `balance` ( 
            `ym` VARCHAR(6) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT '年月(YYYYMM)'
        ) ENGINE = InnoDB;
        ALTER TABLE `balance` ADD PRIMARY KEY(`ym`);
        ALTER TABLE `balance` ADD UNIQUE(`ym`);
        ALTER TABLE `balance`
        ADD `in_total` INT(11) NOT NULL COMMENT '總收入(未稅)' AFTER `ym`,
        ADD `in_content` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '收入項目' AFTER `in_total`,
        ADD `out_total` INT(11) NOT NULL COMMENT '總支出(未稅)' AFTER `in_content`,
        ADD `out_content` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '支出項目' AFTER `out_total`;
        ALTER TABLE `balance` ADD `in_tax` INT(11) NOT NULL COMMENT '總收入(稅金部分)' AFTER `in_total`;
        ALTER TABLE `balance` ADD `out_tax` INT(11) NOT NULL COMMENT '總支出(稅金部分)' AFTER `out_total`;
        ALTER TABLE `crm_shipment` ADD `contract_unit_id` INT(11) NOT NULL DEFAULT '0' COMMENT '對應合約商品id' AFTER `caseid`;
        ALTER TABLE `balance` ADD `companys_content` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '依公司統計的收支' AFTER `out_content`;
        INSERT INTO `system_parameter` (`id`, `data`, `note`) VALUES ('10', '0', '薪資及收支是否依公司查看 0.否 1.是');
        UPDATE `system_parameter` SET `note` = '薪資是否依公司分配 0.否 1.是' WHERE `system_parameter`.`id` = 10;

    -- 可調整績效認列時間
        ALTER TABLE `eve_steps` ADD `kpi_time` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '績效認列時間' AFTER `update_time`;
        UPDATE `eve_steps` set kpi_time = update_time WHERE `status`=1;


/*2023-06-15 事件簿 發布後可調整工作等*/
    -- 刪除無用資料
        DROP TABLE `eve_events_back`;
        DROP TABLE `eve_events_child`;
        ALTER TABLE `eve_events` DROP `parent_id`;
        ALTER TABLE `eve_events` DROP `arr_eid`;
        ALTER TABLE `eve_events` DROP `del_flag`;
        ALTER TABLE `eve_events`
          DROP `ydfinishday`,
          DROP `finishday`;
        ALTER TABLE `eve_events` DROP `cid`;
        ALTER TABLE `eve_events` DROP `note`;
        ALTER TABLE `eve_steps` DROP `code_id`;
        ALTER TABLE `eve_steps` CHANGE `count` `count` INT(10) NOT NULL DEFAULT '0' COMMENT '驗收未過次數';
        ALTER TABLE `eve_steps` DROP `time_change`;
        ALTER TABLE `eve_steps` DROP `back_id`;
        ALTER TABLE `eve_events` CHANGE `result_temp` `result_temp` SMALLINT(3) NULL DEFAULT NULL COMMENT '丟進垃圾桶前的狀態(還原用)';
        ALTER TABLE `eve_events` CHANGE `eve_pid` `eve_pid` INT(10) NOT NULL COMMENT '套用模組事件id(僅記錄用)';
        ALTER TABLE `eve_events` CHANGE `eve_type` `eve_type` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '套用模組事件 0.否 1.是';
        ALTER TABLE `eve_steps` CHANGE `update_time` `update_time` INT(11) NOT NULL COMMENT '更新時間(改排程or驗收通過都會改此時間)';
/*2023-06-16 事件模組複製功能*/
    ALTER TABLE `eve_processes` DROP `cid`;
    ALTER TABLE `eve_processes` CHANGE `eid` `eid` INT(11) NOT NULL COMMENT '指派人員id';

/*2023-06-17 人事資料調整*/
    ALTER TABLE `eip_user` DROP `experience`;
    ALTER TABLE `eip_user_data`
      DROP `work_type`,
      DROP `educ_heig`,
      DROP `educ_depa`,
      DROP `user_tag`;
    ALTER TABLE `eip_user_data` 
    ADD `work_intro_sale` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '業務類_介紹' AFTER `pot_num`, 
    ADD `work_year_sale` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '業務類_年資' AFTER `work_intro_sale`, 
    ADD `work_note_sale` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '業務類_備註' AFTER `work_year_sale`;
    ALTER TABLE `eip_user_data` 
    ADD `work_intro_skill` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '技術類_介紹' AFTER `work_note_sale`, 
    ADD `work_year_skill` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '技術類_年資' AFTER `work_intro_skill`, 
    ADD `work_note_skill` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '技術類_備註' AFTER `work_year_skill`;
    ALTER TABLE `eip_user_data` 
    ADD `work_intro_plan` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '企劃類_介紹' AFTER `work_note_skill`, 
    ADD `work_year_plan` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '企劃類_年資' AFTER `work_intro_plan`, 
    ADD `work_note_plan` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '企劃類_備註' AFTER `work_year_plan`;
    ALTER TABLE `eip_user_data` 
    ADD `work_intro_administration` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '行政類_介紹' AFTER `work_note_plan`, 
    ADD `work_year_administration` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '行政類_年資' AFTER `work_intro_administration`, 
    ADD `work_note_administration` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '行政類_備註' AFTER `work_year_administration`;
    ALTER TABLE `eip_user_data` 
    ADD `work_intro_other` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '其它類_介紹' AFTER `work_note_administration`, 
    ADD `work_year_other` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '其它類_年資' AFTER `work_intro_other`, 
    ADD `work_note_other` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '其它類_備註' AFTER `work_year_other`;
    ALTER TABLE `eip_user_data` 
    ADD `saler_options` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '業務管理_選項' AFTER `work_note_other`, 
    ADD `saler_note` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '業務管理_備註' AFTER `saler_options`;
    ALTER TABLE `eip_user_data` 
    ADD `maintainer_options` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '後勤管理_選項' AFTER `saler_note`, 
    ADD `maintainer_note` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '後勤管理_備註' AFTER `maintainer_options`;
    ALTER TABLE `eip_user_data` 
    ADD `international_options` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '國際經驗_選項' AFTER `maintainer_note`, 
    ADD `international_note` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '國際經驗_備註' AFTER `international_options`;
    ALTER TABLE `eip_user_data` 
    ADD `samecompany_options` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '同行經驗_選項' AFTER `international_note`, 
    ADD `samecompany_note` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '同行經驗_備註' AFTER `samecompany_options`;
    ALTER TABLE `eip_user_data` 
    ADD `project_options` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '大型專案_選項' AFTER `samecompany_note`, 
    ADD `project_note` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '大型專案_備註' AFTER `project_options`;

/*2023-06-19 修改合約狀態名稱*/
    UPDATE `crm_cum_flag` SET `name` = '提案' WHERE `crm_cum_flag`.`id` = 0;
    UPDATE `crm_cum_flag` SET `name` = '已簽約' WHERE `crm_cum_flag`.`id` = 1;

/*2023-06-29 請假申請*/
    INSERT INTO `powercat` 
    (`id`, `level`, `parent_id`, `title`, `codenamed`, `type`, `link`, `description`, `orders`, `status`, `create_time`, `update_time`, `readself`, `readall`, `newcate`, `updatecate`, `delcate`, `truncate`) VALUES 
    (150, '1', '1', '核假管理', 'RestRecord', 'normal', NULL, '', '2', '1', '0', '0', '1', '1', '1', '1', '1', '1');
    ALTER TABLE `rest_records` CHANGE `examiner` `examiner` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '審核人員(本部)';
    ALTER TABLE `rest_records` 
    ADD `examiner_top` INT(11) NOT NULL DEFAULT '0' COMMENT '審核人員(公司)' AFTER `examiner`;
    ALTER TABLE `eip_apart` ADD `rest_examine` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '公司核假 0.否 1.是' AFTER `is_kpi`;
    ALTER TABLE `rest_type` 
    ADD `preapply_days` INT(11) NOT NULL DEFAULT '0' COMMENT '幾日前申請' AFTER `month_limit`, 
    ADD `top_examine_hours` INT(11) NOT NULL DEFAULT '99999' COMMENT '需公司審核(小時)' AFTER `preapply_days`;
    ALTER TABLE `rest_type` 
    ADD `min_range` INT(11) NOT NULL DEFAULT '15' COMMENT '最短請假單位(分鐘)' AFTER `top_examine_hours`;
    ALTER TABLE `rest_records` CHANGE `hours` `hours` DECIMAL(8,3) NOT NULL DEFAULT '0' COMMENT '請假時數';
    ALTER TABLE `rest_records` ADD `apply_status` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '申請狀態 0.審核通過 1.修改中 2.職代審核 3.部審核 4.公司審核' AFTER `note`;
    CREATE TABLE `rest_records_reply` ( 
        `id` INT(11) NOT NULL , 
        `rest_records_id` INT(11) NOT NULL COMMENT '對應休假申請id' , 
        `apply_status` INT(11) NOT NULL COMMENT '申請進度' , 
        `time` VARCHAR(10) NULL DEFAULT NULL COMMENT '記錄時間'
    ) ENGINE = InnoDB;
    ALTER TABLE `rest_records_reply` ADD PRIMARY KEY(`id`);
    ALTER TABLE `rest_records_reply` CHANGE id id int(11) AUTO_INCREMENT;
    ALTER TABLE `rest_records_reply` ADD `note` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '回覆內容' AFTER `apply_status`;
    ALTER TABLE `rest_records_reply` ADD `value` CHAR(1) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '同意狀態 0.不同意 1.同意' AFTER `apply_status`;
    ALTER TABLE `rest_records` ADD `rest_records_reply_id` INT(11) NOT NULL DEFAULT '0' COMMENT '對應請假申請回覆id' AFTER `note`;
/*2023-06-30 請假申請加證明文件*/
    ALTER TABLE `rest_records` 
    ADD `prove_file` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT 'google空間路徑' AFTER `reason`, 
    ADD `prove_file_name` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '檔案名稱' AFTER `prove_file`;
    ALTER TABLE `rest_type` CHANGE `preapply_days` `preapply_days` INT(11) NOT NULL DEFAULT '-1' COMMENT '幾日前申請(-1不限制)';
/*2023-07-06 日程管理(排班功能)*/
    INSERT INTO `powercat` 
    (`id`, `level`, `parent_id`, `title`, `codenamed`, `type`, `link`, `description`, `orders`, `status`, `create_time`, `update_time`, `readself`, `readall`, `newcate`, `updatecate`, `delcate`, `truncate`) VALUES 
    (151, '1', '67', '日程管理', 'Schedule', 'normal', NULL, '', '10', '1', '0', '0', '1', '1', '1', '1', '1', '0');
    ALTER TABLE `access` 
    ADD `schedule_new` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '日程管理新增' AFTER `balance_red`, 
    ADD `schedule_red` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '日程管理讀取' AFTER `schedule_new`, 
    ADD `schedule_edi` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '日程管理修改' AFTER `schedule_red`, 
    ADD `schedule_del` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '日程管理刪除' AFTER `schedule_edi`;
    CREATE TABLE `schedule` ( 
        `id` INT(11) NOT NULL , 
        `name` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '工作名稱' ,
        `location` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '地點' ,
        `location_gps` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '地點GPS' ,
        `user_id` INT(11) NOT NULL COMMENT '建立者id' , 
        `eve_step_id` INT(11) NOT NULL DEFAULT '0' COMMENT '對應事件步驟id'
    ) ENGINE = InnoDB;
    ALTER TABLE `schedule` ADD PRIMARY KEY(`id`);
    ALTER TABLE `schedule` CHANGE id id int(11) AUTO_INCREMENT;
    CREATE TABLE `schedule_date` ( 
        `id` INT(11) NOT NULL , 
        `schedule_id` INT(11) NOT NULL DEFAULT '0' COMMENT '工作id',
        `date` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '工作日期', 
        `user_in_charge` INT(11) NOT NULL COMMENT '當日管理者',
        `examine_note` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '驗收批示' , 
        `examine_time` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '驗收時間',
        `turn_salary_time` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '轉薪資時間'
    ) ENGINE = InnoDB;
    ALTER TABLE `schedule_date` ADD PRIMARY KEY(`id`);
    ALTER TABLE `schedule_date` CHANGE id id int(11) AUTO_INCREMENT;

    INSERT INTO `powercat` 
    (`id`, `level`, `parent_id`, `title`, `codenamed`, `type`, `link`, `description`, `orders`, `status`, `create_time`, `update_time`, `readself`, `readall`, `newcate`, `updatecate`, `delcate`, `truncate`) VALUES 
    (152, '1', '67', '我的班表', 'ScheduleDetail', 'normal', NULL, '', '20', '1', '0', '0', '1', '1', '1', '1', '1', '0');
    CREATE TABLE `schedule_date_user` ( 
     `id` INT(11) NOT NULL , 
     `schedule_date_id` INT(11) NOT NULL DEFAULT '0' COMMENT '工作日id',
     `worktime_s` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '排班時間_開始',
     `worktime_e` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '排班時間_結束',
     `user_id` INT(11) NOT NULL COMMENT '出勤人id',
     `user_skill` INT(11) NOT NULL DEFAULT '1' COMMENT '出勤人_技能id',
     `user_hour_pay` INT(11) NOT NULL DEFAULT '0' COMMENT '出勤人_時薪id',
     `roll_call_come` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '上班點名時間',
     `roll_call_leave` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '下班點名時間',
     `roll_call_overtime` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '加班點名時間',
     `do_hour` DECIMAL(8,3) NOT NULL DEFAULT '0' COMMENT '正規工時(小時)',
     `do_hour_overtime` DECIMAL(8,3) NOT NULL DEFAULT '0' COMMENT '加班工時(小時)',
     `note` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '備註'
    ) ENGINE = InnoDB;
    ALTER TABLE `schedule_date_user` ADD PRIMARY KEY(`id`);
    ALTER TABLE `schedule_date_user` CHANGE id id int(11) AUTO_INCREMENT;
/*2023-07-08 添加技能管理(關聯日薪)*/
    INSERT INTO `powercat` 
    (`id`, `level`, `parent_id`, `title`, `codenamed`, `type`, `link`, `description`, `orders`, `status`, `create_time`, `update_time`, `readself`, `readall`, `newcate`, `updatecate`, `delcate`, `truncate`) VALUES 
    (153, '2', '129', '技能管理', 'UserSkill', 'normal', NULL, '', '7', '1', '0', '0', '0', '0', '0', '0', '0', '0');
    ALTER TABLE `access` 
    ADD `userskill_new` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '日程管理新增' AFTER `schedule_del`, 
    ADD `userskill_red` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '日程管理讀取' AFTER `userskill_new`, 
    ADD `userskill_edi` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '日程管理修改' AFTER `userskill_red`, 
    ADD `userskill_hid` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '日程管理丟棄' AFTER `userskill_edi`;
    CREATE TABLE `user_skill` (
     `id` INT NOT NULL , 
     `name` TEXT CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT '技能名稱',
     `hour_pay` INT NOT NULL COMMENT '技能時薪',
     `order_id` INT(11) NOT NULL DEFAULT '0' COMMENT '排序' , 
     `status` TINYINT(1) NOT NULL DEFAULT '1' COMMENT '狀態 1.使用中 0.刪除' 
    ) ENGINE = InnoDB;
    ALTER TABLE `user_skill` ADD PRIMARY KEY(`id`);
    ALTER TABLE `user_skill` CHANGE id id int(11) AUTO_INCREMENT;
    INSERT INTO `user_skill` (`id`, `name`, `hour_pay`, `order_id`, `status`) VALUES (1, '通用', '0', '0', '1');
    CREATE TABLE `salary_records_skill` (
     `id` INT NOT NULL , 
     `salary_records_id` INT(11) NOT NULL DEFAULT '0' COMMENT '對應薪資紀錄id' , 
     `user_skill_id` INT(11) NOT NULL DEFAULT '0' COMMENT '對應技能id' , 
     `hour_pay` INT(11) NOT NULL DEFAULT '0' COMMENT '時薪'
    ) ENGINE = InnoDB;
    ALTER TABLE `salary_records_skill` ADD PRIMARY KEY(`id`);
    ALTER TABLE `salary_records_skill` CHANGE id id int(11) AUTO_INCREMENT;
/*2023-07-08 合約添加預估支出*/
    CREATE TABLE `crm_contract_unit2` (
     `id` int(11) NOT NULL,
     `cat_unit_id` int(11) NOT NULL COMMENT 'crm_cum_cat_unit表ID',
     `pid` int(11) NOT NULL COMMENT 'crm_contract表ID',
     `name` varchar(265) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT '品名',
     `number` varchar(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '商品代號',
     `type` varchar(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '規格',
     `list_price` varchar(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '網路價',
     `sale_price` varchar(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '售價',
     `inventory` varchar(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '庫存',
     `profit` float NOT NULL DEFAULT '0' COMMENT '業績BV',
     `num` int(11) NOT NULL DEFAULT '0' COMMENT '數量',
     `total` int(11) NOT NULL DEFAULT '0' COMMENT '金額',
     `total_dis` int(11) NOT NULL DEFAULT '0' COMMENT '優惠總價',
     `cost` int(11) NOT NULL DEFAULT '0' COMMENT '成本'
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1;
    ALTER TABLE `crm_contract_unit2` ADD PRIMARY KEY (`id`);
    ALTER TABLE `crm_contract_unit2` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
    ALTER TABLE `crm_contract_unit` ADD `inventory_predict` VARCHAR(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '庫存(預測)' AFTER `inventory`;
    ALTER TABLE `crm_contract_unit2` ADD `inventory_predict` VARCHAR(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '庫存(預測)' AFTER `inventory`;
    ALTER TABLE `crm_cum_cat_unit` ADD `inventory_predict` VARCHAR(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '庫存(預測)' AFTER `inventory`;
    UPDATE `crm_cum_cat_unit` SET `inventory_predict` = `inventory`;
    CREATE TABLE `schedule_user_skill` (
     `id` int(11) NOT NULL,
     `user_skill_id` int(11) NOT NULL COMMENT 'user_skill表ID',
     `schedule_id` int(11) NOT NULL COMMENT '工作id',
     `name` varchar(265) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT '技能名稱',
     `hour_pay` int(11) NOT NULL DEFAULT '0' COMMENT '技能時薪',
     `hour_predict` int(11) NOT NULL DEFAULT '0' COMMENT '預計工時(H)'
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1;
    ALTER TABLE `schedule_user_skill` ADD PRIMARY KEY (`id`);
    ALTER TABLE `schedule_user_skill` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
/*2023-07-09 排班驗收*/
    CREATE TABLE `schedule_date_report` ( 
     `id` INT(11) NOT NULL , 
     `schedule_date_id` INT(11) NOT NULL DEFAULT '0' COMMENT '工作日id',
     `name` VARCHAR(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '名稱',
     `note` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL COMMENT '備註'
    ) ENGINE = InnoDB;
    ALTER TABLE `schedule_date_report` ADD PRIMARY KEY(`id`);
    ALTER TABLE `schedule_date_report` CHANGE id id int(11) AUTO_INCREMENT;
/*2023-07-10 拋轉薪資資料*/
    ALTER TABLE `schedule_date_user` ADD `pay_total` INT(11) NOT NULL DEFAULT '0' COMMENT '當日薪資(拋轉薪資資料時計算)' AFTER `note`;
    ALTER TABLE `schedule_date_user` ADD `schedule_date_user_pay_id` INT(11) NOT NULL DEFAULT '0' COMMENT '對應之支付單id' AFTER `pay_total`;
    CREATE TABLE `schedule_date_user_pay` ( 
     `id` INT(11) NOT NULL , 
     `user_id` INT(11) NOT NULL COMMENT '給付對象(員工id)',
     `pay_sum` INT(11) NOT NULL DEFAULT '1' COMMENT '總支付額',
     `create_time` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '生成時間',
     `comfirm_time` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '核可時間',
     `note` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '備註'
    ) ENGINE = InnoDB;
    ALTER TABLE `schedule_date_user_pay` ADD PRIMARY KEY(`id`);
    ALTER TABLE `schedule_date_user_pay` CHANGE id id int(11) AUTO_INCREMENT;
    INSERT INTO `powercat` 
    (`id`, `level`, `parent_id`, `title`, `codenamed`, `type`, `link`, `description`, `orders`, `status`, `create_time`, `update_time`, `readself`, `readall`, `newcate`, `updatecate`, `delcate`, `truncate`) VALUES 
    (154, '1', '67', '技工付薪', 'SchedulePay', 'normal', NULL, '', '30', '1', '0', '0', '1', '1', '1', '1', '1', '0');
    ALTER TABLE `access` 
    ADD `schedulepay_red` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '技工付薪讀取' AFTER `userskill_hid`, 
    ADD `schedulepay_edi` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '技工付薪修改' AFTER `schedulepay_red`;
/*2023-07-12 優化權限資料表*/
    ALTER TABLE `access` DROP `regulation_new`, DROP `regulation_red`, DROP `regulation_edi`, DROP `regulation_hid`, DROP `regulation_del`, DROP `regulation_all`, DROP `salereg_new`, DROP `salereg_red`, DROP `salereg_edi`, DROP `salereg_hid`, DROP `salereg_del`, DROP `salereg_all`, DROP `workreg_new`, DROP `workreg_red`, DROP `workreg_edi`, DROP `workreg_hid`, DROP `workreg_del`, DROP `workreg_all`, DROP `inpost_new`, DROP `inpost_red`, DROP `inpost_edi`, DROP `inpost_hid`, DROP `inpost_del`, DROP `inpost_all`, DROP `outpost_new`, DROP `outpost_red`, DROP `outpost_edi`, DROP `outpost_hid`, DROP `outpost_del`, DROP `outpost_all`, DROP `saleteach_new`, DROP `saleteach_red`, DROP `saleteach_edi`, DROP `saleteach_hid`, DROP `saleteach_del`, DROP `saleteach_all`, DROP `url_new`, DROP `url_red`, DROP `url_edi`, DROP `url_hid`, DROP `url_del`, DROP `url_all`, DROP `document_new`, DROP `document_red`, DROP `document_edi`, DROP `document_hid`, DROP `document_del`, DROP `document_all`, DROP `teach_new`, DROP `teach_red`, DROP `teach_edi`, DROP `teach_hid`, DROP `teach_del`, DROP `teach_all`;
    ALTER TABLE `access` DROP `apart_new`, DROP `apart_red`, DROP `apart_edi`, DROP `apart_hid`, DROP `apart_del`, DROP `apart_all`, DROP `analy_new`, DROP `analy_red`, DROP `analy_edi`, DROP `analy_hid`, DROP `analy_del`, DROP `analy_all`, DROP `mail_new`, DROP `mail_red`, DROP `mail_edi`, DROP `mail_hid`, DROP `mail_del`, DROP `mail_all`, DROP `back_new`, DROP `back_red`, DROP `back_edi`, DROP `back_hid`, DROP `back_del`, DROP `back_all`, DROP `time_new`, DROP `time_red`, DROP `time_edi`, DROP `time_hid`, DROP `time_del`, DROP `time_all`;
    DELETE FROM `access` WHERE `access`.`id` = 1;
    INSERT INTO `access` 
    (`id`, `power_id`, `status`, `name`, `km_access`, `mens_new`, `mens_red`, `mens_edi`, `mens_hid`, `mens_del`, `mens_all`, `access_new`, `access_red`, `access_edi`, `access_hid`, `access_del`, `access_all`, `custo_new`, `custo_red`, `custo_edi`, `custo_hid`, `custo_del`, `custo_all`, `fig_new`, `fig_red`, `fig_edi`, `fig_hid`, `fig_del`, `fig_all`, `imcrm_red`, `imcrm_edi`, `imcrm_hid`, `anay_new`, `anay_red`, `anay_edi`, `anay_hid`, `anay_del`, `anay_all`, `alllist_new`, `alllist_red`, `alllist_edi`, `alllist_hid`, `alllist_del`, `alllist_all`, `crmcumcat_new`, `crmcumcat_red`, `crmcumcat_edi`, `crmcumcat_hid`, `org_new`, `org_red`, `org_edi`, `org_hid`, `org_del`, `org_all`, `server_new`, `server_red`, `server_edi`, `server_hid`, `server_del`, `server_all`, `domain_new`, `domain_red`, `domain_edi`, `domain_hid`, `domain_del`, `domain_all`, `sercom_new`, `sercom_red`, `sercom_edi`, `sercom_hid`, `sercom_del`, `sercom_all`, `domcom_new`, `domcom_red`, `domcom_edi`, `domcom_hid`, `domcom_del`, `domcom_all`, `seo_red`, `seo_edi`, `seo_all`, `seoto_new`, `seoto_red`, `seoto_edi`, `seoto_hid`, `seoto_del`, `seoto_all`, `events_new`, `events_red`, `events_edi`, `events_hid`, `events_del`, `events_all`, `getmoney_new`, `getmoney_red`, `getmoney_edi`, `getmoney_del`, `getmoney_all`, `crecords_red`, `crecords_edi`, `crecords_all`, `jobs_new`, `jobs_red`, `jobs_edi`, `jobs_hid`, `jobs_del`, `course_red`, `course_all`, `crm_new`, `crm_red`, `crm_edi`, `crm_hid`, `crm_del`, `crm_all`, `seoprice_new`, `seoprice_red`, `seoprice_edi`, `seoprice_hid`, `seoprice_del`, `resdoc_new`, `resdoc_red`, `resdoc_edi`, `resdoc_hid`, `resdoc_del`, `resdoc_all`, `internal_new`, `internal_red`, `internal_edi`, `internal_hid`, `internal_del`, `internal_all`, `external_new`, `external_red`, `external_edi`, `external_hid`, `external_del`, `external_all`, `conlist_red`, `marketing_new`, `marketing_red`, `marketing_edi`, `marketing_hid`, `marketing_del`, `marketing_all`, `kpimodel_new`, `kpimodel_red`, `kpimodel_edi`, `kpimodel_hid`, `kpimodel_del`, `kpimodel_all`, `performance_new`, `performance_red`, `performance_edi`, `performance_hid`, `performance_del`, `performance_all`, `ssl_new`, `ssl_red`, `ssl_edi`, `ssl_hid`, `ssl_del`, `ssl_all`, `conference_new`, `conference_red`, `conference_edi`, `conference_hid`, `conference_del`, `conference_all`, `parameter_new`, `parameter_red`, `parameter_edi`, `parameter_hid`, `parameter_del`, `parameter_all`, `crmproperty_new`, `crmproperty_red`, `crmproperty_edi`, `crmproperty_hid`, `crmproperty_del`, `industr_new`, `industr_red`, `industr_edi`, `industr_del`, `kmsetting_new`, `kmsetting_red`, `kmsetting_edi`, `kmsetting_del`, `product_new`, `product_red`, `product_edi`, `product_hid`, `crmcumcatpay_new`, `crmcumcatpay_red`, `crmcumcatpay_edi`, `crmcumcatpay_hid`, `cumpri_red`, `cumpri_edi`, `salesetting_red`, `salesetting_edi`, `accountantin_new`, `accountantin_red`, `accountantin_edi`, `accountantin_hid`, `insurancelevel_new`, `insurancelevel_red`, `insurancelevel_hid`, `insurancehealth_new`, `insurancehealth_red`, `insurancehealth_hid`, `insurancelabor_new`, `insurancelabor_red`, `insurancelabor_hid`, `specialrest_new`, `specialrest_red`, `specialrest_hid`, `resttype_new`, `resttype_red`, `resttype_edi`, `resttype_hid`, `demo_red`, `accountantout_new`, `accountantout_red`, `accountantout_edi`, `accountantout_hid`, `bonustype_new`, `bonustype_red`, `bonustype_hid`, `salary_red`, `salary_edi`, `salary_hid`, `productpay_new`, `productpay_red`, `productpay_edi`, `productpay_hid`, `conlistpay_red`, `alllistpay_new`, `alllistpay_red`, `alllistpay_edi`, `alllistpay_hid`, `alllistpay_del`, `alllistpay_all`, `coursepay_red`, `coursepay_all`, `getmoneypay_new`, `getmoneypay_red`, `getmoneypay_edi`, `getmoneypay_del`, `getmoneypay_all`, `crecordspay_red`, `crecordspay_edi`, `crecordspay_all`, `balance_red`, `schedule_new`, `schedule_red`, `schedule_edi`, `schedule_del`, `userskill_new`, `userskill_red`, `userskill_edi`, `userskill_hid`, `schedulepay_red`, `schedulepay_edi`) VALUES 
    (1, '0', '0', '管理員', '{\"file_new\":\"1\",\"file_red\":\"1\",\"file_edi\":\"1\",\"file_hid\":\"1\",\"file_del\":\"1\",\"file_all\":\"1\",\"accountant_new\":\"1\",\"accountant_red\":\"1\",\"accountant_edi\":\"1\",\"accountant_hid\":\"1\",\"accountant_del\":\"1\",\"accountant_all\":\"1\",\"business_new\":\"1\",\"business_red\":\"1\",\"business_edi\":\"1\",\"business_hid\":\"1\",\"business_del\":\"1\",\"business_all\":\"1\",\"plan_new\":\"1\",\"plan_red\":\"1\",\"plan_edi\":\"1\",\"plan_hid\":\"1\",\"plan_del\":\"1\",\"plan_all\":\"1\",\"design_new\":\"1\",\"design_red\":\"1\",\"design_edi\":\"1\",\"design_hid\":\"1\",\"design_del\":\"1\",\"design_all\":\"1\",\"engineering_new\":\"1\",\"engineering_red\":\"1\",\"engineering_edi\":\"1\",\"engineering_hid\":\"1\",\"engineering_del\":\"1\",\"engineering_all\":\"1\",\"important_new\":\"1\",\"important_red\":\"1\",\"important_edi\":\"1\",\"important_hid\":\"1\",\"important_del\":\"1\",\"important_all\":\"1\",\"administrative_new\":\"1\",\"administrative_red\":\"1\",\"administrative_edi\":\"1\",\"administrative_hid\":\"1\",\"administrative_del\":\"1\",\"administrative_all\":\"1\",\"train_new\":\"1\",\"train_red\":\"1\",\"train_edi\":\"1\",\"train_hid\":\"1\",\"train_del\":\"1\",\"train_all\":\"1\"}', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1');
/*2023-07-13 薪資技能選擇後的後續效果調整&日程驗收修改*/
    UPDATE `powercat` SET `title` = '工種管理' WHERE `powercat`.`id` = 153;
    ALTER TABLE `schedule_date_user` CHANGE `user_skill` `user_skill` INT(11) NOT NULL DEFAULT '1' COMMENT '出勤人_技能id';
    ALTER TABLE `schedule_date_report` ADD `note_examine` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '驗收批示' AFTER `note`;
    ALTER TABLE `schedule_date_report` ADD `note_examine_time` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '驗收批示時間' AFTER `note_examine`;
    CREATE TABLE `date_report_model` ( 
     `id` INT(11) NOT NULL ,
     `name` VARCHAR(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '名稱'
    ) ENGINE = InnoDB;
    ALTER TABLE `date_report_model` ADD PRIMARY KEY(`id`);
    ALTER TABLE `date_report_model` CHANGE id id int(11) AUTO_INCREMENT;
    CREATE TABLE `date_report_model_detail` ( 
     `id` INT(11) NOT NULL , 
     `date_report_model_id` INT(11) NOT NULL DEFAULT '0' COMMENT '驗收模組id',
     `name` VARCHAR(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '驗收名稱',
     `note` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL COMMENT '驗收備註'
    ) ENGINE = InnoDB;
    ALTER TABLE `date_report_model_detail` ADD PRIMARY KEY(`id`);
    ALTER TABLE `date_report_model_detail` CHANGE id id int(11) AUTO_INCREMENT;
    ALTER TABLE `schedule` CHANGE `user_id` `user_id` INT(11) NOT NULL COMMENT '建立者id(審核者)';
/*2023-08-14 Google打卡*/
    CREATE TABLE `attendance_date`(
        `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `date` DATE NOT NULL COMMENT '須打卡日期',
        PRIMARY KEY(`id`) USING BTREE,
        UNIQUE INDEX `date`(`date`) USING BTREE
    ) COMMENT = '出勤日' COLLATE = 'utf8mb4_general_ci' ENGINE = INNODB AUTO_INCREMENT = 1;
    CREATE TABLE `attendance_records`(
        `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `date` DATE NOT NULL COMMENT '出勤日期',
        `user_id` INT(10) UNSIGNED NOT NULL COMMENT '員工ID ref:eip_user.id',
        `time_come` TIME NULL DEFAULT NULL COMMENT '上班時間',
        `time_leave` TIME NULL DEFAULT NULL COMMENT '下班時間',
        PRIMARY KEY(`id`) USING BTREE,
        INDEX `FK_punch_records_punch_date`(`date`) USING BTREE,
        CONSTRAINT `FK_punch_records_punch_date` FOREIGN KEY(`date`) REFERENCES `attendance_date`(`date`) ON UPDATE NO ACTION ON DELETE NO ACTION
    ) COMMENT = '出勤紀錄' COLLATE = 'utf8mb4_general_ci' ENGINE = INNODB AUTO_INCREMENT = 1;
    INSERT INTO `powercat` (`id`, `level`, `parent_id`, `title`, `codenamed`, `type`, `link`, `description`, `orders`, `status`, `create_time`, `update_time`, `readself`, `readall`, `newcate`, `updatecate`, `delcate`, `truncate`)
        VALUES (155, 2, 129, '打卡管理', 'AttendanceDate', 'normal', NULL, '', 40, 1, 0, 0, 0, 0, 0, 0, 0, 0);
    INSERT INTO `powercat` (`id`, `level`, `parent_id`, `title`, `codenamed`, `type`, `link`, `description`, `orders`, `status`, `create_time`, `update_time`, `readself`, `readall`, `newcate`, `updatecate`, `delcate`, `truncate`)
        VALUES (156, 1, 1, '打卡紀錄', 'AttendanceRecords', 'normal', NULL, '', 3, 1, 0, 0, 0, 0, 0, 0, 0, 0);
    INSERT INTO `system_parameter` (`id`, `data`, `note`)
        VALUES (11, '25.02500316260215, 121.55338708314407, 200', '公司位置緯、經度、距離公尺');
    INSERT INTO `system_parameter` (`id`, `data`, `note`)
        VALUES (12, '09:00:00, 18:15:00', '公司正常上下班時間');
    ALTER TABLE
        `eip_user`
        ADD `use_attendance` TINYINT(1) NOT NULL DEFAULT '1' COMMENT '是否需要打卡' AFTER `extension`;
    ALTER TABLE
        `access`
        ADD `attendancedate_red` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '打卡管理瀏覽' AFTER `schedulepay_edi`,
        ADD `attendancedate_new` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '打卡管理新增' AFTER `attendancedate_red`,
        ADD `attendancedate_del` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '打卡管理刪除' AFTER `attendancedate_new`,
        ADD `attendancerecords_red` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '打卡紀錄瀏覽' AFTER `attendancedate_del`,
        ADD `attendancerecords_edi` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '打卡紀錄編輯' AFTER `attendancerecords_red`,
        ADD `attendancerecords_all` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '打卡紀錄看全部' AFTER `attendancerecords_edi`;
    UPDATE
        `access`
    SET
        `attendancedate_red` = '1',
        `attendancedate_new` = '1',
        `attendancedate_del` = '1',
        `attendancerecords_red` = '1',
        `attendancerecords_edi` = '1',
        `attendancerecords_all` = '1'
    WHERE
        `access`.`id` = 1;
/*2023-08-27 人事功能修改&少翔客制化*/
    ALTER TABLE
        `eip_user`
        ADD `idno` VARCHAR(32) NULL DEFAULT NULL COMMENT '身份証' AFTER `resaddr`,
        ADD `bank_account_name` VARCHAR(20) NULL DEFAULT NULL COMMENT '銀行戶名' AFTER `idno`,
        ADD `ememo` VARCHAR(255) NULL DEFAULT NULL COMMENT '緊急聯絡人備註' AFTER `emphone`,
        ADD `bank_branch_name` VARCHAR(10) NULL DEFAULT NULL COMMENT '銀行分行名稱' AFTER `bank_account`;
/*2023-09-25 合約執行項目允許輸入小數*/
    ALTER TABLE `crm_contract_unit` CHANGE `total_dis` `total_dis` FLOAT(11) NOT NULL DEFAULT '0' COMMENT '優惠總價';
/*2023-10-02 合約添加簽約時間紀錄*/
    ALTER TABLE `crm_contract` ADD `sign_date` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '簽約日期' AFTER `cdate`;
    UPDATE `crm_contract` SET `sign_date` = `cdate` WHERE `flag` = '1' ;
/*2023-10-26 事件步驟添加時間安排類型*/
    ALTER TABLE `eve_steps` ADD `time_type` TINYINT(1) NOT NULL DEFAULT '1' COMMENT '時間安排類型 1.執行 0.預估' AFTER `count_type`;

/*2023-10-27 合約金額上限調整*/
    ALTER TABLE `crm_contract` CHANGE `allmoney` `allmoney` DOUBLE(64,2) NOT NULL DEFAULT '0.00' COMMENT '總金額';
    ALTER TABLE `crm_contract_unit` CHANGE `total` `total` DOUBLE(64,2) NOT NULL DEFAULT '0.00' COMMENT '金額';
    ALTER TABLE `crm_contract_unit` CHANGE `total_dis` `total_dis` DOUBLE(64,2) NOT NULL DEFAULT '0.00' COMMENT '優惠總價';
    ALTER TABLE `crm_othermoney` CHANGE `dqmoney` `dqmoney` DOUBLE(64,2) NOT NULL COMMENT '當期出貨金額';
    ALTER TABLE `crm_othermoney` CHANGE `xdj` `xdj` DOUBLE(64,2) NOT NULL DEFAULT '0.00' COMMENT '消订金';
    ALTER TABLE `crm_othermoney` CHANGE `xqj` `xqj` DOUBLE(64,2) NULL DEFAULT NULL COMMENT '消期金';
    ALTER TABLE `crm_shipment` CHANGE `money` `money` DOUBLE(64,2) NOT NULL COMMENT '金额';
    ALTER TABLE `rest_type` CHANGE `deduct_percent` `deduct_percent` DECIMAL(3,2) NOT NULL DEFAULT '0.00' COMMENT '每小時薪資變動';
    UPDATE `powercat` SET `title` = '勤務/假別設定' WHERE `powercat`.`id` = 137;
    UPDATE `powercat` SET `title` = '假勤申核' WHERE `powercat`.`id` = 150;
/*2023-10-30 薪資健勞保設定調整*/
    ALTER TABLE `salary_records`
        DROP `insurance_level`,
        DROP `insurance_level_type`,
        DROP `insurance_health_dependents`;
    ALTER TABLE `salary_records` 
        ADD `insurance` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '投保項目(json)' AFTER `bonus`, 
        ADD `insurance_personal_pay` INT NOT NULL DEFAULT '0' COMMENT '個人負擔額' AFTER `insurance`, 
        ADD `insurance_company_pay` INT NOT NULL DEFAULT '0' COMMENT '公司負擔額' AFTER `insurance_personal_pay`;
    DROP TABLE `insurance_health`, `insurance_labor`, `insurance_level`;
    ALTER TABLE `salary`
        DROP `insurance_level`,
        DROP `insurance_level_type`,
        DROP `insurance_labor`,
        DROP `insurance_labor_company`,
        DROP `insurance_health`,
        DROP `insurance_health_company`,
        DROP `labor_retired`,
        DROP `labor_retired_company`;
    ALTER TABLE `salary` 
        ADD `insurance` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '投保項目(json) ' AFTER `total_bonus_award`, 
        ADD `insurance_personal_pay` INT NOT NULL DEFAULT '0' COMMENT '個人負擔額' AFTER `insurance`, 
        ADD `insurance_company_pay` INT NOT NULL DEFAULT '0' COMMENT '公司負擔額' AFTER `insurance_personal_pay`;
    DELETE FROM `powercat` WHERE `powercat`.`id` = 133;
    DELETE FROM `powercat` WHERE `powercat`.`id` = 134;
    DELETE FROM `powercat` WHERE `powercat`.`id` = 135;
    ALTER TABLE `access`
        DROP `insurancelevel_new`,
        DROP `insurancelevel_red`,
        DROP `insurancelevel_hid`,
        DROP `insurancehealth_new`,
        DROP `insurancehealth_red`,
        DROP `insurancehealth_hid`,
        DROP `insurancelabor_new`,
        DROP `insurancelabor_red`,
        DROP `insurancelabor_hid`;
    ALTER TABLE `salary_records` ADD `pay_type` TINYINT(1) NOT NULL DEFAULT '1' COMMENT '付款方式 0.時薪 1.月薪' AFTER `day_s`;
    UPDATE `salary_records` SET `pay_type` = '0' WHERE `salary_records`.`pay_hour` != 0;
    UPDATE `powercat` SET `title` = '時薪付薪' WHERE `powercat`.`id` = 154;
    ALTER TABLE `salary` 
    CHANGE `pay_hour` `pay_hour` FLOAT(11,3) NOT NULL DEFAULT '0' COMMENT '時薪', 
    CHANGE `hour_count` `hour_count` FLOAT(11,3) NOT NULL DEFAULT '0' COMMENT '時薪時數';
    ALTER TABLE `schedule_date_user` ADD `insurance_personal_pay` INT NULL DEFAULT '0' COMMENT '投保個人負擔額' AFTER `pay_total`;
    ALTER TABLE `schedule_date_user_pay` 
    ADD `pay_total` INT(11) NOT NULL DEFAULT '0' COMMENT '時薪總計' AFTER `user_id`, 
    ADD `insurance_personal_pay` INT(11) NOT NULL DEFAULT '0' COMMENT '保險自付額' AFTER `pay_total`;
    UPDATE `access` SET `status` = '1';
/*日程組更獨立化*/
    ALTER TABLE `schedule` ADD `contract_id` INT(11) NOT NULL DEFAULT '0' COMMENT '對應合約id(若eve_step_id有設定擇依事件設定)' AFTER `eve_step_id`;
/*日程添加備註*/
    ALTER TABLE `schedule_date` ADD `date_note` VARCHAR(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '日程備註' AFTER `turn_salary_time`;

/*更改系統文字追蹤方式*/
    UPDATE `system_parameter` SET `data` = '1' WHERE `system_parameter`.`id` = 1;
    UPDATE `system_parameter` SET `note` = '說明請參考 lang/1/system_parameter.txt 檔案' WHERE `system_parameter`.`id` = 1;

/*調整選單階層*/
    UPDATE `powercat` SET `level` = '2' WHERE `powercat`.`id` = 106;
    UPDATE `powercat` SET `level` = '2' WHERE `powercat`.`id` = 58;
    UPDATE `powercat` SET `parent_id` = '130' WHERE `powercat`.`id` = 132;
    UPDATE `powercat` SET `orders` = '10' WHERE `powercat`.`id` = 132;
    UPDATE `powercat` SET `parent_id` = '130' WHERE `powercat`.`id` = 138;

    INSERT INTO `powercat` 
    (`id`, `level`, `parent_id`, `title`, `codenamed`, `type`, `link`, `description`, `orders`, `status`, `create_time`, `update_time`, `readself`, `readall`, `newcate`, `updatecate`, `delcate`, `truncate`) VALUES 
    (157, '2', '129', '上班時間設定', 'WorkTime', 'normal', NULL, '', '45', '1', '0', '0', '0', '0', '0', '0', '0', '0');
    UPDATE `powercat` SET `title` = '打卡日設定' WHERE `powercat`.`id` = 155;

    ALTER TABLE `eip_user` ADD `work_time_id` INT(11) NOT NULL DEFAULT '0' COMMENT '對應上班時間id' AFTER `use_attendance`;
    ALTER TABLE `eip_user` ADD `pay_count_type` TINYINT(1) NOT NULL DEFAULT '1' COMMENT '時薪統計方式 1.月 2.雙周 3.日' AFTER `use_attendance`;
    CREATE TABLE `work_time` ( 
        `id` INT(11) NOT NULL AUTO_INCREMENT, 
        `time_come` CHAR(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '上班時間' , 
        `time_leave` CHAR(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '下班時間' ,
        PRIMARY KEY (`id`)
    ) ENGINE = InnoDB AUTO_INCREMENT=1;
    INSERT INTO `work_time` (`id`, `time_come`, `time_leave`) VALUES (1, '09:00', '18:00');
    ALTER TABLE `access`
        ADD `worktime_red` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '上班時間瀏覽' AFTER `attendancerecords_all`,
        ADD `worktime_new` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '上班時間新增' AFTER `worktime_red`,
        ADD `worktime_del` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '上班時間刪除' AFTER `worktime_new`;
    UPDATE `access` SET
        `worktime_red` = '1',
        `worktime_new` = '1',
        `worktime_del` = '1'
    WHERE `access`.`id` = 1;

    UPDATE `system_parameter` SET `data` = '09:00, 18:15' WHERE `system_parameter`.`id` = 12;
    UPDATE `system_parameter` SET `note` = '預設上下班時間' WHERE `system_parameter`.`id` = 12;
    
    DROP TABLE `attendance_records`;
    DROP TABLE `attendance_date`;
    CREATE TABLE `attendance_date`(
        `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `date` DATE NOT NULL COMMENT '出勤日期',
        `user_id` INT(10) UNSIGNED NOT NULL COMMENT '員工ID ref:eip_user.id',
        `time_come` TIME NULL DEFAULT NULL COMMENT '上班時間',
        `time_leave` TIME NULL DEFAULT NULL COMMENT '下班時間',
        PRIMARY KEY(`id`) USING BTREE,
        UNIQUE INDEX `date_user_id`(`date`, `user_id`) USING BTREE
    ) COMMENT = '出勤紀錄(範本,誤刪)' COLLATE = 'utf8mb4_general_ci' ENGINE = INNODB AUTO_INCREMENT = 1;
    ALTER TABLE `access`
        DROP `attendancedate_new`,
        DROP `attendancedate_del`;
    ALTER TABLE`access` ADD 
        `attendancedate_edi` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '打卡管理編輯' AFTER `attendancedate_red`;
    UPDATE `access` 
        SET `attendancedate_edi` = '1' 
        WHERE `access`.`id` = 1;
/*清除月薪的在職率細項紀錄*/
    ALTER TABLE `salary` DROP `month_count_detail`;
    DELETE FROM `salary`;
/*清除營運統計公司詳細記錄*/
    ALTER TABLE `balance` DROP `companys_content`;
    DELETE FROM `balance`;
    ALTER TABLE `salary` CHANGE `total_bonus_award` `total_bonus_award` INT(11) NOT NULL DEFAULT '0' COMMENT '總加給-獎金' AFTER `total_bonus`;
    ALTER TABLE `salary` CHANGE `total_pay_month` `total_pay_month` INT(11) NOT NULL DEFAULT '0' COMMENT '總月薪(在職率*(月薪+月加給))';
/*調整排班的時薪計算*/
    ALTER TABLE `user_skill` CHANGE `hour_pay` `hour_pay` FLOAT(11,2) NOT NULL COMMENT '技能時薪';
    ALTER TABLE `salary_records_skill` CHANGE `hour_pay` `hour_pay` FLOAT(11,2) NOT NULL COMMENT '時薪';
    ALTER TABLE `user_skill` ADD `hour_pay_over` FLOAT(11,2) NOT NULL COMMENT '技能時薪(加班)' AFTER `hour_pay`;
    UPDATE `user_skill` SET `hour_pay_over` = `user_skill`.`hour_pay`;
    ALTER TABLE `salary_records_skill` ADD `hour_pay_over` FLOAT(11,2) NOT NULL COMMENT '時薪(加班)' AFTER `hour_pay`;
    UPDATE `salary_records_skill` SET `hour_pay_over` = `salary_records_skill`.`hour_pay`;
    ALTER TABLE `salary_records` CHANGE `pay_hour` `pay_hour` FLOAT(11,2) NOT NULL DEFAULT '0' COMMENT '時薪';
    ALTER TABLE `schedule_date_user` CHANGE `user_hour_pay` `user_hour_pay` FLOAT(11,2) NOT NULL DEFAULT '0' COMMENT '出勤人_時薪';
    ALTER TABLE `schedule_date_user` ADD `user_hour_pay_over` FLOAT(11,2) NOT NULL COMMENT '出勤人_時薪(加班)' AFTER `user_hour_pay`;
    ALTER TABLE `schedule_date_user` ADD `change_num` INT(11) NOT NULL DEFAULT '0' COMMENT '獎懲調薪' AFTER `note`;
    ALTER TABLE `schedule_date_user_pay` ADD `change_num` INT(11) NOT NULL DEFAULT '0' COMMENT '獎懲調薪' AFTER `pay_total`;

    ALTER TABLE `schedule_date_user` ADD `roll_call_come_name` VARCHAR(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '點上班名者' AFTER `roll_call_come`;
    ALTER TABLE `schedule_date_user` ADD `roll_call_leave_name` VARCHAR(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '點下班名者' AFTER `roll_call_leave`;
    ALTER TABLE `schedule_date_user` ADD `roll_call_overtime_name` VARCHAR(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '確認薪資者' AFTER `roll_call_overtime`;
    ALTER TABLE `schedule_date_user` CHANGE `roll_call_overtime` `roll_call_overtime` VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '確認薪資';
    ALTER TABLE `schedule_date` ADD `turn_salary_time_name` VARCHAR(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '拋轉薪資者' AFTER `turn_salary_time`;
    ALTER TABLE `schedule_date_user` 
        CHANGE `roll_call_overtime` `roll_call_confirm` VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '確認薪資', 
        CHANGE `roll_call_overtime_name` `roll_call_confirm_name` VARCHAR(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '確認薪資者';

    ALTER TABLE `access` ADD `schedule_all` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '日程管理看全部(看操作者)' AFTER `schedule_del`;
    UPDATE `access` 
        SET `schedule_all` = '1' 
        WHERE `access`.`id` = 1;
/*排班自動化請款*/
    /*工種管理添加請款的預設值&統計的設定*/
    ALTER TABLE `user_skill` 
    ADD `hour_price` FLOAT(11,2) NOT NULL DEFAULT '0' COMMENT '請款金額' AFTER `hour_pay_over`, 
    ADD `hour_price_over` FLOAT(11,2) NOT NULL DEFAULT '0' COMMENT '加班請款金額' AFTER `hour_price`, 
    ADD `account_in_id` INT(11) NOT NULL DEFAULT '0' COMMENT '進項會計名稱' AFTER `hour_price_over`, 
    ADD `account_out_id` INT(11) NOT NULL DEFAULT '0' COMMENT '出項會計名稱' AFTER `account_in_id`;

    CREATE TABLE `crm_contract_user_skill` ( 
        `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `pid` INT(11) NOT NULL COMMENT '對應合約id',
        `user_skill_id` INT(11) NOT NULL COMMENT '對應工種id(營運統計用)' , 
        `hour_price` FLOAT(11,2) NOT NULL DEFAULT '0' COMMENT '請款金額' , 
        `hour_price_over` FLOAT(11,2) NOT NULL DEFAULT '0' COMMENT '加班請款金額',
        PRIMARY KEY(`id`) USING BTREE,
        INDEX `FK_crm_contract_user_skill_to_user_skill_id`(`user_skill_id`) USING BTREE,
        CONSTRAINT `FK_crm_contract_user_skill_to_user_skill_id` FOREIGN KEY(`user_skill_id`) REFERENCES `user_skill`(`id`) ON UPDATE NO ACTION ON DELETE NO ACTION
    ) COMMENT = '合約內容-人力請款(工種請款)' COLLATE = 'utf8mb4_general_ci' ENGINE = INNODB AUTO_INCREMENT = 1;
    /*合約添加欄位*/
    ALTER TABLE `crm_contract` ADD `topic` VARCHAR(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '合約主題' AFTER `sn`;

    ALTER TABLE `schedule_date_user` 
    CHANGE `user_hour_pay` `user_hour_pay` FLOAT(11,2) NOT NULL COMMENT '出勤人_時薪' AFTER `roll_call_confirm_name`,
    CHANGE `user_hour_pay_over` `user_hour_pay_over` FLOAT(11,2) NOT NULL COMMENT '出勤人_時薪(加班)' AFTER `do_hour`,
    CHANGE `note` `note` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '備註' AFTER `pay_total`;
    ALTER TABLE `schedule_date_user` CHANGE `pay_total` `pay_total` INT(11) NOT NULL DEFAULT '0' COMMENT '正規薪資(拋轉薪資資料時計算，薪*時)';
    ALTER TABLE `schedule_date_user` CHANGE `change_num` `change_num` INT(11) NOT NULL DEFAULT '0' COMMENT '獎懲調薪(可正可負)' AFTER `pay_total`;
    ALTER TABLE `schedule_date_user` CHANGE `insurance_personal_pay` `insurance_personal_pay` INT(11) NOT NULL DEFAULT '0' COMMENT '投保個人負擔額' AFTER `change_num`;

    ALTER TABLE `schedule_date` 
    ADD `moneyid` INT(11) NOT NULL DEFAULT '0' COMMENT '對應crm_othermoney id' AFTER `turn_salary_time_name`, 
    ADD `create_money_name` VARCHAR(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '請款操作者' AFTER `moneyid`;
    ALTER TABLE `crm_shipment` ADD `content_table` CHAR(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'crm_contract_unit' COMMENT '請款內容對應項目表(取會計名稱用)' AFTER `contract_unit_id`;
    ALTER TABLE `crm_shipment` CHANGE `contract_unit_id` `contract_unit_id` INT(11) NOT NULL DEFAULT '0' COMMENT '請款內容對應項目表的主鍵值(取會計名稱用)';
    ALTER TABLE `schedule_date_user` DROP `insurance_personal_pay`;
    ALTER TABLE `schedule_date_user_pay` CHANGE `pay_sum` `pay_sum` INT(11) NOT NULL DEFAULT '1' COMMENT '總支付額(時總+獎調-保)';
    ALTER TABLE `schedule` ADD `auto_money` TINYINT(1) NOT NULL DEFAULT '1' COMMENT '是否依人力請款 0.是 1.否' AFTER `contract_id`;
/*客戶區分供應商*/
    ALTER TABLE `crm_crm` ADD `status_supplier` TINYINT(1) NOT NULL DEFAULT '1' COMMENT '是否為供應商 1.否 2.是' AFTER `nick`;
    UPDATE `powercat` SET `title` = '廠商管理' WHERE `powercat`.`id` = 15;
    INSERT INTO `powercat` 
    (`id`, `level`, `parent_id`, `title`, `codenamed`, `type`, `link`, `description`, `orders`, `status`, `create_time`, `update_time`, `readself`, `readall`, `newcate`, `updatecate`, `delcate`, `truncate`) VALUES 
    (158, '1', '15', '供應商列表', 'Supplier', 'normal', NULL, '', '2', '1', '0', '0', '1', '1', '0', '1', '1', '1');
    UPDATE `powercat` SET `title` = '匯入廠商' WHERE `powercat`.`id` = 21;
    ALTER TABLE `crm_crm`
        DROP `pay_money_month`,
        DROP `pay_money_date`,
        DROP `receive_mothod`,
        DROP `pay_mothod`,
        DROP `need_track`;
    ALTER TABLE `crm_cum_sourse` DROP `cid`;
    UPDATE `powercat` SET `link` = '/index.php/Custo/index?status_supplier=2' WHERE `powercat`.`id` = 158;
    UPDATE `powercat` SET `codenamed` = '' WHERE `powercat`.`id` = 158;
/*打卡添加是否需要計算出缺席判斷*/
    ALTER TABLE `attendance_date` ADD `need_show` TINYINT(1) NOT NULL DEFAULT '1' COMMENT '是否需計算出席率' AFTER `time_leave`;
    ALTER TABLE `crm_crm` CHANGE `zbe` `zbe` DOUBLE(64,0) NOT NULL DEFAULT 0 COMMENT '資本額';
/*商品添加分類*/
    ALTER TABLE `crm_cum_cat_unit` ADD `category_id` INT(11) NOT NULL DEFAULT '0' COMMENT '對應分類id' AFTER `accountant_id`;
    CREATE TABLE `crm_cum_cat_unit_category` ( 
        `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `get_or_pay` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '收款或付款 0.收款 1.付款', 
        `name` VARCHAR(16) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '名稱',
        `orders` INT(11) NOT NULL COMMENT '排序', 
        `status` TINYINT(1) NOT NULL DEFAULT '1' COMMENT '狀態', 
        PRIMARY KEY(`id`) USING BTREE
    ) COMMENT = '合約項目(商品)分類' COLLATE = 'utf8mb4_general_ci' ENGINE = INNODB AUTO_INCREMENT = 1;
    CREATE TABLE `crm_cum_cat_unit_crm` ( 
        `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `crm_id` INT(11) NOT NULL COMMENT '對應客戶id', 
        `crm_cum_cat_unit_id` INT(11) NOT NULL COMMENT '對應商品id',
        UNIQUE (`crm_id`, `crm_cum_cat_unit_id`),
        PRIMARY KEY(`id`) USING BTREE
    ) COMMENT = '供應商提供提供合約項目(商品)關係表' COLLATE = 'utf8mb4_general_ci' ENGINE = INNODB AUTO_INCREMENT = 1;
/*刪除商品的庫存欄位*/
    ALTER TABLE `crm_cum_cat_unit`
        DROP `inventory`,
        DROP `inventory_predict`;
    ALTER TABLE `crm_contract_unit`
        DROP `inventory`,
        DROP `inventory_predict`;
    DROP TABLE `crm_contract_unit2`;
    ALTER TABLE `crm_contract` ADD `pay_to` INT(11) NOT NULL DEFAULT '0' COMMENT '付款對象(屬於哪個收款合約)' AFTER `get_or_pay`;
    ALTER TABLE `crm_contract` ADD `belongs_to` INT(11) NOT NULL DEFAULT '0' COMMENT '附屬對象(屬於哪個主要合約)' AFTER `get_or_pay`;

/*款項建立&審核通過添加人名紀錄*/
    ALTER TABLE `crm_seomoney` 
    ADD `create_user_name` CHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '建立者姓名' AFTER `ship_status`, 
    ADD `audit_user_name` CHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '審核者姓名' AFTER `queryflag`;
    ALTER TABLE `crm_othermoney` 
    ADD `create_user_name` CHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '建立者姓名' AFTER `ship_status`, 
    ADD `audit_user_name` CHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '審核者姓名' AFTER `queryflag`;
