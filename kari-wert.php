<?php

error_reporting(E_ALL);
ini_set('display_errors', 'On');

$user_level_required=1;
include 'auth.php';
require_once 'includes/db.php';
require_once 'includes/helpers.php';
require_once 'includes/layout.php';
require_once 'includes/protokoll.php';

function parse_decimal_input($value, $label, $required, &$errors, $default = null) {
    $raw = trim((string) ($value ?? ''));
    if ($raw === '') {
        if ($required) {
            $errors[] = $label . " muss ausgefüllt werden.";
            return null;
        }
        return $default;
    }

    $normalized = str_replace(',', '.', $raw);
    if (!preg_match('/^[0-9]+(\.[0-9]{1,2})?$/', $normalized)) {
        $errors[] = $label . " muss eine Zahl mit maximal zwei Nachkommastellen sein.";
        return null;
    }

    return round((float) $normalized, 2);
}

// Prüfe, ob alle drei GET-Parameter (TurnerID, RiegeID, GeraetID) vorhanden sind
if (!isset($_GET['TurnerID'], $_GET['RiegeID'], $_GET['GeraetID'])) {
    header("Location: kari.php");
    exit;
}
$turnerID = (int) $_GET['TurnerID'];
$riegeID  = (int) $_GET['RiegeID'];
$geraetID = (int) $_GET['GeraetID'];
$errors = [];

$pdo = db();

// Wenn das Formular per POST abgeschickt wurde, Werte in die Datenbank eintragen bzw. aktualisieren
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $redirectAction = $_POST['redirect_action'] ?? 'list';

    // Aus den Feldern werden geprüfte Dezimalwerte als float bzw. null übernommen.
    $p_stufe = parse_decimal_input($_POST['p_stufe'] ?? null, 'P-Stufe', false, $errors, null);
    $d_note  = parse_decimal_input($_POST['d_note'] ?? null, 'D-Note', true, $errors, null);
    $e1_note = parse_decimal_input($_POST['e1_note'] ?? null, 'E1-Note', false, $errors, null);
    $e2_note = parse_decimal_input($_POST['e2_note'] ?? null, 'E2-Note', false, $errors, null);
    $e3_note = parse_decimal_input($_POST['e3_note'] ?? null, 'E3-Note', false, $errors, null);
    $e4_note = parse_decimal_input($_POST['e4_note'] ?? null, 'E4-Note', false, $errors, null);
    $na_abzug = parse_decimal_input($_POST['na_abzug'] ?? null, 'nA-Abzug', true, $errors, 0.0);

    if (!$errors) {
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

        $stmt = $pdo->prepare("SELECT Vorname, Nachname FROM Turner WHERE TurnerID = ?");
        $stmt->execute([$turnerID]);
        $savedTurner = $stmt->fetch(PDO::FETCH_ASSOC);
        $savedName = trim(($savedTurner['Vorname'] ?? '') . ' ' . ($savedTurner['Nachname'] ?? ''));

        if ($redirectAction === 'next') {
            $stmt = $pdo->prepare("
                SELECT t.TurnerID
                FROM Turner t
                LEFT JOIN Wertungen w ON t.TurnerID = w.TurnerID AND w.GeraetID = ?
                WHERE t.RiegenID = ? AND w.WertungID IS NULL
                ORDER BY t.Nachname, t.Vorname
                LIMIT 1
            ");
            $stmt->execute([$geraetID, $riegeID]);
            $nextTurnerID = $stmt->fetchColumn();

            if ($nextTurnerID) {
                header("Location: kari-wert.php?TurnerID=" . (int) $nextTurnerID . "&RiegeID=" . $riegeID . "&GeraetID=" . $geraetID . "&saved=" . urlencode($savedName));
                exit;
            }
        }

        $doneParam = ($redirectAction === 'next') ? '&done=1' : '';
        header("Location: kari.php?RiegeID=" . $riegeID . "&GeraetID=" . $geraetID . "&saved=" . urlencode($savedName) . $doneParam);
        exit;
    }
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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $errors) {
    $p_stufe_val = $_POST['p_stufe'] ?? '';
    $d_note_val = $_POST['d_note'] ?? '';
    $e1_note_val = $_POST['e1_note'] ?? '';
    $e2_note_val = $_POST['e2_note'] ?? '';
    $e3_note_val = $_POST['e3_note'] ?? '';
    $e4_note_val = $_POST['e4_note'] ?? '';
    $na_abzug_val = $_POST['na_abzug'] ?? '';
}
$previousSavedName = trim($_GET['saved'] ?? '');
$showE3 = trim((string) $e1_note_val) !== '' && trim((string) $e2_note_val) !== '';
$showE4 = $showE3 && trim((string) $e3_note_val) !== '';

