<?php
// Eigene Funktion, die htmlspecialchars verwendet, aber bei einem null-Wert "-" zurückgibt.
function safe_html_again($string) {
    if ($string === null) {
        return "-";
    } else {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
}

// Funktion zum Eintragen eines Protokolleintrags in die Tabelle "Protokoll"
// Hinweis: In PHP sind Funktionsnamen ohne Bindestriche erlaubt, daher wird stattdessen ein Unterstrich verwendet.
function Protokoll_Eintragen_erstellen($MeinText) {
    // Globale Variablen für die Datenbankverbindung
    global $dbHost, $dbName, $dbUser, $dbPass;
    
    // Text mit safe_html_again überprüfen und gegebenenfalls säubern
    $sanitizedText = safe_html_again($MeinText);
    
    // Ermitteln der IP-Adresse des aufrufenden Clients
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    
    try {
        // Aufbau der PDO-Verbindung mit dem Charset "utf8"
        $dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8";
        $pdo = new PDO($dsn, $dbUser, $dbPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Vorbereitung des SQL-Statements. Spaltennamen mit Sonderzeichen (Bindestrich) werden in Backticks gesetzt.
        $stmt = $pdo->prepare("INSERT INTO `Protokoll` (`IP-Adresse`, `Aktion`) VALUES (:ip, :aktion)");
        
        // Binden der Parameter und Ausführen des Statements
        $stmt->bindParam(':ip', $ipAddress);
        $stmt->bindParam(':aktion', $sanitizedText);
        $stmt->execute();
    } catch (PDOException $e) {
        // Fehlerbehandlung: Fehlermeldung in das Error-Log schreiben und Exception weiterwerfen
        error_log("Datenbankfehler: " . $e->getMessage());
        throw $e;
    }
}
?>
