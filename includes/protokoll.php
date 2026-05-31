<?php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

function Protokoll_Eintragen_erstellen($MeinText): void
{
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $aktion = h($MeinText);

    try {
        $pdo = db();
        $stmt = $pdo->prepare("INSERT INTO `Protokoll` (`IP-Adresse`, `Aktion`) VALUES (:ip, :aktion)");
        $stmt->execute([
            ':ip' => $ipAddress,
            ':aktion' => $aktion,
        ]);
    } catch (PDOException $e) {
        error_log("Datenbankfehler: " . $e->getMessage());
        throw $e;
    }
}
