-- 日程添加打卡距離設定
  ALTER TABLE `schedule` ADD `location_range` INT(8) NOT NULL DEFAULT '50' COMMENT '地點GPS距離' AFTER `location_gps`;
