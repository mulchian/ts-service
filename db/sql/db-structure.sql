-- -------------------------------------------------------------
-- TablePlus 6.6.8(632)
--
-- https://tableplus.com/
--
-- Database: touchdownstars
-- Generation Time: 2025-08-04 08:48:29.3800
-- -------------------------------------------------------------


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


DROP TABLE IF EXISTS `t_achievement`;
CREATE TABLE `t_achievement` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `achievement` varchar(150) COLLATE utf8mb3_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

DROP TABLE IF EXISTS `t_answer_to_thread`;
CREATE TABLE `t_answer_to_thread` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `topic` varchar(255) NOT NULL,
  `text` varchar(4000) NOT NULL,
  `created` int unsigned NOT NULL,
  `idUser` int unsigned DEFAULT NULL,
  `idThread` int unsigned NOT NULL,
  `idForum` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_to_forumAnswer` (`idUser`),
  KEY `thread_to_forumAnswer` (`idThread`),
  KEY `forum_to_forumAnswer` (`idForum`),
  CONSTRAINT `forum_to_forumAnswer` FOREIGN KEY (`idForum`) REFERENCES `t_forum` (`id`) ON DELETE CASCADE,
  CONSTRAINT `thread_to_forumAnswer` FOREIGN KEY (`idThread`) REFERENCES `t_thread` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_to_forumAnswer` FOREIGN KEY (`idUser`) REFERENCES `t_user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `t_building`;
CREATE TABLE `t_building` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(45) COLLATE utf8mb3_bin NOT NULL,
  `maxLevel` int NOT NULL,
  `description` varchar(100) COLLATE utf8mb3_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

DROP TABLE IF EXISTS `t_building_to_buildingeffect`;
CREATE TABLE `t_building_to_buildingeffect` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `idBuilding` int unsigned NOT NULL,
  `idBuildingEffect` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`),
  KEY `idBuilding_idx` (`idBuilding`),
  KEY `idBuildingEffect_idx` (`idBuildingEffect`),
  CONSTRAINT `idBuilding` FOREIGN KEY (`idBuilding`) REFERENCES `t_building` (`id`),
  CONSTRAINT `idBuildingEffect` FOREIGN KEY (`idBuildingEffect`) REFERENCES `t_buildingeffect` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

DROP TABLE IF EXISTS `t_building_to_stadium`;
CREATE TABLE `t_building_to_stadium` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `idStadium` int unsigned NOT NULL,
  `idBuilding` int unsigned NOT NULL,
  `level` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`),
  UNIQUE KEY `stadium_with_building_UNIQUE` (`idStadium`,`idBuilding`),
  KEY `idStadium_idx` (`idStadium`),
  KEY `idBuilding_idx` (`idBuilding`),
  CONSTRAINT `building_to_stadium` FOREIGN KEY (`idBuilding`) REFERENCES `t_building` (`id`),
  CONSTRAINT `idStadium` FOREIGN KEY (`idStadium`) REFERENCES `t_stadium` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

DROP TABLE IF EXISTS `t_buildingeffect`;
CREATE TABLE `t_buildingeffect` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(45) COLLATE utf8mb3_bin NOT NULL,
  `description` varchar(45) COLLATE utf8mb3_bin NOT NULL,
  `value` decimal(3,2) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

DROP TABLE IF EXISTS `t_character`;
CREATE TABLE `t_character` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `description` varchar(45) COLLATE utf8mb3_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

DROP TABLE IF EXISTS `t_chat`;
CREATE TABLE `t_chat` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `idUser1` int unsigned NOT NULL,
  `idUser2` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user1_to_user` (`idUser1`),
  KEY `user2_to_user` (`idUser2`),
  CONSTRAINT `user1_to_user` FOREIGN KEY (`idUser1`) REFERENCES `t_user` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user2_to_user` FOREIGN KEY (`idUser2`) REFERENCES `t_user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `t_coaching`;
CREATE TABLE `t_coaching` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `gameplanNr` enum('1','2','3','4','5') NOT NULL,
  `teamPart` enum('offense','defense','general') NOT NULL,
  `down` enum('1st','2nd','3rd','4th') NOT NULL,
  `playrange` enum('Short','Middle','Long','Run','Pass','General') NOT NULL,
  `gameplay1` varchar(255) NOT NULL,
  `gameplay2` varchar(255) NOT NULL,
  `rating` int NOT NULL,
  `idTeam` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `index_unique` (`gameplanNr`,`teamPart`,`down`,`playrange`,`idTeam`),
  KEY `coaching_to_team` (`idTeam`),
  CONSTRAINT `coaching_to_team` FOREIGN KEY (`idTeam`) REFERENCES `t_team` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1803 DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `t_coachingname`;
