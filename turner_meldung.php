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
*/

include 'auth.php';
include 'config.php';
// Datenbankverbindungsparameter anpassen!


// Eigene Funktion zur Ausgabe, welche bei null "-" zurückgibt
function my_htmlspecialchars($string) {
    if ($string === null) {
        return "-";
    }
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Überprüfen, ob VereinID per GET übergeben wurde
if (!isset($_GET['VereinID'])) {
    echo '<p>Fehler: Kein VereinID angegeben!</p>';
    exit;
}

$vereinID = (int) $_GET['VereinID'];

// Überprüfen, ob der GET-Parameter hash übergeben wurde
if (!isset($_GET['hash'])) {
    echo '<p>Fehler: Kein hash angegeben!</p>';
    exit;
}
$hash = $_GET['hash'];

try {
    // PDO-Verbindung aufbauen
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Verbindungsfehler: " . $e->getMessage());
}

// Verein-Daten abrufen und Geheimnis überprüfen
$stmt = $pdo->prepare("SELECT Geheimnis_fuer_Meldung FROM Vereine WHERE VereinID = ?");
$stmt->execute([$vereinID]);
$verein = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$verein) {
    echo '<p>Fehler: Verein nicht gefunden.</p>';
    exit;
}
$secret = $verein['Geheimnis_fuer_Meldung'];
if (hash('sha256', $secret) !== $hash) {
    echo '<p>Fehler: Ungültiger hash.</p>';
    exit;
}

// POST-Anfragen verarbeiten (Hinzufügen, Bearbeiten, Löschen)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        // Neuer Turner: Nur Vorname, Nachname, Geburtsdatum, GeschlechtID, WettkampfID
        $vorname = $_POST['Vorname'] ?? '';
        $nachname = $_POST['Nachname'] ?? '';
        $geburtsdatum = $_POST['Geburtsdatum'] ?? '';
        $geschlechtID = $_POST['GeschlechtID'] ?? 3; // Standard: weiblich (ID 3)
        $wettkampfID = ($_POST['WettkampfID'] === '' ? null : $_POST['WettkampfID']);
        
        $stmt = $pdo->prepare("INSERT INTO Turner (Vorname, Nachname, Geburtsdatum, GeschlechtID, VereinID, WettkampfID, RiegenID, MannschaftsID) VALUES (?, ?, ?, ?, ?, ?, NULL, NULL)");
        $stmt->execute([$vorname, $nachname, $geburtsdatum, $geschlechtID, $vereinID, $wettkampfID]);
        header("Location: ?VereinID={$vereinID}&hash=" . urlencode($hash));
        exit;
    } elseif ($action === 'edit' && isset($_POST['TurnerID'])) {
        // Bearbeiten eines bestehenden Eintrags: Zuerst überprüfen, ob der Turner zur aktuellen VereinID gehört
        $turnerID = (int) $_POST['TurnerID'];
        $stmt = $pdo->prepare("SELECT VereinID FROM Turner WHERE TurnerID = ?");
        $stmt->execute([$turnerID]);
        $entry = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$entry || $entry['VereinID'] != $vereinID) {
            echo '<p>Fehler: Ungültiger Turner.</p>';
            exit;
        }
        $vorname = $_POST['Vorname'] ?? '';
        $nachname = $_POST['Nachname'] ?? '';
        $geburtsdatum = $_POST['Geburtsdatum'] ?? '';
        $geschlechtID = $_POST['GeschlechtID'] ?? 3;
        $wettkampfID = ($_POST['WettkampfID'] === '' ? null : $_POST['WettkampfID']);
        
        $stmt = $pdo->prepare("UPDATE Turner SET Vorname = ?, Nachname = ?, Geburtsdatum = ?, GeschlechtID = ?, WettkampfID = ? WHERE TurnerID = ?");
        $stmt->execute([$vorname, $nachname, $geburtsdatum, $geschlechtID, $wettkampfID, $turnerID]);
        header("Location: ?VereinID={$vereinID}&hash=" . urlencode($hash));
        exit;
    } elseif ($action === 'delete' && isset($_POST['TurnerID'])) {
        // Löschen eines Eintrags – auch hier Zugehörigkeit prüfen
        $turnerID = (int) $_POST['TurnerID'];
        $stmt = $pdo->prepare("SELECT VereinID FROM Turner WHERE TurnerID = ?");
        $stmt->execute([$turnerID]);
        $entry = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$entry || $entry['VereinID'] != $vereinID) {
            echo '<p>Fehler: Ungültiger Turner.</p>';
            exit;
        }
        $stmt = $pdo->prepare("DELETE FROM Turner WHERE TurnerID = ?");
        $stmt->execute([$turnerID]);
        header("Location: ?VereinID={$vereinID}&hash=" . urlencode($hash));
        exit;
    }
}

// Daten für Dropdown-Felder laden
$wettkaempfe = $pdo->query("SELECT WettkampfID, Beschreibung FROM Wettkaempfe")->fetchAll(PDO::FETCH_ASSOC);
$geschlechter = $pdo->query("SELECT GeschlechtID, Beschreibung FROM Geschlechter")->fetchAll(PDO::FETCH_ASSOC);
$riegen = $pdo->query("SELECT RiegenID, Beschreibung FROM Riegen")->fetchAll(PDO::FETCH_ASSOC);

