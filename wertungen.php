<?php

error_reporting(E_ALL);
ini_set('display_errors', 'On');

/*
Programmiere eine php-Seite für Mobil-Geräte optimiert und in einem modernen Design.
Sie soll auf eine SQL-Datenbank zugreifen, deren Struktur angehängt ist.

Die Webseite soll die Bearbeitung der SQL-Tabelle "Wertungen" ermöglichen. 
Möglich sein soll: 
- Neuen Eintrag hinzufügen,
- Bestehenden bearbeiten,
- bestehenden Löschen.

Alle Einträge aus der SQL-Tabelle sollen in einer Tabelle ausgegeben werden. Sortierung nach dem WertungsID. 

Die Spalten "TurnerID", "GeraetID" sollen in den Tabellen "Tuner" und "Geraete" nachgeschlagen werden. 
Die Eingabe in die Felder P-Stufe, D-Note, E1-Note, E2-Note, E3-Note, E4-Note, nA-Abzug ist eine Fließkommazahl, jedoch nur auf 2 Nachkommastellen genau.

Standard beim Eingaben der WErte soll sein:
- D-Note, E1-Note, E2-Note, E3-Note, E4-Note und P-Stufe NULL
- nA-Abzug ist 0,0

Es sollen Dropdowns für die Nachgeschlagenen Werte verwendet werden.
Bootstrap und PDO sollen verwendet werden.

Wenn die Seite auf dem Handy/Tablett geöffnet wird, soll bei der Eingabe in die Zahlen-Felder (P-Stufe, D-Note, E1-Note, E2-Note, E3-Note, E4-Note und nA-Abzug) vom Betriebsystemher eine Zahlen-Tastatur angezeigt werden.

Aktuell erhalte ich den Fehler: "Deprecated: htmlspecialchars(): Passing null to parameter #1 ($string) of type string is deprecated in ..." 
Um dies zu lösen, ersetze bei der Nutzung von htmlspecialchars durch eine eigene Funktion, welche bei einem übergebenen null-String einfach "-" ausgibt und sonst die Funktion "htmlspecialchars" aufruft.

Für die Anbingung an die Datenbank sollen folgende Variablen verwendet werden: $dbHost, $dbName, $dbUser, $dbPass
und als charset: "utf8".

Beim Neuanlegen oder Bearbeiten soll überprüft werden, ob ein Eintrag in der Tabelle Wertungen für die gleichen (TurnerID,GeraeteTypID) bereits vorhanden ist (nachschauen über Tabelle Geraete in Tabelle GeraeteTypen). Falls ja, so soll eine Warnmeldung angezeigt werden.

*/

include 'auth.php';
include 'config.php';
// Datenbankverbindungsparameter anpassen!


try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Verbindung fehlgeschlagen: " . $e->getMessage());
}

