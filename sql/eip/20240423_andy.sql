-- 驗收未過添加預排狀態
ALTER TABLE `wrong_job` ADD `time_type` TINYINT(1) NOT NULL DEFAULT '1' COMMENT '時間安排類型 1.執行 0.預估' AFTER `steps_id`;
