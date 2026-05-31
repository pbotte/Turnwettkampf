<?php

error_reporting(E_ALL);
ini_set('display_errors', 'On');

#include 'auth.php';
require_once 'includes/db.php';
require_once 'includes/helpers.php';
require_once 'includes/layout.php';
require_once 'includes/protokoll.php';

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

$pdo = db();

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
$pageTitle = "Turner Verwaltung für Verein " . h($verein['Vereinsname']);

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
render_header($pageTitle);
?>
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
                    echo '<option value="' . h($g['GeschlechtID']) . '" ' . $selected . '>' . h($g['Beschreibung']) . '</option>';
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
                    echo '<option value="' . h($w['WettkampfID']) . '">' . h($w['Beschreibung']) . '</option>';
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
                    <td><?php echo h($t['Vorname']); ?></td>
                    <td><?php echo h($t['Nachname']); ?></td>
                    <td><?php echo date("d.m.Y", strtotime($t['Geburtsdatum'])); ?></td>
                    <td>
                        <?php
                        // Geschlecht anhand der Geschlechter-Tabelle nachschlagen
                        foreach ($geschlechter as $g) {
                            if ($g['GeschlechtID'] == $t['GeschlechtID']) {
                                echo h($g['Beschreibung']);
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
                        echo h($wettkampfText);
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
                        echo h($riegeText);
                        ?>
                    </td>
                    <td><?php echo h($t['MannschaftsID']); ?></td>
                    <td><?php echo h($t['Wertungssumme']); ?></td>
                    <td><?php echo h($t['Platzierung']); ?></td>
                    <td>
                        <!-- Formular, um in den Bearbeitungsmodus zu wechseln -->
                        <form method="post" style="display:inline-block;">
                            <input type="hidden" name="action" value="edit_form">
                            <input type="hidden" name="TurnerID" value="<?php echo h($t['TurnerID']); ?>">
                            <button type="submit" class="btn btn-sm btn-warning">Bearbeiten</button>
                        </form>
                        <!-- Formular zum Löschen -->
                        <form method="post" style="display:inline-block;" onsubmit="return confirm('Soll dieser Turner wirklich gelöscht werden?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="TurnerID" value="<?php echo h($t['TurnerID']); ?>">
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
                            <input type="hidden" name="TurnerID" value="<?php echo h($t['TurnerID']); ?>">
                            <div class="col-md-2">
                                <label class="form-label">Vorname</label>
                                <input type="text" name="Vorname" class="form-control" value="<?php echo h($t['Vorname']); ?>" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Nachname</label>
                                <input type="text" name="Nachname" class="form-control" value="<?php echo h($t['Nachname']); ?>" required>
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
                                        echo '<option value="' . h($g['GeschlechtID']) . '" ' . $selected . '>' . h($g['Beschreibung']) . '</option>';
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
                                        echo '<option value="' . h($w['WettkampfID']) . '" ' . $selected . '>' . h($w['Beschreibung']) . '</option>';
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
<?php render_footer(); ?>