// Eigene htmlspecialchars-Funktion, die bei null "-" zurückgibt.
function custom_htmlspecialchars($string) {
    return is_null($string) ? '-' : htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

$message = '';

// Verarbeitung von Formularaktionen: Hinzufügen und Bearbeiten
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    // Felder aus dem Formular
    $turnerID = $_POST['TurnerID'] ?? '';
    $geraetID = $_POST['GeraetID'] ?? '';
    // Bei den Fließkommawerten: Ist das Feld leer, wird NULL (bzw. Standardwert) gesetzt.
    $p_stufe = (isset($_POST['P_Stufe']) && $_POST['P_Stufe'] !== '') ? number_format((float)$_POST['P_Stufe'], 2, '.', '') : null;
    $d_note  = (isset($_POST['D_Note'])  && $_POST['D_Note']  !== '') ? number_format((float)$_POST['D_Note'], 2, '.', '')  : 0.00;
    $e1_note = (isset($_POST['E1_Note']) && $_POST['E1_Note'] !== '') ? number_format((float)$_POST['E1_Note'], 2, '.', '') : null;
    $e2_note = (isset($_POST['E2_Note']) && $_POST['E2_Note'] !== '') ? number_format((float)$_POST['E2_Note'], 2, '.', '') : null;
    $e3_note = (isset($_POST['E3_Note']) && $_POST['E3_Note'] !== '') ? number_format((float)$_POST['E3_Note'], 2, '.', '') : null;
    $e4_note = (isset($_POST['E4_Note']) && $_POST['E4_Note'] !== '') ? number_format((float)$_POST['E4_Note'], 2, '.', '') : null;
    $na_abzug = (isset($_POST['nA_Abzug']) && $_POST['nA_Abzug'] !== '') ? number_format((float)$_POST['nA_Abzug'], 2, '.', '') : '0.00';

    // Überprüfung, ob bereits ein Eintrag für (TurnerID, Gerätetyp) existiert.
    // Hierzu wird über die Geraete-Tabelle der GeraeteTypID ermittelt.
    $sqlDup = "SELECT COUNT(*) FROM Wertungen
        JOIN Geraete ON Wertungen.`GeraetID` = Geraete.`GeraetID`
        WHERE Wertungen.`TurnerID` = :turnerID
        AND Geraete.`GeraeteTypID` = (SELECT G.`GeraeteTypID` FROM Geraete G WHERE G.`GeraetID` = :geraetID)";
    // Falls es sich um eine Bearbeitung handelt, den aktuellen Datensatz ausschließen.
    if ($action == 'edit') {
        $wertungID = $_POST['WertungID'];
        $sqlDup .= " AND Wertungen.`WertungID` != :wertungID";
        $stmtDup = $pdo->prepare($sqlDup);
        $stmtDup->execute([':turnerID' => $turnerID, ':geraetID' => $geraetID, ':wertungID' => $wertungID]);
    } else {
        $stmtDup = $pdo->prepare($sqlDup);
        $stmtDup->execute([':turnerID' => $turnerID, ':geraetID' => $geraetID]);
    }
    $duplicateCount = $stmtDup->fetchColumn();

    if ($duplicateCount > 0) {
        $message = '<div class="alert alert-warning">Ein Eintrag für diesen Turner und Gerätetyp existiert bereits.</div>';
    } else {
        if ($action == 'add') {
            $sql = "INSERT INTO Wertungen (TurnerID, GeraetID, `P-Stufe`, `D-Note`, `E1-Note`, `E2-Note`, `E3-Note`, `E4-Note`, `nA-Abzug`)
                    VALUES (:turnerID, :geraetID, :p_stufe, :d_note, :e1_note, :e2_note, :e3_note, :e4_note, :na_abzug)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':turnerID' => $turnerID,
                ':geraetID' => $geraetID,
                ':p_stufe' => $p_stufe,
                ':d_note'  => $d_note,
                ':e1_note' => $e1_note,
                ':e2_note' => $e2_note,
                ':e3_note' => $e3_note,
                ':e4_note' => $e4_note,
                ':na_abzug'=> $na_abzug
            ]);
            $message = '<div class="alert alert-success">Eintrag hinzugefügt.</div>';
        } elseif ($action == 'edit') {
            $wertungID = $_POST['WertungID'];
            $sql = "UPDATE Wertungen SET
                    TurnerID = :turnerID,
                    GeraetID = :geraetID,
                    `P-Stufe` = :p_stufe,
                    `D-Note` = :d_note,
                    `E1-Note` = :e1_note,
                    `E2-Note` = :e2_note,
                    `E3-Note` = :e3_note,
                    `E4-Note` = :e4_note,
                    `nA-Abzug` = :na_abzug
                    WHERE WertungID = :wertungID";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':turnerID' => $turnerID,
                ':geraetID' => $geraetID,
                ':p_stufe' => $p_stufe,
                ':d_note'  => $d_note,
                ':e1_note' => $e1_note,
                ':e2_note' => $e2_note,
                ':e3_note' => $e3_note,
                ':e4_note' => $e4_note,
                ':na_abzug'=> $na_abzug,
                ':wertungID' => $wertungID
            ]);
            $message = '<div class="alert alert-success">Eintrag aktualisiert.</div>';
        }
    }
}

// Löschen eines Eintrags per GET-Parameter
if (isset($_GET['delete'])) {
    $wertungID = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM Wertungen WHERE WertungID = :wertungID");
    $stmt->execute([':wertungID' => $wertungID]);
    $message = '<div class="alert alert-success">Eintrag gelöscht.</div>';
}

// Abfrage für die Dropdown-Liste Turner
$turnerStmt = $pdo->query("SELECT TurnerID, Vorname, Nachname FROM Turner ORDER BY Nachname, Vorname");
$turnerList = $turnerStmt->fetchAll(PDO::FETCH_ASSOC);

