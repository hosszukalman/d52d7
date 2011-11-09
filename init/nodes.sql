-- phpMyAdmin SQL Dump
-- version 3.3.9.2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Nov 09, 2011 at 06:20 AM
-- Server version: 5.5.9
-- PHP Version: 5.3.6

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `epiteszforum_old2new_conection`
--

-- --------------------------------------------------------

--
-- Table structure for table `nodes`
--

CREATE TABLE `nodes` (
  `old_nid` int(11) NOT NULL,
  `new_nid` int(11) NOT NULL,
  PRIMARY KEY (`old_nid`),
  KEY `new_nid` (`new_nid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
