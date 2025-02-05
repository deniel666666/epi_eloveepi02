-- phpMyAdmin SQL Dump
-- version 4.9.7
-- https://www.phpmyadmin.net/
--
-- 主機： localhost:3306
-- 產生時間： 2021 年 06 月 25 日 12:04
-- 伺服器版本： 5.7.33-cll-lve
-- PHP 版本： 7.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 資料庫： `erp2000_eip_seo_key_rank`
--

-- --------------------------------------------------------

--
-- 資料表結構 `crm_key_copy`
--

CREATE TABLE `crm_key_copy` (
  `id` int(10) NOT NULL,
  `key_Id` int(10) NOT NULL COMMENT '关键字id',
  `key_Name` varchar(50) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT '关键字名称',
  `key_ranking` int(10) NOT NULL DEFAULT '0' COMMENT '排名',
  `BuildDate` char(10) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '""' COMMENT '建立日期',
  `BuildTime` varchar(8) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '""' COMMENT '建立時間',
  `SearchTime` varchar(8) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '""' COMMENT '搜尋時間',
  `update` date NOT NULL COMMENT '更新日期',
  `employee_Id` varchar(20) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT '业务id',
  `searchEngine` varchar(10) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT '搜索引擎',
  `customers_Name` varchar(200) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT '客户名称',
  `c_ID` int(10) NOT NULL DEFAULT '0' COMMENT '合约id',
  `uptime` time NOT NULL COMMENT '更新时间',
  `url_ranking` varchar(100) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT '""' COMMENT '排名网址',
  `states` int(1) NOT NULL DEFAULT '0' COMMENT '关键字属性',
  `PrevRank` int(11) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='SEO每日關鍵字';

-- --------------------------------------------------------

--
-- 資料表結構 `crm_key_rank`
--

CREATE TABLE `crm_key_rank` (
  `id` int(10) NOT NULL,
  `key_Id` int(10) NOT NULL COMMENT '关键字id',
  `key_Name` varchar(50) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT '关键字名称',
  `key_ranking` int(10) NOT NULL DEFAULT '0' COMMENT '排名',
  `BuildDate` char(10) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '""' COMMENT '建立日期',
  `BuildTime` varchar(8) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '""' COMMENT '建立時間',
  `SearchTime` varchar(8) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '""' COMMENT '搜尋時間',
  `update` date NOT NULL COMMENT '更新日期',
  `employee_Id` varchar(20) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT '业务id',
  `searchEngine` varchar(10) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT '搜索引擎',
  `customers_Name` varchar(200) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT '客户名称',
  `c_ID` int(10) NOT NULL DEFAULT '0' COMMENT '合约id',
  `uptime` time NOT NULL COMMENT '更新时间',
  `url_ranking` varchar(100) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT '""' COMMENT '排名网址',
  `states` int(1) NOT NULL DEFAULT '0' COMMENT '关键字属性',
  `PrevRank` int(11) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='SEO每日關鍵字';

--
-- 已傾印資料表的索引
--

--
-- 資料表索引 `crm_key_copy`
--
ALTER TABLE `crm_key_copy`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`);

--
-- 資料表索引 `crm_key_rank`
--
ALTER TABLE `crm_key_rank`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`);

--
-- 在傾印的資料表使用自動遞增(AUTO_INCREMENT)
--

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `crm_key_copy`
--
ALTER TABLE `crm_key_copy`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `crm_key_rank`
--
ALTER TABLE `crm_key_rank`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
