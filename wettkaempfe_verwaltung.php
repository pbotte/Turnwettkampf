<?php

error_reporting(E_ALL);
ini_set('display_errors', 'On');

/*
Programmiere eine php-Seite für Mobil-Geräte optimiert und in einem modernen Design.
Sie soll auf eine SQL-Datenbank zugreifen, deren Struktur angehängt ist.

Die Webseite soll die Bearbeitung der SQL-Tabelle "Wettkaempfe" ermöglichen. Möglich sein soll: 
- Neuen Eintrag hinzufügen,
- Bestehenden bearbeiten,
- bestehenden Löschen.

Alle Einträge aus der SQL-Tabelle sollen in einer Tabelle ausgegeben werden. Sortierung nach dem Alphabet.

Die Spalten "WettkampfmodusID" und "WettkampfSprungmodusID" "GeschlechtID" sollen in den Tabellen "Wettkaempfe_Modi" und "Wettkaempfe_Modi_Sprung" und "Geschlechter" nachgeschlagen werden. Standard soll "gemischt" (also ID=1) sein.

Es sollen Dropdowns für die Nachgeschlagenen Werte verwendet werden.
Bootstrap und PDO sollen verwendet werden.
*/

include 'auth.php';
include 'config.php';
// -----------------------
// Datenbankverbindung mittels PDO
// -----------------------
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    echo "Verbindung fehlgeschlagen: " . $e->getMessage();
    exit;
}

// -----------------------
// Nachrichten-Handling
// -----------------------
$message = '';

// -----------------------
// Löschen eines Eintrags (per GET)
// -----------------------
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM Wettkaempfe WHERE WettkampfID = ?");
    if ($stmt->execute([$id])) {
        $message = "Eintrag erfolgreich gelöscht.";
    } else {
        $message = "Fehler beim Löschen des Eintrags.";
    }
    header("Location: wettkaempfe_verwaltung.php?message=" . urlencode($message));
    exit;
}

// -----------------------
// Hinzufügen / Aktualisieren eines Eintrags (per POST)
// -----------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $beschreibung = isset($_POST['beschreibung']) ? trim($_POST['beschreibung']) : '';
    $wettkampfmodusID = isset($_POST['wettkampfmodusID']) ? (int)$_POST['wettkampfmodusID'] : 1;
    $wettkampfsprungmodusID = isset($_POST['wettkampfsprungmodusID']) ? (int)$_POST['wettkampfsprungmodusID'] : 1;
    $geschlechtID = isset($_POST['geschlechtID']) ? (int)$_POST['geschlechtID'] : 1;

    // Neuer Eintrag
    if (isset($_POST['add'])) {
        $stmt = $pdo->prepare("INSERT INTO Wettkaempfe (Beschreibung, WettkampfmodusID, WettkampfSprungmodusID, GeschlechtID) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$beschreibung, $wettkampfmodusID, $wettkampfsprungmodusID, $geschlechtID])) {
            $message = "Neuer Eintrag erfolgreich hinzugefügt.";
        } else {
            $message = "Fehler beim Hinzufügen des Eintrags.";
        }
    }
    // Bestehenden Eintrag aktualisieren
    elseif (isset($_POST['update'])) {
        $stmt = $pdo->prepare("UPDATE Wettkaempfe SET Beschreibung = ?, WettkampfmodusID = ?, WettkampfSprungmodusID = ?, GeschlechtID = ? WHERE WettkampfID = ?");
        if ($stmt->execute([$beschreibung, $wettkampfmodusID, $wettkampfsprungmodusID, $geschlechtID, $id])) {
            $message = "Eintrag erfolgreich aktualisiert.";
        } else {
            $message = "Fehler beim Aktualisieren des Eintrags.";
        }
    }
    header("Location: wettkaempfe_verwaltung.php?message=" . urlencode($message));
    exit;
}

// -----------------------
// Bearbeitungsmodus prüfen
// -----------------------
$editMode  = false;
$editEntry = [
    'WettkampfID' => 0,
    'Beschreibung' => '',
    'WettkampfmodusID' => 1,
    'WettkampfSprungmodusID' => 1,
    'GeschlechtID' => 1
];
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $editMode = true;
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM Wettkaempfe WHERE WettkampfID = ?");
    $stmt->execute([$id]);
    $editEntry = $stmt->fetch();
    if (!$editEntry) {
        $message = "Eintrag nicht gefunden.";
        $editMode = false;
    }
}

// -----------------------
// Nachschlagen der Dropdown-Werte aus den Lookup-Tabellen
// -----------------------
$stmt = $pdo->query("SELECT * FROM Wettkaempfe_Modi ORDER BY Beschreibung ASC");
$wettkampfmodi = $stmt->fetchAll();

$stmt = $pdo->query("SELECT * FROM Wettkaempfe_Modi_Sprung ORDER BY Beschreibung ASC");
$wettkampfsprungmodi = $stmt->fetchAll();

$stmt = $pdo->query("SELECT * FROM Geschlechter ORDER BY Beschreibung ASC");
$geschlechter = $stmt->fetchAll();

