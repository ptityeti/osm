-- script to create the database needed to store the Belgian walking
-- routes.
-- It creates the database structure

-- create database
CREATE DATABASE `osm_walking`;
USE `osm_walking`;


-- create table routelist
-- this will contain all known routes that I care about
CREATE TABLE `routelist`
(
	`id` BIGINT PRIMARY KEY AUTO_INCREMENT,
    `relationid` BIGINT,
    `type` VARCHAR(200),
    `ref` VARCHAR(25),
    `gr_nr` INT DEFAULT NULL,
    `name` VARCHAR(500),
    `location` VARCHAR(200),
    `remark` TEXT    
);

-- create table downloads
-- this will contain one record for each time the update script tries to download a relation
CREATE TABLE `downloads`
(
	`id` BIGINT PRIMARY KEY AUTO_INCREMENT,
    `relationid` BIGINT,
	`starttime` DATETIME NOT NULL,
	`endtime` DATETIME -- is null on creation of the record
) ENGINE=InnoDB;

-- create table relationmembers
-- this contains a list of all members in the relation
CREATE TABLE `relationmembers`
(
	`id` BIGINT PRIMARY KEY AUTO_INCREMENT,
	`downloadid` BIGINT NOT NULL,
	`relationid` BIGINT NOT NULL,
	`memberid` BIGINT NOT NULL,
    `idx` INT NOT NULL, -- gives the order in the relation
	`membertype` VARCHAR(10),
	`role` VARCHAR(200),
	KEY(`downloadid`, `relationid`, `memberid`),
	FOREIGN KEY `FK_relationmembers`(`downloadid`) REFERENCES `downloads`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- create table ways
-- this contains a list of all the ways in the relation with the tags
CREATE TABLE `ways`
(
	`id` BIGINT PRIMARY KEY AUTO_INCREMENT,
	`downloadid` BIGINT NOT NULL,
    `wayid` BIGINT NOT NULL,
    `tag_highway` VARCHAR(100),
    `tag_surface` VARCHAR(100),
    `tag_name` VARCHAR(500),
    `tag_tracktype` VARCHAR(50),
    `taglist` TEXT,
    `length` DOUBLE, -- calculated afterwards based on table pointsinway
    `pointcount` BIGINT,
    KEY(`downloadid`, `wayid`),
    FOREIGN KEY `FK_ways`(`downloadid`) REFERENCES `downloads`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB;

CREATE TABLE `pointsinway`
(
	`id` BIGINT PRIMARY KEY AUTO_INCREMENT,
    `downloadid` BIGINT NOT NULL,
    `wayid` BIGINT NOT NULL,
    `pointid` BIGINT NOT NULL,
    `idx` BIGINT NOT NULL, -- gives the order in the way
    `lat` DOUBLE,
    `lng` DOUBLE,
    UNIQUE(`downloadid`, `wayid`, `pointid`),
    FOREIGN KEY `FK_pointsinway`(`downloadid`) REFERENCES `downloads`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB;

CREATE TABLE `relationtags`
(
	`id` BIGINT PRIMARY KEY AUTO_INCREMENT,
    `downloadid` BIGINT NOT NULL,
    `relationid` BIGINT NOT NULL,
    `key` VARCHAR(250) NOT NULL,
    `value` VARCHAR(250) NOT NULL,
    UNIQUE(`downloadid`, `relationid`, `key`),
    FOREIGN KEY `FK_relationtags`(`downloadid`) REFERENCES `downloads`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB;

CREATE TABLE `relationdatacalculated`
(
    `id` BIGINT PRIMARY KEY AUTO_INCREMENT,
    `downloadid` BIGINT NOT NULL,
    `relationid` BIGINT NOT NULL,
    `totallength` DOUBLE,
    `totalpointcount` INT,
    `components` INT,
    UNIQUE (`downloadid`, `relationid`),
    FOREIGN KEY `FK_relationtags`(`downloadid`) REFERENCES `downloads`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB;
