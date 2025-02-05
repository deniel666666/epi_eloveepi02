-- 文件添加亂數，用以判斷是否可編輯內容
ALTER TABLE `file` ADD `edit_code` CHAR(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '更新亂數' AFTER `file_layer`;
