<?php

error_reporting(E_ALL);
ini_set('display_errors', 'On');



require_once 'includes/db.php';
require_once 'includes/helpers.php';
require_once 'includes/layout.php';

$pdo = db();

// Alle Wettkämpfe sortiert nach Beschreibung abfragen
$stmtWettkaempfe = $pdo->query("SELECT * FROM Wettkaempfe ORDER BY Beschreibung ASC");
$wettkaempfe = $stmtWettkaempfe->fetchAll(PDO::FETCH_ASSOC);
render_header('Wettkampf-Ergebnisse');
?>
  <div class="container my-4">
    <h1 class="mb-4">Wettkampf-Ergebnisse</h1>
    <?php foreach ($wettkaempfe as $wettkampf): ?>
      <div class="card mb-4">
        <div class="card-header">
          <h2><?php echo h($wettkampf['Beschreibung']); ?></h2>
        </div>
        <div class="card-body">
          <p>
            <strong>Geschlecht:</strong>
            <?php
              // Geschlecht über GeschlechtID nachschlagen
              $stmtGeschlecht = $pdo->prepare("SELECT Beschreibung FROM Geschlechter WHERE GeschlechtID = ?");
              $stmtGeschlecht->execute([$wettkampf['GeschlechtID']]);
              $geschlecht = $stmtGeschlecht->fetch(PDO::FETCH_ASSOC);
              echo h($geschlecht['Beschreibung']);
            ?>
          </p>
          <p><strong>NWertungen:</strong> <?php echo h($wettkampf['NWertungen']); ?></p>
          <p><strong>NGeraeteMax:</strong> <?php echo h($wettkampf['NGeraeteMax']); ?></p>
          
          <h3>Turner</h3>
          <?php
            // Alle Turner zum aktuellen Wettkampf abrufen und nach Platzierung sortieren
            $stmtTurner = $pdo->prepare(
              "SELECT t.*, g.Beschreibung_kurz, v.Vereinsname 
               FROM Turner t 
               LEFT JOIN Geschlechter g ON t.GeschlechtID = g.GeschlechtID 
               LEFT JOIN Vereine v ON t.VereinID = v.VereinID 
               WHERE t.WettkampfID = ? 
               ORDER BY t.Platzierung ASC"
            );
            $stmtTurner->execute([$wettkampf['WettkampfID']]);
            $turnerListe = $stmtTurner->fetchAll(PDO::FETCH_ASSOC);
          ?>
          <?php if (count($turnerListe) > 0): ?>
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>Platzierung</th>
                  <th>Nachname</th>
                  <th>Vorname</th>
                  <th>Jahrgang</th>
                  <th>Geschlecht</th>
                  <th>Verein</th>
                  <th>Wertungssumme</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($turnerListe as $turner): ?>
                  <tr>
                    <td><?php echo h($turner['Platzierung']); ?></td>
                    <td><?php echo h($turner['Nachname']); ?></td>
                    <td><?php echo h($turner['Vorname']); ?></td>
                    <td><?php echo date('Y', strtotime($turner['Geburtsdatum'])); ?></td>
                    <td><?php echo h($turner['Beschreibung_kurz']); ?></td>
                    <td><?php echo h($turner['Vereinsname']); ?></td>
                    <td><?php echo h($turner['Wertungssumme']); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php else: ?>
            <p>Keine Turner gefunden.</p>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php render_footer(); ?>