CREATE TABLE `t_coachingname` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `gameplanNr` enum('1','2','3','4','5') NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `teamPart` enum('general','offense','defense') NOT NULL,
  `idTeam` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `team_to_coachingname` (`idTeam`),
  CONSTRAINT `team_to_coachingname` FOREIGN KEY (`idTeam`) REFERENCES `t_team` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `t_conference`;
CREATE TABLE `t_conference` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `description` varchar(45) COLLATE utf8mb3_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

DROP TABLE IF EXISTS `t_contract`;
CREATE TABLE `t_contract` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `salary` int NOT NULL,
  `signingBonus` int NOT NULL DEFAULT '0',
  `endOfContract` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=341 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

DROP TABLE IF EXISTS `t_division`;
CREATE TABLE `t_division` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `description` varchar(45) COLLATE utf8mb3_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

DROP TABLE IF EXISTS `t_draftposition`;
CREATE TABLE `t_draftposition` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `idLeague` int unsigned NOT NULL,
  `season` int NOT NULL,
  `round` int DEFAULT NULL,
  `pick` int DEFAULT NULL,
  `isDrafted` tinyint DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`),
  KEY `league_to_draftposition` (`idLeague`),
  CONSTRAINT `league_to_draftposition` FOREIGN KEY (`idLeague`) REFERENCES `t_league` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

DROP TABLE IF EXISTS `t_employee`;
CREATE TABLE `t_employee` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `idJob` int unsigned NOT NULL,
  `idTeam` int unsigned DEFAULT NULL,
  `lastName` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `firstName` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `age` int NOT NULL,
  `nationality` varchar(255) COLLATE utf8mb3_bin NOT NULL,
  `ovr` int NOT NULL,
  `talent` int NOT NULL,
  `experience` float NOT NULL DEFAULT '0',
  `moral` decimal(3,2) DEFAULT NULL,
  `unemployedSeasons` int NOT NULL DEFAULT '0',
  `marketValue` int NOT NULL,
  `idContract` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`),
  UNIQUE KEY `employeeWithoutTeam_UNIQUE` (`idJob`,`lastName`,`firstName`,`nationality`),
  UNIQUE KEY `idJob_idTeam_UNIQUE` (`idJob`,`idTeam`) USING BTREE,
  UNIQUE KEY `contract_UNIQUE` (`idContract`),
  KEY `team_to_employee` (`idTeam`),
  CONSTRAINT `contract_to_employee` FOREIGN KEY (`idContract`) REFERENCES `t_contract` (`id`) ON DELETE SET NULL,
  CONSTRAINT `job_to_employee` FOREIGN KEY (`idJob`) REFERENCES `t_job` (`id`),
  CONSTRAINT `team_to_employee` FOREIGN KEY (`idTeam`) REFERENCES `t_team` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=71 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

DROP TABLE IF EXISTS `t_event`;
CREATE TABLE `t_event` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `gameTime` int unsigned NOT NULL,
  `season` int unsigned NOT NULL,
  `gameDay` int unsigned DEFAULT NULL,
  `home` varchar(45) DEFAULT NULL,
  `away` varchar(45) DEFAULT NULL,
  `result` varchar(10) DEFAULT NULL,
  `idLeague` int unsigned DEFAULT NULL,
  `homeAccepted` tinyint(1) NOT NULL DEFAULT '0',
  `awayAccepted` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`),
  UNIQUE KEY `gameday_UNIQUE` (`gameTime`,`season`,`gameDay`,`home`,`away`) USING BTREE,
  KEY `game_schedule_to_league` (`idLeague`),
  CONSTRAINT `game_schedule_to_league` FOREIGN KEY (`idLeague`) REFERENCES `t_league` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=545 DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `t_fanbase`;
CREATE TABLE `t_fanbase` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `idTeam` int unsigned NOT NULL,
  `amount` int DEFAULT '0',
  `satisfaction` decimal(3,2) DEFAULT '0.50',
  `expectedWins` int DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`),
  UNIQUE KEY `idTeam_UNIQUE` (`id`),
  KEY `idTeam_idx` (`idTeam`) USING BTREE,
  CONSTRAINT `fanbase_to_team` FOREIGN KEY (`idTeam`) REFERENCES `t_team` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

