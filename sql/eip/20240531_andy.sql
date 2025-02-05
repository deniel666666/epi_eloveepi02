-- 添加單位欄位
ALTER TABLE `crm_cum_cat_unit` ADD `unit` CHAR(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '單位' AFTER `type`;
ALTER TABLE `crm_contract_unit` ADD `unit` CHAR(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '單位' AFTER `type`;
ALTER TABLE `crm_shipment` ADD `unit` CHAR(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '單位' AFTER `num`;
-- 用「商品」的單位修改「合約項目」的單位
UPDATE `crm_contract_unit`
SET `crm_contract_unit`.`unit` = (
     SELECT `crm_cum_cat_unit`.`unit`
     FROM `crm_cum_cat_unit`
     WHERE `crm_contract_unit`.`cat_unit_id` = `crm_cum_cat_unit`.`id`
)
WHERE EXISTS (
     SELECT 1
     FROM `crm_cum_cat_unit`
     WHERE `crm_contract_unit`.`cat_unit_id` = `crm_cum_cat_unit`.`id`
);
-- 用「合約項目」的單位修改「請款項目」的單位
UPDATE `crm_shipment`
SET  `crm_shipment`.`unit` = (
     SELECT `crm_contract_unit`.`unit`
     FROM `crm_contract_unit` 
     WHERE `crm_shipment`.`contract_unit_id` = `crm_contract_unit`.`id`
)
WHERE EXISTS (
     SELECT 1
     FROM `crm_contract_unit`
     WHERE `crm_shipment`.`contract_unit_id` = `crm_contract_unit`.`id`
);
