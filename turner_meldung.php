<?php

error_reporting(E_ALL);
ini_set('display_errors', 'On');

/*
Programmiere eine php-Seite für Mobil-Geräte optimiert und in einem modernen Design.
Sie soll auf eine SQL-Datenbank zugreifen, deren Struktur angehängt ist.

Die Webseite soll mit einem GET-Parameter VereinID aufgerufen werden. Ist diese nicht angegeben, so soll ein entsprechender Hinweis angezeigt werden und die weitere Ausgabe der Seite beendet werden.
Ist die VereinID vorhanden, so soll in der Tabelle Vereine der zu diesem Verein passende Eintrag in "Geheimnis_fuer_Meldung" herausgesucht werden. 

Die Webseite wird zusätzlich mit dem GET-Parameter "hash" aufgerufen. Dieses enthält einen String (ca. 250 Zeichen Länge). Es soll nur dann Zugriff auf die Webseite gewährt werden, wenn folgende Berechnung gültig ist:
sha256(Geheimnis_fuer_Meldung) == hash
Andernfalls die Ausgabe der Webseite mit einem Fehler abbrechen.

Der Titel der Seite soll lauten: Turner Verwaltung für Verein Vereinsname (wobei Vereinsname aus der Tabelle Vereine über die VereinID nachgeschlagen werden soll)

Die Webseite soll die Bearbeitung der SQL-Tabelle "Turner" ermöglichen. 
Möglich sein soll: 
- Neuen Eintrag hinzufügen,
- Bestehenden bearbeiten,
- bestehenden Löschen.
Auf der ganzen Seite sollen nur solche Turner bearbeitet/neuangelegt/gelöscht werden können, welche die VereinID haben, welche als GET-Parameter übergeben wurde. 

Beim Anlegen eines neuen Turners sollen nur die Spalten Vorname, Nachname, Geburtsdatum, GeschlechtID und WettkampfID abgefragt werden. Später in der Anzeige sollen auch die anderen Spalten aus der Tabelle "Turner" (außer "TurnerID") angezeigt werden.
Beim Bearbeiten sollen jedoch nur die gleichen Spalten wie beim Anlegen bearbeitbar sein.

Alle Einträge aus der SQL-Tabelle sollen in einer Tabelle ausgegeben werden. Sortierung nach dem Alphabet. Das Datum soll in der Tabelle im Format Tag.Monat.Jahr ausgegeben werden.

Die Spalten "WettkampfID", "GeschlechtID" und "RiegenID" sollen in den Tabellen "Wettkaempfe" und "Geschlechter" und "Riegen" nachgeschlagen werden. 

Standard bei 
- Wettkampf soll NULL
- Riege soll NULL
- MannschaftsID soll NULL
- Geschlecht soll "weiblich" (also ID=3) sein.

Es sollen Dropdowns für die Nachgeschlagenen Werte verwendet werden.
Bootstrap und PDO sollen verwendet werden.

Aktuell erhalte ich den Fehler: "Deprecated: htmlspecialchars(): Passing null to parameter #1 ($string) of type string is deprecated in ..." 
Um dies zu lösen, ersetze bei der Nutzung von htmlspecialchars durch eine eigene Funktion, welche bei einem übergebenen null-String einfach "-" ausgibt und sonst die Funktion "htmlspecialchars" aufruft.

Für die Anbingung an die Datenbank sollen folgende Variablen verwendet werden: $dbHost, $dbName, $dbUser, $dbPass

Immer nachdem eine Anfrage an die Datenank gesendet wurde soll die folgende Funktion aufgerufen werden:
    Protokoll_Eintragen_erstellen(PARAMETER);
Dabei soll als PARAMETER das wichtigste in Kurzform über die Datenkbankoperation protokolliert werden.
*/

include 'auth.php';
include 'config.php';
include 'includes/protokoll.php';






/**
 * Eigene Funktion für htmlspecialchars, die bei null den String "-" ausgibt.
 */