DROP TABLE IF EXISTS `t_forum`;
CREATE TABLE `t_forum` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `t_gameplay_calculation`;
CREATE TABLE `t_gameplay_calculation` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `gameplay` varchar(255) NOT NULL,
  `defGameplay` varchar(255) DEFAULT NULL,
  `calculation` int unsigned NOT NULL,
  `difference` varchar(20) NOT NULL,
  `distances` varchar(4048) NOT NULL,
  `chances` varchar(4048) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `calculation` (`gameplay`,`defGameplay`,`calculation`,`difference`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=304 DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `t_gameplay_history`;
CREATE TABLE `t_gameplay_history` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `gameplayTime` int unsigned NOT NULL,
  `quarter` int unsigned NOT NULL,
  `playClock` int unsigned NOT NULL,
  `yardsToTD` int NOT NULL DEFAULT '65',
  `yardsToFirstDown` int NOT NULL DEFAULT '10',
  `startQuarter` int unsigned NOT NULL,
  `startPlayClock` int unsigned NOT NULL,
  `startYardsToTD` int NOT NULL DEFAULT '65',
  `startYardsToFirstDown` int NOT NULL DEFAULT '10',
  `down` enum('1st','2nd','3rd','4th') NOT NULL DEFAULT '1st',
  `runner` enum('RB','FB','QB') DEFAULT NULL,
  `secondRB` tinyint(1) NOT NULL DEFAULT '0',
  `isKickOff` tinyint(1) NOT NULL DEFAULT '0',
  `isFG` tinyint(1) NOT NULL DEFAULT '0',
  `isTD` tinyint(1) NOT NULL DEFAULT '0',
  `isPAT` tinyint(1) NOT NULL DEFAULT '0',
  `isPunt` tinyint(1) NOT NULL DEFAULT '0',
  `isTwoPointConversion` tinyint(1) NOT NULL DEFAULT '0',
  `isInterception` tinyint(1) NOT NULL DEFAULT '0',
  `gametext` varchar(4048) NOT NULL,
  `offGameplanNr` enum('1','2','3','4','5') DEFAULT NULL,
  `defGameplanNr` enum('1','2','3','4','5') DEFAULT NULL,
  `offGameplay` varchar(255) DEFAULT NULL,
  `defGameplay` varchar(255) DEFAULT NULL,
  `idOffTeam` int unsigned NOT NULL,
  `idDefTeam` int unsigned NOT NULL,
  `idLeagueGame` int unsigned DEFAULT NULL,
  `idFriendlyGame` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idLeagueGame` (`idLeagueGame`),
  CONSTRAINT `idLeagueGame` FOREIGN KEY (`idLeagueGame`) REFERENCES `t_event` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4638 DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `t_gameplay_standings`;
CREATE TABLE `t_gameplay_standings` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `score` varchar(10) NOT NULL DEFAULT '0;0',
  `score1` varchar(10) NOT NULL DEFAULT '0;0',
  `score2` varchar(10) NOT NULL DEFAULT '0;0',
  `score3` varchar(10) NOT NULL DEFAULT '0;0',
  `score4` varchar(10) NOT NULL DEFAULT '0;0',
  `ot` varchar(10) NOT NULL DEFAULT '0;0',
  `idLeagueGame` int unsigned DEFAULT NULL,
  `idFriendlyGame` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idFriendlyGame_to_game` (`idFriendlyGame`),
  KEY `idLeagueGame_to_game` (`idLeagueGame`),
  CONSTRAINT `idFriendlyGame_to_game` FOREIGN KEY (`idFriendlyGame`) REFERENCES `t_event` (`id`) ON DELETE CASCADE,
  CONSTRAINT `idLeagueGame_to_game` FOREIGN KEY (`idLeagueGame`) REFERENCES `t_event` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `t_gameplay_to_positional_skills`;
CREATE TABLE `t_gameplay_to_positional_skills` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `calcNr` enum('1','2','3') NOT NULL,
  `lineupPosition` varchar(10) NOT NULL,
  `gameplay` varchar(255) NOT NULL,
  `skillNames` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_position_gameplay` (`calcNr`,`lineupPosition`,`gameplay`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=403 DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `t_gametexts`;
CREATE TABLE `t_gametexts` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `language` varchar(5) NOT NULL DEFAULT 'de',
  `gameplay` enum('Run','Pass','Special') DEFAULT NULL,
  `situation` enum('Fumble','Penalty','Safety','Sack','Interception','Deflection','Incomplete') DEFAULT NULL,
  `textName` varchar(255) DEFAULT NULL,
  `triggeringPosition` varchar(50) DEFAULT NULL,
  `playrangeVon` int DEFAULT NULL,
  `playrangeBis` int DEFAULT NULL,
  `td` tinyint(1) NOT NULL DEFAULT '0',
  `text` varchar(4048) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_text` (`language`,`gameplay`,`situation`,`textName`,`triggeringPosition`,`playrangeVon`,`playrangeBis`,`td`)
) ENGINE=InnoDB AUTO_INCREMENT=188 DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `t_injury_to_player`;
CREATE TABLE `t_injury_to_player` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `idPlayer` int unsigned NOT NULL,
  `injury` varchar(45) COLLATE utf8mb3_bin NOT NULL,
  `time` int NOT NULL,
  `isHealed` tinyint DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`),
  KEY `idPlayer_idx` (`idPlayer`),
  CONSTRAINT `player_to_injury` FOREIGN KEY (`idPlayer`) REFERENCES `t_player` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

DROP TABLE IF EXISTS `t_job`;
CREATE TABLE `t_job` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(45) COLLATE utf8mb3_bin NOT NULL,
  `description` varchar(255) COLLATE utf8mb3_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `description_UNIQUE` (`name`),
  UNIQUE KEY `id_UNIQUE` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

DROP TABLE IF EXISTS `t_league`;
CREATE TABLE `t_league` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `description` varchar(45) COLLATE utf8mb3_bin NOT NULL,
  `leagueNumber` int NOT NULL,
  `country` enum('Deutschland') COLLATE utf8mb3_bin NOT NULL DEFAULT 'Deutschland',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

DROP TABLE IF EXISTS `t_main`;
CREATE TABLE `t_main` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `firstGameday` int unsigned NOT NULL,
  `lastSeasonday` int unsigned NOT NULL,
  `season` int unsigned NOT NULL,
  `gameweek` enum('1','2','3','4') NOT NULL DEFAULT '1',
  `gameday` int unsigned NOT NULL,
  `highestSalaryCap` int unsigned NOT NULL,
  `startBudget` int unsigned NOT NULL,
  `lastChanged` int unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `t_messages`;
CREATE TABLE `t_messages` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `fromUser` int unsigned NOT NULL,
  `toUser` int unsigned NOT NULL,
  `message` text NOT NULL,
  `sendOn` int unsigned NOT NULL,
  `readIt` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fromUser_to_user` (`fromUser`),
  KEY `toUser_to_user` (`toUser`),
  CONSTRAINT `fromUser_to_user` FOREIGN KEY (`fromUser`) REFERENCES `t_user` (`id`) ON DELETE CASCADE,
  CONSTRAINT `toUser_to_user` FOREIGN KEY (`toUser`) REFERENCES `t_user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `t_penalty`;
CREATE TABLE `t_penalty` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `gameplay` enum('Run','Pass') NOT NULL,
  `teamPart` enum('Offense','Defense') NOT NULL,
  `timescale` enum('vorher','nachher') NOT NULL,
  `penalty` varchar(255) NOT NULL,
  `chance` float unsigned NOT NULL,
  `yards` int NOT NULL,
  `isFirstDown` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `penalty` (`gameplay`,`teamPart`,`penalty`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `t_picture`;
CREATE TABLE `t_picture` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `pictureLocation` varchar(255) NOT NULL,
  `height` int unsigned NOT NULL,
  `width` int unsigned NOT NULL,
  `idUser` int unsigned DEFAULT NULL,
  `idTeam` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `logo_to_user` (`idUser`),
  KEY `logo_to_team` (`idTeam`),
  CONSTRAINT `logo_to_team` FOREIGN KEY (`idTeam`) REFERENCES `t_team` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `logo_to_user` FOREIGN KEY (`idUser`) REFERENCES `t_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `t_player`;
