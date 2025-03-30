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
- E1-Note, E2-Note, E3-Note, E4-Note und P-Stufe NULL
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

// Abfrage aller Einträge aus Wertungen (Join mit Turner und Geraete)
$sql = "SELECT W.WertungID, T.Vorname, T.Nachname, G.Beschreibung AS GeraetBeschreibung,
        W.`P-Stufe`, W.`D-Note`, W.`E1-Note`, W.`E2-Note`, W.`E3-Note`, W.`E4-Note`, W.`nA-Abzug`
        FROM Wertungen W
        JOIN Turner T ON W.TurnerID = T.TurnerID
        JOIN Geraete G ON W.GeraetID = G.GeraetID
        ORDER BY W.WertungID";
$stmt = $pdo->query($sql);
$wertungen = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Wertungen Verwaltung</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS einbinden -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<script src="menu.js"></script>
<div class="container mt-3">
    <h1 class="mb-4">Wertungen Verwaltung</h1>
    <?php echo $message; ?>

    <!-- Formular für neuen Eintrag -->
    <div class="card mb-4">
        <div class="card-header">Neuen Eintrag hinzufügen</div>
        <div class="card-body">
            <form method="post">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label for="TurnerID">Turner</label>
                    <select class="form-control" id="TurnerID" name="TurnerID" required>
                        <option value="">Bitte auswählen</option>
                        <?php foreach ($turnerList as $turner): ?>
                            <option value="<?php echo $turner['TurnerID']; ?>">
                                <?php echo custom_htmlspecialchars($turner['Vorname'] . ' ' . $turner['Nachname']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="GeraetID">Gerät</label>
                    <select class="form-control" id="GeraetID" name="GeraetID" required>
                        <option value="">Bitte auswählen</option>
                        <?php foreach ($geraeteList as $geraet): ?>
                            <option value="<?php echo $geraet['GeraetID']; ?>">
                                <?php echo custom_htmlspecialchars($geraet['TypBeschreibung'] . ' - ' . $geraet['Beschreibung']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- Zahlenfelder mit input type="number" und inputmode="decimal" für mobile Keypads -->
                <div class="form-group">
                    <label for="P_Stufe">P-Stufe</label>
                    <input type="number" step="0.01" inputmode="decimal" class="form-control" id="P_Stufe" name="P_Stufe" placeholder="NULL">
                </div>
                <div class="form-group">
                    <label for="D_Note">D-Note</label>
                    <input type="number" step="0.01" inputmode="decimal" class="form-control" id="D_Note" name="D_Note" value="0.00" required>
                </div>
                <div class="form-group">
                    <label for="E1_Note">E1-Note</label>
                    <input type="number" step="0.01" inputmode="decimal" class="form-control" id="E1_Note" name="E1_Note" placeholder="NULL">
                </div>
                <div class="form-group">
                    <label for="E2_Note">E2-Note</label>
                    <input type="number" step="0.01" inputmode="decimal" class="form-control" id="E2_Note" name="E2_Note" placeholder="NULL">
                </div>
                <div class="form-group">
                    <label for="E3_Note">E3-Note</label>
                    <input type="number" step="0.01" inputmode="decimal" class="form-control" id="E3_Note" name="E3_Note" placeholder="NULL">
                </div>
                <div class="form-group">
                    <label for="E4_Note">E4-Note</label>
                    <input type="number" step="0.01" inputmode="decimal" class="form-control" id="E4_Note" name="E4_Note" placeholder="NULL">
                </div>
                <div class="form-group">
                    <label for="nA_Abzug">nA-Abzug</label>
                    <input type="number" step="0.01" inputmode="decimal" class="form-control" id="nA_Abzug" name="nA_Abzug" value="0.00" required>
                </div>
                <button type="submit" class="btn btn-primary">Hinzufügen</button>
            </form>
        </div>
    </div>

    <!-- Tabelle mit allen Einträgen -->
    <table class="table table-striped table-responsive">
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
                <tr>
                    <td><?php echo custom_htmlspecialchars($wertung['WertungID']); ?></td>
                    <td><?php echo custom_htmlspecialchars($wertung['Vorname'] . ' ' . $wertung['Nachname']); ?></td>
                    <td><?php echo custom_htmlspecialchars($wertung['GeraetBeschreibung']); ?></td>
                    <td><?php echo custom_htmlspecialchars($wertung['P-Stufe']); ?></td>
                    <td><?php echo custom_htmlspecialchars($wertung['D-Note']); ?></td>
                    <td><?php echo custom_htmlspecialchars($wertung['E1-Note']); ?></td>
                    <td><?php echo custom_htmlspecialchars($wertung['E2-Note']); ?></td>
                    <td><?php echo custom_htmlspecialchars($wertung['E3-Note']); ?></td>
                    <td><?php echo custom_htmlspecialchars($wertung['E4-Note']); ?></td>
                    <td><?php echo custom_htmlspecialchars($wertung['nA-Abzug']); ?></td>
                    <td>
                        <!-- Aktionen: Bearbeiten (führt zu einer edit.php) und Löschen -->
                        <a href="wertungen_edit.php?id=<?php echo $wertung['WertungID']; ?>" class="btn btn-sm btn-warning">Edit</a>
                        <a href="?delete=<?php echo $wertung['WertungID']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Eintrag wirklich löschen?');">Löschen</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</div>
<!-- jQuery und Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