function safeHtml($string) {
    if ($string === null) {
        return "-";
    }
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Überprüfe GET-Parameter VereinID
if (!isset($_GET['VereinID'])) {
    echo "VereinID nicht angegeben.";
    exit;
}
$vereinID = (int) $_GET['VereinID'];

// Überprüfe GET-Parameter hash
if (!isset($_GET['hash'])) {
    echo "Hash nicht angegeben.";
    exit;
}
$hashParam = $_GET['hash'];

// Verbindung zur Datenbank mit PDO herstellen
try {
    $dsn = "mysql:host=$dbHost;dbname=$dbName;charset=utf8";
    $pdo = new PDO($dsn, $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Verbindungsfehler: " . $e->getMessage());
}

// Verein-Datensatz abrufen
$stmt = $pdo->prepare("SELECT * FROM Vereine WHERE VereinID = ?");
$stmt->execute([$vereinID]);
$verein = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$verein) {
    echo "Verein nicht gefunden.";
    exit;
}

// Prüfe, ob der übergebene Hash mit sha256(Geheimnis_fuer_Meldung) übereinstimmt.
$geheimnis = $verein['Geheimnis_fuer_Meldung'];
$computedHash = hash('sha256', $geheimnis);
if ($computedHash !== $hashParam) {
    echo "Ungültiger Zugriff: Hash stimmt nicht überein.";
    exit;
}

// Seitentitel setzen, Vereinsname wird aus dem Datensatz übernommen.
$pageTitle = "Turner Verwaltung für Verein " . safeHtml($verein['Vereinsname']);

// POST-Requests verarbeiten (Eintrag hinzufügen, bearbeiten, löschen)
$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add') {
        // Neuen Turner einfügen – nur die Felder Vorname, Nachname, Geburtsdatum, GeschlechtID, WettkampfID
        $vorname = $_POST['Vorname'];
        $nachname = $_POST['Nachname'];
        $geburtsdatum = $_POST['Geburtsdatum'];
        $geschlechtID = $_POST['GeschlechtID'];
        $wettkampfID = ($_POST['WettkampfID'] === '') ? null : $_POST['WettkampfID'];
        
        $stmt = $pdo->prepare("INSERT INTO Turner (Vorname, Nachname, Geburtsdatum, GeschlechtID, WettkampfID, VereinID) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$vorname, $nachname, $geburtsdatum, $geschlechtID, $wettkampfID, $vereinID]);

        Protokoll_Eintragen_erstellen("Neuen Turner durch Verein eingetragen: $vorname, $nachname, $geburtsdatum, $geschlechtID, $wettkampfID, $vereinID");

        // Nach erfolgreicher Aktion weiterleiten (PRG-Pattern)
        header("Location: " . $_SERVER['PHP_SELF'] . "?VereinID=" . $vereinID . "&hash=" . urlencode($hashParam));
        exit;
    } elseif ($action === 'edit') {
        // Bestehenden Turner bearbeiten (nur die 5 Felder)
        $turnerID = $_POST['TurnerID'];
        $vorname = $_POST['Vorname'];
        $nachname = $_POST['Nachname'];
        $geburtsdatum = $_POST['Geburtsdatum'];
        $geschlechtID = $_POST['GeschlechtID'];
        $wettkampfID = ($_POST['WettkampfID'] === '') ? null : $_POST['WettkampfID'];
        
        $stmt = $pdo->prepare("UPDATE Turner SET Vorname = ?, Nachname = ?, Geburtsdatum = ?, GeschlechtID = ?, WettkampfID = ? WHERE TurnerID = ? AND VereinID = ?");
        $stmt->execute([$vorname, $nachname, $geburtsdatum, $geschlechtID, $wettkampfID, $turnerID, $vereinID]);
        
        Protokoll_Eintragen_erstellen("Bestehenden Turner bearbeitet durch Verein: $vorname, $nachname, $geburtsdatum, $geschlechtID, $wettkampfID, $turnerID, $vereinID");

        header("Location: " . $_SERVER['PHP_SELF'] . "?VereinID=" . $vereinID . "&hash=" . urlencode($hashParam));
        exit;
    } elseif ($action === 'delete') {
        // Turner löschen
        $turnerID = $_POST['TurnerID'];
        $stmt = $pdo->prepare("DELETE FROM Turner WHERE TurnerID = ? AND VereinID = ?");
        $stmt->execute([$turnerID, $vereinID]);

        Protokoll_Eintragen_erstellen("Turner durch Verein gelöscht: $turnerID");
        
        header("Location: " . $_SERVER['PHP_SELF'] . "?VereinID=" . $vereinID . "&hash=" . urlencode($hashParam));
        exit;
    }
}

