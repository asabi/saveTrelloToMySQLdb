/*
 Navicat MySQL Data Transfer

 Source Server         : sabi.me
 Source Server Type    : MySQL
 Source Server Version : 50546
 Source Host           : localhost
 Source Database       : trello

 Target Server Type    : MySQL
 Target Server Version : 50546
 File Encoding         : utf-8

 Date: 12/23/2015 00:24:11 AM
*/

SET NAMES utf8;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
--  Table structure for `board`
-- ----------------------------
DROP TABLE IF EXISTS `board`;
CREATE TABLE `board` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `idOrganization` varchar(255) DEFAULT NULL,
  `closed` int(10) unsigned DEFAULT NULL,
  `timeCreated` datetime DEFAULT NULL,
  `pinned` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ----------------------------
--  Table structure for `card`
-- ----------------------------
DROP TABLE IF EXISTS `card`;
CREATE TABLE `card` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `shortUrl` varchar(255) DEFAULT NULL,
  `due` datetime DEFAULT NULL,
  `dateLastActivity` datetime DEFAULT NULL,
  `closed` int(10) unsigned DEFAULT NULL,
  `desc` text,
  `idBoard` varchar(255) DEFAULT NULL,
  `idList` varchar(255) DEFAULT NULL,
  `timeCreated` datetime DEFAULT NULL,
  `labels` text,
  `pos` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ----------------------------
--  Table structure for `cardLabel`
-- ----------------------------
DROP TABLE IF EXISTS `cardLabel`;
CREATE TABLE `cardLabel` (
  `idCard` varchar(255) NOT NULL,
  `idLabel` varchar(255) NOT NULL,
  PRIMARY KEY (`idCard`,`idLabel`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ----------------------------
--  Table structure for `label`
-- ----------------------------
DROP TABLE IF EXISTS `label`;
CREATE TABLE `label` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `timeCreated` datetime DEFAULT NULL,
  `idBoard` varchar(255) DEFAULT NULL,
  `uses` varchar(255) DEFAULT NULL,
  `color` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ----------------------------
--  Table structure for `list`
-- ----------------------------
DROP TABLE IF EXISTS `list`;
CREATE TABLE `list` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `closed` int(10) unsigned DEFAULT NULL,
  `timeCreated` datetime DEFAULT NULL,
  `idBoard` varchar(255) DEFAULT NULL,
  `pos` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

SET FOREIGN_KEY_CHECKS = 1;