// -----------------------
// Alle Wettkämpfe abrufen (alphabetisch sortiert nach Beschreibung)
// -----------------------
$sql = "SELECT W.*, 
               WM.Beschreibung AS modus, 
               WMS.Beschreibung AS sprungmodus, 
               G.Beschreibung AS geschlecht 
        FROM Wettkaempfe W 
        LEFT JOIN Wettkaempfe_Modi WM ON W.WettkampfmodusID = WM.WettkampfmodusID 
        LEFT JOIN Wettkaempfe_Modi_Sprung WMS ON W.WettkampfSprungmodusID = WMS.WettkampfSprungmodusID 
        LEFT JOIN Geschlechter G ON W.GeschlechtID = G.GeschlechtID 
        ORDER BY W.Beschreibung ASC";
$stmt    = $pdo->query($sql);
$entries = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Wettkämpfe Verwaltung</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS (CDN) -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">
    <style>
        body {
            padding-top: 20px;
            padding-bottom: 20px;
        }
    </style>
</head>
<body>
<div class="container">
    <h1 class="mb-4">Wettkämpfe Verwaltung (<a href="/">zurück</a>)</h1>
    
    <?php if(isset($_GET['message'])): ?>
        <div class="alert alert-info">
            <?php echo htmlspecialchars($_GET['message']); ?>
        </div>
    <?php endif; ?>

    <!-- Formular für Hinzufügen / Bearbeiten -->
    <div class="card mb-4">
        <div class="card-header">
            <?php echo $editMode ? "Eintrag bearbeiten" : "Neuen Eintrag hinzufügen"; ?>
        </div>
        <div class="card-body">
            <form method="post" action="wettkaempfe_verwaltung.php">
                <?php if($editMode): ?>
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($editEntry['WettkampfID']); ?>">
                <?php endif; ?>
                <div class="form-group">
                    <label for="beschreibung">Beschreibung</label>
                    <input type="text" class="form-control" id="beschreibung" name="beschreibung" value="<?php echo $editMode ? htmlspecialchars($editEntry['Beschreibung']) : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label for="wettkampfmodusID">Wettkampfmodus</label>
                    <select class="form-control" id="wettkampfmodusID" name="wettkampfmodusID" required>
                        <?php foreach($wettkampfmodi as $modus): ?>
                            <option value="<?php echo $modus['WettkampfmodusID']; ?>"
                                <?php if($editMode && $editEntry['WettkampfmodusID'] == $modus['WettkampfmodusID']) echo "selected"; ?>>
                                <?php echo htmlspecialchars($modus['Beschreibung']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="wettkampfsprungmodusID">Wettkampfsprungmodus</label>
                    <select class="form-control" id="wettkampfsprungmodusID" name="wettkampfsprungmodusID" required>
                        <?php foreach($wettkampfsprungmodi as $sprungmodus): ?>
                            <option value="<?php echo $sprungmodus['WettkampfSprungmodusID']; ?>"
                                <?php if($editMode && $editEntry['WettkampfSprungmodusID'] == $sprungmodus['WettkampfSprungmodusID']) echo "selected"; ?>>
                                <?php echo htmlspecialchars($sprungmodus['Beschreibung']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="geschlechtID">Geschlecht</label>
                    <select class="form-control" id="geschlechtID" name="geschlechtID" required>
                        <?php foreach($geschlechter as $geschlecht): ?>
                            <option value="<?php echo $geschlecht['GeschlechtID']; ?>"
                                <?php
                                    if ($editMode && $editEntry['GeschlechtID'] == $geschlecht['GeschlechtID']) {
                                        echo "selected";
                                    } elseif (!$editMode && $geschlecht['GeschlechtID'] == 1) {
                                        // Standard: "gemischt" (ID=1)
                                        echo "selected";
                                    }
                                ?>>
                                <?php echo htmlspecialchars($geschlecht['Beschreibung']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if($editMode): ?>
                    <button type="submit" name="update" class="btn btn-primary">Aktualisieren</button>
                    <a href="wettkaempfe_verwaltung.php" class="btn btn-secondary">Abbrechen</a>
                <?php else: ?>
                    <button type="submit" name="add" class="btn btn-success">Hinzufügen</button>
                <?php endif; ?>
            </form>
        </div>
    </div>
    
    <!-- Tabelle mit allen Einträgen -->
    <h2>Bestehende Einträge</h2>
    <div class="table-responsive">
        <table class="table table-striped table-bordered">
            <thead class="thead-dark">
                <tr>
                    <th>ID</th>
                    <th>Beschreibung</th>
                    <th>Wettkampfmodus</th>
                    <th>Wettkampfsprungmodus</th>
                    <th>Geschlecht</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($entries) > 0): ?>
                    <?php foreach($entries as $entry): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($entry['WettkampfID']); ?></td>
                            <td><?php echo htmlspecialchars($entry['Beschreibung']); ?></td>
                            <td><?php echo htmlspecialchars($entry['modus']); ?></td>
                            <td><?php echo htmlspecialchars($entry['sprungmodus']); ?></td>
                            <td><?php echo htmlspecialchars($entry['geschlecht']); ?></td>
                            <td>
                                <a href="wettkaempfe_verwaltung.php?action=edit&id=<?php echo $entry['WettkampfID']; ?>" class="btn btn-sm btn-primary">Bearbeiten</a>
                                <a href="wettkaempfe_verwaltung.php?action=delete&id=<?php echo $entry['WettkampfID']; ?>" onclick="return confirm('Eintrag wirklich löschen?');" class="btn btn-sm btn-danger">Löschen</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6">Keine Einträge gefunden.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Bootstrap JS und Abhängigkeiten -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
