<?php

error_reporting(E_ALL);
ini_set('display_errors', 'On');

/*
Programmiere eine php-Seite für Mobil-Geräte optimiert und in einem modernen Design.
Sie soll auf eine SQL-Datenbank zugreifen, deren Struktur angehängt ist.

Der Seitentitel lautet: "Wettkampf-Detailergebnisse".

Biete unterschiedliche Gruppierungen an:
- Nach Wettkämpfen
- Nach Riegen
- Nach Vereinszugehörigkeit
Die Gruppierung soll durch ein Dropdownmenü auswählbar sein.

Biete innerhalb der Gruppierung unterschiedliche Sortierungen an:
- nach Nachname, Vorname
- nach Platzierung

Iteriere durch alle Einträge in der Tabelle Turner, sortiere nach Nachname, Vorname und gruppiere zuvor nach dem was im Dropdownmenü ausgewählt ist. 
Falls nach Wettkämpfen gruppiert: Gebe Details zum jeweiligen Wettkampf aus, also dessen Beschreibung, Geschlecht (nachschlagen über GeschlechtID), Nwertungen und NGeraeteMax

Gebe anschließend in einer Tabelle aus:
- Tabellenspalten: 
  - aus Tabelle Turner: Platzierung, Nachname, Vorname, Jahrgang, Geschlecht (in Kurzform, über GeschlechtID nachschlagen), Verein (über VereinID nachschlagen), Wertungssumme
  - aus GerateTypen: Alle dort aufgelisteten Gerate (nutze deren Beschreibung) gruppiert und Sortiert nach der Reihenfolge. Haben mehrere Einträge die gleiche Reihenfolge, so bilden die Einträge (mit "," getrennt) einen Spaltennamen der Tabelle.
- Pro Zeile, fülle die Spalten mit:
  - Entsprechenden Daten aus Tabelle Turner
  - Einträge aus Tabelle Wertungen: Schlage in der Tabelle Wertungen nach den zum Turner (via TurnerID) zugehörigen Einzel-Wertungen nach. Fülle in jeder Spalte entsprechend den Informationen aus der Tabelle GeraetTypen und Geraete. Trage die jeweilige Gesamtwertung der Wertung ein.

Bootstrap und PDO sollen verwendet werden.

Um den Fehler: "Deprecated: htmlspecialchars(): Passing null to parameter #1 ($string) of type string is deprecated in ..." zu umgehen, nutze die  Funktion htmlspecialchars nicht direkt, sondern nutze eine eigene Funktion, welche bei einem übergebenen null-String einfach "-" ausgibt und andernfalls die Funktion "htmlspecialchars" aufruft.

Für die Anbingung an die Datenbank sollen folgende Variablen verwendet werden: $dbHost, $dbName, $dbUser, $dbPass
und als charset: "utf8".

In der PHP-Datei soll:
- diese Zeile in der Zeile nach "<body>" eingefügt werden: 
  <script src="menu.js"></script>
- diese Zeile in der <head> Section:
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
- Und diese Zeile bevor dem </body> tag:
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
*/

include 'config.php';

