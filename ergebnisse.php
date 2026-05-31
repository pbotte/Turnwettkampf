<?php

error_reporting(E_ALL);
ini_set('display_errors', 'On');

require_once 'includes/db.php';
require_once 'includes/helpers.php';
require_once 'includes/layout.php';

$pdo = db();

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

render_header('Wettkampf-Detailergebnisse', [
    'extraCss' => <<<'CSS'
        .table thead th {
            position: sticky;
            top: 0;
            background: #212529;
            color: #fff;
            z-index: 2;
        }
        .table thead th:first-child,
        .table tbody td:first-child {
            position: sticky;
            left: 0;
            background: #fff;
            z-index: 1;
        }
        .table thead th:first-child {
            z-index: 3;
            background: #212529;
        }
        .table thead th {
            white-space: nowrap;
        }
        @media (max-width: 768px) {
            .table thead th,
            .table tbody td {
                white-space: nowrap;
            }
            .table-responsive {
                border-radius: 12px;
            }
        }
CSS,
]);
?>
    <div class="container my-4 page-wrap">
        <h1 class="mb-3">Wettkampf-Detailergebnisse</h1>

        <!-- Dropdown-Menüs für Gruppierung und Sortierung -->
        <div class="panel mb-3">
          <form method="get">
            <div class="row g-2">
                <div class="col-12 col-md-6">
                    <label for="grouping" class="form-label">Gruppierung:</label>
                    <select id="grouping" name="grouping" class="form-select" onchange="this.form.submit()">
                        <option value="wettkaempfe" <?php if($grouping=='wettkaempfe') echo 'selected'; ?>>Nach Wettkämpfen</option>
                        <option value="riegen"      <?php if($grouping=='riegen')      echo 'selected'; ?>>Nach Riegen</option>
                        <option value="vereine"     <?php if($grouping=='vereine')     echo 'selected'; ?>>Nach Vereinszugehörigkeit</option>
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label for="sorting" class="form-label">Sortierung:</label>
                    <select id="sorting" name="sorting" class="form-select" onchange="this.form.submit()">
                        <option value="nachname"    <?php if($sorting=='nachname')    echo 'selected'; ?>>Nach Nachname, Vorname</option>
                        <option value="platzierung" <?php if($sorting=='platzierung') echo 'selected'; ?>>Nach Platzierung</option>
                    </select>
                </div>
            </div>
          </form>
        </div>

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
            <div class="panel mb-5">
                <?php
                // Gruppenkopf
                if ($grouping == 'wettkaempfe'):
                    if (isset($wettkaempfeData[$groupKey])):
                        $w = $wettkaempfeData[$groupKey];
                        $gk = $geschlechterLookup[$w['GeschlechtID']]['Beschreibung_kurz'] ?? '-';
                        echo "<h2 class='h5 mb-3'>Wettkampf: " . h($w['Beschreibung']) .
                             " <span class='text-muted'>(Geschlecht: " . h($gk) .
                             ", NWertungen: " . h($w['NWertungen']) .
                             ", NGeraeteMax: " . h($w['NGeraeteMax']) . ")</span></h2>";
                    else:
                        echo "<h2 class='h5 mb-3'>Wettkampf: -</h2>";
                    endif;
                elseif ($grouping == 'riegen'):
                    if (isset($riegenData[$groupKey])):
                        echo "<h2 class='h5 mb-3'>Riege: " . h($riegenData[$groupKey]['Beschreibung']) . "</h2>";
                    else:
                        echo "<h2 class='h5 mb-3'>Riege: -</h2>";
                    endif;
                elseif ($grouping == 'vereine'):
                    $vn = h($turnerList[0]['Vereinsname'] ?? '-');
                    echo "<h2 class='h5 mb-3'>Verein: {$vn}</h2>";
                endif;
                ?>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle mb-0">
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
                                    <th><?php echo h($header); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($turnerList as $t): ?>
                                <tr>
                                    <td><?php echo h($t['Platzierung']); ?></td>
                                    <td><?php echo h($t['Nachname']); ?></td>
                                    <td><?php echo h($t['Vorname']); ?></td>
                                    <td><?php echo h(date("Y", strtotime($t['Geburtsdatum']))); ?></td>
                                    <td><?php echo h($t['Beschreibung_kurz']); ?></td>
                                    <td><?php echo h($t['Vereinsname']); ?></td>
                                    <td><?php echo h($t['Wertungssumme']); ?></td>
                                    <?php
                                    $tid = $t['TurnerID'];
                                    foreach ($dynamicColumnHeaders as $grp => $hdr):
                                        if (isset($wertungenByTurner[$tid][$grp])):
                                            echo "<td>" . h(implode(", ", $wertungenByTurner[$tid][$grp])) . "</td>";
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

<?php render_footer(); ?>
