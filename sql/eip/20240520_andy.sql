-- 控制系統輸入金額(0.未稅 1.實收)
INSERT INTO `system_parameter` (`id`, `data`, `note`) VALUES ('13', '1', '控制系統輸入金額(0.未稅 1.實收)');

-- 調整欄位資料
  ALTER TABLE `crm_contract_unit` 
  CHANGE `total` `total` DECIMAL(64,2) NOT NULL DEFAULT '0' COMMENT '金額', 
  CHANGE `total_dis` `total_dis` DECIMAL(64,2) NOT NULL DEFAULT '0' COMMENT '優惠總價';

  ALTER TABLE `crm_contract` 
  CHANGE `allmoney` `allmoney` DECIMAL(64,2) NOT NULL DEFAULT '0' COMMENT '總金額', 
  CHANGE `money` `money` DECIMAL(64,2) NOT NULL DEFAULT '0' COMMENT '實收訂金';

  ALTER TABLE `crm_othermoney` 
  CHANGE `dqmoney` `dqmoney` DECIMAL(64,2) NOT NULL COMMENT '當期出貨金額', 
  CHANGE `upmoney` `upmoney` DECIMAL(10,2) NOT NULL COMMENT 'seo收費上限', 
  CHANGE `fax` `fax` DECIMAL(10,2) NOT NULL DEFAULT '0.00' COMMENT '税款', 
  CHANGE `xdj` `xdj` DECIMAL(64,2) NOT NULL DEFAULT '0.00' COMMENT '消订金', 
  CHANGE `xqj` `xqj` DECIMAL(64,2) NULL DEFAULT NULL COMMENT '消期金', 
  CHANGE `xqj_tax` `xqj_tax` DECIMAL(10,2) NOT NULL DEFAULT '0.00' COMMENT '期金稅金', 
  CHANGE `noding` `noding` DECIMAL(10,2) NOT NULL DEFAULT '0.00' COMMENT '消未收订金';

  ALTER TABLE `crm_seomoney` 
  CHANGE `dqmoney` `dqmoney` DECIMAL(64,2) NOT NULL COMMENT '當期出貨金額', 
  CHANGE `upmoney` `upmoney` DECIMAL(10,2) NOT NULL COMMENT 'seo收費上限', 
  CHANGE `fax` `fax` DECIMAL(10,2) NOT NULL DEFAULT '0.00' COMMENT '税款', 
  CHANGE `xdj` `xdj` DECIMAL(64,2) NOT NULL DEFAULT '0.00' COMMENT '消订金', 
  CHANGE `xqj` `xqj` DECIMAL(64,2) NULL DEFAULT NULL COMMENT '消期金', 
  CHANGE `xqj_tax` `xqj_tax` DECIMAL(10,2) NOT NULL DEFAULT '0.00' COMMENT '期金稅金', 
  CHANGE `noding` `noding` DECIMAL(10,2) NOT NULL DEFAULT '0.00' COMMENT '消未收订金';

  ALTER TABLE `crm_shipment` CHANGE `money` `money` DECIMAL(64,2) NOT NULL COMMENT '金額';