// Jahrgang aus dem Geburtsdatum ermitteln (nur das Jahr)
$jahrgang = isset($turner['Geburtsdatum']) ? date("Y", strtotime($turner['Geburtsdatum'])) : "-";
render_header('Kari Wertungseingabe Neu/Bearbeiten', [
    'extraCss' => "        .page-wrap {\n            max-width: 760px;\n        }\n        .form-control {\n            font-size: 1.15rem;\n            padding: 0.75rem 0.85rem;\n        }",
]);
?>
<div class="container my-4 page-wrap">
    <h1 class="h4 mb-3">Wertung eingeben</h1>

    <?php if ($previousSavedName !== ''): ?>
        <div class="alert alert-success alert-dismissible fade show py-2 auto-dismiss-alert" role="alert">
            Wertung gespeichert: <?php echo h($previousSavedName); ?>
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Schließen"></button>
        </div>
    <?php endif; ?>

    <?php if ($errors): ?>
        <div class="alert alert-danger" role="alert">
            <strong>Bitte Eingaben prüfen.</strong>
            <ul class="mb-0 mt-2">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo h($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="info-card mb-3">
        <div class="context-title">
            <?php echo h($turner['Nachname'] ?? '-') . ", " . h($turner['Vorname'] ?? '-'); ?>
        </div>
        <div class="context-meta">
            <?php echo h($jahrgang) . " · " . h($turner['Beschreibung_kurz'] ?? '-'); ?>
        </div>
        <div class="context-meta mt-2">
            <?php echo h($geraet['Beschreibung'] ?? '-'); ?> · <?php echo h($riege['Beschreibung'] ?? '-'); ?>
        </div>
    </div>

    <form method="post" id="wertung-form" autocomplete="off">
        <div class="preview-box mb-3">
            <div class="d-flex justify-content-between align-items-end gap-3">
                <div>
                    <div class="preview-detail">Vorschau Gesamtwertung</div>
                    <div class="preview-value" id="gesamt-preview">-</div>
                </div>
                <div class="preview-detail text-end" id="gesamt-detail">D + Ø E - nA</div>
            </div>
        </div>

        <div class="form-section mb-3">
          <div class="row g-3">
            <div class="col-6">
                <label for="p_stufe" class="form-label">P-Stufe</label>
                <input type="text" inputmode="decimal" pattern="[0-9]+([.,][0-9]{1,2})?" enterkeyhint="next"
                       class="form-control" id="p_stufe" name="p_stufe" value="<?php echo h($p_stufe_val); ?>">
            </div>
            <div class="col-6">
                <label for="d_note" class="form-label">D-Note</label>
                <input type="text" inputmode="decimal" pattern="[0-9]+([.,][0-9]{1,2})?" enterkeyhint="next"
                       class="form-control" required id="d_note" name="d_note" value="<?php echo h($d_note_val); ?>">
            </div>
          </div>
        </div>

        <div class="form-section mb-3">
          <div class="row g-3">
            <div class="col-6">
                <label for="e1_note" class="form-label">E1-Note</label>
                <input type="text" inputmode="decimal" pattern="[0-9]+([.,][0-9]{1,2})?" enterkeyhint="next"
                       class="form-control" id="e1_note" name="e1_note" value="<?php echo h($e1_note_val); ?>">
            </div>
            <div class="col-6">
                <label for="e2_note" class="form-label">E2-Note</label>
                <input type="text" inputmode="decimal" pattern="[0-9]+([.,][0-9]{1,2})?" enterkeyhint="next"
                       class="form-control" id="e2_note" name="e2_note" value="<?php echo h($e2_note_val); ?>">
            </div>
            <div class="col-6 <?php echo $showE3 ? '' : 'd-none'; ?>" id="e3_note_group">
                <label for="e3_note" class="form-label">E3-Note</label>
                <input type="text" inputmode="decimal" pattern="[0-9]+([.,][0-9]{1,2})?" enterkeyhint="next"
                       class="form-control" id="e3_note" name="e3_note" value="<?php echo h($e3_note_val); ?>">
            </div>
            <div class="col-6 <?php echo $showE4 ? '' : 'd-none'; ?>" id="e4_note_group">
                <label for="e4_note" class="form-label">E4-Note</label>
                <input type="text" inputmode="decimal" pattern="[0-9]+([.,][0-9]{1,2})?" enterkeyhint="next"
                       class="form-control" id="e4_note" name="e4_note" value="<?php echo h($e4_note_val); ?>">
            </div>
          </div>
        </div>

        <div class="form-section">
          <div class="row g-3">
            <div class="col-12">
                <label for="na_abzug" class="form-label">nA-Abzug</label>
                <input type="text" inputmode="decimal" pattern="[0-9]+([.,][0-9]{1,2})?" enterkeyhint="done"
                       class="form-control" required id="na_abzug" name="na_abzug" value="<?php echo h($na_abzug_val); ?>">
            </div>
          </div>
        </div>

        <div class="sticky-actions">
            <button type="submit" name="redirect_action" value="next" class="btn btn-success btn-lg w-100 mt-2">Speichern &amp; weiter</button>
            <button type="submit" name="redirect_action" value="list" class="btn btn-primary btn-lg w-100 mt-2">Speichern &amp; zur Liste</button>
            <a class="btn btn-outline-secondary w-100 mt-2" href="kari.php?RiegeID=<?php echo $riegeID; ?>&GeraetID=<?php echo $geraetID; ?>">Zurück</a>
        </div>
    </form>
</div>
<script>
	    (function() {
	        const form = document.getElementById('wertung-form');
	        if (!form) return;
	        const inputs = Array.from(form.querySelectorAll('input[type="text"]'));
	        const preview = document.getElementById('gesamt-preview');
	        const detail = document.getElementById('gesamt-detail');
	        const e3Group = document.getElementById('e3_note_group');
	        const e4Group = document.getElementById('e4_note_group');

	        function parseValue(id) {
	            const input = document.getElementById(id);
	            if (!input || input.value.trim() === '') return null;
	            const normalized = input.value.trim().replace(',', '.');
	            if (!/^[0-9]+(\.[0-9]{1,2})?$/.test(normalized)) return null;
	            return Number(normalized);
	        }

	        function formatValue(value) {
	            return value.toFixed(2).replace('.', ',');
	        }

	        function hasValue(id) {
	            const input = document.getElementById(id);
	            return !!input && input.value.trim() !== '';
	        }

	        function setGroupVisible(group, visible) {
	            if (!group) return;
	            group.classList.toggle('d-none', !visible);
	        }

	        function updateENoteVisibility(clearHidden) {
	            const e3Input = document.getElementById('e3_note');
	            const e4Input = document.getElementById('e4_note');
	            const showE3 = hasValue('e1_note') && hasValue('e2_note');
	            const showE4 = showE3 && hasValue('e3_note');

	            setGroupVisible(e3Group, showE3);
	            setGroupVisible(e4Group, showE4);

	            if (clearHidden && !showE3 && e3Input) {
	                e3Input.value = '';
	            }
	            if (clearHidden && !showE4 && e4Input) {
	                e4Input.value = '';
	            }
	        }

	        function updatePreview() {
	            const dNote = parseValue('d_note');
	            const naAbzug = parseValue('na_abzug') ?? 0;
	            const eNotes = ['e1_note', 'e2_note', 'e3_note', 'e4_note']
	                .map(parseValue)
	                .filter((value) => value !== null);

	            if (dNote === null) {
	                preview.textContent = '-';
	                detail.textContent = 'D + Ø E - nA';
	                return;
	            }

	            const eAverage = eNotes.length
	                ? eNotes.reduce((sum, value) => sum + value, 0) / eNotes.length
	                : 0;
	            const total = dNote + eAverage - naAbzug;
	            preview.textContent = formatValue(total);
	            detail.textContent = formatValue(dNote) + ' + ' + formatValue(eAverage) + ' - ' + formatValue(naAbzug);
	        }

	        // Fokus auf erstes leeres Feld setzen
	        const firstEmpty = inputs.find((input) => !input.value);
	        if (firstEmpty) {
	            firstEmpty.focus();
	        }

	        inputs.forEach((input) => input.addEventListener('input', function() {
	            updateENoteVisibility(true);
	            updatePreview();
	        }));
	        updateENoteVisibility(false);
	        updatePreview();

	        // Vor dem Absenden Kommas in Punkte umwandeln
        form.addEventListener('submit', function() {
            updateENoteVisibility(true);
            inputs.forEach((input) => {
                if (input.value) {
                    input.value = input.value.replace(',', '.');
                }
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.auto-dismiss-alert').forEach(function(alertElement) {
                setTimeout(function() {
                    const alert = bootstrap.Alert.getOrCreateInstance(alertElement);
                    alert.close();
                }, 3000);
            });
        });
    })();
</script>
<?php render_footer(); ?>