CREATE TABLE `t_player` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `lastName` varchar(45) COLLATE utf8mb3_bin NOT NULL,
  `firstName` varchar(45) COLLATE utf8mb3_bin NOT NULL,
  `age` int NOT NULL,
  `nationality` varchar(45) COLLATE utf8mb3_bin NOT NULL,
  `height` int NOT NULL,
  `weight` int NOT NULL,
  `marketValue` int NOT NULL,
  `energy` decimal(3,2) NOT NULL DEFAULT '1.00',
  `moral` decimal(3,2) NOT NULL DEFAULT '1.00',
  `minContractMoral` decimal(3,2) NOT NULL DEFAULT '0.75',
  `experience` float NOT NULL DEFAULT '0',
  `talent` int NOT NULL,
  `skillpoints` decimal(7,4) unsigned NOT NULL DEFAULT '0.0000',
  `timeInLeague` int NOT NULL,
  `hallOfFame` tinyint DEFAULT '0',
  `trainingGroup` enum('TE0','TE1','TE2','TE3') COLLATE utf8mb3_bin NOT NULL DEFAULT 'TE0',
  `intensity` enum('1','2','3') COLLATE utf8mb3_bin NOT NULL DEFAULT '1',
  `numberOfTrainings` enum('0','1','2','3') COLLATE utf8mb3_bin NOT NULL DEFAULT '0',
  `lineupPosition` varchar(10) COLLATE utf8mb3_bin DEFAULT NULL,
  `idTeam` int unsigned DEFAULT NULL,
  `idStatus` int unsigned NOT NULL,
  `idCharacter` int unsigned NOT NULL,
  `idContract` int unsigned DEFAULT NULL,
  `idDraftposition` int unsigned DEFAULT NULL,
  `idType` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`),
  UNIQUE KEY `player_UNIQUE` (`lastName`,`firstName`,`nationality`,`height`,`weight`,`idCharacter`,`idType`),
  KEY `type_to_player_idx` (`idType`),
  KEY `character_to_player_idx` (`idCharacter`),
  KEY `draftposition_to_player_idx` (`idDraftposition`),
  KEY `contract_to_player_idx` (`idContract`),
  KEY `status_to_player_idx` (`idStatus`),
  KEY `team_to_player` (`idTeam`),
  CONSTRAINT `character_to_player` FOREIGN KEY (`idCharacter`) REFERENCES `t_character` (`id`),
  CONSTRAINT `contract_to_player` FOREIGN KEY (`idContract`) REFERENCES `t_contract` (`id`) ON DELETE SET NULL,
  CONSTRAINT `draftposition_to_player` FOREIGN KEY (`idDraftposition`) REFERENCES `t_draftposition` (`id`),
  CONSTRAINT `status_to_player` FOREIGN KEY (`idStatus`) REFERENCES `t_status` (`id`),
  CONSTRAINT `team_to_player` FOREIGN KEY (`idTeam`) REFERENCES `t_team` (`id`) ON DELETE SET NULL,
  CONSTRAINT `type_to_player` FOREIGN KEY (`idType`) REFERENCES `t_type` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3338 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

DROP TABLE IF EXISTS `t_position`;
CREATE TABLE `t_position` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `position` varchar(10) COLLATE utf8mb3_bin DEFAULT NULL,
  `description` varchar(45) COLLATE utf8mb3_bin DEFAULT NULL,
  `countStarter` int NOT NULL DEFAULT '1',
  `countBackup` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

DROP TABLE IF EXISTS `t_skill`;
CREATE TABLE `t_skill` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `skillKey` varchar(50) COLLATE utf8mb3_bin NOT NULL,
  `de` varchar(50) COLLATE utf8mb3_bin NOT NULL,
  `en` varchar(50) COLLATE utf8mb3_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

DROP TABLE IF EXISTS `t_skill_to_player`;
CREATE TABLE `t_skill_to_player` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `idPlayer` int unsigned NOT NULL,
  `strength` float(7,4) NOT NULL,
  `speed` float(7,4) NOT NULL,
  `agility` float(7,4) NOT NULL,
  `acceleration` float(7,4) NOT NULL,
  `jump` float(7,4) NOT NULL,
  `passShort` float(7,4) DEFAULT NULL,
  `passMiddle` float(7,4) DEFAULT NULL,
  `passLong` float(7,4) DEFAULT NULL,
  `throw` float(7,4) DEFAULT NULL,
  `pocket` float(7,4) DEFAULT NULL,
  `overview` float(7,4) DEFAULT NULL,
  `ballControl` float(7,4) DEFAULT NULL,
  `breakTackle` float(7,4) DEFAULT NULL,
  `catch` float(7,4) DEFAULT NULL,
  `returning` float(7,4) DEFAULT NULL,
  `blockPass` float(7,4) DEFAULT NULL,
  `blockRun` float(7,4) DEFAULT NULL,
  `runRoute` float(7,4) DEFAULT NULL,
  `safeCatch` float(7,4) DEFAULT NULL,
  `spectacularCatch` float(7,4) DEFAULT NULL,
  `release` float(7,4) DEFAULT NULL,
  `realizeBlitz` float(7,4) DEFAULT NULL,
  `firstStep` float(7,4) DEFAULT NULL,
  `impactBlock` float(7,4) DEFAULT NULL,
  `upfieldBlock` float(7,4) DEFAULT NULL,
  `fastRelease` float(7,4) DEFAULT NULL,
  `saveBlock` float(7,4) DEFAULT NULL,
  `tackle` float(7,4) DEFAULT NULL,
  `hardHit` float(7,4) DEFAULT NULL,
  `safeTackle` float(7,4) DEFAULT NULL,
  `reaction` float(7,4) DEFAULT NULL,
  `coverage` float(7,4) DEFAULT NULL,
  `zoneCoverage` float(7,4) DEFAULT NULL,
  `manCoverage` float(7,4) DEFAULT NULL,
  `kickAccuracy` float(7,4) DEFAULT NULL,
  `puntAccuracy` float(7,4) DEFAULT NULL,
  `power` float(7,4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`),
  UNIQUE KEY `idPlayer_UNIQUE` (`idPlayer`),
  KEY `idPlayer_idx` (`idPlayer`),
  CONSTRAINT `idPlayer` FOREIGN KEY (`idPlayer`) REFERENCES `t_player` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3338 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