// Alle Turner des Vereins laden (sortiert nach Nachname, dann Vorname) und Lookup-Daten verknüpfen
$stmt = $pdo->prepare("SELECT t.*, g.Beschreibung AS Geschlecht, w.Beschreibung AS Wettkampf, r.Beschreibung AS Riege 
                       FROM Turner t 
                       LEFT JOIN Geschlechter g ON t.GeschlechtID = g.GeschlechtID 
                       LEFT JOIN Wettkaempfe w ON t.WettkampfID = w.WettkampfID 
                       LEFT JOIN Riegen r ON t.RiegenID = r.RiegenID 
                       WHERE t.VereinID = ? 
                       ORDER BY t.Nachname, t.Vorname");
$stmt->execute([$vereinID]);
$turnerList = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Falls per GET ein Eintrag zur Bearbeitung ausgewählt wurde, diesen laden
$editTurner = null;
if (isset($_GET['edit'])) {
    $editID = (int) $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM Turner WHERE TurnerID = ? AND VereinID = ?");
    $stmt->execute([$editID, $vereinID]);
    $editTurner = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Turner Verwaltung</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container">
    <h1 class="mt-4">Turner Verwaltung für Verein <?php echo my_htmlspecialchars($vereinID); ?></h1>

    <!-- Formular für Anlegen / Bearbeiten -->
    <div class="card my-4">
        <div class="card-header">
            <?php echo $editTurner ? 'Turner bearbeiten' : 'Neuen Turner anlegen'; ?>
        </div>
        <div class="card-body">
            <form method="post">
                <?php if ($editTurner): ?>
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="TurnerID" value="<?php echo my_htmlspecialchars($editTurner['TurnerID']); ?>">
                <?php else: ?>
                    <input type="hidden" name="action" value="add">
                <?php endif; ?>
                <div class="mb-3">
                    <label for="Vorname" class="form-label">Vorname</label>
                    <input type="text" class="form-control" id="Vorname" name="Vorname" value="<?php echo $editTurner ? my_htmlspecialchars($editTurner['Vorname']) : ''; ?>" required>
                </div>
                <div class="mb-3">
                    <label for="Nachname" class="form-label">Nachname</label>
                    <input type="text" class="form-control" id="Nachname" name="Nachname" value="<?php echo $editTurner ? my_htmlspecialchars($editTurner['Nachname']) : ''; ?>" required>
                </div>
                <div class="mb-3">
                    <label for="Geburtsdatum" class="form-label">Geburtsdatum</label>
                    <input type="date" class="form-control" id="Geburtsdatum" name="Geburtsdatum" value="<?php echo $editTurner ? my_htmlspecialchars($editTurner['Geburtsdatum']) : ''; ?>" required>
                </div>
                <div class="mb-3">
                    <label for="GeschlechtID" class="form-label">Geschlecht</label>
                    <select class="form-select" id="GeschlechtID" name="GeschlechtID" required>
                        <?php foreach ($geschlechter as $g): ?>
                            <option value="<?php echo my_htmlspecialchars($g['GeschlechtID']); ?>"
                                <?php
                                    $selected = false;
                                    if ($editTurner) {
                                        $selected = ($editTurner['GeschlechtID'] == $g['GeschlechtID']);
                                    } else {
                                        // Standard: weiblich (ID 3)
                                        $selected = ($g['GeschlechtID'] == 3);
                                    }
                                    echo $selected ? 'selected' : '';
                                ?>>
                                <?php echo my_htmlspecialchars($g['Beschreibung']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="WettkampfID" class="form-label">Wettkampf</label>
                    <select class="form-select" id="WettkampfID" name="WettkampfID">
                        <option value="" <?php echo (!$editTurner || $editTurner['WettkampfID'] === null) ? 'selected' : ''; ?>>-- Keine Auswahl --</option>
                        <?php foreach ($wettkaempfe as $w): ?>
                            <option value="<?php echo my_htmlspecialchars($w['WettkampfID']); ?>"
                                <?php echo ($editTurner && $editTurner['WettkampfID'] == $w['WettkampfID']) ? 'selected' : ''; ?>>
                                <?php echo my_htmlspecialchars($w['Beschreibung']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">
                    <?php echo $editTurner ? 'Speichern' : 'Hinzufügen'; ?>
                </button>
                <?php if ($editTurner): ?>
                    <a href="?VereinID=<?php echo my_htmlspecialchars($vereinID); ?>&hash=<?php echo urlencode($hash); ?>" class="btn btn-secondary">Abbrechen</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Tabelle der Turner -->
    <h2>Turner Liste</h2>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Vorname</th>
                <th>Nachname</th>
                <th>Geburtsdatum</th>
                <th>Geschlecht</th>
                <th>Wettkampf</th>
                <th>Riege</th>
                <th>MannschaftsID</th>
                <th>Aktionen</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($turnerList as $turner): ?>
                <tr>
                    <td><?php echo my_htmlspecialchars($turner['Vorname']); ?></td>
                    <td><?php echo my_htmlspecialchars($turner['Nachname']); ?></td>
                    <td>
                        <?php 
                        $date = date_create($turner['Geburtsdatum']);
                        echo $date ? date_format($date, 'd.m.Y') : '-';
                        ?>
                    </td>
                    <td><?php echo my_htmlspecialchars($turner['Geschlecht']); ?></td>
                    <td><?php echo my_htmlspecialchars($turner['Wettkampf']); ?></td>
                    <td><?php echo my_htmlspecialchars($turner['Riege']); ?></td>
                    <td><?php echo my_htmlspecialchars($turner['MannschaftsID']); ?></td>
                    <td>
                        <a href="?VereinID=<?php echo my_htmlspecialchars($vereinID); ?>&hash=<?php echo urlencode($hash); ?>&edit=<?php echo my_htmlspecialchars($turner['TurnerID']); ?>" class="btn btn-sm btn-warning">Bearbeiten</a>
                        <form method="post" style="display:inline-block;" onsubmit="return confirm('Wirklich löschen?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="TurnerID" value="<?php echo my_htmlspecialchars($turner['TurnerID']); ?>">
                            <button type="submit" class="btn btn-sm btn-danger">Löschen</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
