-- 工種添加單位
ALTER TABLE `user_skill` ADD `unit_name` CHAR(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '單位' AFTER `hour_price_over`;
UPDATE `user_skill` SET `unit_name` = '小時';

-- 損益的會計項目
INSERT INTO `accountant_item` (`id`, `get_or_pay`, `parent_id`, `name`, `order_id`, `status`) VALUES ('996', '0', '0', '額外收入', '0', '1');
INSERT INTO `accountant_item` (`id`, `get_or_pay`, `parent_id`, `name`, `order_id`, `status`) VALUES ('997', '1', '0', '額外支出', '0', '1');
INSERT INTO `accountant_item` (`id`, `get_or_pay`, `parent_id`, `name`, `order_id`, `status`) VALUES ('998', '0', '996', '帳款損益', '0', '1');
INSERT INTO `accountant_item` (`id`, `get_or_pay`, `parent_id`, `name`, `order_id`, `status`) VALUES ('999', '1', '997', '帳款損益', '0', '1');

-- 調整資料庫日程組 auto_money 備註
ALTER TABLE `schedule` CHANGE `auto_money` `auto_money` TINYINT(1) NOT NULL DEFAULT '1' COMMENT '是否依人力請款 1.是 0.否';

-- --調整資料庫舊紀錄稅金
  -- 商品
  UPDATE `crm_cum_cat_unit` SET `list_price` = ROUND(`list_price`*1.05), `sale_price` = ROUND(`sale_price`*1.05);
  -- 合約套用商品
  UPDATE `crm_contract_unit` SET `list_price` = ROUND(`list_price`*1.05), `sale_price` = ROUND(`sale_price`*1.05);
  UPDATE `crm_contract_unit` SET `total` = ROUND(`total`*1.05), `total_dis` = ROUND(`total_dis`*1.05);
  ALTER TABLE `crm_contract_unit` 
  CHANGE `total` `total` DOUBLE(64,0) NOT NULL DEFAULT '0' COMMENT '金額', 
  CHANGE `total_dis` `total_dis` DOUBLE(64,0) NOT NULL DEFAULT '0' COMMENT '優惠總價';
  -- 工種
  UPDATE `user_skill` SET `hour_price` = ROUND(`hour_price`*1.05), `hour_price_over` = ROUND(`hour_price_over`*1.05);
  -- 合約套用工種
  UPDATE `crm_contract_user_skill` SET `hour_price` = ROUND(`hour_price`*1.05), `hour_price_over` = ROUND(`hour_price_over`*1.05) WHERE `pid` in (
    SELECT `id` FROM `crm_contract` WHERE `invoice`='二聯' OR `invoice`='三聯'
  );
  -- 合約
  UPDATE `crm_contract` SET `allmoney` = ROUND(`allmoney`*1.05), `money` = ROUND(`money`*1.05) WHERE `invoice`='二聯' OR `invoice`='三聯';
  ALTER TABLE `crm_contract` 
  CHANGE `allmoney` `allmoney` DOUBLE(64,0) NOT NULL DEFAULT '0' COMMENT '總金額', 
  CHANGE `money` `money` FLOAT(64,0) NOT NULL DEFAULT '0' COMMENT '實收訂金';
  -- 合約-SEO
  UPDATE `crm_contract_seo` SET `upmoney` = ROUND(`upmoney`*1.05) WHERE `invoice`='二聯' OR `invoice`='三聯';
  -- 合約-主機
  UPDATE `crm_contract_host` SET 
  `h_num` = ROUND(`h_num`*1.05), 
  `h_money` = ROUND(`h_money`*1.05), 
  `d_num` = ROUND(`d_num`*1.05), 
  `d_money` = ROUND(`d_money`*1.05), 
  `s_num` = ROUND(`s_num`*1.05), 
  `s_money` = ROUND(`s_money`*1.05)
  WHERE `pid` in (
    SELECT `id` FROM `crm_contract` WHERE `invoice`='二聯' OR `invoice`='三聯'
  );
  -- 請款
  UPDATE `crm_othermoney` SET 
  `dqmoney` = ROUND(`dqmoney` + `fax`),
  `upmoney` = ROUND(`upmoney`*1.05),
  `fax` = ROUND(`fax`),
  `tips` = ROUND(`tips`),
  `tips1` = ROUND(`tips1`),
  `xdj` = ROUND(`xdj`*1.05),
  `xqj` = ROUND(`xqj` + `xqj_tax`),
  `xqj_tax` = ROUND(`xqj_tax`)
  WHERE `invoice`='二聯' OR `invoice`='三聯';
  ALTER TABLE `crm_othermoney` 
  CHANGE `dqmoney` `dqmoney` DOUBLE(64,0) NOT NULL COMMENT '當期出貨金額', 
  CHANGE `upmoney` `upmoney` DECIMAL(10,0) NOT NULL COMMENT 'seo收費上限', 
  CHANGE `fax` `fax` DECIMAL(10,0) NOT NULL DEFAULT '0.00' COMMENT '税款', 
  CHANGE `xdj` `xdj` DOUBLE(64,0) NOT NULL DEFAULT '0.00' COMMENT '消订金', 
  CHANGE `xqj` `xqj` DOUBLE(64,0) NULL DEFAULT NULL COMMENT '消期金', 
  CHANGE `xqj_tax` `xqj_tax` DECIMAL(10,0) NOT NULL DEFAULT '0.00' COMMENT '期金稅金', 
  CHANGE `noding` `noding` DECIMAL(10,0) NOT NULL DEFAULT '0.00' COMMENT '消未收订金';
  UPDATE `crm_seomoney` SET 
  `dqmoney` = ROUND(`dqmoney` + `fax`),
  `upmoney` = ROUND(`upmoney`*1.05),
  `fax` = ROUND(`fax`),
  `tips` = ROUND(`tips`),
  `tips1` = ROUND(`tips1`),
  `xdj` = ROUND(`xdj`*1.05),
  `xqj` = ROUND(`xqj` + `xqj_tax`),
  `xqj_tax` = ROUND(`xqj_tax`)
  WHERE `invoice`='二聯' OR `invoice`='三聯';
  ALTER TABLE `crm_seomoney` 
  CHANGE `dqmoney` `dqmoney` DOUBLE(64,0) NOT NULL COMMENT '當期出貨金額', 
  CHANGE `upmoney` `upmoney` DECIMAL(10,0) NOT NULL COMMENT 'seo收費上限', 
  CHANGE `fax` `fax` DECIMAL(10,0) NOT NULL DEFAULT '0.00' COMMENT '税款', 
  CHANGE `xdj` `xdj` DOUBLE(64,0) NOT NULL DEFAULT '0.00' COMMENT '消订金', 
  CHANGE `xqj` `xqj` DOUBLE(64,0) NULL DEFAULT NULL COMMENT '消期金', 
  CHANGE `xqj_tax` `xqj_tax` DECIMAL(10,0) NOT NULL DEFAULT '0.00' COMMENT '期金稅金', 
  CHANGE `noding` `noding` DECIMAL(10,0) NOT NULL DEFAULT '0.00' COMMENT '消未收订金';
  ALTER TABLE `crm_othermoney` 
  CHANGE `tips` `tips` DECIMAL(10,0) NOT NULL DEFAULT '0' COMMENT '帳款益損', 
  CHANGE `tips1` `tips1` DECIMAL(10,0) NOT NULL DEFAULT '0' COMMENT '收付款益損';
  ALTER TABLE `crm_seomoney` 
  CHANGE `tips` `tips` DECIMAL(10,0) NOT NULL DEFAULT '0' COMMENT '帳款益損', 
  CHANGE `tips1` `tips1` DECIMAL(10,0) NOT NULL DEFAULT '0' COMMENT '收付款益損';
  -- 請款-細項
  UPDATE `crm_shipment` SET `money` = ROUND(`money`*1.05) WHERE `caseid` in (
    SELECT `id` FROM `crm_contract` WHERE `invoice`='二聯' OR `invoice`='三聯'
  );
  ALTER TABLE `crm_shipment` CHANGE `money` `money` DOUBLE(64,0) NOT NULL COMMENT '金额';
  -- SEO關鍵字
  UPDATE `crm_seo_key` SET `price` = ROUND(`price`*1.05) WHERE `caseid` in (
    SELECT `id` FROM `crm_contract` WHERE `invoice`='二聯' OR `invoice`='三聯'
  );

-- 更新版本號(9.1以上為合約金額為「實收」、以下為「未稅」)
UPDATE `eip_company` SET `version` = '9.1' WHERE `eip_company`.`id` = 1;