// Abfrage für die Dropdown-Liste Geräte inkl. Gerätetypen
$geraeteStmt = $pdo->query("SELECT G.GeraetID, G.Beschreibung, GT.Beschreibung as TypBeschreibung, GT.GeraeteTypID
                              FROM Geraete G
                              JOIN GeraeteTypen GT ON G.GeraeteTypID = GT.GeraeteTypID
                              ORDER BY GT.Reihenfolge, G.Beschreibung");
$geraeteList = $geraeteStmt->fetchAll(PDO::FETCH_ASSOC);

// Suche/Filter & Pagination (serverseitig)
$search = trim($_GET['q'] ?? '');
$onlyIncomplete = isset($_GET['incomplete']) && $_GET['incomplete'] === '1';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = (int)($_GET['per_page'] ?? 25);
if (!in_array($perPage, [10, 25, 50, 100], true)) {
    $perPage = 25;
}

$whereClauses = [];
$params = [];

if ($search !== '') {
    $whereClauses[] = "(T.Vorname LIKE :q OR T.Nachname LIKE :q OR G.Beschreibung LIKE :q OR W.WertungID LIKE :q)";
    $params[':q'] = '%' . $search . '%';
}

if ($onlyIncomplete) {
    $whereClauses[] = "(W.`D-Note` IS NULL OR (W.`E1-Note` IS NULL AND W.`E2-Note` IS NULL AND W.`E3-Note` IS NULL AND W.`E4-Note` IS NULL))";
}

$whereSql = $whereClauses ? ('WHERE ' . implode(' AND ', $whereClauses)) : '';

// Gesamtanzahl für Pagination
$countSql = "SELECT COUNT(*) FROM Wertungen W
        JOIN Turner T ON W.TurnerID = T.TurnerID
        JOIN Geraete G ON W.GeraetID = G.GeraetID
        $whereSql";
$stmtCount = $pdo->prepare($countSql);
$stmtCount->execute($params);
$totalRows = (int)$stmtCount->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $perPage;

// Abfrage der Einträge aus Wertungen (Join mit Turner und Geraete)
$sql = "SELECT W.WertungID, T.Vorname, T.Nachname, G.Beschreibung AS GeraetBeschreibung,
        W.`P-Stufe`, W.`D-Note`, W.`E1-Note`, W.`E2-Note`, W.`E3-Note`, W.`E4-Note`, W.`nA-Abzug`
        FROM Wertungen W
        JOIN Turner T ON W.TurnerID = T.TurnerID
        JOIN Geraete G ON W.GeraetID = G.GeraetID
        $whereSql
        ORDER BY W.WertungID ASC
        LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value, PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$wertungen = $stmt->fetchAll(PDO::FETCH_ASSOC);

$baseParams = [];
if ($search !== '') {
    $baseParams['q'] = $search;
}
if ($onlyIncomplete) {
    $baseParams['incomplete'] = '1';
}
if ($perPage !== 25) {
    $baseParams['per_page'] = (string)$perPage;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Wertungen Verwaltung</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS einbinden -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f6f7fb;
        }
        .page-wrap {
            max-width: 1200px;
        }
        .panel {
            background: #fff;
            border-radius: 16px;
            padding: 16px;
            box-shadow: 0 6px 18px rgba(0,0,0,0.08);
        }
        .form-select,
        .form-control {
            font-size: 1.05rem;
        }
        .action-group {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }
        .action-group .btn {
            white-space: nowrap;
        }
        @media (max-width: 768px) {
            .table-mobile thead {
                display: none;
            }
            .table-mobile tr {
                display: block;
                margin-bottom: 0.75rem;
                border: 1px solid #e6e6e6;
                border-radius: 12px;
                padding: 0.25rem 0;
                background: #fff;
            }
            .table-mobile td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 0.5rem 0.75rem;
                border-top: 1px solid #f0f0f0;
            }
            .table-mobile td:first-child {
                border-top: 0;
            }
            .table-mobile td::before {
                content: attr(data-label);
                font-weight: 600;
                color: #6c757d;
                margin-right: 1rem;
            }
            .table-mobile .action-cell {
                justify-content: flex-end;
            }
            .table-mobile .action-cell::before {
                content: "";
            }
            .action-group {
                flex-direction: column;
                width: 100%;
            }
            .action-group .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
<script src="menu.js"></script>
<div class="container my-4 page-wrap">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <h1 class="m-0">Wertungen Verwaltung</h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addWertungModal">
            Hinzufügen
        </button>
    </div>
    <?php echo $message; ?>

    <!-- Tabelle mit allen Einträgen -->
    <div class="panel mb-3">
        <form method="get">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-6">
                <label for="wertungenSearch" class="form-label">Suche</label>
                <input type="search" id="wertungenSearch" name="q" class="form-control" placeholder="Turner, Gerät, WertungID ..." value="<?php echo custom_htmlspecialchars($search); ?>">
            </div>
            <div class="col-12 col-md-3 d-flex align-items-end">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="toggleIncomplete" name="incomplete" value="1" <?php echo $onlyIncomplete ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="toggleIncomplete">
                        Nur unvollständige Wertungen
                    </label>
                </div>
            </div>
            <div class="col-12 col-md-2">
                <label for="perPageSelect" class="form-label">Pro Seite</label>
                <select id="perPageSelect" name="per_page" class="form-select">
                    <?php foreach ([10, 25, 50, 100] as $size): ?>
                        <option value="<?php echo $size; ?>" <?php echo ($perPage === $size) ? 'selected' : ''; ?>>
                            <?php echo $size; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-1 d-flex align-items-end">
                <button type="submit" class="btn btn-outline-primary w-100">Suchen</button>
            </div>
            <div class="col-12 col-md-12 d-flex align-items-end justify-content-md-end">
                <div class="text-muted small">Treffer: <span id="wertungenCount"><?php echo $totalRows; ?></span></div>
            </div>
        </div>
        </form>
    </div>
    <div class="table-responsive panel">
        <table class="table table-striped table-mobile align-middle mb-0" id="wertungenTable">
            <thead>
                <tr>
                    <th>WertungID</th>
                    <th>Turner</th>
                    <th>Gerät</th>
                    <th>P-Stufe</th>
                    <th>D-Note</th>
                    <th>E1-Note</th>
                    <th>E2-Note</th>
                    <th>E3-Note</th>
                    <th>E4-Note</th>
                    <th>nA-Abzug</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($wertungen as $wertung): ?>
                    <?php
                        $isIncomplete = (
                            $wertung['D-Note'] === null ||
                            (
                                $wertung['E1-Note'] === null &&
                                $wertung['E2-Note'] === null &&
                                $wertung['E3-Note'] === null &&
                                $wertung['E4-Note'] === null
                            )
                        );
                    ?>
                    <tr data-incomplete="<?= $isIncomplete ? '1' : '0' ?>">
                        <td data-label="WertungID"><?php echo custom_htmlspecialchars($wertung['WertungID']); ?></td>
                        <td data-label="Turner"><?php echo custom_htmlspecialchars($wertung['Vorname'] . ' ' . $wertung['Nachname']); ?></td>
                        <td data-label="Gerät"><?php echo custom_htmlspecialchars($wertung['GeraetBeschreibung']); ?></td>
                        <td data-label="P-Stufe"><?php echo custom_htmlspecialchars($wertung['P-Stufe']); ?></td>
                        <td data-label="D-Note"><?php echo custom_htmlspecialchars($wertung['D-Note']); ?></td>
                        <td data-label="E1-Note"><?php echo custom_htmlspecialchars($wertung['E1-Note']); ?></td>
                        <td data-label="E2-Note"><?php echo custom_htmlspecialchars($wertung['E2-Note']); ?></td>
                        <td data-label="E3-Note"><?php echo custom_htmlspecialchars($wertung['E3-Note']); ?></td>
                        <td data-label="E4-Note"><?php echo custom_htmlspecialchars($wertung['E4-Note']); ?></td>
                        <td data-label="nA-Abzug"><?php echo custom_htmlspecialchars($wertung['nA-Abzug']); ?></td>
                        <td data-label="Aktionen" class="action-cell">
                            <div class="action-group">
                                <!-- Aktionen: Bearbeiten (führt zu einer edit.php) und Löschen -->
                                <a href="wertungen_edit.php?id=<?php echo $wertung['WertungID']; ?>" class="btn btn-sm btn-warning">Edit</a>
                                <?php
                                    $deleteParams = $baseParams + ['page' => $page, 'delete' => $wertung['WertungID']];
                                    $deleteUrl = $_SERVER['PHP_SELF'] . '?' . http_build_query($deleteParams);
                                ?>
                                <a href="<?php echo $deleteUrl; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Eintrag wirklich löschen?');">Löschen</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
    <nav class="mt-3">
        <ul class="pagination">
            <?php
                $pageStart = max(1, $page - 2);
                $pageEnd = min($totalPages, $page + 2);
                $paramsPrev = $baseParams + ['page' => max(1, $page - 1)];
                $paramsNext = $baseParams + ['page' => min($totalPages, $page + 1)];
            ?>
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $_SERVER['PHP_SELF'] . '?' . http_build_query($paramsPrev) ?>">«</a>
            </li>
            <?php if ($pageStart > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="<?= $_SERVER['PHP_SELF'] . '?' . http_build_query($baseParams + ['page' => 1]) ?>">1</a>
                </li>
                <?php if ($pageStart > 2): ?>
                    <li class="page-item disabled"><span class="page-link">…</span></li>
                <?php endif; ?>
            <?php endif; ?>
            <?php for ($p = $pageStart; $p <= $pageEnd; $p++): ?>
                <li class="page-item <?= $p == $page ? 'active' : '' ?>">
                    <a class="page-link" href="<?= $_SERVER['PHP_SELF'] . '?' . http_build_query($baseParams + ['page' => $p]) ?>"><?= $p ?></a>
                </li>
            <?php endfor; ?>
            <?php if ($pageEnd < $totalPages): ?>
                <?php if ($pageEnd < $totalPages - 1): ?>
                    <li class="page-item disabled"><span class="page-link">…</span></li>
                <?php endif; ?>
                <li class="page-item">
                    <a class="page-link" href="<?= $_SERVER['PHP_SELF'] . '?' . http_build_query($baseParams + ['page' => $totalPages]) ?>"><?= $totalPages ?></a>
                </li>
            <?php endif; ?>
            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $_SERVER['PHP_SELF'] . '?' . http_build_query($paramsNext) ?>">»</a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>

</div>

<!-- Modal: Neuen Eintrag hinzufügen -->
<div class="modal fade" id="addWertungModal" tabindex="-1" aria-labelledby="addWertungLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addWertungLabel">Neuen Eintrag hinzufügen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <div class="modal-body">
                <form method="post" id="addWertungForm">
                    <input type="hidden" name="action" value="add">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="TurnerID" class="form-label">Turner</label>
                            <select class="form-select" id="TurnerID" name="TurnerID" required>
                                <option value="">Bitte auswählen</option>
                                <?php foreach ($turnerList as $turner): ?>
                                    <option value="<?php echo $turner['TurnerID']; ?>">
                                        <?php echo custom_htmlspecialchars($turner['Vorname'] . ' ' . $turner['Nachname']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="GeraetID" class="form-label">Gerät</label>
                            <select class="form-select" id="GeraetID" name="GeraetID" required>
                                <option value="">Bitte auswählen</option>
                                <?php foreach ($geraeteList as $geraet): ?>
                                    <option value="<?php echo $geraet['GeraetID']; ?>">
                                        <?php echo custom_htmlspecialchars($geraet['TypBeschreibung'] . ' - ' . $geraet['Beschreibung']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="P_Stufe" class="form-label">P-Stufe</label>
                            <input type="number" step="0.01" inputmode="decimal" class="form-control" id="P_Stufe" name="P_Stufe" placeholder="NULL">
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="D_Note" class="form-label">D-Note</label>
                            <input type="number" step="0.01" inputmode="decimal" class="form-control" id="D_Note" name="D_Note" placeholder="NULL" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="E1_Note" class="form-label">E1-Note</label>
                            <input type="number" step="0.01" inputmode="decimal" class="form-control" id="E1_Note" name="E1_Note" placeholder="NULL">
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="E2_Note" class="form-label">E2-Note</label>
                            <input type="number" step="0.01" inputmode="decimal" class="form-control" id="E2_Note" name="E2_Note" placeholder="NULL">
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="E3_Note" class="form-label">E3-Note</label>
                            <input type="number" step="0.01" inputmode="decimal" class="form-control" id="E3_Note" name="E3_Note" placeholder="NULL">
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="E4_Note" class="form-label">E4-Note</label>
                            <input type="number" step="0.01" inputmode="decimal" class="form-control" id="E4_Note" name="E4_Note" placeholder="NULL">
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="nA_Abzug" class="form-label">nA-Abzug</label>
                            <input type="number" step="0.01" inputmode="decimal" class="form-control" id="nA_Abzug" name="nA_Abzug" value="0.00" required>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                <button type="submit" form="addWertungForm" class="btn btn-primary">Hinzufügen</button>
            </div>
        </div>
    </div>
</div>

<script>
    (function() {
        const perPageSelect = document.getElementById('perPageSelect');
        if (perPageSelect && perPageSelect.form) {
            perPageSelect.addEventListener('change', function() {
                perPageSelect.form.submit();
            });
        }
    })();
</script>
<!-- jQuery und Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
