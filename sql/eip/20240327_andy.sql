-- 合約勾選名單
CREATE TABLE `crm_contract_user` ( 
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `caseid` INT(11) NOT NULL COMMENT '對應合約id', 
  `user_id` INT(11) NOT NULL COMMENT '對應員工id', 
  UNIQUE (`caseid`, `user_id`),
  PRIMARY KEY(`id`) USING BTREE
) COMMENT = '可對此合約自行排班的員工' COLLATE = 'utf8mb4_general_ci' ENGINE = INNODB AUTO_INCREMENT = 1;
