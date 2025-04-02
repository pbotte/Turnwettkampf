SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

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

CREATE TABLE `Mannschaften` (
  `MannschaftsID` int(11) NOT NULL,
  `Beschreibung` varchar(250) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE `Protokoll` (
  `ProtokollID` int(11) NOT NULL,
  `IP-Adresse` varchar(15) NOT NULL,
  `Aktion` varchar(250) NOT NULL,
  `Zeitpunkt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

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
  `MannschaftsID` int(11) DEFAULT NULL COMMENT 'Nur Bei Mannschaftswettkämpfen gebraucht, sonst NULL',
  `Wertungssumme` double DEFAULT NULL COMMENT 'Wird durch php-Seite berechnet und eingetragen.',
  `Platzierung` int(11) DEFAULT NULL COMMENT 'Wird durch php-Seite berechnet und eingetragen.'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE `Verbindung_Durchgaenge_Riegen_Geraete` (
  `VDurchgaengeRiegenID` int(11) NOT NULL,
  `RiegenID` int(11) NOT NULL,
  `DurchgangID` int(11) NOT NULL,
  `GeraetID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

INSERT INTO `Verbindung_Durchgaenge_Riegen_Geraete` (`VDurchgaengeRiegenID`, `RiegenID`, `DurchgangID`, `GeraetID`) VALUES
(74, 1, 1, 1),
(75, 1, 2, 3),
(76, 1, 3, 4),
(77, 1, 4, 5),
(78, 1, 5, 7),
(79, 1, 6, 9),
(80, 2, 1, 3),
(81, 2, 2, 4),
(82, 2, 3, 5),
(83, 2, 4, 7),
(84, 2, 5, 9),
(85, 2, 6, 1),
(86, 3, 1, 4),
(87, 3, 2, 5),
(88, 3, 3, 7),
(89, 3, 4, 9),
(90, 3, 5, 1),
(91, 3, 6, 3),
(92, 5, 1, 5),
(93, 5, 2, 7),
(94, 5, 3, 9),
(95, 5, 4, 1),
(96, 5, 5, 3),
(97, 5, 6, 4),
(98, 6, 1, 7),
(99, 6, 2, 9),
(100, 6, 3, 1),
(101, 6, 4, 3),
(102, 6, 5, 4),
(103, 6, 6, 5),
(104, 7, 1, 9),
(105, 7, 2, 1),
(106, 7, 3, 3),
(107, 7, 4, 4),
(108, 7, 5, 5),
(109, 7, 6, 7);

CREATE TABLE `Vereine` (
  `VereinID` int(11) NOT NULL,
  `Vereinsname` varchar(250) NOT NULL,
  `Stadt` varchar(250) NOT NULL,
  `Meldung_offen` tinyint(1) NOT NULL DEFAULT 0,
  `Geheimnis_fuer_Meldung` varchar(300) NOT NULL COMMENT 'Austauschbar falls jemand unberechtigtes es erhalten hatte'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

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
  `nA-Abzug` double NOT NULL DEFAULT 0,
  `Ausfuehrung` float DEFAULT NULL,
  `Gesamtwertung` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
DELIMITER $$
CREATE TRIGGER `trg_wertungen_before_insert` BEFORE INSERT ON `Wertungen` FOR EACH ROW BEGIN
    -- Deklaration von Hilfsvariablen
    DECLARE anzahl INT DEFAULT 0;
    DECLARE v1 DOUBLE;
    DECLARE v2 DOUBLE;
    DECLARE v3 DOUBLE;
    DECLARE diff1 DOUBLE;
    DECLARE diff2 DOUBLE;
    DECLARE diff3 DOUBLE;

    -- Überprüfung und Zählung der nicht NULL E-Noten
    SET anzahl = 0;
    IF NEW.`E1-Note` IS NOT NULL THEN 
        SET anzahl = anzahl + 1;
        SET v1 = NEW.`E1-Note`;
    END IF;
    IF NEW.`E2-Note` IS NOT NULL THEN 
        IF anzahl = 0 THEN
            SET v1 = NEW.`E2-Note`;
        ELSEIF anzahl = 1 THEN
            SET v2 = NEW.`E2-Note`;
        ELSE
            SET v3 = NEW.`E2-Note`;
        END IF;
        SET anzahl = anzahl + 1;
    END IF;
    IF NEW.`E3-Note` IS NOT NULL THEN 
        IF anzahl = 0 THEN
            SET v1 = NEW.`E3-Note`;
        ELSEIF anzahl = 1 THEN
            SET v2 = NEW.`E3-Note`;
        ELSEIF anzahl = 2 THEN
            SET v3 = NEW.`E3-Note`;
        END IF;
        SET anzahl = anzahl + 1;
    END IF;
    IF NEW.`E4-Note` IS NOT NULL THEN 
        IF anzahl = 0 THEN
            SET v1 = NEW.`E4-Note`;
        ELSEIF anzahl = 1 THEN
            SET v2 = NEW.`E4-Note`;
        ELSEIF anzahl = 2 THEN
            SET v3 = NEW.`E4-Note`;
        END IF;
        SET anzahl = anzahl + 1;
    END IF;

    -- Berechnung der Ausfuehrung anhand der Anzahl nicht NULL-Wertungen
    IF anzahl = 0 THEN
        SET NEW.Ausfuehrung = 0;
    ELSEIF anzahl = 1 THEN
        SET NEW.Ausfuehrung = v1;
    ELSEIF anzahl = 2 THEN
        SET NEW.Ausfuehrung = (v1 + v2)/2;
    ELSEIF anzahl = 3 THEN
        -- Berechne die absoluten Differenzen der drei möglichen Paare
        SET diff1 = ABS(v1 - v2);
        SET diff2 = ABS(v1 - v3);
        SET diff3 = ABS(v2 - v3);
        IF diff1 <= diff2 AND diff1 <= diff3 THEN
            SET NEW.Ausfuehrung = (v1 + v2)/2;
        ELSEIF diff2 <= diff1 AND diff2 <= diff3 THEN
            SET NEW.Ausfuehrung = (v1 + v3)/2;
        ELSE
            SET NEW.Ausfuehrung = (v2 + v3)/2;
        END IF;
    ELSEIF anzahl = 4 THEN
        -- Bei vier Wertungen: Berechne den Durchschnitt der beiden mittleren Werte
        SET NEW.Ausfuehrung = (
            NEW.`E1-Note` + NEW.`E2-Note` + NEW.`E3-Note` + NEW.`E4-Note`
            - LEAST(NEW.`E1-Note`, NEW.`E2-Note`, NEW.`E3-Note`, NEW.`E4-Note`)
            - GREATEST(NEW.`E1-Note`, NEW.`E2-Note`, NEW.`E3-Note`, NEW.`E4-Note`)
        ) / 2;
    END IF;

    -- Berechnung der Gesamtwertung:
    -- Gesamtwertung = (D-Note oder, falls NULL, P-Stufe) + Ausfuehrung - nA-Abzug
    SET NEW.Gesamtwertung = IF(NEW.`D-Note` IS NOT NULL, NEW.`D-Note`, NEW.`P-Stufe`) 
                            + NEW.Ausfuehrung - NEW.`nA-Abzug`;

    -- Optional: Zur Protokollierung könnte man hier z.B. in eine Log-Tabelle das JSON speichern:
    -- INSERT INTO TriggerLog(json_output) VALUES (JSON_OBJECT('Ausfuehrung', NEW.Ausfuehrung, 'Gesamtwertung', NEW.Gesamtwertung));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_wertungen_before_update` BEFORE UPDATE ON `Wertungen` FOR EACH ROW BEGIN
    DECLARE anzahl INT DEFAULT 0;
    DECLARE v1 DOUBLE;
    DECLARE v2 DOUBLE;
    DECLARE v3 DOUBLE;
    DECLARE diff1 DOUBLE;
    DECLARE diff2 DOUBLE;
    DECLARE diff3 DOUBLE;

    SET anzahl = 0;
    IF NEW.`E1-Note` IS NOT NULL THEN 
        SET anzahl = anzahl + 1;
        SET v1 = NEW.`E1-Note`;
    END IF;
    IF NEW.`E2-Note` IS NOT NULL THEN 
        IF anzahl = 0 THEN
            SET v1 = NEW.`E2-Note`;
        ELSEIF anzahl = 1 THEN
            SET v2 = NEW.`E2-Note`;
        ELSE
            SET v3 = NEW.`E2-Note`;
        END IF;
        SET anzahl = anzahl + 1;
    END IF;
    IF NEW.`E3-Note` IS NOT NULL THEN 
        IF anzahl = 0 THEN
            SET v1 = NEW.`E3-Note`;
        ELSEIF anzahl = 1 THEN
            SET v2 = NEW.`E3-Note`;
        ELSEIF anzahl = 2 THEN
            SET v3 = NEW.`E3-Note`;
        END IF;
        SET anzahl = anzahl + 1;
    END IF;
    IF NEW.`E4-Note` IS NOT NULL THEN 
        IF anzahl = 0 THEN
            SET v1 = NEW.`E4-Note`;
        ELSEIF anzahl = 1 THEN
            SET v2 = NEW.`E4-Note`;
        ELSEIF anzahl = 2 THEN
            SET v3 = NEW.`E4-Note`;
        END IF;
        SET anzahl = anzahl + 1;
    END IF;

    IF anzahl = 0 THEN
        SET NEW.Ausfuehrung = 0;
    ELSEIF anzahl = 1 THEN
        SET NEW.Ausfuehrung = v1;
    ELSEIF anzahl = 2 THEN
        SET NEW.Ausfuehrung = (v1 + v2)/2;
    ELSEIF anzahl = 3 THEN
        SET diff1 = ABS(v1 - v2);
        SET diff2 = ABS(v1 - v3);
        SET diff3 = ABS(v2 - v3);
        IF diff1 <= diff2 AND diff1 <= diff3 THEN
            SET NEW.Ausfuehrung = (v1 + v2)/2;
        ELSEIF diff2 <= diff1 AND diff2 <= diff3 THEN
            SET NEW.Ausfuehrung = (v1 + v3)/2;
        ELSE
            SET NEW.Ausfuehrung = (v2 + v3)/2;
        END IF;
    ELSEIF anzahl = 4 THEN
        SET NEW.Ausfuehrung = (
            NEW.`E1-Note` + NEW.`E2-Note` + NEW.`E3-Note` + NEW.`E4-Note`
            - LEAST(NEW.`E1-Note`, NEW.`E2-Note`, NEW.`E3-Note`, NEW.`E4-Note`)
            - GREATEST(NEW.`E1-Note`, NEW.`E2-Note`, NEW.`E3-Note`, NEW.`E4-Note`)
        ) / 2;
    END IF;

    SET NEW.Gesamtwertung = IF(NEW.`D-Note` IS NOT NULL, NEW.`D-Note`, NEW.`P-Stufe`) 
                            + NEW.Ausfuehrung - NEW.`nA-Abzug`;

    -- Optional: JSON-Protokollierung (z.B. in eine Log-Tabelle)
    -- INSERT INTO TriggerLog(json_output) VALUES (JSON_OBJECT('Ausfuehrung', NEW.Ausfuehrung, 'Gesamtwertung', NEW.Gesamtwertung));
END
$$
DELIMITER ;

CREATE TABLE `Wettkaempfe` (
  `WettkampfID` int(11) NOT NULL,
  `Beschreibung` varchar(250) NOT NULL DEFAULT 'Neuer Wettkampf',
  `WettkampfmodusID` int(11) NOT NULL DEFAULT 1,
  `WettkampfSprungmodusID` int(11) NOT NULL DEFAULT 1,
  `GeschlechtID` int(11) NOT NULL DEFAULT 1 COMMENT '1=gemischt, 2=männlich, 3=weiblich',
  `NWertungen` int(11) NOT NULL DEFAULT 4 COMMENT 'Anzahl Wertungen an eindeutigen Geräten, die berücksichtigt werden.',
  `NGeraeteMax` int(11) NOT NULL DEFAULT 4 COMMENT 'Anzahl der maximal turnbaren Geräte'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

CREATE TABLE `Wettkaempfe_Modi` (
  `WettkampfmodusID` int(11) NOT NULL,
  `Beschreibung` varchar(250) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

INSERT INTO `Wettkaempfe_Modi` (`WettkampfmodusID`, `Beschreibung`) VALUES
(1, 'Alle 4 Geräte'),
(2, '3 aus 4'),
(3, 'Alle 6 Geräte'),
(4, '4 aus 6');

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

ALTER TABLE `Mannschaften`
  ADD PRIMARY KEY (`MannschaftsID`);

ALTER TABLE `Protokoll`
  ADD PRIMARY KEY (`ProtokollID`);

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

ALTER TABLE `Mannschaften`
  MODIFY `MannschaftsID` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `Protokoll`
  MODIFY `ProtokollID` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `Riegen`
  MODIFY `RiegenID` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `Turner`
  MODIFY `TurnerID` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `Verbindung_Durchgaenge_Riegen_Geraete`
  MODIFY `VDurchgaengeRiegenID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=110;

ALTER TABLE `Vereine`
  MODIFY `VereinID` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `Wertungen`
  MODIFY `WertungID` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `Wettkaempfe`
  MODIFY `WettkampfID` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `Wettkaempfe_Modi`
  MODIFY `WettkampfmodusID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

ALTER TABLE `Wettkaempfe_Modi_Sprung`
  MODIFY `WettkampfSprungmodusID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;
