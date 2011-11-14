-- phpMyAdmin SQL Dump
-- version 3.3.9.2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Nov 14, 2011 at 06:44 AM
-- Server version: 5.5.9
-- PHP Version: 5.3.6

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `epiteszforum_old2new_conection`
--

-- --------------------------------------------------------

--
-- Table structure for table `gallery_images`
--

CREATE TABLE `gallery_images` (
  `old_image_id` int(11) NOT NULL,
  `fid` int(11) NOT NULL,
  `gallery_nid` int(11) NOT NULL,
  PRIMARY KEY (`old_image_id`),
  UNIQUE KEY `fid` (`fid`),
  KEY `gallery_nid` (`gallery_nid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
