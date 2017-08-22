-- phpMyAdmin SQL Dump
-- version 4.5.4.1deb2ubuntu2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Aug 04, 2017 at 06:00 PM
-- Server version: 5.7.19-0ubuntu0.16.04.1
-- PHP Version: 7.0.18-0ubuntu0.16.04.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `digital_contracts`
--
CREATE DATABASE IF NOT EXISTS `digital_contracts` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `digital_contracts`;

-- --------------------------------------------------------

--
-- Table structure for table `contract`
--

DROP TABLE IF EXISTS `contract`;
CREATE TABLE IF NOT EXISTS `contract` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `ContractHeading` varchar(512) NOT NULL,
  `StartingDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `EndingDate` int(11) DEFAULT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `contract_state`
--

DROP TABLE IF EXISTS `contract_state`;
CREATE TABLE IF NOT EXISTS `contract_state` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `Heading` enum('0','1','2','3') NOT NULL,
  `StartingDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `EndingDate` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

DROP TABLE IF EXISTS `payment`;
CREATE TABLE IF NOT EXISTS `payment` (
  `ContractId` int(11) NOT NULL,
  `SignatoryId` int(11) NOT NULL,
  `Amount` float NOT NULL,
  `DateOfIssuance` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ContractId`,`SignatoryId`,`DateOfIssuance`),
  KEY `fk_signatoryid_payment_signatory` (`SignatoryId`),
  KEY `ContractId` (`ContractId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `signatory`
--

DROP TABLE IF EXISTS `signatory`;
CREATE TABLE IF NOT EXISTS `signatory` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `BusinessName` varchar(255) NOT NULL,
  `HeadQuarters` varchar(255) NOT NULL,
  `Holder` varchar(255) NOT NULL,
  `RegistrationNumber` varchar(255) NOT NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `un_signatory_businessname` (`BusinessName`),
  UNIQUE KEY `un_signatory_registrationumber` (`RegistrationNumber`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `signature`
--

DROP TABLE IF EXISTS `signature`;
CREATE TABLE IF NOT EXISTS `signature` (
  `ContractId` int(11) NOT NULL,
  `SignatoryId` int(11) NOT NULL,
  `SignatoryStatus` enum('Client','Contractor') NOT NULL,
  `DateOfSignature` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `SignatureDigest` varchar(1024) DEFAULT NULL,
  PRIMARY KEY (`ContractId`,`SignatoryId`,`DateOfSignature`),
  KEY `ContractId` (`ContractId`),
  KEY `fk_signatoryid_signature_signatory` (`SignatoryId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `fk_contractid_payment_contract` FOREIGN KEY (`ContractId`) REFERENCES `contract` (`Id`),
  ADD CONSTRAINT `fk_signatoryid_payment_signatory` FOREIGN KEY (`SignatoryId`) REFERENCES `signatory` (`Id`);

--
-- Constraints for table `signature`
--
ALTER TABLE `signature`
  ADD CONSTRAINT `fk_contractid_signature_contract` FOREIGN KEY (`ContractId`) REFERENCES `contract` (`Id`),
  ADD CONSTRAINT `fk_signatoryid_signature_signatory` FOREIGN KEY (`SignatoryId`) REFERENCES `signatory` (`Id`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
