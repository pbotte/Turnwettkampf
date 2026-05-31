<?php

function durchgang_group_label(array $durchgang): string
{
    $beschreibung = trim((string) ($durchgang['Beschreibung'] ?? ''));
    if (str_contains($beschreibung, ',')) {
        return trim(explode(',', $beschreibung, 2)[0]);
    }

    return $beschreibung !== '' ? $beschreibung : 'Durchgang';
}

function durchgang_slot_label(array $durchgang): string
{
    $beschreibung = trim((string) ($durchgang['Beschreibung'] ?? ''));
    if (str_contains($beschreibung, ',')) {
        return trim(explode(',', $beschreibung, 2)[1]);
    }

    return $beschreibung !== '' ? $beschreibung : 'Gerät';
}

function group_durchgaenge(array $durchgaenge): array
{
    $groups = [];
    foreach ($durchgaenge as $durchgang) {
        $groupLabel = durchgang_group_label($durchgang);
        if (!isset($groups[$groupLabel])) {
            $groups[$groupLabel] = [];
        }
        $groups[$groupLabel][] = $durchgang;
    }

    return $groups;
}

function riegen_label_parts(string $beschreibung): array
{
    $parts = explode(' - ', $beschreibung, 2);
    return [
        'title' => trim($parts[0]),
        'meta' => trim($parts[1] ?? ''),
    ];
}

function load_assignment_page_data(PDO $pdo): array
{
    $riegen = $pdo->query('SELECT * FROM Riegen ORDER BY Beschreibung ASC')->fetchAll();
    $durchgaenge = $pdo->query('SELECT * FROM Durchgaenge ORDER BY Reihenfolge ASC')->fetchAll();
    $geraete = $pdo->query(
        'SELECT g.*, gt.Beschreibung AS TypBeschreibung
         FROM Geraete g
         LEFT JOIN GeraeteTypen gt ON gt.GeraeteTypID = g.GeraeteTypID
         ORDER BY COALESCE(gt.Reihenfolge, 999), g.Beschreibung ASC'
    )->fetchAll();

    $geraeteById = [];
    foreach ($geraete as $geraet) {
        $geraeteById[$geraet['GeraetID']] = $geraet;
    }

    return [
        'riegen' => $riegen,
        'durchgaenge' => $durchgaenge,
        'durchgangGroups' => group_durchgaenge($durchgaenge),
        'geraete' => $geraete,
        'geraeteById' => $geraeteById,
        'assignments' => load_assignments($pdo),
    ];
}

function load_assignments(PDO $pdo): array
{
    $assignments = [];
    $stmt = $pdo->query('SELECT RiegenID, DurchgangID, GeraetID FROM Verbindung_Durchgaenge_Riegen_Geraete');
    while ($row = $stmt->fetch()) {
        $assignments[$row['RiegenID']][$row['DurchgangID']] = $row['GeraetID'];
    }

    return $assignments;
}

function save_assignments(PDO $pdo, array $postedAssignments): int
{
    $saved = 0;

    $pdo->beginTransaction();
    try {
        $pdo->exec('DELETE FROM Verbindung_Durchgaenge_Riegen_Geraete');
        $stmt = $pdo->prepare(
            'INSERT INTO Verbindung_Durchgaenge_Riegen_Geraete (RiegenID, DurchgangID, GeraetID) VALUES (?, ?, ?)'
        );

        foreach ($postedAssignments as $riegeId => $durchgaenge) {
            if (!is_array($durchgaenge)) {
                continue;
            }

            foreach ($durchgaenge as $durchgangId => $geraetId) {
                if ($geraetId === '' || $geraetId === null) {
                    continue;
                }

                $stmt->execute([(int) $riegeId, (int) $durchgangId, (int) $geraetId]);
                $saved++;
            }
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    return $saved;
}