try {
    $dsn = "mysql:host=$dbHost;dbname=$dbName;charset=utf8";
    $pdo = new PDO($dsn, $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Datenbankverbindung fehlgeschlagen: " . $e->getMessage());
}

// Eigene Funktion als Wrapper für htmlspecialchars,
// gibt bei null den String "-" zurück.
function safeHtml($string) {
    if ($string === null) {
        return "-";
    }
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Lese GET-Parameter für Gruppierung und Sortierung
$grouping = isset($_GET['grouping']) ? $_GET['grouping'] : 'wettkaempfe';
$sorting  = isset($_GET['sorting'])  ? $_GET['sorting']  : 'nachname';
$allowedGroupings = ['wettkaempfe', 'riegen', 'vereine'];
if (!in_array($grouping, $allowedGroupings)) {
    $grouping = 'wettkaempfe';
}
$allowedSortings = ['nachname', 'platzierung'];
if (!in_array($sorting, $allowedSortings)) {
    $sorting = 'nachname';
}

// Sortierreihenfolge festlegen
$orderClause = "";
if ($sorting == 'nachname') {
    $orderClause = "ORDER BY t.Nachname, t.Vorname";
} elseif ($sorting == 'platzierung') {
    $orderClause = "ORDER BY t.Platzierung";
}

// Dynamische Spalten: Hole alle Einträge aus GeraeteTypen und gruppiere nach Reihenfolge
$stmt = $pdo->query("SELECT * FROM GeraeteTypen ORDER BY Reihenfolge ASC");
$geraeteTypen = $stmt->fetchAll(PDO::FETCH_ASSOC);
$deviceGroups = [];      // Gruppiert nach Reihenfolge (jede Gruppe wird später eine Spalte)
$geraeteTypID_to_group = []; // Mapping: GeraeteTypID => Gruppen-Key (Reihenfolge)

foreach ($geraeteTypen as $gt) {
    $groupKey = $gt['Reihenfolge'];
    $geraeteTypID_to_group[$gt['GeraeteTypID']] = $groupKey;
    if (!isset($deviceGroups[$groupKey])) {
        $deviceGroups[$groupKey] = [];
    }
    $deviceGroups[$groupKey][] = $gt['Beschreibung'];
}

// Erstelle die Spaltenüberschriften (bei mehreren Einträgen werden diese per Komma getrennt)
$dynamicColumnHeaders = [];
foreach ($deviceGroups as $key => $descriptions) {
    $dynamicColumnHeaders[$key] = implode(", ", $descriptions);
}

// Hole alle Wertungen und bestimme für jeden Turner (über TurnerID) die Gesamtwertung(en) pro Geräte-Gruppe
$stmt = $pdo->query("
    SELECT w.TurnerID, w.Gesamtwertung, g.GeraeteTypID 
    FROM Wertungen w 
    JOIN Geraete g ON w.GeraetID = g.GeraetID
");
$wertungenRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$wertungenByTurner = []; // Array: TurnerID => [ Gruppen-Key => [Gesamtwertung, ...] ]
foreach ($wertungenRows as $w) {
    $turnerID = $w['TurnerID'];
    $gtID = $w['GeraeteTypID'];
    if (!isset($geraeteTypID_to_group[$gtID])) {
        continue;
    }
    $groupKey = $geraeteTypID_to_group[$gtID];
    if (!isset($wertungenByTurner[$turnerID])) {
        $wertungenByTurner[$turnerID] = [];
    }
    if (!isset($wertungenByTurner[$turnerID][$groupKey])) {
        $wertungenByTurner[$turnerID][$groupKey] = [];
    }
    $wertungenByTurner[$turnerID][$groupKey][] = $w['Gesamtwertung'];
}

// Hole alle Geschlechter (für Kurzform)
$stmt = $pdo->query("SELECT * FROM Geschlechter");
$geschlechter = $stmt->fetchAll(PDO::FETCH_ASSOC);
$geschlechterLookup = [];
foreach ($geschlechter as $g) {
    $geschlechterLookup[$g['GeschlechtID']] = $g;
}

// Hole alle Turner inklusive Informationen aus Geschlechter und Vereine und Wettkämpfe
$query = "
    SELECT t.*, g.Beschreibung_kurz, v.Vereinsname, wk.Beschreibung AS WettkampfBeschreibung, wk.GeschlechtID AS WettkampfGeschlechtID, wk.NWertungen, wk.NGeraeteMax
    FROM Turner t
    LEFT JOIN Geschlechter g ON t.GeschlechtID = g.GeschlechtID
    LEFT JOIN Vereine v ON t.VereinID = v.VereinID
    LEFT JOIN Wettkaempfe wk ON t.WettkampfID = wk.WettkampfID
    $orderClause
";
$stmt = $pdo->query($query);
$turnerRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Gruppiere die Turner entsprechend der Auswahl im Dropdown
$groupedTurner = [];
foreach ($turnerRows as $t) {
    if ($grouping == 'wettkaempfe') {
        $groupKey = $t['WettkampfID'];
    } elseif ($grouping == 'riegen') {
        $groupKey = $t['RiegenID'];
    } elseif ($grouping == 'vereine') {
        $groupKey = $t['VereinID'];
    } else {
        $groupKey = 'default';
    }
    $groupedTurner[$groupKey][] = $t;
}

// Für die Gruppierung nach Wettkämpfe: Hole alle Wettkampf-Details
$wettkaempfeData = [];
if ($grouping == 'wettkaempfe') {
    $stmt = $pdo->query("SELECT * FROM Wettkaempfe ORDER BY Beschreibung");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $w) {
        $wettkaempfeData[$w['WettkampfID']] = $w;
    }
}

// Für die Gruppierung nach Riegen: Hole die Riegen-Daten
$riegenData = [];
if ($grouping == 'riegen') {
    $stmt = $pdo->query("SELECT * FROM Riegen ORDER BY Beschreibung");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $riegenData[$r['RiegenID']] = $r;
    }
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Wettkampf-Detailergebnisse</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <!-- Einbinden des Menü-JavaScripts -->
    <script src="menu.js"></script>

    <div class="container">
        <h1 class="mt-4">Wettkampf-Detailergebnisse</h1>

        <!-- Dropdown-Menüs für Gruppierung und Sortierung -->
        <form method="get" class="mb-3">
            <div class="row">
                <div class="col">
                    <label for="grouping" class="form-label">Gruppierung:</label>
                    <select id="grouping" name="grouping" class="form-select" onchange="this.form.submit()">
                        <option value="wettkaempfe" <?php if($grouping=='wettkaempfe') echo 'selected'; ?>>Nach Wettkämpfen</option>
                        <option value="riegen"      <?php if($grouping=='riegen')      echo 'selected'; ?>>Nach Riegen</option>
                        <option value="vereine"     <?php if($grouping=='vereine')     echo 'selected'; ?>>Nach Vereinszugehörigkeit</option>
                    </select>
                </div>
                <div class="col">
                    <label for="sorting" class="form-label">Sortierung:</label>
                    <select id="sorting" name="sorting" class="form-select" onchange="this.form.submit()">
                        <option value="nachname"    <?php if($sorting=='nachname')    echo 'selected'; ?>>Nach Nachname, Vorname</option>
                        <option value="platzierung" <?php if($sorting=='platzierung') echo 'selected'; ?>>Nach Platzierung</option>
                    </select>
                </div>
            </div>
        </form>

        <?php
        // Sortiere die Gruppen-Keys entsprechend der Beschriftung
        $groupKeys = array_keys($groupedTurner);
        usort($groupKeys, function($a, $b) use ($grouping, $wettkaempfeData, $riegenData, $groupedTurner) {
            switch ($grouping) {
                case 'wettkaempfe':
                    $labelA = $wettkaempfeData[$a]['Beschreibung'] ?? '';
                    $labelB = $wettkaempfeData[$b]['Beschreibung'] ?? '';
                    return strcmp($labelA, $labelB);
                case 'riegen':
                    $labelA = $riegenData[$a]['Beschreibung'] ?? '';
                    $labelB = $riegenData[$b]['Beschreibung'] ?? '';
                    return strcmp($labelA, $labelB);
                case 'vereine':
                    $labelA = $groupedTurner[$a][0]['Vereinsname'] ?? '';
                    $labelB = $groupedTurner[$b][0]['Vereinsname'] ?? '';
                    return strcmp($labelA, $labelB);
                default:
                    return 0;
            }
        });

        // Ausgabe pro Gruppe
        foreach ($groupKeys as $groupKey):
            $turnerList = $groupedTurner[$groupKey];
        ?>
            <div class="mb-5">
                <?php
                // Gruppenkopf
                if ($grouping == 'wettkaempfe'):
                    if (isset($wettkaempfeData[$groupKey])):
                        $w = $wettkaempfeData[$groupKey];
                        $gk = $geschlechterLookup[$w['GeschlechtID']]['Beschreibung_kurz'] ?? '-';
                        echo "<h2>Wettkampf: " . safeHtml($w['Beschreibung']) .
                             " (Geschlecht: " . safeHtml($gk) .
                             ", NWertungen: " . safeHtml($w['NWertungen']) .
                             ", NGeraeteMax: " . safeHtml($w['NGeraeteMax']) . ")</h2>";
                    else:
                        echo "<h2>Wettkampf: -</h2>";
                    endif;
                elseif ($grouping == 'riegen'):
                    if (isset($riegenData[$groupKey])):
                        echo "<h2>Riege: " . safeHtml($riegenData[$groupKey]['Beschreibung']) . "</h2>";
                    else:
                        echo "<h2>Riege: -</h2>";
                    endif;
                elseif ($grouping == 'vereine'):
                    $vn = safeHtml($turnerList[0]['Vereinsname'] ?? '-');
                    echo "<h2>Verein: {$vn}</h2>";
                endif;
                ?>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th>Platzierung</th>
                                <th>Nachname</th>
                                <th>Vorname</th>
                                <th>Jahrgang</th>
                                <th>Geschlecht</th>
                                <th>Verein</th>
                                <th>Wertungssumme</th>
                                <?php foreach ($dynamicColumnHeaders as $header): ?>
                                    <th><?php echo safeHtml($header); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($turnerList as $t): ?>
                                <tr>
                                    <td><?php echo safeHtml($t['Platzierung']); ?></td>
                                    <td><?php echo safeHtml($t['Nachname']); ?></td>
                                    <td><?php echo safeHtml($t['Vorname']); ?></td>
                                    <td><?php echo safeHtml(date("Y", strtotime($t['Geburtsdatum']))); ?></td>
                                    <td><?php echo safeHtml($t['Beschreibung_kurz']); ?></td>
                                    <td><?php echo safeHtml($t['Vereinsname']); ?></td>
                                    <td><?php echo safeHtml($t['Wertungssumme']); ?></td>
                                    <?php
                                    $tid = $t['TurnerID'];
                                    foreach ($dynamicColumnHeaders as $grp => $hdr):
                                        if (isset($wertungenByTurner[$tid][$grp])):
                                            echo "<td>" . safeHtml(implode(", ", $wertungenByTurner[$tid][$grp])) . "</td>";
                                        else:
                                            echo "<td>-</td>";
                                        endif;
                                    endforeach;
                                    ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
