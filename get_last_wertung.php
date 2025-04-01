<?php

error_reporting(E_ALL);
ini_set('display_errors', 'On');



/*


Schreibe eine php-Seite, welche das Ergebnis aus der SQL-Tabelle "Wertungen" zurückgibt, so dass es eine andere Webseite einfach wie einer REST-API abfragen kann. Die Rückgabe soll in json ausgegeben werden. 

Es wird immer der zuletzt eingegeben Eintrag (Wert mit der höchsten "WertungID") ausgegeben.

Zurückgegeben werden sollen alle Werte, zusammen mit dem in Turner nachgeschlagenen Werte für die TurnerID (Vorname, Nachname, Jahrgang (berechnet aus Geburtsdatum)), dem Gerätenamen als "GeraetBeschreibung" (nachgeschlagen aus Geraete via GeraeteID) und (falls vorhanden) dem Wettkampf (via TurnerID zu WettkampfID, Wettkaempfe.Beschreibung, Wettkaempfe.GeschlechtID (falls vorhanden, in Geschlechter nachschlagen und die Kurzform ausgeben)), "Ausfuehrung", "Gesamtwertung"

Wird eine GeraeteID via GET-Parameter angegeben, so sollen nur Werte aus der Tabelle "WertungID" betrachtet werden, welche diese GeraeteID haben.

Aktuell erhalte ich den Fehler: "Deprecated: htmlspecialchars(): Passing null to parameter #1 ($string) of type string is deprecated in ..." 
Um dies zu lösen, ersetze bei der Nutzung von htmlspecialchars durch eine eigene Funktion, welche bei einem übergebenen null-String einfach "-" ausgibt und sonst die Funktion "htmlspecialchars" aufruft.

Für die Anbingung an die Datenbank sollen folgende Variablen verwendet werden: $dbHost, $dbName, $dbUser, $dbPass
und als charset: "utf8".

*/

include 'config.php';
// Datenbankverbindungsparameter anpassen!



header('Content-Type: application/json; charset=utf-8');

// Eigene Funktion als Ersatz für htmlspecialchars, die bei null "-" zurückgibt.
function safe_html($string) {
    if (is_null($string)) {
        return "-";
    }
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}



try {
    // PDO-Verbindung zur Datenbank mit Charset "utf8"
    $dsn = "mysql:host=$dbHost;dbname=$dbName;charset=utf8";
    $pdo = new PDO($dsn, $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Basis-SQL-Statement mit den notwendigen Joins:
    // - Turner: Für Vorname, Nachname und Jahrgang (aus Geburtsdatum, hier als Geburtsjahr)
    // - Geraete: Für die GeraetBeschreibung
    // - Wettkaempfe: Für Wettkampf Beschreibung und GeschlechtID
    // - Geschlechter: Für die Kurzform des Wettkampfgeschlechts (falls vorhanden)
    $sql = "SELECT 
                w.*,
                t.Vorname,
                t.Nachname,
                YEAR(t.Geburtsdatum) AS Jahrgang,
                g.Beschreibung AS GeraetBeschreibung,
                wk.Beschreibung AS WettkampfBeschreibung,
                gch.Beschreibung_kurz AS WettkampfGeschlechtKurz
            FROM Wertungen w
            LEFT JOIN Turner t ON w.TurnerID = t.TurnerID
            LEFT JOIN Geraete g ON w.GeraetID = g.GeraetID
            LEFT JOIN Wettkaempfe wk ON t.WettkampfID = wk.WettkampfID
            LEFT JOIN Geschlechter gch ON wk.GeschlechtID = gch.GeschlechtID";

    $params = [];
    // Falls über GET eine GeraeteID übergeben wurde, nur die entsprechenden Einträge betrachten.
    if (isset($_GET['GeraeteID']) && $_GET['GeraeteID'] !== '') {
        $sql .= " WHERE w.GeraetID = :geraeteid";
        $params[':geraeteid'] = $_GET['GeraeteID'];
    }

    // Der zuletzt eingegebene Eintrag (höchste WertungID)
    $sql .= " ORDER BY w.WertungID DESC LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        echo json_encode(['error' => 'Keine Daten gefunden']);
        exit;
    }

    // Anwendung der safe_html Funktion auf alle stringbasierte Felder
    $result['Vorname'] = safe_html($result['Vorname']);
    $result['Nachname'] = safe_html($result['Nachname']);
    $result['GeraetBeschreibung'] = safe_html($result['GeraetBeschreibung']);
    $result['WettkampfBeschreibung'] = safe_html($result['WettkampfBeschreibung']);
    $result['WettkampfGeschlechtKurz'] = safe_html($result['WettkampfGeschlechtKurz']);

    // JSON-Ausgabe
    echo json_encode($result);

} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}
?>
