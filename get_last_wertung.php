<?php

error_reporting(E_ALL);
ini_set('display_errors', 'On');



/*

Schreibe eine php-Seite, welche das Ergebnis aus der SQL-Tabelle "Wertungen" zurückgibt, so dass es eine andere Webseite einfach wie einer REST-API abfragen kann. Die Rückgabe soll in json ausgegeben werden. 

Es wird immer der zuletzt eingegeben Eintrag (Wert mit der höchsten "WertungID") ausgegeben.

Zurückgegeben werden sollen alle Werte, zusammen mit dem in Turner nachgeschlagenen Werte für die TurnerID (Vorname, Nachname, Jahrgang (berechnet aus Geburtsdatum)), dem Gerätenamen (nachgeschlagen aus Geraete via GeraeteID) und (falls vorhanden) dem Wettkampf (via TurnerID zu WettkampfID, Wettkaempfe.Beschreibung, Wettkaempfe.GeschlechtID (falls vorhanden, in Geschlechter nachschlagen und die Kurzform ausgeben))

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

Wird eine GeraeteID via GET-Parameter angegeben, so sollen nur Werte aus der Tabelle "WertungID" betrachtet werden, welche diese GeraeteID haben.

Aktuell erhalte ich den Fehler: "Deprecated: htmlspecialchars(): Passing null to parameter #1 ($string) of type string is deprecated in ..." 
Um dies zu lösen, ersetze bei der Nutzung von htmlspecialchars durch eine eigene Funktion, welche bei einem übergebenen null-String einfach "-" ausgibt und sonst die Funktion "htmlspecialchars" aufruft.

Für die Anbingung an die Datenbank sollen folgende Variablen verwendet werden: $dbHost, $dbName, $dbUser, $dbPass
und als charset: "utf8".

*/

include 'config.php';
// Datenbankverbindungsparameter anpassen!

$charset = 'utf8';

try {
    $dsn = "mysql:host={$dbHost};dbname={$dbName};charset={$charset}";
    $pdo = new PDO($dsn, $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Datenbankverbindung fehlgeschlagen: ' . $e->getMessage()]);
    exit;
}

// Eigene Funktion als Ersatz für htmlspecialchars, die bei null "-" zurückgibt
function custom_htmlspecialchars($string) {
    if ($string === null) {
        return "-";
    } else {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
}

// Prüfen, ob eine GeraeteID via GET-Parameter übergeben wurde
$geraeteID = isset($_GET['GeraeteID']) ? intval($_GET['GeraeteID']) : null;

// SQL-Abfrage zusammenbauen:
// Es werden alle Felder aus der Tabelle "Wertungen" abgefragt und per JOIN zusätzlich
// - Vorname, Nachname und Geburtsdatum (zur Berechnung des Jahrgangs) aus "Turner"
// - den Gerätenamen aus "Geraete" (als GeraetBeschreibung)
// - (falls vorhanden) Wettkampfinformationen aus "Wettkaempfe" und
//   Geschlechter (Kurzform) aus "Geschlechter"
$sql = "SELECT 
            W.*, 
            T.Vorname, T.Nachname, T.Geburtsdatum, T.WettkampfID,
            G.Beschreibung AS GeraetBeschreibung,
            Wt.Beschreibung AS WettkampfBeschreibung,
            Wt.GeschlechtID AS WettkampfGeschlechtID,
            Geschl.Beschreibung_kurz AS GeschlechtKurz
        FROM Wertungen W
        INNER JOIN Turner T ON W.TurnerID = T.TurnerID
        INNER JOIN Geraete G ON W.GeraetID = G.GeraetID
        LEFT JOIN Wettkaempfe Wt ON T.WettkampfID = Wt.WettkampfID
        LEFT JOIN Geschlechter Geschl ON Wt.GeschlechtID = Geschl.GeschlechtID";

if ($geraeteID !== null) {
    $sql .= " WHERE W.GeraetID = :geraeteID";
}

$sql .= " ORDER BY W.WertungID DESC LIMIT 1";

$stmt = $pdo->prepare($sql);
if ($geraeteID !== null) {
    $stmt->bindValue(':geraeteID', $geraeteID, PDO::PARAM_INT);
}
$stmt->execute();

$result = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$result) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Kein Eintrag gefunden.']);
    exit;
}

// Berechnung der "Ausführung"
// Es werden die Werte E1-Note, E2-Note, E3-Note und E4-Note berücksichtigt
$notes = [];
if (!is_null($result['E1-Note'])) { $notes[] = floatval($result['E1-Note']); }
if (!is_null($result['E2-Note'])) { $notes[] = floatval($result['E2-Note']); }
if (!is_null($result['E3-Note'])) { $notes[] = floatval($result['E3-Note']); }
if (!is_null($result['E4-Note'])) { $notes[] = floatval($result['E4-Note']); }

$count = count($notes);
$ausfuehrung = 0;

if ($count === 0) {
    $ausfuehrung = 0;
} elseif ($count === 1) {
    $ausfuehrung = $notes[0];
} elseif ($count === 2) {
    $ausfuehrung = array_sum($notes) / 2;
} elseif ($count === 3) {
    // Bei drei Wertungen: Zwei auswählen, deren Differenz am geringsten ist
    $pairs = [];
    for ($i = 0; $i < 3; $i++) {
        for ($j = $i + 1; $j < 3; $j++) {
            $pairs[] = [
                'sum' => $notes[$i] + $notes[$j],
                'diff' => abs($notes[$i] - $notes[$j])
            ];
        }
    }
    usort($pairs, function($a, $b) {
        return $a['diff'] <=> $b['diff'];
    });
    $ausfuehrung = $pairs[0]['sum'] / 2;
} elseif ($count === 4) {
    // Bei vier Wertungen: Höchste und niedrigste werden verworfen, Mittelwert der beiden mittleren
    sort($notes);
    $ausfuehrung = ($notes[1] + $notes[2]) / 2;
}

// Berechnung der Gesamtwertung:
// Gesamtwertung = (D-Note, falls vorhanden, ansonsten P-Stufe) + Ausführung - nA-Abzug
$baseNote = (is_null($result['D-Note'])) ? floatval($result['P-Stufe']) : floatval($result['D-Note']);
$gesamtwertung = $baseNote + $ausfuehrung - floatval($result['nA-Abzug']);

// Berechnung des Jahrgangs aus dem Geburtsdatum (hier wird das Geburtsjahr verwendet)
$geburtsdatum = $result['Geburtsdatum'];
$jahrgang = date('Y', strtotime($geburtsdatum));

// Zusammenstellen des Ausgabe-Arrays
$output = $result; // Enthält alle ursprünglichen Felder
$output['Ausfuehrung'] = $ausfuehrung;
$output['Gesamtwertung'] = $gesamtwertung;
$output['Jahrgang'] = $jahrgang;

// Sicherheitsanpassungen für Textfelder mittels custom_htmlspecialchars
$output['Vorname'] = custom_htmlspecialchars($output['Vorname']);
$output['Nachname'] = custom_htmlspecialchars($output['Nachname']);
$output['GeraetBeschreibung'] = custom_htmlspecialchars($output['GeraetBeschreibung']);
$output['WettkampfBeschreibung'] = custom_htmlspecialchars($output['WettkampfBeschreibung']);
$output['GeschlechtKurz'] = custom_htmlspecialchars($output['GeschlechtKurz']);

// Header setzen und JSON ausgeben
header('Content-Type: application/json; charset=utf-8');
echo json_encode($output);
?>