DROP TABLE IF EXISTS `t_stadium`;
CREATE TABLE `t_stadium` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `idTeam` int unsigned NOT NULL,
  `name` varchar(45) COLLATE utf8mb3_bin NOT NULL,
  `description` varchar(45) COLLATE utf8mb3_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`),
  UNIQUE KEY `idTeam_UNIQUE` (`id`),
  KEY `idTeam_idx` (`idTeam`) USING BTREE,
  CONSTRAINT `team_to_stadium` FOREIGN KEY (`idTeam`) REFERENCES `t_team` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

DROP TABLE IF EXISTS `t_statistics_player`;
CREATE TABLE `t_statistics_player` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `season` int unsigned NOT NULL,
  `gameId` int unsigned NOT NULL,
  `paCpl` int unsigned DEFAULT NULL,
  `paAtt` int unsigned DEFAULT NULL,
  `paPct` decimal(3,2) unsigned DEFAULT NULL,
  `paYds` int unsigned DEFAULT NULL,
  `paAvg` int unsigned DEFAULT NULL,
  `paTd` int unsigned DEFAULT NULL,
  `paInt` int unsigned DEFAULT NULL,
  `sck` int unsigned DEFAULT NULL,
  `paRtg` decimal(25,2) unsigned DEFAULT NULL,
  `ruAtt` int unsigned DEFAULT NULL,
  `ruYds` int unsigned DEFAULT NULL,
  `ruAvg` int unsigned DEFAULT NULL,
  `ruTd` int unsigned DEFAULT NULL,
  `fum` int unsigned DEFAULT NULL,
  `rec` int unsigned DEFAULT NULL,
  `reYds` int unsigned DEFAULT NULL,
  `reAvg` int unsigned DEFAULT NULL,
  `reTd` int unsigned DEFAULT NULL,
  `reYdsAC` int unsigned DEFAULT NULL,
  `reYdsACAvg` int unsigned DEFAULT NULL,
  `ovrTd` int unsigned DEFAULT NULL,
  `sckA` int unsigned DEFAULT NULL,
  `tkl` int unsigned DEFAULT NULL,
  `tfl` int unsigned DEFAULT NULL,
  `tflYds` int unsigned DEFAULT NULL,
  `sckMade` int unsigned DEFAULT NULL,
  `sckYds` int unsigned DEFAULT NULL,
  `defAvg` int unsigned DEFAULT NULL,
  `sft` int unsigned DEFAULT NULL,
  `defl` int unsigned DEFAULT NULL,
  `ff` int unsigned DEFAULT NULL,
  `intcept` int unsigned DEFAULT NULL,
  `intYds` int unsigned DEFAULT NULL,
  `intTd` int unsigned DEFAULT NULL,
  `fumRec` int unsigned DEFAULT NULL,
  `fumYds` int unsigned DEFAULT NULL,
  `fumTd` int unsigned DEFAULT NULL,
  `fgAtt` int unsigned DEFAULT NULL,
  `fgMade` int unsigned DEFAULT NULL,
  `fgLong` int unsigned DEFAULT NULL,
  `xpAtt` int unsigned DEFAULT NULL,
  `xpMade` int unsigned DEFAULT NULL,
  `puntAtt` int unsigned DEFAULT NULL,
  `puntYds` int unsigned DEFAULT NULL,
  `puntAvg` int unsigned DEFAULT NULL,
  `krAtt` int unsigned DEFAULT NULL,
  `krYds` int unsigned DEFAULT NULL,
  `krAvg` int unsigned DEFAULT NULL,
  `krTd` int unsigned DEFAULT NULL,
  `prAtt` int unsigned DEFAULT NULL,
  `prYds` int unsigned DEFAULT NULL,
  `prAvg` int unsigned DEFAULT NULL,
  `prTd` int unsigned DEFAULT NULL,
  `penalty` int unsigned DEFAULT NULL,
  `penaltyYds` int unsigned DEFAULT NULL,
  `idPlayer` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_gameday_statistics` (`season`,`gameId`,`idPlayer`),
  KEY `statistics_to_player` (`idPlayer`),
  KEY `playerStatistics_to_event` (`gameId`),
  CONSTRAINT `playerStatistics_to_event` FOREIGN KEY (`gameId`) REFERENCES `t_event` (`id`) ON DELETE CASCADE,
  CONSTRAINT `statistics_to_player` FOREIGN KEY (`idPlayer`) REFERENCES `t_player` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=607 DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `t_statistics_team`;