// Dropdown-Daten abrufen:
// Geschlechter (Standard: weiblich, ID=3)
$geschlechterStmt = $pdo->query("SELECT * FROM Geschlechter ORDER BY Beschreibung");
$geschlechter = $geschlechterStmt->fetchAll(PDO::FETCH_ASSOC);

// Wettkämpfe (Dropdown – Standard: NULL möglich)
$wettkaempfeStmt = $pdo->query("SELECT * FROM Wettkaempfe ORDER BY Beschreibung");
$wettkaempfe = $wettkaempfeStmt->fetchAll(PDO::FETCH_ASSOC);

// Riegen (für Anzeige in der Liste)
$riegenStmt = $pdo->query("SELECT * FROM Riegen ORDER BY Beschreibung");
$riegen = $riegenStmt->fetchAll(PDO::FETCH_ASSOC);

// Alle Turner des Vereins (sortiert alphabetisch nach Nachname und Vorname)
$stmt = $pdo->prepare("SELECT * FROM Turner WHERE VereinID = ? ORDER BY Nachname, Vorname");
$stmt->execute([$vereinID]);
$turnerList = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<script src="menu.js"></script>
<div class="container">
    <h1 class="mt-4"><?php echo $pageTitle; ?></h1>

    <!-- Formular zum Hinzufügen eines neuen Turners -->
    <h2>Neuen Turner hinzufügen</h2>
    <form method="post" class="mb-4">
        <input type="hidden" name="action" value="add">
        <div class="mb-3">
            <label for="Vorname" class="form-label">Vorname</label>
            <input type="text" name="Vorname" id="Vorname" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="Nachname" class="form-label">Nachname</label>
            <input type="text" name="Nachname" id="Nachname" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="Geburtsdatum" class="form-label">Geburtsdatum</label>
            <input type="date" name="Geburtsdatum" id="Geburtsdatum" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="GeschlechtID" class="form-label">Geschlecht</label>
            <select name="GeschlechtID" id="GeschlechtID" class="form-select">
                <?php
                foreach ($geschlechter as $g) {
                    // Standardauswahl: weiblich (ID=3)
                    $selected = ($g['GeschlechtID'] == 3) ? 'selected' : '';
                    echo '<option value="' . safeHtml($g['GeschlechtID']) . '" ' . $selected . '>' . safeHtml($g['Beschreibung']) . '</option>';
                }
                ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="WettkampfID" class="form-label">Wettkampf</label>
            <select name="WettkampfID" id="WettkampfID" class="form-select">
                <option value="">-</option>
                <?php
                foreach ($wettkaempfe as $w) {
                    echo '<option value="' . safeHtml($w['WettkampfID']) . '">' . safeHtml($w['Beschreibung']) . '</option>';
                }
                ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Hinzufügen</button>
    </form>

    <!-- Liste der Turner -->
    <h2>Turnerliste</h2>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Vorname</th>
                <th>Nachname</th>
                <th>Geburtsdatum</th>
                <th>Geschlecht</th>
                <th>Wettkampf</th>
                <th>Riege</th>
                <th>Mannschaft</th>
                <th>Wertungssumme</th>
                <th>Platzierung</th>
                <th>Aktionen</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($turnerList as $t): ?>
                <tr>
                    <td><?php echo safeHtml($t['Vorname']); ?></td>
                    <td><?php echo safeHtml($t['Nachname']); ?></td>
                    <td><?php echo date("d.m.Y", strtotime($t['Geburtsdatum'])); ?></td>
                    <td>
                        <?php 
                        // Geschlecht anhand der Geschlechter-Tabelle nachschlagen
                        foreach ($geschlechter as $g) {
                            if ($g['GeschlechtID'] == $t['GeschlechtID']) {
                                echo safeHtml($g['Beschreibung']);
                                break;
                            }
                        }
                        ?>
                    </td>
                    <td>
                        <?php 
                        // Wettkampf anhand der Wettkämpfe-Tabelle nachschlagen
                        $wettkampfText = "-";
                        foreach ($wettkaempfe as $w) {
                            if ($w['WettkampfID'] == $t['WettkampfID']) {
                                $wettkampfText = $w['Beschreibung'];
                                break;
                            }
                        }
                        echo safeHtml($wettkampfText);
                        ?>
                    </td>
                    <td>
                        <?php 
                        // Riege anhand der Riegen-Tabelle nachschlagen
                        $riegeText = "-";
                        foreach ($riegen as $r) {
                            if ($r['RiegenID'] == $t['RiegenID']) {
                                $riegeText = $r['Beschreibung'];
                                break;
                            }
                        }
                        echo safeHtml($riegeText);
                        ?>
                    </td>
                    <td><?php echo safeHtml($t['MannschaftsID']); ?></td>
                    <td><?php echo safeHtml($t['Wertungssumme']); ?></td>
                    <td><?php echo safeHtml($t['Platzierung']); ?></td>
                    <td>
                        <!-- Formular, um in den Bearbeitungsmodus zu wechseln -->
                        <form method="post" style="display:inline-block;">
                            <input type="hidden" name="action" value="edit_form">
                            <input type="hidden" name="TurnerID" value="<?php echo safeHtml($t['TurnerID']); ?>">
                            <button type="submit" class="btn btn-sm btn-warning">Bearbeiten</button>
                        </form>
                        <!-- Formular zum Löschen -->
                        <form method="post" style="display:inline-block;" onsubmit="return confirm('Soll dieser Turner wirklich gelöscht werden?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="TurnerID" value="<?php echo safeHtml($t['TurnerID']); ?>">
                            <button type="submit" class="btn btn-sm btn-danger">Löschen</button>
                        </form>
                    </td>
                </tr>
                <?php 
                // Falls für diesen Eintrag der Bearbeitungsmodus angefordert wurde, wird ein Inline-Bearbeitungsformular angezeigt.
                if (isset($_POST['action']) && $_POST['action'] === 'edit_form' && isset($_POST['TurnerID']) && $_POST['TurnerID'] == $t['TurnerID']):
                ?>
                <tr>
                    <td colspan="10">
                        <form method="post" class="row g-3">
                            <input type="hidden" name="action" value="edit">
                            <input type="hidden" name="TurnerID" value="<?php echo safeHtml($t['TurnerID']); ?>">
                            <div class="col-md-2">
                                <label class="form-label">Vorname</label>
                                <input type="text" name="Vorname" class="form-control" value="<?php echo safeHtml($t['Vorname']); ?>" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Nachname</label>
                                <input type="text" name="Nachname" class="form-control" value="<?php echo safeHtml($t['Nachname']); ?>" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Geburtsdatum</label>
                                <input type="date" name="Geburtsdatum" class="form-control" value="<?php echo $t['Geburtsdatum']; ?>" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Geschlecht</label>
                                <select name="GeschlechtID" class="form-select">
                                    <?php
                                    foreach ($geschlechter as $g) {
                                        $selected = ($g['GeschlechtID'] == $t['GeschlechtID']) ? 'selected' : '';
                                        echo '<option value="' . safeHtml($g['GeschlechtID']) . '" ' . $selected . '>' . safeHtml($g['Beschreibung']) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Wettkampf</label>
                                <select name="WettkampfID" class="form-select">
                                    <option value="">-</option>
                                    <?php
                                    foreach ($wettkaempfe as $w) {
                                        $selected = ($w['WettkampfID'] == $t['WettkampfID']) ? 'selected' : '';
                                        echo '<option value="' . safeHtml($w['WettkampfID']) . '" ' . $selected . '>' . safeHtml($w['Beschreibung']) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-2 align-self-end">
                                <button type="submit" class="btn btn-primary">Speichern</button>
                            </div>
                        </form>
                    </td>
                </tr>
                <?php endif; ?>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
