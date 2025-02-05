-- 調整主機合約的「設定網址」長度
ALTER TABLE `crm_contract_host` CHANGE `h_url` `h_url` VARCHAR(225) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL COMMENT '設定網址';