CREATE TABLE `t_statistics_team` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `season` int unsigned NOT NULL,
  `gameId` int unsigned NOT NULL,
  `paAtt` int NOT NULL DEFAULT '0',
  `paYds` int NOT NULL DEFAULT '0',
  `paTd` int NOT NULL DEFAULT '0',
  `ruAtt` int NOT NULL DEFAULT '0',
  `ruYds` int NOT NULL DEFAULT '0',
  `ruTd` int NOT NULL DEFAULT '0',
  `firstDowns` int NOT NULL DEFAULT '0',
  `firstDownsComp` int NOT NULL DEFAULT '0',
  `secondDowns` int NOT NULL DEFAULT '0',
  `secondDownsComp` int NOT NULL DEFAULT '0',
  `thirdDowns` int NOT NULL DEFAULT '0',
  `thirdDownsComp` int NOT NULL DEFAULT '0',
  `fourthDowns` int NOT NULL DEFAULT '0',
  `fourthDownsComp` int NOT NULL DEFAULT '0',
  `penalties` int NOT NULL DEFAULT '0',
  `penaltyYds` int NOT NULL DEFAULT '0',
  `sacks` int NOT NULL DEFAULT '0',
  `punts` int NOT NULL DEFAULT '0',
  `fumbles` int NOT NULL DEFAULT '0',
  `lostFumbles` int NOT NULL DEFAULT '0',
  `interceptions` int NOT NULL DEFAULT '0',
  `timeOfPossession` int NOT NULL DEFAULT '0',
  `idTeam` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_team_statistics` (`season`,`gameId`,`idTeam`),
  KEY `statistics_to_team` (`idTeam`),
  KEY `teamStatistics_to_event` (`gameId`),
  CONSTRAINT `statistics_to_team` FOREIGN KEY (`idTeam`) REFERENCES `t_team` (`id`) ON DELETE CASCADE,
  CONSTRAINT `teamStatistics_to_event` FOREIGN KEY (`gameId`) REFERENCES `t_event` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `t_status`;
CREATE TABLE `t_status` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `description` varchar(45) COLLATE utf8mb3_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

DROP TABLE IF EXISTS `t_team`;
CREATE TABLE `t_team` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(45) COLLATE utf8mb3_bin NOT NULL,
  `abbreviation` varchar(3) COLLATE utf8mb3_bin NOT NULL,
  `budget` int NOT NULL,
  `salaryCap` int NOT NULL,
  `credits` int DEFAULT '0',
  `chemie` int unsigned NOT NULL DEFAULT '100',
  `gameplanGeneral` enum('1','2','3','4','5') COLLATE utf8mb3_bin NOT NULL DEFAULT '1',
  `gameplanOff` enum('1','2','3','4','5') COLLATE utf8mb3_bin NOT NULL DEFAULT '1',
  `gameplanDef` enum('1','2','3','4','5') COLLATE utf8mb3_bin NOT NULL DEFAULT '1',
  `lineupOff` enum('TE','FB') COLLATE utf8mb3_bin NOT NULL DEFAULT 'TE',
  `lineupDef` enum('NT','MLB') COLLATE utf8mb3_bin NOT NULL DEFAULT 'NT',
  `idUser` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`),
  UNIQUE KEY `name_UNIQUE` (`name`),
  UNIQUE KEY `idUser_UNIQUE` (`idUser`),
  KEY `idUser_idx` (`idUser`) USING BTREE,
  CONSTRAINT `team_to_user` FOREIGN KEY (`idUser`) REFERENCES `t_user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=72 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

DROP TABLE IF EXISTS `t_team_to_league`;
CREATE TABLE `t_team_to_league` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `idTeam` int unsigned NOT NULL,
  `idLeague` int unsigned NOT NULL,
  `idConference` int unsigned NOT NULL,
  `idDivision` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idTeam_UNIQUE` (`idTeam`),
  UNIQUE KEY `id_UNIQUE` (`id`),
  KEY `team_to_conference_idx` (`idConference`),
  KEY `team_to_league_idx` (`idLeague`),
  KEY `team_to_division_idx` (`idDivision`),
  CONSTRAINT `conference_to_team` FOREIGN KEY (`idConference`) REFERENCES `t_conference` (`id`),
  CONSTRAINT `division_to_team` FOREIGN KEY (`idDivision`) REFERENCES `t_division` (`id`),
  CONSTRAINT `league_to_team` FOREIGN KEY (`idLeague`) REFERENCES `t_league` (`id`),
  CONSTRAINT `team_to_league` FOREIGN KEY (`idTeam`) REFERENCES `t_team` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=72 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

