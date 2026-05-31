<?php

error_reporting(E_ALL);
ini_set('display_errors', 'On');



require_once 'includes/db.php';
require_once 'includes/helpers.php';


header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = db();

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

    $where = [];
    $params = [];
    if (isset($_GET['GeraeteID']) && $_GET['GeraeteID'] !== '') {
        $where[] = "w.GeraetID = :geraeteid";
        $params[':geraeteid'] = (int) $_GET['GeraeteID'];
    }

    if (isset($_GET['BildschirmID']) && $_GET['BildschirmID'] !== '') {
        $bildschirmGeraete = [
            '1' => [5, 6, 15],
            '2' => [10, 14],
            '3' => [1, 2, 13],
        ];
        $geraeteIds = $bildschirmGeraete[(string) $_GET['BildschirmID']] ?? [];
        if ($geraeteIds) {
            $where[] = 'w.GeraetID IN (' . implode(',', $geraeteIds) . ')';
        }
    }

    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= " ORDER BY w.WertungID DESC LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        echo json_encode(['error' => 'Keine Daten gefunden']);
        exit;
    }

    foreach (['Vorname', 'Nachname', 'GeraetBeschreibung', 'WettkampfBeschreibung', 'WettkampfGeschlechtKurz'] as $field) {
        $result[$field] = h($result[$field]);
    }

    echo json_encode($result, JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}
?>
