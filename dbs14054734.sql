SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;


CREATE TABLE `Durchgaenge` (
  `DurchgangID` int(11) NOT NULL,
  `Reihenfolge` int(11) DEFAULT NULL,
  `Beschreibung` text DEFAULT NULL,
  `Startzeitpunkt` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

INSERT INTO `Durchgaenge` (`DurchgangID`, `Reihenfolge`, `Beschreibung`, `Startzeitpunkt`) VALUES
(1, 1, 'Tag 1 Durchgang 1', NULL),
(2, 2, 'Tag 1 Durchgang 2', NULL),
(3, 3, 'Der 3.', NULL),
(4, 4, 'Der 4.', NULL),
(5, 5, 'Der 5.', NULL),
(6, 6, 'Der 6.', NULL),
(8, 100, 'Tag 2 Durchgang 1', NULL),
(9, 101, 'Tag 2 Durchgang 2', NULL);

CREATE TABLE `Einstellungen` (
  `Parameter` varchar(250) NOT NULL,
  `WertTyp` int(11) NOT NULL COMMENT '0:Text, 1: Float',
  `Wert` varchar(2000) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

INSERT INTO `Einstellungen` (`Parameter`, `WertTyp`, `Wert`) VALUES
('Veranstaltungsname', 0, 'Vereinsmeisterschaften'),
('KampfrichterPIN', 0, '123456'),
('Meldehinweise', 0, 'Bitte Anmelden bis 4.5.2025'),
('Aktuelle_DurchgangID', 1, '0');

CREATE TABLE `Geraete` (
  `GeraetID` int(11) NOT NULL,
  `GeraeteTypID` int(11) DEFAULT NULL COMMENT 'veweist auf GeraeteTypen, Wenn NULL, dann Pause',
  `Beschreibung` varchar(250) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci COMMENT='Sprung 1, Sprung 2, Stückreck, etc.';

INSERT INTO `Geraete` (`GeraetID`, `GeraeteTypID`, `Beschreibung`) VALUES
(1, 1, 'Boden 1'),
(2, 1, 'Boden 2'),
(3, 2, 'Seitpferd'),
(4, 3, 'Ringe'),
(5, 4, 'Sprung 1'),
(6, 4, 'Sprung 2'),
(7, 5, 'Barren'),
(8, 5, 'Stufenbarren'),
(9, 6, 'Reck'),
(10, 6, 'Balken'),
(12, NULL, 'Pause');

CREATE TABLE `GeraeteTypen` (
  `GeraeteTypID` int(11) NOT NULL,
  `Beschreibung` varchar(250) NOT NULL,
  `Reihenfolge` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci COMMENT='Barren, Ringe, Sprung, etc.';

INSERT INTO `GeraeteTypen` (`GeraeteTypID`, `Beschreibung`, `Reihenfolge`) VALUES
(1, 'Boden (m)', 1),
(2, 'Seitpferd (m)', 2),
(3, 'Ringe (m)', 3),
(4, 'Sprung (m)', 4),
(5, 'Barren (m)', 5),
(6, 'Reck (m)', 6),
(7, 'Sprung (w)', 1),
(8, 'Stufenbarren (w)', 2),
(9, 'Schwebebalken (w)', 3),
(10, 'Boden (w)', 4);

CREATE TABLE `Geschlechter` (
  `GeschlechtID` int(11) NOT NULL,
  `Beschreibung` varchar(250) NOT NULL,
  `Beschreibung_kurz` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

INSERT INTO `Geschlechter` (`GeschlechtID`, `Beschreibung`, `Beschreibung_kurz`) VALUES
(1, 'gemischt', 'g'),
(2, 'männlich', 'm'),
(3, 'weiblich', 'w');

CREATE TABLE `Riegen` (
  `RiegenID` int(11) NOT NULL,
  `Beschreibung` varchar(250) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE `Turner` (
  `TurnerID` int(11) NOT NULL,
  `Vorname` varchar(100) NOT NULL,
  `Nachname` varchar(100) NOT NULL,
  `Geburtsdatum` date NOT NULL,
  `GeschlechtID` int(11) NOT NULL DEFAULT 3 COMMENT '2=männlich, 3=weiblich',
  `VereinID` int(11) DEFAULT NULL,
  `WettkampfID` int(11) DEFAULT NULL,
  `RiegenID` int(11) DEFAULT NULL,
  `MannschaftsID` int(11) DEFAULT NULL COMMENT 'Nur Bei Mannschaftswettkämpfen gebraucht, sonst NULL'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE `Verbindung_Durchgaenge_Riegen_Geraete` (
  `VDurchgaengeRiegenID` int(11) NOT NULL,
  `RiegenID` int(11) NOT NULL,
  `DurchgangID` int(11) NOT NULL,
  `GeraetID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

INSERT INTO `Verbindung_Durchgaenge_Riegen_Geraete` (`VDurchgaengeRiegenID`, `RiegenID`, `DurchgangID`, `GeraetID`) VALUES
(41, 1, 1, 1),
(42, 1, 2, 3),
(43, 1, 3, 2),
(44, 1, 4, 7),
(45, 1, 5, 8),
(46, 1, 6, 10),
(47, 2, 1, 2),
(48, 3, 1, 4),
(49, 3, 2, 3),
(50, 3, 3, 1),
(51, 6, 1, 3),
(52, 6, 2, 8),
(53, 6, 3, 7),
(54, 6, 4, 12),
(55, 6, 5, 12);

CREATE TABLE `Vereine` (
  `VereinID` int(11) NOT NULL,
  `Vereinsname` varchar(250) NOT NULL,
  `Stadt` varchar(250) NOT NULL,
  `Meldung_offen` tinyint(1) NOT NULL DEFAULT 0,
  `Geheimnis_fuer_Meldung` varchar(300) NOT NULL COMMENT 'Austauschbar falls jemand unberechtigtes es erhalten hatte'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

INSERT INTO `Vereine` (`VereinID`, `Vereinsname`, `Stadt`, `Meldung_offen`, `Geheimnis_fuer_Meldung`) VALUES
(1, 'Mainz-Mombach', 'Mainz', 0, ''),
(2, 'Turn- und Sportgemeinde 1848 Ober-Ingelheim', 'Ingelheim', 0, ''),
(3, 'Turn- und Sportverein 1863 Wöllstein e.V.', 'Wöllstein', 0, 'abc'),
(4, 'Turnverein Eintracht 1880 Gau-Algesheim', 'Gau-Algesheim', 0, '');

CREATE TABLE `Wertungen` (
  `WertungID` int(11) NOT NULL,
  `TurnerID` int(11) NOT NULL,
  `GeraetID` int(11) NOT NULL,
  `P-Stufe` double DEFAULT NULL,
  `D-Note` double NOT NULL DEFAULT 0,
  `E1-Note` double DEFAULT NULL,
  `E2-Note` double DEFAULT NULL,
  `E3-Note` double DEFAULT NULL,
  `E4-Note` double DEFAULT NULL,
  `nA-Abzug` double NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

INSERT INTO `Wertungen` (`WertungID`, `TurnerID`, `GeraetID`, `P-Stufe`, `D-Note`, `E1-Note`, `E2-Note`, `E3-Note`, `E4-Note`, `nA-Abzug`) VALUES
(1, 1, 4, NULL, 0, NULL, NULL, 2.5, 4.5, 0),
(2, 1, 5, NULL, 10, NULL, NULL, NULL, NULL, 0),
(4, 6, 7, NULL, 0, NULL, NULL, NULL, NULL, 0);

CREATE TABLE `Wettkaempfe` (
  `WettkampfID` int(11) NOT NULL,
  `Beschreibung` varchar(250) NOT NULL DEFAULT 'Neuer Wettkampf',
  `WettkampfmodusID` int(11) NOT NULL DEFAULT 1,
  `WettkampfSprungmodusID` int(11) NOT NULL DEFAULT 1,
  `GeschlechtID` int(11) NOT NULL DEFAULT 1 COMMENT '1=gemischt, 2=männlich, 3=weiblich'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

INSERT INTO `Wettkaempfe` (`WettkampfID`, `Beschreibung`, `WettkampfmodusID`, `WettkampfSprungmodusID`, `GeschlechtID`) VALUES
(1, 'Neuer Wettkampf', 1, 1, 1),
(2, 'Neuer Wettkampf 2', 1, 1, 1),
(4, 'Test', 1, 2, 2);

CREATE TABLE `Wettkaempfe_Modi` (
  `WettkampfmodusID` int(11) NOT NULL,
  `Beschreibung` varchar(250) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

INSERT INTO `Wettkaempfe_Modi` (`WettkampfmodusID`, `Beschreibung`) VALUES
(1, 'Alle 4 Geräte'),
(2, '3 aus 4');

CREATE TABLE `Wettkaempfe_Modi_Sprung` (
  `WettkampfSprungmodusID` int(11) NOT NULL,
  `Beschreibung` varchar(250) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

INSERT INTO `Wettkaempfe_Modi_Sprung` (`WettkampfSprungmodusID`, `Beschreibung`) VALUES
(1, 'Mittelwert aus 2 Sprüngen'),
(2, 'Bester aus 2'),
(3, '1 Sprung');


ALTER TABLE `Durchgaenge`
  ADD PRIMARY KEY (`DurchgangID`);

ALTER TABLE `Geraete`
  ADD PRIMARY KEY (`GeraetID`);

ALTER TABLE `GeraeteTypen`
  ADD PRIMARY KEY (`GeraeteTypID`);

ALTER TABLE `Geschlechter`
  ADD PRIMARY KEY (`GeschlechtID`);

ALTER TABLE `Riegen`
  ADD PRIMARY KEY (`RiegenID`);

ALTER TABLE `Turner`
  ADD PRIMARY KEY (`TurnerID`);

ALTER TABLE `Verbindung_Durchgaenge_Riegen_Geraete`
  ADD PRIMARY KEY (`VDurchgaengeRiegenID`);

ALTER TABLE `Vereine`
  ADD PRIMARY KEY (`VereinID`);

ALTER TABLE `Wertungen`
  ADD PRIMARY KEY (`WertungID`);

ALTER TABLE `Wettkaempfe`
  ADD PRIMARY KEY (`WettkampfID`);

ALTER TABLE `Wettkaempfe_Modi`
  ADD PRIMARY KEY (`WettkampfmodusID`);

ALTER TABLE `Wettkaempfe_Modi_Sprung`
  ADD PRIMARY KEY (`WettkampfSprungmodusID`);


ALTER TABLE `Durchgaenge`
  MODIFY `DurchgangID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

ALTER TABLE `Geraete`
  MODIFY `GeraetID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

ALTER TABLE `GeraeteTypen`
  MODIFY `GeraeteTypID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

ALTER TABLE `Geschlechter`
  MODIFY `GeschlechtID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

ALTER TABLE `Riegen`
  MODIFY `RiegenID` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `Turner`
  MODIFY `TurnerID` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `Verbindung_Durchgaenge_Riegen_Geraete`
  MODIFY `VDurchgaengeRiegenID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

ALTER TABLE `Vereine`
  MODIFY `VereinID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

ALTER TABLE `Wertungen`
  MODIFY `WertungID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

ALTER TABLE `Wettkaempfe`
  MODIFY `WettkampfID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

ALTER TABLE `Wettkaempfe_Modi`
  MODIFY `WettkampfmodusID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE `Wettkaempfe_Modi_Sprung`
  MODIFY `WettkampfSprungmodusID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