DROP TABLE IF EXISTS `t_team_to_traininggroup`;
CREATE TABLE `t_team_to_traininggroup` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `trainingGroup` enum('TE1','TE2','TE3') NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `trainingTime` int unsigned DEFAULT NULL,
  `idTeam` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `team_to_traininggroup_UNIQUE` (`trainingGroup`,`idTeam`),
  KEY `team_to_traininggroup` (`idTeam`),
  CONSTRAINT `team_to_traininggroup` FOREIGN KEY (`idTeam`) REFERENCES `t_team` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `t_thread`;
CREATE TABLE `t_thread` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `created` int unsigned NOT NULL,
  `idForum` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `thread_to_forum` (`idForum`),
  CONSTRAINT `thread_to_forum` FOREIGN KEY (`idForum`) REFERENCES `t_forum` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=54 DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `t_type`;
CREATE TABLE `t_type` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `idPosition` int unsigned NOT NULL,
  `description` varchar(45) COLLATE utf8mb3_bin NOT NULL,
  `minHeight` int NOT NULL,
  `maxHeight` int NOT NULL,
  `minWeight` int NOT NULL,
  `maxWeight` int NOT NULL,
  `assignedTeamPart` enum('Offense','Defense','Special Teams','') COLLATE utf8mb3_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`),
  KEY `idPosition_idx` (`idPosition`),
  CONSTRAINT `idPositionTyp` FOREIGN KEY (`idPosition`) REFERENCES `t_position` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

DROP TABLE IF EXISTS `t_type_to_skill`;
CREATE TABLE `t_type_to_skill` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `idType` int unsigned NOT NULL,
  `idSkill` int unsigned NOT NULL,
  `minOVR` int NOT NULL,
  `maxOVR` int NOT NULL,
  `minComb` float DEFAULT NULL,
  `maxComb` float DEFAULT NULL,
  `step` int NOT NULL DEFAULT '1',
  `training` char(1) COLLATE utf8mb3_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`),
  KEY `idType_idx` (`idType`),
  KEY `idSkill_idx` (`idSkill`),
  CONSTRAINT `idSkillType` FOREIGN KEY (`idSkill`) REFERENCES `t_skill` (`id`),
  CONSTRAINT `idTypeSkill` FOREIGN KEY (`idType`) REFERENCES `t_type` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=359 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

