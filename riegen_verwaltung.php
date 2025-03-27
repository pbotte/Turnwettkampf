<?php

error_reporting(E_ALL);
ini_set('display_errors', 'On');
?>
<?php /*
Programmiere eine php-Seite für Mobil-Geräte optimiert und in einem modernen Design.
Sie soll auf eine SQL-Datenbank zugreifen, deren Struktur angehängt ist.

Die Webseite soll die Bearbeitung der SQL-Tabelle "Riegen" ermöglichen. Möglich sein soll: 
- Neuen Eintrag hinzufügen,
- Bestehenden bearbeiten,
- bestehenden Löschen.

Alle Einträge aus der SQL-Tabelle sollen in einer Tabelle ausgegeben werden. Sortierung nach dem Alphabet.
*/?>
<?php include 'auth.php'; ?>
<?php include 'config.php'; ?>
<?php
// -----------------------
// Datenbankverbindung
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
// Löschen eines Eintrags
// -----------------------
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM Riegen WHERE RiegenID = ?");
    if ($stmt->execute([$id])) {
        $message = "Eintrag erfolgreich gelöscht.";
    } else {
        $message = "Fehler beim Löschen des Eintrags.";
    }
    header("Location: riegen_verwaltung.php?message=" . urlencode($message));
    exit;
}

// -----------------------
// Hinzufügen / Aktualisieren eines Eintrags
// -----------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $beschreibung = isset($_POST['beschreibung']) ? trim($_POST['beschreibung']) : '';

    // Neuer Eintrag
    if (isset($_POST['add'])) {
        $stmt = $pdo->prepare("INSERT INTO Riegen (Beschreibung) VALUES (?)");
        if ($stmt->execute([$beschreibung])) {
            $message = "Neuer Eintrag erfolgreich hinzugefügt.";
        } else {
            $message = "Fehler beim Hinzufügen des Eintrags.";
        }
    }
    // Bestehenden Eintrag aktualisieren
    elseif (isset($_POST['update'])) {
        $stmt = $pdo->prepare("UPDATE Riegen SET Beschreibung = ? WHERE RiegenID = ?");
        if ($stmt->execute([$beschreibung, $id])) {
            $message = "Eintrag erfolgreich aktualisiert.";
        } else {
            $message = "Fehler beim Aktualisieren des Eintrags.";
        }
    }
    header("Location: riegen_verwaltung.php?message=" . urlencode($message));
    exit;
}

// -----------------------
// Prüfen, ob Bearbeitungsmodus
// -----------------------
$editMode  = false;
$editEntry = ['RiegenID' => 0, 'Beschreibung' => ''];
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $editMode = true;
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM Riegen WHERE RiegenID = ?");
    $stmt->execute([$id]);
    $editEntry = $stmt->fetch();
    if (!$editEntry) {
        $message = "Eintrag nicht gefunden.";
        $editMode = false;
    }
}

// -----------------------
// Alle Einträge abrufen (alphabetisch sortiert)
// -----------------------
$stmt = $pdo->query("SELECT * FROM Riegen ORDER BY Beschreibung ASC");
$entries = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Riegen Verwaltung</title>
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
    <h1 class="mb-4">Riegen Verwaltung (<a href="/">zurück</a>)</h1>
    
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
            <form method="post" action="riegen_verwaltung.php">
                <?php if($editMode): ?>
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($editEntry['RiegenID']); ?>">
                <?php endif; ?>
                <div class="form-group">
                    <label for="beschreibung">Beschreibung</label>
                    <input type="text" class="form-control" id="beschreibung" name="beschreibung" value="<?php echo $editMode ? htmlspecialchars($editEntry['Beschreibung']) : ''; ?>" required>
                </div>
                <?php if($editMode): ?>
                    <button type="submit" name="update" class="btn btn-primary">Aktualisieren</button>
                    <a href="riegen_verwaltung.php" class="btn btn-secondary">Abbrechen</a>
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
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($entries) > 0): ?>
                    <?php foreach($entries as $entry): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($entry['RiegenID']); ?></td>
                            <td><?php echo htmlspecialchars($entry['Beschreibung']); ?></td>
                            <td>
                                <a href="riegen_verwaltung.php?action=edit&id=<?php echo $entry['RiegenID']; ?>" class="btn btn-sm btn-primary">Bearbeiten</a>
                                <a href="riegen_verwaltung.php?action=delete&id=<?php echo $entry['RiegenID']; ?>" onclick="return confirm('Eintrag wirklich löschen?');" class="btn btn-sm btn-danger">Löschen</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="3">Keine Einträge gefunden.</td></tr>
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
