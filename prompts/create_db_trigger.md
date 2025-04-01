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


Im Folgenden ein Beispiel, wie Du zwei Trigger (einen für INSERT und einen für UPDATE) in MariaDB erstellen kannst, die vor dem Einfügen bzw. Aktualisieren den Wert für „Ausfuehrung“ und „Gesamtwertung“ anhand der vorhandenen Noten berechnen. (Da MariaDB‑Trigger keine direkte Ausgabe an den Client liefern können, wird hier das Ergebnis in den Spalten gespeichert – falls gewünscht, könnte man alternativ z. B. in eine separate Log‑Tabelle schreiben.)

> **Hinweis:**  
> In diesem Beispiel wird ein BEFORE‑Trigger verwendet, damit die Berechnung vor dem Einfügen bzw. Aktualisieren erfolgt. Für den Fall von drei nicht‑NULL–Wertungen wird anhand der absoluten Differenz der drei möglichen Paare diejenige gewählt, deren Differenz am kleinsten ist. Bei vier vorhandenen Noten wird der Mittelwert der beiden mittleren (nach Ausschluss von Minimum und Maximum) berechnet.

---

### Trigger für INSERT

```sql
DELIMITER $$

CREATE TRIGGER trg_wertungen_before_insert
BEFORE INSERT ON Wertungen
FOR EACH ROW
BEGIN
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
END$$

DELIMITER ;
```

---

### Trigger für UPDATE

```sql
DELIMITER $$

CREATE TRIGGER trg_wertungen_before_update
BEFORE UPDATE ON Wertungen
FOR EACH ROW
BEGIN
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
END$$

DELIMITER ;
```

---

### Zusammenfassung

- **Trigger-Ereignisse:**  
  Die beiden Trigger werden jeweils vor einem INSERT bzw. UPDATE auf der Tabelle `Wertungen` ausgeführt.

- **Berechnung der „Ausfuehrung“:**  
  - Es wird zunächst gezählt, wie viele der E‑Noten nicht NULL sind.  
  - Bei 0 nicht‑NULL-Wertungen: Ausgabe 0.  
  - Bei 1: Ausgabe der vorhandenen Note.  
  - Bei 2: Mittelwert der beiden Noten.  
  - Bei 3: Es wird das Paar mit der geringsten Differenz gemittelt (die dritte Note bleibt unberücksichtigt).  
  - Bei 4: Es wird der Mittelwert der beiden mittleren Werte (nach Entfernen von Minimum und Maximum) gebildet.

- **Berechnung der „Gesamtwertung“:**  
  Es wird der (vorzugsweise vorhandene) D‑Note (falls NULL, dann P‑Stufe) zuzüglich der berechneten Ausführung minus dem nA‑Abzug addiert.

Da Trigger in MariaDB nicht dazu gedacht sind, Ergebnisse direkt an den Client auszugeben, kannst Du – falls gewünscht – die berechneten Werte über eine SELECT‑Abfrage in ein JSON-Objekt verpacken, z. B. so:

```sql
SELECT JSON_OBJECT('Ausfuehrung', Ausfuehrung, 'Gesamtwertung', Gesamtwertung) AS json_output
FROM Wertungen
WHERE WertungID = <die betroffene ID>;
```

Dieses Beispiel zeigt, wie Du die Anforderung umsetzen kannst. Passe es bei Bedarf an Deine konkreten Anforderungen oder Umgebung an.
