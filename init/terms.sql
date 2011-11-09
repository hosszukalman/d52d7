-- phpMyAdmin SQL Dump
-- version 3.3.9.2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Nov 09, 2011 at 04:55 AM
-- Server version: 5.5.9
-- PHP Version: 5.3.6

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `epiteszforum_old2new_conection`
--

-- --------------------------------------------------------

--
-- Table structure for table `terms`
--

CREATE TABLE `terms` (
  `old_tid` int(11) NOT NULL,
  `new_tid` int(11) NOT NULL,
  PRIMARY KEY (`old_tid`),
  KEY `new_tid` (`new_tid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
