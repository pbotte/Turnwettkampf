# Erzeugen der Trigger für Wertungen

```
Schreibe einen Trigger für eine MariaDB Datenbank, deren Schema unten angehängt ist.

Immer wenn ein neuer Eintrag hinzugefügt oder bearbeitet wird, soll dieser Trigger aktiv werden.


Zusätzlich sollen folgende Werte im JSON ausgegeben werden:
- "Ausführung" (in der JSON später mit Feldname "Ausfuehrung") 
- "Gesamtwertung"

Die Ausführung wird wie folgt berechnet:
Betrachet werden die Werte E1-Note, E2-Note, E3-Note und E4-Note, welche im folgenden auch als Wertungen bezeichnet werden. Die Variable Anzahl_Wertungen gibt an, wie viele von diesen NICHT NULL sind. 
Ist Anzahl_Wertungen==0, dann ist die Ausführung=0.
Ist Anzahl_Wertungen==1, dann ist die Ausführung gleich der Wertung, welche nicht NULL ist.
Ist Anzahl_Wertungen==2, dann ist die Ausführung der Mittelwert der beiden Wertungen.
Ist Anzahl_Wertungen==3, dann ist die Ausführung der Mitelwert der beiden Wertungen, deren Differenz geringer ist. Die dritte Wertung wird dann nicht weiter berücksichtigt.
Ist Anzahl_Wertungen==4, dann ist die Ausführung der Mittelwert der beiden Wertungen, die am Median liegen. Die höchste und die niedrigste Wertung wird dann nicht weiter berücksichtigt.

Die Gesamtwertung wird wie folgt berechnet:
Dies ist der Wert aus "D-Note" (falls "D-Note"==NULL, dann "P-Stufe") plus der Ausführung abzüglich des Werts "nA-Abzug". 



SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

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

INSERT INTO `Wertungen` (`WertungID`, `TurnerID`, `GeraetID`, `P-Stufe`, `D-Note`, `E1-Note`, `E2-Note`, `E3-Note`, `E4-Note`, `nA-Abzug`, `Ausfuehrung`, `Gesamtwertung`) VALUES
(1, 1, 4, NULL, 10, 1, NULL, 2.5, 4.5, 0, NULL, NULL),
(2, 1, 5, NULL, 10, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(4, 6, 7, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(6, 5, 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(7, 3, 4, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(8, 1, 3, 7.5, 7.5, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(9, 13, 6, 5.5, 10, 1, NULL, 0.8, NULL, 0, NULL, NULL),
(10, 14, 10, NULL, 5, 3, 3, NULL, NULL, 0, NULL, NULL),
(11, 2, 10, NULL, 5.5, 5, NULL, NULL, NULL, 0, NULL, NULL);


ALTER TABLE `Wertungen`
  ADD PRIMARY KEY (`WertungID`);


ALTER TABLE `Wertungen`
  MODIFY `WertungID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;
COMMIT;
```
