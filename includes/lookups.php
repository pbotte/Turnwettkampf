<?php

function lookup_options(PDO $pdo, string $table, string $idColumn, string $labelColumn, string $orderBy): array
{
    $allowed = [
        'Geschlechter' => ['GeschlechtID', 'Beschreibung', 'Beschreibung_kurz'],
        'Vereine' => ['VereinID', 'Vereinsname', 'Stadt'],
        'Wettkaempfe' => ['WettkampfID', 'Beschreibung'],
        'Wettkaempfe_Modi' => ['WettkampfmodusID', 'Beschreibung'],
        'Wettkaempfe_Modi_Sprung' => ['WettkampfSprungmodusID', 'Beschreibung'],
        'Riegen' => ['RiegenID', 'Beschreibung'],
        'Geraete' => ['GeraetID', 'Beschreibung'],
        'GeraeteTypen' => ['GeraeteTypID', 'Beschreibung', 'Reihenfolge'],
    ];

    if (!isset($allowed[$table])) {
        throw new InvalidArgumentException("Nicht erlaubte Lookup-Tabelle: {$table}");
    }

    foreach ([$idColumn, $labelColumn, $orderBy] as $column) {
        if (!in_array($column, $allowed[$table], true)) {
            throw new InvalidArgumentException("Nicht erlaubte Lookup-Spalte: {$column}");
        }
    }

    $stmt = $pdo->query("SELECT {$idColumn} AS id, {$labelColumn} AS label FROM {$table} ORDER BY {$orderBy}");
    return $stmt->fetchAll();
}

function rows_by_id(array $rows, string $idColumn): array
{
    $indexed = [];
    foreach ($rows as $row) {
        $indexed[$row[$idColumn]] = $row;
    }

    return $indexed;
}

