<?php

error_reporting(E_ALL);
ini_set('display_errors', 'On');

require_once 'includes/layout.php';

// GeraeteID aus GET-Parameter holen (falls vorhanden)
$geraeteId = isset($_GET['GeraeteID']) && filter_var($_GET['GeraeteID'], FILTER_VALIDATE_INT)
    ? (int)$_GET['GeraeteID']
    : null;
$bildschirmId = isset($_GET['BildschirmID']) && filter_var($_GET['BildschirmID'], FILTER_VALIDATE_INT)
    ? (int)$_GET['BildschirmID']
    : null;

render_header('Öffentliche Anzeige', [
    'includeMenu' => false,
    'includeAppCss' => false,
    'includeBootstrap' => false,
    'extraCss' => <<<'CSS'
    body {
      margin: 0;
      padding: 0;
      background-color: white;
      transition: background-color 5s;
      font-family: Arial, sans-serif;
    }
    .container {
      position: relative;
      width: 100vw;
      height: 100vh;
    }
    .gesamtwertung {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      font-size: 25vw;
      font-weight: bold;
    }
    .top-left {
      position: absolute;
      top: 10px;
      left: 10px;
      font-size: 8vw;
    }
    .top-right {
      position: absolute;
      top: 10px;
      right: 10px;
      font-size: 2vw;
    }
    .bottom-left {
      position: absolute;
      bottom: 10px;
      left: 10px;
      font-size: 8vw;
    }
CSS,
]);
?>
  <div class="container">
    <!-- Links oben: Nachname, Vorname (Jahrgang) -->
    <div class="top-left"></div>
    <!-- Rechts oben: WettkampfBeschreibung -->
    <div class="top-right"></div>
    <!-- Mittig: Gesamtwertung -->
    <div class="gesamtwertung"></div>
    <!-- Links unten: GeraetBeschreibung -->
    <div class="bottom-left"></div>
  </div>

  <script>
    let previousData = null;
    let backgroundTimeout = null;
    // GeraeteID aus PHP einlesen
    const geraeteId = <?php echo $geraeteId !== null ? json_encode($geraeteId) : 'null'; ?>;
    const bildschirmId = <?php echo $bildschirmId !== null ? json_encode($bildschirmId) : 'null'; ?>;

    // Aktualisiert die Anzeige mit den neuen Daten
    function updateDisplay(data) {
      document.querySelector('.top-left').innerText = data.Nachname + ", " + data.Vorname + " (" + data.Jahrgang + ")";
      document.querySelector('.top-right').innerText = data.WettkampfBeschreibung;
      document.querySelector('.bottom-left').innerText = data.GeraetBeschreibung;
      // Gesamtwertung mit 2 Nachkommastellen formatieren (Dezimaltrennzeichen als Komma)
      let gesamtwertung = parseFloat(data.Gesamtwertung).toFixed(2).replace('.', ',');
      document.querySelector('.gesamtwertung').innerText = gesamtwertung;
    }

    // Prüft, ob die neuen Daten von den vorherigen abweichen und steuert den Hintergrundwechsel
    function checkForUpdates(newData) {
      if (!previousData || JSON.stringify(newData) !== JSON.stringify(previousData)) {
        // Neue Daten: Hintergrund sofort auf grün setzen
        document.body.style.transition = "none"; // Sofortiger Wechsel
        document.body.style.backgroundColor = "#00FF00"; // grün
        // Reflow erzwingen, damit der Farbwechsel sofort sichtbar wird
        void document.body.offsetWidth;
        // Transition wieder aktivieren für den anschließenden Fade-Effekt
        document.body.style.transition = "background-color 5s";

        // Vorherigen Timeout löschen, wenn vorhanden
        if (backgroundTimeout) clearTimeout(backgroundTimeout);
        // Nach 5 Sekunden (bei keiner weiteren Änderung) den Hintergrund wieder auf weiß überblenden
        backgroundTimeout = setTimeout(function(){
          document.body.style.backgroundColor = "white";
        }, 5000);
      }
      previousData = newData;
    }

    // Holt die Daten von der REST API
    function fetchData() {
      let url = 'get_last_wertung.php';
      if (geraeteId !== null) {
        url += '?GeraeteID=' + encodeURIComponent(geraeteId);
      }
      if (bildschirmId !== null) {
        url += '?BildschirmID=' + encodeURIComponent(bildschirmId);
      }
      fetch(url, { cache: 'no-store' })
        .then(response => response.json())
        .then(data => {
          updateDisplay(data);
          checkForUpdates(data);
        })
        .catch(error => {
          console.error("Fehler beim Abrufen der Daten: ", error);
        });
    }

    // Initialer Abruf und danach alle 5 Sekunden
    fetchData();
    setInterval(fetchData, 5000);
  </script>
<?php render_footer(['includeBootstrap' => false]); ?>
