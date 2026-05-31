<?php

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/lookups.php';
require_once __DIR__ . '/../includes/assignment_repository.php';

function assert_same($expected, $actual, string $message): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, $message . PHP_EOL);
        fwrite(STDERR, 'Expected: ' . var_export($expected, true) . PHP_EOL);
        fwrite(STDERR, 'Actual:   ' . var_export($actual, true) . PHP_EOL);
        exit(1);
    }
}

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$pdo->exec("
    CREATE TABLE Riegen (
        RiegenID INTEGER PRIMARY KEY,
        Beschreibung TEXT NOT NULL
    );
    CREATE TABLE Durchgaenge (
        DurchgangID INTEGER PRIMARY KEY,
        Reihenfolge INTEGER,
        Beschreibung TEXT
    );
    CREATE TABLE GeraeteTypen (
        GeraeteTypID INTEGER PRIMARY KEY,
        Beschreibung TEXT NOT NULL,
        Reihenfolge INTEGER NOT NULL
    );
    CREATE TABLE Geraete (
        GeraetID INTEGER PRIMARY KEY,
        GeraeteTypID INTEGER,
        Beschreibung TEXT NOT NULL
    );
    CREATE TABLE Verbindung_Durchgaenge_Riegen_Geraete (
        VDurchgaengeRiegenID INTEGER PRIMARY KEY AUTOINCREMENT,
        RiegenID INTEGER NOT NULL,
        DurchgangID INTEGER NOT NULL,
        GeraetID INTEGER NOT NULL
    );
    CREATE TABLE Geschlechter (
        GeschlechtID INTEGER PRIMARY KEY,
        Beschreibung TEXT NOT NULL,
        Beschreibung_kurz TEXT NOT NULL
    );
");

$pdo->exec("
    INSERT INTO Riegen (RiegenID, Beschreibung) VALUES
        (1, '1. DG Riege 1 - Mix Jungs Teresa'),
        (2, '1. DG Riege 2 - Mix Elke A');
    INSERT INTO Durchgaenge (DurchgangID, Reihenfolge, Beschreibung) VALUES
        (10, 1, '1. DG, 1. Gerät: Mix'),
        (11, 2, '1. DG, 2. Gerät: Mix'),
        (20, 3, '2. DG, 1. Gerät: Mix');
    INSERT INTO GeraeteTypen (GeraeteTypID, Beschreibung, Reihenfolge) VALUES
        (1, 'Boden', 1),
        (2, 'Sprung', 2);
    INSERT INTO Geraete (GeraetID, GeraeteTypID, Beschreibung) VALUES
        (100, 1, 'Boden 1'),
        (200, 2, 'Sprung 1');
    INSERT INTO Geschlechter (GeschlechtID, Beschreibung, Beschreibung_kurz) VALUES
        (3, 'weiblich', 'w'),
        (2, 'männlich', 'm');
");

assert_same('1. DG', durchgang_group_label(['Beschreibung' => '1. DG, 1. Gerät: Mix']), 'Durchgang-Gruppe wird falsch gelesen.');
assert_same('1. Gerät: Mix', durchgang_slot_label(['Beschreibung' => '1. DG, 1. Gerät: Mix']), 'Durchgang-Position wird falsch gelesen.');
assert_same(['title' => '1. DG Riege 1', 'meta' => 'Mix Jungs Teresa'], riegen_label_parts('1. DG Riege 1 - Mix Jungs Teresa'), 'Riegenlabel wird falsch zerlegt.');

$saved = save_assignments($pdo, [
    1 => [10 => 100, 11 => '', 20 => 200],
    2 => [10 => null, 11 => 200],
]);
assert_same(3, $saved, 'Zuordnungen speichern nicht die erwartete Anzahl.');

$assignments = load_assignments($pdo);
assert_same(100, (int) $assignments[1][10], 'Zuordnung Riege 1 / Durchgang 10 fehlt.');
assert_same(200, (int) $assignments[1][20], 'Zuordnung Riege 1 / Durchgang 20 fehlt.');
assert_same(200, (int) $assignments[2][11], 'Zuordnung Riege 2 / Durchgang 11 fehlt.');

$pageData = load_assignment_page_data($pdo);
assert_same(['1. DG', '2. DG'], array_keys($pageData['durchgangGroups']), 'Durchgänge werden nicht korrekt gruppiert.');
assert_same(2, count($pageData['riegen']), 'Riegen werden nicht geladen.');
assert_same('Boden 1', $pageData['geraeteById'][100]['Beschreibung'], 'Geräte-Index wird nicht geladen.');

$geschlechter = lookup_options($pdo, 'Geschlechter', 'GeschlechtID', 'Beschreibung', 'Beschreibung');
assert_same('männlich', $geschlechter[0]['label'], 'Lookup-Sortierung funktioniert nicht.');
assert_same(null, nullable_int(''), 'Leere IDs werden nicht zu null.');
assert_same(42, nullable_int('42'), 'Numerische IDs werden nicht korrekt umgewandelt.');
assert_same('31.05.2026', format_date_de('2026-05-31'), 'Datumsformatierung funktioniert nicht.');

echo "Core test OK\n";
