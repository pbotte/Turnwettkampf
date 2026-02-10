<?php

error_reporting(E_ALL);
ini_set('display_errors', 'On');

/*

Programmiere eine php-Seite für Mobil-Geräte optimiert und in einem modernen Design.
Sie soll auf eine SQL-Datenbank zugreifen, deren Struktur angehängt ist.

Der Seitentitel lautet: "Kari Wertungseingabe Neu/Bearbeiten".

der Seite wird via GET 3 Parameter (typ integer) übergeben:
- TurnerID
- RiegeID
- GeraetID
Falls diese drei Zahlen nicht übergeben wurden, dann die weitere Ausgabe stoppen und zur Seite "kari.php" verweisen.

Die Webseite soll ganz am Anfang, direkt nach dem Titel drei Informationen anzeigen:
- Nachname, Vorname, Jahrgang, Geschlecht in kurzform des Turners (aus Tabelle Turner via TurnerID und aus Tabelle Geschlechter via GeschlechtID)
- Riege: Riegen-Beschreibung (aus Tabelle Riegen via RiegenID)
- Gerät: Geraet.Beschreibung (aus Tabelle Geraete via GeraetID)

Sollte für den Kombination aus TurnerID und GeraetID bereits ein Eintrag in der Tabelle Wertungen vorhanden sein, so sollen diese Werte im folgenden bearbeitet werden, andernfalls erfolgt eine neue Eintragung.

Anschließend sollen die Eingabefelder kommen für:
- P-Stufe, D-Note, E1-Note, E2-Note, E3-Note, E4-Note, nA-Abzug ist eine Fließkommazahl, jedoch nur auf 2 Nachkommastellen genau.

Standard beim Eingaben der WErte soll sein:
- D-Note, E1-Note, E2-Note, E3-Note, E4-Note und P-Stufe NULL
- nA-Abzug ist 0,0

Die Werte für D-Note und nA-Abzug müssen vorhanden sein und dürfen nicht NULL sein.

Wenn die Seite auf dem Handy/Tablett geöffnet wird, soll bei der Eingabe in die Zahlen-Felder (P-Stufe, D-Note, E1-Note, E2-Note, E3-Note, E4-Note und nA-Abzug) vom Betriebsystem her eine Zahlen-Tastatur angezeigt werden.


Es sollen Knöpfe für das "Neu eintragen" bzw. "Bestehendes bearbeiten" angezeigt werden.

Die Webseite soll das Eintragen bzw. Ändern der Werte in der SQL-Tabelle übernehmen. Nach erfolgreicher SQL-Bearbeitung soll an die Seite "kari.php" mit den aktuellen RiegeID und GeraetID weitergeleitet werden.



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

$user_level_required=1;
include 'auth.php';
include 'config.php';
include 'includes/protokoll.php';



// Eigene Funktion um htmlspecialchars aufzurufen, bzw. "-" auszugeben wenn null übergeben wird.
function safe_html($string) {
    if ($string === null) {
        return "-";
    }
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Dezimalwerte aus Formularen robust einlesen (erlaubt Komma oder Punkt)
function normalize_decimal_input($value, $default = null) {
    if (!isset($value) || $value === '') {
        return $default;
    }
    $normalized = str_replace(',', '.', trim($value));
    return (float) $normalized;
}

// Prüfe, ob alle drei GET-Parameter (TurnerID, RiegeID, GeraetID) vorhanden sind
if (!isset($_GET['TurnerID'], $_GET['RiegeID'], $_GET['GeraetID'])) {
    header("Location: kari.php");
    exit;
}
$turnerID = (int) $_GET['TurnerID'];
$riegeID  = (int) $_GET['RiegeID'];
$geraetID = (int) $_GET['GeraetID'];

// Datenbankverbindung mittels PDO aufbauen
try {
    $dsn = "mysql:host=$dbHost;dbname=$dbName;charset=utf8";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Datenbankverbindung fehlgeschlagen: " . $e->getMessage());
}

// Wenn das Formular per POST abgeschickt wurde, Werte in die Datenbank eintragen bzw. aktualisieren
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Aus den Feldern werden die Werte als float bzw. null übernommen.
    $p_stufe = normalize_decimal_input($_POST['p_stufe'] ?? null, null);
    $d_note  = normalize_decimal_input($_POST['d_note'] ?? null, null);
    $e1_note = normalize_decimal_input($_POST['e1_note'] ?? null, null);
    $e2_note = normalize_decimal_input($_POST['e2_note'] ?? null, null);
    $e3_note = normalize_decimal_input($_POST['e3_note'] ?? null, null);
    $e4_note = normalize_decimal_input($_POST['e4_note'] ?? null, null);
    // nA-Abzug: Falls leer, Standard 0,0; ansonsten auf 2 Nachkommastellen runden.
    $na_abzug = normalize_decimal_input($_POST['na_abzug'] ?? null, 0.0);
    $na_abzug = round($na_abzug, 2);

    // Zuerst prüfen, ob bereits ein Eintrag für diese Kombination existiert
    $stmt = $pdo->prepare("SELECT * FROM Wertungen WHERE TurnerID = ? AND GeraetID = ?");
    $stmt->execute([$turnerID, $geraetID]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        // Update: Bestehenden Eintrag bearbeiten
        $sql = "UPDATE Wertungen SET `P-Stufe` = :p_stufe, `D-Note` = :d_note, `E1-Note` = :e1_note, `E2-Note` = :e2_note, `E3-Note` = :e3_note, `E4-Note` = :e4_note, `nA-Abzug` = :na_abzug 
                WHERE TurnerID = :turnerID AND GeraetID = :geraetID";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':p_stufe'  => $p_stufe,
            ':d_note'   => $d_note,
            ':e1_note'  => $e1_note,
            ':e2_note'  => $e2_note,
            ':e3_note'  => $e3_note,
            ':e4_note'  => $e4_note,
            ':na_abzug' => $na_abzug,
            ':turnerID' => $turnerID,
            ':geraetID' => $geraetID
        ]);
    } else {
        // Insert: Neuer Eintrag in der Tabelle Wertungen
        $sql = "INSERT INTO Wertungen (TurnerID, GeraetID, `P-Stufe`, `D-Note`, `E1-Note`, `E2-Note`, `E3-Note`, `E4-Note`, `nA-Abzug`)
                VALUES (:turnerID, :geraetID, :p_stufe, :d_note, :e1_note, :e2_note, :e3_note, :e4_note, :na_abzug)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':turnerID' => $turnerID,
            ':geraetID' => $geraetID,
            ':p_stufe'  => $p_stufe,
            ':d_note'   => $d_note,
            ':e1_note'  => $e1_note,
            ':e2_note'  => $e2_note,
            ':e3_note'  => $e3_note,
            ':e4_note'  => $e4_note,
            ':na_abzug' => $na_abzug
        ]);
    }

    Protokoll_Eintragen_erstellen("Wertungseintrag für TurnerID: $turnerID GeraetID: $geraetID $p_stufe, d_note: $d_note e1_note: $e1_note e2_note: $e2_note e3_note: $e3_note e4_note: $e4_note na_abzug: $na_abzug");

    // Erfolgreiche Eintragung: Weiterleitung an kari.php mit aktueller RiegeID und GeraetID
    header("Location: kari.php?RiegeID=" . $riegeID . "&GeraetID=" . $geraetID);
    exit;
}

// Informationen zum Turner (inklusive Geschlecht in Kurzform) abfragen
$stmt = $pdo->prepare("SELECT t.*, g.Beschreibung_kurz FROM Turner t 
                       JOIN Geschlechter g ON t.GeschlechtID = g.GeschlechtID 
                       WHERE t.TurnerID = ?");
$stmt->execute([$turnerID]);
$turner = $stmt->fetch(PDO::FETCH_ASSOC);

// Riege-Daten laden
$stmt = $pdo->prepare("SELECT * FROM Riegen WHERE RiegenID = ?");
$stmt->execute([$riegeID]);
$riege = $stmt->fetch(PDO::FETCH_ASSOC);

// Gerät-Daten laden
$stmt = $pdo->prepare("SELECT * FROM Geraete WHERE GeraetID = ?");
$stmt->execute([$geraetID]);
$geraet = $stmt->fetch(PDO::FETCH_ASSOC);

// Prüfen, ob bereits ein Eintrag in Wertungen vorliegt (zum Vorbefüllen des Formulars)
$stmt = $pdo->prepare("SELECT * FROM Wertungen WHERE TurnerID = ? AND GeraetID = ?");
$stmt->execute([$turnerID, $geraetID]);
$wertung = $stmt->fetch(PDO::FETCH_ASSOC);

// Falls vorhanden, die Werte für die Felder übernehmen – ansonsten Standardwerte
$p_stufe_val = $wertung['P-Stufe'] ?? '';
$d_note_val  = $wertung['D-Note'] ?? '';
$e1_note_val = $wertung['E1-Note'] ?? '';
$e2_note_val = $wertung['E2-Note'] ?? '';
$e3_note_val = $wertung['E3-Note'] ?? '';
$e4_note_val = $wertung['E4-Note'] ?? '';
$na_abzug_val = isset($wertung['nA-Abzug']) ? number_format($wertung['nA-Abzug'], 2, '.', '') : '0.00';

// Jahrgang aus dem Geburtsdatum ermitteln (nur das Jahr)
$jahrgang = isset($turner['Geburtsdatum']) ? date("Y", strtotime($turner['Geburtsdatum'])) : "-";
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kari Wertungseingabe Neu/Bearbeiten</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f6f7fb;
        }
        .page-wrap {
            max-width: 760px;
        }
        .info-card {
            background: #fff;
            border-radius: 16px;
            padding: 16px;
            box-shadow: 0 6px 18px rgba(0,0,0,0.08);
        }
        .form-control {
            font-size: 1.15rem;
            padding: 0.75rem 0.85rem;
        }
        .form-label {
            font-weight: 600;
        }
        .sticky-actions {
            position: sticky;
            bottom: 0;
            background: #f6f7fb;
            padding: 12px 0 4px;
        }
    </style>
</head>
<body>
<script src="menu.js"></script>
<div class="container my-4 page-wrap">
    <h1 class="h3 mb-3">Kari Wertungseingabe Neu/Bearbeiten</h1>

    <div class="info-card mb-4">
        <div class="mb-2">
            <strong>Turner: </strong>
            <?php
            echo safe_html($turner['Nachname'] ?? '-') . ", " .
                 safe_html($turner['Vorname'] ?? '-') . ", " .
                 safe_html($jahrgang) . ", " .
                 safe_html($turner['Beschreibung_kurz'] ?? '-');
            ?>
        </div>
        <div class="mb-1">
            <strong>Riege: </strong>
            <?php echo safe_html($riege['Beschreibung'] ?? '-'); ?>
        </div>
        <div>
            <strong>Gerät: </strong>
            <?php echo safe_html($geraet['Beschreibung'] ?? '-'); ?>
        </div>
    </div>

    <form method="post" id="wertung-form" autocomplete="off">
        <div class="row g-3">
            <div class="col-6">
                <label for="p_stufe" class="form-label">P-Stufe</label>
                <input type="text" inputmode="decimal" pattern="[0-9]+([.,][0-9]{1,2})?" enterkeyhint="next"
                       class="form-control" id="p_stufe" name="p_stufe" value="<?php echo safe_html($p_stufe_val); ?>">
            </div>
            <div class="col-6">
                <label for="d_note" class="form-label">D-Note</label>
                <input type="text" inputmode="decimal" pattern="[0-9]+([.,][0-9]{1,2})?" enterkeyhint="next"
                       class="form-control" required id="d_note" name="d_note" value="<?php echo safe_html($d_note_val); ?>">
            </div>
            <div class="col-6">
                <label for="e1_note" class="form-label">E1-Note</label>
                <input type="text" inputmode="decimal" pattern="[0-9]+([.,][0-9]{1,2})?" enterkeyhint="next"
                       class="form-control" id="e1_note" name="e1_note" value="<?php echo safe_html($e1_note_val); ?>">
            </div>
            <div class="col-6">
                <label for="e2_note" class="form-label">E2-Note</label>
                <input type="text" inputmode="decimal" pattern="[0-9]+([.,][0-9]{1,2})?" enterkeyhint="next"
                       class="form-control" id="e2_note" name="e2_note" value="<?php echo safe_html($e2_note_val); ?>">
            </div>
            <div class="col-6">
                <label for="e3_note" class="form-label">E3-Note</label>
                <input type="text" inputmode="decimal" pattern="[0-9]+([.,][0-9]{1,2})?" enterkeyhint="next"
                       class="form-control" id="e3_note" name="e3_note" value="<?php echo safe_html($e3_note_val); ?>">
            </div>
            <div class="col-6">
                <label for="e4_note" class="form-label">E4-Note</label>
                <input type="text" inputmode="decimal" pattern="[0-9]+([.,][0-9]{1,2})?" enterkeyhint="next"
                       class="form-control" id="e4_note" name="e4_note" value="<?php echo safe_html($e4_note_val); ?>">
            </div>
            <div class="col-12">
                <label for="na_abzug" class="form-label">nA-Abzug</label>
                <input type="text" inputmode="decimal" pattern="[0-9]+([.,][0-9]{1,2})?" enterkeyhint="done"
                       class="form-control" required id="na_abzug" name="na_abzug" value="<?php echo safe_html($na_abzug_val); ?>">
            </div>
        </div>

        <div class="sticky-actions">
            <?php
            // Je nachdem, ob bereits ein Eintrag vorhanden ist, wird der entsprechende Button angezeigt.
            if ($wertung) {
                echo '<button type="submit" class="btn btn-primary btn-lg w-100">Bestehendes bearbeiten</button>';
            } else {
                echo '<button type="submit" class="btn btn-primary btn-lg w-100">Neu eintragen</button>';
            }
            ?>
            <a class="btn btn-outline-secondary w-100 mt-2" href="kari.php?RiegeID=<?php echo $riegeID; ?>&GeraetID=<?php echo $geraetID; ?>">Zurück</a>
        </div>
    </form>
</div>
<script>
    (function() {
        const form = document.getElementById('wertung-form');
        if (!form) return;
        const inputs = Array.from(form.querySelectorAll('input[type="text"]'));

        // Fokus auf erstes leeres Feld setzen
        const firstEmpty = inputs.find((input) => !input.value);
        if (firstEmpty) {
            firstEmpty.focus();
        }

        // Vor dem Absenden Kommas in Punkte umwandeln
        form.addEventListener('submit', function() {
            inputs.forEach((input) => {
                if (input.value) {
                    input.value = input.value.replace(',', '.');
                }
            });
        });
    })();
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