DROP TABLE IF EXISTS `t_user`;
CREATE TABLE `t_user` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(45) COLLATE utf8mb3_bin NOT NULL,
  `email` varchar(45) COLLATE utf8mb3_bin NOT NULL,
  `password` varchar(4048) COLLATE utf8mb3_bin NOT NULL,
  `realname` varchar(45) COLLATE utf8mb3_bin DEFAULT NULL,
  `city` varchar(45) COLLATE utf8mb3_bin DEFAULT NULL,
  `gender` enum('Männlich','Weiblich','Divers') COLLATE utf8mb3_bin DEFAULT NULL,
  `birthday` datetime DEFAULT NULL,
  `registerDate` datetime NOT NULL,
  `lastActiveTime` int unsigned NOT NULL,
  `status` enum('online','abwesend','offline') COLLATE utf8mb3_bin NOT NULL DEFAULT 'offline',
  `admin` tinyint(1) NOT NULL DEFAULT '0',
  `deactivated` tinyint(1) NOT NULL DEFAULT '0',
  `activationSent` tinyint(1) NOT NULL DEFAULT '0',
  `activated` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_UNIQUE` (`username`),
  UNIQUE KEY `email_UNIQUE` (`email`),
  UNIQUE KEY `id_UNIQUE` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;



/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;